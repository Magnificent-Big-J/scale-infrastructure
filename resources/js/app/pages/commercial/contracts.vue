<template>
    <ModulePlaceholder
        title="Contracts"
        subtitle="Commercial agreements linked to clients, products, packages, renewals, and values."
        scope="Module 04 will build contract tracking, renewal visibility, approval permissions, and client contract tabs."
        status="Module 04"
        icon="mdi-file-sign"
        page-key="commercial.contracts"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Contracts",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
