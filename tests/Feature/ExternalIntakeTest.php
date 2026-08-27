<?php

namespace Tests\Feature;

use App\Contracts\IntakeCredentialServiceInterface;
use App\Models\Deployment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ExternalIntakeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function issueToken(Deployment $deployment, ?string $expiresAt = null): string
    {
        $issued = App::make(IntakeCredentialServiceInterface::class)->issue($deployment, $this->admin(), $expiresAt);

        return $issued['plaintext'];
    }

    public function test_admin_can_generate_and_revoke_an_intake_token(): void
    {
        $this->seed();
        $deployment = Deployment::query()->firstOrFail();

        $token = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertOk()
            ->json('data.token');

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('intake_credentials', ['deployment_id' => $deployment->id, 'revoked_at' => null]);
        $this->assertDatabaseMissing('intake_credentials', ['verifier_hash' => $token]);

        $this->withHeader('X-Intake-Token', $token)
            ->postJson('/api/intake/tickets', ['subject' => 'Works before revoke'])
            ->assertCreated();

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertOk();

        $this->assertDatabaseMissing('intake_credentials', ['deployment_id' => $deployment->id, 'revoked_at' => null]);

        $this->withHeader('X-Intake-Token', $token)
            ->postJson('/api/intake/tickets', ['subject' => 'Should fail after revoke'])
            ->assertUnauthorized();
    }

    public function test_generating_a_new_token_revokes_the_previous_one(): void
    {
        $this->seed();
        $deployment = Deployment::query()->firstOrFail();
        $first = $this->issueToken($deployment);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertOk();

        $this->withHeader('X-Intake-Token', $first)
            ->postJson('/api/intake/tickets', ['subject' => 'Stale token'])
            ->assertUnauthorized();
    }

    public function test_external_system_can_create_a_ticket_with_a_valid_token(): void
    {
        $this->seed();
        $deployment = Deployment::query()->firstOrFail();
        $token = $this->issueToken($deployment);

        $response = $this->withHeader('X-Intake-Token', $token)
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
        $deployment = Deployment::query()->firstOrFail();
        $token = $this->issueToken($deployment);

        $reference = $this->withHeader('X-Intake-Token', $token)
            ->postJson('/api/intake/tickets', ['subject' => 'Minor copy tweak'])
            ->assertCreated()
            ->json('data.reference');

        $this->assertDatabaseHas('support_tickets', ['reference' => $reference, 'severity' => 'low']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->seed();

        $this->withHeader('X-Intake-Token', 'dit_not-a-real-token.nope')
            ->postJson('/api/intake/tickets', ['subject' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->seed();

        $this->postJson('/api/intake/tickets', ['subject' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->seed();
        $deployment = Deployment::query()->firstOrFail();
        $token = $this->issueToken($deployment, now()->subMinute()->toIso8601String());

        $this->withHeader('X-Intake-Token', $token)
            ->postJson('/api/intake/tickets', ['subject' => 'Expired'])
            ->assertUnauthorized();
    }

    public function test_intake_requires_a_subject(): void
    {
        $this->seed();
        $deployment = Deployment::query()->firstOrFail();
        $token = $this->issueToken($deployment);

        $this->withHeader('X-Intake-Token', $token)
            ->postJson('/api/intake/tickets', ['summary' => 'no subject'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');
    }
}
