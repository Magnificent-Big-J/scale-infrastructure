<template>
    <div class="activity-feed">
        <AppTimeline v-if="items.length" :items="items" />

        <div v-else-if="!loading" class="activity-feed__empty">
            <v-icon icon="mdi-history" size="22" />
            <span>{{ emptyText }}</span>
        </div>

        <div v-if="loading && !items.length" class="activity-feed__loading">
            <v-progress-circular indeterminate size="20" width="2" color="primary" />
            <span>Loading activity…</span>
        </div>

        <div v-if="hasMore" class="activity-feed__more">
            <v-btn variant="text" size="small" :loading="loading" @click="loadMore">Load more</v-btn>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

import { v1 } from '../utils/api';
import AppTimeline from './AppTimeline.vue';

const props = defineProps({
    logName: { type: String, default: null },
    subjectType: { type: String, default: null },
    subjectId: { type: [String, Number], default: null },
    perPage: { type: Number, default: 8 },
    emptyText: { type: String, default: 'No activity recorded yet.' },
});

const rows = ref([]);
const meta = ref({ current_page: 1, last_page: 1 });
const loading = ref(false);

const hasMore = computed(() => meta.value.current_page < meta.value.last_page);

// Map the log event to a timeline dot colour.
const eventType = (event) => {
    if (['created', 'restored', 'approved', 'deployed', 'resolved'].includes(event)) return 'success';
    if (['deleted', 'archived', 'rolled_back', 'rejected', 'failed'].includes(event)) return 'error';
    if (['updated', 'assigned'].includes(event)) return 'info';
    return 'info';
};

const relativeTime = (value) => {
    if (!value) return '';

    const diff = Date.now() - new Date(value).getTime();
    const mins = Math.round(diff / 60000);

    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;

    const hours = Math.round(mins / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.round(hours / 24);
    if (days < 30) return `${days}d ago`;

    return new Date(value).toLocaleDateString();
};

const items = computed(() =>
    rows.value.map((row) => ({
        id: row.id,
        type: eventType(row.event),
        title: row.description || `${row.event} ${row.subject_type ?? ''}`.trim(),
        text: [row.causer_name ? `by ${row.causer_name}` : 'System', row.subject_type].filter(Boolean).join(' · '),
        time: relativeTime(row.created_at),
    })),
);

const fetchPage = async (page) => {
    loading.value = true;

    try {
        const params = new URLSearchParams({ page, per_page: props.perPage });

        if (props.logName) params.set('log_name', props.logName);
        if (props.subjectType) params.set('subject_type', props.subjectType);
        if (props.subjectId) params.set('subject_id', props.subjectId);

        const response = await v1(`activities?${params}`);
        const fetched = response?.data?.map((item) => item?.data ?? item) ?? [];

        rows.value = page === 1 ? fetched : [...rows.value, ...fetched];
        meta.value = response?.meta ?? meta.value;
    } catch {
        // Insufficient permission or transient error — leave the feed empty.
        if (page === 1) rows.value = [];
    } finally {
        loading.value = false;
    }
};

const loadMore = () => fetchPage(meta.value.current_page + 1);

onMounted(() => fetchPage(1));

defineExpose({ refresh: () => fetchPage(1) });
</script>

<style scoped>
.activity-feed {
    display: grid;
    gap: 1rem;
}

.activity-feed__empty,
.activity-feed__loading {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1.25rem 0;
    font-size: 0.86rem;
    color: var(--rw-muted);
}

.activity-feed__more {
    display: flex;
    justify-content: center;
}
</style>
