<template>
    <div class="admin-catalogue-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Administration"
                title="Products"
                subtitle="Define what Code Scale Tech sells and supports. Products anchor packages, deployments, and contracts."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} products`" />
                </template>

                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-package-variant-closed-plus" @click="openCreate">
                        New product
                    </v-btn>
                </template>
            </AppPageHeader>

            <div class="admin-catalogue__stats">
                <AppStatCard
                    label="Total products"
                    :value="String(store.meta.total)"
                    helper="Active and archived catalogue items"
                    icon="mdi-package-variant-closed"
                    status="active"
                />
                <AppStatCard
                    label="Search state"
                    :value="filters.search ? 'Filtered' : 'All products'"
                    helper="Live filter bar is active"
                    icon="mdi-filter-variant"
                    status="processing"
                />
            </div>

            <AppSectionCard title="Product catalogue" subtitle="Create, update, and archive the products that back every commercial record.">
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search products"
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
                    title="All products"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No products found"
                    empty-text="Create the first product to start building the catalogue."
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
                            <span class="text-muted text-sm">{{ row.packages_count ?? 0 }}</span>
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
            :title="dialog.mode === 'create' ? 'New product' : 'Edit product'"
            subtitle="Products use a unique code and an operational status."
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
                    {{ dialog.mode === 'create' ? 'Create product' : 'Save changes' }}
                </v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Products",
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
import { useProductsStore } from '../../stores/products';

const store = useProductsStore();

const columns = [
    { key: 'product', label: 'Product' },
    { key: 'packages', label: 'Packages' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const filters = reactive({ search: '', status: '', page: 1 });

const emptyForm = () => ({ code: '', name: '', description: '', status: 'active' });

const dialog = reactive({
    open: false,
    mode: 'create',
    editId: null,
    form: emptyForm(),
    errors: {},
    message: '',
    messageType: 'error',
});

const statusItems = computed(() =>
    store.options.statuses.map((s) => ({ title: s.label, value: s.value })),
);

const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);

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
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: { code: row.code, name: row.name, description: row.description ?? '', status: row.status },
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

    try {
        if (dialog.mode === 'create') {
            await store.create(dialog.form);
        } else {
            await store.update(dialog.editId, dialog.form);
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
        dialog.message = error?.data?.message || 'Could not archive product.';
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
    min-width: min(360px, 100%);
}

.admin-catalogue__status-filter {
    min-width: 220px;
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
