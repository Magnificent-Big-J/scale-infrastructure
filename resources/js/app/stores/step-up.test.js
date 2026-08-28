import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useStepUpStore } from './step-up';

describe('step-up store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('starts closed', () => {
        expect(useStepUpStore().open).toBe(false);
    });

    it('opens on require() and closes on close()', () => {
        const store = useStepUpStore();

        store.require();
        expect(store.open).toBe(true);

        store.close();
        expect(store.open).toBe(false);
    });
});
