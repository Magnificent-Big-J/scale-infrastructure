<template>
    <div class="detail-page">
        <div class="page-wrap">
            <RouterLink to="/support/tickets" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to tickets</RouterLink>

            <AppPageHeader :eyebrow="ticket?.reference || 'Ticket'" :title="ticket?.subject || 'Support ticket'" :subtitle="ticket?.client_name || 'Support request detail and history.'">
                <template #metrics>
                    <AppStatusBadge v-if="ticket" :status="ticket.severity_color || ticket.severity" :label="ticket.severity_label || ticket.severity" />
                    <AppStatusBadge v-if="ticket" :status="ticket.status_color || ticket.status" :label="ticket.status_label || ticket.status" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="goToList">Edit ticket</v-btn>
                </template>
            </AppPageHeader>

            <div class="detail__stats">
                <AppStatCard label="Severity" :value="ticket?.severity_label || '-'" helper="Impact level" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Status" :value="ticket?.status_label || '-'" helper="Current state" icon="mdi-progress-clock" status="processing" />
                <AppStatCard label="Hours logged" :value="String(ticket?.hours_logged ?? 0)" helper="Recorded effort" icon="mdi-clock-outline" status="active" />
                <AppStatCard label="Assigned to" :value="ticket?.assigned_user_name || 'Unassigned'" helper="Owner" icon="mdi-account-outline" status="active" />
            </div>

            <AppSectionCard title="Ticket workspace" subtitle="Overview and full change history.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab value="discussion">Discussion</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Client</dt><dd>{{ ticket?.client_name || '-' }}</dd></div>
                            <div><dt>Deployment</dt><dd>{{ ticket?.deployment_name || '-' }}</dd></div>
                            <div><dt>Support agreement</dt><dd>{{ ticket?.agreement_name || '-' }}</dd></div>
                            <div><dt>Category</dt><dd>{{ ticket?.category || '-' }}</dd></div>
                            <div v-if="ticket"><dt>SLA</dt><dd><AppStatusBadge :status="ticket.sla_status_color" :label="ticket.sla_status_label" /></dd></div>
                            <div><dt>Opened</dt><dd>{{ formatDate(ticket?.opened_at) }}</dd></div>
                            <div><dt>Resolved</dt><dd>{{ formatDate(ticket?.resolved_at) }}</dd></div>
                            <div class="detail-grid__wide"><dt>Summary</dt><dd>{{ ticket?.summary || '-' }}</dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item value="discussion">
                        <AppTicketComments v-if="tab === 'discussion'" :ticket-id="ticketId" :can-comment="canComment" />
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="SupportTicket"
                            :subject-id="ticketId"
                            :per-page="12"
                            empty-text="No activity recorded for this ticket yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Ticket detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppActivityFeed from '../../../components/AppActivityFeed.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import AppTicketComments from '../../../components/AppTicketComments.vue';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const ticketId = route.params.ticket;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);
const canComment = computed(() => session.user?.permissions?.includes('support_tickets.comment') ?? false);

const tab = ref('overview');
const ticket = ref(null);

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '-');

const goToList = () => router.push('/support/tickets');

const load = async () => {
    const response = await v1(`support-tickets/${ticketId}`);
    ticket.value = response?.data ?? response;
};

onMounted(load);
</script>

<style scoped>
.detail__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
@media (max-width: 1200px) { .detail__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .detail__stats { grid-template-columns: 1fr; } }
</style>
