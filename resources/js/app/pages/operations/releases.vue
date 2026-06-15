<template>
    <ModulePlaceholder
        title="Releases"
        subtitle="Version changes, release approvals, deployment notes, and rollback controls."
        scope="Module 06 will build release tracking after the manual deployment process is stable."
        status="Module 06"
        icon="mdi-source-branch"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Releases",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
