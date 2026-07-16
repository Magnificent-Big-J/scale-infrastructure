<template>
    <div class="commercial-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 04" title="Billing" subtitle="Billing records, recurring revenue, MRR, ARR, and outstanding account visibility.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} records`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-cash-plus" @click="openCreate">New billing record</v-btn>
                </template>
            </AppPageHeader>

            <div class="commercial__stats commercial__stats--four">
                <AppStatCard label="MRR" :value="formatAmount(finance.metrics.mrr)" helper="Monthly recurring revenue" icon="mdi-calendar-sync-outline" status="active" />
                <AppStatCard label="ARR" :value="formatAmount(finance.metrics.arr)" helper="Annual run rate" icon="mdi-chart-line" status="processing" />
                <AppStatCard label="Outstanding" :value="formatAmount(finance.metrics.outstanding_total)" helper="Open invoice balance" icon="mdi-receipt-text-clock-outline" status="processing" />
                <AppStatCard label="Overdue" :value="formatAmount(finance.metrics.overdue_total)" :helper="`${finance.metrics.overdue_count} invoice(s) past due`" icon="mdi-alert-octagon-outline" status="suspended" />
            </div>

            <AppSectionCard title="Billing commitments" subtitle="Once-off and recurring revenue captured per client and contract.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search records" prepend-inner-icon="mdi-magnify" class="commercial__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.type" :items="typeFilterItems" label="Type" class="commercial__filter" @update:model-value="onFilter" />
                    <AppSelect v-model="filters.cadence" :items="cadenceFilterItems" label="Cadence" class="commercial__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All billing records" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No billing records found" empty-text="Create the first billing commitment." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="commercial-cell"><strong>{{ row.description }}</strong><small>{{ row.client_name }}{{ row.contract_name ? ` · ${row.contract_name}` : '' }}</small></div></td>
                        <td><span class="text-sm">{{ row.type_label }}</span></td>
                        <td><span class="text-sm">{{ row.cadence_label }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.amount) }}</span></td>
                        <td><AppStatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Active' : 'Inactive'" /></td>
                        <td><v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New billing record' : 'Edit billing record'"
            subtitle="Capture assessment, implementation, support, hosting, enhancement, and consulting revenue."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12"><AppTextField v-model="dialog.form.description" label="Description" :error-messages="dialog.errors.description" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.contract_id" :items="contractItems" label="Contract" :error-messages="dialog.errors.contract_id" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.type" :items="typeItems" label="Type" :error-messages="dialog.errors.type" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.cadence" :items="cadenceItems" label="Cadence" :error-messages="dialog.errors.cadence" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.amount" label="Amount" type="number" :error-messages="dialog.errors.amount" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.starts_on" label="Starts on" type="date" :error-messages="dialog.errors.starts_on" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.ends_on" label="Ends on" type="date" :error-messages="dialog.errors.ends_on" /></v-col>
                        <v-col cols="12"><v-switch v-model="dialog.form.is_active" color="primary" label="Active" hide-details inset /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" /></v-col>
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
{"meta":{"layout":"default","title":"Billing","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextarea from '../../components/AppTextarea.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useBillingRecordsStore } from '../../stores/billing-records';
import { useFinanceStore } from '../../stores/finance';

const store = useBillingRecordsStore();
const finance = useFinanceStore();
const filters = reactive({ search: '', type: '', cadence: '', page: 1 });
const columns = [
    { key: 'description', label: 'Record' },
    { key: 'type', label: 'Type' },
    { key: 'cadence', label: 'Cadence' },
    { key: 'amount', label: 'Amount' },
    { key: 'active', label: 'Active' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const typeItems = computed(() => store.options.types.map((item) => ({ title: item.label, value: item.value })));
const cadenceItems = computed(() => store.options.cadences.map((item) => ({ title: item.label, value: item.value })));
const typeFilterItems = computed(() => [{ title: 'All types', value: '' }, ...typeItems.value]);
const cadenceFilterItems = computed(() => [{ title: 'All cadences', value: '' }, ...cadenceItems.value]);
const clientItems = computed(() => toSelect(store.options.clients, 'Select a client'));
const contractItems = computed(() => toSelect(store.options.contracts, 'No contract'));

const formatAmount = (value) => `ZAR ${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

const emptyForm = () => ({ client_id: '', contract_id: '', type: '', cadence: '', description: '', amount: 0, starts_on: '', ends_on: '', is_active: true, notes: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const defaultValue = (items, preferred) => items.find((item) => item.value === preferred)?.value ?? items[0]?.value ?? '';

const openCreate = () => {
    const form = emptyForm();
    form.type = defaultValue(store.options.types, 'implementation');
    form.cadence = defaultValue(store.options.cadences, 'once_off');
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            client_id: row.client_id ?? '',
            contract_id: row.contract_id ?? '',
            type: row.type ?? '',
            cadence: row.cadence ?? '',
            description: row.description ?? '',
            amount: row.amount ?? 0,
            starts_on: row.starts_on ?? '',
            ends_on: row.ends_on ?? '',
            is_active: row.is_active ?? true,
            notes: row.notes ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['contract_id', 'starts_on', 'ends_on', 'notes'].forEach((key) => {
        if (payload[key] === '') payload[key] = null;
    });
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
        await Promise.all([load(), finance.fetchMetrics()]);
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, type: filters.type, cadence: filters.cadence });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(() => { load(); finance.fetchMetrics(); });
</script>

<style scoped>
.commercial-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.commercial__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
.commercial__stats--four { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.commercial__search { min-width: min(320px, 100%); }
.commercial__filter { min-width: 180px; }
.commercial-cell { display: grid; gap: 0.1rem; }
.commercial-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.text-sm { font-size: 0.85rem; }
.dialog-form { display: grid; gap: 1rem; }
@media (max-width: 1200px) { .commercial__stats--four { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .commercial-page { padding: 1.75rem 1rem 3rem; } .commercial__stats, .commercial__stats--four { grid-template-columns: 1fr; } }
</style>
