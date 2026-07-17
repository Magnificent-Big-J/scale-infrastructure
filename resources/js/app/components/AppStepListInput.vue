<template>
    <div class="app-step-list">
        <label v-if="label" class="app-step-list__label">{{ label }}</label>

        <div v-if="!steps.length" class="app-step-list__empty">No steps yet.</div>

        <div v-for="(step, index) in steps" :key="index" class="app-step-list__row">
            <span class="app-step-list__index">{{ index + 1 }}</span>
            <AppTextField
                :model-value="step"
                :placeholder="`Step ${index + 1}`"
                hide-details
                @update:model-value="(value) => updateStep(index, value)"
            />
            <div class="app-step-list__row-actions">
                <v-btn icon="mdi-arrow-up" size="small" variant="text" :disabled="index === 0" title="Move up" @click="move(index, -1)" />
                <v-btn icon="mdi-arrow-down" size="small" variant="text" :disabled="index === steps.length - 1" title="Move down" @click="move(index, 1)" />
                <v-btn icon="mdi-close" size="small" variant="text" title="Remove step" @click="remove(index)" />
            </div>
        </div>

        <v-btn variant="tonal" color="primary" prepend-icon="mdi-plus" size="small" class="app-step-list__add" @click="add">
            Add step
        </v-btn>

        <span v-if="hasError" class="app-step-list__error">{{ errorText }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import AppTextField from './AppTextField.vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    label: { type: String, default: null },
    errorMessages: { type: [String, Array], default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const steps = computed(() => props.modelValue || []);

const errorList = computed(() => (Array.isArray(props.errorMessages) ? props.errorMessages : [props.errorMessages]).filter(Boolean));
const hasError = computed(() => errorList.value.length > 0);
const errorText = computed(() => errorList.value[0]);

const emitSteps = (next) => emit('update:modelValue', next);

const updateStep = (index, value) => {
    const next = [...steps.value];
    next[index] = value;
    emitSteps(next);
};

const move = (index, delta) => {
    const target = index + delta;
    if (target < 0 || target >= steps.value.length) return;

    const next = [...steps.value];
    [next[index], next[target]] = [next[target], next[index]];
    emitSteps(next);
};

const remove = (index) => {
    const next = steps.value.filter((_, i) => i !== index);
    emitSteps(next);
};

const add = () => emitSteps([...steps.value, '']);
</script>

<style scoped>
.app-step-list {
    display: grid;
    gap: 0.5rem;
}

.app-step-list__label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--rw-muted);
}

.app-step-list__empty {
    font-size: 0.85rem;
    color: var(--rw-muted);
    padding: 0.5rem 0;
}

.app-step-list__row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.app-step-list__index {
    flex-shrink: 0;
    width: 1.5rem;
    text-align: center;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--rw-muted);
}

.app-step-list__row-actions {
    flex-shrink: 0;
    display: flex;
    align-items: center;
}

.app-step-list__add {
    justify-self: start;
}

.app-step-list__error {
    color: #dc2626;
    font-size: 0.75rem;
    padding-left: 0.2rem;
}
</style>
