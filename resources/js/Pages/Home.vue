<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import IreProject360 from '@/irep/shortcodes/IreProject360.vue'

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
</script>

<template>
    <AppLayout active-page="home" :always-visible="true">
        <div class="relative overflow-x-hidden md:h-screen md:overflow-hidden">
            <div class="flex flex-col md:flex-row select-none md:absolute md:inset-0">
                <!-- Interactive 360 demo panel -->
                <div class="hero-media relative order-2 w-full h-[60svh] overflow-hidden bg-black md:order-none md:absolute md:inset-y-0 md:left-0 md:right-auto md:w-1/2 md:h-full md:z-20">
                    <IreProject360 v-if="demoProjectId" :project-id="demoProjectId" :data="props.demoData" class="absolute inset-0 h-full w-full" />
                </div>

                <!-- Content panel -->
                <div class="hero-content relative order-1 w-full min-h-[60svh] py-20 bg-white flex flex-col justify-center px-7 md:px-14 lg:px-20 md:py-0 md:order-none md:absolute md:inset-y-0 md:left-auto md:right-0 md:w-1/2 md:h-full md:z-10">
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
