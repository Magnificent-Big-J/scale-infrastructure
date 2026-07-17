<template>
    <div class="support-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 03" title="Support Agreements" subtitle="Support retainers, tiers, monthly fees, included hours, response SLAs, and dates.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} agreements`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-handshake-outline" @click="openCreate">New agreement</v-btn>
                </template>
            </AppPageHeader>

            <div class="support__stats">
                <AppStatCard label="Agreements" :value="String(store.meta.total)" helper="Retainers in the registry" icon="mdi-handshake-outline" status="active" />
                <AppStatCard label="Monthly value" :value="monthlyValue" helper="Current result set" icon="mdi-cash-multiple" status="processing" />
                <AppStatCard label="Included hours" :value="String(includedHours)" helper="Monthly support capacity" icon="mdi-timer-outline" status="active" />
            </div>

            <AppSectionCard title="Support agreement registry" subtitle="Create and maintain retainers linked to clients and support tiers.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search agreements" prepend-inner-icon="mdi-magnify" class="support__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.status" :items="statusFilterItems" label="Status" class="support__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All agreements" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No agreements found" empty-text="Create the first support agreement." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="support-cell"><strong>{{ row.name }}</strong><small>{{ row.code }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.support_tier_name || '-' }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.monthly_fee) }}</span></td>
                        <td><span class="text-sm">{{ row.included_hours ?? '-' }}</span></td>
                        <td><span class="text-sm">{{ row.response_sla_hours ? `${row.response_sla_hours}h` : '-' }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                        <td><v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New support agreement' : 'Edit support agreement'"
            subtitle="Agreements anchor support tickets, response SLAs, and monthly retainer value."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.code" label="Code" :error-messages="dialog.errors.code" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.status" :items="statusItems" label="Status" :error-messages="dialog.errors.status" /></v-col>
                        <v-col cols="12"><AppTextField v-model="dialog.form.name" label="Name" :error-messages="dialog.errors.name" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.client_id" :items="clientItems" label="Client" :error-messages="dialog.errors.client_id" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.support_tier_id" :items="tierItems" label="Support tier" :error-messages="dialog.errors.support_tier_id" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.monthly_fee" label="Monthly fee" type="number" :error-messages="dialog.errors.monthly_fee" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.included_hours" label="Included hours" type="number" :error-messages="dialog.errors.included_hours" /></v-col>
                        <v-col cols="12" sm="4"><AppTextField v-model="dialog.form.response_sla_hours" label="Response SLA (hours)" type="number" :error-messages="dialog.errors.response_sla_hours" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.starts_on" label="Starts on" type="date" :error-messages="dialog.errors.starts_on" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.ends_on" label="Ends on" type="date" :error-messages="dialog.errors.ends_on" /></v-col>
                        <v-col cols="12"><AppRichTextEditor v-model="dialog.form.notes" label="Notes" :error-messages="dialog.errors.notes" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Create agreement' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Support Agreements","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppRichTextEditor from '../../components/AppRichTextEditor.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useSupportAgreementsStore } from '../../stores/support-agreements';

const store = useSupportAgreementsStore();
const filters = reactive({ search: '', status: '', page: 1 });
const columns = [
    { key: 'agreement', label: 'Agreement' },
    { key: 'tier', label: 'Tier' },
    { key: 'monthly', label: 'Monthly' },
    { key: 'hours', label: 'Hours' },
    { key: 'sla', label: 'SLA' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const toSelect = (items, placeholder) => [...(placeholder ? [{ title: placeholder, value: '' }] : []), ...items.map((item) => ({ title: item.label, value: item.value }))];
const statusItems = computed(() => store.options.statuses.map((item) => ({ title: item.label, value: item.value })));
const statusFilterItems = computed(() => [{ title: 'All statuses', value: '' }, ...statusItems.value]);
const clientItems = computed(() => toSelect(store.options.clients, 'Select a client'));
const tierItems = computed(() => toSelect(store.options.support_tiers, 'No tier'));

const formatAmount = (value) => (value ? `ZAR ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '-');
const monthlyValue = computed(() => formatAmount(store.rows.reduce((sum, row) => sum + Number(row.monthly_fee || 0), 0)));
const includedHours = computed(() => store.rows.reduce((sum, row) => sum + Number(row.included_hours || 0), 0));

const emptyForm = () => ({ client_id: '', support_tier_id: '', code: '', name: '', monthly_fee: 0, included_hours: 0, response_sla_hours: 0, starts_on: '', ends_on: '', status: '', notes: '' });
const dialog = reactive({ open: false, mode: 'create', editId: null, form: emptyForm(), errors: {}, message: '', messageType: 'error' });

const defaultStatus = () => store.options.statuses.find((item) => item.value === 'active')?.value ?? store.options.statuses[0]?.value ?? '';

const openCreate = () => {
    const form = emptyForm();
    form.status = defaultStatus();
    Object.assign(dialog, { open: true, mode: 'create', editId: null, form, errors: {}, message: '' });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            client_id: row.client_id ?? '',
            support_tier_id: row.support_tier_id ?? '',
            code: row.code ?? '',
            name: row.name ?? '',
            monthly_fee: row.monthly_fee ?? 0,
            included_hours: row.included_hours ?? 0,
            response_sla_hours: row.response_sla_hours ?? 0,
            starts_on: row.starts_on ?? '',
            ends_on: row.ends_on ?? '',
            status: row.status ?? '',
            notes: row.notes ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['support_tier_id', 'starts_on', 'ends_on', 'notes'].forEach((key) => {
        if (payload[key] === '') payload[key] = null;
    });
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

const load = () => store.fetch({ page: filters.page, search: filters.search, status: filters.status });
const onSearch = (val) => { filters.search = val; filters.page = 1; load(); };
const onFilter = () => { filters.page = 1; load(); };
const onPage = (page) => { filters.page = page; load(); };
onMounted(load);
</script>

<style scoped>
.support__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
.support__search { flex: 0 1 320px; min-width: 240px; }
.support__filter { min-width: 190px; }
@media (max-width: 960px) { .support__stats { grid-template-columns: 1fr; } }
</style>
