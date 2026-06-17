<template>
    <div class="dashboard">
        <AppPageHeader
            eyebrow="Scale Infrastructure"
            :title="`Good ${dayPart}, ${firstName}.`"
            subtitle="Operational control centre for clients, deployments, infrastructure, monitoring, support, billing, releases, and profitability."
        >
            <template #metrics>
                <AppStatusBadge status="active" label="Phase 0" />
                <AppStatusBadge status="processing" label="Foundation shell" />
            </template>
        </AppPageHeader>

        <div class="stat-row">
            <AppStatCard
                v-for="stat in stats"
                :key="stat.label"
                :label="stat.label"
                :value="stat.value"
                :helper="stat.helper"
                :icon="stat.icon"
                :icon-color="stat.iconColor"
                :icon-background="stat.bg"
            />
        </div>

        <div class="dashboard__body">
            <AppSectionCard title="Build sequence" subtitle="The registry will be built in complete operational slices.">
                <div class="module-grid">
                    <RouterLink
                        v-for="mod in modules"
                        :key="mod.to"
                        :to="mod.to"
                        class="module-card"
                    >
                        <div class="module-card__top">
                            <span class="module-card__icon">
                                <v-icon size="20" :color="mod.color">{{ mod.icon }}</v-icon>
                            </span>
                            <span class="module-card__status">{{ mod.status }}</span>
                        </div>
                        <h3 class="module-card__title">{{ mod.title }}</h3>
                        <p class="module-card__text">{{ mod.text }}</p>
                    </RouterLink>
                </div>
            </AppSectionCard>

            <aside class="dashboard__aside">
                <AppSectionCard title="Signed in as">
                    <div class="identity">
                        <span class="identity__avatar">{{ userInitials }}</span>
                        <div class="identity__info">
                            <span class="identity__name">{{ session.user?.name }}</span>
                            <span class="identity__email">{{ session.user?.email }}</span>
                        </div>
                    </div>
                    <div class="role-list">
                        <AppStatusBadge
                            v-for="role in session.user?.roles"
                            :key="role"
                            :status="role"
                            :label="role"
                        />
                        <span v-if="!session.user?.roles?.length" class="role-none">No roles assigned</span>
                    </div>
                </AppSectionCard>

                <AppSectionCard title="Foundation status">
                    <ul class="stack-list">
                        <li v-for="item in foundationItems" :key="item.name" class="stack-list__item">
                            <span class="stack-list__name">{{ item.name }}</span>
                            <span class="stack-list__ver">{{ item.status }}</span>
                        </li>
                    </ul>
                </AppSectionCard>
            </aside>
        </div>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Dashboard",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed } from 'vue';

import AppPageHeader from '../components/AppPageHeader.vue';
import AppSectionCard from '../components/AppSectionCard.vue';
import AppStatCard from '../components/AppStatCard.vue';
import AppStatusBadge from '../components/AppStatusBadge.vue';
import { useSessionStore } from '../stores/session';

const session = useSessionStore();

const dayPart = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'morning';
    if (h < 17) return 'afternoon';
    return 'evening';
});

const firstName = computed(() =>
    session.user?.name?.split(' ')[0] || 'there'
);

const userInitials = computed(() =>
    (session.user?.name || 'SI')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || '')
        .join('')
);

const stats = [
    { label: 'Roles seeded', value: '7', helper: 'Operations, finance, sales, support and admin access', icon: 'mdi-shield-account-outline', bg: 'rgba(0,106,74,0.08)', iconColor: 'var(--rw-600)' },
    { label: 'Database', value: 'PostgreSQL', helper: 'Default registry database target', icon: 'mdi-database-outline', bg: 'rgba(3,105,161,0.08)', iconColor: '#0369a1' },
    { label: 'Queue backend', value: 'Redis', helper: 'Horizon-ready operational jobs', icon: 'mdi-sync-circle', bg: 'rgba(180,83,9,0.08)', iconColor: 'var(--rw-amber)' },
    { label: 'Storage backend', value: 'S3', helper: 'Media and future operational documents', icon: 'mdi-cloud-outline', bg: 'rgba(101,16,147,0.08)', iconColor: '#6510a3' },
];

const modules = [
    {
        to: '/clients',
        title: 'Clients and contacts',
        text: 'Account master data, contacts, client status, and ownership.',
        icon: 'mdi-domain',
        color: 'var(--rw-600)',
        status: 'Module 01',
    },
    {
        to: '/operations/deployments',
        title: 'Deployments and infrastructure',
        text: 'Product environments, hosting assets, domains, versions, and status.',
        icon: 'mdi-server-network',
        color: '#0369a1',
        status: 'Module 02',
    },
    {
        to: '/support/tickets',
        title: 'Support and incidents',
        text: 'Retainers, support work, severity, incidents, and SLA visibility.',
        icon: 'mdi-ticket-confirmation-outline',
        color: 'var(--rw-amber)',
        status: 'Module 03',
    },
    {
        to: '/commercial/billing',
        title: 'Commercial operations',
        text: 'Contracts, billing records, invoices, payments, and revenue visibility.',
        icon: 'mdi-cash-multiple',
        color: '#7c3aed',
        status: 'Module 04',
    },
];

const foundationItems = [
    { name: 'Laravel', status: '13' },
    { name: 'Vue and Vuetify', status: 'Ready' },
    { name: 'Sanctum auth', status: 'Ready' },
    { name: 'Permission model', status: 'Seeded' },
    { name: 'Activity log', status: 'Ready' },
    { name: 'Horizon', status: 'Ready' },
];
</script>

<style scoped>
.dashboard {
    width: 100%;
    max-width: var(--rw-content-max);
    margin: 0 auto;
    padding: 2.25rem 2rem 4rem;
    display: grid;
    gap: 2rem;
}

.stat-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.875rem;
}

.dashboard__body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 21rem;
    gap: 1.5rem;
    align-items: start;
}

.dashboard__aside {
    display: grid;
    gap: 1rem;
}

.module-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.875rem;
}

.module-card {
    display: grid;
    gap: 0.8rem;
    padding: 1rem;
    border: 1px solid var(--rw-border);
    border-radius: 12px;
    background: var(--rw-surface-2);
    color: var(--rw-ink);
}

.module-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.module-card__icon {
    width: 2.25rem;
    height: 2.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--rw-border);
}

.module-card__status {
    color: var(--rw-muted);
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
}

.module-card__title {
    margin: 0;
    font-size: 1rem;
    line-height: 1.25;
}

.module-card__text {
    margin: 0;
    color: var(--rw-muted);
    font-size: 0.88rem;
    line-height: 1.55;
}

.identity {
    display: flex;
    gap: 0.8rem;
    align-items: center;
}

.identity__avatar {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 12px;
    background: var(--rw-700);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
}

.identity__info {
    min-width: 0;
    display: grid;
}

.identity__name,
.identity__email {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.identity__name {
    font-weight: 700;
}

.identity__email,
.role-none {
    color: var(--rw-muted);
    font-size: 0.84rem;
}

.role-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-top: 1rem;
}

.stack-list {
    display: grid;
    gap: 0.65rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.stack-list__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid var(--rw-border);
}

.stack-list__item:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.stack-list__name {
    color: var(--rw-muted);
}

.stack-list__ver {
    font-weight: 700;
}

@media (max-width: 1020px) {
    .dashboard__body {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .dashboard {
        padding: 1.35rem 1rem 3rem;
    }

    .stat-row,
    .module-grid {
        grid-template-columns: 1fr;
    }
}
</style>
