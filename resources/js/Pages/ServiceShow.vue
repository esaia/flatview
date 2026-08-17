<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DemoProjectsSection from '@/Components/DemoProjectsSection.vue'

const props = defineProps({
    service: { type: Object, required: true },
    cta: { type: Object, default: () => ({}) },
    otherServices: { type: Array, default: () => [] },
})

const blocks = computed(() => props.service.content_blocks ?? [])

// The closing CTA sits on this service's own image, else the shared one from
// Services settings, else the same default photo the /services overview uses.
const ctaBackground = computed(() => props.cta.background || '/images/services-cta.webp')

// A service "slug" may also be a full external URL — those open in a new tab.
const isExternal = (slug) => /^https?:\/\//i.test(slug ?? '')
const serviceHref = (slug) => (isExternal(slug) ? slug : `/services/${slug}`)

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
                            <!-- Rich text from the editor. Blocks saved before
                                 this field became an editor arrive as HTML too:
                                 the controller promotes their plain text. -->
                            <div
                                class="prose-service text-sm md:text-base text-black/45 font-light leading-relaxed"
                                v-html="block.data.text"
                            />

                            <!-- Optional call to action, styled as the standalone
                                 button block so both read as the same control. -->
                            <a
                                v-if="block.data.button_label && block.data.button_url"
                                :href="block.data.button_url"
                                :target="block.data.button_new_tab ? '_blank' : null"
                                :rel="block.data.button_new_tab ? 'noopener noreferrer' : null"
                                class="inline-flex items-center gap-3 mt-8 px-7 py-4 text-[11px] tracking-[0.25em] uppercase transition-colors duration-300 group"
                                :class="block.data.button_style === 'outline'
                                    ? 'border border-black/15 text-black hover:bg-black hover:text-white'
                                    : 'bg-black text-white hover:bg-black/85'"
                            >
                                <span>{{ block.data.button_label }}</span>
                                <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">↗</span>
                            </a>
                        </div>
                    </div>

                    <!-- Live demo projects, the same section the services
                         overview uses. -->
                    <DemoProjectsSection
                        v-else-if="block.type === 'demo_projects'"
                        :kicker="block.data.kicker"
                        :headline="block.data.headline"
                        :headline-accent="block.data.headline_accent"
                        :intro="block.data.intro"
                        :projects="block.data.projects ?? []"
                    />

                    <!-- Quote -->
                    <blockquote v-else-if="block.type === 'quote'" class="border-l-2 border-black/10 pl-6 md:pl-10 max-w-2xl">
                        <p class="display text-xl md:text-3xl text-black leading-snug mb-4 whitespace-pre-line" style="font-weight: 400;">
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

                    <!-- Button -->
                    <div
                        v-else-if="block.type === 'button'"
                        class="flex"
                        :class="block.data.alignment === 'center' ? 'justify-center' : 'justify-start'"
                    >
                        <a
                            :href="block.data.url"
                            :target="block.data.new_tab ? '_blank' : null"
                            :rel="block.data.new_tab ? 'noopener noreferrer' : null"
                            class="inline-flex items-center gap-3 px-7 py-4 text-[11px] tracking-[0.25em] uppercase transition-colors duration-300 group"
                            :class="block.data.style === 'outline'
                                ? 'border border-black/15 text-black hover:bg-black hover:text-white'
                                : 'bg-black text-white hover:bg-black/85'"
                        >
                            <span>{{ block.data.label }}</span>
                            <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">↗</span>
                        </a>
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
                                <p class="text-sm text-black/45 font-light leading-relaxed max-w-md whitespace-pre-line">{{ feature.detail }}</p>
                            </div>
                        </div>
                    </div>

                </template>
            </div>

            <!-- Other services -->
            <div v-if="otherServices.length" class="max-w-[1400px] mx-auto px-6 md:px-16 pb-20 md:pb-32">
                <p class="kicker flex items-center gap-2 mb-8 md:mb-10"><span class="dot"></span> Other services</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:divide-x divide-black/10 border-t border-black/10">
                    <component
                        :is="isExternal(other.slug) ? 'a' : Link"
                        v-for="other in otherServices"
                        :key="other.slug"
                        :href="serviceHref(other.slug)"
                        :target="isExternal(other.slug) ? '_blank' : null"
                        :rel="isExternal(other.slug) ? 'noopener noreferrer' : null"
                        class="group relative pt-8 pb-10 sm:px-8 first:sm:pl-0 last:sm:pr-0 flex flex-col border-b border-black/10 sm:border-b-0"
                    >
                        <h3 class="display text-xl md:text-2xl text-black mb-2.5 leading-snug" style="font-weight: 400;">{{ other.name }}</h3>
                        <p class="text-sm text-black/45 font-light leading-relaxed flex-1 mb-6">{{ other.description }}</p>
                        <span class="inline-flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-black/50 group-hover:text-black transition-colors duration-300">
                            Learn more
                            <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                        </span>
                    </component>
                </div>
            </div>

            <!-- Closing CTA -->
            <section class="relative bg-[#0e0e0e] text-white overflow-hidden">
                <img
                    :src="ctaBackground"
                    alt=""
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 w-full h-full object-cover opacity-80"
                    draggable="false"
                />
                <!-- Just enough dimming to hold the white type, no more. -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/45 via-black/30 to-black/55"></div>

                <div class="relative max-w-[1400px] mx-auto px-6 md:px-16 pt-28 md:pt-44 pb-40 md:pb-56 text-center flex flex-col items-center">
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

/* Headings from the editor's H2 / H3 buttons, in the same display face the
   hand-built section headings use — the body copy around them stays light grey. */
.prose-service :deep(h1),
.prose-service :deep(h2),
.prose-service :deep(h3),
.prose-service :deep(h4) {
    font-family: 'Fraunces', Georgia, serif;
    font-optical-sizing: auto;
    font-weight: 400;
    color: #000;
    line-height: 1.3;
    letter-spacing: -0.01em;
    margin-top: 2em;
    margin-bottom: 0.6em;
}
.prose-service :deep(h1) { font-size: 1.75rem; }
.prose-service :deep(h2) { font-size: 1.5rem; }
.prose-service :deep(h3) { font-size: 1.25rem; }
.prose-service :deep(h4) { font-size: 1.0625rem; }

@media (min-width: 768px) {
    .prose-service :deep(h1) { font-size: 2.25rem; }
    .prose-service :deep(h2) { font-size: 1.875rem; }
    .prose-service :deep(h3) { font-size: 1.5rem; }
    .prose-service :deep(h4) { font-size: 1.125rem; }
}

/* A heading opening the block shouldn't push itself away from the section top. */
.prose-service :deep(h1:first-child),
.prose-service :deep(h2:first-child),
.prose-service :deep(h3:first-child),
.prose-service :deep(h4:first-child) { margin-top: 0; }

/* Bolding a whole heading in the editor is common; keep it looking like a heading. */
.prose-service :deep(h1 strong),
.prose-service :deep(h2 strong),
.prose-service :deep(h3 strong),
.prose-service :deep(h4 strong) { color: inherit; font-weight: 500; }
.prose-service :deep(ul) { list-style-type: disc; padding-left: 1.25em; margin-bottom: 1em; }
.prose-service :deep(ol) { list-style-type: decimal; padding-left: 1.25em; margin-bottom: 1em; }
.prose-service :deep(li) { display: list-item; margin-bottom: 0.4em; }
.prose-service :deep(a) { color: rgba(0, 0, 0, 0.7); text-decoration: underline; text-underline-offset: 2px; }
</style>
