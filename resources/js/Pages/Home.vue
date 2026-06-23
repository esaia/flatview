<script setup>
import { computed, ref, onMounted, defineAsyncComponent } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// Code-split the heavy 360 viewer (gsap/swiper/viewer ≈ 379 KiB) into its own
// chunk so the hero text + featured image paint first; it loads after mount.
const IreProject360 = defineAsyncComponent(() => import('@/irep/shortcodes/IreProject360.vue'))

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    demoProjectId: {
        type: [String, Number],
        default: null,
    },
    demoData: {
        type: Object,
        default: null,
    },
})

const demoProjectId = computed(() => props.demoProjectId)

// Intro loader: logo slides in from the right, wordmark from the left,
// both revealed through a centre mask, then the overlay fades away.
const loaderHidden = ref(false)
const heroReady = ref(false)

onMounted(() => {
    // Give the hero entrance a small head start so the wipe is already in
    // motion the instant the loader clears, then remove the loader.
    setTimeout(() => { heroReady.value = true }, 800)
    setTimeout(() => { loaderHidden.value = true }, 1050)
})
</script>

<template>
    <Head title="Flatview" />

    <AppLayout active-page="home" :always-visible="true">
        <!-- ── Intro loader ──────────────────────────────────────────── -->
        <div
            v-if="!loaderHidden"
            class="intro-loader fixed inset-0 z-[100] flex items-center justify-center bg-white"
        >
            <div class="flex items-center gap-3 md:gap-4 select-none">
                <!-- Logo mask: mark slides in from the right -->
                <span class="loader-mask">
                    <img src="/logo.svg" alt="FlatView" class="loader-logo h-9 md:h-12 w-auto" draggable="false" />
                </span>
                <!-- Text mask: wordmark slides in from the left -->
                <span class="loader-mask">
                    <span class="loader-text text-black text-2xl md:text-4xl font-semibold tracking-[0.2em] uppercase">FlatView</span>
                </span>
            </div>
        </div>

        <div class="relative overflow-x-hidden md:h-screen md:overflow-hidden" :class="{ 'hero-ready': heroReady }">
            <div class="flex flex-col md:flex-row select-none md:absolute md:inset-0">
                <!-- Interactive 360 demo panel -->
                <div class="hero-media relative order-2 w-full h-[60svh] overflow-hidden bg-black md:order-none md:absolute md:inset-y-0 md:left-0 md:right-auto md:w-1/2 md:h-full md:z-20">
                    <IreProject360 v-if="demoProjectId" :project-id="demoProjectId" :data="props.demoData" class="absolute inset-0 h-full w-full" />
                </div>

                <!-- Content panel -->
                <div class="hero-content relative order-1 w-full min-h-[60svh] py-20 bg-white flex flex-col justify-center px-7 md:px-14 lg:px-20 md:py-0 md:order-none md:absolute md:inset-y-0 md:left-auto md:right-0 md:w-1/2 md:h-full md:z-10">
                    <!-- Brand logo, top-right -->
                    <div class="absolute top-7 right-7 md:top-10 md:right-14 lg:right-20 flex items-center gap-2 md:gap-2.5 select-none">
                        <img src="/logo.svg" alt="FlatView" class="h-5 md:h-6 w-auto" draggable="false" />
                        <span class="text-black text-sm md:text-base font-semibold tracking-[0.2em] uppercase">FlatView</span>
                    </div>

                    <div class="w-full">
                        <!-- headline -->
                        <h1 class="reveal display hero-h1 text-black mb-8 md:mb-9 text-balance" style="--d: .08s; line-height: 1.06; font-weight: 300; letter-spacing: -0.022em; white-space: pre-line">{{ props.settings.headline || "Digital\nPresence\nFor Builders." }}</h1>

                        <!-- subtitle -->
                        <p class="reveal border-l border-black/10 pl-5 text-black/50 text-[14px] md:text-[15px] leading-[1.75] max-w-[420px] mb-10 md:mb-12" style="--d: .16s">
                            {{ props.settings.subtitle || 'Websites and interactive floor plan tools for construction and real estate companies.' }}
                        </p>

                        <!-- ctas -->
                        <div class="reveal flex flex-wrap items-center gap-2 md:gap-4" style="--d: .24s">
                            <a href="/work"
                               class="group inline-flex items-center gap-3 bg-black text-white text-[10px] md:text-[11px] tracking-[0.18em] uppercase px-7 py-4 hover:bg-[#5DCAA5] hover:text-black transition-colors duration-300">
                                View Our Work
                                <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                            </a>
                            <a href="/contact"
                               class="group inline-flex items-center gap-2 text-[10px] md:text-[11px] tracking-[0.18em] uppercase text-black/55 hover:text-black px-4 py-4 transition-colors duration-300">
                                Get In Touch
                                <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ── Intro loader ─────────────────────────────────────────────────
 * Two centre masks (overflow-hidden) reveal their contents as they
 * slide in: the logo from the right, the wordmark from the left.
 * When `loading` ends, .intro-loader--out fades the whole overlay out. */
.loader-mask {
    display: inline-flex;
    overflow: hidden;
}
.loader-logo {
    transform: translateX(120%);
    animation: loader-from-right 0.6s cubic-bezier(0.22, 0.9, 0.27, 1) 0.1s forwards;
}
.loader-text {
    display: inline-block;
    transform: translateX(-120%);
    animation: loader-from-left 0.6s cubic-bezier(0.22, 0.9, 0.27, 1) 0.1s forwards;
}
@keyframes loader-from-right {
    to { transform: translateX(0); }
}
@keyframes loader-from-left {
    to { transform: translateX(0); }
}

@media (prefers-reduced-motion: reduce) {
    .loader-logo,
    .loader-text { animation-duration: 0.01s; animation-delay: 0s; }
}

/* Hold the hero entrance (media wipe + text reveal, defined in app.css)
   until the intro loader finishes — .hero-ready is toggled when loading ends. */
.hero-media,
:deep(.reveal) {
    animation-play-state: paused;
}
.hero-ready .hero-media,
.hero-ready :deep(.reveal) {
    animation-play-state: running;
}

/* Mobile: headline scales fluidly with viewport width. */
.hero-h1 {
    font-size: clamp(33px, 11vw, 64px);
}
/* Desktop: bound to the content column width. */
@media (min-width: 768px) {
    .hero-h1 {
        font-size: clamp(33px, 4.4vw, 60px);
    }
}

/* The 360 viewer's children use h-full, so the viewer needs a definite height.
   Its wrapper chain is height:auto, which collapses it to the image's intrinsic
   size. Anchor the viewer to the absolutely-positioned IreProject360 root
   (which fills the .hero-media panel) so it always matches the panel height. */
.hero-media :deep(.irep-project-360-viewer) {
    position: absolute;
    inset: 0;
}
</style>
