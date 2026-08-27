<?php

namespace App\Services;

use App\Contracts\IntakeCredentialServiceInterface;
use App\Enums\IntakeScope;
use App\Models\Deployment;
use App\Models\IntakeCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntakeCredentialService implements IntakeCredentialServiceInterface
{
    private const PREFIX = 'dit_';

    public function issue(Deployment $deployment, User $actor, ?string $expiresAt = null): array
    {
        return DB::transaction(function () use ($deployment, $actor, $expiresAt) {
            $deployment->intakeCredentials()
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $selector = Str::random(16);
            $verifier = Str::random(40);

            $credential = $deployment->intakeCredentials()->create([
                'created_by' => $actor->id,
                'selector' => $selector,
                'verifier_hash' => $this->hashVerifier($verifier),
                'scopes' => [IntakeScope::TicketsCreate->value],
                'expires_at' => $expiresAt,
            ]);

            activity()
                ->performedOn($deployment)
                ->causedBy($actor)
                ->event('intake_credential_issued')
                ->log('Issued a new intake credential');

            return [
                'credential' => $credential,
                'plaintext' => self::PREFIX.$selector.'.'.$verifier,
            ];
        });
    }

    public function revoke(Deployment $deployment): void
    {
        DB::transaction(function () use ($deployment) {
            $revoked = $deployment->intakeCredentials()
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            if ($revoked > 0) {
                activity()
                    ->performedOn($deployment)
                    ->event('intake_credential_revoked')
                    ->log('Revoked the active intake credential');
            }
        });
    }

    public function resolve(string $token): ?IntakeCredential
    {
        if (! str_starts_with($token, self::PREFIX)) {
            return null;
        }

        [$selector, $verifier] = array_pad(
            explode('.', substr($token, strlen(self::PREFIX)), 2),
            2,
            null
        );

        if (! $selector || ! $verifier) {
            return null;
        }

        $credential = IntakeCredential::query()->with('deployment')->where('selector', $selector)->first();

        if (! $credential || ! hash_equals($credential->verifier_hash, $this->hashVerifier($verifier))) {
            return null;
        }

        return $credential;
    }

    private function hashVerifier(string $verifier): string
    {
        return hash('sha256', $verifier);
    }
}
