<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { countries, flagEmoji } from '@/data/countries'

const props = defineProps({
    modelValue: { type: String, default: '+995' }, // selected dial code
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const activeIndex = ref(0)
const rootEl = ref(null)
const searchEl = ref(null)
const listEl = ref(null)

// Several countries share a dial code (+1 → US, Canada, …), so remember the
// exact country the user picked rather than re-deriving it from the code.
const selectedIso2 = ref(
    (countries.find((c) => c.code === props.modelValue) ?? { iso2: 'GE' }).iso2
)

const selected = computed(
    () =>
        countries.find((c) => c.iso2 === selectedIso2.value && c.code === props.modelValue) ??
        countries.find((c) => c.code === props.modelValue) ??
        countries.find((c) => c.iso2 === 'GE')
)

// Keep the highlight in sync when the code is changed from outside (e.g. reset).
watch(
    () => props.modelValue,
    (code) => {
        const current = countries.find((c) => c.iso2 === selectedIso2.value)
        if (!current || current.code !== code) {
            selectedIso2.value = (countries.find((c) => c.code === code) ?? { iso2: 'GE' }).iso2
        }
    }
)

const results = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return countries
    return countries.filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            c.code.includes(q) ||
            c.iso2.toLowerCase() === q
    )
})

function toggle() {
    open.value ? close() : openPanel()
}

async function openPanel() {
    open.value = true
    query.value = ''
    // Start the highlight on the currently selected country.
    activeIndex.value = Math.max(
        0,
        countries.findIndex((c) => c.iso2 === selectedIso2.value)
    )
    await nextTick()
    searchEl.value?.focus()
    scrollActiveIntoView('auto')
}

function close() {
    open.value = false
}

function choose(country) {
    selectedIso2.value = country.iso2
    emit('update:modelValue', country.code)
    close()
}

function onSearch() {
    activeIndex.value = 0
    scrollActiveIntoView('auto')
}

function move(delta) {
    if (!results.value.length) return
    const next = activeIndex.value + delta
    activeIndex.value = (next + results.value.length) % results.value.length
    scrollActiveIntoView('smooth')
}

function chooseActive() {
    const country = results.value[activeIndex.value]
    if (country) choose(country)
}

function scrollActiveIntoView(behavior) {
    nextTick(() => {
        const el = listEl.value?.querySelector('[data-active="true"]')
        el?.scrollIntoView({ block: 'nearest', behavior })
    })
}

function onKeydown(e) {
    if (!open.value) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault()
            openPanel()
        }
        return
    }
    switch (e.key) {
        case 'ArrowDown': e.preventDefault(); move(1); break
        case 'ArrowUp':   e.preventDefault(); move(-1); break
        case 'Enter':     e.preventDefault(); chooseActive(); break
        case 'Escape':    e.preventDefault(); close(); break
        case 'Tab':       close(); break
    }
}

function onDocClick(e) {
    if (rootEl.value && !rootEl.value.contains(e.target)) close()
}

watch(open, (v) => {
    if (v) document.addEventListener('mousedown', onDocClick)
    else document.removeEventListener('mousedown', onDocClick)
})
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocClick))
</script>

<template>
    <div ref="rootEl" class="relative" @keydown="onKeydown">
        <!-- Trigger -->
        <button
            type="button"
            @click="toggle"
            :aria-expanded="open"
            aria-haspopup="listbox"
            aria-label="Select country dial code"
            class="flex items-center gap-2 text-sm font-light text-black focus:outline-none"
        >
            <span class="text-base leading-none" aria-hidden="true">{{ flagEmoji(selected.iso2) }}</span>
            <span>{{ selected.code }}</span>
            <svg
                class="w-2.5 h-2.5 text-black/35 transition-transform duration-200"
                :class="{ 'rotate-180': open }"
                viewBox="0 0 12 12" fill="none" aria-hidden="true"
            >
                <path d="M3 4.5 L6 7.5 L9 4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <!-- Panel -->
        <Transition name="pop">
            <div
                v-if="open"
                class="absolute left-0 top-full mt-3 z-50 w-72 max-w-[80vw] bg-white ring-1 ring-black/10 rounded-md shadow-[0_18px_50px_-12px_rgba(0,0,0,0.22)] overflow-hidden"
            >
                <!-- Search -->
                <div class="flex items-center gap-2.5 px-4 py-3 border-b border-black/[0.07]">
                    <svg class="w-3.5 h-3.5 text-black/30 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.3" />
                        <path d="M10.5 10.5 L14 14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
                    </svg>
                    <input
                        ref="searchEl"
                        v-model="query"
                        @input="onSearch"
                        type="text"
                        placeholder="Search country"
                        class="w-full bg-transparent border-none text-sm text-black placeholder-black/25 outline-none focus:ring-0 p-0 font-light"
                        aria-label="Search country"
                    />
                </div>

                <!-- List -->
                <ul
                    ref="listEl"
                    role="listbox"
                    class="max-h-64 overflow-y-auto py-1 overscroll-contain"
                >
                    <li
                        v-for="(c, i) in results"
                        :key="c.iso2 + c.code"
                        role="option"
                        :aria-selected="c.iso2 === selectedIso2"
                        :data-active="i === activeIndex"
                        @click="choose(c)"
                        @mousemove="activeIndex = i"
                        class="flex items-center gap-3 px-4 py-2.5 cursor-pointer text-sm transition-colors duration-100"
                        :class="i === activeIndex ? 'bg-black/[0.04]' : ''"
                    >
                        <span class="text-base leading-none shrink-0" aria-hidden="true">{{ flagEmoji(c.iso2) }}</span>
                        <span class="flex-1 min-w-0 truncate text-black/85">{{ c.name }}</span>
                        <span class="text-black/40 tabular-nums">{{ c.code }}</span>
                        <span
                            class="w-1.5 h-1.5 rounded-full shrink-0 transition-opacity duration-150"
                            :class="c.iso2 === selectedIso2 ? 'opacity-100' : 'opacity-0'"
                            style="background:#5DCAA5"
                            aria-hidden="true"
                        ></span>
                    </li>

                    <li v-if="!results.length" class="px-4 py-6 text-center text-sm text-black/35">
                        No matches
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.pop-enter-active { transition: opacity .16s ease, transform .16s ease; }
.pop-leave-active { transition: opacity .12s ease, transform .12s ease; }
.pop-enter-from   { opacity: 0; transform: translateY(-6px) scale(.985); }
.pop-leave-to     { opacity: 0; transform: translateY(-4px) scale(.99); }

@media (prefers-reduced-motion: reduce) {
    .pop-enter-active, .pop-leave-active { transition: none; }
    .pop-enter-from, .pop-leave-to { transform: none; }
}
</style>
