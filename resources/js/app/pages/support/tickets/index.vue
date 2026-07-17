<template>
    <div class="support-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 03" title="Support Tickets" subtitle="Client requests, issue categories, severity, assignment, hours logged, and resolution.">
                <template #metrics><AppStatusBadge status="active" :label="`${store.meta.total} tickets`" /></template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-view-column-outline" to="/support/board">Board view</v-btn>
                    <v-btn color="primary" prepend-icon="mdi-ticket-outline" @click="openCreate">New ticket</v-btn>
                </template>
            </AppPageHeader>
            <div class="support__stats">
                <AppStatCard label="Tickets" :value="String(store.meta.total)" helper="Current support work" icon="mdi-ticket-confirmation-outline" status="active" />
                <AppStatCard label="Open risk" :value="String(openRisk)" helper="High or critical open tickets" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Hours logged" :value="String(hoursLogged)" helper="Current result set" icon="mdi-clock-outline" status="processing" />
            </div>

            <AppBarChart
                v-if="store.throughput.length"
                title="Ticket throughput"
                subtitle="Tickets opened vs resolved per month."
                :categories="throughputCategories"
                :series="throughputSeries"
                :colors="['#2563eb', '#16a34a']"
            />

            <AppSectionCard title="Support ticket queue" subtitle="Log, assign, resolve, and close support requests linked to clients, deployments, and agreements.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search tickets" prepend-inner-icon="mdi-magnify" class="support__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="support__filter" @update:model-value="onFilter" />
                    <AppSelect v-model="filters.severity" :items="severityFilterItems" label="Severity" class="support__filter" @update:model-value="onFilter" />
                </AppFilterBar>
                <AppDataTable title="All tickets" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No tickets found" empty-text="Log the first support ticket." @page-change="onPage" @row-click="goToDetail">
                    <template #row="{ row }">
                        <td><div class="support-cell"><strong>{{ row.subject }}</strong><small>{{ row.reference }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.deployment_name || '-' }}</span></td>
                        <td><span class="text-sm">{{ categoryLabel(row.category) }}</span></td>
                        <td><AppStatusBadge :status="row.severity_color || row.severity" :label="row.severity_label || row.severity" /></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ row.hours_logged ?? 0 }}</span></td>
                        <td class="row-actions">
                            <v-btn v-if="canResolve(row)" size="small" variant="text" color="primary" :loading="inlineBusy === row.id" @click.stop="setStatus(row, 'resolved')">Resolve</v-btn>
                            <v-btn v-if="row.status === 'resolved'" size="small" variant="text" :loading="inlineBusy === row.id" @click.stop="setStatus(row, 'closed')">Close</v-btn>
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New support ticket' : 'Edit support ticket'"
            subtitle="Tickets capture client requests and move through open, in progress, resolved, and closed."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.reference" label="Reference" :error-messages="dialog.errors.reference" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.category" :items="categoryItems" label="Category" :error-messages="dialog.errors.category" /></v-col>
                        <v-col cols="12"><AppTextField v-model="dialog.form.subject" label="Subject" :error-messages="dialog.errors.subject" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.deployment_id" :items="deploymentItems" label="Deployment" :error-messages="dialog.errors.deployment_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.support_agreement_id" :items="agreementItems" label="Support agreement" :error-messages="dialog.errors.support_agreement_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.assigned_user_id" :items="userItems" label="Assigned to" :error-messages="dialog.errors.assigned_user_id" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.severity" :items="severityItems" label="Severity" :error-messages="dialog.errors.severity" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.status" :items="statusItems" label="Status" :error-messages="dialog.errors.status" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.hours_logged" label="Hours logged" type="number" :error-messages="dialog.errors.hours_logged" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.opened_at" label="Opened at" type="datetime-local" :error-messages="dialog.errors.opened_at" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.resolved_at" label="Resolved at" type="datetime-local" :error-messages="dialog.errors.resolved_at" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.summary" label="Summary" :error-messages="dialog.errors.summary" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create ticket' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>
<route lang="json">
{"meta":{"layout":"default","title":"Support Tickets","requiresAuth":true,"adminOnly":true}}
</route>
<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppBarChart from '../../../components/AppBarChart.vue';
import AppFilterBar from '../../../components/AppFilterBar.vue';
import AppModal from '../../../components/AppModal.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import AppTextarea from '../../../components/AppTextarea.vue';
import AppTextField from '../../../components/AppTextField.vue';
import { useToast, errorMessage } from '../../../composables/useToast';
import { useSupportTicketsStore } from '../../../stores/support-tickets';

const router = useRouter();
const toast = useToast();
const goToDetail = (row) => router.push(`/support/tickets/${row.id}`);
const store = useSupportTicketsStore();
const inlineBusy = ref(null);

const throughputCategories = computed(() => store.throughput.map((row) => row.period));
const throughputSeries = computed(() => [
    { name: 'Opened', data: store.throughput.map((row) => row.opened) },
    { name: 'Resolved', data: store.throughput.map((row) => row.resolved) },
]);
const filters = reactive({ search: '', status: '', severity: '', page: 1 });
const columns = [
    { key: 'ticket', label: 'Ticket' },
    { key: 'deployment', label: 'Deployment' },
    { key: 'category', label: 'Category' },
    { key: 'severity', label: 'Severity' },
    { key: 'status', label: 'Status' },
    { key: 'hours', label: 'Hours' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];

const clientItems = computed(() => toSelect(store.options.clients, 'Select a client'));
const deploymentItems = computed(() => toSelect(store.options.deployments, 'No deployment'));
const agreementItems = computed(() => toSelect(store.options.agreements, 'No agreement'));
const userItems = computed(() => toSelect(store.options.users, 'Unassigned'));
const categoryItems = computed(() => toSelect(store.options.categories, 'No category'));
const categoryLabel = (value) => store.options.categories.find((item) => item.value === value)?.label || value || '-';
const severityItems = computed(() => store.options.severities.map((item) => ({ title: item.label, value: item.value })));
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const severityFilterItems = computed(() => [{ title: 'All severities', value: '' }, ...severityItems.value]);

const openRisk = computed(() => store.rows.filter((row) => ['critical', 'high'].includes(row.severity) && !['resolved', 'closed'].includes(row.status)).length);
const hoursLogged = computed(() => store.rows.reduce((sum, row) => sum + Number(row.hours_logged || 0), 0));

const emptyForm = () => ({ client_id: '', deployment_id: '', support_agreement_id: '', assigned_user_id: '', reference: '', subject: '', category: '', severity: '', status: '', hours_logged: 0, opened_at: '', resolved_at: '', summary: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const toLocalInput = (value) => (value ? String(value).slice(0, 16) : '');
const defaultStatus = () => store.options.statuses.find((item) => item.value === 'open')?.value ?? store.options.statuses[0]?.value ?? '';
const defaultSeverity = () => store.options.severities.find((item) => item.value === 'medium')?.value ?? store.options.severities[0]?.value ?? '';

const openCreate = () => {
    const form = emptyForm();
    form.status = defaultStatus();
    form.severity = defaultSeverity();
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            client_id: row.client_id ?? '',
            deployment_id: row.deployment_id ?? '',
            support_agreement_id: row.support_agreement_id ?? '',
            assigned_user_id: row.assigned_user_id ?? '',
            reference: row.reference ?? '',
            subject: row.subject ?? '',
            category: row.category ?? '',
            severity: row.severity ?? '',
            status: row.status ?? '',
            hours_logged: row.hours_logged ?? 0,
            opened_at: toLocalInput(row.opened_at),
            resolved_at: toLocalInput(row.resolved_at),
            summary: row.summary ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['deployment_id', 'support_agreement_id', 'assigned_user_id', 'category', 'opened_at', 'resolved_at', 'summary'].forEach((key) => {
        if (payload[key] === '') payload[key] = null;
    });
    return payload;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        if (dialog.mode === 'create') {
            // New rows need server-derived fields, so refetch on create only.
            await store.create(normalizePayload());
            await load();
            toast.success('Ticket created.');
        } else {
            store.upsertRow(await store.update(dialog.editId, normalizePayload()));
            toast.success('Ticket updated.');
        }
        closeDialog();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const canResolve = (row) => !['resolved', 'closed'].includes(row.status);

// Inline status change reuses the update endpoint (no dedicated transition
// route); the service stamps resolved_at server-side.
const setStatus = async (row, status) => {
    inlineBusy.value = row.id;

    try {
        const payload = {
            client_id: row.client_id,
            deployment_id: row.deployment_id || null,
            support_agreement_id: row.support_agreement_id || null,
            assigned_user_id: row.assigned_user_id || null,
            reference: row.reference,
            subject: row.subject,
            category: row.category || null,
            severity: row.severity,
            status,
            hours_logged: row.hours_logged ?? 0,
            opened_at: row.opened_at || null,
            resolved_at: row.resolved_at || null,
            summary: row.summary || null,
        };

        store.upsertRow(await store.update(row.id, payload));
        toast.success(`Ticket ${status}.`);
    } catch (error) {
        toast.error(errorMessage(error, 'Could not update the ticket.'));
    } finally {
        inlineBusy.value = null;
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status, severity: filters.severity });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(load);
</script>
<style scoped>
.support__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
.support__search { flex: 0 1 320px; min-width: 240px; }
.support__filter { min-width: 190px; }
.row-actions { display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem; }
@media (max-width: 960px) { .support__stats { grid-template-columns: 1fr; } }
</style>
