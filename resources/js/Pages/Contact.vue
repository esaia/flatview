<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CountryDialSelect from '@/Components/CountryDialSelect.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    turnstileSiteKey: { type: String, default: '' },
})

const page = usePage()
const flash = computed(() => page.props.flash)

const settings = computed(() => props.settings ?? {})
// The panel takes a single image; extra uploads are ignored rather than tiled.
const contactImage = computed(() => (settings.value.images ?? [])[0] ?? null)
const socials = computed(() => settings.value.socials ?? [])

const sent = ref(false)

const form = useForm({
    name:    '',
    email:   '',
    phone:   '',
    company: '',
    message: '',
    cf_turnstile_response: '',
})

/* ── Phone with dial-code prefix ───────────────────── */
const dialCode = ref('+995')
const phoneNumber = ref('')

function syncPhone() {
    const digits = phoneNumber.value.replace(/[^\d]/g, '')
    form.phone = digits ? `${dialCode.value} ${digits}` : ''
}

/* ── Cloudflare Turnstile ──────────────────────────── */
const turnstileEl = ref(null)
let widgetId = null

function loadTurnstileScript() {
    return new Promise((resolve) => {
        if (window.turnstile) return resolve()
        const existing = document.querySelector('script[data-turnstile]')
        if (existing) return existing.addEventListener('load', resolve)
        const s = document.createElement('script')
        s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js'
        s.async = true
        s.defer = true
        s.dataset.turnstile = 'true'
        s.onload = resolve
        document.head.appendChild(s)
    })
}

async function renderTurnstile() {
    if (!props.turnstileSiteKey) return
    await loadTurnstileScript()
    await nextTick()
    if (!turnstileEl.value || !window.turnstile) return
    removeTurnstile()
    widgetId = window.turnstile.render(turnstileEl.value, {
        sitekey: props.turnstileSiteKey,
        theme: 'light',
        callback: (token) => { form.cf_turnstile_response = token },
        'expired-callback': () => { form.cf_turnstile_response = '' },
        'error-callback': () => { form.cf_turnstile_response = '' },
    })
}

function resetTurnstile() {
    form.cf_turnstile_response = ''
    if (widgetId !== null && window.turnstile) {
        try { window.turnstile.reset(widgetId) } catch (e) { /* widget gone */ }
    }
}

function removeTurnstile() {
    if (widgetId !== null && window.turnstile) {
        try { window.turnstile.remove(widgetId) } catch (e) { /* already removed */ }
    }
    widgetId = null
}

onMounted(renderTurnstile)
onBeforeUnmount(removeTurnstile)

function submit() {
    syncPhone()
    form.post('/contact', {
        onSuccess: () => {
            form.reset()
            phoneNumber.value = ''
            dialCode.value = '+995'
            sent.value = true
        },
        // Turnstile tokens are single-use — refresh on any failed attempt.
        onError: resetTurnstile,
    })
}

function reset() {
    sent.value = false
    form.clearErrors()
    nextTick(renderTurnstile)
}
</script>

<template>
    <AppLayout active-page="contact">

        <div class="relative flex flex-col md:flex-row h-screen overflow-hidden">

            <!-- Brand logo, top-right -->
            <Link href="/" class="absolute top-7 right-8 md:top-10 md:right-16 z-30 flex items-center gap-2 md:gap-2.5 select-none cursor-pointer">
                <img src="/logo.svg" alt="FlatView" class="h-5 md:h-6 w-auto" draggable="false" />
                <span class="text-black text-sm md:text-base font-semibold tracking-[0.2em] uppercase">FlatView</span>
            </Link>


            <!-- LEFT: one full-height image, edge-to-edge -->
            <div v-if="contactImage" class="hidden md:block md:w-1/2 flex-shrink-0 overflow-hidden sticky top-0 h-screen">
                <img
                    :src="contactImage"
                    alt=""
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-[1200ms] ease-out"
                    draggable="false"
                />
            </div>

            <!-- RIGHT: scrollable content panel -->
            <div class="flex-1 md:w-1/2 overflow-y-auto px-8 md:px-16 pt-16 pb-36">

                <!-- Headline -->
                <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: 0s;">
                    <span class="dot"></span> {{ settings.kicker }}
                </span>
                <h1
                    class="reveal display text-black mb-14"
                    style="--d: .08s; font-size: clamp(38px, 4.4vw, 64px); font-weight: 300; line-height: 1.06; letter-spacing: -0.02em;"
                >
                    {{ settings.headline }}
                </h1>

                <!-- Contact blocks -->
                <div class="reveal space-y-10 mb-14" style="--d: .16s;">
                    <div>
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">{{ settings.formLabel }}</p>
                        <p class="text-[15px] text-black/55">{{ settings.formSubtitle }}</p>
                    </div>
                    <div v-if="settings.email">
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">{{ settings.emailLabel }}</p>
                        <a
                            :href="`mailto:${settings.email}`"
                            class="text-[15px] text-black/55 hover:text-black transition-colors duration-200"
                        >
                            {{ settings.email }}
                        </a>
                    </div>
                    <div v-if="settings.location">
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">{{ settings.locationLabel }}</p>
                        <p class="text-[15px] text-black/55">{{ settings.location }}</p>
                        <p v-if="settings.locationNote" class="text-[13px] text-black/35 mt-0.5">{{ settings.locationNote }}</p>
                    </div>
                    <div v-if="socials.length">
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">{{ settings.socialLabel }}</p>
                        <div class="flex gap-6">
                            <a
                                v-for="(social, i) in socials"
                                :key="i"
                                :href="social.url"
                                target="_blank"
                                rel="noopener"
                                class="text-[15px] text-black/55 hover:text-black transition-colors duration-200"
                            >{{ social.label }}</a>
                        </div>
                    </div>
                    <div v-if="settings.whatsappQr" class="flex items-center gap-4">
                        <div class="p-2 bg-white ring-1 ring-black/10 rounded-sm shrink-0">
                            <img
                                :src="settings.whatsappQr"
                                alt="WhatsApp QR code"
                                class="w-24 h-24 object-contain"
                                draggable="false"
                                loading="lazy"
                            />
                        </div>
                        <p class="text-[13px] text-black/45 leading-relaxed max-w-[10rem]">{{ settings.whatsappLabel }}</p>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-black/10 mb-12"></div>

                <Transition name="swap" mode="out-in">
                    <!-- Success state -->
                    <div v-if="sent" key="success" class="success-panel">
                        <!-- Animated check -->
                        <div class="check-mark">
                            <span class="check-ring"></span>
                            <svg class="check-svg" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle class="check-circle" cx="26" cy="26" r="24" stroke="#5DCAA5" stroke-width="1.5" />
                                <path class="check-path" d="M15 27 L23 35 L38 18" stroke="#5DCAA5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                        <p class="success-kicker text-[11px] tracking-[0.25em] uppercase font-medium text-black/40">Message received</p>

                        <h2 class="success-title display text-black" style="font-size: clamp(30px, 3.4vw, 46px); font-weight: 300; line-height: 1.08; letter-spacing: -0.02em;">
                            Thank you.
                        </h2>

                        <p class="success-text text-[15px] text-black/55 leading-relaxed max-w-sm">
                            {{ flash.success || 'Your message is on its way. We will be in touch soon.' }}
                        </p>

                        <button
                            type="button"
                            @click="reset"
                            class="success-reset group inline-flex items-center gap-2 text-[12px] tracking-[0.18em] uppercase font-medium text-black/50 hover:text-black transition-colors duration-200"
                        >
                            <span class="h-px w-6 bg-current transition-all duration-300 group-hover:w-9"></span>
                            Send another message
                        </button>
                    </div>

                    <!-- Form -->
                    <form v-else key="form" @submit.prevent="submit">
                        <div class="space-y-8">

                            <div class="border-b border-black/15 pb-4 focus-within:border-black/40 transition-colors duration-300">
                                <label class="block text-xs font-medium tracking-[0.25em] uppercase text-black/30 mb-2">Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                                    placeholder="Your name"
                                    required
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1.5">{{ form.errors.name }}</p>
                            </div>

                            <div class="border-b border-black/15 pb-4 focus-within:border-black/40 transition-colors duration-300">
                                <label class="block text-xs font-medium tracking-[0.25em] uppercase text-black/30 mb-2">Email *</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                                    placeholder="you@company.com"
                                    required
                                />
                                <p v-if="form.errors.email" class="text-xs text-red-500 mt-1.5">{{ form.errors.email }}</p>
                            </div>

                            <div class="border-b border-black/15 pb-4 focus-within:border-black/40 transition-colors duration-300">
                                <label class="block text-xs font-medium tracking-[0.25em] uppercase text-black/30 mb-2">Phone *</label>
                                <div class="flex items-center gap-2.5">
                                    <CountryDialSelect
                                        v-model="dialCode"
                                        @update:modelValue="syncPhone"
                                        class="shrink-0"
                                    />
                                    <span class="w-px h-4 bg-black/15 shrink-0"></span>
                                    <input
                                        v-model="phoneNumber"
                                        @input="syncPhone"
                                        type="tel"
                                        inputmode="numeric"
                                        class="flex-1 min-w-0 bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                                        placeholder="555 123 456"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1.5">{{ form.errors.phone }}</p>
                            </div>

                            <div class="border-b border-black/15 pb-4 focus-within:border-black/40 transition-colors duration-300">
                                <label class="block text-xs font-medium tracking-[0.25em] uppercase text-black/30 mb-2">Company</label>
                                <input
                                    v-model="form.company"
                                    type="text"
                                    class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                                    placeholder="Company name (optional)"
                                />
                            </div>

                            <div class="border-b border-black/15 pb-4 focus-within:border-black/40 transition-colors duration-300">
                                <label class="block text-xs font-medium tracking-[0.25em] uppercase text-black/30 mb-2">Message *</label>
                                <textarea
                                    v-model="form.message"
                                    rows="5"
                                    class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 resize-none font-light"
                                    placeholder="Tell us about your project..."
                                    required
                                ></textarea>
                                <p v-if="form.errors.message" class="text-xs text-red-500 mt-1.5">{{ form.errors.message }}</p>
                            </div>

                        </div>

                        <!-- Cloudflare Turnstile -->
                        <div v-if="turnstileSiteKey" class="mt-8">
                            <div ref="turnstileEl"></div>
                            <p v-if="form.errors.cf_turnstile_response" class="text-xs text-red-500 mt-1.5">{{ form.errors.cf_turnstile_response }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-black text-white text-[12px] tracking-[0.18em] uppercase py-4 mt-8 hover:bg-[#5DCAA5] hover:text-black transition-colors duration-300 disabled:opacity-40"
                        >
                            {{ form.processing ? 'Sending…' : 'Send message' }}
                        </button>
                    </form>
                </Transition>

            </div>
        </div>

    </AppLayout>
</template>

<style scoped>
/* ── Form ⇄ success swap ───────────────────────────── */
.swap-enter-active { transition: opacity .5s ease, transform .5s ease; }
.swap-leave-active { transition: opacity .25s ease, transform .25s ease; }
.swap-enter-from   { opacity: 0; transform: translateY(10px); }
.swap-leave-to     { opacity: 0; transform: translateY(-8px); }

/* ── Success panel ─────────────────────────────────── */
.success-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 1.25rem;
    min-height: 48vh;
    padding: 2rem 0;
}

.check-mark {
    position: relative;
    width: 4.25rem;
    height: 4.25rem;
    margin-bottom: 0.25rem;
}

.check-svg {
    position: relative;
    width: 4.25rem;
    height: 4.25rem;
}

.check-circle {
    transform-origin: 50% 50%;
    stroke-dasharray: 151;
    stroke-dashoffset: 151;
    animation: circle-draw 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.05s forwards;
}

.check-path {
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: check-draw 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.55s forwards;
}

.check-ring {
    position: absolute;
    inset: 0;
    border-radius: 9999px;
    background: #5DCAA5;
    opacity: 0;
    transform: scale(0);
    animation: ring-pop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) 0.5s forwards;
}

/* staggered text reveal */
.success-kicker { opacity: 0; transform: translateY(8px); animation: rise 0.55s ease-out 0.75s forwards; }
.success-title  { opacity: 0; transform: translateY(10px); animation: rise 0.55s ease-out 0.85s forwards; }
.success-text   { opacity: 0; transform: translateY(10px); animation: rise 0.55s ease-out 0.95s forwards; }
.success-reset  { opacity: 0; transform: translateY(8px); animation: rise 0.55s ease-out 1.1s forwards; }

@keyframes circle-draw { to { stroke-dashoffset: 0; } }
@keyframes check-draw  { to { stroke-dashoffset: 0; } }
@keyframes ring-pop {
    0%   { transform: scale(0);   opacity: 0.22; }
    60%  { transform: scale(1.08); opacity: 0.16; }
    100% { transform: scale(1);   opacity: 0.10; }
}
@keyframes rise { to { opacity: 1; transform: translateY(0); } }

@media (prefers-reduced-motion: reduce) {
    .check-circle, .check-path, .check-ring,
    .success-kicker, .success-title, .success-text, .success-reset {
        animation-duration: 0.01ms;
        animation-delay: 0ms;
        opacity: 1;
        transform: none;
        stroke-dashoffset: 0;
    }
}
</style>
