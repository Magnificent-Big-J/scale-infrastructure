<template>
    <div class="ops-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 06" title="Provisioning Templates" subtitle="Repeatable infrastructure setup steps for environments.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} templates`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-cog-outline" @click="openCreate">New template</v-btn>
                </template>
            </AppPageHeader>

            <AppSectionCard title="Template library" subtitle="Templates describe repeatable provisioning steps for automation runs.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search templates" prepend-inner-icon="mdi-magnify" class="ops__search" @update:model-value="onSearch" />
                </AppFilterBar>

                <AppDataTable title="All templates" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No templates" empty-text="Create the first provisioning template." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.name }}</strong><small>{{ row.code }}</small></div></td>
                        <td><span class="text-sm">{{ row.provider || '-' }}</span></td>
                        <td><span class="text-sm">{{ (row.steps || []).length }} steps</span></td>
                        <td><span class="text-sm">{{ row.automation_runs_count ?? 0 }}</span></td>
                        <td><AppStatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Active' : 'Inactive'" /></td>
                        <td><v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal v-model="dialog.open" :title="dialog.mode === 'create' ? 'New template' : 'Edit template'" subtitle="List one provisioning step per line." persistent>
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.code" label="Code" :error-messages="dialog.errors.code" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.provider" label="Provider" placeholder="DigitalOcean" :error-messages="dialog.errors.provider" /></v-col>
                        <v-col cols="12"><AppTextField v-model="dialog.form.name" label="Name" :error-messages="dialog.errors.name" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.summary" label="Summary" :error-messages="dialog.errors.summary" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.stepsText" label="Steps (one per line)" :error-messages="dialog.errors.steps" /></v-col>
                        <v-col cols="12"><v-switch v-model="dialog.form.is_active" color="primary" label="Active" hide-details inset /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="dialog.open = false">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create template' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Provisioning Templates","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppTextarea from '../../components/AppTextarea.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useProvisioningTemplatesStore } from '../../stores/provisioning-templates';

const store = useProvisioningTemplatesStore();
const filters = reactive({ search: '', page: 1 });
const columns = [
    { key: 'template', label: 'Template' },
    { key: 'provider', label: 'Provider' },
    { key: 'steps', label: 'Steps' },
    { key: 'runs', label: 'Runs' },
    { key: 'active', label: 'Active' },
    { key: 'actions', label: '', class: 'text-right' },
];

const emptyForm = () => ({ code: '', name: '', provider: '', summary: '', stepsText: '', is_active: true });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const openCreate = () => { Object.assign(dialog, { open: true, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '' }); };

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            code: row.code ?? '',
            name: row.name ?? '',
            provider: row.provider ?? '',
            summary: row.summary ?? '',
            stepsText: (row.steps || []).join('\n'),
            is_active: row.is_active ?? true,
        },
        errors: {},
        message: '',
    });
};

const normalizePayload = () => {
    const payload = { ...dialog.form };
    payload.steps = payload.stepsText.split('\n').map((line) => line.trim()).filter(Boolean);
    delete payload.stepsText;
    if (payload.provider === '') payload.provider = null;
    if (payload.summary === '') payload.summary = null;
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

const load = () => store.fetch({ page: filters.page, search: filters.search });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(load);
</script>

<style scoped>
.ops-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.ops__search { min-width: min(320px, 100%); }
.ops-cell { display: grid; gap: 0.1rem; }
.ops-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.text-sm { font-size: 0.85rem; }
.dialog-form { display: grid; gap: 1rem; }
@media (max-width: 960px) { .ops-page { padding: 1.75rem 1rem 3rem; } }
</style>
