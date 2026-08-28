<?php

namespace App\Services;

use App\Contracts\DeploymentServiceInterface;
use App\Enums\IncidentStatus;
use App\Enums\MonitoringCheckStatus;
use App\Enums\SupportSeverity;
use App\Models\Deployment;
use App\Models\Incident;
use App\Models\InfrastructureAsset;
use App\Models\MonitoringCheck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeploymentService implements DeploymentServiceInterface
{
    public function paginateDeployments(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $environment = null, ?string $clientId = null): LengthAwarePaginator
    {
        return Deployment::query()
            ->with(['client', 'product', 'package'])
            ->withCount(['infrastructureAssets', 'monitoringChecks'])
            ->search($search)
            ->withStatus($status)
            ->withEnvironment($environment)
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByRaw("case when environment = 'production' then 0 else 1 end")
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateInfrastructureAssets(int $perPage = 15, ?string $search = null, ?string $type = null): LengthAwarePaginator
    {
        return InfrastructureAsset::query()
            ->with(['deployment.client'])
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('provider', 'like', "%{$search}%")
                        ->orWhere('region', 'like', "%{$search}%")
                        ->orWhereHas('deployment', fn (Builder $deploymentQuery) => $deploymentQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateMonitoringChecks(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return MonitoringCheck::query()
            ->with(['deployment.client'])
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('target', 'like', "%{$search}%")
                        ->orWhere('check_type', 'like', "%{$search}%")
                        ->orWhereHas('deployment', fn (Builder $deploymentQuery) => $deploymentQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->orderByRaw("case when status = 'failing' then 0 when status = 'warning' then 1 else 2 end")
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createDeployment(array $data): Deployment
    {
        return DB::transaction(function () use ($data) {
            $deployment = Deployment::query()->create($data);

            $this->log($deployment, 'created', 'Created deployment');

            return $deployment->load(['client', 'product', 'package'])->loadCount(['infrastructureAssets', 'monitoringChecks']);
        });
    }

    public function updateDeployment(Deployment $deployment, array $data): Deployment
    {
        return DB::transaction(function () use ($deployment, $data) {
            $deployment->fill($data);
            $deployment->save();

            $this->log($deployment, 'updated', 'Updated deployment', ['changes' => array_keys($deployment->getChanges())]);

            return $deployment->refresh()->load(['client', 'product', 'package'])->loadCount(['infrastructureAssets', 'monitoringChecks']);
        });
    }

    public function archiveDeployment(Deployment $deployment): void
    {
        DB::transaction(function () use ($deployment) {
            $deployment->delete();

            $this->log($deployment, 'archived', 'Archived deployment');
        });
    }

    public function createInfrastructureAsset(Deployment $deployment, array $data): InfrastructureAsset
    {
        return DB::transaction(function () use ($deployment, $data) {
            $asset = $deployment->infrastructureAssets()->create($data);

            $this->log($deployment, 'infrastructure_created', 'Created infrastructure asset', ['asset_id' => $asset->id]);

            return $asset->load(['deployment.client']);
        });
    }

    public function updateInfrastructureAsset(InfrastructureAsset $asset, array $data): InfrastructureAsset
    {
        return DB::transaction(function () use ($asset, $data) {
            $asset->fill($data);
            $asset->save();

            $this->log($asset->deployment, 'infrastructure_updated', 'Updated infrastructure asset', ['asset_id' => $asset->id]);

            return $asset->refresh()->load(['deployment.client']);
        });
    }

    public function createMonitoringCheck(Deployment $deployment, array $data): MonitoringCheck
    {
        return DB::transaction(function () use ($deployment, $data) {
            $check = $deployment->monitoringChecks()->create($data);

            $this->log($deployment, 'monitoring_created', 'Created monitoring check', ['check_id' => $check->id]);

            return $check->load(['deployment.client']);
        });
    }

    public function updateMonitoringCheck(MonitoringCheck $check, array $data): MonitoringCheck
    {
        return DB::transaction(function () use ($check, $data) {
            $wasFailing = $check->status === MonitoringCheckStatus::Failing;

            $check->fill($data);
            $check->save();

            $this->log($check->deployment, 'monitoring_updated', 'Updated monitoring check', ['check_id' => $check->id]);

            if (! $wasFailing && $check->status === MonitoringCheckStatus::Failing) {
                $this->openIncidentForFailingCheck($check);
            } elseif ($wasFailing && $check->status === MonitoringCheckStatus::Passing) {
                // Only a return to Passing counts as recovered - a Failing
                // check moving to Warning/Paused is still unhealthy and
                // should keep its incident open.
                $this->resolveIncidentForRecoveredCheck($check);
            }

            return $check->refresh()->load(['deployment.client']);
        });
    }

    /**
     * Opens one incident per failing check, not per failure event: a check
     * that keeps failing (or flaps) should not pile up duplicate open
     * incidents. Severity/title are fixed, reasonable defaults - actual
     * triage, escalation, and notification routing are an on-call process
     * decision outside this service's scope.
     */
    private function openIncidentForFailingCheck(MonitoringCheck $check): void
    {
        $hasOpenIncident = Incident::query()
            ->where('monitoring_check_id', $check->id)
            ->whereNotIn('status', [IncidentStatus::Resolved, IncidentStatus::Closed])
            ->exists();

        if ($hasOpenIncident) {
            return;
        }

        $incident = Incident::query()->create([
            'client_id' => $check->deployment->client_id,
            'deployment_id' => $check->deployment_id,
            'monitoring_check_id' => $check->id,
            'reference' => $this->uniqueIncidentReference(),
            'title' => "{$check->name} check is failing",
            'severity' => SupportSeverity::Medium,
            'status' => IncidentStatus::Open,
            'started_at' => now(),
        ]);

        $this->log($check->deployment, 'incident_auto_opened', 'Auto-opened incident for failing check', [
            'check_id' => $check->id,
            'incident_id' => $incident->id,
        ]);
    }

    private function resolveIncidentForRecoveredCheck(MonitoringCheck $check): void
    {
        $incident = Incident::query()
            ->where('monitoring_check_id', $check->id)
            ->whereNotIn('status', [IncidentStatus::Resolved, IncidentStatus::Closed])
            ->first();

        if (! $incident) {
            return;
        }

        $incident->status = IncidentStatus::Resolved;
        $incident->resolved_at = now();
        $incident->save();

        $this->log($check->deployment, 'incident_auto_resolved', 'Auto-resolved incident for recovered check', [
            'check_id' => $check->id,
            'incident_id' => $incident->id,
        ]);
    }

    private function uniqueIncidentReference(): string
    {
        do {
            $reference = 'INC-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Incident::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function log(Deployment $deployment, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('deployments')
            ->performedOn($deployment)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
