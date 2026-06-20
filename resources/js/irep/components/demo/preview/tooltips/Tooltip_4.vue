<script setup lang="ts">
import {
  currencySymbol,
  getArea,
  getAreaUnitLabel,
  getFloorById,
  getPrice,
  getRoomCount,
  tr,
} from "../../../../composable/helper";
import type {
  BlockItem,
  FlatItem,
  FloorItem,
  TypeItem,
} from "../../../../types/DemoTypes";
import { computed, inject } from "vue";
import Area from "../../../../components/icons/Area.vue";
import Bed from "../../../../components/icons/Bed.vue";
import Floor from "../../../../components/icons/Floor.vue";
import MoneyBag from "../../../../components/icons/MoneyBag.vue";
import Badge from "../../../../components/demo/uiComponents/Badge.vue";

const props = defineProps<{
  hoveredData: any;
  type: "flat" | "floor" | "block" | "tooltip" | "";
}>();

const flatTeaserImageUrl = computed(() => {
  if (props.type !== "flat" || !props.hoveredData) return "";
  const t = props.hoveredData.type as TypeItem | undefined;
  return t?.image_2d?.[0]?.url || t?.image_3d?.[0]?.url || "";
});

const floorItem = computed(() =>
  props.type === "floor" ? (props.hoveredData as FloorItem) : null,
);

const statusConf = computed(() =>
  tr((props.hoveredData as BlockItem | FloorItem | undefined)?.conf || ""),
);

const mouseX = inject<number>("mouseX");
const mouseY = inject<number>("mouseY");

const floorStatsVisible = computed(() => {
  if (!floorItem.value) return false;
  const available = statusConf.value ? 0 : (floorItem.value.counts?.available ?? 0);
  const sold = statusConf.value === "sold" ? floorItem.value.flats?.length || 0 : (floorItem.value.counts?.sold ?? 0);
  const reserved = statusConf.value === "reserved" ? floorItem.value.flats?.length || 0 : (floorItem.value.counts?.reserved ?? 0);
  return available !== 0 || sold !== 0 || reserved !== 0;
});
</script>

<template>
  <div
    v-if="type && hoveredData && type !== 'tooltip'"
    class="irep-tooltip-4__wrapper ire-pointer-events-none ire-absolute ire-left-0 ire-top-0 ire-select-none"
    :style="{
      transform: `translateX(${mouseX || 0}px) translateY(${mouseY || 0}px)`,
    }"
  >
    <div
      class="irep-tooltip irep-tooltip-4 ire-flex ire-min-w-[12.5rem] ire-max-w-[18rem] ire-origin-top ire-flex-col ire-overflow-hidden ire-rounded-lg ire-border ire-border-gray-200/90 ire-bg-white ire-text-gray-800 ire-shadow-lg ire-transition-transform ire-duration-500 ire-ease-in-out"
    >
      <!-- Floor -->
      <template v-if="type === 'floor' && floorItem">
        <div class="irep-tooltip-4__floor-header ire-flex ire-flex-col ire-gap-1 ire-px-4 ire-pb-3 ire-pt-4">
          <div
            class="irep-tooltip-4__floor-title-row ire-flex ire-items-baseline ire-justify-between ire-gap-3"
          >
            <div class="irep-tooltip-4__floor-number-col ire-flex ire-flex-col ire-gap-0.5">
              <span
                class="ire-text-xs ire-font-semibold ire-uppercase ire-tracking-wide ire-text-gray-500"
              >
                {{ tr("floor") }}
              </span>
              <span
                class="ire-text-3xl ire-font-semibold ire-tabular-nums ire-leading-none ire-text-gray-900"
              >
                {{ floorItem.floor_number }}
              </span>
            </div>
            <div
              v-if="statusConf"
              class="irep-tooltip-4__floor-conf ire-shrink-0 ire-rounded-md ire-bg-gray-100 ire-px-2.5 ire-py-1 ire-text-sm ire-font-semibold ire-uppercase ire-tracking-wide ire-text-gray-800"
            >
              {{ statusConf }}
            </div>
            <div v-else class="irep-tooltip-4__floor-price-section ire-min-w-0 ire-text-right">
              <div class="irep-tooltip-4__floor-starting-label ire-text-xs ire-font-medium ire-text-gray-500">
                {{ tr("starting from") }}
              </div>
              <div
                class="irep-tooltip-4__floor-price ire-text-lg ire-font-semibold ire-tabular-nums ire-text-gray-900"
              >
                {{ getPrice(+(floorItem.counts?.minimum_price || 0)) }}
                <span class="ire-text-sm ire-font-medium ire-text-gray-500">
                  {{ currencySymbol() }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div
          v-if="floorStatsVisible"
          class="irep-tooltip-4__floor-stats ire-flex ire-items-center ire-justify-between ire-gap-2 ire-border-t ire-border-gray-100 ire-bg-gray-50 ire-px-4 ire-py-2.5 ire-text-sm ire-text-gray-700"
        >
          <div
            class="irep-tooltip-4__floor-stat-item ire-flex ire-flex-1 ire-flex-col ire-items-center ire-gap-0.5"
          >
            <span
              class="ire-text-lg ire-font-semibold ire-tabular-nums ire-text-gray-900"
            >
              {{ statusConf ? 0 : (floorItem.counts?.available ?? 0) }}
            </span>
            <span class="ire-text-xs ire-font-medium ire-text-gray-500">
              {{ tr("available") }}
            </span>
          </div>
          <span class="ire-text-gray-300" aria-hidden="true">·</span>
          <div
            class="irep-tooltip-4__floor-stat-item ire-flex ire-flex-1 ire-flex-col ire-items-center ire-gap-0.5"
          >
            <span
              class="ire-text-lg ire-font-semibold ire-tabular-nums ire-text-gray-900"
            >
              {{
                statusConf === "sold"
                  ? floorItem.flats?.length || 0
                  : (floorItem.counts?.sold ?? 0)
              }}
            </span>
            <span class="ire-text-xs ire-font-medium ire-text-gray-500">
              {{ tr("sold") }}
            </span>
          </div>
          <span class="ire-text-gray-300" aria-hidden="true">·</span>
          <div
            class="irep-tooltip-4__floor-stat-item ire-flex ire-flex-1 ire-flex-col ire-items-center ire-gap-0.5"
          >
            <span
              class="ire-text-lg ire-font-semibold ire-tabular-nums ire-text-gray-900"
            >
              {{
                statusConf === "reserved"
                  ? floorItem.flats?.length || 0
                  : (floorItem.counts?.reserved ?? 0)
              }}
            </span>
            <span class="ire-text-xs ire-font-medium ire-text-gray-500">
              {{ tr("reserved") }}
            </span>
          </div>
        </div>
      </template>

      <!-- Block -->
      <template v-else-if="type === 'block'">
        <div class="irep-tooltip-4__block-body ire-px-4 ire-py-3.5">
          <div
            class="irep-tooltip-4__block-label ire-text-xs ire-font-semibold ire-uppercase ire-tracking-wide ire-text-gray-500"
          >
            {{ tr("block") }}
          </div>
          <div
            class="irep-tooltip-4__block-title ire-mt-1 ire-text-lg ire-font-semibold ire-leading-snug ire-text-gray-900"
          >
            {{ (hoveredData as BlockItem)?.title }}
          </div>
          <div
            v-if="statusConf"
            class="irep-tooltip-4__block-conf ire-mt-2 ire-inline-flex ire-rounded-md ire-bg-gray-100 ire-px-2.5 ire-py-1 ire-text-sm ire-font-semibold ire-uppercase ire-text-gray-700"
          >
            {{ statusConf }}
          </div>
        </div>
      </template>

      <!-- Flat -->
      <template v-else-if="type === 'flat'">
        <!-- Header: flat number + status badge -->
        <div
          class="irep-tooltip-4__flat-header ire-flex ire-items-start ire-justify-between ire-gap-4 ire-px-3 ire-pt-3"
        >
          <span
            v-if="hoveredData?.flat_number"
            class="ire-text-base ire-font-bold ire-tracking-tight ire-text-gray-900"
          >
            {{ hoveredData.flat_number }}
          </span>
          <Badge
            v-if="(hoveredData as FlatItem)?.conf"
            :conf="(hoveredData as FlatItem).conf || ''"
          />
          <span
            v-else-if="
              !(hoveredData as FlatItem)?.conf &&
              !(hoveredData as FlatItem)?.request_price
            "
            class="ire-shrink-0 ire-rounded-full ire-bg-emerald-50 ire-px-2.5 ire-py-0.5 ire-text-xs ire-uppercase ire-tracking-wide ire-text-black"
          >
            {{ tr("available") }}
          </span>
        </div>

        <!-- Price -->
        <div class="irep-tooltip-4__flat-price-section ire-px-3 ire-pb-3 ire-pt-1">
          <div
            v-if="(hoveredData as FlatItem)?.request_price"
            class="irep-tooltip-4__flat-request-price ire-text-sm ire-font-medium ire-text-gray-500"
          >
            {{ tr("Request Price") }}
          </div>
          <template v-else-if="!(hoveredData as FlatItem)?.conf">
            <div
              v-if="
                (hoveredData as FlatItem)?.offer_price &&
                Number((hoveredData as FlatItem)?.offer_price) > 0
              "
              class="irep-tooltip-4__flat-offer-price ire-flex ire-items-baseline ire-gap-1.5"
            >
              <span
                class="irep-tooltip-4__flat-price ire-text-xl ire-font-bold ire-tabular-nums ire-text-gray-900"
              >
                {{ getPrice(+(hoveredData as FlatItem)?.offer_price || 0) }}
                <span class="ire-text-sm ire-font-medium ire-text-gray-500">{{
                  currencySymbol()
                }}</span>
              </span>
              <span
                class="ire-text-xs ire-tabular-nums ire-text-gray-400 ire-line-through"
              >
                {{ getPrice(+(hoveredData as FlatItem)?.price || 0) }}
                {{ currencySymbol() }}
              </span>
            </div>
            <div
              v-else-if="Number((hoveredData as FlatItem)?.price) > 0"
              class="irep-tooltip-4__flat-price ire-text-xl ire-font-bold ire-tabular-nums ire-text-gray-900"
            >
              {{ getPrice(+(hoveredData as FlatItem)?.price || 0) }}
              <span class="ire-text-sm ire-font-medium ire-text-gray-500">{{
                currencySymbol()
              }}</span>
            </div>
          </template>
        </div>

        <!-- Stats footer -->
        <div
          v-if="
            (hoveredData?.type as TypeItem)?.area_m2 ||
            (hoveredData as FlatItem)?.type?.rooms_count ||
            (hoveredData as FlatItem)?.floor_id
          "
          class="irep-tooltip-4__flat-stats ire-flex ire-items-stretch ire-border-t ire-border-gray-100"
        >
          <div
            v-if="(hoveredData?.type as TypeItem)?.area_m2"
            class="irep-tooltip-4__flat-stat-item ire-flex ire-flex-1 ire-flex-col ire-gap-1 ire-px-4 ire-py-3 [&:not(:last-child)]:ire-border-r [&:not(:last-child)]:ire-border-gray-100"
          >
            <span
              class="ire-text-xs ire-font-medium ire-uppercase ire-tracking-wide ire-text-gray-400"
              >{{ tr("area") }}</span
            >
            <div class="irep-tooltip-4__flat-stat-value ire-flex ire-items-center ire-gap-1.5">
              <Area class="ire-size-3.5 ire-shrink-0 ire-text-gray-400" />
              <span
                class="ire-min-w-max ire-text-sm ire-font-semibold ire-tabular-nums ire-text-gray-900"
              >
                {{ getArea((hoveredData?.type as TypeItem)?.area_m2 ?? "") }}
                <span class="ire-text-xs ire-font-normal ire-text-gray-500"
                  >{{ getAreaUnitLabel() }}²</span
                >
              </span>
            </div>
          </div>
          <div
            v-if="(hoveredData as FlatItem)?.type?.rooms_count"
            class="irep-tooltip-4__flat-stat-item ire-flex ire-flex-1 ire-flex-col ire-gap-1 ire-px-4 ire-py-3 [&:not(:last-child)]:ire-border-r [&:not(:last-child)]:ire-border-gray-100"
          >
            <span
              class="ire-text-xs ire-font-medium ire-uppercase ire-tracking-wide ire-text-gray-400"
              >{{ tr("rooms") }}</span
            >
            <div class="irep-tooltip-4__flat-stat-value ire-flex ire-items-center ire-gap-1.5">
              <Bed class="ire-size-3.5 ire-shrink-0 ire-text-gray-400" />
              <span
                class="ire-min-w-max ire-text-sm ire-font-semibold ire-tabular-nums ire-text-gray-900"
              >
                {{
                  getRoomCount(
                    (hoveredData as FlatItem)?.type?.rooms_count ?? "",
                  )
                }}
              </span>
            </div>
          </div>
          <div
            v-if="(hoveredData as FlatItem)?.floor_id"
            class="irep-tooltip-4__flat-stat-item ire-flex ire-flex-1 ire-flex-col ire-gap-1 ire-px-4 ire-py-3 [&:not(:last-child)]:ire-border-r [&:not(:last-child)]:ire-border-gray-100"
          >
            <span
              class="ire-text-xs ire-font-medium ire-uppercase ire-tracking-wide ire-text-gray-400"
              >{{ tr("floor") }}</span
            >
            <div class="irep-tooltip-4__flat-stat-value ire-flex ire-items-center ire-gap-1.5">
              <Floor class="ire-size-3.5 ire-shrink-0 ire-text-gray-400" />
              <span
                class="ire-min-w-max ire-text-sm ire-font-semibold ire-tabular-nums ire-text-gray-900"
              >
                {{
                  getFloorById(
                    +hoveredData.floor_id,
                  )?.floor_number?.toString() || ""
                }}
              </span>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
