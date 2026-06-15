<template>
    <ModulePlaceholder
        title="Clients"
        subtitle="Client master records, contacts, account ownership, status, and health."
        scope="Module 01 will build client CRUD, contact management, primary contact handling, status filters, and activity logging."
        status="Module 01"
        icon="mdi-domain"
    />
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Clients",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import ModulePlaceholder from '../../components/ModulePlaceholder.vue';
</script>
