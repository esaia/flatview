<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import FaqSection from '@/Components/FaqSection.vue'

const props = defineProps({
    settings:   { type: Object, default: () => ({}) },
    stats:      { type: Array,  default: () => [] },
    valueprops: { type: Array,  default: () => [] },
    faq:        { type: Object, default: () => ({}) },
    gallery:    { type: Array,  default: () => [] },
})

/* ───────────────────────── Project gallery ───────────────────────── */
const galleryImages = computed(() =>
    props.gallery
        .map((item) => (typeof item === 'string' ? item : item?.image))
        .filter(Boolean)
        .map((path) => (path.startsWith('http') ? path : '/storage/' + path.replace(/^\/+/, ''))),
)

/*
 * An even two-up grid: same ratio, same rows, no offsets — staggering left
 * ragged whitespace between the frames. A photo that would sit alone on the
 * last row spans the full width as a banner instead.
 */
const frame = (i) => {
    const isLastAlone = i === galleryImages.value.length - 1 && i % 2 === 0

    return isLastAlone
        ? { span: 'md:col-span-2', ratio: '21 / 9' }
        : { span: '', ratio: '3 / 2' }
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
    if (statObserver) statObserver.disconnect()
})
</script>

<template>
    <AppLayout active-page="about">
        <div class="about-root relative overflow-x-hidden">

            <!-- Brand logo, top-right -->
            <Link href="/" class="absolute top-7 right-8 md:top-10 md:right-16 z-30 flex items-center gap-2 md:gap-2.5 select-none cursor-pointer">
                <img src="/logo.svg" alt="FlatView" class="h-5 md:h-6 w-auto" draggable="false" />
                <span class="text-black text-sm md:text-base font-semibold tracking-[0.2em] uppercase">FlatView</span>
            </Link>

            <!-- ── Section 1: Hero statement ─────────────────────────────── -->
            <!-- Roomier than a plain content block, but nowhere near the full
                 viewport: there is only a kicker and a headline to carry. -->
            <section class="relative w-full max-w-[1400px] mx-auto flex flex-col justify-center px-6 md:px-16 pt-24 md:pt-32 pb-20 md:pb-32 md:min-h-[58vh]">
                <span class="reveal kicker flex items-center gap-2 text-[11px] tracking-[0.25em] uppercase text-black/45 mb-7" style="--d: 0s;">
                    <span class="dot"></span> About Flatview
                </span>

                <h1 class="reveal display text-black max-w-5xl" style="--d: .08s; font-size: clamp(38px, 6.4vw, 88px); line-height: 1.04; font-weight: 300; letter-spacing: -0.02em;">
                    {{ props.settings.about_headline || 'We build digital tools for the built world.' }}
                </h1>

            </section>

            <!-- ── Section 2: Warm beige editorial statement ─────────────── -->
            <section style="background: #efe9e1;" class="px-6 md:px-16 py-20 md:py-32">
                <div class="max-w-[1400px] mx-auto grid md:grid-cols-12 gap-8 md:gap-12">
                    <span class="md:col-span-3 text-[11px] tracking-[0.25em] uppercase" style="color: #9a8f7e;">Who we are</span>
                    <div class="md:col-span-9 max-w-3xl">
                        <p class="leading-relaxed" style="color: #2a2520; font-size: clamp(19px, 2.4vw, 30px); font-weight: 300; line-height: 1.5;">
                            {{ props.settings.about_beige_text_1 || 'Flatview is a boutique web development agency specialising in digital products for the construction and real estate sector.' }}
                        </p>
                        <p class="mt-8 leading-loose" style="color: #6b6155; font-size: clamp(15px, 1.6vw, 18px);">
                            {{ props.settings.about_beige_text_2 || 'Our flagship product is an interactive floor plan plugin that lets buyers browse buildings, select apartments by floor and status, and view pricing — embedded directly in your website.' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- ── Section 3: Project gallery — even two-up grid ────────── -->
            <section v-if="galleryImages.length" class="max-w-[1400px] mx-auto px-6 md:px-16 pt-24 md:pt-36">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                    <figure
                        v-for="(src, i) in galleryImages"
                        :key="src"
                        class="group overflow-hidden bg-[#efe9e1]"
                        :class="frame(i).span"
                        :style="{ aspectRatio: frame(i).ratio }"
                    >
                        <img
                            :src="src"
                            alt=""
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full object-cover transition-transform duration-[1400ms] ease-out group-hover:scale-[1.04]"
                            draggable="false"
                        />
                    </figure>
                </div>
            </section>

            <!-- ── Section 4: Stats — left title rail, full-width rows, count-up on reveal ─── -->
            <section ref="statsSection" class="max-w-[1400px] mx-auto px-6 md:px-16 pt-28 md:pt-36 pb-28 md:pb-36">
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

            <!-- ── Section 4: Why Flatview — value props ─────────────────── -->
            <section v-if="props.valueprops.length" class="max-w-[1400px] mx-auto px-6 md:px-16 pt-24 md:pt-36">
                <p class="flex items-center gap-2 mb-8 md:mb-10">
                    <span class="dot"></span>
                    <span class="text-[11px] tracking-[0.25em] uppercase font-medium text-black/45">
                        {{ props.settings.about_valueprops_kicker || 'Why Flatview?' }}
                    </span>
                </p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-0 border-t border-black/10">
                    <div
                        v-for="prop in props.valueprops"
                        :key="prop.label"
                        class="py-6 md:py-8 pr-6 md:pr-10 border-b border-black/10 md:border-b-0"
                    >
                        <h3 class="display text-lg text-black mb-2.5 md:mb-3 leading-snug" style="font-weight: 400;">{{ prop.label }}</h3>
                        <p class="text-sm text-black/45 font-light leading-relaxed">{{ prop.detail }}</p>
                    </div>
                </div>
            </section>

            <FaqSection
                :kicker="props.faq.kicker"
                :headline="props.faq.headline"
                :intro="props.faq.intro"
                :items="props.faq.items ?? []"
            />

            <!-- ── Section 6: Work with us CTA ───────────────────────────── -->
            <section class="cta relative bg-[#0e0e0e] text-white overflow-hidden">
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

                <div class="relative max-w-[1400px] mx-auto pt-28 md:pt-44 pb-32 md:pb-56 px-6 md:px-16 flex flex-col items-center justify-center text-center">
                    <span class="text-[11px] tracking-[0.25em] uppercase text-white/40 mb-8">Let's talk</span>
                    <h2 class="display mb-10" style="font-size: clamp(44px, 9vw, 128px); font-weight: 300; line-height: 0.95; letter-spacing: -0.03em;">
                        {{ props.settings.about_cta_title || 'Work with us' }}
                    </h2>
                    <a
                        href="/contact"
                        class="group inline-flex items-center gap-3 text-[11px] tracking-[0.25em] uppercase text-white/70 hover:text-white transition-colors duration-300"
                    >
                        <span class="relative pb-1">
                            {{ props.settings.about_cta_link_text || 'Introduce yourself' }}
                            <span class="absolute left-0 -bottom-px h-px w-full bg-white/25"></span>
                            <span class="absolute left-0 -bottom-px h-px w-full bg-white origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500 ease-out"></span>
                        </span>
                        <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                    </a>
                </div>
            </section>

        </div>
    </AppLayout>
</template>

<style scoped>
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
