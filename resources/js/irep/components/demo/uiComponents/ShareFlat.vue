<script setup lang="ts">
import { computed, inject, ref } from "vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { copyToClipboard } from "../../../composable/helper";
import IconButton from "./IconButton.vue";
import Correct from "../../../components/icons/Correct.vue";
import Share from "../../../components/icons/Share.vue";

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { irePlaginWp } = storeToRefs(globalStore);

const copied = ref(false);

const fromListView = inject("fromListView");

const isGoldAndSharable = computed(() => {
  return (
    getMetaValue("shareable_link") === "true" &&
    irePlaginWp.value?.is_gold &&
    !fromListView
  );
});

const copyUrl = async () => {
  if (copied.value || !isGoldAndSharable.value) return;

  // COPY URL
  await copyToClipboard(window.location.href);

  copied.value = true;

  setTimeout(() => {
    copied.value = false;
  }, 2000);
};
</script>

<template>
  <IconButton v-if="isGoldAndSharable" @click="copyUrl">
    <Transition name="ire-scale" mode="out-in">
      <Correct v-if="copied" />
      <Share v-else />
    </Transition>
  </IconButton>
</template>
