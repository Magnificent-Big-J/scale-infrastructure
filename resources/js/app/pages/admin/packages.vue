<template>
    <ModulePlaceholder
        title="Packages"
        subtitle="Product packages, implementation ranges, support ranges, and recommended package flags."
        scope="Module 01 will build package definitions and link packages to products, contracts, and support agreements."
        status="Module 01"
        icon="mdi-tag-multiple-outline"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Packages",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
