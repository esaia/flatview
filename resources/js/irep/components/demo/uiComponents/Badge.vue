<script setup lang="ts">
import { computed } from "vue";
import { getCustomTypeColor, tr } from "../../../composable/helper";
import { useGlobalStore } from "../../../store/useGlobal";

const props = defineProps<{
  conf: string;
}>();

const globalStore = useGlobalStore();

const BUILT_IN_TITLES: Record<string, string> = {
  reserved: "reserved",
  sold: "sold",
  available: "available",
};

const color = computed(() => {
  if (props.conf === "reserved")
    return (
      globalStore.getMetaValue("reserved_color") || "rgba(255, 247, 89, 0.53)"
    );
  if (props.conf === "sold")
    return globalStore.getMetaValue("sold_color") || "rgba(219, 64, 64, 0.45)";
  return getCustomTypeColor(props.conf) || "rgba(0, 0, 0, 0.2)";
});

const title = computed(() => tr(BUILT_IN_TITLES[props.conf]) || tr(props.conf));

const badgeStyleAttr = computed(() => {
  if (!color.value) return {};

  const rgbaMatch = color.value.match(
    /rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/,
  );

  if (rgbaMatch) {
    const [, r, g, b, a] = rgbaMatch;
    return {
      backgroundColor: `rgba(${r}, ${g}, ${b}, ${1})`,
      color: "#fff",
      boxShadow: "none",
    };
  }

  return {
    backgroundColor: color.value,
    color: color.value,
    boxShadow: "none",
  };
});
</script>
<template>
  <span
    v-if="title"
    class="irep-badge ire-inline-flex ire-min-w-max ire-items-center ire-rounded-full ire-px-2 ire-py-1 ire-text-xs ire-uppercase"
    :style="badgeStyleAttr"
  >
    {{ title }}
  </span>
</template>
