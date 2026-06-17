<template>
    <ModulePlaceholder
        title="Billing"
        subtitle="Billing records, invoices, payments, MRR, ARR, and overdue account visibility."
        scope="Module 04 will build billing commitments, invoice tracking, payment recording, and finance dashboard inputs."
        status="Module 04"
        icon="mdi-cash-multiple"
        page-key="commercial.billing"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Billing",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
