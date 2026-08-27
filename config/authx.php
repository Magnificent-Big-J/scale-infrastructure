<?php

use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Rainwaves\LaraAuthSuite\Support\Enums\AuthFeature;
use Rainwaves\LaraAuthSuite\Support\Enums\AuthMode;
use Rainwaves\LaraAuthSuite\Support\Enums\TwoFactorChannel;

return [
    'route_prefix' => 'auth',
    'mode' => AuthMode::Both->value,
    'user_model' => User::class,
    'user_resource' => AuthUserResource::class,
    'debug_ping' => false, // enable GET /auth/ping for health checks (disable in production)
    'frontend' => [
        'password_reset_url' => env('AUTHX_FRONTEND_RESET_URL', '/auth/reset-password'),
    ],
    'password_reset' => [
        'revoke_tokens' => true,       // revoke all Sanctum personal access tokens on reset
        'invalidate_sessions' => true, // remove other database-backed sessions on reset (no-op on non-database session drivers)
        'notify_user' => true,         // send a "your password was changed" notification (best-effort; never blocks reset success)
    ],
    'branding' => [
        // Closing signature line on every package-sent email. Left null, it
        // derives from app.name ("— The Scale Infrastructure Security Team").
        'email_signature' => env('AUTHX_EMAIL_SIGNATURE'),
    ],
    // v2.1: Devices removed from the package's own default set (trusted-device
    // management was never implemented, v2.2 scope) — dropped here too since
    // this app never had that feature either.
    'features' => [
        AuthFeature::PasswordReset->value,
        AuthFeature::TwoFactor->value,
        AuthFeature::Tokens->value,
    ],
    '2fa' => [
        'channels' => [TwoFactorChannel::Email->value, TwoFactorChannel::Totp->value], // allowed: email, totp
        'enforcement' => 'optional', // off | optional | required
        'remember_device_days' => 30,
        'otp' => [
            'length' => 6,
            'expiry_seconds' => 180,
            'throttle_per_minute' => 5,
        ],
        'totp_digits' => 6,
        'totp_period' => 30, // seconds per step (RFC 6238 default)
        'totp_window' => 1,  // steps either side of current accepted (1 = ±30s clock drift)
        'verification_ttl_seconds' => 600, // how long a verified 2FA state is trusted for token users
        'recovery_codes_count' => 8,
        'require_password_on_manage' => true,  // gates disable + regenerate recovery codes
        'require_password_on_enable' => false, // enabling 2FA is security-positive; no password needed
        'recent_password_ttl_seconds' => 300,  // how long a POST /auth/password/confirm stays valid
    ],
    'tokens' => [
        // v2.1 secure defaults (was ['*'] / null): scoped, finite. Nothing in
        // this app depends on unlimited-ability, non-expiring tokens.
        'default_abilities' => ['auth:read'],
        'expiry_minutes' => 10080, // 7 days
        'default_name' => 'authx-client',
        'ability_resolver' => null,
    ],
    // v2.1: independent per-account/per-IP buckets (was one combined-key
    // limiter that an attacker could bypass by rotating either dimension).
    'throttle' => [
        'login_per_account' => 5,
        'login_per_ip' => 20,
        'password_reset_per_account' => 3,
        'password_reset_per_ip' => 10,
        'otp_send_per_account' => 3,
        'otp_send_per_ip' => 10,
        'two_factor_per_account' => 5,
        'two_factor_per_ip' => 20,
        'decay_seconds' => 60,
    ],
    'registration' => [
        'enabled' => false,
        'issue_token_on_register' => false, // return PAT on success (token mode)
        'default_roles' => [], // e.g. ['client']
        'default_permissions' => [], // e.g. ['users.view']
        'allow_self_assign_roles' => false,
        'allow_self_assign_permissions' => false,
        'rules' => [ // host can override entirely in app/config/authx.php
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ],
        // Optional hook to supply rules dynamically (class must implement ProvidesValidationRules)
        'register_rules_provider' => null, // e.g. \App\Auth\RegisterRules::class
    ],

    'permissions' => [
        'enabled' => true, // if true, try assign roles/permissions via spatie/laravel-permission (if present)
        'fail_open_when_tables_missing' => false,
    ],
];
