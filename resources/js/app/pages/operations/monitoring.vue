<template>
    <div class="operations-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 02" title="Monitoring" subtitle="Uptime, SSL, backup, Sentry, server, database, Redis, and queue checks.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} checks`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-monitor-eye" @click="openCreate">New check</v-btn>
                </template>
            </AppPageHeader>

            <div class="operations__stats">
                <AppStatCard label="Checks" :value="String(store.meta.total)" helper="Tracked monitoring checks" icon="mdi-pulse" status="active" />
                <AppStatCard label="Warnings" :value="String(warningCount)" helper="Checks needing attention" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Passing" :value="String(passingCount)" helper="Healthy checks" icon="mdi-check-circle-outline" status="active" />
            </div>

            <AppSectionCard title="Monitoring checks" subtitle="Seeded operational checks linked to deployments and clients.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search checks" prepend-inner-icon="mdi-magnify" class="operations__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="operations__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All checks" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No checks" empty-text="Add the first monitoring check." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.name }}</strong><small>{{ row.deployment_name }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.check_type }}</span></td>
                        <td><span class="text-sm">{{ row.target }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><span class="text-sm">{{ formatDate(row.last_checked_at) }}</span></td>
                        <td><v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New monitoring check' : 'Edit monitoring check'"
            subtitle="Checks belong to a deployment and roll up into that deployment's monitoring tab."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12"><AppSelect v-model="dialog.form.deployment_id" :items="deploymentItems" label="Deployment" :error-messages="dialog.errors.deployment_id" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.name" label="Name" :error-messages="dialog.errors.name" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.check_type" :items="checkTypeItems" label="Check type" :error-messages="dialog.errors.check_type" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.target" label="Target" placeholder="URL, host, or resource name" :error-messages="dialog.errors.target" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.status" :items="statusItems" label="Status" :error-messages="dialog.errors.status" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.last_checked_at" label="Last checked" type="datetime-local" :error-messages="dialog.errors.last_checked_at" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.last_success_at" label="Last success" type="datetime-local" :error-messages="dialog.errors.last_success_at" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Add check' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Monitoring","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';

import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSelect from '../../components/AppSelect.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import { useToast } from '../../composables/useToast';
import { useMonitoringChecksStore } from '../../stores/monitoring-checks';

const toast = useToast();
const store = useMonitoringChecksStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'check', label: 'Check' },
    { key: 'type', label: 'Type' },
    { key: 'target', label: 'Target' },
    { key: 'status', label: 'Status' },
    { key: 'last_checked', label: 'Last checked' },
    { key: 'actions', label: '', class: 'text-right' },
];
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...store.options.statuses.map((item) => ({ title: item.label, value: item.value }))]);
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const checkTypeItems = computed(() => store.options.check_types.map((type) => ({ title: type, value: type })));
const deploymentItems = computed(() => store.options.deployments.map((item) => ({ title: item.label, value: item.value })));
const warningCount = computed(() => store.rows.filter((row) => row.status === 'warning' || row.status === 'failing').length);
const passingCount = computed(() => store.rows.filter((row) => row.status === 'passing').length);
const formatDate = (value) => (value ? new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-');

const toLocalInput = (value) => (value ? String(value).slice(0, 16) : '');
const emptyForm = () => ({ deployment_id: '', name: '', check_type: '', target: '', status: 'passing', last_checked_at: '', last_success_at: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const openCreate = () => {
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            deployment_id: row.deployment_id ?? '',
            name: row.name ?? '',
            check_type: row.check_type ?? '',
            target: row.target ?? '',
            status: row.status ?? '',
            last_checked_at: toLocalInput(row.last_checked_at),
            last_success_at: toLocalInput(row.last_success_at),
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['last_checked_at', 'last_success_at'].forEach((key) => {
        if (payload[key] === '') payload[key] = null;
    });
    return payload;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        if (dialog.mode === 'create') {
            const { deployment_id: deploymentId, ...payload } = normalizePayload();
            await store.create(deploymentId, payload);
            await load();
            toast.success('Monitoring check added.');
        } else {
            store.upsertRow(await store.update(dialog.editId, normalizePayload()));
            toast.success('Monitoring check updated.');
        }
        closeDialog();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status });
const onSearch = (val) => {
    filters.search = val;
    filters.page = 1;
    load();
};
const onFilter = () => {
    filters.page = 1;
    load();
};
const onPage = (page) => {
    filters.page = page;
    load();
};
onMounted(load);
</script>

<style scoped>
.operations-page {
    padding: 2.25rem 2rem 4rem;
}

.page-wrap {
    max-width: var(--rw-content-max);
    margin: 0 auto;
    display: grid;
    gap: 1.5rem;
}

.operations__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
}

.operations__search {
    flex: 0 1 320px; min-width: 240px;
}

.operations__filter {
    min-width: 190px;
}

.ops-cell {
    display: grid;
    gap: 0.1rem;
}

.ops-cell small {
    color: var(--rw-muted);
    font-size: 0.78rem;
}

.text-sm {
    font-size: 0.85rem;
}

.dialog-form {
    display: grid;
    gap: 1rem;
}

@media (max-width: 960px) {
    .operations-page {
        padding: 1.75rem 1rem 3rem;
    }

    .operations__stats {
        grid-template-columns: 1fr;
    }
}
</style>
