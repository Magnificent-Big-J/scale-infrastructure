<?php

namespace App\Services;

use App\Contracts\OperationsDashboardServiceInterface;
use App\Enums\DeploymentStatus;
use App\Enums\IncidentStatus;
use App\Enums\MonitoringCheckStatus;
use App\Models\Deployment;
use App\Models\Incident;
use App\Models\InfrastructureAsset;
use App\Models\MonitoringCheck;

class OperationsDashboardService implements OperationsDashboardServiceInterface
{
    public function metrics(): array
    {
        $deploymentsByStatus = $this->countByStatus(Deployment::query()->toBase(), DeploymentStatus::values());
        $monitoringByStatus = $this->countByStatus(MonitoringCheck::query()->toBase(), MonitoringCheckStatus::values());

        return [
            'deployments_total' => array_sum($deploymentsByStatus),
            'deployments_by_status' => $deploymentsByStatus,
            'active_deployments' => $deploymentsByStatus[DeploymentStatus::Active->value] ?? 0,
            'degraded_deployments' => $deploymentsByStatus[DeploymentStatus::Degraded->value] ?? 0,
            'monitoring_checks_total' => array_sum($monitoringByStatus),
            'monitoring_by_status' => $monitoringByStatus,
            'failing_checks' => $monitoringByStatus[MonitoringCheckStatus::Failing->value] ?? 0,
            'warning_checks' => $monitoringByStatus[MonitoringCheckStatus::Warning->value] ?? 0,
            'infrastructure_assets' => InfrastructureAsset::query()->count(),
            'open_incidents' => Incident::query()
                ->whereNotIn('status', [IncidentStatus::Resolved->value, IncidentStatus::Closed->value])
                ->count(),
        ];
    }

    /**
     * @param  list<string>  $statuses
     * @return array<string, int>
     */
    private function countByStatus($query, array $statuses): array
    {
        $counts = $query->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $result = [];

        foreach ($statuses as $status) {
            $result[$status] = (int) ($counts[$status] ?? 0);
        }

        return $result;
    }
}
