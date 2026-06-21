<template>
    <div class="board-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Module 03"
                title="Ticket board"
                subtitle="Drag tickets across the workflow to update their status. Click a card to open the full ticket."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${tickets.length} tickets`" />
                </template>
                <template #actions>
                    <v-btn variant="tonal" prepend-icon="mdi-format-list-bulleted" to="/support/tickets">List view</v-btn>
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
                        <span class="board-col__count">{{ ticketsFor(column.value).length }}</span>
                    </header>

                    <div class="board-col__cards">
                        <article
                            v-for="ticket in ticketsFor(column.value)"
                            :key="ticket.id"
                            class="board-card"
                            :class="{ 'board-card--busy': busyId === ticket.id }"
                            :draggable="canUpdate"
                            @dragstart="onDragStart(ticket)"
                            @dragend="onDragEnd"
                            @click="openTicket(ticket)"
                        >
                            <div class="board-card__top">
                                <span class="board-card__ref">{{ ticket.reference }}</span>
                                <AppStatusBadge :status="ticket.severity_color || ticket.severity" :label="ticket.severity_label || ticket.severity" />
                            </div>
                            <p class="board-card__subject">{{ ticket.subject }}</p>
                            <div class="board-card__foot">
                                <span class="board-card__client">{{ ticket.client_name || '—' }}</span>
                                <span v-if="ticket.assigned_user_name" class="board-card__owner">{{ ticket.assigned_user_name }}</span>
                            </div>
                        </article>

                        <p v-if="!ticketsFor(column.value).length" class="board-col__empty">No tickets</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<route lang="json">
{"meta":{"layout":"default","title":"Ticket Board","requiresAuth":true,"adminOnly":true}}
</route>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import { useToast, errorMessage } from '../../composables/useToast';
import { useSessionStore } from '../../stores/session';
import { v1 } from '../../utils/api';

const router = useRouter();
const session = useSessionStore();
const toast = useToast();

const canUpdate = session.user?.permissions?.includes('support_tickets.update') ?? false;

const tickets = ref([]);
const columns = ref([]);
const loading = ref(false);
const draggedId = ref(null);
const dragOver = ref(null);
const busyId = ref(null);

const ticketsFor = (status) => tickets.value.filter((ticket) => ticket.status === status);

const load = async () => {
    loading.value = true;

    try {
        const response = await v1('support-tickets?per_page=100');
        tickets.value = response?.data?.map((item) => item?.data ?? item) ?? [];
        columns.value = response?.options?.statuses ?? [];
    } finally {
        loading.value = false;
    }
};

const openTicket = (ticket) => {
    if (!draggedId.value) router.push(`/support/tickets/${ticket.id}`);
};

const onDragStart = (ticket) => {
    if (!canUpdate) return;
    draggedId.value = ticket.id;
};

const onDragEnd = () => {
    draggedId.value = null;
    dragOver.value = null;
};

const onDrop = async (status) => {
    dragOver.value = null;

    const ticket = tickets.value.find((item) => item.id === draggedId.value);
    draggedId.value = null;

    if (!ticket || ticket.status === status) return;

    const previousStatus = ticket.status;
    ticket.status = status; // optimistic move
    busyId.value = ticket.id;

    try {
        const updated = await v1(`support-tickets/${ticket.id}`, {
            method: 'PATCH',
            body: {
                client_id: ticket.client_id,
                deployment_id: ticket.deployment_id || null,
                support_agreement_id: ticket.support_agreement_id || null,
                assigned_user_id: ticket.assigned_user_id || null,
                reference: ticket.reference,
                subject: ticket.subject,
                category: ticket.category || null,
                severity: ticket.severity,
                status,
                hours_logged: ticket.hours_logged ?? 0,
                opened_at: ticket.opened_at || null,
                resolved_at: ticket.resolved_at || null,
                summary: ticket.summary || null,
            },
        });

        Object.assign(ticket, updated?.data ?? updated);
        toast.success(`Moved to ${columns.value.find((c) => c.value === status)?.label ?? status}.`);
    } catch (error) {
        ticket.status = previousStatus; // revert
        toast.error(errorMessage(error, 'Could not move the ticket.'));
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
    grid-auto-columns: minmax(260px, 1fr);
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

.board-col--over {
    border-color: var(--rw-500);
    background: var(--rw-50);
}

.board-col__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.25rem 0.4rem 0.65rem;
}

.board-col__title {
    font-weight: 700;
    font-size: 0.9rem;
}

.board-col__count {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--rw-muted);
    background: var(--rw-surface);
    border: 1px solid var(--rw-border);
    border-radius: 999px;
    padding: 0.05rem 0.5rem;
}

.board-col__cards { display: grid; gap: 0.6rem; }

.board-col__empty {
    margin: 0;
    padding: 0.75rem 0.4rem;
    font-size: 0.82rem;
    color: var(--rw-muted);
}

.board-card {
    background: var(--rw-surface);
    border: 1px solid var(--rw-border);
    border-radius: 10px;
    padding: 0.75rem;
    display: grid;
    gap: 0.5rem;
    cursor: pointer;
    box-shadow: var(--rw-shadow-xs);
    transition: box-shadow 0.12s, transform 0.12s;
}

.board-card[draggable='true'] { cursor: grab; }
.board-card:hover { box-shadow: var(--rw-shadow-sm); }
.board-card--busy { opacity: 0.6; pointer-events: none; }

.board-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.board-card__ref {
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--rw-muted);
}

.board-card__subject {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.35;
}

.board-card__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.78rem;
    color: var(--rw-muted);
}

.board-card__owner {
    background: var(--rw-surface-2);
    border-radius: 999px;
    padding: 0.1rem 0.5rem;
}

@media (max-width: 720px) {
    .board-page { padding: 1.5rem 1rem 3rem; }
}
</style>
