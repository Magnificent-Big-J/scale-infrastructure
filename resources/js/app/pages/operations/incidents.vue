<template>
    <div class="support-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 03" title="Incidents" subtitle="Operational incidents, severity, root cause, resolution, and deployment links.">
                <template #metrics><AppStatusBadge status="active" :label="`${store.meta.total} incidents`" /></template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-alert-plus-outline" @click="openCreate">Log incident</v-btn>
                </template>
            </AppPageHeader>
            <div class="support__stats">
                <AppStatCard label="Incidents" :value="String(store.meta.total)" helper="Operational incident records" icon="mdi-alert-octagon-outline" status="active" />
                <AppStatCard label="Open" :value="String(openCount)" helper="Not yet resolved or closed" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Resolved" :value="String(resolvedCount)" helper="Resolved or closed incidents" icon="mdi-check-circle-outline" status="active" />
            </div>
            <AppSectionCard title="Incident register" subtitle="Log, investigate, and resolve incidents linked to clients and deployments.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search incidents" prepend-inner-icon="mdi-magnify" class="support__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="support__filter" @update:model-value="onFilter" />
                    <AppSelect v-model="filters.severity" :items="severityFilterItems" label="Severity" class="support__filter" @update:model-value="onFilter" />
                </AppFilterBar>
                <AppDataTable title="All incidents" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No incidents found" empty-text="Log the first incident." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="support-cell"><strong>{{ row.title }}</strong><small>{{ row.reference }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.deployment_name || '-' }}</span></td>
                        <td><AppStatusBadge :status="row.severity_color || row.severity" :label="row.severity_label || row.severity" /></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ formatDate(row.started_at) }}</span></td>
                        <td class="row-actions">
                            <v-btn v-if="canResolve(row)" size="small" variant="text" color="primary" :loading="inlineBusy === row.id" @click.stop="setStatus(row, 'resolved')">Resolve</v-btn>
                            <v-btn v-if="row.status === 'resolved'" size="small" variant="text" :loading="inlineBusy === row.id" @click.stop="setStatus(row, 'closed')">Close</v-btn>
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'Log incident' : 'Edit incident'"
            subtitle="Incidents track severity, root cause, and resolution against a client deployment."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.reference" label="Reference" :error-messages="dialog.errors.reference" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.status" :items="statusItems" label="Status" :error-messages="dialog.errors.status" /></v-col>
                        <v-col cols="12"><AppTextField v-model="dialog.form.title" label="Title" :error-messages="dialog.errors.title" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.deployment_id" :items="deploymentItems" label="Deployment" :error-messages="dialog.errors.deployment_id" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.severity" :items="severityItems" label="Severity" :error-messages="dialog.errors.severity" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.started_at" label="Started at" type="datetime-local" :error-messages="dialog.errors.started_at" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.resolved_at" label="Resolved at" type="datetime-local" :error-messages="dialog.errors.resolved_at" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.root_cause" label="Root cause" :error-messages="dialog.errors.root_cause" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.resolution_summary" label="Resolution summary" :error-messages="dialog.errors.resolution_summary" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Log incident' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>
<route lang="json">
{"meta":{"layout":"default","title":"Incidents","requiresAuth":true,"adminOnly":true}}
</route>
<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextarea from '../../components/AppTextarea.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useToast, errorMessage } from '../../composables/useToast';
import { useIncidentsStore } from '../../stores/incidents';

const toast = useToast();
const store = useIncidentsStore();
const inlineBusy = ref(null);
const filters = reactive({ search: '', status: '', severity: '', page: 1 });
const columns = [
    { key: 'incident', label: 'Incident' },
    { key: 'deployment', label: 'Deployment' },
    { key: 'severity', label: 'Severity' },
    { key: 'status', label: 'Status' },
    { key: 'started', label: 'Started' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const severityItems = computed(() => store.options.severities.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const severityFilterItems = computed(() => [{ title: 'All severities', value: '' }, ...severityItems.value]);
const clientItems = computed(() => toSelect(store.options.clients, 'Select a client'));
const deploymentItems = computed(() => toSelect(store.options.deployments, 'No deployment'));

const openCount = computed(() => store.rows.filter((row) => !['resolved', 'closed'].includes(row.status)).length);
const resolvedCount = computed(() => store.rows.filter((row) => ['resolved', 'closed'].includes(row.status)).length);
const formatDate = (value) => (value ? new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-');

const emptyForm = () => ({ client_id: '', deployment_id: '', reference: '', title: '', severity: '', status: '', started_at: '', resolved_at: '', root_cause: '', resolution_summary: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const toLocalInput = (value) => (value ? String(value).slice(0, 16) : '');
const defaultStatus = () => store.options.statuses.find((item) => item.value === 'open')?.value ?? store.options.statuses[0]?.value ?? '';
const defaultSeverity = () => store.options.severities.find((item) => item.value === 'high')?.value ?? store.options.severities[0]?.value ?? '';

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
            reference: row.reference ?? '',
            title: row.title ?? '',
            severity: row.severity ?? '',
            status: row.status ?? '',
            started_at: toLocalInput(row.started_at),
            resolved_at: toLocalInput(row.resolved_at),
            root_cause: row.root_cause ?? '',
            resolution_summary: row.resolution_summary ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['deployment_id', 'started_at', 'resolved_at', 'root_cause', 'resolution_summary'].forEach((key) => {
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
            toast.success('Incident logged.');
        } else {
            store.upsertRow(await store.update(dialog.editId, normalizePayload()));
            toast.success('Incident updated.');
        }
        closeDialog();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const canResolve = (row) => !['resolved', 'closed'].includes(row.status);

const setStatus = async (row, status) => {
    inlineBusy.value = row.id;

    try {
        const payload = {
            client_id: row.client_id,
            deployment_id: row.deployment_id || null,
            reference: row.reference,
            title: row.title,
            severity: row.severity,
            status,
            started_at: row.started_at || null,
            resolved_at: row.resolved_at || null,
            root_cause: row.root_cause || null,
            resolution_summary: row.resolution_summary || null,
        };

        store.upsertRow(await store.update(row.id, payload));
        toast.success(`Incident ${status}.`);
    } catch (error) {
        toast.error(errorMessage(error, 'Could not update the incident.'));
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
.support-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.support__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
.support__search { min-width: min(320px, 100%); }
.support__filter { min-width: 190px; }
.support-cell { display: grid; gap: 0.1rem; }
.support-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.text-sm { font-size: 0.85rem; }
.row-actions { display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem; }
.dialog-form { display: grid; gap: 1rem; }
@media (max-width: 960px) { .support-page { padding: 1.75rem 1rem 3rem; } .support__stats { grid-template-columns: 1fr; } }
</style>
