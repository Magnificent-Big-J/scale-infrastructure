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
                <AppSectionCard title="Deployment health" subtitle="Deployments grouped by lifecycle status.">
                    <ul class="status-list">
                        <li v-for="(count, status) in metrics.deployments_by_status" :key="status">
                            <AppStatusBadge :status="status" :label="labelize(status)" />
                            <span class="status-list__count">{{ count }}</span>
                        </li>
                    </ul>
                </AppSectionCard>

                <AppSectionCard title="Monitoring posture" subtitle="Monitoring checks grouped by current status.">
                    <ul class="status-list">
                        <li v-for="(count, status) in metrics.monitoring_by_status" :key="status">
                            <AppStatusBadge :status="status" :label="labelize(status)" />
                            <span class="status-list__count">{{ count }}</span>
                        </li>
                    </ul>
                    <div class="ops__footnote">{{ metrics.infrastructure_assets }} infrastructure assets tracked.</div>
                </AppSectionCard>
            </div>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Operations Dashboard","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted } from 'vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import { useDashboardsStore } from '../../stores/dashboards';

const store = useDashboardsStore();
const metrics = computed(() => store.operations);

const labelize = (value) => String(value).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

onMounted(() => store.fetchOperations());
</script>

<style scoped>
.ops-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }
.ops__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.ops__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
.status-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.6rem; }
.status-list li { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.status-list__count { font-weight: 800; font-size: 0.95rem; }
.ops__footnote { margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--rw-border); color: var(--rw-muted); font-size: 0.82rem; }
@media (max-width: 1100px) { .ops__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .ops-page { padding: 1.75rem 1rem 3rem; } .ops__stats, .ops__grid { grid-template-columns: 1fr; } }
</style>
