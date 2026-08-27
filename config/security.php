<?php

return [
    // Roles that must have 2FA enrolled before using the app, once
    // authx.2fa.enforcement is set to 'required'. The vendor auth package
    // only challenges users who already have 2FA set up — it never forces
    // enrollment itself, so this app-level gate closes that gap.
    'two_factor_required_roles' => array_filter(explode(',', (string) env('SECURITY_2FA_REQUIRED_ROLES', 'administrator,executive,finance'))),

    // Permission-risk MFA: a user holding any of these permissions must have
    // 2FA enrolled once enforcement is 'required', regardless of role name.
    // Catches a user granted a sensitive permission directly (or via a role
    // not in two_factor_required_roles above) that the role-name check alone
    // would miss.
    'two_factor_required_permissions' => [
        'users.delete',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.assign',
        'payments.create',
        'payments.update',
        'payments.delete',
        'invoices.mark_paid',
        'contracts.approve',
        'releases.approve',
        'releases.deploy',
        'releases.rollback',
        'settings.update',
        'deployments.delete',
        'deployments.provision',
        'reports.export',
    ],

    'intake' => [
        // Per-credential cap on the public external ticket-intake endpoint,
        // independent of the route's own IP-based throttle.
        'rate_limit_per_minute' => (int) env('SECURITY_INTAKE_RATE_LIMIT_PER_MINUTE', 60),
    ],
];
