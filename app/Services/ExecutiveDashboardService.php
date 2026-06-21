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
use Illuminate\Support\Collection;

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
            'distributions' => [
                'deployments_by_status' => $this->distribution(Deployment::query()->pluck('status'), DeploymentStatus::cases()),
                'tickets_by_status' => $this->distribution(SupportTicket::query()->pluck('status'), SupportTicketStatus::cases()),
            ],
        ];
    }

    /**
     * Build a donut-ready distribution from a set of stored status values,
     * ordered by the enum and dropping empty buckets.
     *
     * @param  Collection<int, mixed>  $values
     * @param  array<int, \BackedEnum&\UnitEnum>  $cases
     * @return array{labels: list<string>, series: list<int>}
     */
    private function distribution($values, array $cases): array
    {
        $counts = $values
            ->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value)
            ->countBy()
            ->all();

        $labels = [];
        $series = [];

        foreach ($cases as $case) {
            $count = $counts[$case->value] ?? 0;

            if ($count === 0) {
                continue;
            }

            $labels[] = method_exists($case, 'label') ? $case->label() : $case->name;
            $series[] = $count;
        }

        return ['labels' => $labels, 'series' => $series];
    }
}
