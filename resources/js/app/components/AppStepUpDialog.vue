<template>
    <AppModal :model-value="stepUp.open" title="Confirm your password" subtitle="This action needs a recent password confirmation before it can continue." persistent max-width="440" @update:model-value="onClose">
        <v-form @submit.prevent="confirm">
            <AppTextField v-model="password" label="Password" type="password" autofocus :error-messages="error ? [error] : []" />
        </v-form>
        <template #actions>
            <v-spacer />
            <v-btn variant="text" @click="onClose">Cancel</v-btn>
            <v-btn color="primary" :loading="loading" @click="confirm">Confirm</v-btn>
        </template>
    </AppModal>
</template>

<script setup>
import { ref } from 'vue';
import AppModal from './AppModal.vue';
import AppTextField from './AppTextField.vue';
import { useToast } from '../composables/useToast';
import { csrfCookie, getXsrfToken, SESSION_BASE } from '../stores/auth-shared';
import { useStepUpStore } from '../stores/step-up';
import { api } from '../utils/api';

const stepUp = useStepUpStore();
const toast = useToast();
const password = ref('');
const error = ref('');
const loading = ref(false);

const reset = () => {
    password.value = '';
    error.value = '';
    loading.value = false;
};

const onClose = () => {
    reset();
    stepUp.close();
};

const confirm = async () => {
    if (!password.value) {
        error.value = 'Enter your password.';

        return;
    }

    loading.value = true;
    error.value = '';

    try {
        await csrfCookie();

        await api(`${SESSION_BASE}/password/confirm`, {
            method: 'POST',
            body: { password: password.value },
            headers: { 'X-XSRF-TOKEN': getXsrfToken() },
        });

        toast.success('Password confirmed. Please retry your last action.');
        reset();
        stepUp.close();
    } catch (err) {
        error.value = err?.data?.message || 'Incorrect password.';
    } finally {
        loading.value = false;
    }
};
</script>
