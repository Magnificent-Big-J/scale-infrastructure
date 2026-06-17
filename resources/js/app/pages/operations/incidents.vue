<template>
    <div class="support-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 03" title="Incidents" subtitle="Operational incidents, severity, root cause, resolution, and deployment links.">
                <template #metrics><AppStatusBadge status="active" :label="`${store.meta.total} incidents`" /></template>
            </AppPageHeader>
            <div class="support__stats">
                <AppStatCard label="Incidents" :value="String(store.meta.total)" helper="Operational incident records" icon="mdi-alert-octagon-outline" status="active" />
                <AppStatCard label="Open" :value="String(openCount)" helper="Not yet resolved or closed" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Resolved" :value="String(resolvedCount)" helper="Resolved or closed incidents" icon="mdi-check-circle-outline" status="active" />
            </div>
            <AppSectionCard title="Incident register" subtitle="Seeded incidents linked to clients and deployments.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search incidents" prepend-inner-icon="mdi-magnify" class="support__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="support__filter" @update:model-value="onFilter" />
                    <AppSelect v-model="filters.severity" :items="severityFilterItems" label="Severity" class="support__filter" @update:model-value="onFilter" />
                </AppFilterBar>
                <AppDataTable title="All incidents" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" @page-change="onPage">
                    <template #row="{ row }">
                        <td><div class="support-cell"><strong>{{ row.title }}</strong><small>{{ row.reference }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.deployment_name || '-' }}</span></td>
                        <td><AppStatusBadge :status="row.severity_color || row.severity" :label="row.severity_label || row.severity" /></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ formatDate(row.started_at) }}</span></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </div>
</template>
<route lang="json">
{"meta":{"layout":"default","title":"Incidents","requiresAuth":true,"adminOnly":true}}
</route>
<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useIncidentsStore } from '../../stores/incidents';
const store = useIncidentsStore();
const filters = reactive({ search: '', status: '', severity: '', page: 1 });
const columns = [{ key: 'incident', label: 'Incident' }, { key: 'deployment', label: 'Deployment' }, { key: 'severity', label: 'Severity' }, { key: 'status', label: 'Status' }, { key: 'started', label: 'Started' }];
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...store.options.statuses.map((item) => ({ title: item.label, value: item.value }))]);
const severityFilterItems = computed(() => [{ title: 'All severities', value: '' }, ...store.options.severities.map((item) => ({ title: item.label, value: item.value }))]);
const openCount = computed(() => store.rows.filter((row) => !['resolved', 'closed'].includes(row.status)).length);
const resolvedCount = computed(() => store.rows.filter((row) => ['resolved', 'closed'].includes(row.status)).length);
const formatDate = (value) => (value ? new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-');
const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status, severity: filters.severity });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(load);
</script>
<style scoped>
.support-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.support__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
.support__search { min-width: min(320px, 100%); }
.support__filter { min-width: 190px; }
.support-cell { display: grid; gap: 0.1rem; }
.support-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.text-sm { font-size: 0.85rem; }
@media (max-width: 960px) { .support-page { padding: 1.75rem 1rem 3rem; } .support__stats { grid-template-columns: 1fr; } }
</style>
