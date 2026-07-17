<template>
    <div class="detail-page">
        <div class="page-wrap">
            <RouterLink to="/operations/deployments" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to deployments</RouterLink>

            <AppPageHeader :eyebrow="deployment?.environment_label || 'Deployment'" :title="deployment?.name || 'Deployment'" :subtitle="deployment?.client_name || 'Client environment detail.'">
                <template #metrics>
                    <AppStatusBadge v-if="deployment" :status="deployment.status_color || deployment.status" :label="deployment.status_label || deployment.status" />
                </template>
            </AppPageHeader>

            <div class="detail__stats">
                <AppStatCard label="Environment" :value="deployment?.environment_label || '-'" helper="Deployment tier" icon="mdi-layers-outline" status="active" />
                <AppStatCard label="Version" :value="deployment?.current_version || '-'" helper="Current release" icon="mdi-tag-outline" status="processing" />
                <AppStatCard label="Infrastructure" :value="String(deployment?.infrastructure_assets_count ?? assets.length)" helper="Hosting assets" icon="mdi-server-network" status="active" />
                <AppStatCard label="Monitoring" :value="String(deployment?.monitoring_checks_count ?? checks.length)" helper="Operational checks" icon="mdi-pulse" status="active" />
            </div>

            <AppSectionCard title="Deployment workspace" subtitle="Overview, infrastructure, monitoring, releases, and history.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab value="infrastructure">Infrastructure</v-tab>
                    <v-tab value="monitoring">Monitoring</v-tab>
                    <v-tab value="releases">Releases</v-tab>
                    <v-tab v-if="canManage" value="intake">Intake</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Client</dt><dd>{{ deployment?.client_name || '-' }}</dd></div>
                            <div><dt>Offering</dt><dd>{{ deployment?.package_name || deployment?.product_name || '-' }}</dd></div>
                            <div><dt>Domain</dt><dd>{{ deployment?.domain || '-' }}</dd></div>
                            <div><dt>App URL</dt><dd>{{ deployment?.app_url || '-' }}</dd></div>
                            <div><dt>Go-live date</dt><dd>{{ deployment?.go_live_date || '-' }}</dd></div>
                            <div><dt>Status</dt><dd>{{ deployment?.status_label || '-' }}</dd></div>
                            <div class="detail-grid__wide"><dt>Notes</dt><dd>{{ deployment?.notes || '-' }}</dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item value="infrastructure">
                        <AppDataTable title="Infrastructure assets" :columns="assetColumns" :rows="assets" :meta="noMeta" :loading="loading" empty-title="No assets" empty-text="No infrastructure assets for this deployment.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.name }}</strong><small>{{ row.type_label || row.type }}</small></div></td>
                                <td><span class="text-sm">{{ [row.provider, row.region].filter(Boolean).join(' · ') || '-' }}</span></td>
                                <td><span class="text-sm">{{ row.size || '-' }}</span></td>
                                <td><span class="text-sm">{{ formatAmount(row.monthly_cost) }}</span></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item value="monitoring">
                        <AppDataTable title="Monitoring checks" :columns="checkColumns" :rows="checks" :meta="noMeta" :loading="loading" empty-title="No checks" empty-text="No monitoring checks for this deployment.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.name }}</strong><small>{{ row.check_type }}</small></div></td>
                                <td><span class="text-sm">{{ row.target || '-' }}</span></td>
                                <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item value="releases">
                        <AppDataTable title="Releases" :columns="releaseColumns" :rows="releases" :meta="noMeta" :loading="loading" empty-title="No releases" empty-text="No releases for this deployment.">
                            <template #row="{ row }">
                                <td><div class="detail-cell"><strong>{{ row.version }}</strong><small>{{ row.change_request_reference || '' }}</small></div></td>
                                <td><span class="text-sm">{{ formatDate(row.deployed_at) }}</span></td>
                                <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                            </template>
                        </AppDataTable>
                    </v-window-item>

                    <v-window-item v-if="canManage" value="intake">
                        <div class="intake">
                            <p class="intake__lead">
                                External systems can open support tickets for this deployment's client by POSTing to the intake
                                endpoint with the token below in an <code>X-Intake-Token</code> header. Inbound create only.
                            </p>

                            <div class="intake__field">
                                <span class="intake__label">Endpoint</span>
                                <code class="intake__value">POST {{ intakeUrl }}</code>
                            </div>

                            <div class="intake__field">
                                <span class="intake__label">Token</span>
                                <code v-if="deployment?.intake_token" class="intake__value intake__value--token">{{ deployment.intake_token }}</code>
                                <span v-else class="intake__muted">No token configured — generate one to enable intake.</span>
                                <v-btn v-if="deployment?.intake_token" icon="mdi-content-copy" size="x-small" variant="text" title="Copy token" @click="copyToken" />
                            </div>

                            <div class="intake__actions">
                                <v-btn color="primary" size="small" :loading="intakeBusy" prepend-icon="mdi-key-variant" @click="generateToken">
                                    {{ deployment?.intake_token ? 'Rotate token' : 'Generate token' }}
                                </v-btn>
                                <v-btn v-if="deployment?.intake_token" color="error" variant="text" size="small" :loading="intakeBusy" @click="revokeToken">Revoke</v-btn>
                            </div>
                        </div>
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="Deployment"
                            :subject-id="deploymentId"
                            :per-page="12"
                            empty-text="No activity recorded for this deployment yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Deployment detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import AppActivityFeed from '../../../components/AppActivityFeed.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import { useToast, errorMessage } from '../../../composables/useToast';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const session = useSessionStore();
const toast = useToast();
const deploymentId = route.params.deployment;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);
const canManage = computed(() => session.user?.permissions?.includes('deployments.update') ?? false);
const intakeUrl = `${window.location.origin}/api/intake/tickets`;

const tab = ref('overview');
const deployment = ref(null);
const loading = ref(false);
const intakeBusy = ref(false);

const generateToken = async () => {
    intakeBusy.value = true;

    try {
        const response = await v1(`deployments/${deploymentId}/intake-token`, { method: 'POST' });
        deployment.value.intake_token = (response?.data ?? response)?.intake_token;
        toast.success('Intake token generated.');
    } catch (error) {
        toast.error(errorMessage(error, 'Could not generate the token.'));
    } finally {
        intakeBusy.value = false;
    }
};

const revokeToken = async () => {
    intakeBusy.value = true;

    try {
        await v1(`deployments/${deploymentId}/intake-token`, { method: 'DELETE' });
        deployment.value.intake_token = null;
        toast.success('Intake token revoked.');
    } catch (error) {
        toast.error(errorMessage(error, 'Could not revoke the token.'));
    } finally {
        intakeBusy.value = false;
    }
};

const copyToken = () => {
    navigator.clipboard?.writeText(deployment.value?.intake_token ?? '');
    toast.success('Token copied.');
};

const assets = computed(() => deployment.value?.infrastructure_assets ?? []);
const checks = computed(() => deployment.value?.monitoring_checks ?? []);
const releases = computed(() => deployment.value?.releases ?? []);

const noMeta = { current_page: 1, last_page: 1, per_page: 100 };

const assetColumns = [{ key: 'asset', label: 'Asset' }, { key: 'location', label: 'Provider' }, { key: 'size', label: 'Size' }, { key: 'cost', label: 'Monthly' }];
const checkColumns = [{ key: 'check', label: 'Check' }, { key: 'target', label: 'Target' }, { key: 'status', label: 'Status' }];
const releaseColumns = [{ key: 'release', label: 'Release' }, { key: 'deployed', label: 'Deployed' }, { key: 'status', label: 'Status' }];

const formatAmount = (value) => (value ? `ZAR ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '-');
const formatDate = (value) => (value ? new Date(value).toLocaleDateString() : '-');

const load = async () => {
    loading.value = true;

    try {
        const response = await v1(`deployments/${deploymentId}`);
        deployment.value = response?.data ?? response;
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<style scoped>
.detail__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.detail-grid dd { word-break: break-word; }
.intake { display: grid; gap: 1.1rem; max-width: 640px; }
.intake__lead { margin: 0; color: var(--rw-muted); line-height: 1.6; font-size: 0.9rem; }
.intake__lead code { background: var(--rw-surface-2); padding: 0.05rem 0.35rem; border-radius: 5px; font-size: 0.82rem; }
.intake__field { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.intake__label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--rw-muted); min-width: 5rem; }
.intake__value { background: var(--rw-surface-2); border: 1px solid var(--rw-border); border-radius: 6px; padding: 0.3rem 0.55rem; font-size: 0.82rem; word-break: break-all; }
.intake__value--token { color: var(--rw-700); font-weight: 600; }
.intake__muted { color: var(--rw-muted); font-size: 0.85rem; }
.intake__actions { display: flex; gap: 0.5rem; align-items: center; margin-top: 0.3rem; }
@media (max-width: 1200px) { .detail__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .detail__stats { grid-template-columns: 1fr; } }
</style>
