<template>
    <AuthCard
        eyebrow="Session access"
        title="Sign in"
        subtitle="Use your account credentials to start a secure session."
        :busy="session.loading"
    >
        <FormStatusAlert :message="formMessage" :type="formMessageType" />

        <v-form class="auth-form" @submit.prevent="submit">
            <AppTextField
                v-model="form.email"
                label="Email"
                type="email"
                autocomplete="email"
                required
            />

            <AppTextField
                v-model="form.password"
                label="Password"
                type="password"
                password-toggle
                autocomplete="current-password"
                required
            />

            <FormActions
                submit-label="Sign in"
                :loading="session.loading"
                left-link-label="Forgot password?"
                left-link-to="/auth/forgot-password"
            />
        </v-form>

        <p class="auth-help">
            Need access? Ask an administrator to create your account. Public registration is disabled.
        </p>
    </AuthCard>
</template>

<route lang="json">
{
    "meta": {
        "layout": "auth",
        "title": "Sign in",
        "guestOnly": true
    }
}
</route>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import AppTextField from '../../components/AppTextField.vue';
import { useSessionStore } from '../../stores/session';

const router = useRouter();
const session = useSessionStore();

const form = reactive({
    email: '',
    password: '',
});

const formMessage = ref('');
const formMessageType = ref('error');

const submit = async () => {
    formMessage.value = '';
    formMessageType.value = 'error';

    try {
        const response = await session.login(form);

        if (response?.status === '2fa_required') {
            await router.push('/auth/verify');

            return;
        }

        await router.push(session.homeRoute);
    } catch (error) {
        formMessage.value = error?.data?.message || 'Unable to sign in with those credentials.';
    }
};
</script>

<style scoped>
.auth-form {
    display: grid;
    gap: 1rem;
}

.auth-help {
    margin: 1.75rem 0 0;
    font-size: 0.82rem;
    line-height: 1.55;
    color: var(--rw-muted);
}
</style>
