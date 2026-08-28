<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * A `can:foo.bar` route middleware that references a permission which was
 * never seeded doesn't error - Spatie's Gate check just always returns
 * false, so the route silently becomes unreachable for every role,
 * including administrator. A typo here (or a permission renamed in the
 * seeder without updating its routes) fails safe but fails silently, and
 * nothing short of manually exercising that exact route would surface it.
 * This makes the mismatch a test failure instead.
 */
class PermissionStringIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_route_level_permission_is_a_real_seeded_permission(): void
    {
        $this->seed();

        $known = Permission::query()->pluck('name')->all();
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1')) {
                continue;
            }

            foreach ($route->gatherMiddleware() as $middleware) {
                if (! str_starts_with($middleware, 'can:')) {
                    continue;
                }

                $permission = substr($middleware, 4);

                if (! in_array($permission, $known, true)) {
                    $violations[] = "{$route->methods()[0]} {$route->uri()} -> can:{$permission}";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "These routes gate on a permission that doesn't exist in RolesAndPermissionsSeeder: ".implode(', ', $violations)
        );
    }
}
