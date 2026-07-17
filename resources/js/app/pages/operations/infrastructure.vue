<template>
    <div class="operations-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 02" title="Infrastructure" subtitle="Hosting assets, providers, regions, server sizes, costs, IPs, and metadata.">
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} assets`" />
                </template>
                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-server-plus" @click="openCreate">New asset</v-btn>
                </template>
            </AppPageHeader>

            <div class="operations__stats">
                <AppStatCard label="Assets" :value="String(store.meta.total)" helper="Tracked infrastructure records" icon="mdi-server-network" status="active" />
                <AppStatCard label="Monthly cost" :value="monthlyCost" helper="Current result set" icon="mdi-cash-multiple" status="processing" />
                <AppStatCard label="Deployments" :value="String(store.options.deployments.length)" helper="Available environments" icon="mdi-rocket-launch-outline" status="active" />
            </div>

            <AppSectionCard title="Infrastructure assets" subtitle="Seeded assets linked to deployments and clients.">
                <AppFilterBar>
                    <AppTextField v-model="filters.search" label="Search assets" prepend-inner-icon="mdi-magnify" class="operations__search" @update:model-value="onSearch" />
                    <AppSelect v-model="filters.type" :items="typeFilterItems" label="Asset type" class="operations__filter" @update:model-value="onFilter" />
                </AppFilterBar>

                <AppDataTable title="All assets" :columns="columns" :rows="store.rows" :meta="store.meta" :loading="store.loading" empty-title="No assets" empty-text="Add the first infrastructure asset." @page-change="onPage" @row-click="openEdit">
                    <template #row="{ row }">
                        <td><div class="ops-cell"><strong>{{ row.name }}</strong><small>{{ row.deployment_name }} · {{ row.client_name }}</small></div></td>
                        <td><span class="text-sm">{{ row.type_label }}</span></td>
                        <td><span class="text-sm">{{ row.provider || '-' }}</span></td>
                        <td><span class="text-sm">{{ row.region || '-' }}</span></td>
                        <td><span class="text-sm">{{ row.size || '-' }}</span></td>
                        <td><span class="text-sm">{{ formatAmount(row.monthly_cost, row.currency) }}</span></td>
                        <td><v-btn icon="mdi-pencil-outline" size="small" variant="text" title="Edit" @click.stop="openEdit(row)" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New infrastructure asset' : 'Edit infrastructure asset'"
            subtitle="Assets belong to a deployment and roll up into that deployment's infrastructure tab."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />
                <v-form @submit.prevent="submitDialog">
                    <v-row dense>
                        <v-col cols="12"><AppSelect v-model="dialog.form.deployment_id" :items="deploymentItems" label="Deployment" :error-messages="dialog.errors.deployment_id" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.name" label="Name" :error-messages="dialog.errors.name" /></v-col>
                        <v-col cols="12" sm="6"><AppSelect v-model="dialog.form.type" :items="typeItems" label="Type" :error-messages="dialog.errors.type" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.provider" label="Provider" placeholder="AWS, DigitalOcean, Hetzner…" :error-messages="dialog.errors.provider" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.region" label="Region" :error-messages="dialog.errors.region" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.size" label="Size" placeholder="2 vCPU / 4GB" :error-messages="dialog.errors.size" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.monthly_cost" label="Monthly cost" type="number" :error-messages="dialog.errors.monthly_cost" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.public_ip" label="Public IP" :error-messages="dialog.errors.public_ip" /></v-col>
                        <v-col cols="12" sm="6"><AppTextField v-model="dialog.form.private_ip" label="Private IP" :error-messages="dialog.errors.private_ip" /></v-col>
                    </v-row>
                </v-form>
            </div>
            <template #actions>
                <v-spacer />
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">{{ dialog.mode === 'create' ? 'Add asset' : 'Save changes' }}</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Infrastructure","requiresAuth":true,"adminOnly":true}}
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
import { useInfrastructureAssetsStore } from '../../stores/infrastructure-assets';

const toast = useToast();
const store = useInfrastructureAssetsStore();
const filters = reactive({ search: '', type: '', page: 1 });
const columns = [
    { key: 'asset', label: 'Asset' },
    { key: 'type', label: 'Type' },
    { key: 'provider', label: 'Provider' },
    { key: 'region', label: 'Region' },
    { key: 'size', label: 'Size' },
    { key: 'cost', label: 'Monthly' },
    { key: 'actions', label: '', class: 'text-right' },
];
const typeFilterItems = computed(() => [{ title: 'All types', value: '' }, ...store.options.types.map((item) => ({ title: item.label, value: item.value }))]);
const typeItems = computed(() => store.options.types.map((item) => ({ title: item.label, value: item.value })));
const deploymentItems = computed(() => store.options.deployments.map((item) => ({ title: item.label, value: item.value })));
const formatAmount = (value, currency) => (value ? `${currency || ''} ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`.trim() : '-');
const monthlyCost = computed(() => formatAmount(store.rows.reduce((sum, row) => sum + Number(row.monthly_cost || 0), 0), store.options.default_currency));

const emptyForm = () => ({ deployment_id: '', name: '', type: '', provider: '', region: '', size: '', monthly_cost: '', public_ip: '', private_ip: '' });
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
            type: row.type ?? '',
            provider: row.provider ?? '',
            region: row.region ?? '',
            size: row.size ?? '',
            monthly_cost: row.monthly_cost ?? '',
            public_ip: row.public_ip ?? '',
            private_ip: row.private_ip ?? '',
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => { dialog.open = false; };

const normalizePayload = () => {
    const payload = { ...dialog.form };
    ['provider', 'region', 'size', 'public_ip', 'private_ip'].forEach((key) => {
        if (payload[key] === '') payload[key] = null;
    });
    payload.monthly_cost = payload.monthly_cost === '' ? null : Number(payload.monthly_cost);
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
            toast.success('Infrastructure asset added.');
        } else {
            store.upsertRow(await store.update(dialog.editId, normalizePayload()));
            toast.success('Infrastructure asset updated.');
        }
        closeDialog();
    } catch (error) {
        dialog.errors = error?.data?.errors ?? {};
        dialog.message = error?.data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

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
onMounted(load);
</script>

<style scoped>
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

@media (max-width: 960px) {
    .operations__stats {
        grid-template-columns: 1fr;
    }
}
</style>
