<script setup lang="ts">
import { useGlobalStore } from "../../../../store/useGlobal";
import { computed } from "vue";
import PreviewSelect from "../../form/PreviewSelect.vue";
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
  <div
    class="irep-flats-sidebar-header ire-grid ire-gap-2 ire-bg-gray-50 ire-p-4 ire-pt-0 md:ire-grid-cols-2"
  >
    <RangeInput
      v-model="model.areaRange"
      :min="customAreaOptions?.min || 0"
      :max="customAreaOptions?.max || 300"
      :step="customAreaOptions?.step || 1"
      :unit="`${getAreaUnitLabel()}²`"
      label="area"
      class="md:ire-min-w-[unset]"
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
      class="md:ire-min-w-[unset]"
    />

    <RangeInput
      v-model="model.roomRange"
      :min="customRoomOptions?.min || 0"
      :max="customRoomOptions?.max || 10"
      :step="customRoomOptions?.step || 1"
      unit=""
      label="rooms"
      class="md:ire-min-w-[unset]"
    />

    <div class="irep-flats-sidebar-header__select-wrapper ire-flex ire-w-full ire-items-end">
      <PreviewSelect
        v-model="model.config"
        :data="confOptions"
        :disabled="false"
        class="irep-flats-list-filters-select"
      />
    </div>
  </div>
</template>
