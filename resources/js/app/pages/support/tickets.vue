<template>
    <ModulePlaceholder
        title="Support Tickets"
        subtitle="Client requests, issue categories, severity, assignment, hours logged, and resolution."
        scope="Module 03 will build support ticket lifecycle tracking and client support tabs."
        status="Module 03"
        icon="mdi-ticket-confirmation-outline"
        page-key="support.tickets"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Support Tickets",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
