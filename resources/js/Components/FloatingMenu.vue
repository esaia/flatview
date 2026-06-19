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
const currentPath = computed(() => new URL(page.url, window.location.origin).pathname)

const menuOpen = ref(false)
const buttonHovered = ref(false)
const navScrollRef = ref(null)
const navTrackRef = ref(null)
let rafId = null
let cursorX = 0
let containerW = 0
let mouseInNav = false
let currentX = 0  // current (lerped) track translateX
let targetX = 0   // target translateX from cursor position
let prevX = 0     // previous currentX, for velocity → skew
let skew = 0

function navigate(item) {
    closeMenu()
    if (item.external) {
        window.open(item.href, '_blank', 'noopener,noreferrer')
        return
    }
    router.visit(item.href)
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

        // Velocity-driven skew (lerped) for an elastic, fluid lean.
        const velocity = currentX - prevX
        prevX = currentX
        const targetSkew = Math.max(-7, Math.min(7, velocity * 0.35))
        skew += (targetSkew - skew) * 0.06

        track.style.transform = `translate3d(${-currentX}px, 0, 0) skewX(${skew}deg)`
    }
    rafId = requestAnimationFrame(scrollLoop)
}

async function openMenu() {
    menuOpen.value = true
    emit('update:menuOpen', true)
    document.body.style.overflow = 'hidden'
    await nextTick()
    currentX = 0
    targetX = 0
    prevX = 0
    skew = 0
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

function toggleMenu() { menuOpen.value ? closeMenu() : openMenu() }

onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => {
    document.removeEventListener('keydown', onKey)
    if (rafId) cancelAnimationFrame(rafId)
    document.body.style.overflow = ''
})
</script>

<template>
    <!-- ── Slide-up nav panel ──────────────────────────────────────── -->
    <Transition name="menu-panel">
        <div
            v-if="menuOpen"
            class="fixed bottom-0 left-0 right-0 z-40 flex flex-col bg-black"
            style="height: 50vh;"
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
                        class="menu-col flex-shrink-0 flex flex-col justify-center md:justify-start px-2.5 py-5 md:px-3 md:py-8 cursor-pointer group transition-colors duration-200 hover:bg-white/[0.03]"
                        :style="{ '--col-delay': `${0.18 + i * 0.07}s` }"
                        @click.stop="navigate(item)"
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

                        <!-- 16:9 preview card -->
                        <div
                            class="w-full flex-shrink-0 relative overflow-hidden transition-colors duration-200"
                            style="aspect-ratio: 16 / 9; background: #111;"
                        >
                            <img
                                v-if="item.image"
                                :src="`/storage/${item.image}`"
                                class="w-full h-full object-cover opacity-70 transition-opacity duration-200 group-hover:opacity-100"
                            />
                            <img
                                v-else
                                :src="`https://picsum.photos/seed/${item.label}/640/360`"
                                class="w-full h-full object-cover opacity-50 transition-opacity duration-200 group-hover:opacity-80"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="flex items-center justify-between px-4 md:px-8 py-3 md:py-4 border-t border-white/10 flex-shrink-0">
                <div></div>
                <div class="hidden md:flex items-center gap-6">
                    <span class="text-white/30 text-[10px] tracking-widest uppercase select-none">Follow us</span>
                    <a href="#" class="text-white/50 text-[10px] tracking-widest uppercase hover:text-white transition-colors duration-200">Instagram</a>
                    <a href="#" class="text-white/50 text-[10px] tracking-widest uppercase hover:text-white transition-colors duration-200">LinkedIn</a>
                </div>
            </div>
        </div>
    </Transition>

    <!-- ── Circle button: always fixed, never moves ───────────────── -->
    <button
        @click="toggleMenu"
        @mouseenter="buttonHovered = true"
        @mouseleave="buttonHovered = false"
        class="fixed bottom-4 md:bottom-16 left-1/2 -translate-x-1/2 z-50 group cursor-pointer w-12 h-12 md:w-[85.51px] md:h-[85.51px]"
        :style="{
            opacity: (alwaysVisible || buttonHovered || menuOpen) ? 1 : 0,
            transition: 'opacity 0.2s ease',
        }"
        :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
    >
        <!-- Circle -->
        <div
            class="absolute inset-0 rounded-full transition-transform duration-500 group-hover:scale-90"
            style="background-color: #5DCAA5; aspect-ratio: 1/1;"
        ></div>

        <!-- Vertical label: rotated 90° CW, hidden on mobile -->
        <div class="btn-label-clip hidden md:block" style="left: calc(100% + 10px); width: 18px; height: 52px;">
            <Transition name="btn-label">
                <div :key="menuOpen ? 'close' : 'menu'" class="btn-label-slide">
                    <span class="btn-label-text" :class="menuOpen ? 'text-white' : 'text-black'">
                        {{ menuOpen ? 'Close' : 'Menu' }}
                    </span>
                </div>
            </Transition>
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

/*
 * Staggered column reveal — each column fades + rises in sequence after the
 * panel slides up. Delay is set per-column via the inline --col-delay custom
 * property (inherits to the image, which wipes in with the same timing).
 * Columns mount fresh on every open (panel is v-if'd), so this auto-plays.
 */
.menu-col {
    /* Mobile: one card fills most of the width with the next peeking in. */
    width: clamp(260px, 78vw, 360px);
    opacity: 0;
    animation: menu-col-in 0.65s var(--menu-ease) both;
    animation-delay: var(--col-delay, 0s);
}
/* Desktop: narrower, cursor-scrubbed columns. */
@media (min-width: 768px) {
    .menu-col { width: clamp(240px, 28vw, 460px); }
}
.menu-col img {
    animation: menu-img-in 0.9s var(--menu-ease) both;
    animation-delay: var(--col-delay, 0s);
}
@keyframes menu-col-in {
    from { opacity: 0; transform: translateY(34px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes menu-img-in {
    from { clip-path: inset(0 100% 0 0); transform: scale(1.08); }
    to   { clip-path: inset(0 0 0 0);    transform: scale(1); }
}

@media (prefers-reduced-motion: reduce) {
    .menu-col, .menu-col img { animation: none; opacity: 1; }
}

/*
 * Panel slide — locked to the page content-shift in AppLayout via shared
 * CSS vars (resources/css/app.css). Symmetric enter/leave so open and close
 * read as one motion.
 */
.menu-panel-enter-active,
.menu-panel-leave-active { transition: transform var(--menu-duration) var(--menu-ease); }
.menu-panel-enter-from,
.menu-panel-leave-to     { transform: translateY(100%); }

/*
 * Label layout
 * ─────────────────────────────────────────────────────────────────
 * .btn-label-clip   – overflow:hidden window; sized for the *rotated*
 *                     text (width ≈ line-height, height ≈ text width).
 *                     Positioned to the right of the circle.
 * .btn-label-slide  – absolutely fills the clip window; this is the
 *                     element that transitions (translateY in screen-Y).
 * .btn-label-text   – the word; rotate(90deg) makes it read bottom→top.
 *                     Rotation is purely static — never animated.
 */
.btn-label-clip {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);   /* vertically centres clip on circle */
    overflow: hidden;
    /* width / height set via inline style */
}

.btn-label-slide {
    position: absolute;
    inset: 0;                      /* fills clip window */
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-label-text {
    display: block;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    white-space: nowrap;
    line-height: 1;
    transform: rotate(90deg);      /* static — reads bottom-to-top */
}

/* Enter from screen-top ↓ */
.btn-label-enter-active {
    transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                opacity   0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.btn-label-enter-from { transform: translateY(-100%); opacity: 0; }
.btn-label-enter-to   { transform: translateY(0);     opacity: 1; }

/* Exit to screen-bottom ↓ */
.btn-label-leave-active {
    transition: transform 0.22s cubic-bezier(0.55, 0, 1, 0.45),
                opacity   0.22s cubic-bezier(0.55, 0, 1, 0.45);
}
.btn-label-leave-from { transform: translateY(0);     opacity: 1; }
.btn-label-leave-to   { transform: translateY(100%);  opacity: 0; }
</style>
