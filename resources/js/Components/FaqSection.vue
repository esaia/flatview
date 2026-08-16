<script setup>
/**
 * Shared FAQ accordion — same questions on the services and about pages.
 * Content is edited once under Website → FAQ.
 */
import { ref } from 'vue'

const props = defineProps({
    kicker: { type: String, default: 'Questions' },
    headline: { type: String, default: 'Answers, before you ask' },
    intro: { type: String, default: '' },
    items: { type: Array, default: () => [] },
})

// One row open at a time; clicking the open row closes it.
const openIndex = ref(null)
const toggle = (index) => (openIndex.value = openIndex.value === index ? null : index)

const num = (i) => String(i + 1).padStart(2, '0')
</script>

<template>
    <section v-if="props.items.length" class="px-6 md:px-16 py-24 md:py-36">
        <div class="max-w-[1400px] mx-auto grid grid-cols-1 md:grid-cols-12 gap-y-12 md:gap-x-16">
            <!-- sticky intro rail -->
            <div class="md:col-span-4">
                <div class="md:sticky md:top-28">
                    <p class="kicker flex items-center gap-2 mb-6"><span class="dot"></span> {{ props.kicker }}</p>

                    <h2 class="display text-black leading-[1.08] whitespace-pre-line"
                        style="font-size: clamp(28px, 3vw, 44px); font-weight: 300; letter-spacing: -0.01em;">
                        {{ props.headline }}
                    </h2>

                    <p v-if="props.intro" class="mt-5 text-sm text-black/45 font-light leading-relaxed max-w-xs whitespace-pre-line">
                        {{ props.intro }}
                    </p>
                </div>
            </div>

            <!-- questions -->
            <div class="md:col-span-7 md:col-start-6">
                <div
                    v-for="(item, i) in props.items"
                    :key="item.question"
                    class="border-t border-black/10 last:border-b"
                >
                    <button
                        type="button"
                        class="faq-row group w-full grid grid-cols-[auto_1fr_auto] items-start gap-x-5 md:gap-x-8 py-6 md:py-8 text-left"
                        :aria-expanded="openIndex === i"
                        @click="toggle(i)"
                    >
                        <span class="display tabular-nums text-sm leading-none pt-1.5 transition-colors duration-500"
                              :class="openIndex === i ? 'text-black/70' : 'text-black/25 group-hover:text-black/50'">
                            {{ num(i) }}
                        </span>

                        <span class="display text-lg md:text-xl text-black leading-snug" style="font-weight: 400;">
                            {{ item.question }}
                        </span>

                        <!-- plus that rotates into a minus -->
                        <span class="relative mt-1.5 h-3 w-3 shrink-0" aria-hidden="true">
                            <span class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-black/40 transition-colors duration-300 group-hover:bg-black"></span>
                            <span
                                class="absolute inset-y-0 left-1/2 w-px -translate-x-1/2 bg-black/40 transition-all duration-500 group-hover:bg-black"
                                :class="openIndex === i ? 'rotate-90 opacity-0' : 'rotate-0 opacity-100'"
                            ></span>
                        </span>
                    </button>

                    <!-- answer: grid-rows trick animates to the content's own height -->
                    <div
                        class="grid transition-[grid-template-rows,opacity] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
                        :class="openIndex === i ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                    >
                        <div class="overflow-hidden">
                            <p class="pl-[3.25rem] md:pl-[4.5rem] pr-8 pb-7 md:pb-9 text-sm text-black/45 font-light leading-relaxed max-w-xl whitespace-pre-line">
                                {{ item.answer }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.tabular-nums { font-variant-numeric: tabular-nums; }
</style>
