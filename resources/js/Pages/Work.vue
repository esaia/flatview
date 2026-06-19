<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'

const filters = ['All', 'Web', 'Plugin', 'Real Estate', 'Construction']
const activeFilter = ref('All')

const projects = [
    { name: 'Tbilisi Towers',    category: 'Real Estate', desc: 'Real estate website',  year: 2024 },
    { name: 'SkyView Residences', category: 'Plugin',      desc: 'Floor plan plugin',    year: 2024 },
    { name: 'BuildCore',          category: 'Web',         desc: 'Construction company', year: 2024 },
    { name: 'Axis Group',         category: 'Web',         desc: 'Corporate website',    year: 2023 },
    { name: 'Nova Properties',    category: 'Real Estate', desc: 'Real estate portal',   year: 2023 },
    { name: 'Archi Studio',       category: 'Web',         desc: 'Architecture firm',    year: 2023 },
]

const filteredProjects = computed(() => {
    if (activeFilter.value === 'All') return projects
    return projects.filter(p => p.category === activeFilter.value)
})
</script>

<template>
    <AppLayout active-page="work">
        <div class="max-w-[1400px] mx-auto px-6 md:px-16 pt-14 md:pt-20 pb-20 md:pb-32">

            <!-- Header -->
            <span class="reveal kicker flex items-center gap-2 mb-7" style="--d: 0s;">
                <span class="dot"></span> Portfolio
            </span>
            <div class="reveal flex items-baseline gap-3 md:gap-4 mb-8 md:mb-10 flex-wrap" style="--d: .08s;">
                <h1 class="display text-black"
                    style="font-size: clamp(38px, 6.4vw, 88px); font-weight: 300; line-height: 1.04; letter-spacing: -0.02em;">
                    Selected Work
                </h1>
                <span class="text-[11px] tracking-[0.25em] uppercase text-black/40 border border-black/10 px-2.5 py-1 rounded-full">
                    {{ projects.length }} projects
                </span>
            </div>

            <!-- Filter bar — scrolls horizontally on small screens -->
            <div class="overflow-x-auto -mx-5 sm:-mx-8 md:mx-0 mb-10 md:mb-16">
                <div class="flex items-center flex-nowrap px-5 sm:px-8 md:px-0 border-b border-black/10 min-w-max md:min-w-0">
                    <button
                        v-for="filter in filters"
                        :key="filter"
                        @click="activeFilter = filter"
                        class="px-4 md:px-5 py-3 text-[11px] tracking-[0.25em] uppercase transition-colors duration-200 border-b-2 -mb-px whitespace-nowrap"
                        :class="activeFilter === filter
                            ? 'text-black border-[#5DCAA5]'
                            : 'text-black/30 border-transparent hover:text-black/60'"
                    >
                        {{ filter }}
                    </button>
                </div>
            </div>

            <!-- Project grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                <div
                    v-for="project in filteredProjects"
                    :key="project.name"
                    class="group cursor-pointer"
                >
                    <!-- Image area -->
                    <div class="relative overflow-hidden bg-black/[0.06] aspect-[16/10]">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors duration-300 flex items-center justify-center z-10">
                            <span class="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 text-2xl font-light">
                                →
                            </span>
                        </div>
                        <div class="absolute inset-0 bg-[#1a1a1a] transition-transform duration-500 group-hover:scale-[1.03]"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-white/[0.04] text-4xl md:text-5xl font-bold uppercase tracking-tighter select-none">
                                {{ project.name.split(' ')[0] }}
                            </span>
                        </div>
                    </div>

                    <!-- Project info -->
                    <div class="pt-3 md:pt-4 pb-8 md:pb-10 flex items-start justify-between">
                        <div>
                            <p class="display text-lg text-black leading-snug" style="font-weight: 400;">{{ project.name }}</p>
                            <p class="text-xs md:text-sm text-black/40 mt-1 font-light">{{ project.desc }}</p>
                        </div>
                        <span class="text-xs text-black/25 font-medium mt-1 tabular-nums">{{ project.year }}</span>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="filteredProjects.length === 0" class="py-20 md:py-24 text-center">
                <p class="text-black/30 text-sm tracking-widest uppercase">No projects in this category yet</p>
            </div>

        </div>
    </AppLayout>
</template>
