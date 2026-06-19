<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const flash = computed(() => page.props.flash)

const form = useForm({
    name:    '',
    email:   '',
    company: '',
    message: '',
})

function submit() {
    form.post('/contact', {
        onSuccess: () => form.reset(),
    })
}

const contactImages = [
    'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80',
    'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=800&q=80',
    'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=800&q=80',
    'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&q=80',
]
</script>

<template>
    <AppLayout active-page="contact">

        <div class="flex flex-col md:flex-row h-screen overflow-hidden">

            <!-- LEFT: 2×2 image grid, edge-to-edge, fixed height -->
            <div class="hidden md:grid md:w-1/2 grid-cols-2 flex-shrink-0" style="gap: 2px;">
                <div
                    v-for="(img, i) in contactImages"
                    :key="i"
                    class="overflow-hidden"
                    style="height: 50vh;"
                >
                    <img
                        :src="img"
                        class="w-full h-full object-cover hover:scale-105 transition-transform duration-700"
                        draggable="false"
                        loading="lazy"
                    />
                </div>
            </div>

            <!-- RIGHT: scrollable content panel -->
            <div class="flex-1 md:w-1/2 overflow-y-auto px-8 md:px-16 pt-16 pb-36">

                <!-- Headline -->
                <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: 0s;">
                    <span class="dot"></span> Contact
                </span>
                <h1
                    class="reveal display text-black mb-14"
                    style="--d: .08s; font-size: clamp(38px, 4.4vw, 64px); font-weight: 300; line-height: 1.06; letter-spacing: -0.02em;"
                >
                    Get in touch
                </h1>

                <!-- Contact blocks -->
                <div class="reveal space-y-10 mb-14" style="--d: .16s;">
                    <div>
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">Start a project</p>
                        <p class="text-[15px] text-black/55">Fill out the form below</p>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">Just say hi</p>
                        <a
                            href="mailto:hello@merisimo.com"
                            class="text-[15px] text-black/55 hover:text-black transition-colors duration-200"
                        >
                            hello@merisimo.com
                        </a>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">Location</p>
                        <p class="text-[15px] text-black/55">Tbilisi, Georgia</p>
                        <p class="text-[13px] text-black/35 mt-0.5">Available for remote projects worldwide</p>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.25em] uppercase font-medium text-black mb-1.5">Follow us</p>
                        <div class="flex gap-6">
                            <a href="#" class="text-[15px] text-black/55 hover:text-black transition-colors duration-200">LinkedIn</a>
                            <a href="#" class="text-[15px] text-black/55 hover:text-black transition-colors duration-200">Instagram</a>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-black/10 mb-12"></div>

                <!-- Success flash -->
                <div v-if="flash.success" class="mb-10 py-5 border-l-2 pl-5" style="border-color: #5DCAA5;">
                    <p class="text-sm font-medium text-black">{{ flash.success }}</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit">
                    <div class="space-y-8">

                        <div class="border-b border-black/15 pb-4">
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

                        <div class="border-b border-black/15 pb-4">
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

                        <div class="border-b border-black/15 pb-4">
                            <label class="block text-xs font-medium tracking-[0.25em] uppercase text-black/30 mb-2">Company</label>
                            <input
                                v-model="form.company"
                                type="text"
                                class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                                placeholder="Company name (optional)"
                            />
                        </div>

                        <div class="border-b border-black/15 pb-4">
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

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-black text-white text-[12px] tracking-[0.18em] uppercase py-4 mt-8 hover:bg-[#5DCAA5] hover:text-black transition-colors duration-300 disabled:opacity-40"
                    >
                        {{ form.processing ? 'Sending…' : 'Send message' }}
                    </button>
                </form>

            </div>
        </div>

    </AppLayout>
</template>
