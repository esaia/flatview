<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed } from 'vue'

const props = defineProps({
    settings: { type: Object, default: () => ({ kicker: 'Portfolio', headline: 'Selected Work', intro: '' }) },
    projects: { type: Array, default: () => [] },
})

// Rotating palette — applied by position so admin-managed projects still read as
// a curated set (gradient + accent) when no image is uploaded.
const palette = [
    { from: '#1c2b29', to: '#0b1413', accent: '#5DCAA5' },
    { from: '#26221a', to: '#10100c', accent: '#E0B872' },
    { from: '#1b232e', to: '#0b0f14', accent: '#6FA8E0' },
    { from: '#251c24', to: '#100b10', accent: '#C98CC0' },
    { from: '#1e2622', to: '#0c110e', accent: '#7FD8A0' },
    { from: '#262320', to: '#100f0d', accent: '#D8B48A' },
]

const styled = computed(() =>
    props.projects.map((p, i) => ({ ...p, ...palette[i % palette.length] }))
)

const pad = (n) => String(n).padStart(2, '0')
const monogram = (name) => (name || '').split(' ')[0]
const isExternal = (url) => /^https?:\/\//i.test(url || '')
</script>

<template>
    <AppLayout active-page="work">
        <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-14 md:pt-20 pb-20 md:pb-32">

            <!-- ── Header ─────────────────────────────────────────── -->
            <header class="md:flex md:items-end md:justify-between mb-12 md:mb-20">
                <div>
                    <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: 0s;">
                        <span class="dot"></span> {{ settings.kicker }}
                    </span>
                    <h1 class="reveal display text-black" style="--d: .08s;
                        font-size: clamp(38px, 6.4vw, 88px); font-weight: 300;
                        line-height: 1.04; letter-spacing: -0.02em;">
                        {{ settings.headline }}
                    </h1>
                </div>
                <p v-if="settings.intro"
                   class="reveal text-black/45 font-light text-[15px] md:text-base leading-relaxed max-w-xs mt-7 md:mt-0 md:text-right md:pb-3"
                   style="--d: .16s;">
                    {{ settings.intro }}
                </p>
            </header>

            <!-- ── Project grid ───────────────────────────────────── -->
            <div v-if="styled.length" class="grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-12 md:gap-y-16">
                <a
                    v-for="(project, i) in styled"
                    :key="project.name"
                    :href="project.link || '#'"
                    :target="isExternal(project.link) ? '_blank' : null"
                    :rel="isExternal(project.link) ? 'noopener noreferrer' : null"
                    class="reveal group block"
                    :style="{ '--d': `${0.24 + i * 0.06}s` }"
                >
                    <!-- Image -->
                    <div class="relative overflow-hidden rounded-sm aspect-[16/10]"
                         :style="{ background: `radial-gradient(120% 140% at 20% 0%, ${project.from}, ${project.to})` }">
                        <img v-if="project.image" :src="project.image" :alt="project.name"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-[1.04]" />
                        <template v-else>
                            <div class="grain absolute inset-0 opacity-[0.5]"></div>
                            <div class="absolute inset-0 transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-[1.04]">
                                <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 font-bold uppercase tracking-tighter select-none whitespace-nowrap"
                                      style="color: rgba(255,255,255,0.05); font-size: clamp(40px, 9vw, 72px);">
                                    {{ monogram(project.name) }}
                                </span>
                            </div>
                        </template>

                        <!-- index, top-left -->
                        <span class="absolute top-4 left-4 md:top-5 md:left-5 text-[11px] tracking-[0.25em] tabular-nums text-white/45">
                            {{ pad(i + 1) }}
                        </span>

                        <!-- hover veil + arrow -->
                        <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/30 transition-colors duration-500">
                            <span class="w-12 h-12 rounded-full bg-white text-black flex items-center justify-center text-lg opacity-0 scale-90 group-hover:opacity-100 group-hover:scale-100 transition-all duration-300">→</span>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="pt-4">
                        <p class="display text-black text-lg md:text-xl leading-snug transition-colors" style="font-weight: 400;">
                            {{ project.name }}
                        </p>
                        <p class="text-sm text-black/40 mt-1 font-light">{{ project.desc }}</p>
                    </div>
                </a>
            </div>

            <!-- ── Empty state ────────────────────────────────────── -->
            <div v-if="!styled.length" class="py-20 md:py-28 text-center">
                <p class="text-black/30 text-sm tracking-widest uppercase">No projects added yet</p>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Fine film grain — gives the dark placeholders depth instead of flat fill. */
.grain {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.55'/%3E%3C/svg%3E");
    mix-blend-mode: overlay;
    pointer-events: none;
}
</style>
