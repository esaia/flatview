<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    gallery:  { type: Array,  default: () => [] },
    stats:    { type: Array,  default: () => [] },
})

// 'before' | 'active' | 'after'
const galleryState = ref('before')
const galleryTranslate = ref(0)
const gallerySection = ref(null)

function handleScroll() {
    if (!gallerySection.value) return
    const rect = gallerySection.value.getBoundingClientRect()
    const sectionH = gallerySection.value.offsetHeight
    const totalScrollable = sectionH - window.innerHeight

    if (rect.top > 0) {
        galleryState.value = 'before'
    } else if (rect.bottom <= window.innerHeight) {
        galleryState.value = 'after'
    } else {
        galleryState.value = 'active'
    }

    const scrolled = Math.max(0, Math.min(1, -rect.top / totalScrollable))
    const stripWidth = props.gallery.length * (28 * window.innerWidth / 100 + 8) - window.innerWidth
    galleryTranslate.value = -scrolled * stripWidth
}

onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>

<template>
    <AppLayout active-page="about">
        <div class="overflow-x-hidden">

            <!-- Section 1: Large statement -->
            <div class="flex flex-col justify-center px-6 md:px-16" style="min-height: 70vh;">
                <p style="font-size: clamp(32px, 6vw, 72px); font-weight: 300; line-height: 1.1;" class="text-black max-w-4xl">
                    {{ props.settings.about_headline || 'We build digital tools for the built world.' }}
                </p>
                <a :href="props.settings.about_story_link || '#'" class="mt-8 text-[11px] uppercase tracking-widest text-black/40 inline-block">
                    Our Story →
                </a>
            </div>

            <!-- Section 2 mobile: swipe gallery (replaces scroll-linked version on small screens) -->
            <div class="md:hidden overflow-x-auto snap-x snap-mandatory flex gap-2 pb-4 px-6">
                <div
                    v-for="(img, i) in props.gallery"
                    :key="'mobile-'+i"
                    class="snap-start flex-shrink-0 overflow-hidden rounded"
                    style="width: 80vw; height: 60vw;"
                >
                    <img
                        :src="img.image && img.image.startsWith('http') ? img.image : '/storage/' + img.image"
                        class="w-full h-full object-cover"
                        draggable="false"
                    />
                </div>
            </div>

            <!-- Section 2 desktop: pinned horizontal scroll gallery (JS-driven) -->
            <div ref="gallerySection" class="hidden md:block" style="height: 600vh; position: relative;">
                <div
                    class="h-screen overflow-hidden flex items-center left-0 w-full"
                    :class="{
                        'fixed top-0':       galleryState === 'active',
                        'absolute top-0':    galleryState === 'before',
                        'absolute bottom-0': galleryState === 'after',
                    }"
                >
                    <div
                        class="flex gap-2 will-change-transform"
                        :style="{ transform: `translateX(${galleryTranslate}px)` }"
                    >
                        <div
                            v-for="(img, i) in props.gallery"
                            :key="i"
                            class="flex-shrink-0 overflow-hidden"
                            style="width: 28vw; height: 75vh;"
                        >
                            <img
                                :src="img.image && img.image.startsWith('http') ? img.image : '/storage/' + img.image"
                                class="w-full h-full object-cover"
                                draggable="false"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Stats — label left, numbers centered -->
            <div class="relative px-6 md:px-16 pt-24 pb-24">
                <p class="absolute left-6 md:left-16 top-24 text-[11px] tracking-[0.15em] uppercase font-medium text-black">
                    Stats & Facts
                </p>
                <div class="flex flex-col items-center">
                    <div
                        v-for="(stat, i) in props.stats"
                        :key="i"
                        class="py-10 md:py-16 border-t border-black/10 w-full text-center"
                    >
                        <p class="font-light leading-none tracking-tight text-black mb-4" style="font-size: clamp(48px, 10vw, 120px);">
                            {{ stat.value }}
                        </p>
                        <p class="text-[13px] md:text-[15px] text-black/50">
                            {{ stat.label }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 4: Warm beige closing statement -->
            <div style="background: #f0ebe3;" class="px-6 md:px-16 py-16 md:py-24">
                <p class="text-[15px] md:text-[17px] leading-loose" style="color: #2a2520;">
                    {{ props.settings.about_beige_text_1 || 'Merisimo is a boutique web development agency specialising in digital products for the construction and real estate sector.' }}
                </p>
                <p class="text-[15px] md:text-[17px] leading-loose mt-6" style="color: #2a2520;">
                    {{ props.settings.about_beige_text_2 || 'Our flagship product is an interactive floor plan plugin that lets buyers browse buildings, select apartments by floor and status, and view pricing — embedded directly in your website.' }}
                </p>
            </div>

            <!-- Section 5: Work with us CTA -->
            <div class="pt-16 md:pt-32 pb-32 md:pb-64 px-6 md:px-16 flex flex-col items-center justify-center text-center">
                <h2 class="font-light leading-none tracking-tight text-black mb-6" style="font-size: clamp(36px, 7vw, 96px);">
                    {{ props.settings.about_cta_title || 'Work with us' }}
                </h2>
                <a
                    href="/contact"
                    class="text-[11px] tracking-[0.2em] uppercase text-black/40 hover:text-black transition-colors duration-200 border-b border-black/20 hover:border-black pb-px"
                >
                    {{ props.settings.about_cta_link_text || 'Introduce yourself' }}
                </a>
            </div>

        </div>
    </AppLayout>
</template>
