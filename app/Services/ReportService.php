<?php

namespace App\Services;

use App\Contracts\ReportServiceInterface;
use App\Enums\ClientStatus;
use App\Enums\IncidentStatus;
use App\Enums\MonitoringCheckStatus;
use App\Enums\ReportType;
use App\Enums\SupportAgreementStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\ProfitabilityRecord;

class ReportService implements ReportServiceInterface
{
    public function generate(ReportType $type): array
    {
        [$columns, $rows] = match ($type) {
            ReportType::ClientPortfolio => $this->clientPortfolio(),
            ReportType::OperationsHealth => $this->operationsHealth(),
            ReportType::SupportSummary => $this->supportSummary(),
            ReportType::FinanceSummary => $this->financeSummary(),
            ReportType::ProfitabilitySummary => $this->profitabilitySummary(),
        };

        return [
            'type' => $type->value,
            'title' => $type->label(),
            'description' => $type->description(),
            'generated_at' => now()->toIso8601String(),
            'columns' => $columns,
            'rows' => $rows,
            'row_count' => count($rows),
        ];
    }

    public function toCsv(ReportType $type): string
    {
        $report = $this->generate($type);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $report['columns'], ',', '"', '');

        foreach ($report['rows'] as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function clientPortfolio(): array
    {
        $rows = Client::query()
            ->with('package.product')
            ->where('status', '!=', ClientStatus::Archived->value)
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => [
                $client->name,
                $client->code,
                $client->status?->label(),
                $client->tier?->label(),
                $client->health_score,
                $client->package ? trim("{$client->package->product?->name} {$client->package->name}") : '-',
            ])
            ->all();

        return [['Client', 'Code', 'Status', 'Tier', 'Health', 'Package'], $rows];
    }

    private function operationsHealth(): array
    {
        $rows = Deployment::query()
            ->with('client')
            ->withCount([
                'infrastructureAssets',
                'monitoringChecks as failing_checks_count' => fn ($query) => $query->where('status', MonitoringCheckStatus::Failing->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Deployment $deployment) => [
                $deployment->name,
                $deployment->client?->name,
                $deployment->environment?->label(),
                $deployment->status?->label(),
                $deployment->infrastructure_assets_count,
                $deployment->failing_checks_count,
            ])
            ->all();

        return [['Deployment', 'Client', 'Environment', 'Status', 'Infra assets', 'Failing checks'], $rows];
    }

    private function supportSummary(): array
    {
        $rows = Client::query()
            ->where('status', '!=', ClientStatus::Archived->value)
            ->withCount([
                'supportAgreements as active_agreements_count' => fn ($query) => $query->where('status', SupportAgreementStatus::Active->value),
                'supportTickets as open_tickets_count' => fn ($query) => $query->whereNotIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value]),
                'incidents as open_incidents_count' => fn ($query) => $query->whereNotIn('status', [IncidentStatus::Resolved->value, IncidentStatus::Closed->value]),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => [
                $client->name,
                $client->active_agreements_count,
                $client->open_tickets_count,
                $client->open_incidents_count,
            ])
            ->all();

        return [['Client', 'Active agreements', 'Open tickets', 'Open incidents'], $rows];
    }

    private function financeSummary(): array
    {
        $rows = Client::query()
            ->where('status', '!=', ClientStatus::Archived->value)
            ->withSum('invoices as invoiced_total', 'amount')
            ->withSum('invoices as paid_total', 'amount_paid')
            ->orderBy('name')
            ->get()
            ->map(function (Client $client) {
                $invoiced = (float) ($client->invoiced_total ?? 0);
                $paid = (float) ($client->paid_total ?? 0);

                return [
                    $client->name,
                    number_format($invoiced, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format(max(0, $invoiced - $paid), 2, '.', ''),
                ];
            })
            ->all();

        return [['Client', 'Invoiced', 'Paid', 'Outstanding'], $rows];
    }

    private function profitabilitySummary(): array
    {
        $rows = ProfitabilityRecord::query()
            ->with('client')
            ->orderByDesc('period')
            ->orderBy('client_id')
            ->get()
            ->map(fn (ProfitabilityRecord $record) => [
                $record->client?->name,
                $record->period,
                number_format((float) $record->revenue, 2, '.', ''),
                number_format($record->totalCost(), 2, '.', ''),
                number_format((float) $record->profit, 2, '.', ''),
                number_format((float) $record->margin, 2, '.', ''),
            ])
            ->all();

        return [['Client', 'Period', 'Revenue', 'Cost', 'Profit', 'Margin %'], $rows];
    }
}
