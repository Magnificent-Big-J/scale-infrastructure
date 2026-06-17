<template>
    <div class="support-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 03" title="Support Tickets" subtitle="Client requests, issue categories, severity, assignment, hours logged, and resolution.">
                <template #metrics><AppStatusBadge status="active" :label="`${store.meta.total} tickets`" /></template>
            </AppPageHeader>
            <div class="support__stats">
                <AppStatCard label="Tickets" :value="String(store.meta.total)" helper="Current support work" icon="mdi-ticket-confirmation-outline" status="active" />
                <AppStatCard label="Open risk" :value="String(openRisk)" helper="High or critical open tickets" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Hours logged" :value="String(hoursLogged)" helper="Current result set" icon="mdi-clock-outline" status="processing" />
            </div>
            <AppSectionCard title="Support ticket queue" subtitle="Seeded support requests linked to clients, deployments, agreements, and assignees.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search tickets" prepend-inner-icon="mdi-magnify" class="support__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="support__filter" @update:model-value="onFilter" />
                    <AppSelect v-model="filters.severity" :items="severityFilterItems" label="Severity" class="support__filter" @update:model-value="onFilter" />
                </AppFilterBar>
                <AppDataTable title="All tickets" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" @page-change="onPage">
                    <template #row="{ row }">
                        <td><div class="support-cell"><strong>{{ row.subject }}</strong><small>{{ row.reference }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.deployment_name || '-' }}</span></td>
                        <td><span class="text-sm">{{ row.category || '-' }}</span></td>
                        <td><AppStatusBadge :status="row.severity_color || row.severity" :label="row.severity_label || row.severity" /></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ row.hours_logged ?? 0 }}</span></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </div>
</template>
<route lang="json">
{"meta":{"layout":"default","title":"Support Tickets","requiresAuth":true,"adminOnly":true}}
</route>
<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useSupportTicketsStore } from '../../stores/support-tickets';
const store = useSupportTicketsStore();
const filters = reactive({ search: '', status: '', severity: '', page: 1 });
const columns = [{ key: 'ticket', label: 'Ticket' }, { key: 'deployment', label: 'Deployment' }, { key: 'category', label: 'Category' }, { key: 'severity', label: 'Severity' }, { key: 'status', label: 'Status' }, { key: 'hours', label: 'Hours' }];
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...store.options.statuses.map((item) => ({ title: item.label, value: item.value }))]);
const severityFilterItems = computed(() => [{ title: 'All severities', value: '' }, ...store.options.severities.map((item) => ({ title: item.label, value: item.value }))]);
const openRisk = computed(() => store.rows.filter((row) => ['critical', 'high'].includes(row.severity) && !['resolved', 'closed'].includes(row.status)).length);
const hoursLogged = computed(() => store.rows.reduce((sum, row) => sum + Number(row.hours_logged || 0), 0));
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
