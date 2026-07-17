<template>
    <div class="commercial-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 05" title="Profitability" subtitle="Revenue, cost, profit, and margin per client and period.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} records`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-finance" @click="openCreate">New record</v-btn>
                </template>
            </AppPageHeader>

            <div class="commercial__stats commercial__stats--four">
                <AppStatCard label="Revenue" :value="formatAmount(store.summary.revenue_total)" helper="Across all records" icon="mdi-cash-multiple" status="active" />
                <AppStatCard label="Cost" :value="formatAmount(store.summary.cost_total)" helper="Hosting, labour, monitoring, other" icon="mdi-cash-minus" status="processing" />
                <AppStatCard label="Profit" :value="formatAmount(store.summary.profit_total)" helper="Revenue less cost" :status="store.summary.profit_total >= 0 ? 'active' : 'suspended'" icon="mdi-cash-plus" />
                <AppStatCard label="Avg margin" :value="`${Number(store.summary.margin_avg || 0).toFixed(1)}%`" helper="Blended margin" icon="mdi-percent-outline" status="active" />
            </div>

            <AppLineChart
                v-if="store.trend.length"
                title="Revenue, cost & profit by period"
                subtitle="Totals across all clients, oldest to newest."
                :categories="trendCategories"
                :series="trendSeries"
                :colors="['#2563eb', '#d97706', '#16a34a']"
            />

            <AppSectionCard title="Profitability records" subtitle="Per-client, per-period revenue and cost breakdown with computed margin.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search records" prepend-inner-icon="mdi-magnify" class="commercial__search" @update:model-value="onSearch" />
                    <AppTextField v-model="filters.period" label="Period (YYYY-MM)" class="commercial__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All records" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No records found" empty-text="Create the first profitability record." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="commercial-cell"><strong>{{ row.client_name }}</strong><small>{{ row.period }}</small></div></td>
                        <td><span class="text-sm">{{ formatAmount(row.revenue) }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.total_cost) }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.profit) }}</span></td>
                        <td><AppStatusBadge :status="Number(row.margin) >= 0 ? 'active' : 'suspended'" :label="`${Number(row.margin).toFixed(1)}%`" /></td>
                        <td><v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New profitability record' : 'Edit profitability record'"
            subtitle="Profit and margin are calculated from revenue and the cost breakdown."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.period" label="Period (YYYY-MM)" placeholder="2026-06" :error-messages="dialog.errors.period" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.revenue" label="Revenue" type="number" :error-messages="dialog.errors.revenue" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.hosting_cost" label="Hosting cost" type="number" :error-messages="dialog.errors.hosting_cost" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.labour_cost" label="Labour cost" type="number" :error-messages="dialog.errors.labour_cost" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.monitoring_cost" label="Monitoring cost" type="number" :error-messages="dialog.errors.monitoring_cost" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.other_cost" label="Other cost" type="number" :error-messages="dialog.errors.other_cost" /></v-col>
                        <v-col cols="12">
                            <div class="profit-preview">
                                <span>Projected profit <strong>{{ formatAmount(previewProfit) }}</strong></span>
                                <span>Margin <strong>{{ previewMargin }}%</strong></span>
                            </div>
                        </v-col>
                        <v-col cols="12"><AppRichTextEditor v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create record' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Profitability","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppLineChart from '../../components/AppLineChart.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppRichTextEditor from '../../components/AppRichTextEditor.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useProfitabilityStore } from '../../stores/profitability';

const store = useProfitabilityStore();
const filters = reactive({ search: '', period: '', page: 1 });

const trendCategories = computed(() => store.trend.map((row) => row.period));
const trendSeries = computed(() => [
    { name: 'Revenue', data: store.trend.map((row) => Number(row.revenue)) },
    { name: 'Cost', data: store.trend.map((row) => Number(row.cost)) },
    { name: 'Profit', data: store.trend.map((row) => Number(row.profit)) },
]);
const columns = [
    { key: 'record', label: 'Client / period' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'cost', label: 'Cost' },
    { key: 'profit', label: 'Profit' },
    { key: 'margin', label: 'Margin' },
    { key: 'actions', label: '', class: 'text-right' },
];

const clientItems = computed(() => [{ title: 'Select a client', value: '' }, ...store.options.clients.map((item) => ({ title: item.label, value: item.value }))]);
const formatAmount = (value) => `ZAR ${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

const emptyForm = () => ({ client_id: '', period: '', revenue: 0, hosting_cost: 0, labour_cost: 0, monitoring_cost: 0, other_cost: 0, notes: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const previewProfit = computed(() => {
    const f = dialog.form;
    return Number(f.revenue || 0) - (Number(f.hosting_cost || 0) + Number(f.labour_cost || 0) + Number(f.monitoring_cost || 0) + Number(f.other_cost || 0));
});
const previewMargin = computed(() => {
    const revenue = Number(dialog.form.revenue || 0);
    return revenue > 0 ? ((previewProfit.value / revenue) * 100).toFixed(1) : '0.0';
});

const openCreate = () => {
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            client_id: row.client_id ?? '',
            period: row.period ?? '',
            revenue: row.revenue ?? 0,
            hosting_cost: row.hosting_cost ?? 0,
            labour_cost: row.labour_cost ?? 0,
            monitoring_cost: row.monitoring_cost ?? 0,
            other_cost: row.other_cost ?? 0,
            notes: row.notes ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    if (payload.notes === '') payload.notes = null;
    return payload;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        if (dialog.mode === 'create') {
            await store.create(normalizePayload());
        } else {
            await store.update(dialog.editId, normalizePayload());
        }
        closeDialog();
        await load();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, period: filters.period });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(load);
</script>

<style scoped>
.commercial__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.commercial__search { flex: 0 1 320px; min-width: 240px; }
.commercial__filter { min-width: 180px; }
.profit-preview { display: flex; gap: 2rem; padding: 0.75rem 1rem; border-radius: 12px; background: var(--rw-50); font-size: 0.9rem; }
@media (max-width: 1200px) { .commercial__stats--four { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .commercial-page { padding: 1.75rem 1rem 3rem; } .commercial__stats, .commercial__stats--four { grid-template-columns: 1fr; } }
</style>
