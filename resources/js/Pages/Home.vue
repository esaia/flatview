<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
})

// ── Slideshow ────────────────────────────────────────────────────────────────
const slides = computed(() => props.projects)
const currentSlide = ref(0)
const videoRefs = ref([])
let slideTimer = null

watch(currentSlide, (newIdx) => {
    videoRefs.value.forEach((el, i) => {
        if (!el) return
        i === newIdx ? el.play() : el.pause()
    })
})

// ── Menu ─────────────────────────────────────────────────────────────────────
const page = usePage()
const menuItems = computed(() => page.props.menuItems || [])
const currentPath = computed(() => new URL(page.url, window.location.origin).pathname)

const menuOpen = ref(false)
const navScrollRef = ref(null)
let rafId = null
let cursorX = 0
let containerW = 0
let mouseInNav = false

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
        currentSlide.value = (currentSlide.value + 1) % (slides.value.length || 1)
    }, 5000)
    document.addEventListener('keydown', onKey)
})

onUnmounted(() => {
    clearInterval(slideTimer)
    document.removeEventListener('keydown', onKey)
    if (rafId) cancelAnimationFrame(rafId)
})
</script>

<template>
    <div class="relative h-screen overflow-hidden overflow-x-hidden">

        <!-- ── Main content: slides up when menu opens ────────────────── -->
        <div
            class="absolute inset-0 flex flex-col md:flex-row select-none"
            :style="{
                transform: menuOpen ? 'translateY(-38vh)' : 'translateY(0)',
                transition: 'transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94)'
            }"
        >
            <!-- Image panel -->
            <div class="absolute inset-x-0 top-0 h-[50vh] md:inset-y-0 md:left-0 md:right-auto md:w-1/2 md:h-full overflow-hidden bg-black">
                <div
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    class="absolute inset-0"
                    :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
                    :style="{ backgroundColor: slide.background_color || '#1a1a1a', transition: 'opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)' }"
                >
                    <video
                        v-if="slide.media_type === 'video' && slide.video"
                        :ref="el => { if (el) videoRefs.value[index] = el }"
                        class="absolute inset-0 w-full h-full object-cover"
                        autoplay muted loop playsinline
                        :src="`/storage/${slide.video}`"
                    ></video>
                    <img
                        v-else-if="slide.image"
                        :src="slide.image.startsWith('http') ? slide.image : `/storage/${slide.image}`"
                        :alt="slide.title"
                        class="absolute inset-0 w-full h-full object-cover"
                    />
                    <div
                        v-else
                        class="absolute inset-0"
                        :style="{ backgroundColor: slide.background_color || '#1a1a1a' }"
                    ></div>
                    <div class="absolute inset-0 flex items-center justify-center overflow-hidden">
                        <span class="text-white/[0.03] text-[22vw] md:text-[18vw] font-bold uppercase leading-none tracking-tighter select-none whitespace-nowrap">
                            {{ slide.title ? slide.title.split(' ')[0] : '' }}
                        </span>
                    </div>
                    <div class="absolute bottom-4 left-4 md:bottom-8 md:left-8 z-10">
                        <p class="text-white/30 text-[8px] md:text-[10px] tracking-[0.2em] uppercase mb-1.5">
                            {{ slide.category }}{{ slide.year ? ' · ' + slide.year : '' }}
                        </p>
                        <p class="text-white text-[11px] md:text-sm font-medium">{{ slide.title }}</p>
                    </div>
                    <div class="absolute bottom-7 md:bottom-11 right-6 md:right-10 flex items-center gap-2">
                        <span
                            v-for="(s, i) in slides"
                            :key="i"
                            class="block transition-all duration-500 rounded-full"
                            :class="i === currentSlide ? 'w-4 md:w-6 h-px bg-white' : 'w-0.5 h-0.5 md:w-1 md:h-1 bg-white/25'"
                        ></span>
                    </div>
                </div>
            </div>

            <!-- Content panel -->
            <div class="absolute inset-x-0 bottom-0 h-[50vh] md:inset-y-0 md:left-auto md:right-0 md:w-1/2 md:h-full bg-white flex flex-col justify-center px-6 md:px-16">
                <div class="flex-1 flex flex-col justify-center">
                    <p class="text-[9px] md:text-[10px] tracking-[0.18em] uppercase text-black/40 font-medium mb-8">
                        {{ props.settings.badge || 'Web Development Agency' }}
                    </p>
                    <h1 class="font-black text-[28px] md:text-[clamp(40px,4.5vw,72px)] leading-[0.92] tracking-[-0.02em] uppercase text-black mb-6" style="white-space: pre-line">{{ props.settings.headline || "Digital\nPresence\nFor Builders." }}</h1>
                    <p class="text-black/45 text-[13px] md:text-[15px] leading-relaxed max-w-[340px] mb-8">
                        {{ props.settings.subtitle || 'Websites and interactive floor plan tools for construction and real estate companies.' }}
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="/work"
                           class="border border-black text-black text-[10px] md:text-[11px] tracking-[0.1em] uppercase px-4 py-3 md:px-8 md:py-4 hover:bg-black hover:text-white transition-colors duration-200">
                            View Our Work
                        </a>
                        <a href="/contact"
                           class="border border-black text-black text-[10px] md:text-[11px] tracking-[0.1em] uppercase px-4 py-3 md:px-8 md:py-4 hover:bg-black hover:text-white transition-colors duration-200">
                            Get In Touch
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Menu panel: identical structure to FloatingMenu.vue ──── -->
        <Transition name="menu-panel">
            <div
                v-if="menuOpen"
                class="fixed bottom-0 left-0 right-0 z-40 flex flex-col bg-black"
                style="height: 50vh;"
            >
                <!-- Scrollable columns -->
                <div
                    ref="navScrollRef"
                    class="menus-scroll flex flex-col md:flex-row flex-1 min-h-0 overflow-y-auto md:overflow-x-auto md:overflow-y-hidden"
                    style="cursor: ew-resize;"
                    @mousemove="onNavMouseMove"
                    @mouseleave="onNavMouseLeave"
                >
                    <div class="flex flex-col md:flex-row md:h-full w-full">
                        <div
                            v-for="item in menuItems"
                            :key="item.id"
                            class="md:flex-1 md:flex-shrink-0 border-b border-white/10 md:border-b-0 md:border-r md:last:border-r-0 flex flex-col p-4 md:p-8 cursor-pointer group transition-colors duration-200 hover:bg-white/[0.03]"
                            @click.stop="navigate(item.href)"
                        >
                            <!-- Page label -->
                            <div class="flex items-center gap-2 mb-4 flex-shrink-0">
                                <span
                                    v-if="currentPath === item.href"
                                    class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                    style="background-color: #5DCAA5;"
                                ></span>
                                <span
                                    class="text-white text-[10px] md:text-xs font-medium tracking-[0.2em] uppercase transition-colors duration-200"
                                    :class="currentPath === item.href ? 'text-white' : 'text-white/60 group-hover:text-white/90'"
                                >{{ item.label }}</span>
                            </div>

                            <!-- preview card -->
                            <div
                                class="w-full flex-shrink-0 relative overflow-hidden transition-colors duration-200 mb-3 md:mb-6"
                                style="height: clamp(80px, 15vh, 140px); background: #111;"
                            >
                                <img
                                    v-if="item.image"
                                    :src="`/storage/${item.image}`"
                                    class="w-full h-full object-cover opacity-70"
                                />
                                <img
                                    v-else
                                    :src="`https://picsum.photos/seed/${item.label}/400/300`"
                                    class="w-full h-full object-cover opacity-50"
                                />
                                <div class="absolute inset-0 flex items-end p-3 md:p-4">
                                    <span class="text-white/[0.07] text-xs uppercase tracking-widest font-medium">{{ item.label }}</span>
                                </div>
                                <div
                                    v-if="currentPath === item.href"
                                    class="absolute bottom-0 left-0 right-0 h-px"
                                    style="background-color: #5DCAA5;"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom bar: FOLLOW US + social (replaces FloatingMenu's X close button) -->
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
            class="fixed bottom-4 md:bottom-16 left-1/2 -translate-x-1/2 z-50 group cursor-pointer w-12 h-12 md:w-24 md:h-24"
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
                class="hidden md:block absolute -top-8 -left-8 opacity-0 group-hover:opacity-100 transition-opacity duration-300 group-hover:animate-spin-slow"
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
                class="hidden md:block absolute -top-8 -left-8 animate-spin-slow"
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

.menu-panel-enter-active { transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.menu-panel-leave-active { transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.menu-panel-enter-from,
.menu-panel-leave-to   { transform: translateY(100%); }
</style>
