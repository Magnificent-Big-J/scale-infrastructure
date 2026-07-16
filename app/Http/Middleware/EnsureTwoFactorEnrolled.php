<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Rainwaves\LaraAuthSuite\TwoFactor\Contracts\ITwoFactorAuth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnrolled
{
    public function __construct(private readonly ITwoFactorAuth $twoFactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (config('authx.2fa.enforcement') !== 'required') {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            return $next($request);
        }

        if (! $user->hasAnyRole(config('security.two_factor_required_roles', []))) {
            return $next($request);
        }

        if ($this->twoFactor->isEnabled($user)) {
            return $next($request);
        }

        // Always allow the user to reach their own profile/identity so they
        // can actually complete 2FA setup (the setup panel itself lives on
        // /auth/2fa/* routes, outside this gated group).
        if ($request->is('api/v1/me', 'api/v1/profile', 'api/v1/profile/*')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Two-factor authentication setup is required for your role before you can continue.',
            'code' => 'two_factor_setup_required',
        ], 403);
    }
}
