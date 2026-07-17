<template>
    <div class="detail-page">
        <div class="page-wrap">
            <RouterLink to="/operations/releases" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to releases</RouterLink>

            <AppPageHeader :eyebrow="release?.deployment_name || 'Release'" :title="release?.version || 'Release'" :subtitle="release?.client_name || 'Release record and history.'">
                <template #metrics>
                    <AppStatusBadge v-if="release" :status="release.status_color || release.status" :label="release.status_label || release.status" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="goToList">Manage release</v-btn>
                </template>
            </AppPageHeader>

            <div class="detail__stats">
                <AppStatCard label="Status" :value="release?.status_label || '-'" helper="Current state" icon="mdi-source-branch" status="processing" />
                <AppStatCard label="Approved by" :value="release?.approved_by_name || 'Not approved'" helper="Approval owner" icon="mdi-check-decagram-outline" status="active" />
                <AppStatCard label="Deployed by" :value="release?.deployed_by_name || 'Not deployed'" helper="Deployment owner" icon="mdi-rocket-launch-outline" status="active" />
            </div>

            <AppSectionCard title="Release workspace" subtitle="Overview and full change history.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Deployment</dt><dd>{{ release?.deployment_name || '-' }}</dd></div>
                            <div><dt>Client</dt><dd>{{ release?.client_name || '-' }}</dd></div>
                            <div><dt>Change request</dt><dd>{{ release?.change_request_reference || '-' }}</dd></div>
                            <div><dt>Approved</dt><dd>{{ formatDate(release?.approved_at) }}</dd></div>
                            <div><dt>Deployed</dt><dd>{{ formatDate(release?.deployed_at) }}</dd></div>
                            <div><dt>Rolled back</dt><dd>{{ formatDate(release?.rolled_back_at) }}</dd></div>
                            <div class="detail-grid__wide"><dt>Notes</dt><dd>{{ release?.notes || '-' }}</dd></div>
                            <div v-if="release?.rollback_notes" class="detail-grid__wide"><dt>Rollback notes</dt><dd>{{ release.rollback_notes }}</dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="Release"
                            :subject-id="releaseId"
                            :per-page="12"
                            empty-text="No activity recorded for this release yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Release detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppActivityFeed from '../../../components/AppActivityFeed.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const releaseId = route.params.release;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);

const tab = ref('overview');
const release = ref(null);

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '-');
const goToList = () => router.push('/operations/releases');

const load = async () => {
    const response = await v1(`releases/${releaseId}`);
    release.value = response?.data ?? response;
};

onMounted(load);
</script>

<style scoped>
.detail__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
@media (max-width: 960px) { .detail__stats { grid-template-columns: 1fr; } }
</style>
