<template>
    <ModulePlaceholder
        title="Products"
        subtitle="Code Scale Tech products such as ScaleLens and future operational platforms."
        scope="Module 01 will build product definitions, codes, statuses, package links, and seed data."
        status="Module 01"
        icon="mdi-package-variant-closed"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Products",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
