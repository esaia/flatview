<script setup lang="ts">
import type { FlatItem } from "../../../types/DemoTypes";
import {
  tr,
  getAreaUnitLabel,
  getPrice,
  currencySymbol,
} from "../../../composable/helper";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { computed } from "vue";
import { ref } from "vue";
import FlatPriceHistoryModal from "./FlatPriceHistoryModal.vue";
import IconButton from "./IconButton.vue";
import LineChartIcon from "../../../components/icons/LineChartIcon.vue";

const globalStore = useGlobalStore();
const { hasPriceHistoryAddon } = storeToRefs(globalStore);

const props = defineProps<{
  flat: FlatItem;
}>();

const showPriceHistoryModal = ref(false);
const primaryColor = computed(() => {
  const color =
    globalStore
      .getMetaValue("custom_types")
      ?.find((type: any) => type?.title === props.flat?.conf)?.type_color || "";

  return color ? toOpaqueColor(color) : "";
});

function toOpaqueColor(color: string): string {
  if (color.startsWith("rgba"))
    return color.replace(
      /rgba\(([^,]+),([^,]+),([^,]+),[^)]+\)/,
      "rgba($1,$2,$3, 1)",
    );
  if (color.startsWith("hsla"))
    return color.replace(
      /hsla\(([^,]+),([^,]+),([^,]+),[^)]+\)/,
      "hsla($1,$2,$3, 1)",
    );
  return color;
}

const confBgColor = computed(() => {
  if (props.flat?.conf === "reserved") {
    const c =
      globalStore.getMetaValue("reserved_color") || "rgba(255, 247, 89, 0.53)";
    return toOpaqueColor(c);
  }
  if (props.flat?.conf === "sold") {
    const c =
      globalStore.getMetaValue("sold_color") || "rgba(219, 64, 64, 0.45)";
    return toOpaqueColor(c);
  }
  return primaryColor.value;
});
const hasPriceHistory = computed<boolean>(() => {
  return (
    (props.flat?.price_history?.length ?? 0) >= 2 && hasPriceHistoryAddon.value
  );
});

const inquiryLink = computed<string>(() => {
  const w = props.flat?.whatsapp;
  if (!w || w.type === "none") return "";
  const msg = (w.message || "Hi, I am interested in apartment N{flat_number}").replace(
    "{flat_number}",
    props.flat.flat_number ?? "",
  );
  const encoded = encodeURIComponent(msg);
  if (w.type === "custom_url") {
    if (!w.url) return "";
    return `${w.url}${w.url.includes("?") ? "&" : "?"}text=${encoded}`;
  }
  if (!w.phone) return "";
  return `https://wa.me/${w.phone.replace(/\D/g, "")}?text=${encoded}`;
});

const pricePerM2 = computed<number>(() => {
  const areaM2 = Number(props.flat?.type?.area_m2);
  const selectedPrice = Number(props.flat?.offer_price || props.flat?.price);

  if (
    !Number.isFinite(areaM2) ||
    areaM2 <= 0 ||
    !Number.isFinite(selectedPrice) ||
    selectedPrice <= 0
  ) {
    return 0;
  }

  return selectedPrice / areaM2;
});
</script>
<template>
  <div
    class="irep-price flex ire-min-w-0 ire-max-w-full ire-flex-col ire-items-center ire-justify-center"
  >
    <div
      class="irep-price__wrapper ire-flex ire-max-w-full ire-items-center ire-justify-center ire-gap-2"
    >
      <div
        class="irep-flat-modal-price-container ire-w-fit ire-min-w-0 ire-max-w-full ire-rounded-sm ire-bg-[var(--primary-color)] ire-p-2 ire-text-4xl"
        :class="[
          `irep-flat-modal--${flat?.conf?.replace(/ /g, '-') || 'price'}`,
          'ire-text-white',
        ]"
        :style="{ backgroundColor: confBgColor }"
      >
        <div v-if="flat?.conf" class="irep-price__conf ire-uppercase">
          {{ tr(flat.conf) }}
        </div>

        <template v-else-if="flat?.request_price">
          <a
            v-if="inquiryLink"
            :href="inquiryLink"
            target="_blank"
            rel="noopener noreferrer"
            class="irep-price__request irep-price__whatsapp ire-uppercase ire-cursor-pointer ire-no-underline ire-text-inherit ire-outline-none"
          >
            {{ tr("Request Price") }}
          </a>
          <div v-else class="irep-price__request ire-uppercase">
            {{ tr("Request Price") }}
          </div>
        </template>

        <div
          v-else-if="flat?.offer_price && Number(flat?.offer_price) > 0"
          class="irep-price__offer"
        >
          <div
            class="irep-price__original ire-text-left ire-text-base ire-line-through ire-decoration-white"
          >
            {{ getPrice(Number(flat?.price)) }}

            <span> {{ currencySymbol() }}</span>
          </div>

          <div class="irep-price__value">
            {{ getPrice(Number(flat?.offer_price)) }}
            <span> {{ currencySymbol() }}</span>
          </div>
        </div>

        <div
          v-else-if="Number((flat as any)?.price) > 0"
          class="irep-price__value"
        >
          {{ getPrice(Number(flat?.price)) }}
          <span> {{ currencySymbol() }}</span>
        </div>

        <div v-else class="irep-price__available ire-uppercase">
          {{ tr("available") }}
        </div>
      </div>

      <IconButton
        v-if="hasPriceHistory && flat"
        class="price-history-button"
        @click="showPriceHistoryModal = true"
      >
        <LineChartIcon />
      </IconButton>
    </div>

    <div
      v-if="pricePerM2 > 0 && !flat.request_price && !flat.conf"
      class="irep-price__per-m2 ire-pt-2 ire-text-base ire-text-gray-700"
    >
      {{ getPrice(pricePerM2) }}
      {{ currencySymbol() }} / {{ getAreaUnitLabel() }}²
    </div>

    <Transition name="ire-fade-in-out" mode="out-in">
      <FlatPriceHistoryModal
        v-if="showPriceHistoryModal && flat && hasPriceHistory"
        :price-history="flat.price_history"
        @close="showPriceHistoryModal = false"
      />
    </Transition>
  </div>
</template>
