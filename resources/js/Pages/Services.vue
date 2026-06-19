<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const services = [
    {
        number: '01',
        name: 'Website development',
        description: 'Custom websites for construction and real estate companies. WordPress or custom-built. Fast, SEO-ready, mobile-first — designed to convert visitors into clients.',
    },
    {
        number: '02',
        name: 'Floor plan plugin',
        description: 'Interactive building visualizer. Browse floors, select apartments, view availability and pricing — embedded directly on your website with no third-party platform needed.',
    },
    {
        number: '03',
        name: 'Maintenance & support',
        description: 'Ongoing updates, hosting management, and technical support so your digital presence stays fast, secure, and reliable long after launch.',
    },
]

const valueProps = [
    { label: 'Niche focused',   detail: 'We work exclusively in construction and real estate.' },
    { label: 'Fast delivery',   detail: 'Typical project turnaround: 3–6 weeks.' },
    { label: 'EU-ready',        detail: 'GDPR compliant, multi-language, EU-hosted.' },
    { label: 'Ongoing support', detail: 'Retainer options from day one.' },
]
</script>

<template>
    <AppLayout active-page="services">
        <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-14 md:pt-20 pb-20 md:pb-32">

            <!-- Hero text -->
            <div class="mb-14 md:mb-24 max-w-2xl">
                <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: 0s;">
                    <span class="dot"></span> Our Services
                </span>
                <h1 class="reveal display text-black mb-5 md:mb-6"
                    style="--d: .08s; font-size: clamp(38px, 6.4vw, 88px); font-weight: 300; line-height: 1.04; letter-spacing: -0.02em;">
                    What we do
                </h1>
                <p class="reveal text-sm md:text-base text-black/40 font-light leading-relaxed" style="--d: .16s;">
                    We build digital products for the construction and real estate sector —
                    from marketing websites to interactive tools that help sell properties faster.
                </p>
            </div>

            <!-- Three service blocks -->
            <div class="grid grid-cols-1 md:grid-cols-3 md:divide-x divide-black/10 mb-16 md:mb-32">
                <div
                    v-for="service in services"
                    :key="service.number"
                    class="relative pt-8 pb-10 md:px-10 first:md:pl-0 last:md:pr-0 flex flex-col border-b border-black/10 md:border-b-0 last:border-b-0"
                >
                    <!-- Faded number -->
                    <span
                        class="display absolute top-0 right-4 md:right-8 text-[6rem] md:text-[7rem] leading-none select-none text-black"
                        style="opacity: 0.05;"
                    >
                        {{ service.number }}
                    </span>

                    <div class="relative flex flex-col flex-1">
                        <p class="kicker text-black/45 mb-4">{{ service.number }}</p>
                        <h2 class="display text-2xl text-black mb-3 md:mb-4 leading-snug" style="font-weight: 400;">{{ service.name }}</h2>
                        <p class="text-sm text-black/45 font-light leading-relaxed flex-1">{{ service.description }}</p>
                        <a href="#" class="inline-flex items-center gap-2 text-[11px] tracking-[0.25em] uppercase text-black/55 hover:text-black transition-colors duration-300 mt-7 md:mt-8 group">
                            <span>Learn more</span>
                            <span class="inline-block transition-transform duration-300 group-hover:translate-x-1">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Why Merisimo -->
            <div>
                <p class="kicker flex items-center gap-2 mb-8 md:mb-10"><span class="dot"></span> Why Merisimo?</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-0 border-t border-black/10">
                    <div
                        v-for="prop in valueProps"
                        :key="prop.label"
                        class="py-6 md:py-8 pr-6 md:pr-10 border-b border-black/10 md:border-b-0"
                    >
                        <h3 class="text-sm font-semibold text-black mb-1.5 md:mb-2">{{ prop.label }}</h3>
                        <p class="text-xs md:text-sm text-black/40 font-light leading-relaxed">{{ prop.detail }}</p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
