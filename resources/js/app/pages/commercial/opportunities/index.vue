<template>
    <div class="commercial-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Commercial" title="Opportunities" subtitle="Sales pipeline for potential implementations, retainers, and expansion work.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} opportunities`" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-view-column-outline" to="/commercial/opportunities/board">Pipeline board</v-btn>
                    <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">New opportunity</v-btn>
                </template>
            </AppPageHeader>

            <div class="commercial__stats commercial__stats--four">
                <AppStatCard label="Open value" :value="formatAmount(store.summary.open_value)" :helper="`${store.summary.open_count} open`" icon="mdi-cash-clock" status="processing" />
                <AppStatCard label="Won value" :value="formatAmount(store.summary.won_value)" :helper="`${store.summary.won_count} won`" icon="mdi-trophy-outline" status="active" />
                <AppStatCard label="Win rate" :value="`${Number(store.summary.win_rate || 0).toFixed(0)}%`" helper="Won vs decided" icon="mdi-percent-outline" status="active" />
                <AppStatCard label="Open deals" :value="String(store.summary.open_count)" helper="In the pipeline" icon="mdi-chart-timeline-variant" status="processing" />
            </div>

            <AppBarChart
                v-if="pipelineSeries[0].data.length"
                title="Pipeline value by stage"
                subtitle="Total opportunity value at each stage."
                :categories="pipelineCategories"
                :series="pipelineSeries"
                :colors="['#2563eb']"
            />

            <AppSectionCard title="Opportunity register" subtitle="Track deals from lead to won, with estimated value and expected close.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search opportunities" prepend-inner-icon="mdi-magnify" class="commercial__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.stage" :items="stageFilterItems" label="Stage" class="commercial__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All opportunities" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No opportunities found" empty-text="Capture the first opportunity." @page-change="onPage" @row-click="goToDetail">
                    <template #row="{ row }">
                        <td><div class="commercial-cell"><strong>{{ row.title }}</strong><small>{{ row.display_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.owner_name || '—' }}</span></td>
                        <td><AppStatusBadge :status="row.stage_color" :label="row.stage_label" /></td>
                        <td><span class="text-sm">{{ formatAmount(row.value) }}</span></td>
                        <td><span class="text-sm">{{ row.expected_close_date || '—' }}</span></td>
                        <td class="row-actions">
                            <v-btn v-if="!['won', 'lost'].includes(row.stage)" size="small" variant="text" color="primary" :loading="inlineBusy === row.id" @click.stop="markWon(row)">Win</v-btn>
                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click.stop="openEdit(row)" />
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New opportunity' : 'Edit opportunity'"
            subtitle="Link an existing client, or capture a prospect by name."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12"><AppTextField v-model="dialog.form.title" label="Title" :error-messages="dialog.errors.title" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.prospect_name" label="Prospect (if no client)" :error-messages="dialog.errors.prospect_name" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.owner_id" :items="ownerItems" label="Owner" :error-messages="dialog.errors.owner_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.stage" :items="stageItems" label="Stage" :error-messages="dialog.errors.stage" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.value" label="Value (ZAR)" type="number" :error-messages="dialog.errors.value" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.probability" label="Probability %" type="number" :error-messages="dialog.errors.probability" /></v-col>
                        <v-col cols="12" sm="4"><AppSelect v-model="dialog.form.source" :items="sourceItems" label="Source" :error-messages="dialog.errors.source" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.expected_close_date" label="Expected close" type="date" :error-messages="dialog.errors.expected_close_date" /></v-col>
                        <v-col cols="12"><AppTextarea v-model="dialog.form.description" label="Description" :error-messages="dialog.errors.description" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-btn v-if="dialog.mode === 'edit'" variant="text" color="error" :loading="store.loading" @click="removeCurrent">Delete</v-btn>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Opportunities","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import AppBarChart from '../../../components/AppBarChart.vue';
import AppFilterBar from '../../../components/AppFilterBar.vue';
import AppModal from '../../../components/AppModal.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import AppTextarea from '../../../components/AppTextarea.vue';
import AppTextField from '../../../components/AppTextField.vue';
import { useToast, errorMessage } from '../../../composables/useToast';
import { useOpportunitiesStore } from '../../../stores/opportunities';

const router = useRouter();
const toast = useToast();
const store = useOpportunitiesStore();
const filters = reactive({ search: '', stage: '', page: 1 });
const inlineBusy = ref(null);

const columns = [
    { key: 'opportunity', label: 'Opportunity' },
    { key: 'owner', label: 'Owner' },
    { key: 'stage', label: 'Stage' },
    { key: 'value', label: 'Value' },
    { key: 'close', label: 'Expected close' },
    { key: 'actions', label: '', class: 'text-right' },
];

const formatAmount = (value) => `ZAR ${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
const toItems = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];

const clientItems = computed(() => toItems(store.options.clients, 'No client (prospect)'));
const ownerItems = computed(() => toItems(store.options.owners, 'Unassigned'));
const stageItems = computed(() => store.options.stages.map((item) => ({ title: item.label, value: item.value })));
const sourceItems = computed(() => toItems(store.options.sources, 'No source'));
const stageFilterItems = computed(() => [{ title: 'All stages', value: '' }, ...stageItems.value]);

const pipelineCategories = computed(() => store.pipeline.map((row) => row.label));
const pipelineSeries = computed(() => [{ name: 'Value', data: store.pipeline.map((row) => Number(row.value)) }]);

const emptyForm = () => ({ client_id: '', prospect_name: '', owner_id: '', title: '', description: '', stage: 'lead', value: 0, probability: '', source: '', expected_close_date: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const goToDetail = (row) => router.push(`/commercial/opportunities/${row.id}`);

const openCreate = () => {
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            client_id: row.client_id ?? '',
            prospect_name: row.prospect_name ?? '',
            owner_id: row.owner_id ?? '',
            title: row.title ?? '',
            description: row.description ?? '',
            stage: row.stage ?? 'lead',
            value: row.value ?? 0,
            probability: row.probability ?? '',
            source: row.source ?? '',
            expected_close_date: row.expected_close_date ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['client_id', 'prospect_name', 'owner_id', 'source', 'expected_close_date', 'description'].forEach((key) => {
        if (payload[key] === '') payload[key] = null;
    });
    if (payload.probability === '') payload.probability = null;
    return payload;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        if (dialog.mode === 'create') {
            await store.create(normalizePayload());
            await load();
            toast.success('Opportunity created.');
        } else {
            store.upsertRow(await store.update(dialog.editId, normalizePayload()));
            await refreshSummary();
            toast.success('Opportunity updated.');
        }
        closeDialog();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const removeCurrent = async () => {
    const id = dialog.editId;

    try {
        await store.remove(id);
        store.removeRow(id);
        closeDialog();
        await refreshSummary();
        toast.success('Opportunity deleted.');
    } catch (error) {
        dialog.message = error?.data?.message || 'Could not delete the opportunity.';
        dialog.messageType = 'error';
    }
};

const markWon = async (row) => {
    inlineBusy.value = row.id;

    try {
        store.upsertRow(await store.win(row.id));
        await refreshSummary();
        toast.success('Marked won — a draft contract was created for clients.');
    } catch (error) {
        toast.error(errorMessage(error, 'Could not mark the opportunity won.'));
    } finally {
        inlineBusy.value = null;
    }
};

const load = () => store.fetch({ page: filters.page, search: filters.search, stage: filters.stage });
const refreshSummary = () => store.fetch({ page: filters.page, search: filters.search, stage: filters.stage });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };

onMounted(load);
</script>

<style scoped>
.commercial-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.commercial__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.commercial__search { min-width: min(320px, 100%); }
.commercial__filter { min-width: 200px; }
.commercial-cell { display: grid; gap: 0.1rem; }
.commercial-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.row-actions { display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem; }
.text-sm { font-size: 0.85rem; }
.dialog-form { display: grid; gap: 1rem; }
@media (max-width: 1100px) { .commercial__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .commercial-page { padding: 1.5rem 1rem 3rem; } .commercial__stats { grid-template-columns: 1fr; } }
</style>
