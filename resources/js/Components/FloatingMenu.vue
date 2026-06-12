<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    activePage: { type: String, default: '' },
    centerBorder: { type: Boolean, default: false },
})

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
    closeMenu()
    router.visit(href)
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
}

function scrollLoop() {
    if (mouseInNav && navScrollRef.value && containerW > 0) {
        // cursor right of center → scroll left (positive), left of center → scroll right (negative)
        const normalised = cursorX / containerW   // 0 – 1
        const deadzone = 0.15                     // inner 30% has no scroll
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

onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => {
    document.removeEventListener('keydown', onKey)
    if (rafId) cancelAnimationFrame(rafId)
})
</script>

<template>
    <!-- ── Floating trigger ─────────────────────────────────────────── -->
    <div
        class="fixed z-40 flex items-center gap-2 cursor-pointer group"
        :class="centerBorder
            ? 'bottom-6 right-6 md:bottom-8 md:right-auto md:left-[55%] md:-translate-x-1/2'
            : 'bottom-6 right-6 md:bottom-8 md:right-8'"
        @click="openMenu"
    >
        <div
            class="w-12 h-12 md:w-[52px] md:h-[52px] rounded-full flex items-center justify-center flex-shrink-0 transition-transform duration-200 group-hover:scale-105"
            style="background-color: #5DCAA5;"
        >
            <svg width="16" height="11" viewBox="0 0 18 12" fill="none">
                <rect width="18" height="1.5" rx="0.75" fill="white"/>
                <rect y="5.25" width="18" height="1.5" rx="0.75" fill="white"/>
                <rect y="10.5" width="18" height="1.5" rx="0.75" fill="white"/>
            </svg>
        </div>
        <!-- Vertical "Menu" label — only on home page, hidden on mobile -->
        <span
            v-if="centerBorder"
            class="hidden md:block text-[9px] font-medium tracking-[0.25em] uppercase text-white/70 select-none flex-shrink-0 group-hover:text-white/90 transition-colors duration-200"
            style="writing-mode: vertical-rl;"
        >Menu</span>
    </div>

    <!-- ── Slide-up nav panel ──────────────────────────────────────── -->
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
            class="fixed bottom-0 left-0 right-0 z-50 flex flex-col bg-black"
            style="height: 50vh;"
        >
            <!-- ── Scrollable columns ──── -->
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
                        class="flex-shrink-0 flex flex-col p-5 md:p-7 cursor-pointer group border-r border-white/[0.07] last:border-r-0 transition-colors duration-200 hover:bg-white/[0.03]"
                        style="width: 240px;"
                        :style="{ width: 'clamp(200px, 22vw, 380px)' }"
                        @click.stop="navigate(item.href)"
                    >
                        <!-- Page label -->
                        <div class="flex items-center gap-2 mb-4 flex-shrink-0">
                            <span
                                v-if="activePage === item.key"
                                class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                style="background-color: #5DCAA5;"
                            ></span>
                            <span
                                class="text-white text-[10px] md:text-xs font-medium tracking-[0.2em] uppercase transition-colors duration-200"
                                :class="activePage === item.key ? 'text-white' : 'text-white/60 group-hover:text-white/90'"
                            >{{ item.label }}</span>
                        </div>

                        <!-- 16:9 preview placeholder -->
                        <div
                            class="flex-1 relative overflow-hidden transition-colors duration-200"
                            style="aspect-ratio: 16/9;"
                            :style="{ background: '#111' }"
                        >
                            <!-- subtle page hint watermark -->
                            <div class="absolute inset-0 flex items-end p-3 md:p-4">
                                <span class="text-white/[0.07] text-xs uppercase tracking-widest font-medium">{{ item.label }}</span>
                            </div>
                            <!-- thin teal bar at bottom on active -->
                            <div
                                v-if="activePage === item.key"
                                class="absolute bottom-0 left-0 right-0 h-px"
                                style="background-color: #5DCAA5;"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Close button ──── -->
            <div class="flex items-center justify-center py-5 flex-shrink-0">
                <div
                    class="flex items-center gap-2 cursor-pointer group"
                    @click="closeMenu"
                >
                    <button
                        class="w-12 h-12 md:w-[52px] md:h-[52px] rounded-full flex items-center justify-center transition-transform duration-200 group-hover:scale-105"
                        style="background-color: #5DCAA5;"
                        aria-label="Close navigation"
                    >
                        <svg width="13" height="13" viewBox="0 0 14 14" fill="none">
                            <path d="M1 1L13 13M13 1L1 13" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <span
                        class="hidden md:block text-[9px] font-medium tracking-[0.25em] uppercase text-white/50 select-none group-hover:text-white/80 transition-colors duration-200"
                        style="writing-mode: vertical-rl;"
                    >Close</span>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style>
.menus-scroll::-webkit-scrollbar { display: none; }
</style>
