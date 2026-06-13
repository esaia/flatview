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
        <div class="max-w-[1400px] mx-auto px-5 sm:px-8 md:px-16 pt-14 md:pt-20 pb-20 md:pb-32">

            <!-- Header -->
            <div class="flex items-baseline gap-3 md:gap-4 mb-5 md:mb-6 flex-wrap">
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold uppercase tracking-tight text-black leading-none">
                    Selected Work
                </h1>
                <span class="text-xs font-medium tracking-widest uppercase text-black/30 border border-black/10 px-2.5 py-1 rounded-full">
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
                        class="px-4 md:px-5 py-3 text-xs md:text-sm font-medium tracking-wide transition-colors duration-200 border-b-2 -mb-px whitespace-nowrap"
                        :class="activeFilter === filter
                            ? 'text-black border-black'
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
                            <p class="text-sm md:text-base font-semibold text-black tracking-tight">{{ project.name }}</p>
                            <p class="text-xs md:text-sm text-black/40 mt-0.5 font-light">{{ project.desc }}</p>
                        </div>
                        <span class="text-xs text-black/25 font-medium mt-0.5">{{ project.year }}</span>
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
