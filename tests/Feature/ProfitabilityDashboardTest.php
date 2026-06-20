<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\IncidentStatus;
use App\Enums\SupportTicketStatus;
use App\Exports\ReportExport;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\Incident;
use App\Models\ProfitabilityRecord;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProfitabilityDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_seeder_creates_profitability_records_with_computed_profit(): void
    {
        $this->seed();

        $this->assertSame(5, ProfitabilityRecord::count());

        $record = ProfitabilityRecord::query()
            ->whereHas('client', fn ($query) => $query->where('code', 'NALA-PROJECTS'))
            ->where('period', '2026-06')
            ->firstOrFail();

        // revenue 58000 - cost (4000+16000+1500+1500=23000) = 35000 profit, 60.34% margin.
        $this->assertSame('35000.00', $record->profit);
        $this->assertSame('60.34', $record->margin);
    }

    public function test_creating_profitability_record_computes_profit_and_margin(): void
    {
        $this->seed();

        $client = Client::where('code', 'KOPANO-CONSULTING')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/profitability-records', [
                'client_id' => $client->id,
                'period' => '2026-07',
                'revenue' => 100000,
                'hosting_cost' => 5000,
                'labour_cost' => 30000,
                'monitoring_cost' => 2000,
                'other_cost' => 3000,
            ])
            ->assertCreated()
            ->assertJsonFragment(['profit' => '60000.00', 'margin' => '60.00']);

        $this->assertDatabaseHas('profitability_records', ['period' => '2026-07', 'profit' => 60000]);
    }

    public function test_profitability_index_returns_summary(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/profitability-records')
            ->assertOk()
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('summary.records', 5);
    }

    public function test_executive_dashboard_aggregates(): void
    {
        $this->seed();

        $expectedClients = Client::query()->where('status', ClientStatus::Active->value)->count();
        $expectedTickets = SupportTicket::query()
            ->whereNotIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value])
            ->count();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/dashboard/executive')
            ->assertOk()
            ->assertJsonPath('data.active_clients', $expectedClients)
            ->assertJsonPath('data.open_tickets', $expectedTickets)
            ->assertJsonPath('data.mrr', 103000);
    }

    public function test_operations_dashboard_aggregates(): void
    {
        $this->seed();

        $expectedDeployments = Deployment::query()->count();
        $expectedIncidents = Incident::query()
            ->whereNotIn('status', [IncidentStatus::Resolved->value, IncidentStatus::Closed->value])
            ->count();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/dashboard/operations')
            ->assertOk()
            ->assertJsonPath('data.deployments_total', $expectedDeployments)
            ->assertJsonPath('data.open_incidents', $expectedIncidents);
    }

    public function test_reports_index_lists_all_types(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment(['value' => 'profitability_summary']);
    }

    public function test_report_show_returns_columns_and_rows(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/reports/profitability_summary')
            ->assertOk()
            ->assertJsonPath('data.row_count', 5)
            ->assertJsonPath('data.columns.0', 'Client');
    }

    public function test_report_export_downloads_a_spreadsheet(): void
    {
        $this->seed();
        Excel::fake();

        $this->actingAs($this->admin(), 'sanctum')
            ->get('/api/v1/reports/finance_summary/export')
            ->assertOk();

        Excel::assertDownloaded(
            'finance_summary-'.now()->format('Y-m-d').'.xlsx',
            fn (ReportExport $export) => $export->headings() === ['Client', 'Invoiced', 'Paid', 'Outstanding']
        );
    }

    public function test_unknown_report_type_returns_not_found(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/reports/not_a_report')
            ->assertNotFound();
    }

    public function test_user_without_profitability_permission_cannot_create_record(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();
        $support = User::where('email', 'support@codescaletech.test')->firstOrFail();

        $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/profitability-records', [
                'client_id' => $client->id,
                'period' => '2026-08',
                'revenue' => 1000,
            ])
            ->assertForbidden();
    }

    public function test_user_without_export_permission_cannot_export_report(): void
    {
        $this->seed();

        $operations = User::where('email', 'operations@codescaletech.test')->firstOrFail();

        // operations has reports.view but not reports.export.
        $this->actingAs($operations, 'sanctum')
            ->get('/api/v1/reports/finance_summary')
            ->assertOk();

        $this->actingAs($operations, 'sanctum')
            ->get('/api/v1/reports/finance_summary/export')
            ->assertForbidden();
    }
}
