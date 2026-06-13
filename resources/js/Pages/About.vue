<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const stats = [
    { label: 'Founded',  value: '2024' },
    { label: 'Location', value: 'Tbilisi, Georgia' },
    { label: 'Markets',  value: 'Georgia & EU' },
    { label: 'Focus',    value: 'Construction & Real Estate' },
    { label: 'Language', value: 'English interface' },
]

const team = [
    { name: 'Merab Simonishvili', role: 'Founder & Developer' },
    { name: 'Creative Partner',   role: 'Design & Strategy' },
]
</script>

<template>
    <AppLayout active-page="about">
        <div class="max-w-[1400px] mx-auto px-5 sm:px-8 md:px-16 pt-14 md:pt-20 pb-20 md:pb-32">

            <!-- Headline -->
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold uppercase tracking-tight text-black leading-none mb-12 md:mb-20">
                We build<br>for builders
            </h1>

            <!-- Two-column body -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-24 mb-16 md:mb-32">

                <!-- Left: long-form text -->
                <div class="space-y-5 md:space-y-6 text-sm text-black/55 font-light leading-[1.85]">
                    <p>
                        Merisimo is a boutique web development agency founded in 2024, specialising in digital products
                        for the construction and real estate sector. We build websites and interactive tools that help
                        developers, agencies, and project teams sell properties and communicate their work online.
                    </p>
                    <p>
                        Our flagship product is an interactive floor plan plugin that lets buyers browse buildings, select
                        apartments by floor and status, and view pricing — all directly embedded in your existing website.
                        No third-party platform. No subscriptions for your clients. Just a clean, fast visualizer that you own.
                    </p>
                    <p>
                        We operate at the intersection of design and engineering. Every project is built from scratch —
                        no templates, no page builders — with obsessive attention to performance, accessibility, and long-term
                        maintainability.
                    </p>
                    <p>
                        Based in Tbilisi, we work with clients across Georgia and the European Union. Our team is small by
                        design: fewer projects, deeper focus, better outcomes.
                    </p>
                </div>

                <!-- Right: stats -->
                <div>
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="flex items-baseline justify-between py-4 md:py-5 border-b border-black/[0.07] last:border-b-0"
                    >
                        <span class="text-xs font-medium tracking-[0.15em] uppercase text-black/30">{{ stat.label }}</span>
                        <span class="text-sm font-medium text-black">{{ stat.value }}</span>
                    </div>
                </div>

            </div>

            <!-- Team section -->
            <div>
                <p class="text-xs font-medium tracking-[0.2em] uppercase text-black/25 mb-8 md:mb-10">Team</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-1">
                    <div
                        v-for="member in team"
                        :key="member.name"
                        class="group cursor-default"
                    >
                        <!-- Photo placeholder -->
                        <div class="aspect-[3/4] bg-black/[0.05] mb-3 md:mb-4 overflow-hidden">
                            <div class="w-full h-full flex items-end p-4 md:p-5">
                                <div class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-black/10"></div>
                            </div>
                        </div>
                        <p class="text-sm font-semibold text-black">{{ member.name }}</p>
                        <p class="text-xs text-black/35 mt-0.5 font-light">{{ member.role }}</p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
