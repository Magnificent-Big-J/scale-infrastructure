import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useSessionStore } from './session';

describe('session store surface getters', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('is unauthenticated and a guest with no user loaded', () => {
        const store = useSessionStore();

        expect(store.isAuthenticated).toBe(false);
        expect(store.activeSurface).toBe('guest');
    });

    it('treats an administrator as the admin surface', () => {
        const store = useSessionStore();
        store.user = { roles: ['administrator'] };

        expect(store.isAuthenticated).toBe(true);
        expect(store.isAdminSurface).toBe(true);
        expect(store.activeSurface).toBe('admin');
    });

    it('treats every staff role as the admin surface', () => {
        const store = useSessionStore();

        for (const role of ['executive', 'operations', 'finance', 'sales', 'support', 'technical']) {
            store.user = { roles: [role] };
            expect(store.isAdminSurface).toBe(true);
        }
    });

    it('treats a user with no staff role as the customer surface', () => {
        const store = useSessionStore();
        store.user = { roles: ['client'] };

        expect(store.isAdminSurface).toBe(false);
        expect(store.activeSurface).toBe('customer');
    });

    it('treats a user with no roles at all as the customer surface', () => {
        const store = useSessionStore();
        store.user = {};

        expect(store.isAdminSurface).toBe(false);
        expect(store.activeSurface).toBe('customer');
    });
});
