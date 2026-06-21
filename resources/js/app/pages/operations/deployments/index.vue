<template>
    <div class="operations-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Module 02"
                title="Deployments"
                subtitle="Client product environments, domains, app URLs, versions, and deployment status."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} deployments`" />
                </template>
            </AppPageHeader>

            <div class="operations__stats">
                <AppStatCard label="Total deployments" :value="String(store.meta.total)" helper="Across all environments" icon="mdi-rocket-launch-outline" status="active" />
                <AppStatCard label="Production" :value="String(productionCount)" helper="Production environments" icon="mdi-server-network" status="processing" />
                <AppStatCard label="Monitoring checks" :value="String(monitoringCount)" helper="Attached operational checks" icon="mdi-pulse" status="active" />
            </div>

            <AppSectionCard title="Deployment registry" subtitle="Seeded operational environments linked to clients, products, and packages.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search deployments" prepend-inner-icon="mdi-magnify" class="operations__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.environment" :items="environmentFilterItems" label="Environment" class="operations__filter" @update:model-value="onFilter" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="operations__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable
                    title="All deployments"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No deployments found"
                    empty-text="Seed deployments or create the first client environment."
                    @page-change="onPage"
                    @row-click="goToDetail"
                >
                    <template #row="{ row }">
                        <td>
                            <div class="ops-cell">
                                <strong>{{ row.name }}</strong>
                                <small>{{ row.client_name }}</small>
                            </div>
                        </td>
                        <td><span class="text-sm">{{ row.environment_label }}</span></td>
                        <td>
                            <div class="ops-cell">
                                <span>{{ row.domain || '-' }}</span>
                                <small>{{ row.app_url || '' }}</small>
                            </div>
                        </td>
                        <td><span class="text-sm">{{ row.current_version || '-' }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ row.infrastructure_assets_count ?? 0 }}</span></td>
                        <td><span class="text-sm">{{ row.monitoring_checks_count ?? 0 }}</span></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Deployments",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';

import AppFilterBar from '../../../components/AppFilterBar.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import AppTextField from '../../../components/AppTextField.vue';
import { useDeploymentsStore } from '../../../stores/deployments';

const router = useRouter();
const goToDetail = (row) => router.push(`/operations/deployments/${row.id}`);

const store = useDeploymentsStore();
const filters = reactive({ search: '', status: '', environment: '', page: 1 });

const columns = [
    { key: 'deployment', label: 'Deployment' },
    { key: 'environment', label: 'Environment' },
    { key: 'domain', label: 'Domain' },
    { key: 'version', label: 'Version' },
    { key: 'status', label: 'Status' },
    { key: 'assets', label: 'Assets' },
    { key: 'checks', label: 'Checks' },
];

const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...store.options.statuses.map((item) => ({ title: item.label, value: item.value }))]);
const environmentFilterItems = computed(() => [{ title: 'All environments', value: '' }, ...store.options.environments.map((item) => ({ title: item.label, value: item.value }))]);
const productionCount = computed(() => store.rows.filter((row) => row.environment === 'production').length);
const monitoringCount = computed(() => store.rows.reduce((sum, row) => sum + Number(row.monitoring_checks_count || 0), 0));

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status, environment: filters.environment });
const onSearch = (val) => {
    filters.search = val;
    filters.page = 1;
    load();
};
const onFilter = () => {
    filters.page = 1;
    load();
};
const onPage = (page) => {
    filters.page = page;
    load();
};

onMounted(load);
</script>

<style scoped>
.operations-page {
    padding: 2.25rem 2rem 4rem;
}

.page-wrap {
    max-width: var(--rw-content-max);
    margin: 0 auto;
    display: grid;
    gap: 1.5rem;
}

.operations__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
}

.operations__search {
    min-width: min(320px, 100%);
}

.operations__filter {
    min-width: 190px;
}

.ops-cell {
    display: grid;
    gap: 0.1rem;
}

.ops-cell small {
    color: var(--rw-muted);
    font-size: 0.78rem;
}

.text-sm {
    font-size: 0.85rem;
}

@media (max-width: 960px) {
    .operations-page {
        padding: 1.75rem 1rem 3rem;
    }

    .operations__stats {
        grid-template-columns: 1fr;
    }
}
</style>
