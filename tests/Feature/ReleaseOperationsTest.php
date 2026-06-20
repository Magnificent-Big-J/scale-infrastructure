<?php

namespace Tests\Feature;

use App\Models\AutomationRun;
use App\Models\ChangeRequest;
use App\Models\Deployment;
use App\Models\ProvisioningTemplate;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function technical(): User
    {
        return User::where('email', 'technical@codescaletech.test')->firstOrFail();
    }

    private function executive(): User
    {
        return User::where('email', 'executive@codescaletech.test')->firstOrFail();
    }

    public function test_seeder_creates_release_operations_records(): void
    {
        $this->seed();

        $this->assertSame(3, Release::count());
        $this->assertSame(2, ChangeRequest::count());
        $this->assertSame(1, ProvisioningTemplate::count());
        $this->assertSame(1, AutomationRun::count());
        $this->assertDatabaseHas('releases', ['version' => '2026.06.1', 'status' => 'deployed']);
    }

    public function test_admin_can_record_a_release(): void
    {
        $this->seed();

        $deployment = Deployment::where('name', 'ScaleLens Staging')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/releases', [
                'deployment_id' => $deployment->id,
                'version' => '2026.07.0',
                'notes' => 'Feature release candidate.',
            ])
            ->assertCreated()
            ->assertJsonFragment(['version' => '2026.07.0', 'status' => 'draft']);

        $this->assertDatabaseHas('releases', ['version' => '2026.07.0']);
    }

    public function test_release_lifecycle_approve_deploy_updates_state_and_deployment_version(): void
    {
        $this->seed();

        $deployment = Deployment::where('name', 'Client Sandbox')->firstOrFail();
        $release = Release::where('deployment_id', $deployment->id)->where('version', '2026.05.3')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/releases/{$release->id}/approve")
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        $release->refresh();
        $this->assertNotNull($release->approved_at);
        $this->assertSame($this->admin()->id, $release->approved_by);

        $this->actingAs($this->technical(), 'sanctum')
            ->postJson("/api/v1/releases/{$release->id}/deploy")
            ->assertOk()
            ->assertJsonFragment(['status' => 'deployed']);

        $release->refresh();
        $this->assertNotNull($release->deployed_at);
        $this->assertSame('2026.05.3', $deployment->refresh()->current_version);
    }

    public function test_deploying_an_unapproved_release_is_rejected(): void
    {
        $this->seed();

        $deployment = Deployment::where('name', 'Client Sandbox')->firstOrFail();
        $release = Release::where('deployment_id', $deployment->id)->where('version', '2026.05.3')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/releases/{$release->id}/deploy")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('status');
    }

    public function test_deployed_release_can_be_rolled_back(): void
    {
        $this->seed();

        $release = Release::where('version', '2026.06.1')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/releases/{$release->id}/rollback", ['rollback_notes' => 'Regression in reporting.'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'rolled_back']);

        $release->refresh();
        $this->assertNotNull($release->rolled_back_at);
        $this->assertSame('Regression in reporting.', $release->rollback_notes);
    }

    public function test_user_without_approve_permission_cannot_approve_release(): void
    {
        $this->seed();

        $deployment = Deployment::where('name', 'Client Sandbox')->firstOrFail();
        $release = Release::where('deployment_id', $deployment->id)->where('version', '2026.05.3')->firstOrFail();

        // technical has releases.view/deploy/rollback but not approve.
        $this->actingAs($this->technical(), 'sanctum')
            ->postJson("/api/v1/releases/{$release->id}/approve")
            ->assertForbidden();
    }

    public function test_user_without_rollback_permission_cannot_roll_back_release(): void
    {
        $this->seed();

        $release = Release::where('version', '2026.06.1')->firstOrFail();

        // executive has releases.view only.
        $this->actingAs($this->executive(), 'sanctum')
            ->postJson("/api/v1/releases/{$release->id}/rollback")
            ->assertForbidden();
    }

    public function test_change_request_can_be_approved(): void
    {
        $this->seed();

        $changeRequest = ChangeRequest::where('reference', 'CR-3002')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/change-requests/{$changeRequest->id}/approve")
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        $this->assertSame($this->admin()->id, $changeRequest->refresh()->approved_by);
    }

    public function test_automation_run_requires_an_approved_change_request(): void
    {
        $this->seed();

        $submitted = ChangeRequest::where('reference', 'CR-3002')->firstOrFail();
        $approved = ChangeRequest::where('reference', 'CR-3001')->firstOrFail();
        $template = ProvisioningTemplate::firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/automation-runs', [
                'provisioning_template_id' => $template->id,
                'change_request_id' => $submitted->id,
                'reference' => 'RUN-4002',
                'status' => 'running',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('change_request_id');

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/automation-runs', [
                'provisioning_template_id' => $template->id,
                'change_request_id' => $approved->id,
                'reference' => 'RUN-4003',
                'status' => 'succeeded',
                'output_summary' => 'Completed provisioning.',
            ])
            ->assertCreated()
            ->assertJsonFragment(['reference' => 'RUN-4003']);

        $this->assertDatabaseHas('automation_runs', ['reference' => 'RUN-4003', 'triggered_by' => $this->admin()->id]);
    }
}
