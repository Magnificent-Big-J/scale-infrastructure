<template>
    <ModulePlaceholder
        title="SLA Overview"
        subtitle="Response expectations, SLA risk, escalation visibility, and monthly service review inputs."
        scope="Module 03 and Module 07 will turn support agreements and tickets into service-review controls."
        status="Planned"
        icon="mdi-timer-sand"
        page-key="support.sla-overview"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "SLA Overview",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
