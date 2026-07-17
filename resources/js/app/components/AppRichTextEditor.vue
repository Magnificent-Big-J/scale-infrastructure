<template>
    <div class="app-rich-text" :class="{ 'app-rich-text--error': hasError }">
        <label v-if="label" class="app-rich-text__label">{{ label }}</label>

        <QuillEditor
            :content="modelValue || ''"
            content-type="html"
            theme="snow"
            :placeholder="placeholder"
            :toolbar="toolbar"
            class="app-rich-text__editor"
            @update:content="onUpdate"
        />

        <div class="app-rich-text__messages">
            <span v-if="hasError" class="app-rich-text__error">{{ errorText }}</span>
            <span v-else-if="hint" class="app-rich-text__hint">{{ hint }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { isRichTextEmpty, sanitizeRichText } from '../utils/sanitize';

const props = defineProps({
    modelValue: { type: [String, null], default: null },
    label: { type: String, default: null },
    placeholder: { type: String, default: null },
    hint: { type: String, default: null },
    errorMessages: { type: [String, Array], default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const toolbar = [
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote', 'link'],
    ['clean'],
];

const errorList = computed(() => (Array.isArray(props.errorMessages) ? props.errorMessages : [props.errorMessages]).filter(Boolean));
const hasError = computed(() => errorList.value.length > 0);
const errorText = computed(() => errorList.value[0]);

const onUpdate = (html) => {
    emit('update:modelValue', isRichTextEmpty(html) ? null : sanitizeRichText(html));
};
</script>

<style scoped>
.app-rich-text {
    display: grid;
    gap: 0.3rem;
}

.app-rich-text__label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--rw-muted);
}

.app-rich-text__editor {
    border-radius: 8px;
    overflow: hidden;
}

.app-rich-text__editor :deep(.ql-toolbar) {
    border-color: var(--rw-border);
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    background: var(--rw-surface-2);
}

.app-rich-text__editor :deep(.ql-container) {
    border-color: var(--rw-border);
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    min-height: 6rem;
}

.app-rich-text__editor :deep(.ql-editor) {
    min-height: 6rem;
    color: var(--rw-ink);
}

.app-rich-text__editor :deep(.ql-editor.ql-blank::before) {
    color: var(--rw-dim);
    font-style: normal;
}

.app-rich-text--error .app-rich-text__editor :deep(.ql-toolbar),
.app-rich-text--error .app-rich-text__editor :deep(.ql-container) {
    border-color: var(--rw-error, #dc2626);
}

.app-rich-text__messages {
    min-height: 1rem;
    font-size: 0.75rem;
    padding-left: 0.2rem;
}

.app-rich-text__error {
    color: #dc2626;
}

.app-rich-text__hint {
    color: var(--rw-muted);
}
</style>
