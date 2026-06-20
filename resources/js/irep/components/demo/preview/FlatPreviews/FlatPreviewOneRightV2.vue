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
import Button from "../../../../components/demo/uiComponents/Button.vue";
import DownloadIcon from "../../../../components/icons/DownloadIcon.vue";
import PhoneIcon from "../../../../components/icons/PhoneIcon.vue";
import Price from "../../uiComponents/Price.vue";
import FlatPreviewOneAttrRow from "./FlatPreviewOneAttrRow.vue";
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
    const useType =
        props.flat?.use_type === true ||
        String(props.flat?.use_type) === "true";
    return useType
        ? (props.flat?.type ?? props.flat?.flat_type)
        : (props.flat?.flat_type ?? props.flat?.type);
});
</script>

<template>
    <div
        class="irep-flat-preview__right irep-flat-preview__right--v2 ease-in-out-quint ire-flex ire-h-full ire-w-full ire-flex-[1_0_auto] ire-origin-left ire-flex-col ire-justify-between ire-gap-4 ire-px-6 ire-py-5 ire-transition-transform ire-duration-300"
        :class="{
            '!ire-h-0 -ire-translate-x-full ire-scale-0 md:!ire-h-auto':
                showForm,
        }"
    >
        <div
            class="irep-flat-preview__details-v2 ire-flex ire-w-full ire-flex-col ire-gap-4"
        >
            <div class="ire-flex ire-flex-col ire-gap-1">
                <div class="ire-flex ire-items-center ire-gap-2">
                    <span
                        v-if="flat?.flat_number"
                        class="irep-flat-kv__number-badge ire-inline-flex ire-items-center ire-rounded-md ire-bg-gray-100 ire-px-2.5 ire-py-1 ire-text-xs ire-font-semibold ire-uppercase ire-tracking-wide ire-text-gray-700"
                    >
                        {{ tr("apartment") }} {{ flat.flat_number }}
                    </span>
                </div>

                <div
                    v-if="flatTypeData?.title"
                    class="irep-flat-preview__type-title ire-text-xl ire-font-semibold ire-text-gray-900"
                >
                    {{ flatTypeData?.title }}
                </div>

                <div
                    v-if="flatTypeData?.teaser"
                    class="irep-flat-preview__type-teaser ire-text-sm ire-uppercase ire-text-gray-400"
                >
                    {{ flatTypeData?.teaser }}
                </div>
            </div>

            <div
                class="irep-flat-preview__attr-list ire-flex ire-w-full ire-flex-col ire-overflow-y-auto ire-pr-2 lg:ire-max-h-[300px]"
            >
                <FlatPreviewOneAttrRow
                    v-if="flat?.block_id"
                    :label="tr('block')"
                    :value="getBlockById(+flat.block_id)?.title"
                />

                <FlatPreviewOneAttrRow
                    v-if="flat?.floor_id"
                    :label="tr('floor')"
                    :value="getFloorById(+flat.floor_id)?.floor_number"
                />

                <FlatPreviewOneAttrRow
                    v-if="flat?.floor_id"
                    :label="tr('floor title')"
                    :value="getFloorById(+flat.floor_id)?.title"
                />

                <FlatPreviewOneAttrRow
                    v-if="flatTypeData?.area_m2"
                    :label="tr('area')"
                    :value="getArea(flatTypeData?.area_m2)"
                >
                    {{ getArea(flatTypeData?.area_m2) }}
                    {{ getAreaUnitLabel() }}²
                </FlatPreviewOneAttrRow>

                <FlatPreviewOneAttrRow
                    v-if="flatTypeData?.rooms_count"
                    :label="tr('room')"
                    :value="getRoomCount(flatTypeData?.rooms_count)"
                />

                <FlatPreviewOneAttrRow
                    v-for="other in flatTypeData?.other"
                    :key="other.key"
                    :label="other.key"
                    :value="other.value"
                />
            </div>
        </div>

        <div class="ire-flex ire-flex-col ire-gap-4">
            <Price
                v-if="flat"
                :flat="flat"
                class="[&_.irep-price]:ire-text-left [&_.irep-price]:ire-justify-start"
            />

            <div
                class="irep-flat-moda-action-buttons ire-flex ire-flex-col ire-gap-3 sm:ire-flex-row"
            >
                <a
                    v-if="flat?.files?.[0]?.url"
                    :href="flat?.files?.[0]?.url"
                    target="_blank"
                    class="irep-flat-moda-action-buttons__download-file ire-w-full !ire-no-underline hover:!ire-no-underline"
                >
                    <Button
                        :title="tr('download file')"
                        variant="outline"
                        class="ire-w-full"
                    >
                        <template #icon>
                            <DownloadIcon
                                class="ire-size-4 group-hover:[&_path]:ire-stroke-white"
                            />
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
                            <PhoneIcon
                                class="ire-size-4 [&_path]:ire-fill-white"
                            />
                        </template>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
