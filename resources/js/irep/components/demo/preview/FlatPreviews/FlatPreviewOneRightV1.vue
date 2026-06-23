<script setup lang="ts">
import type { FlatItem } from "../../../../types/DemoTypes";
import {
  getArea,
  getAreaUnitLabel,
  getBlockById,
  getFloorById,
  tr,
  getRoomCount,
} from "../../../../composable/helper";
import FlatPreviewKeyValue from "../../../../components/demo/uiComponents/FlatPreviewKeyValue.vue";
import Button from "../../../../components/demo/uiComponents/Button.vue";
import DownloadIcon from "../../../../components/icons/DownloadIcon.vue";
import PhoneIcon from "../../../../components/icons/PhoneIcon.vue";
import Price from "../../uiComponents/Price.vue";
import { computed } from "vue";

const props = defineProps<{
  flat: FlatItem | undefined;
  showForm: boolean;
  showCallbackButton: boolean;
}>();

const emit = defineEmits<{
  (e: "requestCallback"): void;
}>();

const flatTypeData = computed(() => {
  const useType = props.flat?.use_type === true || String(props.flat?.use_type) === "true";
  return useType ? (props.flat?.type ?? props.flat?.flat_type) : (props.flat?.flat_type ?? props.flat?.type);
});
</script>

<template>
  <div
    class="irep-flat-preview__right ease-in-out-quint ire-flex ire-h-full ire-w-full ire-flex-[1_0_auto] ire-origin-left ire-flex-col ire-justify-between ire-gap-4 ire-px-6 ire-py-4 ire-transition-transform ire-duration-300"
    :class="{
      '!ire-h-0 -ire-translate-x-full ire-scale-0 md:!ire-h-auto': showForm,
    }"
  >
    <div
      class="irep-flat-preview__details ire-flex ire-w-full ire-flex-col ire-items-center ire-justify-center ire-gap-4"
    >
      <FlatPreviewKeyValue
        v-if="flat?.flat_number"
        :keyName="tr('apartment')"
        :value="flat.flat_number"
        keyClass="flat_type"
        valueClass="flat_type_value"
        class="[&_.flat-preview-value]:ire-min-w-fit [&_.flat-preview-value]:ire-font-semibold"
      />

      <div
        v-if="flatTypeData?.title || flatTypeData?.teaser"
        class="irep-flat-preview__type ire-text-center"
      >
        <div class="irep-flat-preview__type-title ire-text-lg ire-font-medium ire-text-black">
          {{ flatTypeData?.title }}
        </div>
        <div
          v-if="flatTypeData?.teaser"
          class="irep-flat-preview__type-teaser ire-pt-2 ire-text-sm ire-uppercase ire-text-gray-500"
        >
          {{ flatTypeData?.teaser }}
        </div>
      </div>

      <div
        class="irep-flat-preview__attributes ire-mt-4 ire-flex ire-w-full ire-flex-wrap ire-items-center ire-justify-center ire-gap-9 ire-overflow-y-auto lg:ire-max-h-[350px]"
      >
        <FlatPreviewKeyValue
          v-if="flat?.block_id"
          :keyName="tr('block')"
          :value="getBlockById(+flat?.block_id)?.title?.toString() || ''"
          keyClass="flat_block_key flat_date_key"
          valueClass="flat_block_value flat_date_value"
          class="irep-flat-preview__right-floor-block"
        />

        <FlatPreviewKeyValue
          v-if="flat?.floor_id"
          :keyName="tr('floor')"
          :value="getFloorById(+flat?.floor_id)?.floor_number?.toString() || ''"
          keyClass="flat_floor_name_key flat_date_key"
          valueClass="flat_floor_name_value flat_date_value"
          class="irep-flat-preview__right-floor-number"
        />

        <FlatPreviewKeyValue
          v-if="flat?.floor_id"
          :keyName="tr('floor title')"
          :value="getFloorById(+flat?.floor_id)?.title?.toString() || ''"
          keyClass="flat_floor_key flat_date_key"
          valueClass="flat_floor_value flat_date_value"
          class="irep-flat-preview__right-floor-title"
        />

        <FlatPreviewKeyValue
          v-if="flatTypeData?.area_m2"
          :keyName="tr('area')"
          :value="getArea(flatTypeData?.area_m2)"
          keyClass="flat_area_key flat_date_key"
          valueClass="flat_area_value flat_date_value"
          class="irep-flat-preview__right-floor-area"
        >
          <template #sufix>
            <span> {{ getAreaUnitLabel() }}² </span>
          </template>
        </FlatPreviewKeyValue>

        <FlatPreviewKeyValue
          v-if="flatTypeData?.rooms_count"
          :keyName="tr('room')"
          :value="getRoomCount(flatTypeData?.rooms_count)"
          keyClass="flat_rooms_key flat_date_key"
          valueClass="flat_rooms_value flat_date_value"
          class="irep-flat-preview__right-floor-room"
        />

        <template v-if="flatTypeData?.other?.length">
          <FlatPreviewKeyValue
            v-for="other in flatTypeData?.other"
            :key="other.key"
            :keyName="other.key"
            :value="other.value"
            keyClass="flat_date_key"
            valueClass="flat_date_value"
          />
        </template>
      </div>
    </div>

    <Price v-if="flat" :flat="flat" />

    <div class="irep-flat-moda-action-buttons ire-flex ire-flex-col ire-gap-4 ire-text-center sm:ire-flex-row">
      <a
        v-if="flat?.files?.[0]?.url"
        :href="flat?.files?.[0]?.url"
        target="_blank"
        class="irep-flat-moda-action-buttons__download-file ire-w-full !ire-no-underline hover:!ire-no-underline"
      >
        <Button :title="tr('download file')" variant="outline" class="ire-w-full">
          <template #icon>
            <DownloadIcon class="ire-size-4 group-hover:[&_path]:ire-stroke-white" />
          </template>
        </Button>
      </a>

      <div
        v-if="showCallbackButton"
        class="irep-flat-moda-action-buttons__request-callback ire-w-full !ire-no-underline hover:!ire-no-underline"
      >
        <Button
          :title="tr('Request callback')"
          active
          class="!ire-w-full"
          @click="emit('requestCallback')"
        >
          <template #icon>
            <PhoneIcon class="ire-size-4 [&_path]:ire-fill-white" />
          </template>
        </Button>
      </div>
    </div>
  </div>
</template>
