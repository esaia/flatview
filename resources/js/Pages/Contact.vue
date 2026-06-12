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
</script>

<template>
    <AppLayout active-page="contact">
        <div class="max-w-[1400px] mx-auto px-5 sm:px-8 md:px-16 pt-14 md:pt-20 pb-20 md:pb-32">

            <!-- Headline -->
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-7xl font-bold uppercase tracking-tight text-black leading-none mb-12 md:mb-20 max-w-3xl">
                Ready to build your digital presence?
            </h1>

            <!-- Success state -->
            <div v-if="flash.success" class="mb-12 md:mb-16 py-5 md:py-6 border-l-2 pl-5 md:pl-6" style="border-color: #5DCAA5;">
                <p class="text-sm font-medium text-black">{{ flash.success }}</p>
            </div>

            <!-- Two-column layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-24">

                <!-- Left: contact info -->
                <div class="space-y-8 md:space-y-10">
                    <div>
                        <p class="text-xs font-medium tracking-[0.2em] uppercase text-black/25 mb-3 md:mb-4">Email</p>
                        <a href="mailto:hello@merisimo.com" class="text-sm text-black hover:text-black/50 transition-colors duration-200">
                            hello@merisimo.com
                        </a>
                    </div>

                    <div>
                        <p class="text-xs font-medium tracking-[0.2em] uppercase text-black/25 mb-3 md:mb-4">Location</p>
                        <p class="text-sm text-black">Tbilisi, Georgia</p>
                        <p class="text-sm text-black/40 font-light mt-0.5">Available for remote projects worldwide</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium tracking-[0.2em] uppercase text-black/25 mb-3 md:mb-4">Social</p>
                        <div class="flex flex-col gap-2">
                            <a href="#" class="text-sm text-black hover:text-black/50 transition-colors duration-200">LinkedIn</a>
                            <a href="#" class="text-sm text-black hover:text-black/50 transition-colors duration-200">GitHub</a>
                        </div>
                    </div>
                </div>

                <!-- Right: form -->
                <form @submit.prevent="submit" class="space-y-0">

                    <div class="border-b border-black/15 pb-4 mb-7 md:mb-8">
                        <label class="block text-xs font-medium tracking-[0.15em] uppercase text-black/30 mb-2">Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                            placeholder="Your name"
                            required
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1.5">{{ form.errors.name }}</p>
                    </div>

                    <div class="border-b border-black/15 pb-4 mb-7 md:mb-8">
                        <label class="block text-xs font-medium tracking-[0.15em] uppercase text-black/30 mb-2">Email *</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                            placeholder="you@company.com"
                            required
                        />
                        <p v-if="form.errors.email" class="text-xs text-red-500 mt-1.5">{{ form.errors.email }}</p>
                    </div>

                    <div class="border-b border-black/15 pb-4 mb-7 md:mb-8">
                        <label class="block text-xs font-medium tracking-[0.15em] uppercase text-black/30 mb-2">Company</label>
                        <input
                            v-model="form.company"
                            type="text"
                            class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 font-light"
                            placeholder="Company name (optional)"
                        />
                    </div>

                    <div class="border-b border-black/15 pb-4 mb-10 md:mb-12">
                        <label class="block text-xs font-medium tracking-[0.15em] uppercase text-black/30 mb-2">Message *</label>
                        <textarea
                            v-model="form.message"
                            rows="5"
                            class="w-full bg-transparent border-none text-sm text-black placeholder-black/20 outline-none focus:ring-0 p-0 resize-none font-light"
                            placeholder="Tell us about your project..."
                            required
                        ></textarea>
                        <p v-if="form.errors.message" class="text-xs text-red-500 mt-1.5">{{ form.errors.message }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto text-sm font-medium tracking-[0.15em] uppercase text-black border border-black/20 px-8 py-4 transition-all duration-200 hover:bg-black hover:text-white disabled:opacity-40"
                    >
                        {{ form.processing ? 'Sending…' : 'Send message' }}
                    </button>

                </form>

            </div>
        </div>
    </AppLayout>
</template>
