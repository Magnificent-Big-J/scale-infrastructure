<template>
    <div class="clients-page">
        <div class="page-wrap">
            <RouterLink to="/clients" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to clients</RouterLink>

            <AppPageHeader :eyebrow="client?.code || 'Client'" :title="client?.name || 'Client detail'" :subtitle="client?.legal_name || 'Client account record, relationships, and commercial position.'">
                <template #metrics>
                    <AppStatusBadge v-if="client" :status="client.status_color || client.status" :label="client.status_label || client.status" />
                    <AppStatusBadge v-if="client" :status="client.tier_color || client.tier" :label="client.tier_label || client.tier" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="goToEdit">Edit client</v-btn>
                </template>
            </AppPageHeader>

            <div class="clients__stats clients__stats--five">
                <AppStatCard label="Deployments" :value="String(summary.deployments_count)" helper="Environments" icon="mdi-rocket-launch-outline" status="active" />
                <AppStatCard label="Active agreements" :value="String(summary.active_agreements)" helper="Support retainers" icon="mdi-handshake-outline" status="active" />
                <AppStatCard label="Open tickets" :value="String(summary.open_tickets)" helper="Unresolved support" icon="mdi-ticket-confirmation-outline" status="processing" />
                <AppStatCard label="Contracts" :value="String(summary.contracts_count)" helper="Commercial agreements" icon="mdi-file-sign" status="active" />
                <AppStatCard label="Outstanding" :value="formatAmount(summary.outstanding_total)" :helper="`${summary.overdue_count} overdue`" icon="mdi-receipt-text-clock-outline" :status="summary.overdue_count ? 'suspended' : 'processing'" />
            </div>

            <AppSectionCard title="Client workspace" subtitle="Overview, contacts, deployments, support, and commercial position.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab value="contacts">Contacts</v-tab>
                    <v-tab value="deployments">Deployments</v-tab>
                    <v-tab value="support">Support</v-tab>
                    <v-tab value="commercial">Commercial</v-tab>
                    <v-tab value="profitability">Profitability</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Legal name</dt><dd>{{ client?.legal_name || '-' }}</dd></div>
                            <div><dt>Package</dt><dd>{{ client?.package_name || '-' }}{{ client?.product_name ? ` · ${client.product_name}` : '' }}</dd></div>
                            <div><dt>Owner</dt><dd>{{ client?.owner_name || '-' }}</dd></div>
                            <div><dt>Health score</dt><dd>{{ client?.health_score ?? '-' }}%</dd></div>
                            <div><dt>Primary contact</dt><dd>{{ client?.primary_contact?.name || '-' }}<small v-if="client?.primary_contact?.email"> · {{ client.primary_contact.email }}</small></dd></div>
                            <div class="detail-grid__wide"><dt>Notes</dt><dd>{{ client?.notes || '-' }}</dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item value="contacts">
                        <AppDataTable title="Contacts" :columns="contactColumns" :rows="contacts" :meta="noMeta" :loading="loading.client" empty-title="No contacts" empty-text="No contacts captured for this client.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.name }}</strong><small>{{ row.role || '-' }}</small></div></td>
                                <td><span class="text-sm">{{ row.email || '-' }}</span></td>
                                <td><span class="text-sm">{{ row.phone || '-' }}</span></td>
                                <td><AppStatusBadge v-if="row.is_primary" status="active" label="Primary" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item value="deployments">
                        <AppDataTable title="Deployments" :columns="deploymentColumns" :rows="tabs.deployments.rows" :meta="noMeta" :loading="loading.deployments" empty-title="No deployments" empty-text="No deployments for this client.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.name }}</strong><small>{{ row.environment_label || row.environment }}</small></div></td>
                                <td><span class="text-sm">{{ row.package_name || row.product_name || '-' }}</span></td>
                                <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item value="support">
                        <AppDataTable title="Support agreements" :columns="agreementColumns" :rows="tabs.agreements.rows" :meta="noMeta" :loading="loading.support" empty-title="No agreements" empty-text="No support agreements for this client.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.name }}</strong><small>{{ row.support_tier_name || '-' }}</small></div></td>
                                <td><span class="text-sm">{{ formatAmount(row.monthly_fee) }}</span></td>
                                <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                            </template>
                        </AppDataTable>
                        <AppDataTable title="Tickets" :columns="ticketColumns" :rows="tabs.tickets.rows" :meta="noMeta" :loading="loading.support" empty-title="No tickets" empty-text="No support tickets for this client." class="detail-stacked">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.subject }}</strong><small>{{ row.reference }}</small></div></td>
                                <td><AppStatusBadge :status="row.severity" :label="row.severity_label || row.severity" /></td>
                                <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item value="commercial">
                        <AppDataTable title="Contracts" :columns="contractColumns" :rows="tabs.contracts.rows" :meta="noMeta" :loading="loading.commercial" empty-title="No contracts" empty-text="No contracts for this client.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.name }}</strong><small>{{ row.code }}</small></div></td>
                                <td><span class="text-sm">{{ formatAmount(row.total_value) }}</span></td>
                                <td><span class="text-sm">{{ row.renewal_date || '-' }}</span></td>
                                <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                            </template>
                        </AppDataTable>
                        <AppDataTable title="Invoices" :columns="invoiceColumns" :rows="tabs.invoices.rows" :meta="noMeta" :loading="loading.commercial" empty-title="No invoices" empty-text="No invoices for this client." class="detail-stacked">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.number }}</strong><small>{{ row.due_on || '-' }}</small></div></td>
                                <td><span class="text-sm">{{ formatAmount(row.amount) }}</span></td>
                                <td><span class="text-sm">{{ formatAmount(row.outstanding) }}</span></td>
                                <td><AppStatusBadge :status="row.is_overdue ? 'suspended' : (row.status_color || row.status)" :label="row.is_overdue ? 'Overdue' : (row.status_label || row.status)" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item value="profitability">
                        <AppDataTable title="Profitability" :columns="profitabilityColumns" :rows="tabs.profitability.rows" :meta="noMeta" :loading="loading.profitability" empty-title="No records" empty-text="No profitability records for this client.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.period }}</strong></div></td>
                                <td><span class="text-sm">{{ formatAmount(row.revenue) }}</span></td>
                                <td><span class="text-sm">{{ formatAmount(row.total_cost) }}</span></td>
                                <td><span class="text-sm">{{ formatAmount(row.profit) }}</span></td>
                                <td><AppStatusBadge :status="Number(row.margin) >= 0 ? 'active' : 'suspended'" :label="`${Number(row.margin).toFixed(1)}%`" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="Client"
                            :subject-id="clientId"
                            :per-page="12"
                            empty-text="No activity recorded for this client yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Client detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppActivityFeed from '../../components/AppActivityFeed.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import { useSessionStore } from '../../stores/session';
import { v1 } from '../../utils/api';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const clientId = route.params.client;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);

const tab = ref('overview');
const client = ref(null);
const contacts = ref([]);
const summary = reactive({ deployments_count: 0, active_agreements: 0, open_tickets: 0, contracts_count: 0, outstanding_total: 0, overdue_count: 0 });
const loading = reactive({ client: false, deployments: false, support: false, commercial: false, profitability: false });
const loaded = reactive({ deployments: false, support: false, commercial: false, profitability: false });
const tabs = reactive({
    deployments: { rows: [] },
    agreements: { rows: [] },
    tickets: { rows: [] },
    contracts: { rows: [] },
    invoices: { rows: [] },
    profitability: { rows: [] },
});

const noMeta = { current_page: 1, last_page: 1, per_page: 100, total: 0 };

const contactColumns = [{ key: 'name', label: 'Name' }, { key: 'email', label: 'Email' }, { key: 'phone', label: 'Phone' }, { key: 'primary', label: '' }];
const deploymentColumns = [{ key: 'name', label: 'Deployment' }, { key: 'offering', label: 'Offering' }, { key: 'status', label: 'Status' }];
const agreementColumns = [{ key: 'agreement', label: 'Agreement' }, { key: 'monthly', label: 'Monthly' }, { key: 'status', label: 'Status' }];
const ticketColumns = [{ key: 'ticket', label: 'Ticket' }, { key: 'severity', label: 'Severity' }, { key: 'status', label: 'Status' }];
const contractColumns = [{ key: 'contract', label: 'Contract' }, { key: 'total', label: 'Total' }, { key: 'renewal', label: 'Renewal' }, { key: 'status', label: 'Status' }];
const invoiceColumns = [{ key: 'invoice', label: 'Invoice' }, { key: 'amount', label: 'Amount' }, { key: 'outstanding', label: 'Outstanding' }, { key: 'status', label: 'Status' }];
const profitabilityColumns = [{ key: 'period', label: 'Period' }, { key: 'revenue', label: 'Revenue' }, { key: 'cost', label: 'Cost' }, { key: 'profit', label: 'Profit' }, { key: 'margin', label: 'Margin' }];

const formatAmount = (value) => (value ? `ZAR ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '-');
const listRows = (response) => response?.data?.map((item) => item?.data ?? item) ?? [];

const goToEdit = () => router.push('/clients');

const loadClient = async () => {
    loading.client = true;

    try {
        const response = await v1(`clients/${clientId}`);
        client.value = response?.data ?? response;
        contacts.value = client.value?.contacts ?? [];
        Object.assign(summary, response?.summary ?? {});
    } finally {
        loading.client = false;
    }
};

const loadTab = async (name) => {
    if (name === 'deployments' && !loaded.deployments) {
        loading.deployments = true;
        try {
            tabs.deployments.rows = listRows(await v1(`deployments?client_id=${clientId}&per_page=100`));
            loaded.deployments = true;
        } finally {
            loading.deployments = false;
        }
    }

    if (name === 'support' && !loaded.support) {
        loading.support = true;
        try {
            const [agreements, tickets] = await Promise.all([
                v1(`support-agreements?client_id=${clientId}&per_page=100`),
                v1(`support-tickets?client_id=${clientId}&per_page=100`),
            ]);
            tabs.agreements.rows = listRows(agreements);
            tabs.tickets.rows = listRows(tickets);
            loaded.support = true;
        } finally {
            loading.support = false;
        }
    }

    if (name === 'commercial' && !loaded.commercial) {
        loading.commercial = true;
        try {
            const [contracts, invoices] = await Promise.all([
                v1(`contracts?client_id=${clientId}&per_page=100`),
                v1(`invoices?client_id=${clientId}&per_page=100`),
            ]);
            tabs.contracts.rows = listRows(contracts);
            tabs.invoices.rows = listRows(invoices);
            loaded.commercial = true;
        } finally {
            loading.commercial = false;
        }
    }

    if (name === 'profitability' && !loaded.profitability) {
        loading.profitability = true;
        try {
            tabs.profitability.rows = listRows(await v1(`profitability-records?client_id=${clientId}&per_page=100`));
            loaded.profitability = true;
        } catch {
            tabs.profitability.rows = [];
        } finally {
            loading.profitability = false;
        }
    }
};

watch(tab, (value) => loadTab(value));
onMounted(loadClient);
</script>

<style scoped>
.clients-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--rw-muted); font-size: 0.85rem; text-decoration: none; }
.back-link:hover { color: var(--rw-700); }
.clients__stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.9rem; }
.detail-tabs { border-bottom: 1px solid var(--rw-border); margin-bottom: 1.25rem; }
.detail-window { padding-top: 0.25rem; }
.detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 2rem; margin: 0; }
.detail-grid__wide { grid-column: 1 / -1; }
.detail-grid dt { color: var(--rw-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }
.detail-grid dd { margin: 0.2rem 0 0; font-size: 0.92rem; }
.detail-grid dd small { color: var(--rw-muted); }
.detail-cell { display: grid; gap: 0.1rem; }
.detail-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.detail-stacked { margin-top: 1.25rem; }
.text-sm { font-size: 0.85rem; }
@media (max-width: 1200px) { .clients__stats--five { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 960px) { .clients-page { padding: 1.75rem 1rem 3rem; } .clients__stats, .clients__stats--five { grid-template-columns: 1fr; } .detail-grid { grid-template-columns: 1fr; } }
</style>
