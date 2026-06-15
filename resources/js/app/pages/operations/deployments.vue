<template>
    <ModulePlaceholder
        title="Deployments"
        subtitle="Client product environments, domains, app URLs, versions, and deployment status."
        scope="Module 02 will build deployment CRUD, client/product links, environment tracking, and status indicators."
        status="Module 02"
        icon="mdi-rocket-launch-outline"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Deployments",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
