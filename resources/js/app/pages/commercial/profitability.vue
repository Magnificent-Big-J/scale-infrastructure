<template>
    <ModulePlaceholder
        title="Profitability"
        subtitle="Client profitability by revenue, hosting, labour, monitoring, and other costs."
        scope="Module 05 will build profitability records, margin calculations, and executive reporting."
        status="Module 05"
        icon="mdi-finance"
        page-key="commercial.profitability"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Profitability",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
