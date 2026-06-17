<?php

namespace Tests\Feature;

use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ScaleInfrastructureFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_scale_infrastructure_accounts(): void
    {
        $this->seed();

        $administrator = User::where('email', 'admin@codescaletech.test')->firstOrFail();
        $operations = User::where('email', 'operations@codescaletech.test')->firstOrFail();
        $finance = User::where('email', 'finance@codescaletech.test')->firstOrFail();

        $this->assertTrue($administrator->hasRole('administrator'));
        $this->assertTrue($operations->hasRole('operations'));
        $this->assertTrue($finance->hasRole('finance'));
        $this->assertDatabaseHas('two_factor_secrets', [
            'user_id' => $operations->id,
            'type' => 'email',
        ]);
    }

    public function test_auth_user_resource_exposes_registry_roles_and_permissions(): void
    {
        $this->seed();

        $administrator = User::where('email', 'admin@codescaletech.test')->firstOrFail();
        $payload = AuthUserResource::make($administrator)->toArray(Request::create('/'));

        $this->assertSame('admin@codescaletech.test', $payload['email']);
        $this->assertContains('administrator', $payload['roles']);
        $this->assertContains('products.view', $payload['permissions']);
        $this->assertContains('clients.view', $payload['permissions']);
        $this->assertContains('deployments.view', $payload['permissions']);
        $this->assertContains('support_tiers.view', $payload['permissions']);
        $this->assertContains('settings.update', $payload['permissions']);
        $this->assertNull($payload['avatar_url']);
    }

    public function test_admin_can_view_seeded_module_demo_records(): void
    {
        $this->seed();

        $administrator = User::where('email', 'admin@codescaletech.test')->firstOrFail();

        $this->actingAs($administrator, 'sanctum')
            ->getJson('/api/v1/module-demo/operations.deployments')
            ->assertOk()
            ->assertJsonPath('summary.total', 3)
            ->assertJsonFragment(['headline' => 'ScaleLens Production']);
    }
}
