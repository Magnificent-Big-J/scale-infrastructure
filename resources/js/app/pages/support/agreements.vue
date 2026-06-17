<template>
    <ModulePlaceholder
        title="Support Agreements"
        subtitle="Support retainers, tiers, monthly fees, included hours, response SLAs, and dates."
        scope="Module 03 will build support agreement records linked to clients and contracts."
        status="Module 03"
        icon="mdi-handshake-outline"
        page-key="support.agreements"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Support Agreements",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
