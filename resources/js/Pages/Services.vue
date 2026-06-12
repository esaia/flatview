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
        <div class="max-w-[1400px] mx-auto px-5 sm:px-8 md:px-16 pt-14 md:pt-20 pb-20 md:pb-32">

            <!-- Hero text -->
            <div class="mb-14 md:mb-24 max-w-2xl">
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold uppercase tracking-tight text-black leading-none mb-5 md:mb-6">
                    What we do
                </h1>
                <p class="text-sm md:text-base text-black/40 font-light leading-relaxed">
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
                        class="absolute top-0 right-4 md:right-8 text-[6rem] md:text-[7rem] font-bold leading-none select-none text-black"
                        style="opacity: 0.05;"
                    >
                        {{ service.number }}
                    </span>

                    <div class="relative flex flex-col flex-1">
                        <p class="text-xs font-medium tracking-[0.2em] uppercase text-black/25 mb-4">{{ service.number }}</p>
                        <h2 class="text-lg md:text-xl font-bold text-black mb-3 md:mb-4 leading-snug">{{ service.name }}</h2>
                        <p class="text-sm text-black/45 font-light leading-relaxed flex-1">{{ service.description }}</p>
                        <a href="#" class="inline-flex items-center gap-1.5 text-xs font-medium tracking-widest uppercase text-black mt-7 md:mt-8 group">
                            <span>Learn more</span>
                            <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Why Merisimo -->
            <div>
                <p class="text-xs font-medium tracking-[0.2em] uppercase text-black/25 mb-8 md:mb-10">Why Merisimo?</p>
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
