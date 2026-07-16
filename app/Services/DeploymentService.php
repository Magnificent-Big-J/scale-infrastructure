<?php

namespace App\Services;

use App\Contracts\DeploymentServiceInterface;
use App\Models\Deployment;
use App\Models\InfrastructureAsset;
use App\Models\MonitoringCheck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
            $check->fill($data);
            $check->save();

            $this->log($check->deployment, 'monitoring_updated', 'Updated monitoring check', ['check_id' => $check->id]);

            return $check->refresh()->load(['deployment.client']);
        });
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
