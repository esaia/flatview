<script setup lang="ts">
import { tr } from "../../../composable/helper";
import type { imageInterface } from "../../../types/DemoTypes";
import { ref } from "vue";

const props = defineProps<{
  imagesUrls: imageInterface[];
  activeSlideIndex: number;
  goToSlide: (index: number) => void;
}>();

const goToSlide = (index: number) => {
  props.goToSlide(index);
};
</script>
<template>
  <div
    class="irep-swiper-pagination ire-pointer-events-auto ire-flex ire-items-center ire-gap-1.5 ire-rounded-full ire-border ire-border-black/[0.06] ire-bg-white/95 ire-px-3 ire-py-1.5 ire-shadow-sm"
    role="tablist"
    :aria-label="tr('Images')"
  >
    <div
      v-for="(_, i) in imagesUrls"
      :key="`dot-${i}`"
      type="button"
      class="irep-swiper-pagination__dot ire-box-border ire-size-2 ire-min-w-0 ire-shrink-0 ire-cursor-pointer ire-rounded-full ire-border-0 ire-p-1 ire-transition-all ire-duration-200 [line-height:0] focus-visible:ire-outline focus-visible:ire-outline-2 focus-visible:ire-outline-offset-2 focus-visible:ire-outline-[var(--primary-color)]"
      :class="
        i === activeSlideIndex
          ? 'ire-scale-125 ire-bg-[var(--primary-color)]'
          : 'ire-bg-gray-300'
      "
      :aria-label="`${tr('Slide')} ${i + 1}`"
      :aria-current="i === activeSlideIndex ? 'true' : undefined"
      @click="goToSlide(i)"
    />
  </div>
</template>
