<script setup lang="ts">
import { inject, ref, watch } from "vue";
import Tooltip_4 from "../preview/tooltips/Tooltip_4.vue";

defineProps<{
  hoverdData: any;
  type?: any;
}>();

const showFlatModal = inject<any>("showFlatModal");
const showTooltips = ref(true);

watch(
  () => showFlatModal?.value,
  () => {
    if (showFlatModal?.value) {
      showTooltips.value = false;
    } else {
      setTimeout(() => {
        showTooltips.value = true;
      }, 400);
    }
  },
);
</script>

<template>
  <div class="irep-preview-layout ire-group ire-relative">
    <div
      v-if="$slots.header"
      class="irep-preview-layout__header py-2 ire-mb-3 ire-flex ire-h-fit ire-items-center ire-justify-between ire-gap-2 ire-px-4"
    >
      <slot name="header" />
    </div>

    <slot />

    <Transition name="ire-fade-in-out">
      <Tooltip_4
        v-if="showTooltips"
        :hovered-data="hoverdData"
        :type="type || ''"
        class="ire-z-20 ire-hidden lg:ire-flex [&_.irep-tooltip]:group-active:!ire-scale-110"
      />
    </Transition>
  </div>
</template>
