<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalIntakeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function deploymentWithToken(): Deployment
    {
        $deployment = Deployment::query()->firstOrFail();
        $deployment->regenerateIntakeToken();

        return $deployment;
    }

    public function test_admin_can_generate_and_revoke_an_intake_token(): void
    {
        $this->seed();
        $deployment = Deployment::query()->firstOrFail();

        $token = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertOk()
            ->json('data.intake_token');

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'intake_token' => $token]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertOk();

        $this->assertDatabaseHas('deployments', ['id' => $deployment->id, 'intake_token' => null]);
    }

    public function test_external_system_can_create_a_ticket_with_a_valid_token(): void
    {
        $this->seed();
        $deployment = $this->deploymentWithToken();

        $response = $this->withHeader('X-Intake-Token', $deployment->intake_token)
            ->postJson('/api/intake/tickets', [
                'subject' => 'Checkout page returning 500',
                'summary' => 'Reported by the client monitoring hook.',
                'severity' => 'high',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $reference = $response->json('data.reference');

        $this->assertDatabaseHas('support_tickets', [
            'reference' => $reference,
            'client_id' => $deployment->client_id,
            'deployment_id' => $deployment->id,
            'source' => 'intake',
            'severity' => 'high',
            'status' => 'open',
        ]);
    }

    public function test_intake_defaults_severity_to_low(): void
    {
        $this->seed();
        $deployment = $this->deploymentWithToken();

        $reference = $this->withHeader('X-Intake-Token', $deployment->intake_token)
            ->postJson('/api/intake/tickets', ['subject' => 'Minor copy tweak'])
            ->assertCreated()
            ->json('data.reference');

        $this->assertDatabaseHas('support_tickets', ['reference' => $reference, 'severity' => 'low']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->seed();

        $this->withHeader('X-Intake-Token', 'dit_not-a-real-token')
            ->postJson('/api/intake/tickets', ['subject' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->seed();

        $this->postJson('/api/intake/tickets', ['subject' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_intake_requires_a_subject(): void
    {
        $this->seed();
        $deployment = $this->deploymentWithToken();

        $this->withHeader('X-Intake-Token', $deployment->intake_token)
            ->postJson('/api/intake/tickets', ['summary' => 'no subject'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');
    }
}
