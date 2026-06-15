<template>
    <div class="module-placeholder">
        <AppPageHeader :eyebrow="eyebrow" :title="title" :subtitle="subtitle">
            <template #metrics>
                <AppStatusBadge status="processing" :label="status" />
            </template>
        </AppPageHeader>

        <AppSectionCard title="Planned scope" :subtitle="scope">
            <AppEmptyState
                :title="emptyTitle"
                :text="emptyText"
                :icon="icon"
            />
        </AppSectionCard>
    </div>
</template>

<script setup>
import AppEmptyState from './AppEmptyState.vue';
import AppPageHeader from './AppPageHeader.vue';
import AppSectionCard from './AppSectionCard.vue';
import AppStatusBadge from './AppStatusBadge.vue';

defineProps({
    eyebrow: { type: String, default: 'Scale Infrastructure' },
    title: { type: String, required: true },
    subtitle: { type: String, required: true },
    scope: { type: String, required: true },
    status: { type: String, default: 'Planned' },
    icon: { type: String, default: 'mdi-progress-wrench' },
    emptyTitle: { type: String, default: 'Module not built yet' },
    emptyText: { type: String, default: 'This page is part of the Phase 0 navigation shell and will be implemented in the planned module sequence.' },
});
</script>

<style scoped>
.module-placeholder {
    width: min(100%, 1080px);
    margin: 0 auto;
    padding: 2.25rem 2rem 4rem;
    display: grid;
    gap: 1.5rem;
}

@media (max-width: 720px) {
    .module-placeholder {
        padding: 1.35rem 1rem 3rem;
    }
}
</style>
