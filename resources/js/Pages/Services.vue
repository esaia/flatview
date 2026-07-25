<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    gallery: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
})

const num = (i) => String(i + 1).padStart(2, '0')

// Service & process numbers are derived from position so the admin only edits text.
const services = computed(() =>
    (props.settings.blocks ?? []).map((b, i) => ({ ...b, number: num(i) }))
)

// Service grid is laid out 2-up so it stays balanced for any count (4 = a clean 2x2).
const isLastServiceCol = (i) => i % 2 === 1 || i + 1 >= services.value.length
const isLastServiceRow = (i) => Math.floor(i / 2) === Math.floor((services.value.length - 1) / 2)
const valueProps = computed(() => props.settings.valueprops ?? [])

// Product feature showcase — each card pairs an admin-uploaded image with copy.
const features = computed(() => props.settings.features ?? [])
const process = computed(() =>
    (props.settings.process ?? []).map((p, i) => ({ ...p, step: num(i) }))
)

// A service "slug" may also be a full external URL (e.g. a WordPress plugin page).
// External ones open in a new tab via a plain <a>, internal ones stay Inertia links.
const isExternal = (slug) => /^https?:\/\//i.test(slug ?? '')
const serviceHref = (slug) => (isExternal(slug) ? slug : `/services/${slug}`)

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
const FRAME_VH = 66          // each frame height in vh (4:3 landscape → width derives from it)
const GAP_PX = 12

// Pixel width of one 4:3 landscape frame, derived from its viewport height.
function frameWidthPx() {
    return window.innerHeight * (FRAME_VH / 100) * (4 / 3)
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
        <div class="relative overflow-x-hidden">

            <!-- Brand logo, top-right -->
            <Link href="/" class="absolute top-7 right-8 md:top-10 md:right-16 z-30 flex items-center gap-2 md:gap-2.5 select-none cursor-pointer">
                <img src="/logo.svg" alt="FlatView" class="h-5 md:h-6 w-auto" draggable="false" />
                <span class="text-black text-sm md:text-base font-semibold tracking-[0.2em] uppercase">FlatView</span>
            </Link>

            <!-- Hero text -->
            <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-14 md:pt-20 pb-14 md:pb-24">
                <div class="max-w-2xl">
                    <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: 0s;">
                        <span class="dot"></span> {{ settings.kicker }}
                    </span>
                    <h1 class="reveal display text-black mb-5 md:mb-6 whitespace-pre-line"
                        style="--d: .08s; font-size: clamp(38px, 6.4vw, 88px); font-weight: 300; line-height: 1.04; letter-spacing: -0.02em;">
                        {{ settings.headline }}
                    </h1>
                    <p class="reveal text-sm md:text-base text-black/40 font-light leading-relaxed whitespace-pre-line" style="--d: .16s;">
                        {{ settings.intro }}
                    </p>
                </div>
            </div>

            <!-- ── Selected Work mobile: swipe gallery ───────────────────── -->
            <div v-if="props.gallery.length" class="md:hidden pb-14">
                <div class="flex items-end justify-between px-6 mb-4">
                    <span class="text-[11px] tracking-[0.2em] uppercase text-black/45">{{ props.settings.galleryKicker || 'Selected Work' }}</span>
                    <span class="text-[11px] tracking-[0.2em] uppercase text-black/30">{{ props.gallery.length }} —</span>
                </div>
                <div class="overflow-x-auto snap-x snap-mandatory flex gap-3 pb-6 px-6 no-bar">
                    <figure
                        v-for="(img, i) in props.gallery"
                        :key="'mobile-'+i"
                        class="snap-start flex-shrink-0 overflow-hidden relative bg-[#ece8e2]"
                        style="width: 80vw; aspect-ratio: 4 / 3;"
                    >
                        <img :src="imgSrc(img)" alt="" loading="lazy" decoding="async" class="w-full h-full object-cover" draggable="false" />
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
                            <span class="dot"></span> {{ props.settings.galleryKicker || 'Selected Work' }}
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
                            :style="{ height: FRAME_VH + 'vh', aspectRatio: '4 / 3' }"
                        >
                            <img
                                :src="imgSrc(img)"
                                alt=""
                                loading="lazy"
                                decoding="async"
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

                <!-- Service blocks: 2-up grid, balanced for any count (e.g. a clean 2x2 for 4) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 mb-16 md:mb-32">
                    <component
                        :is="isExternal(service.slug) ? 'a' : Link"
                        v-for="(service, i) in services"
                        :key="service.number"
                        :href="serviceHref(service.slug)"
                        :target="isExternal(service.slug) ? '_blank' : null"
                        :rel="isExternal(service.slug) ? 'noopener noreferrer' : null"
                        class="group relative py-10 md:py-14 px-0 sm:px-10 md:px-14 flex flex-col border-b border-black/10 transition-colors duration-300 hover:bg-black/[0.02]"
                        :class="[
                            !isLastServiceCol(i) ? 'sm:border-r sm:border-black/10' : '',
                            i % 2 === 0 ? 'sm:pl-0' : '',
                            isLastServiceCol(i) ? 'sm:pr-0' : '',
                            i === services.length - 1 ? 'border-b-0' : '',
                            isLastServiceRow(i) ? 'sm:border-b-0' : '',
                        ]"
                    >
                        <!-- Faded number -->
                        <span
                            class="display absolute top-0 right-4 md:right-8 text-[6rem] md:text-[8rem] leading-none select-none text-black"
                            style="opacity: 0.05;"
                        >
                            {{ service.number }}
                        </span>

                        <div class="relative flex flex-col flex-1 max-w-md">
                            <h2 class="display text-2xl md:text-3xl text-black mb-3 md:mb-4 leading-snug" style="font-weight: 400;">{{ service.name }}</h2>
                            <p class="text-sm text-black/45 font-light leading-relaxed flex-1 mb-6">{{ service.description }}</p>
                            <span class="inline-flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-black/50 group-hover:text-black transition-colors duration-300">
                                Learn more
                                <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                            </span>
                        </div>
                    </component>
                </div>

                <!-- Why Flatview -->
                <div>
                    <p class="kicker flex items-center gap-2 mb-8 md:mb-10"><span class="dot"></span> {{ settings.valuepropsKicker }}</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-0 border-t border-black/10">
                        <div
                            v-for="prop in valueProps"
                            :key="prop.label"
                            class="py-6 md:py-8 pr-6 md:pr-10 border-b border-black/10 md:border-b-0"
                        >
                            <h3 class="display text-lg text-black mb-2.5 md:mb-3 leading-snug" style="font-weight: 400;">{{ prop.label }}</h3>
                            <p class="text-sm text-black/45 font-light leading-relaxed">{{ prop.detail }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── Product showcase ────────────────────────────────────────
                     Headline + 2×2 grid of feature cards. Each card frames an
                     admin-uploaded image inside a warm stage, with title + copy. -->
                <div v-if="features.length" class="mt-24 md:mt-40">
                    <p class="kicker flex items-center gap-2 mb-6"><span class="dot"></span> {{ settings.featuresKicker }}</p>
                    <h2 class="display text-black leading-[1.04] mb-12 md:mb-16 max-w-3xl whitespace-pre-line"
                        style="font-size: clamp(30px, 4.4vw, 60px); font-weight: 300; letter-spacing: -0.02em;">
                        {{ settings.featuresHeadline }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 border-t border-l border-black/10">
                        <article
                            v-for="feature in features"
                            :key="feature.title"
                            class="border-b border-r border-black/10 p-6 md:p-10 flex flex-col"
                        >
                            <div class="fc-stage relative overflow-hidden mb-8 md:mb-10" style="aspect-ratio: 16 / 11;">
                                <img
                                    v-if="feature.image"
                                    :src="imgSrc(feature)"
                                    :alt="feature.title"
                                    loading="lazy"
                                    decoding="async"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    draggable="false"
                                />
                            </div>

                            <h3 class="display text-xl md:text-2xl text-black mb-3 leading-snug" style="font-weight: 400;">{{ feature.title }}</h3>
                            <p class="text-sm text-black/45 font-light leading-relaxed">{{ feature.description }}</p>
                        </article>
                    </div>
                </div>

                <!-- How we work -->
                <div class="mt-24 md:mt-40 grid grid-cols-1 md:grid-cols-12 gap-y-12 md:gap-x-16">

                    <!-- sticky intro -->
                    <div class="md:col-span-4">
                        <div class="md:sticky md:top-28">
                            <p class="kicker flex items-center gap-2 mb-6"><span class="dot"></span> {{ settings.processKicker }}</p>
                            <h2 class="display text-black leading-[1.08] mb-5 whitespace-pre-line"
                                style="font-size: clamp(28px, 3vw, 40px); font-weight: 300; letter-spacing: -0.01em;">
                                {{ settings.processHeadline }}
                            </h2>
                            <p class="text-sm text-black/45 font-light leading-relaxed max-w-xs">
                                {{ settings.processIntro }}
                            </p>
                        </div>
                    </div>

                    <!-- stepped process list -->
                    <div class="md:col-span-7 md:col-start-6">
                        <div
                            v-for="(item, i) in process"
                            :key="item.step"
                            class="step-row group relative grid grid-cols-[auto_1fr] gap-x-6 md:gap-x-10 items-start pl-1 py-7 md:py-9 border-t border-black/10 last:border-b transition-[background-color] duration-500 hover:bg-black/[0.015]"
                        >
                            <!-- index + connecting node -->
                            <div class="step-index relative flex flex-col items-center">
                                <span class="display tabular-nums leading-none text-black/15 group-hover:text-black/70 transition-colors duration-500"
                                      style="font-size: 1.75rem; font-weight: 300;">
                                    {{ item.step }}
                                </span>
                                <span class="step-node mt-3 h-1.5 w-1.5 rounded-full bg-black/15 group-hover:bg-[#5DCAA5] group-hover:scale-150 transition-all duration-500"></span>
                            </div>

                            <div class="pt-0.5">
                                <h3 class="display text-xl md:text-2xl text-black leading-snug mb-2 flex items-center gap-3" style="font-weight: 400;">
                                    {{ item.title }}
                                    <span class="text-base text-black/30 -translate-x-2 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-500">→</span>
                                </h3>
                                <p class="text-sm text-black/45 font-light leading-relaxed max-w-md">{{ item.detail }}</p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Closing CTA -->
            <section class="bg-[#0e0e0e] text-white">
                <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-28 md:pt-44 pb-40 md:pb-56 text-center flex flex-col items-center">
                    <p class="kicker mb-8 md:mb-10" style="color: rgba(255,255,255,0.4);">{{ settings.ctaKicker }}</p>
                    <h2
                        class="display font-light whitespace-pre-line"
                        style="font-size: clamp(40px, 7vw, 104px); line-height: 1.02; letter-spacing: -0.02em;"
                    >
                        {{ settings.ctaHeadline }}
                    </h2>
                    <a
                        :href="settings.ctaButtonLink || '/contact'"
                        class="inline-flex items-center gap-3 mt-12 md:mt-16 px-7 py-4 bg-white text-black text-[11px] tracking-[0.25em] uppercase hover:bg-white/90 transition-colors duration-300 group"
                    >
                        <span>{{ settings.ctaButtonText }}</span>
                        <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">↗</span>
                    </a>
                </div>
            </section>

        </div>
    </AppLayout>
</template>

<style scoped>
/* hide scrollbar on the mobile swipe strip */
.no-bar::-webkit-scrollbar { display: none; }
.no-bar { -ms-overflow-style: none; scrollbar-width: none; }

.tabular-nums { font-variant-numeric: tabular-nums; }

/* Continuous vertical timeline line behind the step nodes.
   The node dot sits above it (relative z-index) and masks the line. */
.step-index::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: -1.5rem;          /* bridge the gap into the next row */
    left: 50%;
    width: 1px;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.08);
}
.step-row:last-child .step-index::before { bottom: 0; }   /* don't trail past the final node */
.step-index .step-node { position: relative; z-index: 1; box-shadow: 0 0 0 4px #fff; }

/* Warm stage panel behind each showcase image — a faint contour wash (a nod
   to site plans) so empty/transparent images still sit in the brand system. */
.fc-stage {
    background-color: #f4f1ec;
    background-image:
        radial-gradient(120% 90% at 85% 15%, rgba(93,202,165,0.06), transparent 60%),
        radial-gradient(100% 80% at 10% 95%, rgba(0,0,0,0.025), transparent 55%);
}
</style>
