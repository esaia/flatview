<script setup lang="ts">
import ArrowRight from "../../../components/icons/ArrowRight.vue";
import { tr } from "../../../composable/helper";
</script>

<template>
  <div
    class="irep-back-button ire-group/button ire-flex ire-w-fit ire-cursor-pointer ire-items-center ire-gap-1 ire-rounded-lg ire-border ire-bg-gray-50 ire-px-4 ire-py-1 ire-shadow-sm ire-transition-all hover:ire-bg-black"
  >
    <ArrowRight
      class="ire-w-6 ire-rotate-180 group-hover/button:[&_path]:ire-fill-white"
    />
    <div
      class="irep-back-button__text !ire-text-base ire-text-black group-hover/button:!ire-text-white"
    >
      {{ tr("back") }}
    </div>
  </div>
</template>
