<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from "vue";
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

// v-model holds the full phone string in E.164-ish form (e.g. "+380501234567").
const model = defineModel<string>({ default: "" });

// National-number formatting masks for the well-known countries. "#" consumes
// one digit; every other character is a literal separator. Unlisted countries
// fall back to a plain "+<dial> <rest>" rendering.
const formats: Record<string, string> = {
    US: "(###) ###-####",
    CA: "(###) ###-####",
    GB: "#### ######",
    DE: "### #######",
    FR: "# ## ## ## ##",
    IT: "### ### ####",
    ES: "### ## ## ##",
    RU: "(###) ###-##-##",
    UA: "## ### ## ##",
    GE: "### ## ## ##",
    TR: "### ### ## ##",
    IN: "##### #####",
    CN: "### #### ####",
    JP: "## #### ####",
    BR: "(##) #####-####",
    AU: "### ### ###",
    AE: "## ### ####",
    PL: "### ### ###",
    NL: "# ## ## ## ##",
};

// All digits the user has entered, dial code included (no leading "+").
const digits = ref("");

const open = ref(false);
const focused = ref(false);
const query = ref("");
const activeIndex = ref(0);
const root = ref<HTMLElement | null>(null);
const list = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);

// Longest dial-code prefix wins; "+1" resolves to the US (shared NANP code).
const detectCountry = (d: string): Country | null => {
    let best: Country | null = null;
    for (const c of countries) {
        if (c.dial && d.startsWith(c.dial)) {
            if (!best || c.dial.length > best.dial.length) best = c;
        }
    }
    if (best && best.dial === "1")
        return countries.find((c) => c.iso === "US") ?? best;
    return best;
};

const selected = computed(() => detectCountry(digits.value));

const applyTemplate = (national: string, tpl: string) => {
    let out = "";
    let i = 0;
    for (const ch of tpl) {
        if (i >= national.length) break;
        if (ch === "#") out += national[i++];
        else out += ch;
    }
    if (i < national.length) out += " " + national.slice(i);
    return out;
};

const formatDisplay = (d: string) => {
    if (!d) return "+";
    const c = detectCountry(d);
    if (!c) return "+" + d;
    const national = d.slice(c.dial.length);
    if (!national) return "+" + c.dial;
    const tpl = formats[c.iso];
    return tpl
        ? `+${c.dial} ${applyTemplate(national, tpl)}`
        : `+${c.dial} ${national}`;
};

// Cap input: masked countries stop at their template length, others at E.164's 15.
const maxDigitsFor = (d: string) => {
    const c = detectCountry(d);
    if (c && formats[c.iso])
        return c.dial.length + (formats[c.iso].match(/#/g)?.length ?? 0);
    return 15;
};

// The masked text shown in the field. Bound via :value (not v-model) so we can
// force the DOM to re-sync even when the sanitized value is unchanged — that
// edge case is what made the leading "+" vanish on delete.
const display = ref(formatDisplay(""));

const onInput = (e: Event) => {
    const el = e.target as HTMLInputElement;
    const d = el.value.replace(/\D/g, "");
    digits.value = d.slice(0, maxDigitsFor(d));
    el.value = formatDisplay(digits.value);
};

watch(
    digits,
    (d) => {
        display.value = formatDisplay(d);
        model.value = d ? `+${d}` : "";
    },
    { immediate: true },
);

// Picking from the dropdown swaps the dial code, keeping any typed national part.
const selectCountry = (c: Country) => {
    const cur = selected.value;
    const national = cur ? digits.value.slice(cur.dial.length) : "";
    digits.value = c.dial + national;
    open.value = false;
    query.value = "";
    nextTick(() => inputEl.value?.focus());
};

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return countries;
    return countries.filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            c.dial.includes(q.replace("+", "")),
    );
});

// Split a country name into segments so the matched part can be highlighted.
const matchParts = (name: string) => {
    const q = query.value.trim();
    if (!q) return [{ text: name, hit: false }];
    const i = name.toLowerCase().indexOf(q.toLowerCase());
    if (i === -1) return [{ text: name, hit: false }];
    return [
        { text: name.slice(0, i), hit: false },
        { text: name.slice(i, i + q.length), hit: true },
        { text: name.slice(i + q.length), hit: false },
    ];
};

const scrollActiveIntoView = () => {
    nextTick(() => {
        const items = list.value?.querySelectorAll("li");
        items?.[activeIndex.value]?.scrollIntoView({ block: "nearest" });
    });
};

const toggle = () => {
    open.value = !open.value;
    query.value = "";
    if (open.value) {
        const i = filtered.value.findIndex(
            (c) => c.iso === selected.value?.iso,
        );
        activeIndex.value = i < 0 ? 0 : i;
        scrollActiveIntoView();
    }
};

const onClickOutside = (e: MouseEvent) => {
    if (open.value && root.value && !root.value.contains(e.target as Node)) {
        open.value = false;
        query.value = "";
    }
};

// Type-to-search while the dropdown is open (keystrokes don't reach the field).
const onKeydown = (e: KeyboardEvent) => {
    if (!open.value) return;
    if (e.key === "Escape") {
        open.value = false;
        query.value = "";
        return;
    }
    if (e.key === "ArrowDown") {
        e.preventDefault();
        activeIndex.value = Math.min(
            activeIndex.value + 1,
            filtered.value.length - 1,
        );
        scrollActiveIntoView();
        return;
    }
    if (e.key === "ArrowUp") {
        e.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
        scrollActiveIntoView();
        return;
    }
    if (e.key === "Enter") {
        e.preventDefault();
        const c = filtered.value[activeIndex.value];
        if (c) selectCountry(c);
        return;
    }
    if (e.key === "Backspace") {
        e.preventDefault();
        query.value = query.value.slice(0, -1);
        return;
    }
    if (e.key.length === 1 && /\S/.test(e.key)) {
        e.preventDefault();
        query.value += e.key;
    }
};

watch(query, () => {
    activeIndex.value = 0;
    nextTick(() => list.value?.scrollTo({ top: 0 }));
});

onMounted(() => {
    document.addEventListener("click", onClickOutside);
    document.addEventListener("keydown", onKeydown);
});
onBeforeUnmount(() => {
    document.removeEventListener("click", onClickOutside);
    document.removeEventListener("keydown", onKeydown);
});
</script>

<template>
    <div
        ref="root"
        class="irep-phone ire-relative ire-flex ire-w-full ire-flex-col ire-items-start ire-text-black"
    >
        <div
            class="irep-phone__field ire-relative ire-flex ire-w-full ire-items-stretch ire-rounded-md ire-ring-[1px] ire-transition-all"
            :class="
                error
                    ? 'ire-ring-red-400'
                    : focused || open
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
                class="irep-phone__country ire-flex ire-shrink-0 ire-items-center ire-gap-1 ire-rounded-l-md ire-pr-2 ire-pt-4 ire-outline-none"
            >
                <span class="ire-text-xl ire-leading-none">{{
                    selected ? flagEmoji(selected.iso) : "🌐"
                }}</span>
                <svg
                    width="16"
                    height="16"
                    class="ire-text-gray-500 ire-transition-transform"
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

            <!-- Number (dial code typed inline, only "+" is fixed) -->
            <input
                ref="inputEl"
                :value="display"
                @input="onInput"
                type="tel"
                inputmode="tel"
                @focus="focused = true"
                @blur="focused = false"
                class="irep-phone__input no-spinner ire-w-full ire-rounded-r-md !ire-border-none ire-bg-transparent ire-pb-2 ire-pr-3 ire-pt-5 ire-text-base !ire-outline-none"
            />

            <!-- Dropdown -->
            <Transition name="ire-fade-in-out">
                <div
                    v-if="open"
                    class="irep-phone__dropdown ire-absolute ire-left-0 ire-top-full ire-z-40 ire-mt-1 ire-flex ire-flex-col ire-overflow-hidden ire-rounded-md ire-bg-white ire-shadow-lg ire-ring-[1px] ire-ring-gray-200"
                >
                    <!-- Typed-query hint (only while searching) -->
                    <div
                        v-if="query"
                        class="irep-phone__hint ire-flex ire-items-center ire-gap-2 ire-px-3 ire-py-2 ire-text-sm ire-text-gray-500"
                    >
                        <svg
                            width="16"
                            height="16"
                            class="ire-shrink-0"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span class="irep-phone__hint-text ire-text-black">{{
                            query
                        }}</span>
                    </div>

                    <ul ref="list" class="ire-flex-1 ire-overflow-y-auto">
                        <li
                            v-for="(c, idx) in filtered"
                            :key="c.iso + c.dial"
                            @click="selectCountry(c)"
                            @mousemove="activeIndex = idx"
                            class="ire-flex ire-cursor-pointer ire-items-center ire-gap-2 ire-whitespace-nowrap ire-px-3 ire-py-2 ire-text-base"
                            :class="idx === activeIndex ? 'ire-bg-gray-100' : ''"
                        >
                            <span class="ire-text-lg ire-leading-none">{{
                                flagEmoji(c.iso)
                            }}</span>
                            <span>
                                <template
                                    v-for="(part, i) in matchParts(c.name)"
                                    :key="i"
                                    ><span
                                        v-if="part.text"
                                        :class="part.hit ? 'irep-phone__hit' : ''"
                                        >{{ part.text }}</span
                                    ></template
                                >+{{ c.dial }}
                            </span>
                        </li>
                        <li
                            v-if="!filtered.length"
                            class="ire-px-3 ire-py-3 ire-text-sm ire-text-gray-400"
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
    </div>
</template>

<style scoped>
.irep-phone__country {
    padding-left: 0.75rem;
}

.irep-phone__input {
    padding-left: 0.5rem;
}

/* Kill the @tailwindcss/forms default focus ring (blue box-shadow + border). */
.irep-phone__input:focus,
.irep-phone__input:focus-visible {
    outline: none;
    border-color: transparent;
    box-shadow: none;
}

.irep-phone__dropdown {
    width: max-content;
    min-width: 100%;
    max-width: 360px;
    max-height: 18rem;
}

.irep-phone__hint {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #fff;
    border-bottom: 1px solid #f3f4f6;
}

.irep-phone__hint-text {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.irep-phone__hit {
    font-weight: 700;
    background: #fde68a;
    border-radius: 2px;
    padding: 0 1px;
}

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
