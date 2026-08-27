<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StepUpAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    private function actingAsAdmin(): User
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'web');
        $this->actingAs($admin, 'sanctum');

        return $admin;
    }

    public function test_a_sensitive_action_is_blocked_until_password_is_recently_confirmed(): void
    {
        $this->seed();
        $this->actingAsAdmin();
        $deployment = Deployment::query()->firstOrFail();

        $this->postJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertStatus(428)
            ->assertJsonPath('code', 'password_confirmation_required');
    }

    public function test_confirming_the_password_unlocks_the_sensitive_action(): void
    {
        $this->seed();
        $this->actingAsAdmin();
        $deployment = Deployment::query()->firstOrFail();

        $this->postJson('/auth/session/password/confirm', ['password' => 'password'])
            ->assertOk();

        $this->postJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertOk();
    }

    public function test_wrong_password_does_not_unlock_the_sensitive_action(): void
    {
        $this->seed();
        $this->actingAsAdmin();
        $deployment = Deployment::query()->firstOrFail();

        $this->postJson('/auth/session/password/confirm', ['password' => 'not-the-password'])
            ->assertUnprocessable();

        $this->postJson("/api/v1/deployments/{$deployment->id}/intake-token")
            ->assertStatus(428);
    }
}
