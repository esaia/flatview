<script setup lang="ts">
import type { FlatItem, FloorItem } from "../../../../../types/DemoTypes";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { tr } from "../../../../../composable/helper";
import { Swiper, SwiperSlide } from "swiper/vue";
import SwiperPagination from "../../../../../components/demo/uiComponents/SwiperPagination.vue";
import Cube3d from "../../../../../components/icons/Cube3d.vue";
import FlatIcon from "../../../../../components/icons/FlatIcon.vue";
import { Fancybox } from "@fancyapps/ui";
import PlayIcon from "../../../../../components/icons/PlayIcon.vue";

const props = defineProps<{
  flat: FlatItem | undefined;
  floors?: FloorItem[] | undefined;
}>();

const swiper = ref();
const activeSlideIndex = ref(0);
const show2dImage = ref(true);
const key = ref(0);

const flatTypeData = computed(() => {
  const useType = props.flat?.use_type === true || String(props.flat?.use_type) === "true";
  return useType ? (props.flat?.type ?? props.flat?.flat_type) : (props.flat?.flat_type ?? props.flat?.type);
});

const hasImg = computed(() => {
  return flatTypeData.value?.image_3d?.length || flatTypeData.value?.image_2d?.length;
});

const hasBothPhoto = computed(() => {
  if (!props.flat) return false;
  return !!(
    flatTypeData.value?.image_2d?.length && flatTypeData.value?.image_3d?.length
  );
});

const imagesUrls = computed(() => {
  if (!props.flat) return [];
  const arr =
    show2dImage.value && flatTypeData.value?.image_2d?.length
      ? flatTypeData.value.image_2d
      : !show2dImage.value && flatTypeData.value?.image_3d?.length
        ? flatTypeData.value.image_3d
        : [];

  return arr.slice(0, 6);
});

const onSwiper = (swiperInstance: any) => {
  swiper.value = swiperInstance;
  activeSlideIndex.value = swiperInstance.activeIndex ?? 0;
  swiperInstance.on?.("slideChange", () => {
    activeSlideIndex.value = swiperInstance.activeIndex ?? 0;
  });
};

const goToSlide = (index: number) => {
  swiper.value?.slideTo?.(index);
};

const isVideo = (img: any) => !!img?.mime?.startsWith("video/");
const isYoutube = (img: any) => img?.mime === "youtube";

const extractYoutubeId = (url: string): string => {
  const m = url?.match(
    /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/
  );
  return m ? m[1] : "";
};

const fancyboxOptions = {
  Hash: false,
  showClass: "f-fadeIn",
  hideClass: "f-fadeOut",
  Images: { zoom: false, Panzoom: { maxScale: 2 } },
};

onMounted(() => {
  Fancybox.bind("[data-fancybox='flat-swiper-two']", fancyboxOptions);
});

onUnmounted(() => {
  Fancybox.unbind("[data-fancybox='flat-swiper-two']");
  Fancybox.close();
});

const syncDefaultPlanView = () => {
  const flat = props.flat;
  if (!flat) return;
  if (!flatTypeData.value?.image_2d?.length) {
    show2dImage.value = false;
  } else {
    show2dImage.value = true;
  }
};

watch(
  () => show2dImage.value,
  () => {
    key.value++;
    setTimeout(() => {
      swiper.value?.update?.();
      activeSlideIndex.value = swiper.value?.activeIndex ?? 0;
    }, 400);
  },
);

watch(
  () => props.flat,
  () => {
    syncDefaultPlanView();
  },
  { immediate: true },
);
</script>

<template>
  <div
    v-if="flat && hasImg"
      class="irep-flat-swiper-two ire-relative ire-mx-6 ire-my-2 ire-min-h-[200px] ire-overflow-hidden ire-rounded-xl ire-bg-gray-50 ire-pt-[70%]"
    >
      <Transition name="ire-scale" mode="out-in">
        <Swiper
          :key="imagesUrls?.[0]?.url"
          :slides-per-view="1"
          :space-between="0"
          class="ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-max-w-full ire-overflow-hidden"
          @swiper="onSwiper"
        >
          <SwiperSlide
            v-for="(img, i) in imagesUrls"
            :key="`${img?.url ?? ''}-${i}`"
            class="ire-group ire-flex !ire-w-full ire-justify-center ire-bg-gray-50 ire-text-center"
          >
            <!-- YouTube slide -->
            <a
              v-if="isYoutube(img)"
              :key="img?.url + i"
              :href="img?.url"
              data-fancybox="flat-swiper-two"
              class="irep-flat-preview__left-3d irep-video-slide ire-relative ire-flex ire-h-full ire-w-full ire-cursor-pointer ire-items-center ire-justify-center"
            >
              <img
                :src="`https://img.youtube.com/vi/${extractYoutubeId(img?.url)}/hqdefault.jpg`"
                class="ire-pointer-events-none ire-h-full ire-w-full ire-rounded-xl ire-object-cover ire-object-center"
                alt=""
              />
              <div
                class="irep-video-play-icon ire-pointer-events-none ire-absolute ire-inset-0 ire-z-10 ire-flex ire-items-center ire-justify-center"
              >
                <div
                  class="ire-flex ire-size-12 ire-items-center ire-justify-center ire-rounded-full ire-bg-black/40 ire-text-white"
                >
                  <PlayIcon class="ire-size-6" />
                </div>
              </div>
            </a>

            <!-- Video slide -->
            <a
              v-else-if="isVideo(img)"
              :key="img?.url + i"
              :href="img?.url"
              data-fancybox="flat-swiper-two"
              data-type="video"
              class="irep-flat-preview__left-3d irep-video-slide ire-relative ire-flex ire-h-full ire-w-full ire-cursor-pointer ire-items-center ire-justify-center"
            >
              <video
                :src="img?.url"
                class="ire-pointer-events-none ire-h-full ire-w-full ire-rounded-xl ire-object-cover ire-object-center"
                muted
                loop
                autoplay
                playsinline
              />
              <div
                class="irep-video-play-icon ire-pointer-events-none ire-absolute ire-inset-0 ire-z-10 ire-flex ire-items-center ire-justify-center"
              >
                <div
                  class="ire-flex ire-size-12 ire-items-center ire-justify-center ire-rounded-full ire-bg-black/40 ire-text-white"
                >
                  <PlayIcon class="ire-size-6" />
                </div>
              </div>
            </a>

            <!-- Image slide -->
            <a
              v-else
              :key="img?.url + i"
              :href="img?.url"
              data-fancybox="flat-swiper-two"
              class="irep-flat-preview__left-3d ire-flex ire-w-fit ire-cursor-pointer ire-items-center ire-justify-center"
            >
              <img
                :src="img?.url"
                :alt="''"
                class="ire-h-full ire-w-full ire-rounded-xl ire-object-cover ire-object-center ire-transition-all ire-duration-1000 ire-ease-[cubic-bezier(0.25,1,0.5,1)]"
              />
            </a>
          </SwiperSlide>
        </Swiper>
      </Transition>

      <div
        v-if="imagesUrls.length > 1"
        class="irep-flat-swiper-two__pagination ire-pointer-events-none ire-absolute ire-left-1/2 ire-z-20 -ire-translate-x-1/2"
        :class="{
          'ire-bottom-14': hasBothPhoto,
          'ire-bottom-4': !hasBothPhoto,
        }"
      >
        <SwiperPagination
          v-if="imagesUrls?.length > 1"
          :imagesUrls="imagesUrls"
          :activeSlideIndex="activeSlideIndex"
          :goToSlide="goToSlide"
        />
      </div>

      <div
        v-if="hasBothPhoto"
        class="irep-flat-swiper-two__view-toggle-wrapper ire-pointer-events-none ire-absolute ire-bottom-3 ire-left-1/2 ire-z-20 ire-w-[min(100%,16rem)] -ire-translate-x-1/2 ire-px-2"
      >
        <div
          class="irep-flat-swiper-two__view-toggle ire-pointer-events-auto ire-inline-flex ire-min-h-[2.25rem] ire-w-full ire-items-stretch ire-gap-1 ire-rounded-full ire-bg-gray-200/[0.8] ire-p-1 ire-backdrop-blur-sm"
          role="tablist"
          :aria-label="tr('plan view')"
        >
          <div
            v-if="flatTypeData?.image_2d?.[0]?.url"
            role="tab"
            :aria-selected="show2dImage"
            class="irep-flat-swiper-two__view-tab ire-group ire-flex ire-min-w-0 ire-flex-1 ire-cursor-pointer ire-items-center ire-justify-center ire-gap-1.5 ire-rounded-full ire-fill-transparent ire-text-xs ire-transition-all ire-duration-300 hover:ire-bg-gray-100 hover:ire-text-black focus-visible:ire-outline focus-visible:ire-outline-2 focus-visible:ire-outline-offset-2 focus-visible:ire-outline-[var(--primary-color)] sm:ire-text-sm"
            :class="
              show2dImage
                ? 'ire-bg-white ire-text-gray-900 ire-shadow-sm'
                : 'ire-bg-transparent ire-text-black'
            "
            @click="show2dImage = true"
          >
            <FlatIcon
              class="ire-size-3.5 ire-shrink-0 sm:ire-size-4 [&_path]:ire-stroke-current"
              :class="show2dImage ? 'ire-text-gray-900' : 'ire-text-black'"
            />
            <span class="ire-whitespace-nowrap">{{ tr("2d plan") }}</span>
          </div>

          <div
            v-if="flatTypeData?.image_3d?.[0]?.url"
            role="tab"
            :aria-selected="!show2dImage"
            class="irep-flat-swiper-two__view-tab ire-group ire-flex ire-min-w-0 ire-flex-1 ire-cursor-pointer ire-items-center ire-justify-center ire-gap-1.5 ire-rounded-full ire-text-xs ire-transition-all ire-duration-300 hover:ire-bg-gray-100 hover:ire-text-black focus-visible:ire-outline focus-visible:ire-outline-2 focus-visible:ire-outline-offset-2 focus-visible:ire-outline-[var(--primary-color)] sm:ire-text-sm"
            :class="
              !show2dImage
                ? 'ire-bg-white ire-text-gray-900 ire-shadow-sm'
                : 'ire-bg-transparent ire-text-black'
            "
            @click="show2dImage = false"
          >
            <Cube3d
              class="ire-size-3.5 ire-shrink-0 ire-fill-transparent sm:ire-size-4 [&_path]:ire-stroke-current"
              :class="!show2dImage ? 'ire-text-gray-900' : 'ire-text-black'"
            />
            <span class="ire-whitespace-nowrap">{{ tr("3d plan") }}</span>
          </div>
        </div>
      </div>
    </div>
</template>

<style scoped>
.irep-video-play-icon {
  opacity: 0;
  transition: opacity 0.2s ease;
}
.irep-video-slide:hover .irep-video-play-icon {
  opacity: 1;
}
</style>

