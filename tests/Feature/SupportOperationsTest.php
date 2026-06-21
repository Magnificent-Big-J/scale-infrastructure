<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Deployment;
use App\Models\Incident;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use App\Models\SupportTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_support_operations_seeder_creates_agreements_tickets_and_incidents(): void
    {
        $this->seed();

        $this->assertSame(3, SupportAgreement::count());
        $this->assertSame(3, SupportTicket::count());
        $this->assertSame(3, Incident::count());
        $this->assertDatabaseHas('support_agreements', ['code' => 'AGR-AURECON-STRATEGIC', 'status' => 'review']);
        $this->assertDatabaseHas('support_tickets', ['reference' => 'TCK-1002', 'severity' => 'high']);
        $this->assertDatabaseHas('incidents', ['reference' => 'INC-2001', 'status' => 'investigating']);
    }

    public function test_admin_can_list_support_agreements(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/support-agreements')
            ->assertOk()
            ->assertJsonFragment(['code' => 'AGR-NALA-PRIORITY'])
            ->assertJsonPath('options.statuses.0.value', 'draft')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_create_support_agreement(): void
    {
        $this->seed();

        $client = Client::where('code', 'AURECON-PMO')->firstOrFail();
        $tier = SupportTier::where('code', 'SUPPORT-STRATEGIC')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/support-agreements', [
                'client_id' => $client->id,
                'support_tier_id' => $tier->id,
                'code' => 'AGR-AURECON-EXEC',
                'name' => 'Executive support - Aurecon PMO',
                'monthly_fee' => 72000,
                'included_hours' => 48,
                'response_sla_hours' => 6,
                'starts_on' => '2026-07-01',
                'status' => 'active',
                'notes' => 'Executive support agreement for escalation coverage.',
            ])
            ->assertCreated()
            ->assertJsonFragment(['code' => 'AGR-AURECON-EXEC']);

        $this->assertDatabaseHas('support_agreements', ['code' => 'AGR-AURECON-EXEC']);
    }

    public function test_admin_can_list_support_tickets(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/support-tickets')
            ->assertOk()
            ->assertJsonFragment(['reference' => 'TCK-1002'])
            ->assertJsonPath('options.severities.0.value', 'low')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_support_tickets_index_returns_monthly_throughput(): void
    {
        $this->seed();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/support-tickets')
            ->assertOk()
            ->assertJsonStructure(['throughput' => [['period', 'opened', 'resolved']]]);

        // Six monthly buckets, and every seeded ticket is counted as opened.
        $throughput = $response->json('throughput');
        $this->assertCount(6, $throughput);
        $this->assertSame(3, array_sum(array_column($throughput, 'opened')));
    }

    public function test_admin_can_create_support_ticket(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();
        $deployment = Deployment::where('name', 'ScaleLens Staging')->firstOrFail();
        $agreement = SupportAgreement::where('code', 'AGR-NALA-PRIORITY')->firstOrFail();
        $supportUser = User::where('email', 'support@codescaletech.test')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/support-tickets', [
                'client_id' => $client->id,
                'deployment_id' => $deployment->id,
                'support_agreement_id' => $agreement->id,
                'assigned_user_id' => $supportUser->id,
                'reference' => 'TCK-1100',
                'subject' => 'Queue retry review',
                'category' => 'operations',
                'severity' => 'medium',
                'status' => 'open',
                'hours_logged' => 0.5,
                'opened_at' => '2026-06-17 08:00:00',
                'summary' => 'Client requested a retry audit after staging queue warnings.',
            ])
            ->assertCreated()
            ->assertJsonFragment(['reference' => 'TCK-1100']);

        $this->assertDatabaseHas('support_tickets', ['reference' => 'TCK-1100']);
    }

    public function test_admin_can_list_incidents(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/incidents')
            ->assertOk()
            ->assertJsonFragment(['reference' => 'INC-2001'])
            ->assertJsonPath('options.statuses.0.value', 'open')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_create_incident(): void
    {
        $this->seed();

        $client = Client::where('code', 'AURECON-PMO')->firstOrFail();
        $deployment = Deployment::where('name', 'ScaleLens Production')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/incidents', [
                'client_id' => $client->id,
                'deployment_id' => $deployment->id,
                'reference' => 'INC-2100',
                'title' => 'Production report latency',
                'severity' => 'high',
                'status' => 'investigating',
                'started_at' => '2026-06-17 09:30:00',
                'root_cause' => 'Pending investigation.',
            ])
            ->assertCreated()
            ->assertJsonFragment(['reference' => 'INC-2100']);

        $this->assertDatabaseHas('incidents', ['reference' => 'INC-2100']);
    }

    public function test_resolving_a_ticket_stamps_resolved_at(): void
    {
        $this->seed();

        $ticket = SupportTicket::where('reference', 'TCK-1002')->firstOrFail();
        $supportUser = User::where('email', 'support@codescaletech.test')->firstOrFail();
        $this->assertNull($ticket->resolved_at);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/support-tickets/{$ticket->id}", [
                'client_id' => $ticket->client_id,
                'assigned_user_id' => $supportUser->id,
                'reference' => $ticket->reference,
                'subject' => $ticket->subject,
                'severity' => $ticket->severity->value,
                'status' => 'resolved',
                'hours_logged' => 3.5,
            ])
            ->assertOk()
            ->assertJsonFragment(['status' => 'resolved']);

        $ticket->refresh();
        $this->assertNotNull($ticket->resolved_at);
        $this->assertSame($supportUser->id, $ticket->assigned_user_id);
    }

    public function test_admin_can_update_support_agreement(): void
    {
        $this->seed();

        $agreement = SupportAgreement::where('code', 'AGR-NALA-PRIORITY')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/support-agreements/{$agreement->id}", [
                'client_id' => $agreement->client_id,
                'support_tier_id' => $agreement->support_tier_id,
                'code' => $agreement->code,
                'name' => $agreement->name,
                'monthly_fee' => 41000,
                'included_hours' => $agreement->included_hours,
                'status' => 'active',
            ])
            ->assertOk();

        $this->assertDatabaseHas('support_agreements', ['id' => $agreement->id, 'monthly_fee' => 41000, 'status' => 'active']);
    }

    public function test_resolving_an_incident_stamps_resolved_at(): void
    {
        $this->seed();

        $incident = Incident::where('reference', 'INC-2001')->firstOrFail();
        $this->assertNull($incident->resolved_at);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/incidents/{$incident->id}", [
                'client_id' => $incident->client_id,
                'reference' => $incident->reference,
                'title' => $incident->title,
                'severity' => $incident->severity->value,
                'status' => 'resolved',
                'resolution_summary' => 'Mitigated by scaling the worker pool.',
            ])
            ->assertOk()
            ->assertJsonFragment(['status' => 'resolved']);

        $this->assertNotNull($incident->refresh()->resolved_at);
    }

    public function test_user_without_update_permission_cannot_update_ticket(): void
    {
        $this->seed();

        $ticket = SupportTicket::where('reference', 'TCK-1002')->firstOrFail();
        $sales = User::where('email', 'sales@codescaletech.test')->firstOrFail();

        $this->actingAs($sales, 'sanctum')
            ->patchJson("/api/v1/support-tickets/{$ticket->id}", [
                'client_id' => $ticket->client_id,
                'reference' => $ticket->reference,
                'subject' => $ticket->subject,
                'severity' => $ticket->severity->value,
                'status' => 'closed',
            ])
            ->assertForbidden();
    }
}
