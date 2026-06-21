<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue";
import { tr } from "../../../composable/helper";
import { countries, flagEmoji, type Country } from "../../../composable/countries";

const props = withDefaults(
    defineProps<{
        label?: string;
        error?: string;
        defaultIso?: string;
    }>(),
    {
        label: "Phone",
        error: "",
        defaultIso: "UA",
    },
);

// v-model holds the full phone string (e.g. "+380501234567").
const model = defineModel<string>({ default: "" });

const country = ref<Country>(
    countries.find((c) => c.iso === props.defaultIso) ?? countries[0],
);
const number = ref("");

const open = ref(false);
const search = ref("");
const focused = ref(false);
const root = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return countries;
    return countries.filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            c.dial.includes(q.replace("+", "")),
    );
});

const syncModel = () => {
    const digits = number.value.replace(/[^\d]/g, "");
    model.value = digits ? `+${country.value.dial}${digits}` : "";
};

const selectCountry = (c: Country) => {
    country.value = c;
    open.value = false;
    search.value = "";
    syncModel();
};

const toggle = async () => {
    open.value = !open.value;
    if (open.value) {
        await nextTick();
        searchInput.value?.focus();
    }
};

const onClickOutside = (e: MouseEvent) => {
    if (open.value && root.value && !root.value.contains(e.target as Node)) {
        open.value = false;
        search.value = "";
    }
};

onMounted(() => document.addEventListener("click", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("click", onClickOutside));
</script>

<template>
    <label
        ref="root"
        class="irep-phone ire-relative ire-flex ire-w-full ire-flex-col ire-items-start ire-text-black"
    >
        <div
            class="irep-phone__field ire-relative ire-flex ire-w-full ire-items-stretch ire-rounded-md ire-ring-[1px] ire-transition-all"
            :class="
                error
                    ? 'ire-ring-red-400'
                    : focused
                      ? 'ire-ring-black'
                      : 'ire-ring-gray-200'
            "
        >
            <span
                class="ire-pointer-events-none ire-absolute ire-left-3 ire-top-1.5 ire-text-xs ire-text-gray-600"
            >
                {{ tr(label) }}
            </span>

            <!-- Country selector -->
            <button
                type="button"
                @click="toggle"
                class="irep-phone__country ire-flex ire-shrink-0 ire-items-center ire-gap-1 ire-rounded-l-md ire-pl-3 ire-pr-2 ire-pt-4 ire-outline-none"
            >
                <span class="ire-text-xl ire-leading-none">{{
                    flagEmoji(country.iso)
                }}</span>
                <svg
                    class="ire-h-4 ire-w-4 ire-text-gray-500 ire-transition-transform"
                    :class="open ? 'ire-rotate-180' : ''"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>

            <!-- Dial code + number -->
            <input
                v-model="number"
                type="tel"
                inputmode="tel"
                @focus="focused = true"
                @blur="focused = false"
                @input="syncModel"
                class="no-spinner ire-w-full ire-rounded-r-md !ire-border-none ire-bg-transparent ire-pb-2 ire-pr-3 ire-pt-5 ire-text-base !ire-outline-none"
                :placeholder="`+${country.dial}`"
            />

            <!-- Dropdown -->
            <Transition name="ire-fade-in-out">
                <div
                    v-if="open"
                    class="irep-phone__dropdown ire-absolute ire-left-0 ire-top-full ire-z-50 ire-mt-1 ire-flex ire-max-h-72 ire-w-full ire-flex-col ire-overflow-hidden ire-rounded-md ire-bg-white ire-shadow-lg ire-ring-[1px] ire-ring-gray-200"
                >
                    <div class="ire-border-b ire-border-gray-100 ire-p-2">
                        <input
                            ref="searchInput"
                            v-model="search"
                            type="text"
                            :placeholder="tr('Search')"
                            class="ire-w-full ire-rounded ire-px-2 ire-py-1.5 ire-text-sm !ire-outline-none ire-ring-[1px] ire-ring-gray-200 focus:ire-ring-black"
                        />
                    </div>
                    <ul class="ire-flex-1 ire-overflow-y-auto">
                        <li
                            v-for="c in filtered"
                            :key="c.iso + c.dial"
                            @click="selectCountry(c)"
                            class="ire-flex ire-cursor-pointer ire-items-center ire-gap-2 ire-px-3 ire-py-2 ire-text-sm hover:ire-bg-gray-100"
                            :class="
                                c.iso === country.iso ? 'ire-bg-blue-50' : ''
                            "
                        >
                            <span class="ire-text-lg ire-leading-none">{{
                                flagEmoji(c.iso)
                            }}</span>
                            <span class="ire-flex-1 ire-truncate">{{
                                c.name
                            }}</span>
                            <span class="ire-text-gray-500">+{{ c.dial }}</span>
                        </li>
                        <li
                            v-if="!filtered.length"
                            class="ire-px-3 ire-py-2 ire-text-sm ire-text-gray-400"
                        >
                            {{ tr("No results") }}
                        </li>
                    </ul>
                </div>
            </Transition>
        </div>

        <Transition name="ire-error-slide">
            <div
                v-if="error"
                class="irep-phone__error ire-mt-1 ire-text-xs ire-text-red-600"
            >
                {{ error }}
            </div>
        </Transition>
    </label>
</template>

<style scoped>
.ire-error-slide-enter-active,
.ire-error-slide-leave-active {
    transition: all 0.2s ease;
}
.ire-error-slide-enter-from,
.ire-error-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
