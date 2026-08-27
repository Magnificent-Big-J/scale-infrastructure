import { defineStore } from 'pinia';

export const useStepUpStore = defineStore('stepUp', {
    state: () => ({
        open: false,
    }),
    actions: {
        require() {
            this.open = true;
        },
        close() {
            this.open = false;
        },
    },
});
