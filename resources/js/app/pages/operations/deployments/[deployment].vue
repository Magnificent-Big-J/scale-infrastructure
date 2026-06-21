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
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const session = useSessionStore();
const deploymentId = route.params.deployment;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);

const tab = ref('overview');
const deployment = ref(null);
const loading = ref(false);

const assets = computed(() => deployment.value?.infrastructure_assets ?? []);
const checks = computed(() => deployment.value?.monitoring_checks ?? []);
const releases = computed(() => deployment.value?.releases ?? []);

const noMeta = { current_page: 1, last_page: 1, per_page: 100, total: 0 };

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
.detail-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--rw-muted); font-size: 0.85rem; text-decoration: none; }
.back-link:hover { color: var(--rw-700); }
.detail__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.detail-tabs { border-bottom: 1px solid var(--rw-border); margin-bottom: 1.25rem; }
.detail-window { padding-top: 0.25rem; }
.detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 2rem; margin: 0; }
.detail-grid__wide { grid-column: 1 / -1; }
.detail-grid dt { color: var(--rw-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }
.detail-grid dd { margin: 0.2rem 0 0; font-size: 0.92rem; word-break: break-word; }
.detail-cell { display: grid; gap: 0.1rem; }
.detail-cell small { color: var(--rw-muted); font-size: 0.78rem; }
.text-sm { font-size: 0.85rem; }
@media (max-width: 1200px) { .detail__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .detail-page { padding: 1.75rem 1rem 3rem; } .detail__stats { grid-template-columns: 1fr; } .detail-grid { grid-template-columns: 1fr; } }
</style>
