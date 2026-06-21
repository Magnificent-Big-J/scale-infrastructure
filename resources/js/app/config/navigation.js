/**
 * Single source of truth for the authenticated navigation. Consumed by the
 * sidebar (layouts/default.vue) and the command palette so both stay in sync.
 * Each item is permission-gated via the `permission` key.
 */
export const navGroups = [
    {
        label: null,
        items: [
            { label: 'Dashboard', to: '/dashboard', icon: 'mdi-view-dashboard-outline', permission: 'dashboard.view' },
            { label: 'Clients', to: '/clients', icon: 'mdi-domain', permission: 'clients.view' },
            { label: 'Reports', to: '/reports', icon: 'mdi-file-chart-outline', permission: 'reports.view' },
        ],
    },
    {
        label: 'Commercial',
        items: [
            { label: 'Opportunities', to: '/commercial/opportunities', icon: 'mdi-chart-timeline-variant', permission: 'opportunities.view' },
            { label: 'Contracts', to: '/commercial/contracts', icon: 'mdi-file-sign', permission: 'contracts.view' },
            { label: 'Billing', to: '/commercial/billing', icon: 'mdi-cash-multiple', permission: 'billing.view' },
            { label: 'Invoices', to: '/commercial/invoices', icon: 'mdi-receipt-text-outline', permission: 'invoices.view' },
            { label: 'Profitability', to: '/commercial/profitability', icon: 'mdi-finance', permission: 'profitability.view' },
        ],
    },
    {
        label: 'Operations',
        items: [
            { label: 'Ops Dashboard', to: '/operations/dashboard', icon: 'mdi-monitor-dashboard', permission: 'dashboard.view' },
            { label: 'Deployments', to: '/operations/deployments', icon: 'mdi-rocket-launch-outline', permission: 'deployments.view' },
            { label: 'Infrastructure', to: '/operations/infrastructure', icon: 'mdi-server-network', permission: 'infrastructure.view' },
            { label: 'Monitoring', to: '/operations/monitoring', icon: 'mdi-pulse', permission: 'monitoring.view' },
            { label: 'Incidents', to: '/operations/incidents', icon: 'mdi-alert-octagon-outline', permission: 'incidents.view' },
            { label: 'Releases', to: '/operations/releases', icon: 'mdi-source-branch', permission: 'releases.view' },
            { label: 'Change Requests', to: '/operations/change-requests', icon: 'mdi-clipboard-text-outline', permission: 'releases.view' },
            { label: 'Provisioning', to: '/operations/provisioning', icon: 'mdi-cog-outline', permission: 'releases.view' },
            { label: 'Automation', to: '/operations/automation', icon: 'mdi-robot-outline', permission: 'releases.view' },
        ],
    },
    {
        label: 'Support',
        items: [
            { label: 'Agreements', to: '/support/agreements', icon: 'mdi-handshake-outline', permission: 'support_agreements.view' },
            { label: 'Tickets', to: '/support/tickets', icon: 'mdi-ticket-confirmation-outline', permission: 'support_tickets.view' },
            { label: 'SLA Overview', to: '/support/sla-overview', icon: 'mdi-timer-sand', permission: 'support_tickets.view' },
        ],
    },
    {
        label: 'Administration',
        items: [
            { label: 'Products', to: '/admin/products', icon: 'mdi-package-variant-closed', permission: 'products.view' },
            { label: 'Packages', to: '/admin/packages', icon: 'mdi-tag-multiple-outline', permission: 'packages.view' },
            { label: 'Package Features', to: '/admin/catalogue-features', icon: 'mdi-format-list-checks', permission: 'catalogue_features.view' },
            { label: 'Support Tiers', to: '/admin/support-tiers', icon: 'mdi-handshake-outline', permission: 'support_tiers.view' },
            { label: 'Reference Data', to: '/admin/reference-data', icon: 'mdi-format-list-bulleted-type', permission: 'lookups.view' },
            { label: 'Users', to: '/admin/users', icon: 'mdi-account-group-outline', permission: 'users.view' },
            { label: 'Profile', to: '/auth/profile', icon: 'mdi-account-circle-outline', permission: 'profile.view' },
        ],
    },
];
