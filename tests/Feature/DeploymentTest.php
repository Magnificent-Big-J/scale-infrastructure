<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Deployment;
use App\Models\InfrastructureAsset;
use App\Models\MonitoringCheck;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_deployment_seeder_creates_deployments_assets_and_checks(): void
    {
        $this->seed();

        $this->assertSame(3, Deployment::count());
        $this->assertSame(5, InfrastructureAsset::count());
        $this->assertSame(6, MonitoringCheck::count());
        $this->assertDatabaseHas('deployments', ['name' => 'ScaleLens Production', 'environment' => 'production']);
        $this->assertDatabaseHas('monitoring_checks', ['name' => 'Nightly backups', 'status' => 'warning']);
    }

    public function test_admin_can_list_deployments(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/deployments')
            ->assertOk()
            ->assertJsonFragment(['name' => 'ScaleLens Production'])
            ->assertJsonPath('options.environments.0.value', 'production');
    }

    public function test_admin_can_create_deployment(): void
    {
        $this->seed();

        $client = Client::where('code', 'AURECON-PMO')->firstOrFail();
        $product = Product::where('code', 'SCALELENS')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/deployments', [
                'client_id' => $client->id,
                'product_id' => $product->id,
                'name' => 'ScaleLens UAT',
                'environment' => 'uat',
                'app_url' => 'https://uat.scalelens.test',
                'status' => 'planned',
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'ScaleLens UAT']);
    }

    public function test_admin_can_add_infrastructure_and_monitoring_to_deployment(): void
    {
        $this->seed();

        $deployment = Deployment::where('name', 'ScaleLens Production')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/infrastructure-assets", [
                'name' => 'Object storage bucket',
                'type' => 'storage',
                'provider' => 'AWS',
                'monthly_cost' => 180,
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Object storage bucket']);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/monitoring-checks", [
                'name' => 'Object storage health',
                'check_type' => 'storage',
                'target' => 'aurecon-media',
                'status' => 'passing',
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Object storage health']);
    }

    public function test_admin_can_update_infrastructure_asset_and_monitoring_check(): void
    {
        $this->seed();

        $deployment = Deployment::where('name', 'ScaleLens Production')->firstOrFail();

        $asset = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/infrastructure-assets", [
                'name' => 'Object storage bucket',
                'type' => 'storage',
                'provider' => 'AWS',
                'monthly_cost' => 180,
            ])
            ->assertCreated()
            ->json();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/infrastructure-assets/{$asset['id']}", [
                'name' => 'Object storage bucket (resized)',
                'type' => 'storage',
                'provider' => 'AWS',
                'monthly_cost' => 240,
            ])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Object storage bucket (resized)']);

        $this->assertDatabaseHas('infrastructure_assets', ['id' => $asset['id'], 'monthly_cost' => 240]);

        $check = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/monitoring-checks", [
                'name' => 'Object storage health',
                'check_type' => 'storage',
                'target' => 'aurecon-media',
                'status' => 'passing',
            ])
            ->assertCreated()
            ->json();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/v1/monitoring-checks/{$check['id']}", [
                'name' => 'Object storage health',
                'check_type' => 'storage',
                'target' => 'aurecon-media',
                'status' => 'warning',
            ])
            ->assertOk()
            ->assertJsonFragment(['status' => 'warning']);

        $this->assertDatabaseHas('monitoring_checks', ['id' => $check['id'], 'status' => 'warning']);
    }

    public function test_user_without_infrastructure_update_permission_cannot_edit_asset(): void
    {
        $this->seed();

        $deployment = Deployment::where('name', 'ScaleLens Production')->firstOrFail();

        $asset = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/deployments/{$deployment->id}/infrastructure-assets", [
                'name' => 'Object storage bucket',
                'type' => 'storage',
            ])
            ->assertCreated()
            ->json();

        $viewer = User::where('email', 'sales@codescaletech.test')->firstOrFail();

        $this->actingAs($viewer, 'sanctum')
            ->patchJson("/api/v1/infrastructure-assets/{$asset['id']}", ['name' => 'Renamed', 'type' => 'storage'])
            ->assertForbidden();
    }
}
