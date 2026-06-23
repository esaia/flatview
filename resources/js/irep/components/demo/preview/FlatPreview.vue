<script setup lang="ts">
import { computed, inject, onMounted, onUnmounted } from "vue";
import type { FlatItem, FloorItem } from "../../../types/DemoTypes";
import { setQuery } from "../../../composable/helper";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import FlatPreviewTwo from "./FlatPreviews/FlatPreviewTwo/FlatPreviewTwo.vue";
import FlatPreviewOne from "./FlatPreviews/FlatPreviewOne.vue";

defineEmits<{
  (
    e: "changeComponent",
    flow: "" | "flat" | "floor" | "block" | "project",
    hoveredData: any,
  ): void;
}>();

const props = defineProps<{
  flat: FlatItem | undefined;
  floors?: FloorItem[] | undefined;
}>();

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { irePlaginWp, shortcodeData } = storeToRefs(globalStore);

const fromListView = inject("fromListView");

const isGoldAndSharable = computed(() => {
  return (
    getMetaValue("shareable_link") === "true" && irePlaginWp.value?.is_gold
  );
});

const flatPreview = computed(() => {
  return getMetaValue("flat_preview");
});

const setFlatQuery = () => {
  if (!isGoldAndSharable.value || fromListView) return;

  const floor = shortcodeData.value?.floors?.find(
    (i) => i.id?.toString() === props.flat?.floor_id?.toString(),
  );

  const query = {
    flatId: props.flat?.id,
    floorId: floor?.id,
    projectId: shortcodeData.value?.project?.id,
  };

  Object.entries(query).forEach(([key, value]) => {
    if (key && value) {
      setQuery(key, value);
    }
  });
};

onMounted(() => {
  setFlatQuery();
});

onUnmounted(() => {
  const keys = ["flatId", "floorId", "projectId"];

  keys.forEach((key) => {
    setQuery(key, "");
  });
});
</script>
<template>
  <div class="irep-flat-preview-wrapper">
    <FlatPreviewTwo v-if="flatPreview === '2'" :flat="flat" :floors="floors" />

    <FlatPreviewOne v-else :flat="flat" :floors="floors" />
  </div>
</template>
