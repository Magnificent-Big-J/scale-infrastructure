<?php

namespace Tests\Feature;

use App\Models\InfrastructureAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureCostRedactionTest extends TestCase
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

    public function test_a_role_without_profitability_view_does_not_see_monthly_cost(): void
    {
        $this->seed();
        $asset = InfrastructureAsset::query()->firstOrFail();
        $asset->forceFill(['monthly_cost' => 999.99])->save();

        $response = $this->actingAs($this->technical(), 'sanctum')
            ->getJson('/api/v1/infrastructure-assets')
            ->assertOk();

        $row = collect($response->json('data'))->firstWhere('id', $asset->id);
        $this->assertArrayNotHasKey('monthly_cost', $row);
    }

    public function test_a_role_with_profitability_view_sees_monthly_cost(): void
    {
        $this->seed();
        $asset = InfrastructureAsset::query()->firstOrFail();
        $asset->forceFill(['monthly_cost' => 999.99])->save();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/infrastructure-assets')
            ->assertOk();

        $row = collect($response->json('data'))->firstWhere('id', $asset->id);
        $this->assertEquals('999.99', $row['monthly_cost']);
    }

    public function test_saving_an_edit_without_seeing_the_cost_does_not_wipe_it(): void
    {
        $this->seed();
        $asset = InfrastructureAsset::query()->firstOrFail();
        $asset->forceFill(['monthly_cost' => 999.99])->save();

        $this->actingAs($this->technical(), 'sanctum')
            ->patchJson("/api/v1/infrastructure-assets/{$asset->id}", [
                'name' => $asset->name,
                'type' => $asset->type,
                // monthly_cost intentionally omitted - the technical role
                // never receives it, so it must never submit it either.
            ])
            ->assertOk();

        $this->assertEquals('999.99', $asset->fresh()->monthly_cost);
    }
}
