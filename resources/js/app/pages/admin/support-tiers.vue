<template>
    <div class="admin-catalogue-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Administration"
                title="Support Tiers"
                subtitle="Maintain monthly support retainers separately from signed support agreements."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} tiers`" />
                </template>

                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-handshake-outline" @click="openCreate">
                        New tier
                    </v-btn>
                </template>
            </AppPageHeader>

            <div class="admin-catalogue__stats">
                <AppStatCard
                    label="Support tiers"
                    :value="String(store.meta.total)"
                    helper="Standard retainers and SLAs"
                    icon="mdi-handshake-outline"
                    status="active"
                />
                <AppStatCard
                    label="Monthly baseline"
                    :value="monthlyRange"
                    helper="From active tier pricing"
                    icon="mdi-cash-multiple"
                    status="processing"
                />
            </div>

            <AppSectionCard title="Support pricing" subtitle="Create, update, and archive the retainers used before client-specific agreements are signed.">
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search tiers"
                        prepend-inner-icon="mdi-magnify"
                        class="admin-catalogue__search"
                        @update:model-value="onSearch"
                    />
                    <AppSelect
                        v-model="filters.status"
                        :items="statusFilterItems"
                        label="Filter by status"
                        class="admin-catalogue__status-filter"
                        @update:model-value="onFilter"
                    />
                </AppFilterBar>

                <AppDataTable
                    title="All support tiers"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No support tiers found"
                    empty-text="Create the first support tier."
                    @page-change="onPage"
                    @row-click="openEdit"
                >
                    <template #row="{ row }">
                        <td>
                            <div class="catalogue-cell">
                                <div class="catalogue-cell__name">{{ row.name }}</div>
                                <div class="catalogue-cell__code">{{ row.code }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="text-sm">{{ formatAmount(row.monthly_fee, row.currency) }}</span>
                        </td>
                        <td>
                            <span class="text-muted text-sm">{{ row.included_hours ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="text-muted text-sm">{{ row.response_sla_label || '-' }}</span>
                        </td>
                        <td>
                            <AppStatusBadge :status="row.status" :label="row.status_label || row.status" />
                        </td>
                        <td>
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New support tier' : 'Edit support tier'"
            subtitle="Support tiers define recurring pricing, included hours, and response SLA targets."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />

                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6">
                            <AppTextField v-model="dialog.form.code" label="Code" :error-messages="dialog.errors.code" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppSelect
                                v-model="dialog.form.status"
                                :items="statusItems"
                                label="Status"
                                :error-messages="dialog.errors.status"
                            />
                        </v-col>
                        <v-col cols="12">
                            <AppTextField v-model="dialog.form.name" label="Name" :error-messages="dialog.errors.name" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppTextField
                                v-model="dialog.form.monthly_fee"
                                label="Monthly fee"
                                type="number"
                                :error-messages="dialog.errors.monthly_fee"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppTextField v-model="dialog.form.currency" label="Currency" :error-messages="dialog.errors.currency" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppTextField
                                v-model="dialog.form.included_hours"
                                label="Included hours"
                                type="number"
                                :error-messages="dialog.errors.included_hours"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppTextField
                                v-model="dialog.form.response_sla_hours"
                                label="Response SLA hours"
                                type="number"
                                :error-messages="dialog.errors.response_sla_hours"
                            />
                        </v-col>
                        <v-col cols="12">
                            <AppTextField
                                v-model="dialog.form.service_review"
                                label="Service review"
                                :error-messages="dialog.errors.service_review"
                            />
                        </v-col>
                        <v-col cols="12">
                            <AppTextField v-model="dialog.form.best_for" label="Best for" :error-messages="dialog.errors.best_for" />
                        </v-col>
                        <v-col cols="12">
                            <AppTextField
                                v-model="dialog.form.description"
                                label="Description"
                                :error-messages="dialog.errors.description"
                            />
                        </v-col>
                    </v-row>
                </v-form>
            </div>

            <template #actions>
                <v-btn v-if="dialog.mode === 'edit'" variant="text" color="error" :loading="store.loading" @click="archiveCurrent">
                    Archive
                </v-btn>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">
                    {{ dialog.mode === 'create' ? 'Create tier' : 'Save changes' }}
                </v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Support Tiers",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';

import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useSupportTiersStore } from '../../stores/support-tiers';

const store = useSupportTiersStore();

const columns = [
    { key: 'tier', label: 'Tier' },
    { key: 'monthly_fee', label: 'Monthly' },
    { key: 'included_hours', label: 'Hours' },
    { key: 'response_sla', label: 'SLA' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const filters = reactive({ search: '', status: '', page: 1 });

const emptyForm = () => ({
    code: '',
    name: '',
    description: '',
    monthly_fee: '',
    included_hours: '',
    response_sla_hours: '',
    service_review: '',
    best_for: '',
    currency: '',
    status: '',
});

const dialog = reactive({
    open: false,
    mode: 'create',
    editId: null,
    form: emptyForm(),
    errors: {},
    message: '',
    messageType: 'error',
});

const statusItems = computed(() => store.options.statuses.map((s) => ({ title: s.label, value: s.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);

const has = (value) => value !== null && value !== undefined && value !== '';

const formatAmount = (value, currency) => {
    if (!has(value)) return '-';

    return `${currency || ''} ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`.trim();
};

const monthlyRange = computed(() => {
    const amounts = store.rows
        .map((row) => Number(row.monthly_fee))
        .filter((amount) => Number.isFinite(amount) && amount > 0);

    if (!amounts.length) return '-';

    return `${formatAmount(Math.min(...amounts), store.options.default_currency)} - ${formatAmount(Math.max(...amounts), store.options.default_currency)}`;
});

const defaultStatus = () => store.options.statuses[0]?.value ?? '';

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

const openCreate = () => {
    const form = emptyForm();
    form.status = defaultStatus();
    form.currency = store.options.default_currency ?? '';

    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            code: row.code,
            name: row.name,
            description: row.description ?? '',
            monthly_fee: row.monthly_fee ?? '',
            included_hours: row.included_hours ?? '',
            response_sla_hours: row.response_sla_hours ?? '',
            service_review: row.service_review ?? '',
            best_for: row.best_for ?? '',
            currency: row.currency ?? '',
            status: row.status,
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => {
    dialog.open = false;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    const payload = { ...dialog.form };
    if (payload.monthly_fee === '') payload.monthly_fee = null;
    if (payload.included_hours === '') payload.included_hours = null;
    if (payload.response_sla_hours === '') payload.response_sla_hours = null;

    try {
        if (dialog.mode === 'create') {
            await store.create(payload);
        } else {
            await store.update(dialog.editId, payload);
        }

        closeDialog();
        await load();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const archiveCurrent = async () => {
    try {
        await store.archive(dialog.editId);
        closeDialog();
        await load();
    } catch (error) {
        dialog.message = error?.data?.message || 'Could not archive support tier.';
        dialog.messageType = 'error';
    }
};

onMounted(load);
</script>

<style scoped>
.admin-catalogue__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
}

.admin-catalogue__search {
    flex: 0 1 320px; min-width: 240px;
}

.admin-catalogue__status-filter {
    min-width: 200px;
}

.catalogue-cell__name {
    font-weight: 600;
    font-size: 0.9rem;
}

.catalogue-cell__code {
    font-size: 0.8rem;
    color: var(--rw-muted);
}

.text-muted {
    color: var(--rw-muted);
}

.dialog-form {
    gap: 0.5rem;
}

@media (max-width: 960px) {
    .admin-catalogue__stats {
        grid-template-columns: 1fr;
    }
}
</style>
