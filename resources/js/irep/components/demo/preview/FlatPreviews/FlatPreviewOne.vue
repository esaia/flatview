<script setup lang="ts">
import { computed, ref } from "vue";
import type { FlatItem, FloorItem } from "../../../../types/DemoTypes";
import {
  getNested,
  transformOtherToKeyValue,
} from "../../../../composable/helper";
import FlatModalImage from "../../../../components/demo/uiComponents/FlatModalImage.vue";
import ReservationForm from "../../../../components/demo/uiComponents/ReservationForm.vue";
import { useGlobalStore } from "../../../../store/useGlobal";
import { storeToRefs } from "pinia";
import ShareFlat from "../../uiComponents/ShareFlat.vue";
import FlatPreviewOneRightV1 from "./FlatPreviewOneRightV1.vue";
import FlatPreviewOneRightV2 from "./FlatPreviewOneRightV2.vue";

const props = defineProps<{
  flat: FlatItem | undefined;
  floors?: FloorItem[] | undefined;
}>();

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { irePlaginWp, shortcodeData } = storeToRefs(globalStore);

const showForm = ref(false);

const flatPreviewOneStyle = computed(() => getMetaValue("flat_preview_one_style") || "1");

const flatTypeData = computed(() => {
  const useType = props.flat?.use_type === true || String(props.flat?.use_type) === "true";
  return useType ? (props.flat?.type ?? props.flat?.flat_type) : (props.flat?.flat_type ?? props.flat?.type);
});

const hasImg = computed(() => {
  return flatTypeData.value?.image_3d?.length || flatTypeData.value?.image_2d?.length;
});

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
  <div
    class="irep-flat-preview ire-relative ire-grid"
    :class="{
      'lg:ire-grid-cols-[1.4fr,1fr] xl:ire-min-h-[500px] xl:ire-min-w-[1120px]':
        hasImg,
    }"
  >
    <div class="irep-flat-preview__share ire-absolute ire-left-0 ire-top-0 ire-z-20 ire-w-fit ire-p-4">
      <ShareFlat />
    </div>

    <FlatModalImage
      v-if="hasImg && flat"
      :flat="flat"
      class="ire-relative ire-bg-gray-50"
    />

    <div class="irep-flat-preview__info-col flex ire-relative ire-overflow-hidden lg:ire-w-full">
      <FlatPreviewOneRightV2
        v-if="flatPreviewOneStyle === '2'"
        :flat="flat"
        :show-form="showForm"
        :show-callback-button="showCallbackButton"
        @request-callback="handleRequestCallbackClick"
      />

      <FlatPreviewOneRightV1
        v-else
        :flat="flat"
        :show-form="showForm"
        :show-callback-button="showCallbackButton"
        @request-callback="handleRequestCallbackClick"
      />

      <div
        v-if="showCallbackButton"
        class="irep-flat-preview__form-panel ease-in-out-quint ire-mt-4 ire-h-full ire-w-full ire-flex-[1_0_auto] ire-origin-right ire-px-6 ire-py-4 ire-transition-transform ire-duration-300"
        :class="{
          '!ire-h-0 ire-translate-x-full ire-scale-0': !showForm,
          '-ire-translate-x-full': showForm,
        }"
      >
        <ReservationForm :flat="flat" @hideForm="showForm = false" />
      </div>
    </div>
  </div>
</template>
