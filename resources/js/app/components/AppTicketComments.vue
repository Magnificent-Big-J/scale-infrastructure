<template>
    <div class="comments">
        <div v-if="canComment" class="comments__composer">
            <AppTextarea v-model="body" label="Add a comment" rows="3" :error-messages="error" />
            <div class="comments__composer-actions">
                <v-btn color="primary" size="small" :loading="posting" :disabled="!body.trim()" @click="submit">Post comment</v-btn>
            </div>
        </div>

        <div v-if="loading && !comments.length" class="comments__state">
            <v-progress-circular indeterminate size="20" width="2" color="primary" />
            <span>Loading comments…</span>
        </div>

        <div v-else-if="!comments.length" class="comments__state">
            <v-icon icon="mdi-comment-outline" size="22" />
            <span>No comments yet. Start the discussion.</span>
        </div>

        <ul v-else class="comments__list">
            <li v-for="comment in comments" :key="comment.id" class="comment">
                <span class="comment__avatar">{{ comment.author_initials || '–' }}</span>
                <div class="comment__body">
                    <div class="comment__meta">
                        <strong>{{ comment.author_name || 'System' }}</strong>
                        <span>{{ relativeTime(comment.created_at) }}</span>
                    </div>
                    <p class="comment__text">{{ comment.body }}</p>
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';

import AppTextarea from './AppTextarea.vue';
import { useToast, errorMessage } from '../composables/useToast';
import { v1 } from '../utils/api';

const props = defineProps({
    ticketId: { type: [String, Number], required: true },
    canComment: { type: Boolean, default: false },
});

const toast = useToast();
const comments = ref([]);
const loading = ref(false);
const posting = ref(false);
const body = ref('');
const error = ref('');

const relativeTime = (value) => {
    if (!value) return '';

    const mins = Math.round((Date.now() - new Date(value).getTime()) / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;

    const hours = Math.round(mins / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.round(hours / 24);
    return days < 30 ? `${days}d ago` : new Date(value).toLocaleDateString();
};

const load = async () => {
    loading.value = true;

    try {
        const response = await v1(`support-tickets/${props.ticketId}/comments`);
        comments.value = response?.data?.map((item) => item?.data ?? item) ?? [];
    } finally {
        loading.value = false;
    }
};

const submit = async () => {
    error.value = '';
    posting.value = true;

    try {
        const response = await v1(`support-tickets/${props.ticketId}/comments`, { method: 'POST', body: { body: body.value } });
        comments.value.push(response?.data ?? response);
        body.value = '';
        toast.success('Comment added.');
    } catch (e) {
        error.value = e?.data?.errors?.body?.[0] || '';
        toast.error(errorMessage(e, 'Could not post the comment.'));
    } finally {
        posting.value = false;
    }
};

onMounted(load);
</script>

<style scoped>
.comments {
    display: grid;
    gap: 1.5rem;
}

.comments__composer {
    display: grid;
    gap: 0.6rem;
}

.comments__composer-actions {
    display: flex;
    justify-content: flex-end;
}

.comments__state {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1rem 0;
    font-size: 0.88rem;
    color: var(--rw-muted);
}

.comments__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 1.25rem;
}

.comment {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.85rem;
    align-items: flex-start;
}

.comment__avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 10px;
    background: var(--rw-700);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 700;
    flex-shrink: 0;
}

.comment__meta {
    display: flex;
    align-items: baseline;
    gap: 0.6rem;
    font-size: 0.88rem;
    color: var(--rw-ink-2);
}

.comment__meta span {
    color: var(--rw-muted);
    font-size: 0.78rem;
}

.comment__text {
    margin: 0.25rem 0 0;
    line-height: 1.55;
    color: var(--rw-ink);
    white-space: pre-wrap;
}
</style>
