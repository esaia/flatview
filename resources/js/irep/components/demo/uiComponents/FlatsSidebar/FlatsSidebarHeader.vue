<script setup lang="ts">
import { useGlobalStore } from "../../../../store/useGlobal";
import { computed } from "vue";
import StatusSelect from "../../form/StatusSelect.vue";
import RangeInput from "../../form/Range.vue";

import {
  getAreaUnitLabel,
  normalizeFilterOptionsMeta,
  normalizeRangeOption,
  tr,
} from "../../../../composable/helper";

const globalStore = useGlobalStore();

const model = defineModel<{
  areaRange: [number, number];
  floorRange: [number, number];
  roomRange: [number, number];
  config: string;
}>("filtersObject", { required: true });

const props = defineProps<{
  hideFloorRange?: boolean;
}>();

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

const filterOptions = normalizeFilterOptionsMeta(
  globalStore.getMetaValue("filter_options"),
);
const customAreaOptions = normalizeRangeOption(
  filterOptions.area_filter_options,
  {
    min: 0,
    max: 300,
    step: 10,
  },
);
const customRoomOptions = normalizeRangeOption(
  filterOptions.rooms_filter_options,
  {
    min: 0,
    max: 10,
    step: 1,
  },
);

const customTypes = globalStore.getMetaValue("custom_types");
const customTypeOptions = Array.isArray(customTypes)
  ? customTypes.map((t: { title: string; value: string }) => ({
      title: tr(t.title),
      value: t.value,
    }))
  : [];

const hiddenStatuses = computed(() => {
  const raw = normalizeFilterOptionsMeta(globalStore.getMetaValue("filter_options"));
  const h = raw.hidden_statuses;
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
</script>
<template>
  <div class="irep-flats-sidebar-header ire-px-4 ire-pb-4 ire-pt-4">
    <div
      class="irep-flats-sidebar-header__panel ire-flex ire-flex-col ire-gap-4 ire-rounded-2xl ire-border ire-border-gray-200/70 ire-bg-white ire-p-4 ire-shadow-[0_1px_2px_rgba(16,24,40,0.04)]"
    >
      <RangeInput
        v-model="model.areaRange"
        :min="customAreaOptions?.min || 0"
        :max="customAreaOptions?.max || 300"
        :step="customAreaOptions?.step || 1"
        :unit="`${getAreaUnitLabel()}²`"
        label="area"
      />

      <RangeInput
        v-if="
          !props.hideFloorRange &&
          Number.isFinite(floorsMinMax.min) &&
          Number.isFinite(floorsMinMax.max)
        "
        v-model="model.floorRange"
        :min="floorsMinMax.min"
        :max="floorsMinMax.max"
        :step="1"
        unit=""
        label="floor"
      />

      <RangeInput
        v-model="model.roomRange"
        :min="customRoomOptions?.min || 0"
        :max="customRoomOptions?.max || 10"
        :step="customRoomOptions?.step || 1"
        unit=""
        label="rooms"
      />

      <div class="irep-flats-sidebar-header__status ire-w-full">
        <span
          class="irep-flats-sidebar-header__status-label ire-mb-2 ire-block ire-text-[11px] ire-font-semibold ire-uppercase ire-tracking-[0.14em] ire-text-gray-400"
        >
          {{ tr("status") }}
        </span>

        <StatusSelect
          v-model="model.config"
          :data="confOptions"
          :disabled="false"
          class="irep-flats-list-filters-select"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Tracked micro-label to match the slider labels (arbitrary tracking utilities
   are absent from the frozen irep stylesheet, so set it here). */
.irep-flats-sidebar-header__status-label {
  letter-spacing: 0.14em;
}
</style>
