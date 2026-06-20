<?php

namespace App\Services;

use App\Contracts\ExecutiveDashboardServiceInterface;
use App\Contracts\FinanceDashboardServiceInterface;
use App\Enums\ClientStatus;
use App\Enums\DeploymentStatus;
use App\Enums\IncidentStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\Incident;
use App\Models\SupportTicket;

class ExecutiveDashboardService implements ExecutiveDashboardServiceInterface
{
    public function __construct(private readonly FinanceDashboardServiceInterface $finance) {}

    public function metrics(): array
    {
        $finance = $this->finance->metrics();

        $activeClients = Client::query()->where('status', ClientStatus::Active->value)->count();

        $averageHealth = (float) Client::query()
            ->where('status', '!=', ClientStatus::Archived->value)
            ->avg('health_score');

        return [
            'active_clients' => $activeClients,
            'at_risk_clients' => Client::query()->where('status', ClientStatus::AtRisk->value)->count(),
            'active_deployments' => Deployment::query()->where('status', DeploymentStatus::Active->value)->count(),
            'mrr' => $finance['mrr'],
            'arr' => $finance['arr'],
            'outstanding_total' => $finance['outstanding_total'],
            'open_tickets' => SupportTicket::query()
                ->whereNotIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value])
                ->count(),
            'open_incidents' => Incident::query()
                ->whereNotIn('status', [IncidentStatus::Resolved->value, IncidentStatus::Closed->value])
                ->count(),
            'average_client_health' => round($averageHealth, 1),
        ];
    }
}
