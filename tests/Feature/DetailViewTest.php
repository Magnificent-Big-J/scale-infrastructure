<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Deployment;
use App\Models\Invoice;
use App\Models\Release;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailViewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_support_ticket_show_returns_record_with_relations(): void
    {
        $this->seed();

        $ticket = SupportTicket::where('reference', 'TCK-1001')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/support-tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.reference', 'TCK-1001')
            ->assertJsonPath('data.client_name', fn ($name) => is_string($name) && $name !== '');
    }

    public function test_deployment_show_returns_nested_collections(): void
    {
        $this->seed();

        $deployment = Deployment::query()->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/deployments/{$deployment->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'infrastructure_assets',
                    'monitoring_checks',
                    'releases',
                ],
            ]);
    }

    public function test_release_show_returns_record(): void
    {
        $this->seed();

        $release = Release::query()->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/releases/{$release->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $release->id)
            ->assertJsonStructure(['data' => ['version', 'deployment_name', 'status']]);
    }

    public function test_contract_show_returns_nested_collections(): void
    {
        $this->seed();

        $contract = Contract::query()->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/contracts/{$contract->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'billing_records', 'invoices']]);
    }

    public function test_invoice_show_returns_payments(): void
    {
        $this->seed();

        $invoice = Invoice::query()->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'number', 'payments']]);
    }

    public function test_support_ticket_show_requires_permission(): void
    {
        $this->seed();

        $ticket = SupportTicket::where('reference', 'TCK-1001')->firstOrFail();

        // A user without support_tickets.view (finance) is forbidden.
        $finance = User::where('email', 'finance@codescaletech.test')->firstOrFail();

        $this->actingAs($finance, 'sanctum')
            ->getJson("/api/v1/support-tickets/{$ticket->id}")
            ->assertForbidden();
    }
}
