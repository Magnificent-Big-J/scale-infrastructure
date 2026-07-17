<template>
    <span v-if="isRichTextEmpty(content)" class="app-rich-text-display app-rich-text-display--empty">{{ emptyText }}</span>
    <div v-else class="app-rich-text-display" v-html="sanitizeRichText(content)" />
</template>

<script setup>
import { isRichTextEmpty, sanitizeRichText } from '../utils/sanitize';

defineProps({
    content: { type: [String, null], default: null },
    emptyText: { type: String, default: '-' },
});
</script>

<style scoped>
.app-rich-text-display :deep(p) {
    margin: 0 0 0.5rem;
}

.app-rich-text-display :deep(p:last-child) {
    margin-bottom: 0;
}

.app-rich-text-display :deep(ul),
.app-rich-text-display :deep(ol) {
    margin: 0 0 0.5rem;
    padding-left: 1.4rem;
}

.app-rich-text-display :deep(blockquote) {
    margin: 0 0 0.5rem;
    padding-left: 0.75rem;
    border-left: 3px solid var(--rw-border-strong);
    color: var(--rw-muted);
}

.app-rich-text-display :deep(a) {
    color: var(--rw-600);
    text-decoration: underline;
}
</style>
