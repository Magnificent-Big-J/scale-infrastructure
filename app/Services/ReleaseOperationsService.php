<?php

namespace App\Services;

use App\Contracts\ReleaseOperationsServiceInterface;
use App\Enums\ChangeRequestStatus;
use App\Enums\ReleaseStatus;
use App\Models\AutomationRun;
use App\Models\ChangeRequest;
use App\Models\ProvisioningTemplate;
use App\Models\Release;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleaseOperationsService implements ReleaseOperationsServiceInterface
{
    public function paginateReleases(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $deploymentId = null): LengthAwarePaginator
    {
        return Release::query()
            ->with(['deployment.client', 'changeRequest', 'approver', 'deployer'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($deploymentId, fn ($query) => $query->where('deployment_id', $deploymentId))
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function paginateChangeRequests(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return ChangeRequest::query()
            ->with(['deployment', 'client', 'approver'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw("case when status = 'submitted' then 0 else 1 end")
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function paginateTemplates(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return ProvisioningTemplate::query()
            ->withCount('automationRuns')
            ->search($search)
            ->orderByRaw('case when is_active then 0 else 1 end')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateAutomationRuns(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return AutomationRun::query()
            ->with(['provisioningTemplate', 'deployment', 'client', 'changeRequest', 'triggeredBy'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('started_at')
            ->paginate($perPage);
    }

    public function createRelease(array $data): Release
    {
        return DB::transaction(function () use ($data) {
            $data['status'] ??= ReleaseStatus::Draft->value;
            $release = Release::query()->create($data);

            $this->log('releases', $release, 'created', 'Recorded release');

            return $release->load(['deployment.client', 'changeRequest', 'approver', 'deployer']);
        });
    }

    public function updateRelease(Release $release, array $data): Release
    {
        return DB::transaction(function () use ($release, $data) {
            $release->fill($data)->save();

            $this->log('releases', $release, 'updated', 'Updated release', ['changes' => array_keys($release->getChanges())]);

            return $release->refresh()->load(['deployment.client', 'changeRequest', 'approver', 'deployer']);
        });
    }

    public function approveRelease(Release $release): Release
    {
        $this->guard(
            in_array($release->status, [ReleaseStatus::Draft, ReleaseStatus::PendingApproval], true),
            'status',
            'Only draft or pending releases can be approved.',
        );

        if ($release->change_request_id !== null) {
            $this->guard(
                $release->changeRequest?->isApproved() === true,
                'change_request_id',
                'The linked change request must be approved before the release can be approved.',
            );
        }

        return DB::transaction(function () use ($release) {
            $release->forceFill([
                'status' => ReleaseStatus::Approved,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])->save();

            $this->log('releases', $release, 'approved', 'Approved release');

            return $release->refresh()->load(['deployment.client', 'changeRequest', 'approver', 'deployer']);
        });
    }

    public function deployRelease(Release $release): Release
    {
        $this->guard(
            $release->status === ReleaseStatus::Approved,
            'status',
            'Only approved releases can be deployed.',
        );

        return DB::transaction(function () use ($release) {
            $release->forceFill([
                'status' => ReleaseStatus::Deployed,
                'deployed_by' => auth()->id(),
                'deployed_at' => now(),
            ])->save();

            $release->deployment?->forceFill(['current_version' => $release->version])->save();

            $this->log('releases', $release, 'deployed', 'Deployed release');

            return $release->refresh()->load(['deployment.client', 'changeRequest', 'approver', 'deployer']);
        });
    }

    public function rollbackRelease(Release $release, ?string $notes): Release
    {
        $this->guard(
            $release->status === ReleaseStatus::Deployed,
            'status',
            'Only deployed releases can be rolled back.',
        );

        return DB::transaction(function () use ($release, $notes) {
            $release->forceFill([
                'status' => ReleaseStatus::RolledBack,
                'rolled_back_at' => now(),
                'rollback_notes' => $notes,
            ])->save();

            $this->log('releases', $release, 'rolled_back', 'Rolled back release', ['notes' => $notes]);

            return $release->refresh()->load(['deployment.client', 'changeRequest', 'approver', 'deployer']);
        });
    }

    public function createChangeRequest(array $data): ChangeRequest
    {
        return DB::transaction(function () use ($data) {
            $changeRequest = ChangeRequest::query()->create([
                ...$data,
                'status' => $data['status'] ?? ChangeRequestStatus::Draft->value,
                'requested_by' => $data['requested_by'] ?? auth()->id(),
            ]);

            $this->log('change_requests', $changeRequest, 'created', 'Logged change request');

            return $changeRequest->load(['deployment', 'client', 'approver']);
        });
    }

    public function updateChangeRequest(ChangeRequest $changeRequest, array $data): ChangeRequest
    {
        return DB::transaction(function () use ($changeRequest, $data) {
            $changeRequest->fill($data)->save();

            $this->log('change_requests', $changeRequest, 'updated', 'Updated change request', ['changes' => array_keys($changeRequest->getChanges())]);

            return $changeRequest->refresh()->load(['deployment', 'client', 'approver']);
        });
    }

    public function decideChangeRequest(ChangeRequest $changeRequest, bool $approved): ChangeRequest
    {
        $this->guard(
            ! in_array($changeRequest->status, [ChangeRequestStatus::Approved, ChangeRequestStatus::Rejected], true),
            'status',
            'This change request has already been decided.',
        );

        return DB::transaction(function () use ($changeRequest, $approved) {
            $changeRequest->forceFill([
                'status' => $approved ? ChangeRequestStatus::Approved : ChangeRequestStatus::Rejected,
                'approved_by' => auth()->id(),
                'approved_at' => $approved ? now() : null,
            ])->save();

            $this->log('change_requests', $changeRequest, $approved ? 'approved' : 'rejected', $approved ? 'Approved change request' : 'Rejected change request');

            return $changeRequest->refresh()->load(['deployment', 'client', 'approver']);
        });
    }

    public function createTemplate(array $data): ProvisioningTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = ProvisioningTemplate::query()->create($data);

            $this->log('provisioning_templates', $template, 'created', 'Created provisioning template');

            return $template->loadCount('automationRuns');
        });
    }

    public function updateTemplate(ProvisioningTemplate $template, array $data): ProvisioningTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            $template->fill($data)->save();

            $this->log('provisioning_templates', $template, 'updated', 'Updated provisioning template', ['changes' => array_keys($template->getChanges())]);

            return $template->refresh()->loadCount('automationRuns');
        });
    }

    public function createAutomationRun(array $data): AutomationRun
    {
        $changeRequest = isset($data['change_request_id'])
            ? ChangeRequest::query()->find($data['change_request_id'])
            : null;

        $this->guard(
            $changeRequest?->isApproved() === true,
            'change_request_id',
            'Automation runs require an approved change request and must not bypass change approval.',
        );

        return DB::transaction(function () use ($data, $changeRequest) {
            $run = AutomationRun::query()->create([
                ...$data,
                'client_id' => $data['client_id'] ?? $changeRequest?->client_id,
                'deployment_id' => $data['deployment_id'] ?? $changeRequest?->deployment_id,
                'started_at' => $data['started_at'] ?? now(),
                'triggered_by' => auth()->id(),
            ]);

            $this->log('automation_runs', $run, 'created', 'Recorded automation run', ['status' => $run->status->value]);

            return $run->load(['provisioningTemplate', 'deployment', 'client', 'changeRequest', 'triggeredBy']);
        });
    }

    private function guard(bool $passes, string $field, string $message): void
    {
        if (! $passes) {
            throw ValidationException::withMessages([$field => [$message]]);
        }
    }

    private function log(string $domain, Model $subject, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity($domain)
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
