<template>
    <v-app>
        <div class="auth-shell">
            <!-- ── Brand pane ────────────────────────────────── -->
            <aside class="auth-brand">
                <div class="auth-brand__inner">
                    <RouterLink to="/" class="auth-logo">
                        <span class="auth-logo__badge">SI</span>
                        <span class="auth-logo__name">Scale Infrastructure</span>
                    </RouterLink>

                    <div class="auth-brand__lead">
                        <h1 class="auth-brand__headline">See the state of every client environment.</h1>
                        <p class="auth-brand__sub">The single source of truth for delivery, support, and infrastructure.</p>

                        <ul class="auth-features">
                            <li v-for="feature in features" :key="feature.label" class="auth-features__item">
                                <v-icon :icon="feature.icon" size="18" class="auth-features__icon" />
                                <span>{{ feature.label }}</span>
                            </li>
                        </ul>
                    </div>

                    <footer class="auth-brand__footer">© {{ year }} Scale Infrastructure · Code Scale Tech</footer>
                </div>

                <div class="auth-brand__glow" />
            </aside>

            <!-- ── Form pane ─────────────────────────────────── -->
            <main class="auth-panel">
                <RouterLink to="/" class="auth-panel__logo">
                    <span class="auth-logo__badge">SI</span>
                    <span class="auth-logo__name auth-logo__name--dark">Scale Infrastructure</span>
                </RouterLink>

                <div class="auth-panel__stage">
                    <RouterView />
                </div>
            </main>
        </div>
    </v-app>
</template>

<script setup>
const year = new Date().getFullYear();

const features = [
    { icon: 'mdi-shield-check-outline', label: 'Secure session with optional two-factor' },
    { icon: 'mdi-pulse', label: 'Live environment and infrastructure health' },
    { icon: 'mdi-file-chart-outline', label: 'Reports generated from real operational data' },
    { icon: 'mdi-account-group-outline', label: 'Role-aware access for every team' },
];
</script>

<style scoped>
.auth-shell {
    min-height: 100vh;
    display: flex;
    align-items: stretch;
    background: var(--rw-surface);
}

/* ── Brand pane ────────────────────────────────────── */
.auth-brand {
    position: relative;
    flex: 0 0 42%;
    max-width: 40rem;
    overflow: hidden;
    background:
        radial-gradient(120% 120% at 0% 0%, #16234a 0%, transparent 55%),
        linear-gradient(160deg, #0a1326 0%, #0d1b3a 60%, #0a1326 100%);
    color: #fff;
}

.auth-brand__inner {
    position: relative;
    z-index: 1;
    height: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 3rem 3.25rem;
}

.auth-brand__lead {
    margin-top: auto;
    margin-bottom: auto;
    max-width: 28rem;
}

.auth-brand__headline {
    margin: 0;
    font-size: clamp(2rem, 3.4vw, 2.85rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.025em;
}

.auth-brand__sub {
    margin: 1.1rem 0 0;
    font-size: 1rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.62);
}

.auth-features {
    list-style: none;
    margin: 2.25rem 0 0;
    padding: 0;
    display: grid;
    gap: 0.95rem;
}

.auth-features__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.82);
}

.auth-features__icon {
    color: var(--rw-500);
    flex-shrink: 0;
}

.auth-brand__footer {
    font-size: 0.75rem;
    letter-spacing: 0.03em;
    color: rgba(255, 255, 255, 0.38);
}

/* Soft signal glow, bottom-left, echoing the reference */
.auth-brand__glow {
    position: absolute;
    z-index: 0;
    left: -18%;
    bottom: -28%;
    width: 36rem;
    height: 36rem;
    border-radius: 50%;
    border: 1px solid rgba(37, 99, 235, 0.16);
    background: radial-gradient(circle, rgba(37, 99, 235, 0.12), transparent 68%);
}

/* ── Logo ──────────────────────────────────────────── */
.auth-logo,
.auth-panel__logo {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    text-decoration: none;
}

.auth-logo__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 9px;
    background: linear-gradient(145deg, #1c3a6e, #3b82f6);
    color: #fff;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.08), 0 2px 10px rgba(37, 99, 235, 0.45);
}

.auth-logo__name {
    font-size: 1.02rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.92);
    letter-spacing: -0.01em;
}

.auth-logo__name--dark {
    color: var(--rw-ink);
}

/* ── Form pane ─────────────────────────────────────── */
.auth-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem clamp(1.5rem, 6vw, 6rem);
    background: var(--rw-surface);
}

.auth-panel__logo {
    display: none;
    margin-bottom: 2.5rem;
}

.auth-panel__stage {
    width: 100%;
    max-width: 25rem;
    margin: 0 auto;
}

/* ── Responsive: collapse to single column ─────────── */
@media (max-width: 900px) {
    .auth-brand {
        display: none;
    }

    .auth-panel {
        justify-content: flex-start;
        padding: 2.5rem 1.5rem;
    }

    .auth-panel__logo {
        display: inline-flex;
    }
}
</style>
