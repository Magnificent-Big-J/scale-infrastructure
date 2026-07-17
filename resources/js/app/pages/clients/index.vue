<template>
    <div class="clients-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Module 01"
                title="Clients"
                subtitle="Client master records, account ownership, package tier, status, health, and primary contact visibility."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} clients`" />
                </template>

                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-domain-plus" @click="openCreate">
                        New client
                    </v-btn>
                </template>
            </AppPageHeader>

            <div class="clients__stats">
                <AppStatCard
                    label="Total clients"
                    :value="String(store.meta.total)"
                    helper="Active and onboarding account records"
                    icon="mdi-domain"
                    status="active"
                />
                <AppStatCard
                    label="Average health"
                    :value="averageHealth"
                    helper="Across the current result set"
                    icon="mdi-heart-pulse"
                    status="processing"
                />
                <AppStatCard
                    label="Filtered view"
                    :value="filters.search || filters.status || filters.tier ? 'Active' : 'All clients'"
                    helper="Search, status, and tier filters"
                    icon="mdi-filter-variant"
                    status="active"
                />
            </div>

            <AppSectionCard title="Client registry" subtitle="Search, filter, create, update, and archive client account records.">
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search clients"
                        prepend-inner-icon="mdi-magnify"
                        class="clients__search"
                        @update:model-value="onSearch"
                    />
                    <AppSelect
                        v-model="filters.status"
                        :items="statusFilterItems"
                        label="Filter by status"
                        class="clients__filter"
                        @update:model-value="onFilter"
                    />
                    <AppSelect
                        v-model="filters.tier"
                        :items="tierFilterItems"
                        label="Filter by tier"
                        class="clients__filter"
                        @update:model-value="onFilter"
                    />
                </AppFilterBar>

                <AppDataTable
                    title="All clients"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No clients found"
                    empty-text="Create the first client account record."
                    @page-change="onPage"
                    @row-click="goToDetail"
                >
                    <template #row="{ row }">
                        <td>
                            <div class="client-cell">
                                <v-avatar size="36" color="primary" variant="tonal">
                                    <span class="client-cell__initials">{{ initials(row.name) }}</span>
                                </v-avatar>
                                <div>
                                    <div class="client-cell__name">{{ row.name }}</div>
                                    <div class="client-cell__code">{{ row.code }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="client-package">
                                <span>{{ row.package_name || '-' }}</span>
                                <small>{{ row.product_name || '' }}</small>
                            </div>
                        </td>
                        <td>
                            <AppStatusBadge :status="row.tier_color || row.tier" :label="row.tier_label || row.tier" />
                        </td>
                        <td>
                            <AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" />
                        </td>
                        <td>
                            <span class="health-pill">{{ row.health_score }}%</span>
                        </td>
                        <td>
                            <div class="contact-cell">
                                <span>{{ row.primary_contact?.name || '-' }}</span>
                                <small>{{ row.primary_contact?.email || '' }}</small>
                            </div>
                        </td>
                        <td class="text-right">
                            <v-btn icon="mdi-eye-outline" size="small" variant="text" title="View detail" @click.stop="goToDetail(row)" />
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New client' : 'Edit client'"
            subtitle="Clients anchor deployments, support agreements, contracts, and billing records."
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
                        <v-col cols="12">
                            <AppTextField
                                v-model="dialog.form.legal_name"
                                label="Legal name"
                                :error-messages="dialog.errors.legal_name"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppSelect
                                v-model="dialog.form.tier"
                                :items="tierItems"
                                label="Tier"
                                :error-messages="dialog.errors.tier"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppTextField
                                v-model="dialog.form.health_score"
                                label="Health score"
                                type="number"
                                :error-messages="dialog.errors.health_score"
                            />
                        </v-col>
                        <v-col cols="12">
                            <AppSelect
                                v-model="dialog.form.package_id"
                                :items="packageItems"
                                label="Package"
                                :error-messages="dialog.errors.package_id"
                            />
                        </v-col>
                        <v-col cols="12">
                            <AppSelect
                                v-model="dialog.form.owner_user_id"
                                :items="ownerItems"
                                label="Owner"
                                :error-messages="dialog.errors.owner_user_id"
                            />
                        </v-col>
                        <v-col cols="12">
                            <AppTextField v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" />
                        </v-col>
                    </v-row>

                    <div class="primary-contact-form">
                        <div>
                            <h3>Primary contact</h3>
                            <p>Visible in the client registry and used as the default operational contact.</p>
                        </div>
                        <v-row dense>
                            <v-col cols="12" sm="6">
                                <AppTextField
                                    v-model="dialog.form.primary_contact.name"
                                    label="Contact name"
                                    :error-messages="dialog.errors['primary_contact.name']"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <AppTextField
                                    v-model="dialog.form.primary_contact.email"
                                    label="Contact email"
                                    type="email"
                                    :error-messages="dialog.errors['primary_contact.email']"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <AppTextField
                                    v-model="dialog.form.primary_contact.phone"
                                    label="Phone"
                                    :error-messages="dialog.errors['primary_contact.phone']"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <AppTextField
                                    v-model="dialog.form.primary_contact.role"
                                    label="Role"
                                    :error-messages="dialog.errors['primary_contact.role']"
                                />
                            </v-col>
                        </v-row>
                    </div>
                </v-form>
            </div>

            <template #actions>
                <v-btn v-if="dialog.mode === 'edit'" variant="text" color="error" :loading="store.loading" @click="archiveCurrent">
                    Archive
                </v-btn>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">
                    {{ dialog.mode === 'create' ? 'Create client' : 'Save changes' }}
                </v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Clients",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';

import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useClientsStore } from '../../stores/clients';

const store = useClientsStore();
const router = useRouter();

const goToDetail = (row) => router.push(`/clients/${row.id}`);

const columns = [
    { key: 'client', label: 'Client' },
    { key: 'package', label: 'Package' },
    { key: 'tier', label: 'Tier' },
    { key: 'status', label: 'Status' },
    { key: 'health', label: 'Health' },
    { key: 'primary_contact', label: 'Primary contact' },
    { key: 'actions', label: '', class: 'text-right' },
];

const filters = reactive({ search: '', status: '', tier: '', page: 1 });

const emptyPrimaryContact = () => ({ name: '', email: '', phone: '', role: '' });

const emptyForm = () => ({
    code: '',
    name: '',
    legal_name: '',
    tier: '',
    status: '',
    health_score: 75,
    package_id: '',
    owner_user_id: '',
    notes: '',
    primary_contact: emptyPrimaryContact(),
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
const tierItems = computed(() => store.options.tiers.map((t) => ({ title: t.label, value: t.value })));
const packageItems = computed(() => [{ title: 'No package selected', value: '' }, ...store.options.packages.map((p) => ({ title: p.label, value: p.value }))]);
const ownerItems = computed(() => [{ title: 'No owner selected', value: '' }, ...store.options.owners.map((o) => ({ title: o.label, value: o.value }))]);

const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const tierFilterItems = computed(() => [{ title: 'All tiers', value: '' }, ...tierItems.value]);

const averageHealth = computed(() => {
    if (!store.rows.length) return '-';

    const total = store.rows.reduce((sum, row) => sum + Number(row.health_score || 0), 0);

    return `${Math.round(total / store.rows.length)}%`;
});

const initials = (name) =>
    (name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('');

const defaultStatus = () => store.options.statuses.find((item) => item.value === 'active')?.value ?? store.options.statuses[0]?.value ?? '';
const defaultTier = () => store.options.tiers[0]?.value ?? '';

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status, tier: filters.tier });

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
    form.tier = defaultTier();

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
            legal_name: row.legal_name ?? '',
            tier: row.tier,
            status: row.status,
            health_score: row.health_score,
            package_id: row.package_id ?? '',
            owner_user_id: row.owner_user_id ?? '',
            notes: row.notes ?? '',
            primary_contact: {
                name: row.primary_contact?.name ?? '',
                email: row.primary_contact?.email ?? '',
                phone: row.primary_contact?.phone ?? '',
                role: row.primary_contact?.role ?? '',
            },
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => {
    dialog.open = false;
};

const normalizePayload = () => {
    const payload = { ...dialog.form, primary_contact: { ...dialog.form.primary_contact } };

    if (payload.package_id === '') payload.package_id = null;
    if (payload.owner_user_id === '') payload.owner_user_id = null;
    if (!payload.primary_contact.name) payload.primary_contact = null;

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
        dialog.message = error?.data?.message || 'Could not archive client.';
        dialog.messageType = 'error';
    }
};

onMounted(load);
</script>

<style scoped>
.clients__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
}

.clients__search {
    flex: 0 1 320px; min-width: 240px;
}

.clients__filter {
    min-width: 190px;
}

.client-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.client-cell__initials {
    font-size: 0.82rem;
    font-weight: 800;
}

.client-cell__name {
    font-weight: 700;
    font-size: 0.9rem;
}

.client-cell__code,
.client-package small,
.contact-cell small {
    display: block;
    color: var(--rw-muted);
    font-size: 0.78rem;
}

.client-package,
.contact-cell {
    display: grid;
    gap: 0.1rem;
}

.health-pill {
    display: inline-flex;
    min-width: 3.25rem;
    justify-content: center;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: var(--rw-50);
    color: var(--rw-700);
    font-size: 0.78rem;
    font-weight: 800;
}

.primary-contact-form {
    display: grid;
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--rw-border);
}

.primary-contact-form h3 {
    margin: 0;
    font-size: 1rem;
}

.primary-contact-form p {
    margin: 0;
    color: var(--rw-muted);
    font-size: 0.84rem;
}

@media (max-width: 960px) {
    .clients__stats {
        grid-template-columns: 1fr;
    }
}
</style>
