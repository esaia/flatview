<script setup lang="ts">
import ShortcodeWrapper from "../components/demo/layout/ShortcodeWrapper.vue";
import Project360Viewer from "../components/demo/preview/Project360Viewer.vue";
import type { ProjectInterface } from "../types/DemoTypes";
import { computed, provide } from "vue";
import { createGlobalStore, GLOBAL_STORE_KEY } from "../store/useGlobal";

interface IrePlagin {
  nonce: string;
  ajax_url: string;
  translations: object;
  is_premium: boolean;
  is_gold: boolean;
  price_history_addon: boolean;
}

const props = defineProps<{
  data: any;
  irePlugin: IrePlagin;
}>();

const globalStore = createGlobalStore(`ire-project360-${props.data?.project?.id ?? Math.random()}`)();

globalStore.setData(props.data);
globalStore.setIrePlaginWp(props.irePlugin);

provide(GLOBAL_STORE_KEY, globalStore);
provide("fromListView", false);

const project = computed<ProjectInterface>(() => props.data.project);

const pathsFillOnHoverOnly = computed(
  () => globalStore.getMetaValue("paths_hover_fill") === "true",
);
</script>

<template>
  <ShortcodeWrapper>
    <Project360Viewer
      v-if="project?.['360images']?.length"
      :project="project"
      :paths-fill-on-hover-only="pathsFillOnHoverOnly"
    />
  </ShortcodeWrapper>
</template>
