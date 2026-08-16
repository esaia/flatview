<script setup lang="ts">
/**
 * Provides one IREP global store to a whole page section, so the interactive
 * viewer and the flats list below it share the same project data and colours.
 *
 * Unlike the single-purpose shortcodes (Project / Project360), this renders
 * whatever the page puts in its slot — the marketing copy between the viewer
 * and the unit list lives in the page, not in here.
 *
 * Viewer-only stylesheets are imported here so they stay in this async chunk
 * instead of the global app.css. Fancybox base CSS must load BEFORE irep.css so
 * irep.css's overrides win (notably the lightbox z-index above the flat modal).
 */
import "@fancyapps/ui/dist/fancybox/fancybox.css";
import "../../../css/irep.css";
import { provide } from "vue";
import ShortcodeWrapper from "../components/demo/layout/ShortcodeWrapper.vue";
import { createGlobalStore, GLOBAL_STORE_KEY } from "../store/useGlobal";

const props = defineProps<{
  data: any;
}>();

const irePlugin = {
  nonce: "",
  ajax_url: "/irep/reservation",
  translations: {},
  is_premium: true,
  is_gold: true,
  price_history_addon: false,
};

const globalStore = createGlobalStore(
  `ire-showcase-${props.data?.project?.id ?? Math.random()}`,
)();

globalStore.setData(props.data);
globalStore.setIrePlaginWp(irePlugin);

provide(GLOBAL_STORE_KEY, globalStore);
provide("fromListView", false);
</script>

<template>
  <ShortcodeWrapper>
    <slot />
  </ShortcodeWrapper>
</template>
