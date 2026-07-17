<template>
    <div class="board-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Commercial"
                title="Pipeline board"
                subtitle="Drag opportunities across stages to advance them. Click a card to open the full deal."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${opportunities.length} deals`" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-format-list-bulleted" to="/commercial/opportunities">List view</v-btn>
                </template>
            </AppPageHeader>

            <div class="board" :class="{ 'board--loading': loading }">
                <section
                    v-for="column in columns"
                    :key="column.value"
                    class="board-col"
                    :class="{ 'board-col--over': dragOver === column.value }"
                    @dragover.prevent="canUpdate && (dragOver = column.value)"
                    @dragleave="dragOver = dragOver === column.value ? null : dragOver"
                    @drop="onDrop(column.value)"
                >
                    <header class="board-col__head">
                        <span class="board-col__title">{{ column.label }}</span>
                        <span class="board-col__count">{{ valueFor(column.value) }}</span>
                    </header>

                    <div class="board-col__cards">
                        <article
                            v-for="deal in dealsFor(column.value)"
                            :key="deal.id"
                            class="board-card"
                            :class="{ 'board-card--busy': busyId === deal.id }"
                            :draggable="canUpdate"
                            @dragstart="onDragStart(deal)"
                            @dragend="onDragEnd"
                            @click="openDeal(deal)"
                        >
                            <p class="board-card__title">{{ deal.title }}</p>
                            <div class="board-card__foot">
                                <span class="board-card__client">{{ deal.display_name }}</span>
                                <strong class="board-card__value">{{ formatAmount(deal.value) }}</strong>
                            </div>
                            <span v-if="deal.owner_name" class="board-card__owner">{{ deal.owner_name }}</span>
                        </article>

                        <p v-if="!dealsFor(column.value).length" class="board-col__empty">No deals</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Pipeline Board","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import { useToast, errorMessage } from '../../../composables/useToast';
import { useSessionStore } from '../../../stores/session';
import { v1 } from '../../../utils/api';

const router = useRouter();
const session = useSessionStore();
const toast = useToast();

const canUpdate = session.user?.permissions?.includes('opportunities.update') ?? false;

const opportunities = ref([]);
const columns = ref([]);
const loading = ref(false);
const draggedId = ref(null);
const dragOver = ref(null);
const busyId = ref(null);

const formatAmount = (value) => `ZAR ${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
const dealsFor = (stage) => opportunities.value.filter((deal) => deal.stage === stage);
const valueFor = (stage) => formatAmount(dealsFor(stage).reduce((sum, deal) => sum + Number(deal.value || 0), 0));

const load = async () => {
    loading.value = true;

    try {
        const response = await v1('opportunities?per_page=100');
        opportunities.value = response?.data?.map((item) => item?.data ?? item) ?? [];
        columns.value = response?.options?.stages ?? [];
    } finally {
        loading.value = false;
    }
};

const openDeal = (deal) => {
    if (!draggedId.value) router.push(`/commercial/opportunities/${deal.id}`);
};

const onDragStart = (deal) => {
    if (!canUpdate) return;
    draggedId.value = deal.id;
};

const onDragEnd = () => {
    draggedId.value = null;
    dragOver.value = null;
};

const onDrop = async (stage) => {
    dragOver.value = null;

    const deal = opportunities.value.find((item) => item.id === draggedId.value);
    draggedId.value = null;

    if (!deal || deal.stage === stage) return;

    const previousStage = deal.stage;
    deal.stage = stage; // optimistic
    busyId.value = deal.id;

    try {
        const updated = stage === 'won'
            ? await v1(`opportunities/${deal.id}/win`, { method: 'POST' })
            : await v1(`opportunities/${deal.id}`, { method: 'PATCH', body: { stage } });
        Object.assign(deal, updated?.data ?? updated);
        toast.success(stage === 'won' ? 'Marked won — a draft contract was created for clients.' : `Moved to ${columns.value.find((c) => c.value === stage)?.label ?? stage}.`);
    } catch (error) {
        deal.stage = previousStage; // revert
        toast.error(errorMessage(error, 'Could not move the opportunity.'));
    } finally {
        busyId.value = null;
    }
};

onMounted(load);
</script>

<style scoped>
.board-page { padding: 2.25rem 2rem 4rem; }
.page-wrap { max-width: var(--rw-content-max); margin: 0 auto; display: grid; gap: 1.5rem; }

.board {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(240px, 1fr);
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    align-items: start;
}

.board--loading { opacity: 0.6; }

.board-col {
    background: var(--rw-surface-2);
    border: 1px solid var(--rw-border);
    border-radius: 14px;
    padding: 0.75rem;
    min-height: 8rem;
    transition: border-color 0.12s, background 0.12s;
}

.board-col--over { border-color: var(--rw-500); background: var(--rw-50); }

.board-col__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.25rem 0.4rem 0.65rem;
}

.board-col__title { font-weight: 700; font-size: 0.9rem; }

.board-col__count {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--rw-muted);
}

.board-col__cards { display: grid; gap: 0.6rem; }

.board-col__empty { margin: 0; padding: 0.75rem 0.4rem; font-size: 0.82rem; color: var(--rw-muted); }

.board-card {
    background: var(--rw-surface);
    border: 1px solid var(--rw-border);
    border-radius: 10px;
    padding: 0.75rem;
    display: grid;
    gap: 0.5rem;
    cursor: pointer;
    box-shadow: var(--rw-shadow-xs);
    transition: box-shadow 0.12s;
}

.board-card[draggable='true'] { cursor: grab; }
.board-card:hover { box-shadow: var(--rw-shadow-sm); }
.board-card--busy { opacity: 0.6; pointer-events: none; }

.board-card__title { margin: 0; font-size: 0.9rem; font-weight: 600; line-height: 1.35; }

.board-card__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.78rem;
    color: var(--rw-muted);
}

.board-card__value { color: var(--rw-ink); }

.board-card__owner {
    font-size: 0.74rem;
    color: var(--rw-muted);
    background: var(--rw-surface-2);
    border-radius: 999px;
    padding: 0.1rem 0.5rem;
    justify-self: start;
}

@media (max-width: 720px) {
    .board-page { padding: 1.5rem 1rem 3rem; }
}
</style>
