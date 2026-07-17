<template>
    <div class="ops-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 06" title="Change Requests" subtitle="Change governance gate for releases and automation.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} requests`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-clipboard-text-outline" @click="openCreate">New change request</v-btn>
                </template>
            </AppPageHeader>

            <AppSectionCard title="Change request register" subtitle="Approved change requests authorise deployment and automation.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search requests" prepend-inner-icon="mdi-magnify" class="ops__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="ops__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All change requests" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No change requests" empty-text="Log the first change request." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.title }}</strong><small>{{ row.reference }}{{ row.client_name ? ` · ${row.client_name}` : '' }}</small></div></td>
                        <td><AppStatusBadge :status="row.risk_color || row.risk" :label="row.risk_label || row.risk" /></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ row.scheduled_for || '-' }}</span></td>
                        <td class="text-right ops-actions">
                            <template v-if="!['approved', 'rejected'].includes(row.status)">
                                <v-btn size="small" variant="text" color="primary" @click.stop="decide(row, 'approve')">Approve</v-btn>
                                <v-btn size="small" variant="text" color="error" @click.stop="decide(row, 'reject')">Reject</v-btn>
                            </template>
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal v-model="dialog.open" :title="dialog.mode === 'create' ? 'New change request' : 'Edit change request'" subtitle="Change requests record the what, the risk, and the approval." persistent>
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.reference" label="Reference" :error-messages="dialog.errors.reference" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.risk" :items="riskItems" label="Risk" :error-messages="dialog.errors.risk" /></v-col>
                        <v-col cols="12"><AppTextField v-model="dialog.form.title" label="Title" :error-messages="dialog.errors.title" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.deployment_id" :items="deploymentItems" label="Deployment" :error-messages="dialog.errors.deployment_id" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.scheduled_for" label="Scheduled for" type="date" :error-messages="dialog.errors.scheduled_for" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.description" label="Description" :error-messages="dialog.errors.description" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="dialog.open = false">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create request' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Change Requests","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppTextarea from '../../components/AppTextarea.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useToast, errorMessage } from '../../composables/useToast';
import { useChangeRequestsStore } from '../../stores/change-requests';

const toast = useToast();
const store = useChangeRequestsStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'request', label: 'Change request' },
    { key: 'risk', label: 'Risk' },
    { key: 'status', label: 'Status' },
    { key: 'scheduled', label: 'Scheduled' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const riskItems = computed(() => store.options.risks.map((item) => ({ title: item.label, value: item.value })));
const clientItems = computed(() => toSelect(store.options.clients, 'No client'));
const deploymentItems = computed(() => toSelect(store.options.deployments, 'No deployment'));

const emptyForm = () => ({ reference: '', title: '', risk: 'low', client_id: '', deployment_id: '', scheduled_for: '', description: '', notes: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const openCreate = () => {
    const form = emptyForm();
    form.risk = store.options.risks[0]?.value ?? 'low';
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            reference: row.reference ?? '',
            title: row.title ?? '',
            risk: row.risk ?? 'low',
            client_id: row.client_id ?? '',
            deployment_id: row.deployment_id ?? '',
            scheduled_for: row.scheduled_for ?? '',
            description: row.description ?? '',
            notes: row.notes ?? '',
        },
        errors: {},
        message: '',
    });
};

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['client_id', 'deployment_id', 'scheduled_for', 'description', 'notes'].forEach((key) => { if (payload[key] === '') payload[key] = null; });
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
        dialog.open = false;
        await load();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
    }
};

const decide = async (row, action) => {
    try {
        await store.decide(row.id, action);
        await load();
        toast.success(action === 'approve' ? 'Change request approved.' : 'Change request rejected.');
    } catch (error) {
        toast.error(errorMessage(error, `Could not ${action} the change request.`));
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(load);
</script>

<style scoped>
.ops__search { flex: 0 1 320px; min-width: 240px; }
.ops__filter { min-width: 190px; }
.ops-actions { white-space: nowrap; }
</style>
