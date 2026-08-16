<script setup lang="ts">
/**
 * The interactive part of a project, for use inside <IrepProvider>.
 *
 * Projects captured as a 360 orbit render the 360 viewer; everything else falls
 * back to the standard polygon flow (project → block/floor → flat).
 */
import { computed } from "vue";
import { storeToRefs } from "pinia";
import Preview from "../components/demo/preview/Preview.vue";
import Project360Viewer from "../components/demo/preview/Project360Viewer.vue";
import { useGlobalStore } from "../store/useGlobal";

const globalStore = useGlobalStore();
const { shortcodeData } = storeToRefs(globalStore);

const project = computed(() => shortcodeData.value?.project);
const has360 = computed(() => Boolean((project.value as any)?.["360images"]?.length));

const pathsFillOnHoverOnly = computed(
  () => globalStore.getMetaValue("paths_hover_fill") === "true",
);
</script>

<template>
  <Project360Viewer
    v-if="has360 && project"
    :project="project"
    :paths-fill-on-hover-only="pathsFillOnHoverOnly"
  />

  <Preview v-else />
</template>
