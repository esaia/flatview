<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useGlobalStore } from "../../../store/useGlobal";
import { getCustomTypeColor, tr } from "../../../composable/helper";
import type { selectDataItem } from "../../../types/DemoTypes";

const props = withDefaults(
  defineProps<{
    data: selectDataItem[];
    disabled?: boolean;
  }>(),
  { disabled: false },
);

const model = defineModel<string>();

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { openReservedFlat, openSoldFlat } = storeToRefs(globalStore);

/** Force any rgb/rgba color to full opacity so the status dot reads as a solid swatch. */
function solidify(color: string | null): string | null {
  if (!color) return null;
  const m = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*[\d.]+)?\)/);
  if (m) {
    const [, r, g, b] = m;
    return `rgb(${r}, ${g}, ${b})`;
  }
  return color;
}

/** Resolve the swatch color for a status value, mirroring the Badge/path color rules. */
function statusColor(value: string): string | null {
  if (value === "all") return null;
  if (value === "available")
    return solidify(getMetaValue("available_flat_color")) || "#ffffff";
  if (value === "reserved")
    return solidify(getMetaValue("reserved_color")) || "#fff759";
  if (value === "sold") return solidify(getMetaValue("sold_color")) || "#db4040";
  return solidify(getCustomTypeColor(value)) || "#9ca3af";
}

function isOptionDisabled(item: selectDataItem): boolean {
  if (props.disabled) return true;
  if (item.value === "reserved" && !openReservedFlat.value) return true;
  if (item.value === "sold" && !openSoldFlat.value) return true;
  return false;
}

const selected = computed(
  () => props.data.find((o) => o.value === model.value) ?? props.data[0],
);

const open = ref(false);
const rootRef = ref<HTMLElement | null>(null);
const triggerRef = ref<HTMLElement | null>(null);
const listRef = ref<HTMLElement | null>(null);
const activeIndex = ref(-1);

const selectableIndexes = computed(() =>
  props.data
    .map((item, i) => (isOptionDisabled(item) ? -1 : i))
    .filter((i) => i !== -1),
);

function scrollActiveIntoView() {
  void nextTick(() => {
    listRef.value
      ?.querySelector<HTMLElement>('[data-active="true"]')
      ?.scrollIntoView({ block: "nearest" });
  });
}

function openMenu() {
  if (props.disabled) return;
  open.value = true;
  activeIndex.value = props.data.findIndex((o) => o.value === model.value);
  scrollActiveIntoView();
}

function closeMenu() {
  open.value = false;
}

function toggle() {
  open.value ? closeMenu() : openMenu();
}

function choose(item: selectDataItem) {
  if (isOptionDisabled(item)) return;
  model.value = item.value;
  closeMenu();
  triggerRef.value?.focus();
}

function moveActive(dir: 1 | -1) {
  const order = selectableIndexes.value;
  if (!order.length) return;
  const pos = order.indexOf(activeIndex.value);
  const next =
    pos === -1 ? order[0] : order[(pos + dir + order.length) % order.length];
  activeIndex.value = next;
  scrollActiveIntoView();
}

function onTriggerKeydown(e: KeyboardEvent) {
  if (e.key === "ArrowDown" || e.key === "ArrowUp") {
    e.preventDefault();
    if (!open.value) openMenu();
    else moveActive(e.key === "ArrowDown" ? 1 : -1);
  } else if (e.key === "Enter" || e.key === " ") {
    e.preventDefault();
    if (!open.value) openMenu();
    else if (activeIndex.value >= 0) choose(props.data[activeIndex.value]);
  } else if (e.key === "Escape") {
    closeMenu();
  }
}

function onDocPointer(e: PointerEvent) {
  if (open.value && rootRef.value && !rootRef.value.contains(e.target as Node)) {
    closeMenu();
  }
}

watch(open, (isOpen) => {
  if (isOpen) document.addEventListener("pointerdown", onDocPointer, true);
  else document.removeEventListener("pointerdown", onDocPointer, true);
});

onMounted(() => {
  if (!props.data.some((o) => o.value === model.value) && props.data[0]) {
    model.value = props.data[0].value;
  }
});

onBeforeUnmount(() => {
  document.removeEventListener("pointerdown", onDocPointer, true);
});
</script>

<template>
  <div ref="rootRef" class="irep-status-select">
    <button
      ref="triggerRef"
      type="button"
      class="irep-status-select__trigger"
      :class="{ 'is-open': open }"
      :disabled="disabled"
      role="combobox"
      aria-haspopup="listbox"
      :aria-expanded="open"
      :aria-label="selected?.title || tr('Status')"
      @click="toggle"
      @keydown="onTriggerKeydown"
    >
      <span
        v-if="selected && statusColor(selected.value)"
        class="irep-status-select__dot"
        :style="{ backgroundColor: statusColor(selected.value)! }"
      />
      <span v-else class="irep-status-select__dot irep-status-select__dot--all" />

      <span class="irep-status-select__value">{{ selected?.title }}</span>

      <svg
        class="irep-status-select__chevron"
        :class="{ 'is-open': open }"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="m6 9 6 6 6-6" />
      </svg>
    </button>

    <Transition name="irep-status-select-pop">
      <ul
        v-if="open"
        ref="listRef"
        class="irep-status-select__menu"
        role="listbox"
        tabindex="-1"
      >
        <li
          v-for="(item, i) in data"
          :key="item.value"
          :data-active="i === activeIndex"
          class="irep-status-select__option"
          :class="{
            'is-disabled': isOptionDisabled(item),
            'is-selected': item.value === model.value,
            'is-active':
              i === activeIndex &&
              !isOptionDisabled(item) &&
              item.value !== model.value,
          }"
          role="option"
          :aria-selected="item.value === model.value"
          :aria-disabled="isOptionDisabled(item)"
          @click="choose(item)"
          @mousemove="activeIndex = i"
        >
          <span
            v-if="statusColor(item.value)"
            class="irep-status-select__dot"
            :style="{ backgroundColor: statusColor(item.value)! }"
          />
          <span
            v-else
            class="irep-status-select__dot irep-status-select__dot--all"
          />

          <span class="irep-status-select__option-label">{{ item.title }}</span>

          <svg
            v-if="item.value === model.value"
            class="irep-status-select__check"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M20 6 9 17l-5-5" />
          </svg>
        </li>
      </ul>
    </Transition>
  </div>
</template>

<style scoped>
.irep-status-select {
  position: relative;
  width: 100%;
}

/* ── Trigger ─────────────────────────────────────────────── */
.irep-status-select__trigger {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  border-radius: 10px;
  background: #ffffff;
  padding: 10px 12px 10px 14px;
  text-align: left;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  outline: none;
  box-shadow: inset 0 0 0 1px #e5e7eb;
  transition:
    box-shadow 0.18s ease,
    background-color 0.18s ease;
}
.irep-status-select__trigger:hover {
  box-shadow: inset 0 0 0 1px #d1d5db;
}
.irep-status-select__trigger.is-open {
  box-shadow: inset 0 0 0 2px var(--primary-color);
}
.irep-status-select__trigger:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.irep-status-select__value {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-transform: capitalize;
}

.irep-status-select__chevron {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: #9ca3af;
  transition: transform 0.2s ease;
}
.irep-status-select__chevron.is-open {
  transform: rotate(180deg);
}

/* ── Status swatch ───────────────────────────────────────── */
.irep-status-select__dot {
  width: 11px;
  height: 11px;
  flex-shrink: 0;
  border-radius: 9999px;
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.12);
}
/* "All" reads as "every status" — a quiet wheel of the palette. */
.irep-status-select__dot--all {
  background: conic-gradient(
    from 90deg,
    #fbbf24 0deg 120deg,
    #ef4444 120deg 240deg,
    #e5e7eb 240deg 360deg
  );
}

/* ── Menu ────────────────────────────────────────────────── */
.irep-status-select__menu {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  z-index: 40;
  max-height: 256px;
  overflow-y: auto;
  margin: 0;
  padding: 6px;
  list-style: none;
  border-radius: 14px;
  border: 1px solid rgba(229, 231, 235, 0.8);
  background: #ffffff;
  box-shadow: 0 18px 40px -16px rgba(16, 24, 40, 0.3);
}

.irep-status-select__option {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  border-radius: 10px;
  padding: 9px 10px;
  font-size: 14px;
  color: #374151;
  transition: background-color 0.14s ease;
}
.irep-status-select__option.is-active {
  background-color: #f9fafb;
}
.irep-status-select__option.is-selected {
  font-weight: 600;
  background-color: color-mix(in srgb, var(--primary-color) 8%, #ffffff);
}
.irep-status-select__option.is-disabled {
  cursor: not-allowed;
  color: #d1d5db;
}
.irep-status-select__option.is-disabled .irep-status-select__dot {
  opacity: 0.4;
}

.irep-status-select__option-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-transform: capitalize;
}

.irep-status-select__check {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: var(--primary-color);
}

/* ── Open / close motion ─────────────────────────────────── */
.irep-status-select-pop-enter-active,
.irep-status-select-pop-leave-active {
  transition:
    opacity 0.16s ease,
    transform 0.16s cubic-bezier(0.22, 1, 0.36, 1);
  transform-origin: top center;
}
.irep-status-select-pop-enter-from,
.irep-status-select-pop-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .irep-status-select-pop-enter-active,
  .irep-status-select-pop-leave-active {
    transition: opacity 0.12s ease;
    transform: none;
  }
  .irep-status-select-pop-enter-from,
  .irep-status-select-pop-leave-to {
    transform: none;
  }
  .irep-status-select__chevron {
    transition: none;
  }
}
</style>
