<?php

namespace App\Contracts;

use App\Models\Deployment;
use App\Models\InfrastructureAsset;
use App\Models\MonitoringCheck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DeploymentServiceInterface
{
    public function paginateDeployments(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $environment = null): LengthAwarePaginator;

    public function paginateInfrastructureAssets(int $perPage = 15, ?string $search = null, ?string $type = null): LengthAwarePaginator;

    public function paginateMonitoringChecks(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    public function createDeployment(array $data): Deployment;

    public function updateDeployment(Deployment $deployment, array $data): Deployment;

    public function archiveDeployment(Deployment $deployment): void;

    public function createInfrastructureAsset(Deployment $deployment, array $data): InfrastructureAsset;

    public function createMonitoringCheck(Deployment $deployment, array $data): MonitoringCheck;
}
