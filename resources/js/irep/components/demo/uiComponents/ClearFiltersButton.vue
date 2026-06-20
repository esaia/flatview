<script setup lang="ts">
import { tr } from "../../../composable/helper";

defineProps<{
  visible: boolean;
}>();

defineEmits<{
  (e: "click"): void;
}>();
</script>
<template>
  <div
    v-if="visible"
    class="irep-clear-filters-button ire-cursor-pointer ire-text-sm ire-font-medium ire-capitalize ire-text-[var(--primary-color)] ire-underline-offset-4 ire-transition-all hover:ire-text-black hover:ire-underline"
    @click="$emit('click')"
  >
    {{ tr("clear filters") }}
  </div>
</template>
