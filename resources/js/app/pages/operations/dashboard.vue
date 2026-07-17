<template>
    <div class="ops-page">
        <div class="page-wrap">
            <AppPageHeader eyebrow="Module 05" title="Operations Dashboard" subtitle="Deployment status and monitoring posture across all environments.">
                <template #metrics>
                    <AppStatusBadge :status="metrics.open_incidents ? 'suspended' : 'active'" :label="`${metrics.open_incidents} open incidents`" />
                </template>
            </AppPageHeader>

            <div class="ops__stats">
                <AppStatCard label="Deployments" :value="String(metrics.deployments_total)" helper="All environments" icon="mdi-rocket-launch-outline" status="active" />
                <AppStatCard label="Active" :value="String(metrics.active_deployments)" helper="Healthy deployments" icon="mdi-check-decagram-outline" status="active" />
                <AppStatCard label="Degraded" :value="String(metrics.degraded_deployments)" helper="Need attention" :status="metrics.degraded_deployments ? 'suspended' : 'processing'" icon="mdi-alert-decagram-outline" />
                <AppStatCard label="Failing checks" :value="String(metrics.failing_checks)" :helper="`${metrics.warning_checks} warnings`" :status="metrics.failing_checks ? 'suspended' : 'active'" icon="mdi-pulse" />
            </div>

            <div class="ops__grid">
                <AppDonutChart
                    v-if="deploymentDist.series.length"
                    title="Deployment health"
                    subtitle="Deployments grouped by lifecycle status."
                    :labels="deploymentDist.labels"
                    :series="deploymentDist.series"
                    :colors="deploymentDist.colors"
                />
                <AppSectionCard v-else title="Deployment health" subtitle="Deployments grouped by lifecycle status.">
                    <p class="ops__empty">No deployments recorded yet.</p>
                </AppSectionCard>

                <div class="ops__monitor">
                    <AppDonutChart
                        v-if="monitoringDist.series.length"
                        title="Monitoring posture"
                        subtitle="Monitoring checks grouped by current status."
                        :labels="monitoringDist.labels"
                        :series="monitoringDist.series"
                        :colors="monitoringDist.colors"
                    />
                    <AppSectionCard v-else title="Monitoring posture" subtitle="Monitoring checks grouped by current status.">
                        <p class="ops__empty">No monitoring checks recorded yet.</p>
                    </AppSectionCard>
                    <div class="ops__footnote">{{ metrics.infrastructure_assets }} infrastructure assets tracked.</div>
                </div>
            </div>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Operations Dashboard","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted } from 'vue';

import AppDonutChart from '../../components/AppDonutChart.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import { useDashboardsStore } from '../../stores/dashboards';

const store = useDashboardsStore();
const metrics = computed(() => store.operations);

const labelize = (value) => String(value).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

// Map a status value to a semantic donut colour (graphite·electric palette).
const statusColor = (status) => ({
    active: '#16a34a', passing: '#16a34a',
    provisioning: '#d97706', warning: '#d97706', pending: '#d97706',
    degraded: '#dc2626', failing: '#dc2626',
    planned: '#2563eb',
    retired: '#94a3b8', paused: '#94a3b8', inactive: '#94a3b8',
}[status] || '#2563eb');

// Turn a {status: count} map into donut series, dropping empty buckets.
const toDistribution = (byStatus) => {
    const labels = [];
    const series = [];
    const colors = [];

    Object.entries(byStatus || {}).forEach(([status, count]) => {
        if (count > 0) {
            labels.push(labelize(status));
            series.push(count);
            colors.push(statusColor(status));
        }
    });

    return { labels, series, colors };
};

const deploymentDist = computed(() => toDistribution(metrics.value.deployments_by_status));
const monitoringDist = computed(() => toDistribution(metrics.value.monitoring_by_status));

onMounted(() => store.fetchOperations());
</script>

<style scoped>
.ops__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.ops__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; align-items: start; }
.ops__monitor { display: grid; gap: 0.75rem; }
.ops__footnote { color: var(--rw-muted); font-size: 0.82rem; padding-left: 0.25rem; }
.ops__empty { margin: 0; color: var(--rw-muted); font-size: 0.88rem; }
@media (max-width: 1100px) { .ops__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .ops__stats, .ops__grid { grid-template-columns: 1fr; } }
</style>
