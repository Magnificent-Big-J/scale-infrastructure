<?php

namespace App\Http\Middleware;

use App\Models\Deployment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveIntakeToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Intake-Token') ?: $request->bearerToken();
        $deployment = $token ? Deployment::query()->where('intake_token', $token)->first() : null;

        abort_if($deployment === null, 401, 'Invalid or missing intake token.');

        $request->attributes->set('intake_deployment', $deployment);

        return $next($request);
    }
}
