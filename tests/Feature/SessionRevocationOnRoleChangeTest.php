<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SessionRevocationOnRoleChangeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_changing_a_users_role_revokes_their_existing_tokens(): void
    {
        $this->seed();
        $admin = $this->admin();
        $target = User::where('email', 'support@codescaletech.test')->firstOrFail();

        $targetToken = $target->createToken('test')->plainTextToken;

        // The target's token works before the change.
        $this->withHeader('Authorization', "Bearer {$targetToken}")
            ->getJson('/api/v1/me')
            ->assertOk();

        Auth::forgetGuards();
        $this->actingAs($admin, 'web');
        $this->actingAs($admin, 'sanctum');
        $this->postJson('/auth/session/password/confirm', ['password' => 'password'])->assertOk();

        $this->patchJson("/api/v1/users/{$target->id}", ['roles' => ['operations']])
            ->assertOk();

        // The token minted before the role change must no longer work.
        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$targetToken}")
            ->getJson('/api/v1/me')
            ->assertUnauthorized();

        $this->assertTrue($target->fresh()->hasRole('operations'));
    }

    public function test_updating_a_user_without_touching_roles_or_permissions_does_not_revoke_tokens(): void
    {
        $this->seed();
        $admin = $this->admin();
        $target = User::where('email', 'support@codescaletech.test')->firstOrFail();

        $targetToken = $target->createToken('test')->plainTextToken;

        Auth::forgetGuards();
        $this->actingAs($admin, 'web');
        $this->actingAs($admin, 'sanctum');
        $this->postJson('/auth/session/password/confirm', ['password' => 'password'])->assertOk();

        $this->patchJson("/api/v1/users/{$target->id}", ['name' => 'Support User Renamed'])
            ->assertOk();

        Auth::forgetGuards();
        $this->withHeader('Authorization', "Bearer {$targetToken}")
            ->getJson('/api/v1/me')
            ->assertOk();
    }
}
