<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    gallery:  { type: Array,  default: () => [] },
    stats:    { type: Array,  default: () => [] },
})

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
const COL_VW = 26             // each frame width in vw
const GAP_PX = 12

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
    return Math.max(0, props.gallery.length * (COL_VW * window.innerWidth / 100 + GAP_PX) - window.innerWidth)
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

/* ───────────────────────── Stats: reveal + count-up ───────────────────────── */
const statsSection = ref(null)
const statProgress = ref(0)

function parseStat(value) {
    const m = String(value ?? '').match(/^(\D*?)([\d.,]+)(.*)$/s)
    if (!m) return { prefix: '', num: null, suffix: String(value ?? ''), decimals: 0 }
    const numStr = m[2].replace(/,/g, '')
    const decimals = numStr.includes('.') ? (numStr.split('.')[1]?.length ?? 0) : 0
    return { prefix: m[1], num: parseFloat(numStr), suffix: m[3], decimals }
}

function displayStat(value) {
    const p = parseStat(value)
    if (p.num === null) return p.suffix
    const eased = statProgress.value
    const current = p.num * eased
    const formatted = current.toLocaleString(undefined, {
        minimumFractionDigits: p.decimals,
        maximumFractionDigits: p.decimals,
    })
    return p.prefix + formatted + p.suffix
}

const pad = (n) => String(n + 1).padStart(2, '0')

function animateStats() {
    const duration = 1500
    const start = performance.now()
    const tick = (now) => {
        const t = Math.min(1, (now - start) / duration)
        statProgress.value = 1 - Math.pow(1 - t, 4) // easeOutQuart
        if (t < 1) requestAnimationFrame(tick)
    }
    requestAnimationFrame(tick)
}

let statObserver = null

onMounted(async () => {
    await nextTick()
    recompute()
    window.addEventListener('scroll', handleScroll, { passive: true })
    window.addEventListener('resize', recompute)
    handleScroll()
    rafId = requestAnimationFrame(loop)

    if (statsSection.value) {
        statObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                animateStats()
                statObserver.disconnect()
            }
        }, { threshold: 0.3 })
        statObserver.observe(statsSection.value)
    }
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
    window.removeEventListener('resize', recompute)
    if (rafId) cancelAnimationFrame(rafId)
    if (statObserver) statObserver.disconnect()
})

const progressPct = computed(() => Math.round(progress.value * 100))
</script>

<template>
    <AppLayout active-page="about">
        <div class="about-root overflow-x-hidden">

            <!-- ── Section 1: Hero statement ─────────────────────────────── -->
            <section class="relative flex flex-col justify-start md:justify-center px-6 md:px-16 pt-28 pb-16 md:py-0 min-h-0 md:min-h-[86vh]">
                <span class="reveal kicker flex items-center gap-2 text-[11px] tracking-[0.25em] uppercase text-black/45 mb-7" style="--d: 0s;">
                    <span class="dot"></span> About Merisimo
                </span>

                <h1 class="reveal display text-black max-w-5xl" style="--d: .08s; font-size: clamp(38px, 6.4vw, 88px); line-height: 1.04; font-weight: 300; letter-spacing: -0.02em;">
                    {{ props.settings.about_headline || 'We build digital tools for the built world.' }}
                </h1>

                <a
                    :href="props.settings.about_story_link || '#'"
                    class="reveal story-link group mt-12 inline-flex items-center gap-3 text-[11px] uppercase tracking-[0.25em] text-black/50 hover:text-black transition-colors duration-300 w-fit"
                    style="--d: .18s;"
                >
                    Our Story
                    <span class="story-arrow inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                </a>

                <!-- corner index -->
                <span class="reveal hidden md:block absolute right-16 bottom-16 text-[11px] tracking-[0.2em] uppercase text-black/30" style="--d: .26s;">
                    Est. — Studio
                </span>
            </section>

            <!-- ── Section 2 mobile: swipe gallery ───────────────────────── -->
            <div class="md:hidden">
                <div class="flex items-end justify-between px-6 mb-4">
                    <span class="text-[11px] tracking-[0.2em] uppercase text-black/45">Selected Work</span>
                    <span class="text-[11px] tracking-[0.2em] uppercase text-black/30">{{ props.gallery.length }} —</span>
                </div>
                <div class="overflow-x-auto snap-x snap-mandatory flex gap-3 pb-6 px-6 no-bar">
                    <figure
                        v-for="(img, i) in props.gallery"
                        :key="'mobile-'+i"
                        class="snap-start flex-shrink-0 overflow-hidden relative bg-[#ece8e2]"
                        style="width: 82vw; height: 64vw;"
                    >
                        <img :src="imgSrc(img)" class="w-full h-full object-contain" draggable="false" />
                    </figure>
                </div>
            </div>

            <!-- ── Section 2 desktop: pinned horizontal scroll gallery ───── -->
            <div
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
                            :style="{ width: COL_VW + 'vw', height: '66vh' }"
                        >
                            <img
                                :src="imgSrc(img)"
                                class="w-full h-full object-contain transition-transform duration-[1200ms] ease-out group-hover:scale-[1.04]"
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

            <!-- ── Section 3: Stats — left title rail, full-width rows, count-up on reveal ─── -->
            <section ref="statsSection" class="px-6 md:px-16 pt-28 md:pt-36 pb-28 md:pb-36">
                <div class="grid md:grid-cols-12 gap-12 md:gap-16">
                    <!-- left: title rail -->
                    <div class="md:col-span-4">
                        <div class="md:sticky md:top-32">
                            <span class="flex items-center gap-2 mb-7">
                                <span class="dot"></span>
                                <span class="text-[11px] tracking-[0.25em] uppercase font-medium text-black/45">By the numbers</span>
                            </span>
                            <h2 class="display text-black"
                                style="font-size: clamp(30px, 3.8vw, 52px); font-weight: 300; line-height: 1.06; letter-spacing: -0.02em;">
                                {{ props.settings.about_stats_title || 'A track record, measured.' }}
                            </h2>
                            <p class="mt-6 text-[14px] text-black/50 leading-relaxed max-w-xs">
                                {{ props.settings.about_stats_subtitle || 'Figures shaped by years of focused, deliberate work.' }}
                            </p>
                        </div>
                    </div>

                    <!-- right: 2x2 stat grid -->
                    <div class="md:col-span-8">
                        <div class="stat-grid grid grid-cols-1 sm:grid-cols-2 border-b border-black/10">
                            <div
                                v-for="(stat, i) in props.stats"
                                :key="i"
                                class="stat-cell group relative border-t border-black/10 py-10 md:py-12 sm:pr-8 md:pr-10"
                            >
                                <span class="flex items-center gap-2 mb-5">
                                    <span class="cell-dot"></span>
                                    <span class="display text-black/35 text-[12px] tabular-nums tracking-wide">{{ pad(i) }}</span>
                                </span>

                                <p class="display tabular-nums text-black leading-[0.85] mb-4"
                                   style="font-size: clamp(44px, 5vw, 72px); font-weight: 300; letter-spacing: -0.03em;">
                                    {{ displayStat(stat.value) }}
                                </p>

                                <p class="text-[13px] md:text-[14px] text-black/55 leading-relaxed max-w-xs">
                                    {{ stat.label }}
                                </p>

                                <!-- accent line that draws in on hover -->
                                <span class="stat-line"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Section 4: Warm beige editorial statement ─────────────── -->
            <section style="background: #efe9e1;" class="px-6 md:px-16 py-20 md:py-32">
                <div class="grid md:grid-cols-12 gap-8 md:gap-12">
                    <span class="md:col-span-3 text-[11px] tracking-[0.25em] uppercase" style="color: #9a8f7e;">Who we are</span>
                    <div class="md:col-span-9 max-w-3xl">
                        <p class="leading-relaxed" style="color: #2a2520; font-size: clamp(19px, 2.4vw, 30px); font-weight: 300; line-height: 1.5;">
                            {{ props.settings.about_beige_text_1 || 'Merisimo is a boutique web development agency specialising in digital products for the construction and real estate sector.' }}
                        </p>
                        <p class="mt-8 leading-loose" style="color: #6b6155; font-size: clamp(15px, 1.6vw, 18px);">
                            {{ props.settings.about_beige_text_2 || 'Our flagship product is an interactive floor plan plugin that lets buyers browse buildings, select apartments by floor and status, and view pricing — embedded directly in your website.' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- ── Section 5: Work with us CTA ───────────────────────────── -->
            <section class="cta pt-28 md:pt-44 pb-32 md:pb-56 px-6 md:px-16 flex flex-col items-center justify-center text-center">
                <span class="text-[11px] tracking-[0.25em] uppercase text-black/35 mb-8">Let's talk</span>
                <h2 class="display text-black mb-10" style="font-size: clamp(44px, 9vw, 128px); font-weight: 300; line-height: 0.95; letter-spacing: -0.03em;">
                    {{ props.settings.about_cta_title || 'Work with us' }}
                </h2>
                <a
                    href="/contact"
                    class="group inline-flex items-center gap-3 text-[11px] tracking-[0.25em] uppercase text-black/50 hover:text-black transition-colors duration-300"
                >
                    <span class="relative pb-1">
                        {{ props.settings.about_cta_link_text || 'Introduce yourself' }}
                        <span class="absolute left-0 -bottom-px h-px w-full bg-black/20"></span>
                        <span class="absolute left-0 -bottom-px h-px w-full bg-black origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500 ease-out"></span>
                    </span>
                    <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                </a>
            </section>

        </div>
    </AppLayout>
</template>

<style scoped>
/* hide scrollbar on the mobile swipe strip */
.no-bar::-webkit-scrollbar { display: none; }
.no-bar { -ms-overflow-style: none; scrollbar-width: none; }

/* stats grid: hairline vertical divider between the two columns (desktop) */
@media (min-width: 640px) {
    .stat-cell:nth-child(even) {
        padding-left: 2.5rem;
        border-left: 1px solid rgba(0, 0, 0, 0.1);
    }
}
.stat-cell {
    transition: padding-left 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.stat-cell .cell-dot {
    width: 5px;
    height: 5px;
    border-radius: 9999px;
    background-color: rgba(0, 0, 0, 0.2);
    transition: background-color 0.3s ease;
    display: inline-block;
    flex-shrink: 0;
}
.stat-cell:hover .cell-dot { background-color: #5DCAA5; }

/* the accent line that draws across the top border of a cell on hover */
.stat-line {
    position: absolute;
    left: 0;
    top: -1px;
    height: 1px;
    width: 100%;
    background-color: #5DCAA5;
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 1;
}
.stat-cell:hover .stat-line {
    transform: scaleX(1);
}

/* the gallery progress fill should track instantly with scroll */
.tabular-nums { font-variant-numeric: tabular-nums; }
</style>
