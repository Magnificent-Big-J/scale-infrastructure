<template>
    <div class="operations-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 02" title="Monitoring" subtitle="Uptime, SSL, backup, Sentry, server, database, Redis, and queue checks.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} checks`" />
                </template>
            </AppPageHeader>

            <div class="operations__stats">
                <AppStatCard label="Checks" :value="String(store.meta.total)" helper="Tracked monitoring checks" icon="mdi-pulse" status="active" />
                <AppStatCard label="Warnings" :value="String(warningCount)" helper="Checks needing attention" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Passing" :value="String(passingCount)" helper="Healthy checks" icon="mdi-check-circle-outline" status="active" />
            </div>

            <AppSectionCard title="Monitoring checks" subtitle="Seeded operational checks linked to deployments and clients.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search checks" prepend-inner-icon="mdi-magnify" class="operations__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="operations__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All checks" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" @page-change="onPage">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.name }}</strong><small>{{ row.deployment_name }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.check_type }}</span></td>
                        <td><span class="text-sm">{{ row.target }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ formatDate(row.last_checked_at) }}</span></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Monitoring","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';

import AppFilterBar from '../../components/AppFilterBar.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useMonitoringChecksStore } from '../../stores/monitoring-checks';

const store = useMonitoringChecksStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'check', label: 'Check' },
    { key: 'type', label: 'Type' },
    { key: 'target', label: 'Target' },
    { key: 'status', label: 'Status' },
    { key: 'last_checked', label: 'Last checked' },
];
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...store.options.statuses.map((item) => ({ title: item.label, value: item.value }))]);
const warningCount = computed(() => store.rows.filter((row) => row.status === 'warning' || row.status === 'failing').length);
const passingCount = computed(() => store.rows.filter((row) => row.status === 'passing').length);
const formatDate = (value) => (value ? new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-');
const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status });
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
