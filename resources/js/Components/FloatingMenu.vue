<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    // When true the circle button is always visible (Home); otherwise it
    // reveals on hover / while the menu is open (inner pages).
    alwaysVisible: { type: Boolean, default: false },
})

const emit = defineEmits(['update:menuOpen'])

const page = usePage()
const menuItems = computed(() => page.props.menuItems || [])
const socialLabel = computed(() => page.props.siteSocials?.label || 'Follow us')
const socialLinks = computed(() => page.props.siteSocials?.links || [])
const currentPath = computed(() => new URL(page.url, window.location.origin).pathname)

const menuOpen = ref(false)
const buttonHovered = ref(false)

// Fine pointer = mouse/trackpad → cursor-scrub. Touch → native finger scroll,
// so the rAF scrub loop must NOT run (it would fight/no-op against the native
// scroll and just thrash style recalcs every frame).
const isFinePointer = typeof window !== 'undefined'
    && typeof window.matchMedia === 'function'
    && window.matchMedia('(hover: hover) and (pointer: fine)').matches

// Arc-text ring content: repeated word stretched to the full circumference
// (textLength) so it reads as a seamless ring around the circle's rim.
const ringText = computed(() => `${(menuOpen.value ? 'Close' : 'Menu').toUpperCase()} • `.repeat(3))
const navScrollRef = ref(null)
const navTrackRef = ref(null)
let rafId = null
let cursorX = 0
let containerW = 0
let mouseInNav = false
let currentX = 0  // current (lerped) track translateX
let targetX = 0   // target translateX from cursor position

function goHome() {
    closeMenu()
    router.visit('/')
}

function navigate(item) {
    closeMenu()
    if (item.external) {
        window.open(item.href, '_blank', 'noopener,noreferrer')
        return
    }
    router.visit(item.href)
}

// ── Touch tap vs. scroll ──────────────────────────────────────────────
// On touch, a horizontal swipe to scroll the columns cancels the synthesized
// `click`, so plain @click navigation silently fails after a scroll. We detect
// a genuine tap (little movement) on touchend and navigate ourselves, then
// suppress the ghost click that may follow so we never navigate twice.
const TAP_MOVE_TOLERANCE = 10 // px of movement still counted as a tap
let cardTouchX = 0
let cardTouchY = 0
let cardTouchMoved = false
let lastTouchNav = 0

function onCardTouchStart(e) {
    const t = e.touches[0]
    cardTouchX = t.clientX
    cardTouchY = t.clientY
    cardTouchMoved = false
}

function onCardTouchMove(e) {
    const t = e.touches[0]
    if (Math.abs(t.clientX - cardTouchX) > TAP_MOVE_TOLERANCE
        || Math.abs(t.clientY - cardTouchY) > TAP_MOVE_TOLERANCE) {
        cardTouchMoved = true
    }
}

function onCardTouchEnd(item) {
    if (cardTouchMoved) return // was a scroll, not a tap
    lastTouchNav = Date.now()
    navigate(item)
}

function onCardClick(item) {
    // Ignore the ghost click that trails a touch tap we already handled.
    if (Date.now() - lastTouchNav < 700) return
    navigate(item)
}

function onKey(e) {
    if (e.key === 'Escape') closeMenu()
}

function onNavMouseMove(e) {
    if (!navScrollRef.value) return
    const rect = navScrollRef.value.getBoundingClientRect()
    cursorX = e.clientX - rect.left
    containerW = rect.width
    mouseInNav = true
}

function onNavMouseLeave() {
    mouseInNav = false
    targetX = 0 // glide the list back to the start when the cursor leaves
}

const SCROLL_EASE = 0.06 // 0..1 — lower = smoother/floatier scrub lag (GSAP-style)
const SCROLL_START = 0.30 // cursor below this (of width) → list at the start
const SCROLL_END = 0.70   // cursor above this → list at the end

function scrollLoop() {
    const el = navScrollRef.value
    const track = navTrackRef.value
    if (el && track) {
        if (mouseInNav && containerW > 0) {
            // Map the 20%–80% cursor band → full range, with edge margins.
            const raw = cursorX / containerW
            const ratio = Math.max(0, Math.min(1, (raw - SCROLL_START) / (SCROLL_END - SCROLL_START)))
            const maxTranslate = Math.max(0, track.scrollWidth - el.clientWidth)
            targetX = ratio * maxTranslate
        }

        // Scrub: ease the whole parent track toward the target translateX.
        currentX += (targetX - currentX) * SCROLL_EASE

        track.style.transform = `translate3d(${-currentX}px, 0, 0)`
    }
    rafId = requestAnimationFrame(scrollLoop)
}

async function openMenu() {
    menuOpen.value = true
    emit('update:menuOpen', true)
    document.body.style.overflow = 'hidden'
    await nextTick()
    // Always reopen scrolled to the first column.
    if (navScrollRef.value) navScrollRef.value.scrollLeft = 0
    // Cursor-scrub is mouse-only; on touch the columns scroll natively.
    if (!isFinePointer) return
    currentX = 0
    targetX = 0
    if (rafId) cancelAnimationFrame(rafId)
    rafId = requestAnimationFrame(scrollLoop)
}

function closeMenu() {
    menuOpen.value = false
    emit('update:menuOpen', false)
    document.body.style.overflow = ''
    mouseInNav = false
    if (rafId) { cancelAnimationFrame(rafId); rafId = null }
}

// Debounce the toggle so a tap that lands as two events on touch (e.g. a
// touch-driven event plus a trailing ghost click) can't open then instantly
// close the panel.
let lastToggle = 0
function toggleMenu() {
    const now = Date.now()
    if (now - lastToggle < 400) return
    lastToggle = now
    menuOpen.value ? closeMenu() : openMenu()
}

onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => {
    document.removeEventListener('keydown', onKey)
    if (rafId) cancelAnimationFrame(rafId)
    document.body.style.overflow = ''
})
</script>

<template>
    <!-- ── Slide-up nav panel ──────────────────────────────────────── -->
    <!--
         The panel is ALWAYS mounted (content included) and slides via a
         reactive inline-style transition — identical mechanism, duration and
         easing to the page content-shift in AppLayout. This keeps the two
         motions in perfect lockstep (a Vue <Transition> here would start a
         frame late and briefly expose a white gap between the page bottom and
         the panel top) and lets the items slide down with the panel on close
         instead of vanishing instantly.
    -->
    <div
        class="fixed bottom-0 left-0 right-0 z-40 flex flex-col bg-black h-[min(400px,82vh)] md:h-[min(460px,92vh)] 2xl:h-[min(500px,92vh)]"
        :style="{
            transform: menuOpen ? 'translateY(0)' : 'translateY(100%)',
            transition: 'transform var(--menu-duration) var(--menu-ease)',
            pointerEvents: menuOpen ? 'auto' : 'none',
        }"
    >
        <!-- Scrollable columns — always horizontal (desktop: cursor-driven; mobile: touch) -->
        <div
            ref="navScrollRef"
            class="menus-scroll flex flex-1 min-h-0"
            style="cursor: ew-resize; -webkit-overflow-scrolling: touch;"
            @mousemove="onNavMouseMove"
            @mouseleave="onNavMouseLeave"
        >
            <div ref="navTrackRef" class="flex h-full px-6 md:px-12" style="will-change: transform; transform-origin: center;">
                <div
                    v-for="(item, i) in menuItems"
                    :key="item.id"
                    class="menu-col flex-shrink-0 flex flex-col justify-center md:justify-start px-2.5 py-5 md:px-3 md:py-8 cursor-pointer group"
                    @click.stop="onCardClick(item)"
                    @touchstart.passive="onCardTouchStart"
                    @touchmove.passive="onCardTouchMove"
                    @touchend="onCardTouchEnd(item)"
                >
                    <!-- Page label — dot slides in on hover, always shown when active -->
                    <div class="flex items-center mb-4 md:mb-5 flex-shrink-0">
                        <span
                            class="rounded-full flex-shrink-0 transition-all duration-300 ease-out"
                            :class="currentPath === item.href
                                ? 'w-1.5 mr-2 opacity-100'
                                : 'w-0 mr-0 opacity-0 group-hover:w-1.5 group-hover:mr-2 group-hover:opacity-100'"
                            style="height: 6px; background-color: #5DCAA5;"
                        ></span>
                        <span class="text-white text-[11px] md:text-sm font-semibold tracking-[0.2em] uppercase">{{ item.label }}</span>
                    </div>

                    <!-- preview card -->
                    <div
                        class="menu-card w-full flex-shrink-0 relative overflow-hidden rounded-sm transition-colors duration-200"
                        style="aspect-ratio: 16 / 9; background: #111;"
                    >
                        <!-- lazy: the panel is always in the DOM but off-screen
                             until opened, so these only fetch when revealed —
                             they must never weigh on initial page load. -->
                        <img
                            v-if="item.image"
                            :src="`/storage/${item.image}`"
                            :alt="item.label"
                            width="640"
                            height="360"
                            loading="lazy"
                            decoding="async"
                            class="w-full h-full object-cover opacity-90 transition-opacity duration-200 group-hover:opacity-100"
                        />
                        <img
                            v-else
                            :src="`https://picsum.photos/seed/${item.label}/640/360`"
                            :alt="item.label"
                            width="640"
                            height="360"
                            loading="lazy"
                            decoding="async"
                            class="w-full h-full object-cover opacity-80 transition-opacity duration-200 group-hover:opacity-100"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="flex items-center justify-between px-4 md:px-8 py-3 md:py-4 border-t border-white/10 flex-shrink-0">
            <div
                class="flex items-center gap-2 md:gap-2.5 select-none cursor-pointer"
                @click.stop="goHome"
            >
                <img
                    src="/logo.svg"
                    alt="FlatView"
                    class="h-5 md:h-6 w-auto"
                    style="filter: brightness(0) invert(1);"
                    draggable="false"
                />
                <span class="text-white text-sm md:text-base font-semibold tracking-[0.2em] uppercase">FlatView</span>
            </div>
            <div class="flex items-center gap-6 md:gap-10">
                <div class="flex items-center gap-3 md:gap-4">
                    <span class="hidden md:inline text-white/30 text-[10px] tracking-widest uppercase select-none">Partner</span>
                    <a
                        href="https://www.ireplugin.com/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="opacity-60 hover:opacity-100 transition-opacity duration-200"
                        aria-label="IREP Plugin"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" class="h-5 md:h-6 w-auto">
                            <g transform="translate(0,500) scale(0.1,-0.1)" fill="#ffffff" stroke="#ffffff">
                                <path d="M2750 4347 c-521 -241 -603 -282 -621 -312 -18 -29 -19 -93 -19 -1656 0 -1449 -2 -1628 -15 -1639 -23 -19 -180 15 -200 44 -13 18 -15 206 -15 1404 0 1244 -2 1385 -16 1407 -20 31 -70 48 -109 36 -73 -22 -692 -323 -713 -347 l-22 -25 0 -979 c0 -969 0 -979 -20 -990 -13 -7 -26 -7 -38 -1 -29 15 -182 179 -264 282 -81 101 -102 115 -156 100 -17 -5 -117 -75 -222 -156 -105 -80 -199 -145 -208 -144 -55 5 -82 -16 -82 -64 0 -40 9 -54 40 -65 42 -15 78 4 91 49 11 34 34 56 197 180 101 78 192 143 201 145 22 6 27 1 99 -91 94 -120 267 -292 300 -298 15 -3 40 0 57 7 64 27 60 -46 65 1035 l5 986 345 162 c189 90 350 163 357 163 6 0 19 -7 27 -16 14 -14 16 -155 16 -1399 0 -993 3 -1391 11 -1408 14 -32 35 -42 138 -67 94 -24 128 -20 162 16 l24 26 3 1632 c2 1538 3 1634 20 1648 13 12 619 301 680 325 7 3 22 -3 32 -12 20 -18 20 -42 20 -1792 0 -1957 -5 -1809 62 -1832 31 -11 100 -1 139 20 51 27 49 -10 49 1126 0 582 4 1063 8 1069 9 13 668 234 699 234 12 0 27 -9 33 -19 7 -13 10 -323 10 -934 l0 -914 25 -26 c32 -31 71 -39 110 -22 37 15 173 156 279 288 43 53 84 97 92 97 25 0 414 -301 414 -320 0 -57 85 -86 122 -41 41 51 8 116 -58 114 -35 -1 -57 12 -234 146 -108 81 -208 151 -223 155 -44 11 -82 -15 -153 -104 -98 -124 -202 -236 -247 -267 l-39 -27 -19 23 c-18 22 -19 57 -19 938 l0 915 -29 29 c-18 18 -40 29 -57 29 -26 0 -632 -194 -707 -227 -71 -31 -67 43 -67 -1132 0 -1174 5 -1083 -66 -1097 -27 -5 -40 -2 -52 10 -16 15 -17 152 -19 1789 -2 1403 -5 1777 -15 1796 -11 21 -61 51 -85 51 -4 0 -60 -24 -123 -53z" stroke-width="60"/>
                                <path d="M2496 3648 c-22 -31 -20 -71 4 -93 20 -18 20 -31 20 -1417 0 -1385 0 -1400 -20 -1425 -41 -53 -14 -113 50 -113 64 0 91 60 50 113 -20 25 -20 40 -20 1422 0 1382 0 1397 20 1422 11 14 20 36 20 49 0 58 -92 89 -124 42z" stroke-width="60"/>
                                <path d="M1430 2973 c-51 -19 -68 -84 -30 -118 20 -18 20 -31 20 -967 0 -935 0 -948 -20 -968 -11 -11 -20 -33 -20 -50 0 -65 79 -94 119 -44 26 34 27 64 1 92 -20 21 -20 35 -20 968 0 931 0 946 20 971 11 14 20 36 20 48 0 42 -52 82 -90 68z" stroke-width="60"/>
                                <path d="M3487 2462 c-23 -26 -22 -78 3 -99 20 -17 20 -30 20 -643 0 -613 0 -626 -20 -643 -38 -33 -19 -115 28 -118 56 -4 78 9 87 53 5 23 2 38 -14 59 -21 28 -21 34 -21 650 0 606 0 621 20 646 25 33 25 54 -1 87 -25 32 -77 36 -102 8z" stroke-width="60"/>
                            </g>
                        </svg>
                    </a>
                </div>
                <div v-if="socialLinks.length" class="flex items-center gap-3 md:gap-6">
                    <span class="hidden md:inline text-white/30 text-[10px] tracking-widest uppercase select-none">{{ socialLabel }}</span>
                    <a
                        v-for="(social, i) in socialLinks"
                        :key="i"
                        :href="social.url || '#'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-white/50 text-[10px] tracking-widest uppercase hover:text-white transition-colors duration-200"
                    >{{ social.label }}</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Circle button: always fixed, never moves ───────────────── -->
    <button
        @click="toggleMenu"
        @mouseenter="buttonHovered = true"
        @mouseleave="buttonHovered = false"
        class="menu-btn fixed bottom-[calc(1.25rem+env(safe-area-inset-bottom))] md:bottom-16 left-1/2 -translate-x-1/2 z-50 group cursor-pointer w-14 h-14 md:w-[85.51px] md:h-[85.51px]"
        :style="{
            opacity: (alwaysVisible || buttonHovered || menuOpen) ? 1 : 0,
            transition: 'opacity 0.2s ease',
        }"
        :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
    >
        <!-- Circle (stays full-size so the arc text always has its margin).
             group-active scale gives touch a tactile press in place of hover. -->
        <div
            class="absolute inset-0 rounded-full transition-transform duration-500 group-hover:scale-[0.97] group-active:scale-[0.9] group-active:duration-150"
            style="background-color: #5DCAA5; aspect-ratio: 1/1;"
        ></div>

        <!-- Mobile affordance — a two-line menu mark that morphs into an X when
             open, so the circle reads as a labelled control with no hover. The
             bar color flips dark→white on open, mirroring the desktop arc-text. -->
        <div
            class="menu-icon md:hidden absolute inset-0 pointer-events-none"
            :class="{ 'menu-icon--open': menuOpen }"
            aria-hidden="true"
        >
            <span class="menu-icon__bar menu-icon__bar--top"></span>
            <span class="menu-icon__bar menu-icon__bar--bottom"></span>
        </div>

        <!-- Arc-text ring — curves "MENU" around the rim; reveals + orbits on
             hover / while the menu is open. Desktop only (no hover on touch). -->
        <div
            class="menu-ring hidden md:block absolute inset-0 pointer-events-none"
            :class="{ 'menu-ring--active': buttonHovered || menuOpen }"
            aria-hidden="true"
        >
            <svg class="menu-ring__spin h-full w-full" viewBox="0 0 100 100">
                <defs>
                    <!-- Full circle, r=37, starting at top → text reads upright clockwise.
                         Kept inside the rim so glyphs stay on green even at scale-90. -->
                    <path id="menuRingPath" fill="none" d="M 50,13 A 37,37 0 1 1 49.99,13" />
                </defs>
                <text class="menu-ring__text" :fill="menuOpen ? '#ffffff' : '#0b0b0b'">
                    <textPath
                        href="#menuRingPath"
                        startOffset="0"
                        textLength="232.5"
                        lengthAdjust="spacingAndGlyphs"
                    >{{ ringText }}</textPath>
                </text>
            </svg>
        </div>
    </button>
</template>

<style>
/* Touch devices: native horizontal finger scroll. */
.menus-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}
.menus-scroll::-webkit-scrollbar { display: none; }

/* Mouse/pointer devices: movement is transform-driven (scrub), so disable
   native scroll to avoid fighting the translateX. */
@media (hover: hover) and (pointer: fine) {
    .menus-scroll { overflow-x: hidden; }
}

/* Menu columns — no entrance animation; they slide in/out with the panel. */
.menu-col {
    /* Mobile: one card fills most of the width with the next peeking in. */
    width: clamp(260px, 78vw, 360px);
}
/* Desktop: fixed-width, cursor-scrubbed columns. Fixed (not vw) so the 16:9
   cards keep a constant size at any viewport width — narrower screens scroll
   horizontally instead of shrinking the cards. */
@media (min-width: 768px) {
    .menu-col { width: 360px; }
    /* Fixed 360×192 card on laptops (overrides the mobile aspect-ratio). */
    .menu-card { aspect-ratio: auto; height: 192px; }
}
/* Large desktops: slightly bigger cards. */
@media (min-width: 1536px) {
    .menu-col { width: 400px; }
    .menu-card { height: 214px; }
}

/*
 * Arc-text ring
 * ─────────────────────────────────────────────────────────────────
 * .menu-ring        – reveal wrapper; fades + scales the ring in when
 *                     the button is hovered or the menu is open.
 * .menu-ring__spin  – the <svg>; orbits continuously for a living feel.
 * .menu-ring__text  – the curved word stamped along the circle path.
 */
.menu-ring {
    opacity: 0;
    transform: scale(0.82) rotate(-12deg);
    transform-origin: 50% 50%;
    transition: opacity 0.4s ease,
                transform 0.6s cubic-bezier(0.22, 0.9, 0.27, 1);
    will-change: opacity, transform;
}
.menu-ring--active {
    opacity: 1;
    transform: scale(1) rotate(0deg);
}

.menu-ring__spin {
    transform-origin: 50% 50%;
    animation: menu-ring-spin 16s linear infinite;
}
@keyframes menu-ring-spin {
    to { transform: rotate(360deg); }
}

.menu-ring__text {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    paint-order: stroke;
}

/*
 * Mobile menu mark (md:hidden)
 * ─────────────────────────────────────────────────────────────────
 * Two centered bars sit 5px above/below the circle's middle as a quiet
 * "menu" glyph, then rotate onto each other to form an X when the panel
 * opens — the touch counterpart to the desktop MENU/CLOSE arc text.
 */
.menu-icon__bar {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 2px;
    border-radius: 2px;
    background-color: #0b0b0b;
    transition: transform 0.35s cubic-bezier(0.22, 0.9, 0.27, 1),
                background-color 0.3s ease;
}
.menu-icon__bar--top { transform: translate(-50%, -50%) translateY(-5px) rotate(0deg); }
.menu-icon__bar--bottom { transform: translate(-50%, -50%) translateY(5px) rotate(0deg); }
.menu-icon--open .menu-icon__bar { background-color: #ffffff; }
.menu-icon--open .menu-icon__bar--top { transform: translate(-50%, -50%) translateY(0) rotate(45deg); }
.menu-icon--open .menu-icon__bar--bottom { transform: translate(-50%, -50%) translateY(0) rotate(-45deg); }

/* Keyboard focus only — never show the ring on a mouse/touch press. */
.menu-btn:focus { outline: none; }
.menu-btn:focus-visible {
    outline: 2px solid #5DCAA5;
    outline-offset: 4px;
    border-radius: 9999px;
}

@media (prefers-reduced-motion: reduce) {
    .menu-icon__bar { transition: background-color 0.2s ease; }
}

@media (prefers-reduced-motion: reduce) {
    .menu-ring__spin { animation: none; }
}
</style>
