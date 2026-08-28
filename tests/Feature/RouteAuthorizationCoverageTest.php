<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * This app enforces authorization at the route level via `can:` middleware
 * (there are no Policy classes - see CLAUDE.md). That convention is only as
 * good as every route actually following it: nothing stops a future route
 * from being added to the v1 group without a `can:` gate. This asserts
 * every v1 API route carries one, except a short, explicit allowlist of
 * routes that are intentionally scoped to the authenticated user's own
 * identity or expose non-sensitive shared reference data rather than a
 * permissioned resource.
 */
class RouteAuthorizationCoverageTest extends TestCase
{
    private const ALLOWLIST = [
        'GET api/v1/ping',
        'GET api/v1/me',
        'GET api/v1/profile',
        'PATCH api/v1/profile',
        'PUT api/v1/profile/password',
        'GET api/v1/module-demo/{pageKey}',
        'GET api/v1/lookups/{type}',
    ];

    public function test_every_v1_route_is_permission_gated_or_explicitly_allowlisted(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1')) {
                continue;
            }

            $method = $route->methods()[0];
            $key = "{$method} {$route->uri()}";

            if (in_array($key, self::ALLOWLIST, true)) {
                continue;
            }

            $isGated = collect($route->gatherMiddleware())->contains(fn (string $middleware) => str_starts_with($middleware, 'can:'));

            if (! $isGated) {
                $violations[] = $key;
            }
        }

        $this->assertEmpty(
            $violations,
            "These v1 routes have no 'can:' authorization middleware and aren't in the allowlist: ".implode(', ', $violations)
        );
    }

    public function test_the_allowlist_does_not_contain_stale_routes(): void
    {
        $registered = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1'))
            ->map(fn ($route) => "{$route->methods()[0]} {$route->uri()}")
            ->all();

        $stale = array_diff(self::ALLOWLIST, $registered);

        $this->assertEmpty($stale, 'Allowlist entries no longer match a registered route: '.implode(', ', $stale));
    }
}
