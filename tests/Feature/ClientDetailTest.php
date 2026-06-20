<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Deployment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_client_show_returns_summary(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.code', 'NALA-PROJECTS');

        $response->assertJsonPath('summary.contracts_count', 1)
            ->assertJsonPath('summary.active_agreements', 1)
            ->assertJsonPath('summary.overdue_count', 0);

        // INV-2026-0001 paid (excluded), INV-2026-0002 partially paid leaves 90000 outstanding.
        $this->assertEquals(90000, $response->json('summary.outstanding_total'));
        $this->assertGreaterThanOrEqual(1, $response->json('summary.open_tickets'));
    }

    public function test_contracts_index_filters_by_client(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/contracts?client_id={$client->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment(['code' => 'CON-NALA-2026'])
            ->assertJsonMissing(['code' => 'CON-AURECON-2026']);
    }

    public function test_invoices_index_filters_by_client(): void
    {
        $this->seed();

        $client = Client::where('code', 'AURECON-PMO')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/invoices?client_id={$client->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment(['number' => 'INV-2026-0003']);
    }

    public function test_support_tickets_index_filters_by_client(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/support-tickets?client_id={$client->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment(['reference' => 'TCK-1001']);
    }

    public function test_deployments_index_filters_by_client(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();
        $expected = Deployment::where('client_id', $client->id)->count();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/v1/deployments?client_id={$client->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', $expected);
    }
}
