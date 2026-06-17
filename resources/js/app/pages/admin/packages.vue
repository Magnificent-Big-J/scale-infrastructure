<template>
    <div class="admin-catalogue-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Administration"
                title="Packages"
                subtitle="Configure the priced tiers that clients subscribe to under each product."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} packages`" />
                </template>

                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-layers-plus" :disabled="!store.options.products.length" @click="openCreate">
                        New package
                    </v-btn>
                </template>
            </AppPageHeader>

            <AppBanner
                v-if="!store.loading && !store.options.products.length"
                title="No products yet"
                message="Create a product first — packages must belong to a product."
                type="info"
            />

            <div class="admin-catalogue__stats">
                <AppStatCard
                    label="Total packages"
                    :value="String(store.meta.total)"
                    helper="Across all products"
                    icon="mdi-layers-outline"
                    status="active"
                />
                <AppStatCard
                    label="Products"
                    :value="String(store.options.products.length)"
                    helper="Available to attach packages to"
                    icon="mdi-package-variant-closed"
                    status="processing"
                />
            </div>

            <AppSectionCard title="Package catalogue" subtitle="Create, update, and archive priced packages per product.">
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search packages"
                        prepend-inner-icon="mdi-magnify"
                        class="admin-catalogue__search"
                        @update:model-value="onSearch"
                    />
                    <AppSelect
                        v-model="filters.productId"
                        :items="productFilterItems"
                        label="Filter by product"
                        class="admin-catalogue__status-filter"
                        @update:model-value="onFilter"
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
                    title="All packages"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No packages found"
                    empty-text="Create the first package under a product."
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
                            <span class="text-muted text-sm">{{ row.product_name || '—' }}</span>
                        </td>
                        <td>
                            <span class="text-muted text-sm">{{ row.billing_interval_label || '—' }}</span>
                        </td>
                        <td>
                            <span class="text-sm">{{ formatPrice(row) }}</span>
                        </td>
                        <td>
                            <AppStatusBadge :status="row.status" :label="row.status_label || row.status" />
                        </td>
                        <td>
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New package' : 'Edit package'"
            subtitle="Packages belong to a product and carry a billing interval and price."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />

                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12">
                            <AppSelect
                                v-model="dialog.form.product_id"
                                :items="productItems"
                                label="Product"
                                :error-messages="dialog.errors.product_id"
                            />
                        </v-col>
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
                            <AppSelect
                                v-model="dialog.form.billing_interval"
                                :items="intervalItems"
                                label="Billing interval"
                                :error-messages="dialog.errors.billing_interval"
                            />
                        </v-col>
                        <v-col cols="6" sm="3">
                            <AppTextField
                                v-model="dialog.form.price"
                                label="Price"
                                type="number"
                                :error-messages="dialog.errors.price"
                            />
                        </v-col>
                        <v-col cols="6" sm="3">
                            <AppTextField v-model="dialog.form.currency" label="Currency" :error-messages="dialog.errors.currency" />
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
                    {{ dialog.mode === 'create' ? 'Create package' : 'Save changes' }}
                </v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Packages",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';

import AppBanner from '../../components/AppBanner.vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import { usePackagesStore } from '../../stores/packages';

const store = usePackagesStore();

const columns = [
    { key: 'package', label: 'Package' },
    { key: 'product', label: 'Product' },
    { key: 'interval', label: 'Billing' },
    { key: 'price', label: 'Price' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const filters = reactive({ search: '', status: '', productId: '', page: 1 });

const emptyForm = () => ({
    product_id: '',
    code: '',
    name: '',
    description: '',
    billing_interval: 'monthly',
    price: '',
    currency: 'ZAR',
    status: 'active',
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
const intervalItems = computed(() => store.options.billing_intervals.map((i) => ({ title: i.label, value: i.value })));
const productItems = computed(() => store.options.products.map((p) => ({ title: p.label, value: p.value })));

const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const productFilterItems = computed(() => [{ title: 'All products', value: '' }, ...productItems.value]);

const formatPrice = (row) => {
    if (row.price === null || row.price === undefined || row.price === '') return '—';

    return `${row.currency || ''} ${Number(row.price).toLocaleString(undefined, { minimumFractionDigits: 2 })}`.trim();
};

const load = () =>
    store.fetch({ page: filters.page, search: filters.search, status: filters.status, productId: filters.productId });

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
    form.product_id = filters.productId || store.options.products[0]?.value || '';

    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            product_id: row.product_id,
            code: row.code,
            name: row.name,
            description: row.description ?? '',
            billing_interval: row.billing_interval,
            price: row.price ?? '',
            currency: row.currency ?? 'ZAR',
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
    if (payload.price === '') payload.price = null;

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
        dialog.message = error?.data?.message || 'Could not archive package.';
        dialog.messageType = 'error';
    }
};

onMounted(load);
</script>

<style scoped>
.admin-catalogue-page {
    padding: 2rem 1.25rem 4rem;
}

.page-wrap {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 1.5rem;
}

.admin-catalogue__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
}

.admin-catalogue__search {
    min-width: min(320px, 100%);
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

.text-sm {
    font-size: 0.85rem;
}

.dialog-form {
    display: grid;
    gap: 0.5rem;
}

@media (max-width: 960px) {
    .admin-catalogue-page {
        padding: 1.75rem 1rem 3rem;
    }

    .admin-catalogue__stats {
        grid-template-columns: 1fr;
    }
}
</style>
