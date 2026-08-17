<script setup>
/**
 * "Live demos" — a kicker/headline rail above a grid of demo project cards,
 * each opening its own /projects/{slug} showcase page with the interactive
 * site plan and the full unit list.
 *
 * Shared by the services overview and the demo-projects content block on a
 * service page, so both stay one design.
 */
import { Link } from '@inertiajs/vue3'

defineProps({
    kicker: { type: String, default: '' },
    headline: { type: String, default: '' },
    // Rendered in the brand green after the headline, on the same line.
    headlineAccent: { type: String, default: '' },
    intro: { type: String, default: '' },
    projects: { type: Array, default: () => [] },
})
</script>

<template>
    <div v-if="projects.length">
        <div v-if="kicker || headline || intro" class="grid grid-cols-1 md:grid-cols-12 gap-y-6 md:gap-x-16">
            <div class="md:col-span-3">
                <p v-if="kicker" class="kicker flex items-center gap-2"><span class="dot"></span> {{ kicker }}</p>
            </div>

            <div class="md:col-span-9">
                <h2
                    v-if="headline || headlineAccent"
                    class="display text-black leading-[1.04] whitespace-pre-line"
                    style="font-size: clamp(30px, 4.4vw, 60px); font-weight: 300; letter-spacing: -0.02em;"
                >
                    {{ headline
                    }}<span v-if="headlineAccent" style="color: #5DCAA5;">{{ (headline ? ' ' : '') + headlineAccent }}</span>
                </h2>
                <p
                    v-if="intro"
                    class="text-sm md:text-base text-black/45 font-light leading-relaxed max-w-2xl mt-8 md:mt-12 whitespace-pre-line"
                >
                    {{ intro }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 border-t border-l border-black/10 mt-12 md:mt-20">
            <Link
                v-for="project in projects"
                :key="project.slug"
                :href="`/projects/${project.slug}`"
                class="group border-b border-r border-black/10 p-6 md:p-10 flex flex-col"
            >
                <div class="fc-stage relative overflow-hidden mb-8 md:mb-10" style="aspect-ratio: 16 / 11;">
                    <img
                        v-if="project.image"
                        :src="project.image"
                        :alt="project.title"
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-[1200ms] ease-out group-hover:scale-[1.04]"
                        draggable="false"
                    />
                    <span
                        v-if="project.unitCount"
                        class="absolute top-4 left-4 bg-white/90 text-black text-[10px] tracking-[0.2em] uppercase px-3 py-1.5"
                    >
                        {{ project.unitCount }} units
                    </span>
                </div>

                <h3 class="display text-xl md:text-2xl text-black mb-3 leading-snug" style="font-weight: 400;">{{ project.title }}</h3>
                <p v-if="project.tagline" class="text-sm text-black/45 font-light leading-relaxed mb-6">{{ project.tagline }}</p>

                <span class="mt-auto inline-flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-black/50 group-hover:text-black transition-colors duration-300">
                    Open demo
                    <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                </span>
            </Link>
        </div>
    </div>
</template>

<style scoped>
/* Warm stage panel behind each showcase image — a faint contour wash (a nod
   to site plans) so empty/transparent images still sit in the brand system. */
.fc-stage {
    background-color: #f4f1ec;
    background-image:
        radial-gradient(120% 90% at 85% 15%, rgba(93,202,165,0.06), transparent 60%),
        radial-gradient(100% 80% at 10% 95%, rgba(0,0,0,0.025), transparent 55%);
}
</style>
