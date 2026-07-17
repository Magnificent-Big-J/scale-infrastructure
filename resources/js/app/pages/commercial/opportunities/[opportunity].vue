<template>
    <div class="detail-page">
        <div class="page-wrap">
            <RouterLink to="/commercial/opportunities" class="back-link"><v-icon size="16">mdi-arrow-left</v-icon> Back to opportunities</RouterLink>

            <AppPageHeader :eyebrow="deal?.display_name || 'Opportunity'" :title="deal?.title || 'Opportunity'" :subtitle="deal?.source ? `Source: ${deal.source}` : 'Sales pipeline detail.'">
                <template #metrics>
                    <AppStatusBadge v-if="deal" :status="deal.stage_color" :label="deal.stage_label" />
                </template>
                <template #actions>
                    <v-btn v-if="canWin && deal && !['won', 'lost'].includes(deal.stage)" color="primary" prepend-icon="mdi-trophy-outline" :loading="winning" @click="win">Mark won</v-btn>
                    <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" @click="goToList">Edit</v-btn>
                </template>
            </AppPageHeader>

            <div class="detail__stats">
                <AppStatCard label="Value" :value="formatAmount(deal?.value)" helper="Estimated deal value" icon="mdi-cash-multiple" status="active" />
                <AppStatCard label="Probability" :value="deal?.probability != null ? `${deal.probability}%` : '—'" helper="Likelihood to close" icon="mdi-percent-outline" status="processing" />
                <AppStatCard label="Owner" :value="deal?.owner_name || 'Unassigned'" helper="Deal owner" icon="mdi-account-outline" status="active" />
                <AppStatCard label="Expected close" :value="deal?.expected_close_date || '—'" helper="Target date" icon="mdi-calendar-outline" status="processing" />
            </div>

            <AppSectionCard title="Opportunity workspace" subtitle="Overview and full history.">
                <v-tabs v-model="tab" class="detail-tabs" color="primary" density="comfortable">
                    <v-tab value="overview">Overview</v-tab>
                    <v-tab v-if="canViewActivity" value="activity">Activity</v-tab>
                </v-tabs>

                <v-window v-model="tab" class="detail-window">
                    <v-window-item value="overview">
                        <dl class="detail-grid">
                            <div><dt>Client</dt><dd>{{ deal?.client_name || '—' }}</dd></div>
                            <div><dt>Prospect</dt><dd>{{ deal?.prospect_name || '—' }}</dd></div>
                            <div><dt>Stage</dt><dd>{{ deal?.stage_label || '—' }}</dd></div>
                            <div><dt>Source</dt><dd>{{ deal?.source || '—' }}</dd></div>
                            <div><dt>Won</dt><dd>{{ formatDate(deal?.won_at) }}</dd></div>
                            <div><dt>Lost</dt><dd>{{ formatDate(deal?.lost_at) }}</dd></div>
                            <div v-if="deal?.contract_id"><dt>Contract</dt><dd><RouterLink class="detail-link" :to="`/commercial/contracts/${deal.contract_id}`">{{ deal.contract_name || 'View contract' }}</RouterLink></dd></div>
                            <div v-if="deal?.lost_reason"><dt>Lost reason</dt><dd>{{ deal.lost_reason }}</dd></div>
                            <div class="detail-grid__wide"><dt>Description</dt><dd><AppRichTextDisplay :content="deal?.description" empty-text="—" /></dd></div>
                        </dl>
                    </v-window-item>

                    <v-window-item v-if="canViewActivity" value="activity">
                        <AppActivityFeed
                            v-if="tab === 'activity'"
                            subject-type="Opportunity"
                            :subject-id="opportunityId"
                            :per-page="12"
                            empty-text="No activity recorded for this opportunity yet."
                        />
                    </v-window-item>
                </v-window>
            </AppSectionCard>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Opportunity detail","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppActivityFeed from '../../../components/AppActivityFeed.vue';
import AppRichTextDisplay from '../../../components/AppRichTextDisplay.vue';
import AppSectionCard from '../../../components/AppSectionCard.vue';
import AppStatCard from '../../../components/AppStatCard.vue';
import { useToast, errorMessage } from '../../../composables/useToast';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const toast = useToast();
const opportunityId = route.params.opportunity;

const canViewActivity = computed(() => session.user?.permissions?.includes('activity.view') ?? false);
const canWin = computed(() => session.user?.permissions?.includes('opportunities.update') ?? false);

const tab = ref('overview');
const deal = ref(null);
const winning = ref(false);

const formatAmount = (value) => `ZAR ${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
const formatDate = (value) => (value ? new Date(value).toLocaleDateString() : '—');
const goToList = () => router.push('/commercial/opportunities');

const load = async () => {
    const response = await v1(`opportunities/${opportunityId}`);
    deal.value = response?.data ?? response;
};

const win = async () => {
    winning.value = true;

    try {
        const updated = await v1(`opportunities/${opportunityId}/win`, { method: 'POST' });
        deal.value = updated?.data ?? updated;
        toast.success('Marked won — a draft contract was created for clients.');
    } catch (error) {
        toast.error(errorMessage(error, 'Could not mark the opportunity won.'));
    } finally {
        winning.value = false;
    }
};

onMounted(load);
</script>

<style scoped>
.detail__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.detail-link { color: var(--rw-600); font-weight: 600; text-decoration: none; }
@media (max-width: 1200px) { .detail__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 960px) { .detail__stats { grid-template-columns: 1fr; } }
</style>
