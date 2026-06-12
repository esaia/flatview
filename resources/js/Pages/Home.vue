<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import FloatingMenu from '@/Components/FloatingMenu.vue'

const slides = [
    { id: 1, label: 'Tbilisi Towers',     category: 'Real Estate · 2024',       shade: '#111111' },
    { id: 2, label: 'SkyView Residences', category: 'Floor Plan Plugin · 2024', shade: '#161616' },
    { id: 3, label: 'BuildCore',          category: 'Construction · 2024',       shade: '#0d0d0d' },
]

const currentSlide = ref(0)
let timer = null

onMounted(() => {
    timer = setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % slides.length
    }, 4000)
})

onUnmounted(() => {
    clearInterval(timer)
})
</script>

<template>
    <!-- Stack vertically on mobile, side-by-side on desktop -->
    <div class="min-h-screen md:h-screen overflow-hidden flex flex-col md:flex-row relative select-none">

        <!-- Image panel: full-width strip on mobile, 55% on desktop -->
        <div class="h-[45vh] md:h-full w-full md:w-[55%] flex-shrink-0 relative overflow-hidden bg-black">
            <div
                v-for="(slide, index) in slides"
                :key="slide.id"
                class="absolute inset-0 transition-opacity duration-1000"
                :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
                :style="{ backgroundColor: slide.shade }"
            >
                <!-- Large faint watermark -->
                <div class="absolute inset-0 flex items-center justify-center overflow-hidden">
                    <span class="text-white/[0.03] text-[22vw] md:text-[18vw] font-bold uppercase leading-none tracking-tighter select-none whitespace-nowrap">
                        {{ slide.label.split(' ')[0] }}
                    </span>
                </div>

                <!-- Caption -->
                <div class="absolute bottom-6 md:bottom-10 left-6 md:left-10">
                    <p class="text-white/30 text-[10px] tracking-[0.2em] uppercase mb-1.5">{{ slide.category }}</p>
                    <p class="text-white text-sm font-medium">{{ slide.label }}</p>
                </div>

                <!-- Slide dots -->
                <div class="absolute bottom-7 md:bottom-11 right-6 md:right-10 flex items-center gap-2">
                    <span
                        v-for="(s, i) in slides"
                        :key="i"
                        class="block h-px transition-all duration-500 rounded-full"
                        :class="i === currentSlide ? 'w-6 bg-white' : 'w-1.5 bg-white/25'"
                    ></span>
                </div>
            </div>
        </div>

        <!-- Content panel: below on mobile, 45% on desktop -->
        <div class="flex-1 md:w-[45%] md:flex-shrink-0 bg-white flex flex-col justify-center px-8 sm:px-12 md:px-14 lg:px-20 py-12 md:py-0">
            <p class="text-[10px] font-medium tracking-[0.22em] uppercase text-black/25 mb-8 md:mb-10">
                Web Development Agency
            </p>

            <h1 class="text-3xl sm:text-4xl md:text-[3.25rem] font-bold uppercase tracking-tight text-black leading-[1.1] mb-5 md:mb-7">
                Digital<br>presence<br>for builders.
            </h1>

            <p class="text-sm text-black/40 font-light leading-relaxed mb-10 md:mb-12 max-w-[300px]">
                Websites and interactive floor plan tools for construction and real estate companies.
            </p>

            <div class="flex flex-col sm:flex-row gap-3">
                <button
                    @click="router.visit('/work')"
                    class="text-[10px] font-medium tracking-[0.18em] uppercase text-black border border-black/20 px-7 py-4 transition-all duration-200 hover:bg-black hover:text-white"
                >
                    View our work
                </button>
                <button
                    @click="router.visit('/contact')"
                    class="text-[10px] font-medium tracking-[0.18em] uppercase text-black border border-black/20 px-7 py-4 transition-all duration-200 hover:bg-black hover:text-white"
                >
                    Get in touch
                </button>
            </div>
        </div>

        <!-- Floating menu: center-border on desktop, bottom-right on mobile -->
        <FloatingMenu active-page="home" :center-border="true" />

    </div>
</template>
