<template>
    <div class="ops-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 06" title="Releases" subtitle="Version tracking with permission-gated approval, deployment, and rollback.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} releases`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-source-branch-plus" @click="openCreate">New release</v-btn>
                </template>
            </AppPageHeader>

            <AppSectionCard title="Release register" subtitle="Releases belong to deployments and move through approval, deployment, and rollback.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search releases" prepend-inner-icon="mdi-magnify" class="ops__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="ops__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All releases" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No releases found" empty-text="Record the first release." @page-change="onPage" @row-click="goToDetail">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.version }}</strong><small>{{ row.deployment_name }}{{ row.client_name ? ` · ${row.client_name}` : '' }}</small></div></td>
                        <td><span class="text-sm">{{ row.change_request_reference || '-' }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ row.deployed_at ? formatDate(row.deployed_at) : (row.approved_at ? `Approved ${formatDate(row.approved_at)}` : '-') }}</span></td>
                        <td class="text-right ops-actions">
                            <v-btn v-if="['draft', 'pending_approval'].includes(row.status)" size="small" variant="text" color="primary" @click.stop="act(row, 'approve')">Approve</v-btn>
                            <v-btn v-if="row.status === 'approved'" size="small" variant="text" color="primary" @click.stop="act(row, 'deploy')">Deploy</v-btn>
                            <v-btn v-if="row.status === 'deployed'" size="small" variant="text" color="error" @click.stop="openRollback(row)">Rollback</v-btn>
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal v-model="dialog.open" :title="dialog.mode === 'create' ? 'New release' : 'Edit release'" subtitle="Releases belong to a deployment and may reference a change request." persistent>
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.deployment_id" :items="deploymentItems" label="Deployment" :error-messages="dialog.errors.deployment_id" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.version" label="Version" placeholder="2026.07.0" :error-messages="dialog.errors.version" /></v-col>
                        <v-col cols="12"><AppSelect v-model="dialog.form.change_request_id" :items="changeRequestItems" label="Change request" :error-messages="dialog.errors.change_request_id" /></v-col>
                        <v-col cols="12"><AppRichTextEditor v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="dialog.open = false">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create release' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>

        <AppModal v-model="rollback.open" title="Roll back release" subtitle="Rollback is restricted and recorded against the release." persistent>
            <div class="dialog-form">
                <FormStatusAlert :message="rollback.message" :type="rollback.messageType" />
                <AppRichTextEditor v-model="rollback.notes" label="Rollback notes" />
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="rollback.open = false">Cancel</v-btn>
                <v-btn color="error" :loading="store.loading" @click="submitRollback">Roll back</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Releases","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import AppFilterBar from '../../../components/AppFilterBar.vue';
import AppModal from '../../../components/AppModal.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppRichTextEditor from '../../../components/AppRichTextEditor.vue';
import AppTextField from '../../../components/AppTextField.vue';
import { useToast, errorMessage } from '../../../composables/useToast';
import { useReleasesStore } from '../../../stores/releases';

const router = useRouter();
const toast = useToast();
const goToDetail = (row) => router.push(`/operations/releases/${row.id}`);
const store = useReleasesStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'release', label: 'Release' },
    { key: 'change', label: 'Change request' },
    { key: 'status', label: 'Status' },
    { key: 'timing', label: 'Approved / deployed' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const deploymentItems = computed(() => toSelect(store.options.deployments, 'Select a deployment'));
const changeRequestItems = computed(() => toSelect(store.options.change_requests, 'No change request'));

const formatDate = (value) => (value ? new Date(value).toLocaleDateString() : '-');

const emptyForm = () => ({ deployment_id: '', version: '', change_request_id: '', notes: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });
const rollback = reactive({ open: false, releaseId: null, notes: '', message: '', messageType: 'error' });

const openCreate = () => { Object.assign(dialog, { open: true, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '' }); };

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: { deployment_id: row.deployment_id ?? '', version: row.version ?? '', change_request_id: row.change_request_id ?? '', notes: row.notes ?? '' },
        errors: {},
        message: '',
    });
};

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['change_request_id', 'notes'].forEach((key) => { if (payload[key] === '') payload[key] = null; });
    return payload;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        if (dialog.mode === 'create') {
            await store.create(normalizePayload());
            await load();
            toast.success('Release created.');
        } else {
            const updated = await store.update(dialog.editId, normalizePayload());
            store.upsertRow(updated?.data ?? updated);
            toast.success('Release updated.');
        }
        dialog.open = false;
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
    }
};

const act = async (row, action) => {
    try {
        const updated = await store.transition(row.id, action);
        store.upsertRow(updated?.data ?? updated);
        toast.success(`Release ${action}d.`);
    } catch (error) {
        toast.error(errorMessage(error, `Could not ${action} the release.`));
    }
};

const openRollback = (row) => { Object.assign(rollback, { open: true, releaseId: row.id, notes: '', message: '' }); };

const submitRollback = async () => {
    rollback.message = '';

    try {
        const updated = await store.transition(rollback.releaseId, 'rollback', { rollback_notes: rollback.notes || null });
        store.upsertRow(updated?.data ?? updated);
        rollback.open = false;
        toast.success('Release rolled back.');
    } catch (error) {
        rollback.message = error?.data?.message || 'Could not roll back the release.';
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
