<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    service: { type: Object, required: true },
    cta: { type: Object, default: () => ({}) },
})

const blocks = computed(() => props.service.content_blocks ?? [])

function imgSrc(path) {
    if (!path) return ''
    if (path.startsWith('http')) return path
    return '/storage/' + path.replace(/^\/+/, '')
}
</script>

<template>
    <AppLayout active-page="services">
        <div class="overflow-x-hidden">

            <!-- Hero -->
            <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-14 md:pt-20 pb-14 md:pb-20">
                <Link
                    href="/services"
                    class="reveal inline-flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-black/45 hover:text-black transition-colors duration-300 mb-8 md:mb-10"
                    style="--d: 0s;"
                >
                    <span aria-hidden="true">←</span> All services
                </Link>

                <div class="max-w-2xl">
                    <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: .04s;">
                        <span class="dot"></span> Our Services
                    </span>
                    <h1 class="reveal display text-black"
                        style="--d: .08s; font-size: clamp(38px, 6.4vw, 88px); font-weight: 300; line-height: 1.04; letter-spacing: -0.02em;">
                        {{ service.name }}
                    </h1>
                </div>
            </div>

            <!-- Hero image -->
            <div v-if="service.hero_image" class="max-w-[1400px] mx-auto px-6 md:px-16 mb-20 md:mb-32">
                <div class="relative overflow-hidden bg-[#ece8e2]" style="aspect-ratio: 16 / 8;">
                    <img
                        :src="imgSrc(service.hero_image)"
                        :alt="service.name"
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 w-full h-full object-cover"
                        draggable="false"
                    />
                </div>
            </div>

            <div class="max-w-[1400px] mx-auto px-6 md:px-16 pb-20 md:pb-32 space-y-20 md:space-y-32">
                <template v-for="(block, i) in blocks" :key="i">

                    <!-- Rich text -->
                    <div
                        v-if="block.type === 'rich_text'"
                        class="prose-service max-w-2xl text-sm md:text-base text-black/45 font-light leading-relaxed"
                        v-html="block.data.content"
                    />

                    <!-- Image + text split -->
                    <div
                        v-else-if="block.type === 'image_text'"
                        class="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-16 items-center"
                    >
                        <div
                            class="relative overflow-hidden bg-[#ece8e2]"
                            :class="block.data.image_position === 'right' ? 'md:order-2' : ''"
                            style="aspect-ratio: 4 / 3;"
                        >
                            <img
                                v-if="block.data.image"
                                :src="imgSrc(block.data.image)"
                                :alt="block.data.heading"
                                loading="lazy"
                                decoding="async"
                                class="absolute inset-0 w-full h-full object-cover"
                                draggable="false"
                            />
                        </div>
                        <div>
                            <h3 class="display text-2xl md:text-3xl text-black mb-4 leading-snug" style="font-weight: 400;">{{ block.data.heading }}</h3>
                            <p class="text-sm md:text-base text-black/45 font-light leading-relaxed">{{ block.data.text }}</p>
                        </div>
                    </div>

                    <!-- Quote -->
                    <blockquote v-else-if="block.type === 'quote'" class="border-l-2 border-black/10 pl-6 md:pl-10 max-w-2xl">
                        <p class="display text-xl md:text-3xl text-black leading-snug mb-4" style="font-weight: 400;">
                            &ldquo;{{ block.data.quote }}&rdquo;
                        </p>
                        <cite v-if="block.data.attribution" class="text-[11px] tracking-[0.2em] uppercase text-black/40 not-italic">
                            {{ block.data.attribution }}
                        </cite>
                    </blockquote>

                    <!-- Stats row -->
                    <div v-else-if="block.type === 'stats'" class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-0 border-t border-black/10">
                        <div
                            v-for="stat in block.data.items"
                            :key="stat.label"
                            class="pt-8 md:pr-10"
                        >
                            <p class="display text-3xl md:text-4xl text-black mb-2" style="font-weight: 300;">{{ stat.value }}</p>
                            <p class="text-sm text-black/45 font-light leading-relaxed">{{ stat.label }}</p>
                        </div>
                    </div>

                    <!-- Feature list -->
                    <div v-else-if="block.type === 'feature_list'">
                        <p class="kicker flex items-center gap-2 mb-8 md:mb-10"><span class="dot"></span> {{ block.data.heading }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-16 border-t border-black/10">
                            <div
                                v-for="feature in block.data.items"
                                :key="feature.title"
                                class="py-7 md:py-9 border-b border-black/10"
                            >
                                <h3 class="display text-xl md:text-2xl text-black mb-2.5 leading-snug" style="font-weight: 400;">{{ feature.title }}</h3>
                                <p class="text-sm text-black/45 font-light leading-relaxed max-w-md">{{ feature.detail }}</p>
                            </div>
                        </div>
                    </div>

                </template>
            </div>

            <!-- Closing CTA -->
            <section class="bg-[#0e0e0e] text-white">
                <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-28 md:pt-44 pb-40 md:pb-56 text-center flex flex-col items-center">
                    <p class="kicker mb-8 md:mb-10" style="color: rgba(255,255,255,0.4);">{{ cta.kicker }}</p>
                    <h2
                        class="display font-light whitespace-pre-line"
                        style="font-size: clamp(40px, 7vw, 104px); line-height: 1.02; letter-spacing: -0.02em;"
                    >
                        {{ cta.headline }}
                    </h2>
                    <a
                        :href="cta.buttonLink || '/contact'"
                        class="inline-flex items-center gap-3 mt-12 md:mt-16 px-7 py-4 bg-white text-black text-[11px] tracking-[0.25em] uppercase hover:bg-white/90 transition-colors duration-300 group"
                    >
                        <span>{{ cta.buttonText }}</span>
                        <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">↗</span>
                    </a>
                </div>
            </section>

        </div>
    </AppLayout>
</template>

<style scoped>
.prose-service :deep(p) { margin-bottom: 1em; }
.prose-service :deep(p:last-child) { margin-bottom: 0; }
.prose-service :deep(strong) { color: rgba(0, 0, 0, 0.7); font-weight: 500; }
.prose-service :deep(ul) { list-style-type: disc; padding-left: 1.25em; margin-bottom: 1em; }
.prose-service :deep(ol) { list-style-type: decimal; padding-left: 1.25em; margin-bottom: 1em; }
.prose-service :deep(li) { display: list-item; margin-bottom: 0.4em; }
.prose-service :deep(a) { color: rgba(0, 0, 0, 0.7); text-decoration: underline; text-underline-offset: 2px; }
</style>
