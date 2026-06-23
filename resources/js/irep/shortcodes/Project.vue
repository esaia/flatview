<script setup lang="ts">
import ShortcodeWrapper from "../components/demo/layout/ShortcodeWrapper.vue";
import Preview from "../components/demo/preview/Preview.vue";
import { createGlobalStore, GLOBAL_STORE_KEY } from "../store/useGlobal";
import { provide } from "vue";

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

const globalStore = createGlobalStore(`ire-project-${props.data?.project?.id ?? Math.random()}`)();
globalStore.setData(props.data);
globalStore.setIrePlaginWp(props.irePlugin);

provide(GLOBAL_STORE_KEY, globalStore);
provide("fromListView", false);
</script>

<template>
  <ShortcodeWrapper>
    <Preview />
  </ShortcodeWrapper>
</template>
