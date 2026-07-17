<template>
    <div class="detail-page">
        <div class="page-wrap">
            <RouterLink to="/commercial/invoices" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to invoices</RouterLink>

            <AppPageHeader :eyebrow="invoice?.number || 'Invoice'" :title="invoice?.client_name || 'Invoice'" :subtitle="invoice?.contract_name || 'Invoice detail and payment history.'">
                <template #metrics>
                    <AppStatusBadge v-if="invoice" :status="invoice.is_overdue ? 'suspended' : (invoice.status_color || invoice.status)" :label="invoice.is_overdue ? 'Overdue' : (invoice.status_label || invoice.status)" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="goToList">Manage invoice</v-btn>
                </template>
            </AppPageHeader>

            <div class="detail__stats">
                <AppStatCard label="Amount" :value="formatAmount(invoice?.amount)" helper="Invoice total" icon="mdi-receipt-text-outline" status="active" />
                <AppStatCard label="Paid" :value="formatAmount(invoice?.amount_paid)" helper="Received" icon="mdi-cash-check" status="active" />
                <AppStatCard label="Outstanding" :value="formatAmount(invoice?.outstanding)" helper="Remaining" icon="mdi-cash-clock" :status="Number(invoice?.outstanding) > 0 ? 'pending' : 'active'" />
            </div>

            <AppSectionCard title="Invoice workspace" subtitle="Overview, payments, and history.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab value="payments">Payments</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Client</dt><dd>{{ invoice?.client_name || '-' }}</dd></div>
                            <div><dt>Contract</dt><dd>{{ invoice?.contract_name || '-' }}</dd></div>
                            <div><dt>Issued</dt><dd>{{ invoice?.issued_on || '-' }}</dd></div>
                            <div><dt>Due</dt><dd>{{ invoice?.due_on || '-' }}</dd></div>
                            <div><dt>Status</dt><dd>{{ invoice?.status_label || '-' }}</dd></div>
                            <div><dt>External reference</dt><dd>{{ invoice?.external_reference || '-' }}</dd></div>
                            <div class="detail-grid__wide"><dt>Notes</dt><dd><AppRichTextDisplay :content="invoice?.notes" /></dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item value="payments">
                        <AppDataTable title="Payments" :columns="paymentColumns" :rows="payments" :meta="noMeta" :loading="loading" empty-title="No payments" empty-text="No payments recorded for this invoice.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ formatAmount(row.amount) }}</strong><small>{{ row.reference || '-' }}</small></div></td>
                                <td><span class="text-sm">{{ row.method_label || row.method || '-' }}</span></td>
                                <td><span class="text-sm">{{ row.paid_on || '-' }}</span></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="Invoice"
                            :subject-id="invoiceId"
                            :per-page="12"
                            empty-text="No activity recorded for this invoice yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Invoice detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppActivityFeed from '../../../components/AppActivityFeed.vue';
import AppRichTextDisplay from '../../../components/AppRichTextDisplay.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const invoiceId = route.params.invoice;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);

const tab = ref('overview');
const invoice = ref(null);
const loading = ref(false);

const payments = computed(() => invoice.value?.payments ?? []);

const noMeta = { current_page: 1, last_page: 1, per_page: 100 };
const paymentColumns = [{ key: 'amount', label: 'Amount' }, { key: 'method', label: 'Method' }, { key: 'date', label: 'Paid on' }];

const formatAmount = (value) => (value ? `ZAR ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '-');
const goToList = () => router.push('/commercial/invoices');

const load = async () => {
    loading.value = true;

    try {
        const response = await v1(`invoices/${invoiceId}`);
        invoice.value = response?.data ?? response;
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
