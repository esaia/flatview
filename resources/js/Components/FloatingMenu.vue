<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    centerBorder: { type: Boolean, default: false },
})

const emit = defineEmits(['update:menuOpen'])

const page = usePage()
const menuItems = computed(() => page.props.menuItems || [])
const currentPath = computed(() => new URL(page.url, window.location.origin).pathname)

const menuOpen = ref(false)
const buttonHovered = ref(false)
const navScrollRef = ref(null)
let rafId = null
let cursorX = 0
let containerW = 0
let mouseInNav = false

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
    emit('update:menuOpen', true)
    await nextTick()
    if (rafId) cancelAnimationFrame(rafId)
    rafId = requestAnimationFrame(scrollLoop)
}

function closeMenu() {
    menuOpen.value = false
    emit('update:menuOpen', false)
    mouseInNav = false
    if (rafId) { cancelAnimationFrame(rafId); rafId = null }
}

function toggleMenu() { menuOpen.value ? closeMenu() : openMenu() }

onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => {
    document.removeEventListener('keydown', onKey)
    if (rafId) cancelAnimationFrame(rafId)
})
</script>

<template>
    <!-- ── Home page trigger (centerBorder = true, unused since Home.vue owns it) ── -->
    <button
        v-if="centerBorder"
        @click="toggleMenu"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 group cursor-pointer"
        style="width: 85.51px; height: 85.51px;"
        :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
    >
        <div class="rounded-full transition-transform duration-500 group-hover:scale-90" style="position:absolute;top:0;left:0;width:85.51px;height:85.51px;aspect-ratio:1/1;background-color:#5DCAA5;"></div>

        <!-- Vertical label: rotated 90° CW, slides in/out in screen-Y -->
        <div class="btn-label-clip" style="left: calc(100% + 10px); width: 18px; height: 52px;">
            <Transition name="btn-label">
                <div :key="menuOpen ? 'close' : 'menu'" class="btn-label-slide">
                    <span class="btn-label-text" :class="menuOpen ? 'text-white' : 'text-black'">
                        {{ menuOpen ? 'Close' : 'Menu' }}
                    </span>
                </div>
            </Transition>
        </div>
    </button>

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
                        v-for="item in menuItems"
                        :key="item.id"
                        class="flex-shrink-0 flex flex-col p-5 md:p-7 pb-4 cursor-pointer group border-r border-white/[0.07] last:border-r-0 transition-colors duration-200 hover:bg-white/[0.03]"
                        style="width: 240px;"
                        :style="{ width: 'clamp(200px, 22vw, 380px)' }"
                        @click.stop="navigate(item.href)"
                    >
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

                        <div
                            class="flex-shrink-0 relative overflow-hidden transition-colors duration-200"
                            style="height: 160px; background: #111;"
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

            <!-- Bottom bar -->
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

    <!-- ── Inner-page trigger: centered circle, AFTER panel in DOM so z-50 wins ── -->
    <button
        v-if="!centerBorder"
        @click="toggleMenu"
        @mouseenter="buttonHovered = true"
        @mouseleave="buttonHovered = false"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 group cursor-pointer"
        :style="{
            width: '85.51px',
            height: '85.51px',
            opacity: (buttonHovered || menuOpen) ? 1 : 0,
            transition: 'opacity 0.2s ease',
        }"
        :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
    >
        <div
            class="rounded-full transition-transform duration-500 group-hover:scale-90"
            style="position:absolute;top:0;left:0;width:85.51px;height:85.51px;aspect-ratio:1/1;background-color:#5DCAA5;"
        ></div>

        <!-- Vertical label: rotated 90° CW, slides in/out in screen-Y -->
        <div class="btn-label-clip" style="left: calc(100% + 10px); width: 18px; height: 52px;">
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
.menus-scroll::-webkit-scrollbar { display: none; }

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
    /* width / height set via inline style per button */
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
