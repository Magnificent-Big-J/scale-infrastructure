<template>
    <div class="detail-page">
        <div class="page-wrap">
            <RouterLink to="/operations/incidents" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to incidents</RouterLink>

            <AppPageHeader :eyebrow="incident?.reference || 'Incident'" :title="incident?.title || 'Incident'" :subtitle="incident?.client_name || 'Incident detail and history.'">
                <template #metrics>
                    <AppStatusBadge v-if="incident" :status="incident.severity_color || incident.severity" :label="incident.severity_label || incident.severity" />
                    <AppStatusBadge v-if="incident" :status="incident.status_color || incident.status" :label="incident.status_label || incident.status" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="goToList">Edit incident</v-btn>
                </template>
            </AppPageHeader>

            <div class="detail__stats">
                <AppStatCard label="Severity" :value="incident?.severity_label || '-'" helper="Impact level" icon="mdi-alert-circle-outline" status="pending" />
                <AppStatCard label="Status" :value="incident?.status_label || '-'" helper="Current state" icon="mdi-progress-clock" status="processing" />
                <AppStatCard label="Started" :value="formatDate(incident?.started_at)" helper="Incident start time" icon="mdi-clock-start" status="active" />
                <AppStatCard label="Resolved" :value="formatDate(incident?.resolved_at)" helper="Resolution time" icon="mdi-clock-end" status="active" />
            </div>

            <AppSectionCard title="Incident workspace" subtitle="Overview, discussion, and full change history.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab value="discussion">Discussion</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Client</dt><dd>{{ incident?.client_name || '-' }}</dd></div>
                            <div><dt>Deployment</dt><dd>{{ incident?.deployment_name || '-' }}</dd></div>
                            <div><dt>Started</dt><dd>{{ formatDate(incident?.started_at) }}</dd></div>
                            <div><dt>Resolved</dt><dd>{{ formatDate(incident?.resolved_at) }}</dd></div>
                            <div class="detail-grid__wide"><dt>Root cause</dt><dd><AppRichTextDisplay :content="incident?.root_cause" /></dd></div>
                            <div class="detail-grid__wide"><dt>Resolution summary</dt><dd><AppRichTextDisplay :content="incident?.resolution_summary" /></dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item value="discussion">
                        <AppComments v-if="tab === 'discussion'" :resource-url="`incidents/${incidentId}`" :can-comment="canComment" />
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="Incident"
                            :subject-id="incidentId"
                            :per-page="12"
                            empty-text="No activity recorded for this incident yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Incident detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppActivityFeed from '../../../components/AppActivityFeed.vue';
import AppComments from '../../../components/AppComments.vue';
import AppRichTextDisplay from '../../../components/AppRichTextDisplay.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const incidentId = route.params.incident;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);
const canComment = computed(() => session.user?.permissions?.includes('incidents.comment') ?? false);

const tab = ref('overview');
const incident = ref(null);

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '-');

const goToList = () => router.push('/operations/incidents');

const load = async () => {
    const response = await v1(`incidents/${incidentId}`);
    incident.value = response?.data ?? response;
};

onMounted(load);
</script>

<style scoped>
.detail__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
@media (max-width: 1200px) { .detail__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .detail__stats { grid-template-columns: 1fr; } }
</style>
