<script setup lang="ts">
import type { FlatItem, FloorItem } from "../../../../../types/DemoTypes";
import ShareFlat from "../../../../../components/demo/uiComponents/ShareFlat.vue";
import Price from "../../../../../components/demo/uiComponents/Price.vue";
import FlatSwiperTwo from "../../../../../components/demo/preview/FlatPreviews/FlatPreviewTwo/FlatSwiperTwo.vue";
import FlatPreviewTwoBottom from "../../../../../components/demo/preview/FlatPreviews/FlatPreviewTwo/FlatPreviewTwoBottom.vue";
import PreviewTwoKeyValue from "../../../../../components/demo/preview/FlatPreviews/FlatPreviewTwo/PreviewTwoKeyValue.vue";
import {
  tr,
  getArea,
  getBlockById,
  getFloorById,
  getRoomCount,
  transformOtherToKeyValue,
  getNested,
  getAreaUnitLabel,
} from "../../../../../composable/helper";
import { computed, ref } from "vue";
import { useGlobalStore } from "../../../../../store/useGlobal";
import { storeToRefs } from "pinia";
import ReservationForm from "../../../../../components/demo/uiComponents/ReservationForm.vue";

const props = defineProps<{
  flat: FlatItem | undefined;
  floors?: FloorItem[] | undefined;
}>();

const showForm = ref(false);

const flatTypeData = computed(() => {
  const useType = props.flat?.use_type === true || String(props.flat?.use_type) === "true";
  return useType ? (props.flat?.type ?? props.flat?.flat_type) : (props.flat?.flat_type ?? props.flat?.type);
});

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { irePlaginWp, shortcodeData } = storeToRefs(globalStore);

const showCallbackButton = computed(() => {
  return !!(
    getMetaValue("request_callback") === "true" && irePlaginWp.value?.is_gold
  );
});

const tableContactUrl = computed(() => {
  return shortcodeData.value?.configs.tableContactUrl;
});

const handleRequestCallbackClick = () => {
  if (
    showCallbackButton.value &&
    getMetaValue("redirect_to_callback_url") === "true"
  ) {
    const flat = props.flat;
    const typeData = flatTypeData.value;
    const formattedFlat = flat
      ? {
          ...flat,
          type: typeData
            ? {
                ...typeData,
                other: transformOtherToKeyValue(typeData?.other ?? []),
              }
            : typeData,
        }
      : null;

    window.open(
      `${tableContactUrl.value}${getNested(formattedFlat, shortcodeData.value?.configs?.flatFieldQueryParameter || "") || flat?.id}`,
      "_blank",
    );
  } else {
    showForm.value = true;
  }
};
</script>

<template>
  <transition name="ire-fade-in-out" mode="out-in">
    <div
      v-if="showForm"
      class="irep-flat-preview-two__form-panel ire-m-4 ire-max-h-[calc(100dvh-5rem)] ire-overflow-y-auto"
    >
      <ReservationForm :flat="flat" @hideForm="showForm = false" />
    </div>

    <div v-else class="irep-flat-preview-two ire-relative">
      <div
        class="irep-flat-preview-two__header ire-z-10 ire-flex ire-w-full ire-items-center ire-gap-2 ire-bg-white ire-px-4 ire-py-1"
      >
        <ShareFlat />
      </div>

      <div
        class="irep-flat-preview-two__body ire-max-h-[calc(100dvh-16rem)] ire-min-h-0 ire-overflow-y-auto sm:ire-max-h-[min(700px,calc(100dvh-14rem))]"
      >
        <FlatSwiperTwo :flat="flat" :floors="floors" />

        <div
          class="irep-flat-preview-two__info flex ire-m-6 ire-mb-4 ire-flex-col ire-justify-center ire-gap-4 ire-text-center sm:ire-flex-row sm:ire-justify-between lg:ire-gap-14"
        >
          <div class="irep-flat-preview-two__title-section">
            <div
              class="irep-flat-preview-two__flat-number ire-text-left ire-text-2xl ire-font-semibold"
            >
              {{ flat?.flat_number }}
            </div>

            <div
              v-if="flatTypeData?.teaser"
              class="irep-flat-preview-two__teaser ire-mt-2 ire-text-left ire-text-sm ire-text-gray-700"
            >
              {{ flatTypeData?.teaser }}
            </div>
          </div>

          <div class="irep-flat-preview-two__price-section ire-min-w-max">
            <Price
              v-if="flat"
              :flat="flat"
              class="!ire-items-end sm:!ire-items-center"
            />
          </div>
        </div>

        <div
          class="irep-flat-preview-two__attributes ire-grid ire-grid-cols-2 ire-gap-x-4 ire-gap-y-6 ire-bg-gray-50 ire-p-6 md:ire-grid-cols-3"
        >
          <PreviewTwoKeyValue
            v-if="flat?.block_id"
            :keyName="tr('block')"
            :value="getBlockById(+flat?.block_id)?.title?.toString() || ''"
          />

          <PreviewTwoKeyValue
            v-if="flat?.floor_id"
            :keyName="tr('floor')"
            :value="
              getFloorById(+flat?.floor_id)?.floor_number?.toString() || ''
            "
          />

          <!-- <PreviewTwoKeyValue
            v-if="flat?.floor_id"
            :keyName="tr('floor title')"
            :value="getFloorById(+flat?.floor_id)?.title?.toString() || ''"
          /> -->

          <PreviewTwoKeyValue
            v-if="flatTypeData?.area_m2"
            :keyName="tr('area')"
            :value="getArea(flatTypeData?.area_m2)"
          >
            <template #sufix>
              <span> {{ getAreaUnitLabel() }}² </span>
            </template>
          </PreviewTwoKeyValue>

          <PreviewTwoKeyValue
            v-if="flatTypeData?.rooms_count"
            :keyName="tr('room')"
            :value="getRoomCount(flatTypeData?.rooms_count)"
          />

          <template v-if="flatTypeData?.other?.length">
            <PreviewTwoKeyValue
              v-for="other in flatTypeData?.other.filter((i: any) => i?.value)"
              :key="other.key"
              :keyName="other.key"
              :value="other.value"
            />
          </template>
        </div>
      </div>

      <FlatPreviewTwoBottom
        :flat="flat"
        :floors="floors"
        :showCallbackButton="showCallbackButton"
        @showForm="handleRequestCallbackClick"
      />
    </div>
  </transition>
</template>
