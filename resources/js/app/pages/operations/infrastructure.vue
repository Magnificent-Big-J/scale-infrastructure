<template>
    <div class="operations-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 02" title="Infrastructure" subtitle="Hosting assets, providers, regions, server sizes, costs, IPs, and metadata.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} assets`" />
                </template>
            </AppPageHeader>

            <div class="operations__stats">
                <AppStatCard label="Assets" :value="String(store.meta.total)" helper="Tracked infrastructure records" icon="mdi-server-network" status="active" />
                <AppStatCard label="Monthly cost" :value="monthlyCost" helper="Current result set" icon="mdi-cash-multiple" status="processing" />
                <AppStatCard label="Deployments" :value="String(store.options.deployments.length)" helper="Available environments" icon="mdi-rocket-launch-outline" status="active" />
            </div>

            <AppSectionCard title="Infrastructure assets" subtitle="Seeded assets linked to deployments and clients.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search assets" prepend-inner-icon="mdi-magnify" class="operations__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.type" :items="typeFilterItems" label="Asset type" class="operations__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All assets" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" @page-change="onPage">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.name }}</strong><small>{{ row.deployment_name }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.type_label }}</span></td>
                        <td><span class="text-sm">{{ row.provider || '-' }}</span></td>
                        <td><span class="text-sm">{{ row.region || '-' }}</span></td>
                        <td><span class="text-sm">{{ row.size || '-' }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.monthly_cost, row.currency) }}</span></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Infrastructure","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';

import AppFilterBar from '../../components/AppFilterBar.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useInfrastructureAssetsStore } from '../../stores/infrastructure-assets';

const store = useInfrastructureAssetsStore();
const filters = reactive({ search: '', type: '', page: 1 });
const columns = [
    { key: 'asset', label: 'Asset' },
    { key: 'type', label: 'Type' },
    { key: 'provider', label: 'Provider' },
    { key: 'region', label: 'Region' },
    { key: 'size', label: 'Size' },
    { key: 'cost', label: 'Monthly' },
];
const typeFilterItems = computed(() => [{ title: 'All types', value: '' }, ...store.options.types.map((item) => ({ title: item.label, value: item.value }))]);
const formatAmount = (value, currency) => (value ? `${currency || ''} ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`.trim() : '-');
const monthlyCost = computed(() => formatAmount(store.rows.reduce((sum, row) => sum + Number(row.monthly_cost || 0), 0), store.options.default_currency));
const load = () => store.fetch({ page: filters.page, search: filters.search, type: filters.type });
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
