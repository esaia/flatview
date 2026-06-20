<script setup lang="ts">
import { tr } from "../../../composable/helper";
import type { FlatItem } from "../../../types/DemoTypes";
import { computed, onMounted, ref, watch } from "vue";
import FlatIcon from "../../../components/icons/FlatIcon.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import Cube3d from "../../../components/icons/Cube3d.vue";
import { Fancybox } from "@fancyapps/ui";
import { Swiper, SwiperSlide } from "swiper/vue";
import Logo from "../../../components/icons/Logo.vue";
import SwiperPagination from "./SwiperPagination.vue";
import PlayIcon from "../../../components/icons/PlayIcon.vue";

const props = defineProps<{
  flat: FlatItem;
}>();

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { irePlaginWp } = storeToRefs(globalStore);

const swiper = ref();
const activeSlideIndex = ref(0);
const show2dImage = ref(true);
const selectedFloor = ref(0);
const key = ref(0);

const flatTypeData = computed(() => {
  const useType = props.flat?.use_type === true || String(props.flat?.use_type) === "true";
  return useType ? (props.flat?.type ?? props.flat?.flat_type) : (props.flat?.flat_type ?? props.flat?.type);
});

const hasBothPhoto = computed(() => {
  return !!(
    flatTypeData.value?.image_2d?.length && flatTypeData.value?.image_3d?.length
  );
});

const isVideo = (img: any) => !!img?.mime?.startsWith("video/");
const isYoutube = (img: any) => img?.mime === "youtube";

const extractYoutubeId = (url: string): string => {
  const m = url?.match(
    /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/
  );
  return m ? m[1] : "";
};

const videoHtml = (img: any) =>
  `<video controls style="max-height:80vh;max-width:100%;display:block;margin:auto"><source src="${img?.url}" type="${img?.mime}"></video>`;


const imagesUrls = computed(() => {
  const arr =
    show2dImage.value && flatTypeData.value?.image_2d?.length
      ? flatTypeData.value?.image_2d
      : !show2dImage.value && flatTypeData.value?.image_3d?.length
        ? flatTypeData.value?.image_3d
        : [];

  return arr.slice(0, 6);
});

const gallerySlides = computed(() =>
  imagesUrls.value.map((img: any) => {
    if (isYoutube(img)) return { src: img?.url };
    if (isVideo(img)) return { type: "html", html: videoHtml(img) };
    return { src: img?.url, type: "image" };
  }),
);

const openGallery = (index: number) => {
  Fancybox.show(gallerySlides.value, {
    startIndex: index,
    Hash: false,
    showClass: "f-fadeIn",
    hideClass: "f-fadeOut",
    Images: { zoom: false, Panzoom: { maxScale: 2 } },
  });
};

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

watch(
  () => show2dImage.value,
  () => {
    selectedFloor.value = 0;
    key.value++;

    setTimeout(() => {
      swiper.value?.update?.();
      activeSlideIndex.value = swiper.value?.activeIndex ?? 0;
    }, 400);
  },
);

onMounted(() => {
  if (!flatTypeData.value?.image_2d?.length) {
    show2dImage.value = false;
  } else {
    show2dImage.value = true;
  }
});
</script>

<template>
  <div
    class="irep-flat-modal-image ire-flex ire-h-full ire-w-full ire-flex-col ire-justify-between ire-overflow-hidden"
  >
      <div
        class="irep-flat-modal-image__header ire-absolute ire-h-8 lg:ire-static"
      >
        <!-- <ShareFlat /> -->
      </div>

      <div
        class="irep-flat-modal-image__slides ire-relative ire-grid ire-overflow-visible ire-px-4"
      >
        <!-- <Transition name="ire-fade-in-out" mode="out-in"> -->
        <Swiper
          :key="imagesUrls[0]?.url"
          :slides-per-view="1"
          :space-between="50"
          class="ire-w-full !ire-overflow-visible ire-rounded-md"
          @swiper="onSwiper"
        >
          <swiper-slide
            v-for="(img, i) in imagesUrls"
            :key="img?.url + i"
            class="ire-flex !ire-w-full ire-justify-center ire-bg-gray-50 ire-text-center"
          >
            <!-- YouTube slide -->
            <a
              v-if="isYoutube(img)"
              :key="img?.url + i"
              href="#"
              class="irep-flat-preview__left-3d irep-video-slide ire-relative ire-block ire-w-full ire-cursor-pointer"
              @click.prevent="openGallery(i)"
            >
              <div
                class="irep-flat-modal-image__image-spacer ire-w-full ire-pt-[75%]"
                aria-hidden="true"
              />
              <img
                :src="`https://img.youtube.com/vi/${extractYoutubeId(img?.url)}/hqdefault.jpg`"
                class="ire-pointer-events-none ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-object-cover ire-object-center"
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
              href="#"
              class="irep-flat-preview__left-3d irep-video-slide ire-relative ire-block ire-w-full ire-cursor-pointer"
              @click.prevent="openGallery(i)"
            >
              <div
                class="irep-flat-modal-image__image-spacer ire-w-full ire-pt-[75%]"
                aria-hidden="true"
              />
              <video
                :src="img?.url"
                class="ire-pointer-events-none ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-object-contain ire-object-center"
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
              href="#"
              class="irep-flat-preview__left-3d ire-relative ire-block ire-w-full ire-cursor-pointer"
              @click.prevent="openGallery(i)"
            >
              <!-- 4:3 box (padding-top = height/width × 100%); same height for every slide when toggling 2D/3D -->
              <div
                class="irep-flat-modal-image__image-spacer ire-w-full ire-pt-[75%]"
                aria-hidden="true"
              />
              <img
                :key="img?.url + i"
                :src="img?.url"
                :alt="img?.alt || ''"
                :width="
                  img?.width && Number(img.width) > 0
                    ? Math.round(Number(img.width))
                    : undefined
                "
                :height="
                  img?.height && Number(img.height) > 0
                    ? Math.round(Number(img.height))
                    : undefined
                "
                class="ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-object-contain ire-object-center"
              />
            </a>
          </swiper-slide>
        </Swiper>
        <!-- </Transition> -->

        <div
          v-if="imagesUrls.length > 1"
          class="irep-flat-modal-image__pagination ire-pointer-events-none ire-absolute ire-bottom-3 ire-left-1/2 ire-z-20 -ire-translate-x-1/2"
        >
          <SwiperPagination
            v-if="imagesUrls?.length > 1"
            :imagesUrls="imagesUrls"
            :activeSlideIndex="activeSlideIndex"
            :goToSlide="goToSlide"
          />
        </div>
      </div>

      <div
        class="irep-flat-modal-image__actions flex ire-flex-col ire-flex-wrap ire-items-center ire-justify-center ire-gap-4 ire-p-4 sm:ire-flex-row lg:ire-gap-3"
      >
        <div
          v-if="hasBothPhoto"
          class="irep-flat-modal-image__view-toggle-wrapper ire-w-auto"
        >
          <div
            class="irep-flat-modal-image__view-toggle ire-inline-flex ire-min-h-[2.75rem] ire-w-full ire-max-w-md ire-items-stretch ire-gap-1 ire-rounded-full ire-bg-[#e5e7eb33] ire-p-1 sm:ire-w-auto sm:ire-min-w-[min(100%,20rem)]"
            role="tablist"
            :aria-label="tr('plan view')"
          >
            <div
              v-if="flatTypeData?.image_2d?.[0]?.url"
              role="tab"
              :aria-selected="show2dImage"
              class="irep-flat-modal-image__view-tab ire-group ire-flex ire-min-w-0 ire-flex-1 ire-cursor-pointer ire-items-center ire-justify-center ire-gap-2 ire-rounded-full ire-fill-transparent ire-px-2 ire-py-1 ire-text-sm ire-transition-all ire-duration-500 hover:ire-text-black"
              :class="
                show2dImage
                  ? 'ire-bg-white ire-text-gray-900 ire-shadow-sm'
                  : 'ire-bg-transparent hover:ire-bg-white'
              "
              @click="show2dImage = true"
            >
              <FlatIcon
                class="ire-size-4 ire-shrink-0 ire-transition-all ire-duration-500 [&_path]:ire-stroke-current group-hover:[&_path]:ire-stroke-black"
              />
              <span class="ire-whitespace-nowrap">{{ tr("2d plan") }}</span>
            </div>

            <div
              v-if="flatTypeData?.image_3d?.[0]?.url"
              role="tab"
              :aria-selected="!show2dImage"
              class="irep-flat-modal-image__view-tab ire-group ire-flex ire-min-w-0 ire-flex-1 ire-cursor-pointer ire-items-center ire-justify-center ire-gap-2 ire-rounded-full ire-px-2 ire-py-1 ire-text-sm ire-transition-all ire-duration-500 hover:ire-text-black"
              :class="
                !show2dImage
                  ? 'ire-bg-white ire-text-gray-900 ire-shadow-sm'
                  : 'ire-bg-transparent hover:ire-bg-white'
              "
              @click="show2dImage = false"
            >
              <Cube3d
                class="ire-size-4 ire-shrink-0 ire-fill-transparent ire-transition-all ire-duration-500 [&_path]:ire-stroke-current group-hover:[&_path]:ire-stroke-black"
              />
              <span class="ire-whitespace-nowrap">{{ tr("3d plan") }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="
        getMetaValue('remove_watermark') !== 'true' || !irePlaginWp?.is_premium
      "
      class="irep-flat-modal-image__watermark ire-absolute ire-right-12 ire-top-4 ire-z-20 ire-aspect-square ire-cursor-pointer md:ire-bottom-4 md:ire-left-4 md:ire-right-[unset] md:ire-top-[unset]"
    >
      <a href="https://www.ireplugin.com/" target="_blank">
        <Logo class="ire-size-12" />
      </a>
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
