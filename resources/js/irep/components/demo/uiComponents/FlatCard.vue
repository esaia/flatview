<script setup lang="ts">
import { computed, ref } from "vue";
import type { FlatItem } from "../../../types/DemoTypes";
import {
  currencySymbol,
  getArea,
  getAreaUnitLabel,
  getPrice,
  getRoomCount,
  tr,
} from "../../../composable/helper";
import Placeholder from "../../../components/icons/Placeholder.vue";
import Badge from "./Badge.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import LineChartIcon from "../../../components/icons/LineChartIcon.vue";
import IconButton from "./IconButton.vue";
import FlatPriceHistoryModal from "./FlatPriceHistoryModal.vue";
import Area from "../../../components/icons/Area.vue";
import Bed from "../../../components/icons/Bed.vue";

const emit = defineEmits<{
  (e: "openFlat", flatId: string): void;
}>();

const props = defineProps<{
  flat: FlatItem;
}>();

const globalStore = useGlobalStore();
const { hasPriceHistoryAddon } = storeToRefs(globalStore);

const showPriceHistoryModal = ref(false);

const flatImages = computed(() => {
  return [
    ...(props.flat.type?.image_3d || []),
    ...(props.flat.type?.image_2d || []),
  ].map((i) => i.url);
});

const hasPriceHistory = computed(
  () =>
    (props.flat?.price_history?.length ?? 0) >= 2 && hasPriceHistoryAddon.value,
);
</script>
<template>
  <div
    class="irep-flat-card ire-cursor-pointer ire-rounded-sm ire-text-black ire-transition-all ire-duration-300"
    @click="emit('openFlat', flat.id)"
  >
    <div
      class="irep-flat-card__image-wrapper ire-group ire-relative ire-pt-[70%]"
    >
      <img
        v-if="flatImages[0]"
        :src="flatImages[0]"
        alt=""
        class="ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-rounded-lg ire-object-cover ire-transition-all ire-duration-700 ire-ease-[cubic-bezier(0.25,1,0.5,1)] group-hover:ire-scale-95"
      />
      <div v-else class="irep-flat-card__placeholder">
        <Placeholder
          class="ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-bg-gray-100 ire-transition-all ire-duration-700 ire-ease-[cubic-bezier(0.25,1,0.5,1)] group-hover:ire-scale-95 [&_rect]:ire-fill-gray-100"
        />
      </div>
    </div>

    <div
      class="irep-flat-card__info ire-mt-4 ire-flex ire-flex-col ire-gap-1 md:ire-gap-2"
    >
      <div
        class="irep-flat-card__title-row flex ire-flex-col ire-justify-between ire-gap-1 ire-text-center sm:ire-flex-row md:ire-gap-2"
      >
        <div
          class="irep-flat-card__title ire-line-clamp-2 ire-text-left ire-text-lg ire-font-semibold"
        >
          {{ flat.flat_number }}
        </div>

        <div
          class="irep-flat-card__price-row flex ire-min-w-max ire-items-center ire-gap-1 ire-text-xl ire-text-[var(--primary-color)] md:ire-gap-2"
        >
          <div
            v-if="flat.request_price"
            class="irep-flat-card__request-price ire-font-semibold ire-capitalize"
          >
            {{ tr("request price") }}
          </div>

          <Badge v-else-if="flat.conf" :conf="flat.conf" />

          <div
            v-else-if="flat.offer_price"
            class="irep-flat-card__offer-price ire-font-semibold"
          >
            <div
              class="irep-flat-card__original-price ire-text-xs ire-text-gray-500 ire-line-through"
            >
              {{ getPrice(+(flat as any).price_n) }}
              {{ currencySymbol() }}
            </div>

            <div class="irep-flat-card__price">
              {{ getPrice(+flat.offer_price) }}
              {{ currencySymbol() }}
            </div>
          </div>

          <div
            v-else-if="Number((flat as any)?.price_n) > 0"
            class="irep-flat-card__price ire-font-semibold"
          >
            {{ getPrice(+(flat as any).price_n) }}
            {{ currencySymbol() }}
          </div>

          <IconButton
            v-if="hasPriceHistory && flat"
            class="price-history-button ire-w-fit"
            @click.stop.prevent="showPriceHistoryModal = true"
          >
            <LineChartIcon />
          </IconButton>
        </div>
      </div>

      <div class="irep-flat-card__meta flex ire-items-center ire-gap-4">
        <div
          v-if="flat.type?.area_m2"
          class="irep-flat-card__area flex ire-items-center ire-gap-1"
        >
          <Area class="ire-size-6" />
          <span class="ire-right-[2px] ire-text-base">
            {{ getArea((flat as any).type?.area_m2_n) }}
            {{ getAreaUnitLabel() }}
            <sup class="ire-bg-transparent ire-text-sm"> 2 </sup>
          </span>
        </div>

        <div
          v-if="flat.type?.rooms_count"
          class="irep-flat-card__rooms flex ire-items-center ire-gap-1"
        >
          <Bed class="ire-size-4 [&_path]:ire-fill-gray-500" />

          <span class="ire-right-[2px] ire-text-base">
            {{ getRoomCount(flat.type?.rooms_count) }}
            {{ tr("room") }}
          </span>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="ire-fade-in-out" mode="out-in">
        <FlatPriceHistoryModal
          v-if="showPriceHistoryModal && flat && hasPriceHistory"
          :price-history="flat?.price_history"
          @close="showPriceHistoryModal = false"
        />
      </Transition>
    </Teleport>
  </div>
</template>
