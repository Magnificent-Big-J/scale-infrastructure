<template>
    <ModulePlaceholder
        title="Infrastructure"
        subtitle="Hosting assets, providers, regions, server sizes, costs, IPs, and metadata."
        scope="Module 02 will build infrastructure asset records linked to deployments."
        status="Module 02"
        icon="mdi-server-network"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Infrastructure",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
