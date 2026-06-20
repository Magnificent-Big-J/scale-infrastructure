<template>
    <div class="reports-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 05" title="Reports" subtitle="Operational, support, finance, and profitability summaries for monthly review.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.types.length} reports`" />
                </template>
            </AppPageHeader>

            <div class="reports__layout">
                <AppSectionCard title="Report library" subtitle="Select a summary to preview or export." class="reports__list">
                    <button
                        v-for="type in store.types"
                        :key="type.value"
                        type="button"
                        class="report-card"
                        :class="{ 'report-card--active': active === type.value }"
                        @click="select(type.value)"
                    >
                        <span class="report-card__icon"><v-icon size="20">{{ type.icon }}</v-icon></span>
                        <span class="report-card__body">
                            <strong>{{ type.label }}</strong>
                            <small>{{ type.description }}</small>
                        </span>
                    </button>
                </AppSectionCard>

                <AppSectionCard :title="store.report?.title || 'Preview'" :subtitle="store.report?.description || 'Select a report to preview its rows.'" class="reports__preview">
                    <template v-if="store.report" #actions>
                        <v-btn variant="tonal" prepend-icon="mdi-download" :loading="exporting" @click="exportActive">Export CSV</v-btn>
                    </template>

                    <div v-if="!store.report && !store.loading" class="reports__empty">Choose a report from the library to preview it here.</div>

                    <v-progress-linear v-if="store.loading" indeterminate color="primary" class="mb-3" />

                    <div v-if="store.report" class="reports__table-wrap">
                        <p class="reports__meta">{{ store.report.row_count }} rows · generated {{ generatedAt }}</p>
                        <table class="reports__table">
                            <thead>
                                <tr><th v-for="col in store.report.columns" :key="col">{{ col }}</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in store.report.rows" :key="index">
                                    <td v-for="(cell, cellIndex) in row" :key="cellIndex">{{ cell ?? '-' }}</td>
                                </tr>
                                <tr v-if="!store.report.rows.length"><td :colspan="store.report.columns.length" class="reports__empty-row">No data for this report.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </AppSectionCard>
            </div>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Reports","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AppSectionCard from '../components/AppSectionCard.vue';
import { useReportsStore } from '../stores/reports';

const store = useReportsStore();
const active = ref('');
const exporting = ref(false);

const generatedAt = computed(() => (store.report?.generated_at ? new Date(store.report.generated_at).toLocaleString() : ''));

const select = async (type) => {
    active.value = type;
    await store.fetchReport(type);
};

const exportActive = async () => {
    if (!active.value) return;

    exporting.value = true;

    try {
        await store.exportReport(active.value);
    } finally {
        exporting.value = false;
    }
};

onMounted(async () => {
    await store.fetchTypes();
    if (store.types.length) select(store.types[0].value);
});
</script>

<style scoped>
.reports-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.reports__layout { display: grid; grid-template-columns: minmax(280px, 340px) 1fr; gap: 1.25rem; align-items: start; }
.reports__list { display: grid; gap: 0.6rem; }
.report-card { display: flex; gap: 0.75rem; align-items: flex-start; width: 100%; text-align: left; padding: 0.85rem 1rem; border: 1px solid var(--rw-border); border-radius: 14px; background: transparent; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.report-card:hover { border-color: var(--rw-300); }
.report-card--active { border-color: var(--rw-500); background: var(--rw-50); }
.report-card__icon { color: var(--rw-600); margin-top: 0.1rem; }
.report-card__body { display: grid; gap: 0.15rem; }
.report-card__body strong { font-size: 0.9rem; }
.report-card__body small { color: var(--rw-muted); font-size: 0.78rem; }
.reports__meta { color: var(--rw-muted); font-size: 0.8rem; margin: 0 0 0.75rem; }
.reports__empty, .reports__empty-row { color: var(--rw-muted); font-size: 0.88rem; padding: 1rem 0; text-align: center; }
.reports__table-wrap { overflow-x: auto; }
.reports__table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.reports__table th, .reports__table td { text-align: left; padding: 0.55rem 0.75rem; border-bottom: 1px solid var(--rw-border); white-space: nowrap; }
.reports__table th { color: var(--rw-muted); font-weight: 700; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.04em; }
@media (max-width: 960px) { .reports-page { padding: 1.75rem 1rem 3rem; } .reports__layout { grid-template-columns: 1fr; } }
</style>
