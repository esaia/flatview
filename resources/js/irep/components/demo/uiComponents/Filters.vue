<script setup lang="ts">
import PreviewSelect from "../form/PreviewSelect.vue";
import RangeInput from "../form/Range.vue";
import { tr, currencySymbol, getAreaUnitLabel } from "../../../composable/helper";
import { useGlobalStore } from "../../../store/useGlobal";
import { computed, nextTick, onMounted, ref, watch } from "vue";
import ClearFiltersButton from "./ClearFiltersButton.vue";

const globalStore = useGlobalStore();

const model = defineModel<any>("filtersObject");
const isReady = ref(false);

function normalizeFilterOptionsMeta(raw: unknown): Record<string, unknown> {
  if (raw == null) return {};
  if (typeof raw === "object" && !Array.isArray(raw)) {
    return raw as Record<string, unknown>;
  }
  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw) as unknown;
      if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
        return parsed as Record<string, unknown>;
      }
    } catch {
      /* ignore invalid json */
    }
  }
  return {};
}

function normalizeRangeOption(
  raw: unknown,
  fallback: { min: number; max: number; step: number },
) {
  let obj: unknown = raw;
  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw) as unknown;
      if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
        obj = parsed;
      } else {
        return fallback;
      }
    } catch {
      return fallback;
    }
  }
  if (obj && typeof obj === "object" && !Array.isArray(obj)) {
    const min = Number((obj as { min?: unknown }).min);
    const max = Number((obj as { max?: unknown }).max);
    const step = Number((obj as { step?: unknown }).step);
    return {
      min: Number.isFinite(min) ? min : fallback.min,
      max: Number.isFinite(max) ? max : fallback.max,
      step: Number.isFinite(step) && step > 0 ? step : fallback.step,
    };
  }
  return fallback;
}

const floorsMinMax = computed(() => {
  const floors = globalStore.shortcodeData?.floors ?? [];
  const floorNumbers = floors
    .map((floor) => Number(floor.floor_number))
    .filter((value) => Number.isFinite(value));

  if (!floorNumbers.length) {
    return { min: 0, max: 16 };
  }

  return {
    min: Math.min(...floorNumbers),
    max: Math.max(...floorNumbers),
  };
});

const filterOptions = computed(() =>
  normalizeFilterOptionsMeta(globalStore.getMetaValue("filter_options")),
);
const customPriceOptions = computed(() =>
  normalizeRangeOption(filterOptions.value.price_filter_options, {
    min: 0,
    max: 1000000,
    step: 1000,
  }),
);
const customAreaOptions = computed(() =>
  normalizeRangeOption(filterOptions.value.area_filter_options, {
    min: 0,
    max: 300,
    step: 10,
  }),
);
const customRoomOptions = computed(() =>
  normalizeRangeOption(filterOptions.value.rooms_filter_options, {
    min: 0,
    max: 10,
    step: 1,
  }),
);

const priceFilterEnabled = computed(
  () => filterOptions.value.price_filter_enabled !== false,
);
const areaFilterEnabled = computed(
  () => filterOptions.value.area_filter_enabled !== false,
);
const roomsFilterEnabled = computed(
  () => filterOptions.value.rooms_filter_enabled !== false,
);
const floorFilterEnabled = computed(
  () => filterOptions.value.floor_filter_enabled !== false,
);
const statusFilterEnabled = computed(
  () => filterOptions.value.status_filter_enabled !== false,
);

const customTypes = globalStore.getMetaValue("custom_types");
const customTypeOptions = Array.isArray(customTypes)
  ? customTypes.map((t: any) => ({ title: tr(t.title), value: t.value }))
  : [];

const hiddenStatuses = computed(() => {
  const h = filterOptions.value.hidden_statuses;
  return Array.isArray(h) ? (h as string[]) : [];
});

const confOptions = computed(() => {
  const all = [
    { title: tr("all"), value: "all" },
    { title: tr("available"), value: "available" },
    { title: tr("reserved"), value: "reserved" },
    { title: tr("sold"), value: "sold" },
    ...customTypeOptions,
  ];
  return all.filter(
    (o) => o.value === "all" || !hiddenStatuses.value.includes(o.value),
  );
});
const isFiltersReady = ref(false);

const defaultFilters = computed(() => ({
  priceRange: [customPriceOptions.value.min, customPriceOptions.value.max],
  areaRange: [customAreaOptions.value.min, customAreaOptions.value.max],
  floorRange: [floorsMinMax.value.min, floorsMinMax.value.max],
  roomRange: [customRoomOptions.value.min, customRoomOptions.value.max],
  config: "all",
}));

const hasChanges = computed(() => {
  if (!isFiltersReady.value) return false;
  if (!model.value) return false;
  const defaults = defaultFilters.value;
  const current = model.value;

  return (
    JSON.stringify(current.priceRange) !==
      JSON.stringify(defaults.priceRange) ||
    JSON.stringify(current.areaRange) !== JSON.stringify(defaults.areaRange) ||
    JSON.stringify(current.floorRange) !==
      JSON.stringify(defaults.floorRange) ||
    JSON.stringify(current.roomRange) !== JSON.stringify(defaults.roomRange) ||
    current.config !== defaults.config
  );
});

watch(
  floorsMinMax,
  (range) => {
    if (!model.value) return;
    const current = model.value.floorRange;
    const isDefaultFloorRange =
      Array.isArray(current) && current[0] === 0 && current[1] === 16;
    const isInvalidFloorRange =
      !Array.isArray(current) ||
      current.length !== 2 ||
      current.some((v: unknown) => !Number.isFinite(Number(v)));

    if (isDefaultFloorRange || isInvalidFloorRange) {
      model.value = {
        ...model.value,
        floorRange: [range.min, range.max],
      };
    }
    nextTick(() => {
      isFiltersReady.value = true;
    });
  },
  { immediate: true },
);

watch(priceFilterEnabled, (enabled) => {
  if (!enabled && model.value) {
    model.value = {
      ...model.value,
      priceRange: [customPriceOptions.value.min, customPriceOptions.value.max],
    };
  }
});

watch(areaFilterEnabled, (enabled) => {
  if (!enabled && model.value) {
    model.value = {
      ...model.value,
      areaRange: [customAreaOptions.value.min, customAreaOptions.value.max],
    };
  }
});

watch(roomsFilterEnabled, (enabled) => {
  if (!enabled && model.value) {
    model.value = {
      ...model.value,
      roomRange: [customRoomOptions.value.min, customRoomOptions.value.max],
    };
  }
});

watch(hiddenStatuses, (hidden) => {
  if (model.value && hidden.includes(model.value.config)) {
    model.value = { ...model.value, config: "all" };
  }
});

const resetFilters = () => {
  model.value = { ...defaultFilters.value };
};

onMounted(() => {
  setTimeout(() => {
    isReady.value = true;
  }, 500);
});
</script>
<template>
  <div
    class="irep-flats-list-filters ire-flex ire-w-full ire-flex-wrap ire-items-center ire-gap-x-8 ire-gap-y-4 ire-text-start ire-text-base"
  >
    <RangeInput
      v-if="priceFilterEnabled"
      v-model="model.priceRange"
      :min="customPriceOptions.min ?? 0"
      :max="customPriceOptions.max ?? 1000000"
      :step="customPriceOptions.step ?? 1000"
      :unit="currencySymbol()"
      label="price"
      is-price
    />

    <RangeInput
      v-if="areaFilterEnabled"
      v-model="model.areaRange"
      :min="customAreaOptions.min ?? 0"
      :max="customAreaOptions.max ?? 300"
      :step="customAreaOptions.step ?? 1"
      :unit="`${getAreaUnitLabel()}²`"
      label="area"
    />

    <RangeInput
      v-if="floorFilterEnabled && floorsMinMax.min && floorsMinMax.max"
      v-model="model.floorRange"
      :min="floorsMinMax.min"
      :max="floorsMinMax.max"
      :step="1"
      unit=""
      label="floor"
    />

    <RangeInput
      v-if="roomsFilterEnabled"
      v-model="model.roomRange"
      :min="customRoomOptions.min ?? 0"
      :max="customRoomOptions.max ?? 10"
      :step="customRoomOptions.step ?? 1"
      unit=""
      label="rooms"
    />

    <div
      v-if="statusFilterEnabled"
      class="irep-flats-list-filters__select-wrapper ire-w-full md:ire-w-[200px] md:ire-max-w-[200px] [&_select]:ire-w-full"
    >
      <PreviewSelect
        v-model="model.config"
        :data="confOptions"
        :disabled="false"
        class="irep-flats-list-filters-select"
      />
    </div>

    <Transition name="ire-fade-in-out">
      <ClearFiltersButton :visible="hasChanges" @click="resetFilters" />
    </Transition>
  </div>
</template>
