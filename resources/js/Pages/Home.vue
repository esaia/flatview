<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

// ── Slideshow ────────────────────────────────────────────────────────────────
const slides = [
    { id: 1, label: 'Tbilisi Towers',     category: 'Real Estate · 2024',       shade: '#111111' },
    { id: 2, label: 'SkyView Residences', category: 'Floor Plan Plugin · 2024', shade: '#161616' },
    { id: 3, label: 'BuildCore',          category: 'Construction · 2024',       shade: '#0d0d0d' },
]
const currentSlide = ref(0)
let slideTimer = null

// ── Menu ─────────────────────────────────────────────────────────────────────
const menuOpen = ref(false)
const navScrollRef = ref(null)
let rafId = null
let cursorX = 0
let containerW = 0
let mouseInNav = false

const navItems = [
    { key: 'home',     label: 'Home',     href: '/' },
    { key: 'work',     label: 'Work',     href: '/work' },
    { key: 'services', label: 'Services', href: '/services' },
    { key: 'about',    label: 'About',    href: '/about' },
    { key: 'contact',  label: 'Contact',  href: '/contact' },
]

function navigate(href) {
    menuOpen.value = false
    mouseInNav = false
    if (rafId) { cancelAnimationFrame(rafId); rafId = null }
    router.visit(href)
}

function onNavMouseMove(e) {
    if (!navScrollRef.value) return
    const rect = navScrollRef.value.getBoundingClientRect()
    cursorX = e.clientX - rect.left
    containerW = rect.width
    mouseInNav = true
}

function onNavMouseLeave() { mouseInNav = false }

function scrollLoop() {
    if (mouseInNav && navScrollRef.value && containerW > 0) {
        const normalised = cursorX / containerW
        const deadzone = 0.15
        let velocity = 0
        if (normalised > 0.5 + deadzone) {
            velocity = (normalised - 0.5 - deadzone) / (0.5 - deadzone) * 8
        } else if (normalised < 0.5 - deadzone) {
            velocity = (normalised - 0.5 + deadzone) / (0.5 - deadzone) * 8
        }
        navScrollRef.value.scrollLeft += velocity
    }
    rafId = requestAnimationFrame(scrollLoop)
}

async function openMenu() {
    menuOpen.value = true
    await nextTick()
    if (rafId) cancelAnimationFrame(rafId)
    rafId = requestAnimationFrame(scrollLoop)
}

function closeMenu() {
    menuOpen.value = false
    mouseInNav = false
    if (rafId) { cancelAnimationFrame(rafId); rafId = null }
}

function toggleMenu() {
    menuOpen.value ? closeMenu() : openMenu()
}

function onKey(e) {
    if (e.key === 'Escape') closeMenu()
}

onMounted(() => {
    slideTimer = setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % slides.length
    }, 4000)
    document.addEventListener('keydown', onKey)
})

onUnmounted(() => {
    clearInterval(slideTimer)
    document.removeEventListener('keydown', onKey)
    if (rafId) cancelAnimationFrame(rafId)
})
</script>

<template>
    <div class="relative h-screen overflow-hidden">

        <!-- ── Main content: slides up when menu opens ────────────────── -->
        <div
            class="absolute inset-0 flex flex-col md:flex-row select-none"
            :style="{
                transform: menuOpen ? 'translateY(-25vh)' : 'translateY(0)',
                transition: 'transform 0.7s cubic-bezier(0.76, 0, 0.24, 1)'
            }"
        >
            <!-- Image panel -->
            <div class="h-[45vh] md:h-full w-full md:w-1/2 flex-shrink-0 relative overflow-hidden bg-black">
                <div
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    class="absolute inset-0 transition-opacity duration-1000"
                    :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
                    :style="{ backgroundColor: slide.shade }"
                >
                    <div class="absolute inset-0 flex items-center justify-center overflow-hidden">
                        <span class="text-white/[0.03] text-[22vw] md:text-[18vw] font-bold uppercase leading-none tracking-tighter select-none whitespace-nowrap">
                            {{ slide.label.split(' ')[0] }}
                        </span>
                    </div>
                    <div class="absolute bottom-6 md:bottom-10 left-6 md:left-10">
                        <p class="text-white/30 text-[10px] tracking-[0.2em] uppercase mb-1.5">{{ slide.category }}</p>
                        <p class="text-white text-sm font-medium">{{ slide.label }}</p>
                    </div>
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

            <!-- Content panel -->
            <div class="absolute inset-y-0 right-0 w-1/2 bg-white flex flex-col px-16">
                <div class="flex-1 flex flex-col justify-center">
                    <p class="text-[10px] tracking-[0.18em] uppercase text-black/40 font-medium mb-8">
                        Web Development Agency
                    </p>
                    <h1 class="font-black text-[clamp(40px,4.5vw,72px)] leading-[0.92] tracking-[-0.02em] uppercase text-black mb-6">
                        Digital<br>Presence<br>For Builders.
                    </h1>
                    <p class="text-black/45 text-[15px] leading-relaxed max-w-[340px] mb-8">
                        Websites and interactive floor plan tools for
                        construction and real estate companies.
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="/work"
                           class="border border-black text-black text-[11px] tracking-[0.1em] uppercase px-8 py-4 hover:bg-black hover:text-white transition-colors duration-200">
                            View Our Work
                        </a>
                        <a href="/contact"
                           class="border border-black text-black text-[11px] tracking-[0.1em] uppercase px-8 py-4 hover:bg-black hover:text-white transition-colors duration-200">
                            Get In Touch
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Menu panel: identical structure to FloatingMenu.vue ──── -->
        <Transition
            enter-active-class="transition-transform duration-500 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition-transform duration-300 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="menuOpen"
                class="fixed bottom-0 left-0 right-0 z-40 flex flex-col bg-black"
                style="height: 50vh;"
            >
                <!-- Scrollable columns -->
                <div
                    ref="navScrollRef"
                    class="menus-scroll flex flex-1 min-h-0 overflow-x-auto overflow-y-hidden"
                    style="cursor: ew-resize;"
                    @mousemove="onNavMouseMove"
                    @mouseleave="onNavMouseLeave"
                >
                    <div class="flex h-full">
                        <div
                            v-for="item in navItems"
                            :key="item.key"
                            class="flex-shrink-0 flex flex-col p-5 md:p-7 pb-4 cursor-pointer group border-r border-white/[0.07] last:border-r-0 transition-colors duration-200 hover:bg-white/[0.03]"
                            style="width: 240px;"
                            :style="{ width: 'clamp(200px, 22vw, 380px)' }"
                            @click.stop="navigate(item.href)"
                        >
                            <!-- Page label -->
                            <div class="flex items-center gap-2 mb-4 flex-shrink-0">
                                <span
                                    v-if="'home' === item.key"
                                    class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                    style="background-color: #5DCAA5;"
                                ></span>
                                <span
                                    class="text-white text-[10px] md:text-xs font-medium tracking-[0.2em] uppercase transition-colors duration-200"
                                    :class="'home' === item.key ? 'text-white' : 'text-white/60 group-hover:text-white/90'"
                                >{{ item.label }}</span>
                            </div>

                            <!-- preview card — fixed height so it doesn't overflow the button area -->
                            <div
                                class="flex-shrink-0 relative overflow-hidden transition-colors duration-200"
                                style="height: 160px;"
                                :style="{ background: '#111' }"
                            >
                                <img
                                    :src="`https://picsum.photos/seed/${item.key}/400/200`"
                                    class="w-full h-full object-cover opacity-60"
                                    alt=""
                                />
                                <div class="absolute inset-0 flex items-end p-3 md:p-4">
                                    <span class="text-white/[0.07] text-xs uppercase tracking-widest font-medium">{{ item.label }}</span>
                                </div>
                                <div
                                    v-if="'home' === item.key"
                                    class="absolute bottom-0 left-0 right-0 h-px"
                                    style="background-color: #5DCAA5;"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom bar: FOLLOW US + social (replaces FloatingMenu's X close button) -->
                <div class="flex-shrink-0 flex items-center justify-between px-8 py-5 border-t border-white/[0.07]">
                    <div></div>
                    <div class="flex items-center gap-6">
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
            class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 group cursor-pointer"
            style="width: 96px; height: 96px;"
            :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
        >
            <!-- Circle (empty, no icon) -->
            <div
                class="absolute inset-0 rounded-full transition-transform duration-500 group-hover:scale-90"
                style="background-color: #5DCAA5;"
            ></div>

            <!-- Rotating MENU text — shown when menu is CLOSED -->
            <svg
                v-if="!menuOpen"
                class="absolute -top-8 -left-8 opacity-0 group-hover:opacity-100 transition-opacity duration-300 group-hover:animate-spin-slow"
                width="160" height="160"
                viewBox="0 0 160 160"
            >
                <defs>
                    <path
                        id="circle-text-path"
                        d="M 80,80 m -68,0 a 68,68 0 1,1 136,0 a 68,68 0 1,1 -136,0"
                    />
                </defs>
                <text fill="black" font-size="10" font-family="Inter, sans-serif" letter-spacing="6" font-weight="500">
                    <textPath href="#circle-text-path">MENU · MENU · MENU · MENU ·</textPath>
                </text>
            </svg>

            <!-- Rotating CLOSE text — shown when menu is OPEN -->
            <svg
                v-if="menuOpen"
                class="absolute -top-8 -left-8 animate-spin-slow"
                width="160" height="160"
                viewBox="0 0 160 160"
            >
                <defs>
                    <path
                        id="close-text-path"
                        d="M 80,80 m -68,0 a 68,68 0 1,1 136,0 a 68,68 0 1,1 -136,0"
                    />
                </defs>
                <text fill="white" font-size="10" font-family="Inter, sans-serif" letter-spacing="6" font-weight="500">
                    <textPath href="#close-text-path">CLOSE · CLOSE · CLOSE · CLOSE ·</textPath>
                </text>
            </svg>
        </button>

    </div>
</template>

<style>
.menus-scroll::-webkit-scrollbar { display: none; }
</style>
