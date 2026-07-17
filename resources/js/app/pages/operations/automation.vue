<template>
    <div class="ops-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 06" title="Automation Runs" subtitle="Audited provisioning and automation attempts, gated by change approval.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} runs`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-robot-outline" @click="openCreate">Record run</v-btn>
                </template>
            </AppPageHeader>

            <AppSectionCard title="Automation log" subtitle="Every run is linked to an approved change request for auditability.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search runs" prepend-inner-icon="mdi-magnify" class="ops__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="ops__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All runs" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No automation runs" empty-text="Record the first automation run." @page-change="onPage">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.reference }}</strong><small>{{ row.template_name || '-' }}</small></div></td>
                        <td><span class="text-sm">{{ row.deployment_name || row.client_name || '-' }}</span></td>
                        <td><span class="text-sm">{{ row.change_request_reference || '-' }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ row.started_at ? formatDate(row.started_at) : '-' }}</span></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal v-model="dialog.open" title="Record automation run" subtitle="Runs require an approved change request and must not bypass change approval." persistent>
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.reference" label="Reference" :error-messages="dialog.errors.reference" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.status" :items="statusItems" label="Status" :error-messages="dialog.errors.status" /></v-col>
                        <v-col cols="12"><AppSelect v-model="dialog.form.change_request_id" :items="changeRequestItems" label="Change request" :error-messages="dialog.errors.change_request_id" /></v-col>
                        <v-col cols="12"><AppSelect v-model="dialog.form.provisioning_template_id" :items="templateItems" label="Provisioning template" :error-messages="dialog.errors.provisioning_template_id" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.output_summary" label="Output summary" :error-messages="dialog.errors.output_summary" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="dialog.open = false">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">Record run</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Automation Runs","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppTextarea from '../../components/AppTextarea.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useAutomationRunsStore } from '../../stores/automation-runs';

const store = useAutomationRunsStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'run', label: 'Run' },
    { key: 'target', label: 'Target' },
    { key: 'change', label: 'Change request' },
    { key: 'status', label: 'Status' },
    { key: 'started', label: 'Started' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const templateItems = computed(() => toSelect(store.options.templates, 'No template'));
const changeRequestItems = computed(() => toSelect(store.options.change_requests, 'Select an approved change request'));

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '-');

const emptyForm = () => ({ reference: '', status: 'queued', change_request_id: '', provisioning_template_id: '', output_summary: '' });
const dialog = reactive({ open: false, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const openCreate = () => {
    const form = emptyForm();
    form.status = store.options.statuses[0]?.value ?? 'queued';
    Object.assign(dialog, { open: true, form, errors: {}, message: '' });
};

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['provisioning_template_id', 'output_summary'].forEach((key) => { if (payload[key] === '') payload[key] = null; });
    return payload;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        await store.create(normalizePayload());
        dialog.open = false;
        await load();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
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
</style>
