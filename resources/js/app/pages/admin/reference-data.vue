<template>
    <div class="admin-catalogue-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Administration"
                title="Reference data"
                subtitle="Manage the selectable vocabularies used across the platform. Add, rename, reorder, or retire options without a deploy."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} options`" />
                </template>

                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">
                        New option
                    </v-btn>
                </template>
            </AppPageHeader>

            <div class="admin-catalogue__stats">
                <AppStatCard
                    label="Managed lists"
                    :value="String(store.options.types.length)"
                    helper="Code-controlled vocabularies"
                    icon="mdi-format-list-bulleted-type"
                    status="active"
                />
                <AppStatCard
                    label="View"
                    :value="activeTypeLabel"
                    helper="Filter the options below by list"
                    icon="mdi-filter-variant"
                    status="processing"
                />
            </div>

            <AppSectionCard title="Reference options" subtitle="Each option belongs to a managed list. Inactive options stay on existing records but no longer appear in dropdowns.">
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search options"
                        prepend-inner-icon="mdi-magnify"
                        class="admin-catalogue__search"
                        @update:model-value="onSearch"
                    />
                    <AppSelect
                        v-model="filters.type"
                        :items="typeFilterItems"
                        label="Filter by list"
                        class="admin-catalogue__status-filter"
                        @update:model-value="onFilter"
                    />
                </AppFilterBar>

                <AppDataTable
                    title="All options"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No options found"
                    empty-text="Create the first option to start building this list."
                    @page-change="onPage"
                    @row-click="openEdit"
                >
                    <template #row="{ row }">
                        <td>
                            <div class="catalogue-cell">
                                <div class="catalogue-cell__name">{{ row.label }}</div>
                                <div class="catalogue-cell__code">{{ row.value }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="text-sm">{{ row.type_label }}</span>
                        </td>
                        <td>
                            <span class="text-muted text-sm">{{ row.sort_order }}</span>
                        </td>
                        <td>
                            <AppStatusBadge
                                :status="row.is_active ? 'active' : 'inactive'"
                                :label="row.is_active ? 'Active' : 'Inactive'"
                            />
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
            :title="dialog.mode === 'create' ? 'New option' : 'Edit option'"
            subtitle="The value is a stable machine key; the label is what users see."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />

                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6">
                            <AppSelect
                                v-model="dialog.form.type"
                                :items="typeItems"
                                label="List"
                                :disabled="dialog.mode === 'edit'"
                                :error-messages="dialog.errors.type"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <AppTextField
                                v-model="dialog.form.value"
                                label="Value (key)"
                                hint="Lowercase letters, numbers, hyphens, underscores"
                                :error-messages="dialog.errors.value"
                            />
                        </v-col>
                        <v-col cols="12" sm="8">
                            <AppTextField v-model="dialog.form.label" label="Label" :error-messages="dialog.errors.label" />
                        </v-col>
                        <v-col cols="12" sm="4">
                            <AppTextField
                                v-model="dialog.form.sort_order"
                                label="Sort order"
                                type="number"
                                :error-messages="dialog.errors.sort_order"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-switch
                                v-model="dialog.form.is_active"
                                color="primary"
                                :label="dialog.form.is_active ? 'Active — shown in dropdowns' : 'Inactive — hidden from dropdowns'"
                                hide-details
                                inset
                            />
                        </v-col>
                    </v-row>
                </v-form>
            </div>

            <template #actions>
                <v-btn v-if="dialog.mode === 'edit'" variant="text" color="error" :loading="store.loading" @click="removeCurrent">
                    Delete
                </v-btn>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">
                    {{ dialog.mode === 'create' ? 'Create option' : 'Save changes' }}
                </v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Reference Data",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';

import { useReferenceDataStore } from '../../stores/reference-data';

const store = useReferenceDataStore();

const columns = [
    { key: 'option', label: 'Option' },
    { key: 'list', label: 'List' },
    { key: 'order', label: 'Order' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const filters = reactive({ search: '', type: '', page: 1 });

const emptyForm = () => ({ type: '', value: '', label: '', sort_order: 0, is_active: true });

const dialog = reactive({
    open: false,
    mode: 'create',
    editId: null,
    form: emptyForm(),
    errors: {},
    message: '',
    messageType: 'error',
});

const typeItems = computed(() => store.options.types.map((t) => ({ title: t.label, value: t.value })));

const typeFilterItems = computed(() => [{ title: 'All lists', value: '' }, ...typeItems.value]);

const defaultType = () => store.options.types[0]?.value ?? '';

const activeTypeLabel = computed(() =>
    filters.type ? store.options.types.find((t) => t.value === filters.type)?.label ?? 'All lists' : 'All lists',
);

const load = () => store.fetch({ page: filters.page, search: filters.search, type: filters.type });

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
    form.type = filters.type || defaultType();

    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: { type: row.type, value: row.value, label: row.label, sort_order: row.sort_order ?? 0, is_active: row.is_active },
        errors: {},
        message: '',
    });
};

const closeDialog = () => {
    dialog.open = false;
};

const buildPayload = () => ({
    ...(dialog.mode === 'create' ? { type: dialog.form.type, value: dialog.form.value } : { value: dialog.form.value }),
    label: dialog.form.label,
    sort_order: Number(dialog.form.sort_order) || 0,
    is_active: dialog.form.is_active,
});

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        if (dialog.mode === 'create') {
            await store.create(buildPayload());
        } else {
            await store.update(dialog.editId, buildPayload());
        }

        closeDialog();
        await load();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const removeCurrent = async () => {
    try {
        await store.remove(dialog.editId);
        closeDialog();
        await load();
    } catch (error) {
        dialog.message = error?.data?.message || 'Could not delete option.';
        dialog.messageType = 'error';
    }
};

onMounted(load);
</script>

<style scoped>
.admin-catalogue-page {
    padding: 2.25rem 2rem 4rem;
}

.page-wrap {
    max-width: var(--rw-content-max);
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
