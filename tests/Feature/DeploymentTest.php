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
}
