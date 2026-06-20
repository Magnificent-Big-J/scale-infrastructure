<?php

namespace App\Contracts;

use App\Models\AutomationRun;
use App\Models\ChangeRequest;
use App\Models\ProvisioningTemplate;
use App\Models\Release;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReleaseOperationsServiceInterface
{
    public function paginateReleases(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $deploymentId = null): LengthAwarePaginator;

    public function paginateChangeRequests(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    public function paginateTemplates(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function paginateAutomationRuns(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    public function createRelease(array $data): Release;

    public function updateRelease(Release $release, array $data): Release;

    public function approveRelease(Release $release): Release;

    public function deployRelease(Release $release): Release;

    public function rollbackRelease(Release $release, ?string $notes): Release;

    public function createChangeRequest(array $data): ChangeRequest;

    public function updateChangeRequest(ChangeRequest $changeRequest, array $data): ChangeRequest;

    public function decideChangeRequest(ChangeRequest $changeRequest, bool $approved): ChangeRequest;

    public function createTemplate(array $data): ProvisioningTemplate;

    public function updateTemplate(ProvisioningTemplate $template, array $data): ProvisioningTemplate;

    public function createAutomationRun(array $data): AutomationRun;
}
