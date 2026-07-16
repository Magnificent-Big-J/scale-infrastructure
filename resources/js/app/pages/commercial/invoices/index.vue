<template>
    <div class="commercial-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 04" title="Invoices" subtitle="Issued invoices, payment capture, outstanding balances, and overdue accounts.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} invoices`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-receipt-text-plus-outline" @click="openCreate">New invoice</v-btn>
                </template>
            </AppPageHeader>

            <div class="commercial__stats">
                <AppStatCard label="Outstanding" :value="formatAmount(finance.metrics.outstanding_total)" helper="Open invoice balance" icon="mdi-receipt-text-clock-outline" status="processing" />
                <AppStatCard label="Overdue" :value="formatAmount(finance.metrics.overdue_total)" :helper="`${finance.metrics.overdue_count} invoice(s) past due`" icon="mdi-alert-octagon-outline" status="suspended" />
                <AppStatCard label="Paid this month" :value="formatAmount(finance.metrics.payments_this_month)" helper="Payments captured" icon="mdi-cash-check" status="active" />
            </div>

            <AppSectionCard title="Invoice register" subtitle="Issue invoices and record payments against outstanding balances.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search invoices" prepend-inner-icon="mdi-magnify" class="commercial__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="commercial__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All invoices" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No invoices found" empty-text="Create the first invoice." @page-change="onPage" @row-click="goToDetail">
                    <template #row="{ row }">
                        <td><div class="commercial-cell"><strong>{{ row.number }}</strong><small>{{ row.client_name }}{{ row.contract_name ? ` · ${row.contract_name}` : '' }}</small></div></td>
                        <td><AppStatusBadge :status="row.is_overdue ? 'suspended' : (row.status_color || row.status)" :label="row.is_overdue ? 'Overdue' : (row.status_label || row.status)" /></td>
                        <td><span class="text-sm">{{ formatAmount(row.amount) }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.amount_paid) }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.outstanding) }}</span></td>
                        <td><span class="text-sm">{{ row.due_on || '-' }}</span></td>
                        <td class="text-right">
                            <v-btn icon="mdi-cash-plus" size="small" variant="text" title="Record payment" @click.stop="openPayment(row)" />
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New invoice' : 'Edit invoice'"
            subtitle="Invoices track issued amounts, due dates, and outstanding balances."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.number" label="Invoice number" :error-messages="dialog.errors.number" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.status" :items="statusItems" label="Status" :error-messages="dialog.errors.status" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.contract_id" :items="contractItems" label="Contract" :error-messages="dialog.errors.contract_id" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.amount" label="Amount" type="number" :error-messages="dialog.errors.amount" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.issued_on" label="Issued on" type="date" :error-messages="dialog.errors.issued_on" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.due_on" label="Due on" type="date" :error-messages="dialog.errors.due_on" /></v-col>
                        <v-col cols="12"><AppTextField v-model="dialog.form.external_reference" label="External reference" :error-messages="dialog.errors.external_reference" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create invoice' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>

        <AppModal
            v-model="payment.open"
            title="Record payment"
            :subtitle="payment.invoice ? `${payment.invoice.number} · outstanding ${formatAmount(payment.invoice.outstanding)}` : 'Capture a payment against this invoice.'"
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="payment.message" :type="payment.messageType" />
                <v-form @submit.prevent="submitPayment">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="payment.form.amount" label="Amount" type="number" :error-messages="payment.errors.amount" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="payment.form.method" :items="methodItems" label="Method" :error-messages="payment.errors.method" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="payment.form.paid_on" label="Paid on" type="date" :error-messages="payment.errors.paid_on" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="payment.form.reference" label="Reference" :error-messages="payment.errors.reference" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="payment.form.notes" label="Notes" :error-messages="payment.errors.notes" /></v-col>
                    </v-row>
                </v-form>

                <div v-if="payment.history.length" class="payment-history">
                    <h4>Previous payments</h4>
                    <ul>
                        <li v-for="entry in payment.history" :key="entry.id">
                            <span>{{ formatAmount(entry.amount) }} · {{ entry.method_label }}</span>
                            <small>{{ entry.paid_on }}{{ entry.reference ? ` · ${entry.reference}` : '' }}</small>
                        </li>
                    </ul>
                </div>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closePayment">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitPayment">Record payment</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Invoices","requiresAuth":true,"adminOnly":true}}
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
import { useFinanceStore } from '../../../stores/finance';
import { useInvoicesStore } from '../../../stores/invoices';

const router = useRouter();
const toast = useToast();
const goToDetail = (row) => router.push(`/commercial/invoices/${row.id}`);
const store = useInvoicesStore();
const finance = useFinanceStore();
const filters = reactive({ search: '', status: '', page: 1 });
const paymentMethods = [
    { value: 'eft', label: 'EFT' },
    { value: 'card', label: 'Card' },
    { value: 'payfast', label: 'PayFast' },
    { value: 'debit_order', label: 'Debit order' },
    { value: 'cash', label: 'Cash' },
    { value: 'other', label: 'Other' },
];
const columns = [
    { key: 'invoice', label: 'Invoice' },
    { key: 'status', label: 'Status' },
    { key: 'amount', label: 'Amount' },
    { key: 'paid', label: 'Paid' },
    { key: 'outstanding', label: 'Outstanding' },
    { key: 'due', label: 'Due' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const clientItems = computed(() => toSelect(store.options.clients, 'Select a client'));
const contractItems = computed(() => toSelect(store.options.contracts, 'No contract'));
const methodItems = computed(() => paymentMethods.map((item) => ({ title: item.label, value: item.value })));

const formatAmount = (value) => `ZAR ${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

const todayIso = () => new Date().toISOString().slice(0, 10);

const emptyForm = () => ({ client_id: '', contract_id: '', number: '', status: '', amount: 0, issued_on: '', due_on: '', external_reference: '', notes: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const emptyPaymentForm = () => ({ amount: 0, method: 'eft', paid_on: todayIso(), reference: '', notes: '' });
const payment = reactive({ open: false, invoice: null, form: emptyPaymentForm(), history: [], errors: {}, message: '', messageType: 'error' });

const defaultStatus = () => store.options.statuses.find((item) => item.value === 'draft')?.value ?? store.options.statuses[0]?.value ?? '';

const openCreate = () => {
    const form = emptyForm();
    form.status = defaultStatus();
    form.issued_on = todayIso();
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
            number: row.number ?? '',
            status: row.status ?? '',
            amount: row.amount ?? 0,
            issued_on: row.issued_on ?? '',
            due_on: row.due_on ?? '',
            external_reference: row.external_reference ?? '',
            notes: row.notes ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['contract_id', 'issued_on', 'due_on', 'external_reference', 'notes'].forEach((key) => {
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
            await Promise.all([load(), finance.fetchMetrics()]);
            toast.success('Invoice created.');
        } else {
            store.upsertRow(await store.update(dialog.editId, normalizePayload()));
            finance.fetchMetrics();
            toast.success('Invoice updated.');
        }
        closeDialog();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const openPayment = async (row) => {
    const form = emptyPaymentForm();
    form.amount = Number(row.outstanding || 0);
    Object.assign(payment, { open: true, invoice: row, form, history: [], errors: {}, message: '' });

    try {
        const detail = await store.show(row.id);
        payment.invoice = detail;
        payment.history = detail?.payments ?? [];
    } catch {
        payment.history = [];
    }
};

const closePayment = () => { payment.open = false; };

const submitPayment = async () => {
    payment.errors = {};
    payment.message = '';

    const body = { ...payment.form };
    if (body.reference === '') body.reference = null;
    if (body.notes === '') body.notes = null;

    try {
        await store.recordPayment(payment.invoice.id, body);
        closePayment();
        await Promise.all([load(), finance.fetchMetrics()]);
        toast.success('Payment recorded.');
    } catch (error) {
        payment.errors = error?.data?.errors ?? {};
        payment.message = error?.data?.message || 'Something went wrong.';
        payment.messageType = 'error';
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(() => { load(); finance.fetchMetrics(); });
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
.payment-history h4 { font-size: 0.85rem; margin-bottom: 0.5rem; }
.payment-history ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.4rem; }
.payment-history li { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.85rem; border-top: 1px solid var(--rw-border, rgba(255, 255, 255, 0.08)); padding-top: 0.4rem; }
.payment-history small { color: var(--rw-muted); }
@media (max-width: 960px) { .commercial-page { padding: 1.75rem 1rem 3rem; } .commercial__stats { grid-template-columns: 1fr; } }
</style>
