<template>
    <div class="module-placeholder">
        <AppPageHeader :eyebrow="eyebrow" :title="title" :subtitle="subtitle">
            <template #metrics>
                <AppStatusBadge status="processing" :label="status" />
            </template>
        </AppPageHeader>

        <div v-if="summaryCards.length" class="module-placeholder__stats">
            <AppStatCard
                v-for="card in summaryCards"
                :key="card.label"
                :label="card.label"
                :value="card.value"
                :helper="card.helper"
                :icon="card.icon"
                :status="card.status"
            />
        </div>

        <AppSectionCard :title="records.length ? 'Seeded snapshot' : 'Planned scope'" :subtitle="scope">
            <div v-if="records.length" class="module-record-grid">
                <article v-for="record in records" :key="record.id" class="module-record">
                    <div class="module-record__top">
                        <span class="module-record__label">{{ record.label }}</span>
                        <AppStatusBadge :status="record.status" :label="formatStatus(record.status)" />
                    </div>
                    <h3 class="module-record__title">{{ record.headline }}</h3>
                    <p class="module-record__summary">{{ record.summary }}</p>
                    <dl v-if="metricEntries(record).length" class="module-record__metrics">
                        <div v-for="[key, value] in metricEntries(record)" :key="key" class="module-record__metric">
                            <dt>{{ key }}</dt>
                            <dd>{{ value }}</dd>
                        </div>
                    </dl>
                </article>
            </div>
            <AppEmptyState
                v-else
                :title="emptyTitle"
                :text="emptyText"
                :icon="icon"
            />
        </AppSectionCard>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

import AppEmptyState from './AppEmptyState.vue';
import AppPageHeader from './AppPageHeader.vue';
import AppSectionCard from './AppSectionCard.vue';
import AppStatCard from './AppStatCard.vue';
import AppStatusBadge from './AppStatusBadge.vue';
import { v1 } from '../utils/api';

const props = defineProps({
    eyebrow: { type: String, default: 'Scale Infrastructure' },
    title: { type: String, required: true },
    subtitle: { type: String, required: true },
    scope: { type: String, required: true },
    status: { type: String, default: 'Planned' },
    icon: { type: String, default: 'mdi-progress-wrench' },
    pageKey: { type: String, default: null },
    emptyTitle: { type: String, default: 'Module not built yet' },
    emptyText: { type: String, default: 'This page is part of the Phase 0 navigation shell and will be implemented in the planned module sequence.' },
});

const records = ref([]);
const summary = ref(null);

const formatStatus = (value) =>
    String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());

const metricEntries = (record) => Object.entries(record.metrics ?? {});

const summaryCards = computed(() => {
    if (!summary.value || !records.value.length) return [];

    const statuses = summary.value.statuses ?? {};
    const atRisk = statuses.processing ?? 0;
    const resolved = statuses.resolved ?? 0;

    return [
        {
            label: 'Seeded records',
            value: String(summary.value.total ?? records.value.length),
            helper: 'Available in this module view',
            icon: 'mdi-database-outline',
            status: 'active',
        },
        {
            label: 'In progress',
            value: String(atRisk),
            helper: 'Records needing active attention',
            icon: 'mdi-progress-clock',
            status: atRisk ? 'processing' : 'active',
        },
        {
            label: 'Resolved',
            value: String(resolved),
            helper: 'Closed or completed records',
            icon: 'mdi-check-circle-outline',
            status: resolved ? 'active' : 'inactive',
        },
    ];
});

const load = async () => {
    if (!props.pageKey) return;

    try {
        const response = await v1(`module-demo/${props.pageKey}`);

        records.value = response?.data?.map((item) => item?.data ?? item) ?? [];
        summary.value = response?.summary ?? null;
    } catch (_error) {
        records.value = [];
        summary.value = null;
    }
};

onMounted(load);
</script>

<style scoped>
.module-placeholder {
    width: 100%;
    max-width: var(--rw-content-max);
    margin: 0 auto;
    padding: 2.25rem 2rem 4rem;
    display: grid;
    gap: 1.5rem;
}

.module-placeholder__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
}

.module-record-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
}

.module-record {
    display: grid;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid var(--rw-border);
    border-radius: 12px;
    background: var(--rw-surface-2);
}

.module-record__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.module-record__label {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--rw-muted);
}

.module-record__title {
    margin: 0;
    font-size: 1rem;
    line-height: 1.25;
}

.module-record__summary {
    margin: 0;
    color: var(--rw-muted);
    font-size: 0.86rem;
    line-height: 1.55;
}

.module-record__metrics {
    display: grid;
    gap: 0.45rem;
    margin: 0;
}

.module-record__metric {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding-top: 0.45rem;
    border-top: 1px solid var(--rw-border);
    font-size: 0.8rem;
}

.module-record__metric dt {
    color: var(--rw-muted);
}

.module-record__metric dd {
    margin: 0;
    font-weight: 700;
    text-align: right;
}

@media (max-width: 1180px) {
    .module-record-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 920px) {
    .module-placeholder__stats,
    .module-record-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .module-placeholder {
        padding: 1.35rem 1rem 3rem;
    }
}
</style>
