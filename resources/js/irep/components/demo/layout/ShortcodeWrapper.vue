<script setup lang="ts">
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { onMounted, onUnmounted, ref, watch } from "vue";

const globalStore = useGlobalStore();
const { cssVariables, customTypesPathCss } = storeToRefs(globalStore);

const wrapperRef = ref<HTMLDivElement | null>(null);
const styleEl = document.createElement("style");

watch(
  customTypesPathCss,
  (css) => {
    styleEl.textContent = css ?? "";
  },
  { immediate: true },
);

onMounted(() => {
  if (wrapperRef.value) wrapperRef.value.appendChild(styleEl);
});

onUnmounted(() => {
  styleEl.remove();
});
</script>
<template>
  <div ref="wrapperRef" class="irep-shortcode-wrapper" :style="cssVariables">
    <slot />
  </div>
</template>
