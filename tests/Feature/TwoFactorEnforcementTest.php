<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function support(): User
    {
        return User::where('email', 'support@codescaletech.test')->firstOrFail();
    }

    public function test_privileged_user_without_2fa_is_blocked_when_enforcement_is_required(): void
    {
        $this->seed();
        config(['authx.2fa.enforcement' => 'required']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/clients')
            ->assertStatus(403)
            ->assertJsonPath('code', 'two_factor_setup_required');
    }

    public function test_privileged_user_without_2fa_can_still_reach_own_profile_and_identity(): void
    {
        $this->seed();
        config(['authx.2fa.enforcement' => 'required']);

        $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/me')->assertOk();
        $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/profile')->assertOk();
    }

    public function test_non_privileged_role_is_not_gated(): void
    {
        $this->seed();
        config(['authx.2fa.enforcement' => 'required']);

        $this->actingAs($this->support(), 'sanctum')
            ->getJson('/api/v1/support-tickets')
            ->assertOk();
    }

    public function test_enforcement_optional_does_not_gate_anyone(): void
    {
        $this->seed();
        config(['authx.2fa.enforcement' => 'optional']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/clients')
            ->assertOk();
    }
}
