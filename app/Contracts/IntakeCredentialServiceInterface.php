<?php

namespace App\Contracts;

use App\Models\Deployment;
use App\Models\IntakeCredential;
use App\Models\User;

interface IntakeCredentialServiceInterface
{
    /**
     * Revoke any active credential and issue a new one. Returns the model
     * alongside the plaintext token — the plaintext is never persisted and
     * this is the only place it is ever available.
     *
     * @return array{credential: IntakeCredential, plaintext: string}
     */
    public function issue(Deployment $deployment, User $actor, ?string $expiresAt = null): array;

    public function revoke(Deployment $deployment): void;

    /**
     * Resolve a presented plaintext token to its credential, only if it is
     * well-formed, matches a stored selector, and the verifier is correct.
     * Revocation/expiry/scope/IP checks are the caller's responsibility.
     */
    public function resolve(string $token): ?IntakeCredential;
}
