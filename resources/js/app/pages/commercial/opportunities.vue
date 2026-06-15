<template>
    <ModulePlaceholder
        title="Opportunities"
        subtitle="Sales pipeline visibility for potential implementations, retainers, and expansion work."
        scope="Opportunities are planned for the commercial module after the operational registry foundation is stable."
        status="Planned"
        icon="mdi-chart-timeline-variant"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Opportunities",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
