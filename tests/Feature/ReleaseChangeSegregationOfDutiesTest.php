<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Deployment;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ReleaseChangeSegregationOfDutiesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    /**
     * technical already has releases.view (required by the enclosing route
     * group) plus deploy/rollback; grants approve directly on top -
     * segregation of duties is only observable once approval authority
     * exists on a non-administrator user, which the current role seed data
     * never does on its own (only administrator holds releases.approve
     * today).
     */
    private function technicalWithApprovalAuthority(): User
    {
        $user = User::where('email', 'technical@codescaletech.test')->firstOrFail();
        $user->givePermissionTo('releases.approve');

        return $user;
    }

    /** A second non-admin user able to deploy, distinct from the approver above. */
    private function anotherDeployer(): User
    {
        $user = User::where('email', 'operations@codescaletech.test')->firstOrFail();
        $user->givePermissionTo(['releases.view', 'releases.deploy']);

        return $user;
    }

    public function test_a_non_admin_cannot_approve_a_change_request_they_requested_themselves(): void
    {
        $this->seed();
        $requester = $this->technicalWithApprovalAuthority();
        $deployment = Deployment::query()->firstOrFail();

        $changeRequest = ChangeRequest::query()->create([
            'deployment_id' => $deployment->id,
            'client_id' => $deployment->client_id,
            'reference' => 'CR-TEST-0001',
            'title' => 'Self-requested change',
            'description' => 'Test',
            'risk' => 'low',
            'status' => 'submitted',
            'requested_by' => $requester->id,
        ]);

        $this->actingAs($requester, 'sanctum')
            ->postJson("/api/v1/change-requests/{$changeRequest->id}/approve")
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('segregation_of_duties');

        $this->assertSame('submitted', $changeRequest->fresh()->status->value);
    }

    public function test_a_non_admin_can_approve_a_change_request_someone_else_requested(): void
    {
        $this->seed();
        $requester = User::where('email', 'support@codescaletech.test')->firstOrFail();
        $approver = $this->technicalWithApprovalAuthority();
        $deployment = Deployment::query()->firstOrFail();

        $changeRequest = ChangeRequest::query()->create([
            'deployment_id' => $deployment->id,
            'client_id' => $deployment->client_id,
            'reference' => 'CR-TEST-0002',
            'title' => "Someone else's change",
            'description' => 'Test',
            'risk' => 'low',
            'status' => 'submitted',
            'requested_by' => $requester->id,
        ]);

        $this->actingAs($approver, 'sanctum')
            ->postJson("/api/v1/change-requests/{$changeRequest->id}/approve")
            ->assertOk();

        $this->assertSame('approved', $changeRequest->fresh()->status->value);
    }

    public function test_an_administrator_can_approve_their_own_change_request(): void
    {
        $this->seed();
        $admin = $this->admin();
        $deployment = Deployment::query()->firstOrFail();

        $changeRequest = ChangeRequest::query()->create([
            'deployment_id' => $deployment->id,
            'client_id' => $deployment->client_id,
            'reference' => 'CR-TEST-0003',
            'title' => 'Admin self-requested change',
            'description' => 'Test',
            'risk' => 'low',
            'status' => 'submitted',
            'requested_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/change-requests/{$changeRequest->id}/approve")
            ->assertOk();
    }

    private function actingAsWithStepUp(User $user): void
    {
        Auth::forgetGuards();
        $this->actingAs($user, 'web');
        $this->actingAs($user, 'sanctum');
        $this->postJson('/auth/session/password/confirm', ['password' => 'password'])->assertOk();
    }

    public function test_a_non_admin_cannot_deploy_a_release_they_approved_themselves(): void
    {
        $this->seed();
        $approver = $this->technicalWithApprovalAuthority();
        $deployment = Deployment::where('name', 'Client Sandbox')->firstOrFail();
        $release = Release::where('deployment_id', $deployment->id)->where('version', '2026.05.3')->firstOrFail();

        $this->actingAsWithStepUp($approver);
        $this->postJson("/api/v1/releases/{$release->id}/approve")
            ->assertOk();

        $this->actingAsWithStepUp($approver);
        $this->postJson("/api/v1/releases/{$release->id}/deploy")
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('segregation_of_duties');
    }

    public function test_a_different_non_admin_can_deploy_a_release_someone_else_approved(): void
    {
        $this->seed();
        $approver = $this->technicalWithApprovalAuthority();
        $deployer = $this->anotherDeployer();
        $deployment = Deployment::where('name', 'Client Sandbox')->firstOrFail();
        $release = Release::where('deployment_id', $deployment->id)->where('version', '2026.05.3')->firstOrFail();

        $this->actingAsWithStepUp($approver);
        $this->postJson("/api/v1/releases/{$release->id}/approve")
            ->assertOk();

        $this->actingAsWithStepUp($deployer);
        $this->postJson("/api/v1/releases/{$release->id}/deploy")
            ->assertOk();
    }
}
