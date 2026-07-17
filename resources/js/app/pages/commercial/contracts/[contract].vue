<template>
    <div class="detail-page">
        <div class="page-wrap">
            <RouterLink to="/commercial/contracts" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to contracts</RouterLink>

            <AppPageHeader :eyebrow="contract?.code || 'Contract'" :title="contract?.name || 'Contract'" :subtitle="contract?.client_name || 'Commercial agreement detail.'">
                <template #metrics>
                    <AppStatusBadge v-if="contract" :status="contract.status_color || contract.status" :label="contract.status_label || contract.status" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="goToList">Edit contract</v-btn>
                </template>
            </AppPageHeader>

            <div class="detail__stats">
                <AppStatCard label="Total value" :value="formatAmount(contract?.total_value)" helper="Contract value" icon="mdi-cash-multiple" status="active" />
                <AppStatCard label="Monthly value" :value="formatAmount(contract?.monthly_value)" helper="Recurring" icon="mdi-calendar-sync-outline" status="processing" />
                <AppStatCard label="Renewal" :value="contract?.renewal_date || '-'" helper="Next renewal" icon="mdi-autorenew" status="active" />
            </div>

            <AppSectionCard title="Contract workspace" subtitle="Overview, billing, invoices, and history.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab value="billing">Billing</v-tab>
                    <v-tab value="invoices">Invoices</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Client</dt><dd>{{ contract?.client_name || '-' }}</dd></div>
                            <div><dt>Offering</dt><dd>{{ contract?.package_name || contract?.product_name || '-' }}</dd></div>
                            <div><dt>Starts</dt><dd>{{ contract?.starts_on || '-' }}</dd></div>
                            <div><dt>Ends</dt><dd>{{ contract?.ends_on || '-' }}</dd></div>
                            <div><dt>Renewal</dt><dd>{{ contract?.renewal_date || '-' }}</dd></div>
                            <div><dt>Status</dt><dd>{{ contract?.status_label || '-' }}</dd></div>
                            <div class="detail-grid__wide"><dt>Notes</dt><dd>{{ contract?.notes || '-' }}</dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item value="billing">
                        <AppDataTable title="Billing records" :columns="billingColumns" :rows="billingRecords" :meta="noMeta" :loading="loading" empty-title="No billing records" empty-text="No billing records for this contract.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.description || row.type_label }}</strong><small>{{ row.cadence_label || row.cadence }}</small></div></td>
                                <td><span class="text-sm">{{ formatAmount(row.amount) }}</span></td>
                                <td><AppStatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Active' : 'Inactive'" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item value="invoices">
                        <AppDataTable title="Invoices" :columns="invoiceColumns" :rows="invoices" :meta="noMeta" :loading="loading" empty-title="No invoices" empty-text="No invoices for this contract.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.number }}</strong><small>{{ row.due_on || '-' }}</small></div></td>
                                <td><span class="text-sm">{{ formatAmount(row.amount) }}</span></td>
                                <td><span class="text-sm">{{ formatAmount(row.outstanding) }}</span></td>
                                <td><AppStatusBadge :status="row.is_overdue ? 'suspended' : (row.status_color || row.status)" :label="row.is_overdue ? 'Overdue' : (row.status_label || row.status)" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="Contract"
                            :subject-id="contractId"
                            :per-page="12"
                            empty-text="No activity recorded for this contract yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Contract detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppActivityFeed from '../../../components/AppActivityFeed.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const contractId = route.params.contract;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);

const tab = ref('overview');
const contract = ref(null);
const loading = ref(false);

const billingRecords = computed(() => contract.value?.billing_records ?? []);
const invoices = computed(() => contract.value?.invoices ?? []);

const noMeta = { current_page: 1, last_page: 1, per_page: 100 };
const billingColumns = [{ key: 'record', label: 'Record' }, { key: 'amount', label: 'Amount' }, { key: 'status', label: 'Status' }];
const invoiceColumns = [{ key: 'invoice', label: 'Invoice' }, { key: 'amount', label: 'Amount' }, { key: 'outstanding', label: 'Outstanding' }, { key: 'status', label: 'Status' }];

const formatAmount = (value) => (value ? `ZAR ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '-');
const goToList = () => router.push('/commercial/contracts');

const load = async () => {
    loading.value = true;

    try {
        const response = await v1(`contracts/${contractId}`);
        contract.value = response?.data ?? response;
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<style scoped>
.detail__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
@media (max-width: 960px) { .detail__stats { grid-template-columns: 1fr; } }
</style>
