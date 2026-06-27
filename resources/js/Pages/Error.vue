<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    status: { type: [Number, String], default: 404 },
})

// Copy per status. The page is tuned for 404 — the building metaphor only
// reads true there — but other codes degrade gracefully to a plain message.
const COPY = {
    404: {
        kicker: '404 — Not found',
        title: 'Nothing found here.',
        body: 'The page you were after has moved, been taken down, or never existed. Pick a direction below and we’ll get you back on track.',
    },
    403: {
        kicker: '403 — Restricted',
        title: 'This floor is private.',
        body: 'You don’t have access to this page. If you think that’s a mistake, get in touch and we’ll sort it out.',
    },
    500: {
        kicker: '500 — Something broke',
        title: 'We hit a wall.',
        body: 'Something failed on our end, not yours. Try again in a moment, or head back home while we patch it up.',
    },
}

const copy = computed(() => COPY[Number(props.status)] ?? COPY[404])

// The unit the visitor "looked for" — read live so it mirrors the real URL,
// the way the product looks up a flat by its number.
const attemptedPath = ref('/')
onMounted(() => {
    attemptedPath.value = window.location.pathname + window.location.search
})

// Floor directory: unit 404 is the vacant slot. Numbers read as building
// units (floor 4, unit 0X), so HTTP 404 literally becomes apartment 404.
const units = [
    { no: '401', name: 'Corner studio', state: 'taken' },
    { no: '402', name: 'One bedroom', state: 'taken' },
    { no: '403', name: 'Two bedroom', state: 'taken' },
    { no: '404', name: 'Not found', state: 'vacant' },
    { no: '405', name: 'Garden flat', state: 'taken' },
]
</script>

<template>
    <Head :title="`${status} — ${copy.title}`" />

    <AppLayout active-page="error">
        <main class="nf">
            <div class="nf__grid">
                <!-- Editorial column -->
                <section class="nf__lede">
                    <p class="kicker reveal" style="--d: 0.05s">
                        <span class="dot" /> {{ copy.kicker }}
                    </p>

                    <h1 class="display nf__title reveal" style="--d: 0.12s">
                        {{ copy.title }}
                    </h1>

                    <p class="nf__body reveal" style="--d: 0.2s">
                        {{ copy.body }}
                    </p>

                    <div class="nf__looked reveal" style="--d: 0.26s" aria-label="Address you tried to reach">
                        <span class="nf__looked-label">You looked for</span>
                        <code class="nf__looked-path">{{ attemptedPath }}</code>
                    </div>

                    <div class="nf__actions reveal" style="--d: 0.32s">
                        <Link href="/" class="nf__btn nf__btn--solid">Back to home</Link>
                        <Link href="/work" class="nf__btn nf__btn--ghost">
                            Explore our work
                            <span class="nf__btn-arrow" aria-hidden="true">→</span>
                        </Link>
                    </div>
                </section>

                <!-- Signature: floor directory with unit 404 vacant -->
                <section class="nf__board reveal" style="--d: 0.18s" aria-hidden="true">
                    <div class="board">
                        <header class="board__head">
                            <span class="board__floor display">04</span>
                            <span class="board__head-meta">
                                <span class="board__head-title">Floor directory</span>
                                <span class="board__head-sub">5 units · 1 vacant</span>
                            </span>
                        </header>

                        <ul class="board__list">
                            <li
                                v-for="u in units"
                                :key="u.no"
                                class="unit"
                                :class="{ 'unit--vacant': u.state === 'vacant' }"
                            >
                                <span class="unit__no display">{{ u.no }}</span>
                                <span class="unit__name">{{ u.name }}</span>
                                <span class="unit__state">
                                    <span class="unit__chip">
                                        {{ u.state === 'vacant' ? 'Vacant' : 'Occupied' }}
                                    </span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </main>
    </AppLayout>
</template>

<style scoped>
.nf {
    --ink: #1e293b;
    --muted: #8a8a82;
    --line: #e8e7e2;
    --paper: #faf9f7;
    --green: #5dcaa5;
    --green-ink: #2f9e7b;

    min-height: 100vh;
    display: flex;
    align-items: center;
    color: var(--ink);
    padding: 7rem 1.5rem 5rem;
}

.nf__grid {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    align-items: center;
    gap: clamp(2.5rem, 6vw, 6rem);
}

/* ── Editorial column ───────────────────────────── */
.nf__lede .kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
}

.nf__title {
    margin: 1.4rem 0 0;
    font-weight: 400;
    font-size: clamp(2.6rem, 6vw, 4.6rem);
    line-height: 0.98;
    letter-spacing: -0.02em;
}

.nf__body {
    margin: 1.5rem 0 0;
    max-width: 30rem;
    font-size: 1.0625rem;
    line-height: 1.7;
    color: #55565a;
}

.nf__looked {
    margin-top: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.nf__looked-label {
    font-size: 11px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--muted);
}

.nf__looked-path {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 0.95rem;
    color: var(--ink);
    padding: 0.3rem 0.7rem;
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 0;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nf__actions {
    margin-top: 2.4rem;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    flex-wrap: wrap;
}

.nf__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 0.01em;
    padding: 0.85rem 1.5rem;
    text-decoration: none;
    transition: background 0.25s ease, color 0.25s ease, border-color 0.25s ease, transform 0.25s ease;
}

.nf__btn--solid {
    background: var(--ink);
    color: #fff;
}
.nf__btn--solid:hover {
    background: #0f172a;
    transform: translateY(-2px);
}

.nf__btn--ghost {
    color: var(--ink);
    border: 1px solid var(--line);
    background: transparent;
}
.nf__btn--ghost:hover {
    border-color: var(--ink);
}
.nf__btn-arrow {
    transition: transform 0.25s ease;
}
.nf__btn--ghost:hover .nf__btn-arrow {
    transform: translateX(4px);
}

.nf__btn:focus-visible {
    outline: 2px solid var(--green-ink);
    outline-offset: 3px;
}

/* ── Signature: floor directory board ───────────── */
.board {
    position: relative;
    background: #fff;
    border: 1px solid var(--line);
    padding: clamp(1.4rem, 3vw, 2.2rem);
    box-shadow: 0 30px 60px -45px rgba(30, 41, 59, 0.45);
}

/* Building core: a hairline shaft running down the card's left edge,
   echoing a stair/lift core on an elevation drawing. */
.board::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: clamp(1.4rem, 3vw, 2.2rem);
    width: 1px;
    background: repeating-linear-gradient(
        to bottom,
        var(--line) 0,
        var(--line) 6px,
        transparent 6px,
        transparent 12px
    );
}

.board__head {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0 0 1.3rem 1.6rem;
    border-bottom: 1px solid var(--line);
}

.board__floor {
    font-size: 2.6rem;
    line-height: 1;
    font-weight: 400;
    letter-spacing: -0.02em;
}

.board__head-meta {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.board__head-title {
    font-size: 0.9rem;
    font-weight: 500;
}
.board__head-sub {
    font-size: 0.78rem;
    color: var(--muted);
}

.board__list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.unit {
    display: grid;
    grid-template-columns: 3.4rem 1fr auto;
    align-items: center;
    gap: 1rem;
    padding: 0.95rem 0 0.95rem 1.6rem;
    border-bottom: 1px solid var(--line);
}
.unit:last-child {
    border-bottom: 0;
}

.unit__no {
    font-size: 1.25rem;
    font-weight: 400;
    color: var(--ink);
}
.unit__name {
    font-size: 0.92rem;
    color: #55565a;
}
.unit__state {
    justify-self: end;
}
.unit__chip {
    font-size: 0.7rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0.28rem 0.6rem;
    border: 1px solid var(--line);
}

/* The missing unit — the page's one moment of emphasis. */
.unit--vacant {
    position: relative;
    margin-left: 1.6rem;
    padding-left: 1rem;
    border: 1px dashed var(--green);
    border-radius: 2px;
    background: linear-gradient(0deg, rgba(93, 202, 165, 0.07), rgba(93, 202, 165, 0.07));
    animation: vacant-pulse 2.8s ease-in-out infinite;
}
.unit--vacant + .unit {
    border-top: 0;
}
.unit--vacant .unit__no {
    color: var(--green-ink);
}
.unit--vacant .unit__name {
    color: var(--green-ink);
    font-style: italic;
}
.unit--vacant .unit__chip {
    color: #fff;
    background: var(--green-ink);
    border-color: var(--green-ink);
}

@keyframes vacant-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(93, 202, 165, 0.0); }
    50% { box-shadow: 0 0 0 5px rgba(93, 202, 165, 0.12); }
}

/* ── Responsive ─────────────────────────────────── */
@media (max-width: 860px) {
    .nf {
        padding: 6rem 1.25rem 4rem;
    }
    .nf__grid {
        grid-template-columns: 1fr;
        gap: 3rem;
    }
    .nf__board {
        order: 2;
    }
}

@media (prefers-reduced-motion: reduce) {
    .unit--vacant {
        animation: none;
    }
    .nf__btn--solid:hover,
    .nf__btn--ghost:hover .nf__btn-arrow {
        transform: none;
    }
}
</style>
