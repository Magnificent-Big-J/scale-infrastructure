<template>
    <div class="commercial-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 04" title="Contracts" subtitle="Commercial agreements linked to clients, products, packages, renewals, and values.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} contracts`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-file-sign" @click="openCreate">New contract</v-btn>
                </template>
            </AppPageHeader>

            <div class="commercial__stats">
                <AppStatCard label="Contracts" :value="String(store.meta.total)" helper="Agreements in the registry" icon="mdi-file-document-multiple-outline" status="active" />
                <AppStatCard label="Total value" :value="totalValue" helper="Current result set" icon="mdi-cash-multiple" status="processing" />
                <AppStatCard label="Monthly value" :value="monthlyValue" helper="Recurring contract value" icon="mdi-calendar-sync-outline" status="active" />
            </div>

            <AppSectionCard title="Contract registry" subtitle="Track contract value, renewal dates, and lifecycle status.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search contracts" prepend-inner-icon="mdi-magnify" class="commercial__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="commercial__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All contracts" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No contracts found" empty-text="Create the first contract." @page-change="onPage" @row-click="goToDetail">
                    <template #row="{ row }">
                        <td><div class="commercial-cell"><strong>{{ row.name }}</strong><small>{{ row.code }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.package_name || row.product_name || '-' }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.total_value) }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.monthly_value) }}</span></td>
                        <td><span class="text-sm">{{ row.renewal_date || '-' }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New contract' : 'Edit contract'"
            subtitle="Contracts anchor billing records, invoices, and renewal visibility."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.code" label="Code" :error-messages="dialog.errors.code" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.status" :items="statusItems" label="Status" :error-messages="dialog.errors.status" /></v-col>
                        <v-col cols="12"><AppTextField v-model="dialog.form.name" label="Name" :error-messages="dialog.errors.name" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.product_id" :items="productItems" label="Product" :error-messages="dialog.errors.product_id" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.package_id" :items="packageItems" label="Package" :error-messages="dialog.errors.package_id" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.total_value" label="Total value" type="number" :error-messages="dialog.errors.total_value" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.monthly_value" label="Monthly value" type="number" :error-messages="dialog.errors.monthly_value" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.starts_on" label="Starts on" type="date" :error-messages="dialog.errors.starts_on" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.renewal_date" label="Renewal date" type="date" :error-messages="dialog.errors.renewal_date" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.ends_on" label="Ends on" type="date" :error-messages="dialog.errors.ends_on" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create contract' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Contracts","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import AppFilterBar from '../../../components/AppFilterBar.vue';
import AppModal from '../../../components/AppModal.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import AppTextarea from '../../../components/AppTextarea.vue';
import AppTextField from '../../../components/AppTextField.vue';
import { useToast } from '../../../composables/useToast';
import { useContractsStore } from '../../../stores/contracts';

const router = useRouter();
const toast = useToast();
const goToDetail = (row) => router.push(`/commercial/contracts/${row.id}`);
const store = useContractsStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'contract', label: 'Contract' },
    { key: 'offering', label: 'Offering' },
    { key: 'total', label: 'Total' },
    { key: 'monthly', label: 'Monthly' },
    { key: 'renewal', label: 'Renewal' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const clientItems = computed(() => toSelect(store.options.clients, 'Select a client'));
const productItems = computed(() => toSelect(store.options.products, 'No product'));
const packageItems = computed(() => toSelect(store.options.packages, 'No package'));

const formatAmount = (value) => (value ? `ZAR ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '-');
const totalValue = computed(() => formatAmount(store.rows.reduce((sum, row) => sum + Number(row.total_value || 0), 0)));
const monthlyValue = computed(() => formatAmount(store.rows.reduce((sum, row) => sum + Number(row.monthly_value || 0), 0)));

const emptyForm = () => ({ client_id: '', product_id: '', package_id: '', code: '', name: '', total_value: 0, monthly_value: 0, starts_on: '', renewal_date: '', ends_on: '', status: '', notes: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const defaultStatus = () => store.options.statuses.find((item) => item.value === 'active')?.value ?? store.options.statuses[0]?.value ?? '';

const openCreate = () => {
    const form = emptyForm();
    form.status = defaultStatus();
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            client_id: row.client_id ?? '',
            product_id: row.product_id ?? '',
            package_id: row.package_id ?? '',
            code: row.code ?? '',
            name: row.name ?? '',
            total_value: row.total_value ?? 0,
            monthly_value: row.monthly_value ?? 0,
            starts_on: row.starts_on ?? '',
            renewal_date: row.renewal_date ?? '',
            ends_on: row.ends_on ?? '',
            status: row.status ?? '',
            notes: row.notes ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['product_id', 'package_id', 'starts_on', 'renewal_date', 'ends_on', 'notes'].forEach((key) => {
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
            await load();
            toast.success('Contract created.');
        } else {
            store.upsertRow(await store.update(dialog.editId, normalizePayload()));
            toast.success('Contract updated.');
        }
        closeDialog();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(load);
</script>

<style scoped>
.commercial-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.commercial__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
.commercial__search { flex: 0 1 320px; min-width: 240px; }
.commercial__filter { min-width: 190px; }
.commercial-cell { display: grid; gap: 0.1rem; }
.commercial-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.text-sm { font-size: 0.85rem; }
.dialog-form { display: grid; gap: 1rem; }
@media (max-width: 960px) { .commercial-page { padding: 1.75rem 1rem 3rem; } .commercial__stats { grid-template-columns: 1fr; } }
</style>
