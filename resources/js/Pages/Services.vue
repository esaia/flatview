<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    gallery: { type: Array, default: () => [] },
})

const services = [
    {
        number: '01',
        name: 'Website development',
        description: 'Custom websites for construction and real estate companies. WordPress or custom-built. Fast, SEO-ready, mobile-first — designed to convert visitors into clients.',
    },
    {
        number: '02',
        name: 'Floor plan plugin',
        description: 'Interactive building visualizer. Browse floors, select apartments, view availability and pricing — embedded directly on your website with no third-party platform needed.',
    },
    {
        number: '03',
        name: 'Maintenance & support',
        description: 'Ongoing updates, hosting management, and technical support so your digital presence stays fast, secure, and reliable long after launch.',
    },
]

const valueProps = [
    { label: 'Niche focused',   detail: 'We work exclusively in construction and real estate.' },
    { label: 'Fast delivery',   detail: 'Typical project turnaround: 3–6 weeks.' },
    { label: 'EU-ready',        detail: 'GDPR compliant, multi-language, EU-hosted.' },
    { label: 'Ongoing support', detail: 'Retainer options from day one.' },
]

// Resolve a gallery image to a usable URL: pass external URLs through,
// otherwise serve the stored relative path from the public disk symlink.
function imgSrc(img) {
    const path = img?.image
    if (!path) return ''
    if (path.startsWith('http')) return path
    return '/storage/' + path.replace(/^\/+/, '')
}

/* ───────────────────────── Gallery: pinned horizontal scroll ─────────────────────────
 * SENSITIVITY < 1 means the pinned section is SHORTER than the horizontal strip, so a
 * little vertical scroll travels a lot of horizontal distance → faster, snappier feel.
 * The strip position is eased (lerp) every frame for buttery, weighted motion.
 */
const SENSITIVITY = 0.55      // lower = faster traversal (less scroll needed)
const EASE = 0.12             // lerp factor toward target (lower = floatier)
const FRAME_VH = 66          // each frame height in vh (9:16 portrait → width derives from it)
const GAP_PX = 12

// Pixel width of one 9:16 portrait frame, derived from its viewport height.
function frameWidthPx() {
    return window.innerHeight * (FRAME_VH / 100) * (9 / 16)
}

const gallerySection = ref(null)
const galleryState = ref('before')   // 'before' | 'active' | 'after'
const sectionHeight = ref('300vh')
const progress = ref(0)              // 0..1 scroll progress through the gallery
const activeIndex = ref(0)

let targetTranslate = 0
let currentTranslate = 0
const galleryTranslate = ref(0)
let rafId = null

function stripWidth() {
    return Math.max(0, props.gallery.length * (frameWidthPx() + GAP_PX) - window.innerWidth)
}

function pinPx() {
    return stripWidth() * SENSITIVITY
}

function recompute() {
    sectionHeight.value = `${Math.round(window.innerHeight + pinPx())}px`
}

function handleScroll() {
    const el = gallerySection.value
    if (!el) return
    const rect = el.getBoundingClientRect()
    const pin = pinPx()

    if (rect.top > 0) {
        galleryState.value = 'before'
    } else if (rect.bottom <= window.innerHeight) {
        galleryState.value = 'after'
    } else {
        galleryState.value = 'active'
    }

    const scrolled = pin > 0 ? Math.max(0, Math.min(1, -rect.top / pin)) : 0
    progress.value = scrolled
    targetTranslate = -scrolled * stripWidth()
    activeIndex.value = Math.min(props.gallery.length - 1, Math.round(scrolled * (props.gallery.length - 1)))
}

function loop() {
    currentTranslate += (targetTranslate - currentTranslate) * EASE
    if (Math.abs(targetTranslate - currentTranslate) < 0.05) currentTranslate = targetTranslate
    galleryTranslate.value = currentTranslate
    rafId = requestAnimationFrame(loop)
}

const pad = (n) => String(n + 1).padStart(2, '0')
const progressPct = computed(() => Math.round(progress.value * 100))

onMounted(async () => {
    await nextTick()
    recompute()
    window.addEventListener('scroll', handleScroll, { passive: true })
    window.addEventListener('resize', recompute)
    handleScroll()
    rafId = requestAnimationFrame(loop)
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
    window.removeEventListener('resize', recompute)
    if (rafId) cancelAnimationFrame(rafId)
})
</script>

<template>
    <AppLayout active-page="services">
        <div class="overflow-x-hidden">

            <!-- Hero text -->
            <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-14 md:pt-20 pb-14 md:pb-24">
                <div class="max-w-2xl">
                    <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: 0s;">
                        <span class="dot"></span> Our Services
                    </span>
                    <h1 class="reveal display text-black mb-5 md:mb-6"
                        style="--d: .08s; font-size: clamp(38px, 6.4vw, 88px); font-weight: 300; line-height: 1.04; letter-spacing: -0.02em;">
                        What we do
                    </h1>
                    <p class="reveal text-sm md:text-base text-black/40 font-light leading-relaxed" style="--d: .16s;">
                        We build digital products for the construction and real estate sector —
                        from marketing websites to interactive tools that help sell properties faster.
                    </p>
                </div>
            </div>

            <!-- ── Selected Work mobile: swipe gallery ───────────────────── -->
            <div v-if="props.gallery.length" class="md:hidden pb-14">
                <div class="flex items-end justify-between px-6 mb-4">
                    <span class="text-[11px] tracking-[0.2em] uppercase text-black/45">Selected Work</span>
                    <span class="text-[11px] tracking-[0.2em] uppercase text-black/30">{{ props.gallery.length }} —</span>
                </div>
                <div class="overflow-x-auto snap-x snap-mandatory flex gap-3 pb-6 px-6 no-bar">
                    <figure
                        v-for="(img, i) in props.gallery"
                        :key="'mobile-'+i"
                        class="snap-start flex-shrink-0 overflow-hidden relative bg-[#ece8e2]"
                        style="width: 70vw; aspect-ratio: 9 / 16;"
                    >
                        <img :src="imgSrc(img)" class="w-full h-full object-cover" draggable="false" />
                    </figure>
                </div>
            </div>

            <!-- ── Selected Work desktop: pinned horizontal scroll gallery ── -->
            <div
                v-if="props.gallery.length"
                ref="gallerySection"
                class="hidden md:block relative"
                :style="{ height: sectionHeight }"
            >
                <div
                    class="h-screen overflow-hidden flex flex-col justify-center left-0 w-full"
                    :class="{
                        'fixed top-0':       galleryState === 'active',
                        'absolute top-0':    galleryState === 'before',
                        'absolute bottom-0': galleryState === 'after',
                    }"
                >
                    <!-- top meta row -->
                    <div class="flex items-center justify-between px-16 pb-8">
                        <span class="text-[11px] tracking-[0.25em] uppercase text-black/45 flex items-center gap-2">
                            <span class="dot"></span> Selected Work
                        </span>
                        <span class="display text-black/80 tabular-nums" style="font-size: 15px; letter-spacing: .04em;">
                            {{ pad(activeIndex) }} <span class="text-black/30">/ {{ pad(props.gallery.length - 1) }}</span>
                        </span>
                    </div>

                    <!-- strip -->
                    <div
                        class="flex pl-16 will-change-transform"
                        :style="{ gap: GAP_PX + 'px', transform: `translateX(${galleryTranslate}px)` }"
                    >
                        <figure
                            v-for="(img, i) in props.gallery"
                            :key="i"
                            class="flex-shrink-0 overflow-hidden relative group bg-[#ece8e2]"
                            :style="{ height: FRAME_VH + 'vh', aspectRatio: '9 / 16' }"
                        >
                            <img
                                :src="imgSrc(img)"
                                class="w-full h-full object-cover transition-transform duration-[1200ms] ease-out group-hover:scale-[1.04]"
                                draggable="false"
                            />
                        </figure>
                    </div>

                    <!-- progress bar -->
                    <div class="px-16 pt-10">
                        <div class="flex items-center gap-4">
                            <div class="h-px flex-1 bg-black/10 relative overflow-hidden">
                                <div class="absolute inset-y-0 left-0 bg-black transition-none" :style="{ width: progressPct + '%' }"></div>
                            </div>
                            <span class="text-[10px] tracking-[0.2em] tabular-nums text-black/40 w-10 text-right">{{ progressPct }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-14 md:pt-24 pb-20 md:pb-32">

                <!-- Three service blocks -->
                <div class="grid grid-cols-1 md:grid-cols-3 md:divide-x divide-black/10 mb-16 md:mb-32">
                    <div
                        v-for="service in services"
                        :key="service.number"
                        class="relative pt-8 pb-10 md:px-10 first:md:pl-0 last:md:pr-0 flex flex-col border-b border-black/10 md:border-b-0 last:border-b-0"
                    >
                        <!-- Faded number -->
                        <span
                            class="display absolute top-0 right-4 md:right-8 text-[6rem] md:text-[7rem] leading-none select-none text-black"
                            style="opacity: 0.05;"
                        >
                            {{ service.number }}
                        </span>

                        <div class="relative flex flex-col flex-1">
                            <p class="kicker text-black/45 mb-4">{{ service.number }}</p>
                            <h2 class="display text-2xl text-black mb-3 md:mb-4 leading-snug" style="font-weight: 400;">{{ service.name }}</h2>
                            <p class="text-sm text-black/45 font-light leading-relaxed flex-1">{{ service.description }}</p>
                            <a href="#" class="inline-flex items-center gap-2 text-[11px] tracking-[0.25em] uppercase text-black/55 hover:text-black transition-colors duration-300 mt-7 md:mt-8 group">
                                <span>Learn more</span>
                                <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Why Merisimo -->
                <div>
                    <p class="kicker flex items-center gap-2 mb-8 md:mb-10"><span class="dot"></span> Why Merisimo?</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-0 border-t border-black/10">
                        <div
                            v-for="prop in valueProps"
                            :key="prop.label"
                            class="py-6 md:py-8 pr-6 md:pr-10 border-b border-black/10 md:border-b-0"
                        >
                            <h3 class="text-sm font-semibold text-black mb-1.5 md:mb-2">{{ prop.label }}</h3>
                            <p class="text-xs md:text-sm text-black/40 font-light leading-relaxed">{{ prop.detail }}</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
/* hide scrollbar on the mobile swipe strip */
.no-bar::-webkit-scrollbar { display: none; }
.no-bar { -ms-overflow-style: none; scrollbar-width: none; }

.tabular-nums { font-variant-numeric: tabular-nums; }
</style>
