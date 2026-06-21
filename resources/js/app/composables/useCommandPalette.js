import { ref } from 'vue';

// Module-level singleton so the topbar trigger and the palette share one state.
const isOpen = ref(false);

export function useCommandPalette() {
    return {
        isOpen,
        open: () => { isOpen.value = true; },
        close: () => { isOpen.value = false; },
        toggle: () => { isOpen.value = !isOpen.value; },
    };
}
