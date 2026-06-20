<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    slides: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
})

// ── Slideshow ────────────────────────────────────────────────────────────────
const slides = computed(() => props.slides)
const currentSlide = ref(0)
const videoRefs = ref([])
let slideTimer = null

watch(currentSlide, (newIdx) => {
    videoRefs.value.forEach((el, i) => {
        if (!el) return
        i === newIdx ? el.play() : el.pause()
    })
})

onMounted(() => {
    slideTimer = setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % (slides.value.length || 1)
    }, 5000)
})

onUnmounted(() => {
    clearInterval(slideTimer)
})
</script>

<template>
    <AppLayout active-page="home" :always-visible="true">
        <div class="relative overflow-x-hidden md:h-screen md:overflow-hidden">
            <div class="flex flex-col md:flex-row select-none md:absolute md:inset-0">
                <!-- Image panel -->
                <div class="hero-media relative order-2 w-full h-[60svh] overflow-hidden bg-black md:order-none md:absolute md:inset-y-0 md:left-0 md:right-auto md:w-1/2 md:h-full md:z-20">
                    <div
                        v-for="(slide, index) in slides"
                        :key="slide.id"
                        class="absolute inset-0"
                        :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
                        :style="{ backgroundColor: slide.background_color || '#1a1a1a', transition: 'opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)' }"
                    >
                        <video
                            v-if="slide.media_type === 'video' && slide.video"
                            :ref="el => { if (el) videoRefs[index] = el }"
                            class="absolute inset-0 w-full h-full object-cover"
                            autoplay muted loop playsinline
                            :src="`/storage/${slide.video}`"
                        ></video>
                        <img
                            v-else-if="slide.image"
                            :src="slide.image.startsWith('http') ? slide.image : `/storage/${slide.image}`"
                            :alt="slide.title"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="absolute inset-0"
                            :style="{ backgroundColor: slide.background_color || '#1a1a1a' }"
                        ></div>
                        <div class="absolute inset-0 flex items-center justify-center overflow-hidden">
                            <span class="text-white/[0.03] text-[22vw] md:text-[18vw] font-bold uppercase leading-none tracking-tighter select-none whitespace-nowrap">
                                {{ slide.title ? slide.title.split(' ')[0] : '' }}
                            </span>
                        </div>
                        <div class="absolute bottom-4 left-4 md:bottom-8 md:left-8 z-10">
                            <p class="text-white text-[11px] md:text-sm font-medium">{{ slide.title }}</p>
                        </div>
                        <div class="absolute bottom-7 md:bottom-11 right-6 md:right-10 flex items-center gap-2">
                            <span
                                v-for="(s, i) in slides"
                                :key="i"
                                class="block transition-all duration-500 rounded-full"
                                :class="i === currentSlide ? 'w-4 md:w-6 h-px bg-white' : 'w-0.5 h-0.5 md:w-1 md:h-1 bg-white/25'"
                            ></span>
                        </div>
                    </div>
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
</style>
