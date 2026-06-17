<template>
    <div class="support-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 03" title="Support Agreements" subtitle="Support retainers, tiers, monthly fees, included hours, response SLAs, and dates.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} agreements`" />
                </template>
            </AppPageHeader>

            <div class="support__stats">
                <AppStatCard label="Agreements" :value="String(store.meta.total)" helper="Retainers in the registry" icon="mdi-handshake-outline" status="active" />
                <AppStatCard label="Monthly value" :value="monthlyValue" helper="Current result set" icon="mdi-cash-multiple" status="processing" />
                <AppStatCard label="Included hours" :value="String(includedHours)" helper="Monthly support capacity" icon="mdi-timer-outline" status="active" />
            </div>

            <AppSectionCard title="Support agreement registry" subtitle="Seeded retainers linked to clients and support tiers.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search agreements" prepend-inner-icon="mdi-magnify" class="support__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="support__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All agreements" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" @page-change="onPage">
                    <template #row="{ row }">
                        <td><div class="support-cell"><strong>{{ row.name }}</strong><small>{{ row.code }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.support_tier_name || '-' }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.monthly_fee) }}</span></td>
                        <td><span class="text-sm">{{ row.included_hours ?? '-' }}</span></td>
                        <td><span class="text-sm">{{ row.response_sla_hours ? `${row.response_sla_hours}h` : '-' }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Support Agreements","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useSupportAgreementsStore } from '../../stores/support-agreements';

const store = useSupportAgreementsStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'agreement', label: 'Agreement' },
    { key: 'tier', label: 'Tier' },
    { key: 'monthly', label: 'Monthly' },
    { key: 'hours', label: 'Hours' },
    { key: 'sla', label: 'SLA' },
    { key: 'status', label: 'Status' },
];
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...store.options.statuses.map((item) => ({ title: item.label, value: item.value }))]);
const formatAmount = (value) => (value ? `ZAR ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '-');
const monthlyValue = computed(() => formatAmount(store.rows.reduce((sum, row) => sum + Number(row.monthly_fee || 0), 0)));
const includedHours = computed(() => store.rows.reduce((sum, row) => sum + Number(row.included_hours || 0), 0));
const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status });
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
