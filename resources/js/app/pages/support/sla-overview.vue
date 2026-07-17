<template>
    <div class="sla-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Module 03"
                title="SLA Overview"
                subtitle="Resolution targets from each support agreement, weighted by severity. Breached and at-risk tickets surface first."
            >
                <template #metrics>
                    <AppStatusBadge :status="summary.breached ? 'suspended' : 'active'" :label="`${summary.breached} breached`" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-ticket-confirmation-outline" to="/support/tickets">All tickets</v-btn>
                </template>
            </AppPageHeader>

            <div class="sla__stats">
                <AppStatCard label="Breached" :value="String(summary.breached)" helper="Past the SLA target" icon="mdi-alert-octagon-outline" status="suspended" />
                <AppStatCard label="At risk" :value="String(summary.at_risk)" helper="Within 25% of target" icon="mdi-timer-alert-outline" status="pending" />
                <AppStatCard label="On track" :value="String(summary.on_track)" helper="Comfortably inside SLA" icon="mdi-timer-check-outline" status="processing" />
                <AppStatCard label="Met" :value="String(summary.met)" helper="Resolved within target" icon="mdi-check-circle-outline" status="active" />
            </div>

            <AppSectionCard title="SLA watchlist" subtitle="Tickets carrying a support agreement, ordered by urgency.">
                <AppDataTable
                    title="Tickets"
                    :columns="columns"
                    :rows="rows"
                    :meta="noMeta"
                    :loading="loading"
                    empty-title="No SLA-tracked tickets"
                    empty-text="Tickets linked to a support agreement will appear here."
                    @row-click="openTicket"
                >
                    <template #row="{ row }">
                        <td><div class="sla-cell"><strong>{{ row.subject }}</strong><small>{{ row.reference }} · {{ row.client_name }}</small></div></td>
                        <td><AppStatusBadge :status="row.severity_color || row.severity" :label="row.severity_label || row.severity" /></td>
                        <td><AppStatusBadge :status="row.sla_status_color" :label="row.sla_status_label" /></td>
                        <td><span class="text-sm">{{ slaTiming(row) }}</span></td>
                        <td><AppStatusBadge :status="row.status_color || row.status" :label="row.status_label || row.status" /></td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"SLA Overview","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import { v1 } from '../../utils/api';

const router = useRouter();

const rows = ref([]);
const summary = reactive({ breached: 0, at_risk: 0, on_track: 0, met: 0 });
const loading = ref(false);

const noMeta = { current_page: 1, last_page: 1, per_page: 100 };
const columns = [
    { key: 'ticket', label: 'Ticket' },
    { key: 'severity', label: 'Severity' },
    { key: 'sla', label: 'SLA' },
    { key: 'timing', label: 'Target' },
    { key: 'status', label: 'Status' },
];

const slaTiming = (row) => {
    if (row.sla_status === 'breached' && row.sla_hours_remaining !== null) {
        return `${Math.abs(row.sla_hours_remaining)}h over`;
    }
    if (row.sla_hours_remaining !== null) {
        return `${row.sla_hours_remaining}h left`;
    }
    if (row.sla_status === 'met') return 'Resolved in time';
    if (row.sla_status === 'breached') return 'Resolved late';

    return '—';
};

const openTicket = (row) => router.push(`/support/tickets/${row.id}`);

const load = async () => {
    loading.value = true;

    try {
        const response = await v1('support/sla');
        rows.value = response?.data?.map((item) => item?.data ?? item) ?? [];
        Object.assign(summary, response?.summary ?? {});
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<style scoped>
.sla__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
@media (max-width: 1200px) { .sla__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .sla-page { padding: 1.5rem 1rem 3rem; } .sla__stats { grid-template-columns: 1fr; } }
</style>
