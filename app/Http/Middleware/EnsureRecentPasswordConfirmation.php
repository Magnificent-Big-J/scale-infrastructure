<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Rainwaves\LaraAuthSuite\Support\RecentPasswordConfirmation;
use Symfony\Component\HttpFoundation\Response;

/**
 * Step-up gate for sensitive actions (role/permission changes, external
 * credential issuance, report exports, release approve/deploy, automation
 * execution): the user must have confirmed their password within the
 * package's configured TTL (authx.2fa.recent_password_ttl_seconds, default
 * 300s) via POST /auth/session/password/confirm. Reuses the package's own
 * confirmation state instead of building a second one - the FE already
 * hits that endpoint for 2FA management, and both are the same class of
 * action (prove you still hold the credential before something sensitive).
 */
class EnsureRecentPasswordConfirmation
{
    public function __construct(private readonly RecentPasswordConfirmation $recentPassword) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($this->recentPassword->isConfirmed($user)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Please confirm your password to continue.',
            'code' => 'password_confirmation_required',
        ], 428);
    }
}
