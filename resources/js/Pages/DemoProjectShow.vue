<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import IrepProvider from '@/irep/shortcodes/IrepProvider.vue'
import ProjectViewer from '@/irep/shortcodes/ProjectViewer.vue'
import FlatsList from '@/irep/shortcodes/FlatsList.vue'

const props = defineProps({
    project: { type: Object, required: true },
    projectData: { type: Object, default: null },
    cta: { type: Object, default: () => ({}) },
})

// Projects without their own hero image fall back to a shipped render, so the
// page never opens on a flat black band.
const heroImage = computed(() => props.project.heroImage || '/images/demo-project-hero.webp')

// The 360 viewer sizes itself against its container, so it needs a stage with a
// definite height; the polygon viewer sizes itself from its own image instead.
const has360 = computed(() => Boolean(props.projectData?.project?.['360images']?.length))

const planSection = ref(null)
const scrollToPlan = () => planSection.value?.scrollIntoView({ behavior: 'smooth' })
</script>

<template>
    <AppLayout>
        <div class="relative overflow-x-hidden">

            <!-- Brand logo, top-right -->
            <Link href="/" class="absolute top-7 right-8 md:top-10 md:right-16 z-30 flex items-center gap-2 md:gap-2.5 select-none cursor-pointer">
                <!-- The mark is black artwork; force it white to match the
                     wordmark against the dark hero. -->
                <img src="/logo.svg" alt="FlatView" class="h-5 md:h-6 w-auto brightness-0 invert" draggable="false" />
                <span class="text-white text-sm md:text-base font-semibold tracking-[0.2em] uppercase">FlatView</span>
            </Link>

            <!-- ── Hero ───────────────────────────────────────────────────── -->
            <section class="relative bg-[#0e0e0e] text-white overflow-hidden">
                <img
                    :src="heroImage"
                    :alt="project.title"
                    class="absolute inset-0 w-full h-full object-cover opacity-70"
                    draggable="false"
                />
                <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/30 to-black/70"></div>

                <div class="relative max-w-[1400px] mx-auto px-6 md:px-16 pt-32 md:pt-48 pb-14 md:pb-20">
                    <h1 class="reveal display leading-[1.02]" style="--d: 0s; font-size: clamp(40px, 8vw, 120px); font-weight: 300; letter-spacing: -0.02em;">
                        {{ project.title }}
                    </h1>

                    <div class="h-px bg-white/25 mt-12 md:mt-20"></div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-y-8 md:gap-x-16 pt-8 md:pt-10">
                        <div class="md:col-span-5 flex items-center gap-6">
                            <button
                                type="button"
                                aria-label="Scroll to the interactive site plan"
                                class="h-12 w-12 shrink-0 border border-white/25 flex items-center justify-center text-white/70 hover:text-white hover:border-white/60 transition-colors duration-300"
                                @click="scrollToPlan"
                            >
                                ↓
                            </button>

                            <Link
                                href="/services"
                                class="group inline-flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-white/50 hover:text-white transition-colors duration-300"
                            >
                                <span class="inline-block transition-transform duration-300 group-hover:-translate-x-1">←</span>
                                Back to services
                            </Link>
                        </div>

                        <div class="md:col-span-7">
                            <p v-if="project.heroDescription" class="text-sm md:text-base text-white/70 font-light leading-relaxed max-w-xl whitespace-pre-line">
                                {{ project.heroDescription }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <IrepProvider v-if="projectData" :data="projectData">

                <!-- ── Interactive site plan ──────────────────────────────── -->
                <section ref="planSection" class="max-w-[1400px] mx-auto px-6 md:px-16 pt-20 md:pt-32">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-y-6 md:gap-x-16">
                        <div class="md:col-span-3">
                            <p class="kicker flex items-center gap-2"><span class="dot"></span> {{ project.planKicker }}</p>
                        </div>

                        <div class="md:col-span-9">
                            <h2 class="display text-black leading-[1.04]"
                                style="font-size: clamp(30px, 4.4vw, 60px); font-weight: 300; letter-spacing: -0.02em;">
                                {{ project.planHeadline
                                }}<span v-if="project.planHeadlineAccent" style="color: #5DCAA5;">{{ ' ' + project.planHeadlineAccent }}</span>
                            </h2>
                            <p v-if="project.planIntro" class="text-sm md:text-base text-black/45 font-light leading-relaxed max-w-2xl mt-8 md:mt-12 whitespace-pre-line">
                                {{ project.planIntro }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="max-w-[1600px] mx-auto px-0 md:px-16 mt-12 md:mt-20">
                    <div
                        class="viewer-stage overflow-hidden"
                        :class="has360 ? 'viewer-stage--fixed' : ''"
                    >
                        <ProjectViewer />
                    </div>
                </div>

                <!-- ── Unit list ──────────────────────────────────────────── -->
                <section class="max-w-[1400px] mx-auto px-6 md:px-16 pt-24 md:pt-40 pb-20 md:pb-32">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-y-6 md:gap-x-16 mb-12 md:mb-20">
                        <div class="md:col-span-3">
                            <p class="kicker flex items-center gap-2"><span class="dot"></span> {{ project.unitsKicker }}</p>
                        </div>

                        <div class="md:col-span-9">
                            <h2 class="display text-black leading-[1.04]"
                                style="font-size: clamp(30px, 4.4vw, 60px); font-weight: 300; letter-spacing: -0.02em;">
                                {{ project.unitsHeadline
                                }}<span v-if="project.unitsHeadlineAccent" style="color: #5DCAA5;">{{ ' ' + project.unitsHeadlineAccent }}</span>
                            </h2>
                            <p v-if="project.unitsIntro" class="text-sm md:text-base text-black/45 font-light leading-relaxed max-w-2xl mt-8 md:mt-12 whitespace-pre-line">
                                {{ project.unitsIntro }}
                            </p>
                        </div>
                    </div>

                    <FlatsList />
                </section>
            </IrepProvider>

            <!-- Closing CTA -->
            <section class="relative bg-[#0e0e0e] text-white overflow-hidden">
                <img
                    src="/images/services-cta.webp"
                    alt=""
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 w-full h-full object-cover opacity-80"
                    draggable="false"
                />
                <!-- Just enough dimming to hold the white type, no more. -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/45 via-black/30 to-black/55"></div>

                <div class="relative max-w-[1400px] mx-auto px-6 md:px-16 pt-28 md:pt-44 pb-40 md:pb-56 text-center flex flex-col items-center">
                    <p class="kicker mb-8 md:mb-10" style="color: rgba(255,255,255,0.4);">{{ cta.kicker }}</p>
                    <h2 class="display font-light whitespace-pre-line"
                        style="font-size: clamp(40px, 7vw, 104px); line-height: 1.02; letter-spacing: -0.02em;">
                        {{ cta.headline }}
                    </h2>
                    <a
                        :href="cta.buttonLink || '/contact'"
                        class="inline-flex items-center gap-3 mt-12 md:mt-16 px-7 py-4 bg-white text-black text-[11px] tracking-[0.25em] uppercase hover:bg-white/90 transition-colors duration-300 group"
                    >
                        <span>{{ cta.buttonText }}</span>
                        <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">↗</span>
                    </a>
                </div>
            </section>

        </div>
    </AppLayout>
</template>

<style scoped>
/* The 360 viewer resolves `height: 100%` against this stage, so it must be a
   definite height rather than content-driven. */
.viewer-stage--fixed {
    height: min(80svh, 820px);
    min-height: 480px;
}
.viewer-stage--fixed :deep(.irep-project-360-viewer) {
    height: 100%;
}
</style>
