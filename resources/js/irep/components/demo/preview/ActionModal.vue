<script setup lang="ts">
import type { ActionData } from "../../../types/DemoTypes";
import BaseEditor from "../uiComponents/BaseEditor.vue";

defineProps<{
  modalData: ActionData;
}>();
</script>

<template>
  <div class="irep-action-modal ire-p-5">
    <p class="ire-mb-3 !ire-text-3xl ire-font-bold">
      {{ modalData?.modalObject?.title }}
    </p>
    <BaseEditor :editor="modalData?.modalObject?.description"></BaseEditor>

    <img
      v-if="modalData?.modalObject?.modalImage?.[0]?.url"
      :src="modalData?.modalObject?.modalImage?.[0]?.url"
      alt=""
      class="ire-mt-5 ire-h-[400px] ire-w-full ire-object-contain"
    />
  </div>
</template>
