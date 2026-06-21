<template>
    <v-dialog v-model="isOpen" max-width="600" transition="fade-transition">
        <div class="cmdk">
            <div class="cmdk__search">
                <v-icon icon="mdi-magnify" size="20" class="cmdk__search-icon" />
                <input
                    ref="inputEl"
                    v-model="query"
                    type="text"
                    class="cmdk__input"
                    placeholder="Search pages and actions…"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="runHighlighted"
                    @keydown.esc.prevent="isOpen = false"
                >
                <kbd class="cmdk__hint">esc</kbd>
            </div>

            <div ref="listEl" class="cmdk__list">
                <template v-for="(group, groupName) in grouped" :key="groupName">
                    <div class="cmdk__group">{{ groupName }}</div>
                    <button
                        v-for="command in group"
                        :key="command.id"
                        type="button"
                        class="cmdk__item"
                        :class="{ 'cmdk__item--active': command.index === highlighted }"
                        @click="runCommand(command)"
                        @mousemove="highlighted = command.index"
                    >
                        <v-icon :icon="command.icon" size="18" class="cmdk__item-icon" />
                        <span class="cmdk__item-label">{{ command.label }}</span>
                    </button>
                </template>

                <div v-if="!filtered.length" class="cmdk__empty">No matching commands.</div>
            </div>
        </div>
    </v-dialog>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

import { useCommandPalette } from '../composables/useCommandPalette';
import { navGroups } from '../config/navigation';
import { useSessionStore } from '../stores/session';

const router = useRouter();
const session = useSessionStore();
const { isOpen } = useCommandPalette();

const query = ref('');
const highlighted = ref(0);
const inputEl = ref(null);
const listEl = ref(null);

const can = (permission) => !permission || (session.user?.permissions?.includes(permission) ?? false);

const signOut = async () => {
    await session.logout();
    router.push('/auth/login');
};

// Flat command list: permission-filtered navigation + global actions.
const commands = computed(() => {
    const list = [];

    navGroups.forEach((group) => {
        group.items.forEach((item) => {
            if (can(item.permission)) {
                list.push({ id: item.to, label: item.label, group: group.label || 'General', icon: item.icon, action: () => router.push(item.to) });
            }
        });
    });

    list.push({ id: 'action:signout', label: 'Sign out', group: 'Actions', icon: 'mdi-logout', action: signOut });

    return list;
});

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();
    const matches = term
        ? commands.value.filter((command) => `${command.label} ${command.group}`.toLowerCase().includes(term))
        : commands.value;

    return matches.map((command, index) => ({ ...command, index }));
});

const grouped = computed(() => {
    const groups = {};

    filtered.value.forEach((command) => {
        (groups[command.group] ??= []).push(command);
    });

    return groups;
});

const move = (delta) => {
    const count = filtered.value.length;
    if (!count) return;

    highlighted.value = (highlighted.value + delta + count) % count;
    scrollHighlightedIntoView();
};

const runCommand = (command) => {
    isOpen.value = false;
    command.action();
};

const runHighlighted = () => {
    const command = filtered.value[highlighted.value];
    if (command) runCommand(command);
};

const scrollHighlightedIntoView = () => {
    nextTick(() => {
        listEl.value?.querySelector('.cmdk__item--active')?.scrollIntoView({ block: 'nearest' });
    });
};

// Reset highlight whenever the result set changes.
watch(filtered, () => { highlighted.value = 0; });

watch(isOpen, (value) => {
    if (value) {
        query.value = '';
        highlighted.value = 0;
        nextTick(() => inputEl.value?.focus());
    }
});

const onKeydown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        isOpen.value = !isOpen.value;
    }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<style scoped>
.cmdk {
    background: var(--rw-surface);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--rw-shadow-xl);
}

.cmdk__search {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.9rem 1rem;
    border-bottom: 1px solid var(--rw-border);
}

.cmdk__search-icon {
    color: var(--rw-muted);
}

.cmdk__input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 0.95rem;
    color: var(--rw-ink);
}

.cmdk__hint {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--rw-muted);
    background: var(--rw-surface-2);
    border: 1px solid var(--rw-border);
    border-radius: 5px;
    padding: 0.1rem 0.4rem;
    text-transform: uppercase;
}

.cmdk__list {
    max-height: 56vh;
    overflow-y: auto;
    padding: 0.4rem;
}

.cmdk__group {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rw-muted);
    padding: 0.6rem 0.6rem 0.3rem;
}

.cmdk__item {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    padding: 0.55rem 0.6rem;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: var(--rw-ink);
    font-size: 0.9rem;
    text-align: left;
    cursor: pointer;
}

.cmdk__item--active {
    background: var(--rw-50);
    color: var(--rw-700);
}

.cmdk__item-icon {
    color: var(--rw-muted);
    flex-shrink: 0;
}

.cmdk__item--active .cmdk__item-icon {
    color: var(--rw-600);
}

.cmdk__empty {
    padding: 1.5rem 0.6rem;
    text-align: center;
    color: var(--rw-muted);
    font-size: 0.88rem;
}
</style>
