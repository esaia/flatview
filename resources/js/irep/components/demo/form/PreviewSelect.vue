<script setup lang="ts">
import { useGlobalStore } from "../../../store/useGlobal";
import type { selectDataItem } from "../../../types/DemoTypes";
import { storeToRefs } from "pinia";
import { computed } from "vue";

withDefaults(
  defineProps<{
    data: selectDataItem[];
    placeholder?: string;
    label?: string;
    clearable?: boolean;
    required?: boolean;
    disabled?: boolean;
  }>(),
  {
    placeholder: "Choose",
    placeholderPrefix: "",
    label: "",
    clearable: false,
  },
);

const globalStore = useGlobalStore();
const { openReservedFlat, openSoldFlat } = storeToRefs(globalStore);

const selectModel = defineModel();

// Generate a unique ID for the select element to pair with the label
const selectId = `irep-select-${Math.random().toString(36).substring(2, 9)}`;
</script>

<template>
  <div class="irep-select ire-w-full md:ire-max-w-[200px]">
    <label v-if="label" :for="selectId" class="label ire-mb-1 ire-block">
      {{ label }}
    </label>

    <!-- The native select paints its own square background under the rounded
         corners, so the wrapper clips it; the ring is drawn inset to survive. -->
    <div class="irep-select__select-wrapper ire-relative overflow-hidden rounded-lg">
      <select
        :id="selectId"
        v-model="selectModel"
        :aria-label="label || placeholder"
        class="no-spinner ring-inset ire-w-full ire-cursor-pointer ire-appearance-none ire-rounded-lg !ire-border-none ire-bg-white ire-py-2.5 ire-pl-3.5 ire-pr-10 ire-text-sm ire-font-medium ire-text-gray-700 !ire-outline-none !ire-ring-[1px] !ire-ring-gray-200 ire-transition-all hover:!ire-ring-gray-300 focus:!ire-ring-2 focus:!ire-ring-black"
      >
        <option
          v-for="item in data"
          :key="item.value"
          :value="item.value"
          :disabled="
            (item?.title?.includes('reserved') && !openReservedFlat) ||
            (item?.title?.includes('sold') && !openSoldFlat) ||
            disabled
          "
          class="ire-text-base"
        >
          {{ item.title }}
        </option>
      </select>
    </div>
  </div>
</template>
