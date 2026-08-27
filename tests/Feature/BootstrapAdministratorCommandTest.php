<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BootstrapAdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator_with_a_generated_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('app:bootstrap-admin', [
            '--email' => 'first-admin@example.test',
            '--name' => 'First Admin',
        ])->assertSuccessful();

        $user = User::where('email', 'first-admin@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole('administrator'));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_refuses_to_run_again_once_an_administrator_exists(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('app:bootstrap-admin', [
            '--email' => 'first-admin@example.test',
            '--name' => 'First Admin',
        ])->assertSuccessful();

        $this->artisan('app:bootstrap-admin', [
            '--email' => 'second-admin@example.test',
            '--name' => 'Second Admin',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'second-admin@example.test']);
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->create(['email' => 'taken@example.test']);

        $this->artisan('app:bootstrap-admin', [
            '--email' => 'taken@example.test',
            '--name' => 'Someone',
        ])->assertFailed();
    }
}
