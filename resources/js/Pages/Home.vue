<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
})

// ── Slideshow ────────────────────────────────────────────────────────────────
const slides = computed(() => props.projects)
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
        <div class="relative h-screen overflow-hidden overflow-x-hidden">
            <div class="absolute inset-0 flex flex-col md:flex-row select-none">
                <!-- Image panel -->
                <div class="absolute inset-x-0 top-0 h-[50vh] md:inset-y-0 md:left-0 md:right-auto md:w-1/2 md:h-full overflow-hidden bg-black">
                    <div
                        v-for="(slide, index) in slides"
                        :key="slide.id"
                        class="absolute inset-0"
                        :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
                        :style="{ backgroundColor: slide.background_color || '#1a1a1a', transition: 'opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)' }"
                    >
                        <video
                            v-if="slide.media_type === 'video' && slide.video"
                            :ref="el => { if (el) videoRefs.value[index] = el }"
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
                            <p class="text-white/30 text-[8px] md:text-[10px] tracking-[0.2em] uppercase mb-1.5">
                                {{ slide.category }}{{ slide.year ? ' · ' + slide.year : '' }}
                            </p>
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
                <div class="absolute inset-x-0 bottom-0 h-[50vh] md:inset-y-0 md:left-auto md:right-0 md:w-1/2 md:h-full bg-white flex flex-col justify-center px-6 md:px-16">
                    <div class="flex-1 flex flex-col justify-center">
                        <p class="text-[9px] md:text-[10px] tracking-[0.18em] uppercase text-black/40 font-medium mb-8">
                            {{ props.settings.badge || 'Web Development Agency' }}
                        </p>
                        <h1 class="font-black text-[28px] md:text-[clamp(40px,4.5vw,72px)] leading-[0.92] tracking-[-0.02em] uppercase text-black mb-6" style="white-space: pre-line">{{ props.settings.headline || "Digital\nPresence\nFor Builders." }}</h1>
                        <p class="text-black/45 text-[13px] md:text-[15px] leading-relaxed max-w-[340px] mb-8">
                            {{ props.settings.subtitle || 'Websites and interactive floor plan tools for construction and real estate companies.' }}
                        </p>
                        <div class="flex items-center gap-4">
                            <a href="/work"
                               class="border border-black text-black text-[10px] md:text-[11px] tracking-[0.1em] uppercase px-4 py-3 md:px-8 md:py-4 hover:bg-black hover:text-white transition-colors duration-200">
                                View Our Work
                            </a>
                            <a href="/contact"
                               class="border border-black text-black text-[10px] md:text-[11px] tracking-[0.1em] uppercase px-4 py-3 md:px-8 md:py-4 hover:bg-black hover:text-white transition-colors duration-200">
                                Get In Touch
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
