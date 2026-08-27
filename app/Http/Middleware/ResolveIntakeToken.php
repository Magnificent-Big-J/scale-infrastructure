<?php

namespace App\Http\Middleware;

use App\Contracts\IntakeCredentialServiceInterface;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ResolveIntakeToken
{
    public function __construct(
        private readonly IntakeCredentialServiceInterface $credentials,
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $token = $request->header('X-Intake-Token') ?: $request->bearerToken();

        $credential = $token ? $this->credentials->resolve($token) : null;

        if ($credential === null) {
            Log::warning('intake.invalid_credential', ['ip' => $request->ip()]);

            abort(401, 'Invalid or missing intake token.');
        }

        $limitKey = 'intake:'.$credential->id;

        if ($this->limiter->tooManyAttempts($limitKey, (int) config('security.intake.rate_limit_per_minute', 60))) {
            Log::warning('intake.rate_limited', ['credential_id' => $credential->id, 'ip' => $request->ip()]);

            abort(429, 'Too many intake requests.');
        }

        $this->limiter->hit($limitKey, 60);

        if ($credential->isRevoked() || $credential->isExpired()) {
            Log::warning('intake.expired_or_revoked_credential', ['credential_id' => $credential->id, 'ip' => $request->ip()]);

            abort(401, 'Invalid or missing intake token.');
        }

        if (! $credential->ipAllowed((string) $request->ip())) {
            Log::warning('intake.ip_not_allowed', ['credential_id' => $credential->id, 'ip' => $request->ip()]);

            abort(403, 'This intake credential is not permitted from your network.');
        }

        if ($scope !== null && ! $credential->hasScope($scope)) {
            abort(403, 'This intake credential does not permit this action.');
        }

        $credential->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('intake_deployment', $credential->deployment);
        $request->attributes->set('intake_credential', $credential);

        return $next($request);
    }
}
