<script setup lang="ts">
import { useGlobalStore } from "../../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { computed, inject, nextTick, onMounted, ref, watch } from "vue";

import {
  useGetFloorById,
  normalizeFilterOptionsMeta,
  normalizeRangeOption,
  tr,
  getAreaUnitLabel,
  getArea,
  getRoomCount,
  mediaThumbUrl,
} from "../../../../composable/helper";
import FlatsSidebarHeader from "./FlatsSidebarHeader.vue";
import Area from "../../../../components/icons/Area.vue";
import Bed from "../../../../components/icons/Bed.vue";
import Badge from "../Badge.vue";
import Floor from "../../../../components/icons/Floor.vue";
import Toggle from "../../../../components/demo/uiComponents/Toggle.vue";
import EyeIcon from "../../../../components/icons/EyeIcon.vue";
import ClearFiltersButton from "../../../../components/demo/uiComponents/ClearFiltersButton.vue";
import HomeIcon from "../../../../components/icons/HomeIcon.vue";

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { flats, shortcodeData } = storeToRefs(globalStore);
const getFloorById = useGetFloorById();

const showOnlyFilteredOnSvg = defineModel<boolean>("showOnlyFilteredOnSvg", {
  default: false,
});

const props = defineProps<{
  activeView?: "360" | "floors";
  floors360FloorId?: string | null;
  floors360BlockId?: string | null;
}>();

const emit = defineEmits<{
  "update:filteredFlatIds": [ids: ReadonlySet<string>];
}>();

const activateFlat = inject<(flat: any) => void>("activateFlat", () => {});
const focusFlatOnViewer = inject<(flat: any) => void>(
  "focusFlatOnViewer",
  () => {},
);

const onFlatCardClick = (flat: any) => {
  activateFlat(flat);
};

const floorsMinMax = computed(() => {
  const floors = shortcodeData.value?.floors ?? [];
  const floorNumbers = floors
    .map((floor) => Number(floor.floor_number))
    .filter((value) => Number.isFinite(value));

  if (!floorNumbers.length) {
    return { min: 0, max: 16 };
  }

  return {
    min: Math.min(...floorNumbers),
    max: Math.max(...floorNumbers),
  };
});

const pathsFillOnHoverOnly = computed(
  () => globalStore.getMetaValue("paths_hover_fill") === "true",
);

const filterOptions = normalizeFilterOptionsMeta(
  getMetaValue("filter_options"),
);
const customAreaOptions = normalizeRangeOption(
  filterOptions.area_filter_options,
  {
    min: 0,
    max: 300,
    step: 10,
  },
);
const customRoomOptions = normalizeRangeOption(
  filterOptions.rooms_filter_options,
  {
    min: 0,
    max: 10,
    step: 1,
  },
);

const filtersObject = ref({
  areaRange: [customAreaOptions.min, customAreaOptions.max] as [number, number],
  floorRange: [floorsMinMax.value.min, floorsMinMax.value.max] as [
    number,
    number,
  ],
  roomRange: [customRoomOptions.min, customRoomOptions.max] as [number, number],
  config: "all",
});

const defaultFilters = computed(() => ({
  areaRange: [customAreaOptions.min, customAreaOptions.max] as [number, number],
  floorRange: [floorsMinMax.value.min, floorsMinMax.value.max] as [
    number,
    number,
  ],
  roomRange: [customRoomOptions.min, customRoomOptions.max] as [number, number],
  config: "all",
}));

const hasChanges = computed(() => {
  const defaults = defaultFilters.value;
  const current = filtersObject.value;
  return (
    JSON.stringify(current.areaRange) !== JSON.stringify(defaults.areaRange) ||
    JSON.stringify(current.floorRange) !==
      JSON.stringify(defaults.floorRange) ||
    JSON.stringify(current.roomRange) !== JSON.stringify(defaults.roomRange) ||
    current.config !== defaults.config
  );
});

const hasFloorRangeChanged = computed(
  () =>
    filtersObject.value.floorRange[0] !== floorsMinMax.value.min ||
    filtersObject.value.floorRange[1] !== floorsMinMax.value.max,
);

const hasAreaRangeChanged = computed(
  () =>
    filtersObject.value.areaRange[0] !== customAreaOptions.min ||
    filtersObject.value.areaRange[1] !== customAreaOptions.max,
);

const hasRoomRangeChanged = computed(
  () =>
    filtersObject.value.roomRange[0] !== customRoomOptions.min ||
    filtersObject.value.roomRange[1] !== customRoomOptions.max,
);

const resetFilters = () => {
  filtersObject.value = { ...defaultFilters.value };
};

const scrollAreaRef = ref<HTMLElement | null>(null);
const hasScrolled = ref(false);

const onScrollAreaScroll = () => {
  const el = scrollAreaRef.value;
  if (!el) return;
  hasScrolled.value = el.scrollTop > 0;
};

const types = computed(() => {
  if (!shortcodeData.value) return;

  return shortcodeData.value.types;
});

const flatTypeTeaserImageUrl = (flat: any) => {
  const media = [
    ...(flat.type?.image_2d || []),
    ...(flat.type?.image_3d || []),
  ];
  for (const item of media) {
    const url = mediaThumbUrl(item);
    if (url) return url;
  }
  return "";
};

const flatFloorNumberLabel = (flat: any) =>
  getFloorById(+flat?.floor_id)?.floor_number?.toString() ?? "";

const isFloorsView = computed(() => props.activeView === "floors");

const filteredFlats = computed(() => {
  const list = flats.value ?? [];
  if (!list.length) return [];

  const [aMin, aMax] = filtersObject.value.areaRange;
  const [fMin, fMax] = filtersObject.value.floorRange;
  const [rMin, rMax] = filtersObject.value.roomRange;
  const configFilter = filtersObject.value.config ?? "all";

  const normalizeNumber = (value: string | number) =>
    parseFloat(String(value).trim().replace(",", "."));

  return list
    .filter((flat: any) => {
      const area = Number(flat.type?.area_m2_n ?? flat.type?.area_m2);
      const floor = Number(getFloorById(flat.floor_id)?.floor_number);
      const roomsCount = normalizeNumber(
        flat.type?.rooms_count?.toString() || "0",
      );

      const areaMatch =
        !hasAreaRangeChanged.value ||
        (Number.isFinite(area) ? area >= aMin && area <= aMax : false);

      let floorMatch: boolean;
      if (isFloorsView.value) {
        floorMatch =
          props.floors360FloorId != null
            ? String(flat.floor_id) === String(props.floors360FloorId)
            : true;
      } else {
        floorMatch =
          !hasFloorRangeChanged.value ||
          (Number.isFinite(floor) ? floor >= fMin && floor <= fMax : false);
      }

      const blockMatch = isFloorsView.value
        ? props.floors360BlockId != null
          ? String(flat.block_id) === String(props.floors360BlockId)
          : !flat.block_id
        : true;
      const roomMatch =
        !hasRoomRangeChanged.value ||
        (Number.isFinite(roomsCount) &&
          roomsCount >= rMin &&
          roomsCount <= rMax);

      let configMatch = true;
      if (configFilter !== "all") {
        if (configFilter === "available") {
          configMatch = !flat.conf;
        } else {
          const customTypes = getMetaValue("custom_types");
          const customType = Array.isArray(customTypes)
            ? customTypes.find(
                (t: { value: string }) => t.value === configFilter,
              )
            : null;

          if (customType) {
            configMatch = flat.conf === customType.title;
          } else {
            configMatch = flat.conf === configFilter;
          }
        }
      }

      return areaMatch && floorMatch && blockMatch && roomMatch && configMatch;
    })
    .map((flat: any) => {
      if (flat?.use_type || !flat?.type) {
        const flatType = types.value?.find(
          (type) => type?.id === flat?.type_id,
        );
        if (flatType) {
          flat.type = flatType;
        }
      }

      const customTypes = getMetaValue("custom_types");
      const customType = customTypes?.find((t: any) => t.value === flat.conf);
      flat.conf = customType ? customType.title : flat.conf;

      return flat;
    });
});

watch(
  filteredFlats,
  (list) => {
    emit(
      "update:filteredFlatIds",
      new Set(list.map((f: { id: string }) => String(f.id))),
    );
  },
  { deep: true, immediate: true },
);

watch(
  () => hasChanges.value,
  () => {
    if (!hasChanges.value) {
      showOnlyFilteredOnSvg.value = false;
    }
  },
);

watch(
  filtersObject,
  () => {
    visibleCount.value = PAGE_SIZE;
  },
  { deep: true },
);

const PAGE_SIZE = 20;
const visibleCount = ref(PAGE_SIZE);
const visibleFlats = computed(() =>
  filteredFlats.value.slice(0, visibleCount.value),
);
const hasMore = computed(() => visibleCount.value < filteredFlats.value.length);
const loadMore = () => {
  visibleCount.value += PAGE_SIZE;
};

onMounted(() => {
  void nextTick(() => onScrollAreaScroll());
});
</script>
<template>
  <div class="irep-flats-sidebar ire-flex ire-h-full ire-flex-col ire-bg-white">
    <div
      class="irep-flats-sidebar__header ire-z-10 ire-flex ire-h-14 ire-items-center ire-justify-between ire-gap-2 ire-bg-gray-50 ire-p-4 ire-transition-shadow"
      :class="{
        'ire-shadow-[0px_10px_50px_-10px_rgba(0,_0,_0,_0.1)]': hasScrolled,
      }"
    >
      <div class="irep-flats-sidebar__count">
        <span class="ire-text-lg ire-font-medium">
          {{ filteredFlats?.length }}
        </span>

        <span class="ire-ml-1 ire-inline-block ire-text-sm ire-capitalize">
          {{ tr("apartments") }}
        </span>
      </div>

      <Transition name="ire-fade-in-out">
        <div
          v-if="hasChanges"
          class="irep-flats-sidebar__filter-actions ire-flex ire-items-center ire-gap-2"
        >
          <Toggle
            v-if="!pathsFillOnHoverOnly"
            v-model="showOnlyFilteredOnSvg"
            :aria-label="tr('Show only filtered flats on 360')"
            :title="tr('Show only filtered flats on 360')"
          />
          <ClearFiltersButton :visible="hasChanges" @click="resetFilters" />
        </div>
      </Transition>
    </div>

    <div
      ref="scrollAreaRef"
      class="irep-flats-sidebar__body ire-min-h-0 ire-flex-1 ire-overflow-y-auto"
      @scroll.passive="onScrollAreaScroll"
    >
      <FlatsSidebarHeader
        v-model:filters-object="filtersObject"
        :hide-floor-range="isFloorsView"
      />

      <div
        v-if="filteredFlats.length === 0"
        class="irep-flats-sidebar__empty ire-flex ire-flex-col ire-items-center ire-justify-center ire-gap-2 ire-py-16 ire-text-gray-400"
      >
        <HomeIcon class="ire-size-8 ire-stroke-black" />
        <span class="ire-text-sm ire-font-medium ire-text-black">{{
          tr("no apartments found")
        }}</span>
      </div>

      <div v-else class="irep-flats-sidebar__list">
        <div
          class="irep-flats-sidebar__grid ire-grid ire-grid-cols-2 ire-gap-4 ire-p-4"
        >
          <div
            v-for="flat in visibleFlats"
            :key="flat.id"
            class="irep-flats-sidebar__item ire-group ire-relative ire-cursor-pointer ire-overflow-visible ire-rounded-xl ire-border ire-border-gray-200/80 ire-bg-white ire-p-2 ire-shadow-[0_1px_2px_rgba(16,24,40,0.04)] ire-transition-all ire-duration-300 ire-ease-out hover:-ire-translate-y-0.5 hover:ire-border-[var(--primary-color)] hover:ire-shadow-[0_12px_28px_-12px_rgba(16,24,40,0.22)]"
            @click="onFlatCardClick(flat)"
            @keydown.enter.prevent="onFlatCardClick(flat)"
            @keydown.space.prevent="onFlatCardClick(flat)"
          >
            <div
              class="irep-flats-sidebar__item-focus-btn ire-absolute ire-right-3 ire-top-3 ire-z-10 ire-flex ire-size-8 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-full ire-border ire-border-gray-200 ire-bg-white/90 ire-text-gray-600 ire-shadow-sm ire-backdrop-blur ire-transition-all hover:ire-border-[var(--primary-color)] hover:ire-bg-white hover:ire-text-[var(--primary-color)] active:ire-scale-95"
              role="button"
              aria-label="Show on 360"
              tabindex="0"
              @click.stop="focusFlatOnViewer(flat)"
              @keydown.enter.prevent.stop="focusFlatOnViewer(flat)"
              @keydown.space.prevent.stop="focusFlatOnViewer(flat)"
            >
              <EyeIcon class="ire-size-[1.15rem]" />
            </div>

            <div
              class="irep-flats-sidebar__item-badge ire-absolute ire-left-3 ire-top-3 ire-z-10 ire-w-fit"
            >
              <Badge v-if="flat.conf" :conf="flat.conf" />
            </div>

            <div
              class="irep-flats-sidebar__item-image-wrapper ire-relative ire-w-full ire-overflow-hidden ire-rounded-lg ire-border ire-border-gray-100 ire-bg-gray-50 ire-pt-[85%]"
            >
              <img
                v-if="flatTypeTeaserImageUrl(flat)"
                :src="flatTypeTeaserImageUrl(flat)"
                class="ire-absolute ire-inset-0 ire-h-full ire-w-full ire-object-cover ire-transition-transform ire-duration-[600ms] ire-ease-out group-hover:ire-scale-[1.045]"
                alt="Apartment plan"
              />
              <div
                v-else
                class="irep-flats-sidebar__item-placeholder ire-absolute ire-inset-0 ire-flex ire-items-center ire-justify-center ire-text-gray-300"
              >
                <HomeIcon class="ire-size-7" />
              </div>
            </div>

            <div
              class="irep-flats-sidebar__item-info ire-mt-2.5 ire-flex-1 ire-px-0.5 ire-text-sm"
            >
              <div
                class="irep-flats-sidebar__item-number ire-font-semibold ire-tracking-tight ire-text-gray-900 ire-transition-colors group-hover:ire-text-[var(--primary-color)]"
              >
                {{ flat.flat_number }}
              </div>

              <div
                class="irep-flats-sidebar__item-attrs ire-mt-1 ire-grid ire-grid-cols-2 ire-gap-2"
              >
                <div
                  v-if="flat?.type?.area_m2"
                  class="irep-flats-sidebar__item-area ire-group/sbArea focus-visible:ire-ring-[var(--primary-color)]/40 ire-relative ire-flex ire-items-center ire-gap-1 ire-rounded-lg ire-px-1 ire-py-0.5 ire-outline-none ire-transition-colors focus-visible:ire-ring-2"
                  tabindex="-1"
                >
                  <div
                    class="irep-flats-sidebar__tooltip ease-out ire-pointer-events-none ire-absolute ire-bottom-full ire-left-1/2 ire-z-30 ire-mb-1 ire-min-w-0 -ire-translate-x-1/2 ire-translate-y-px ire-rounded-md ire-border ire-border-gray-200/80 ire-bg-white ire-px-2 ire-py-1 ire-text-center ire-opacity-0 ire-shadow-sm ire-transition-all ire-duration-150 group-hover/sbArea:ire-translate-y-0 group-hover/sbArea:ire-opacity-100"
                    role="tooltip"
                  >
                    <div
                      class="irep-flats-sidebar__tooltip-text ire-whitespace-nowrap ire-text-xs ire-font-medium ire-text-gray-700"
                    >
                      {{ tr("area") }}
                    </div>
                  </div>
                  <Area class="ire-size-4 ire-shrink-0 ire-text-gray-600" />
                  <span class="ire-text-xs ire-font-medium">
                    {{ getArea(flat.type?.area_m2) }} {{ getAreaUnitLabel() }}²
                  </span>
                </div>

                <div
                  v-if="flat?.type?.rooms_count"
                  class="irep-flats-sidebar__item-rooms ire-group/sbRooms focus-visible:ire-ring-[var(--primary-color)]/40 ire-relative ire-flex ire-items-center ire-gap-1 ire-rounded-lg ire-px-1 ire-py-0.5 ire-outline-none ire-transition-colors focus-visible:ire-ring-2"
                  tabindex="-1"
                >
                  <div
                    class="irep-flats-sidebar__tooltip ease-out ire-pointer-events-none ire-absolute ire-bottom-full ire-left-1/2 ire-z-30 ire-mb-1 ire-min-w-0 -ire-translate-x-1/2 ire-translate-y-px ire-rounded-md ire-border ire-border-gray-200/80 ire-bg-white ire-px-2 ire-py-1 ire-text-center ire-opacity-0 ire-shadow-sm ire-transition-all ire-duration-150 group-hover/sbRooms:ire-translate-y-0 group-hover/sbRooms:ire-opacity-100"
                    role="tooltip"
                  >
                    <div
                      class="irep-flats-sidebar__tooltip-text ire-whitespace-nowrap ire-text-xs ire-font-medium ire-text-gray-700"
                    >
                      {{ tr("rooms") }}
                    </div>
                  </div>
                  <Bed class="ire-size-4 ire-shrink-0 ire-text-gray-600" />
                  <span class="ire-text-xs ire-font-medium">
                    {{ getRoomCount(flat.type?.rooms_count?.toString()) || "" }}
                  </span>
                </div>

                <div
                  v-if="flat?.floor_id"
                  class="irep-flats-sidebar__item-floor ire-group/sbFloor focus-visible:ire-ring-[var(--primary-color)]/40 ire-relative ire-flex ire-items-center ire-gap-1 ire-rounded-lg ire-px-1 ire-py-0.5 ire-outline-none ire-transition-colors focus-visible:ire-ring-2"
                  tabindex="-1"
                >
                  <div
                    class="irep-flats-sidebar__tooltip ease-out ire-pointer-events-none ire-absolute ire-bottom-full ire-left-1/2 ire-z-30 ire-mb-1 ire-min-w-0 -ire-translate-x-1/2 ire-translate-y-px ire-rounded-md ire-border ire-border-gray-200/80 ire-bg-white ire-px-2 ire-py-1 ire-text-center ire-opacity-0 ire-shadow-sm ire-transition-all ire-duration-150 group-hover/sbFloor:ire-translate-y-0 group-hover/sbFloor:ire-opacity-100"
                    role="tooltip"
                  >
                    <div
                      class="irep-flats-sidebar__tooltip-text ire-whitespace-nowrap ire-text-xs ire-font-medium ire-text-gray-700"
                    >
                      {{ tr("floor") }}
                    </div>
                  </div>
                  <Floor class="ire-size-4 ire-shrink-0 ire-text-gray-600" />
                  <span class="ire-text-xs ire-font-medium">
                    {{ flatFloorNumberLabel(flat) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="hasMore"
          class="irep-flats-sidebar__load-more-wrapper ire-flex ire-px-4 ire-pb-2"
        >
          <div
            class="irep-flats-sidebar__load-more ire-flex ire-w-full ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-md ire-border ire-border-gray-200 ire-bg-white ire-px-4 ire-py-2.5 ire-text-sm ire-font-medium ire-capitalize ire-text-gray-700 ire-transition-colors hover:ire-bg-gray-50 active:ire-bg-gray-100"
            @click="loadMore"
          >
            {{ tr("load more") }}
            <span class="ire-ml-1 ire-text-gray-400"
              >({{ filteredFlats.length - visibleCount }})</span
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Apartment card hover choreography ───────────────────────────────────────
   All effects are :hover-only and additive — at rest the card is untouched,
   so touch devices (no hover) still show every control. */

/* Soft tinted surface so the white cards lift off the background */
.irep-flats-sidebar__body {
  background: linear-gradient(180deg, #f8fafc 0%, #f3f5f8 100%);
}

.irep-flats-sidebar__item {
  will-change: transform;
}

/* Faint accent-tinted background on hover (theme-aware via the primary color) */
.irep-flats-sidebar__item:hover {
  background-color: #f4f7ff; /* fallback */
  background-color: color-mix(in srgb, var(--primary-color) 6%, #ffffff);
}

/* Depth veil rising from the bottom of the plan */
.irep-flats-sidebar__item-image-wrapper::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background: linear-gradient(
    to top,
    rgba(15, 23, 42, 0.12),
    transparent 42%
  );
  opacity: 0;
  transition: opacity 0.4s ease;
}
.irep-flats-sidebar__item:hover .irep-flats-sidebar__item-image-wrapper::before {
  opacity: 1;
}

/* Diagonal sheen that sweeps across the plan once on hover */
.irep-flats-sidebar__item-image-wrapper::after {
  content: "";
  position: absolute;
  top: -10%;
  left: 0;
  z-index: 2;
  height: 120%;
  width: 55%;
  pointer-events: none;
  opacity: 0;
  transform: translateX(-180%) skewX(-14deg);
  background: linear-gradient(
    100deg,
    transparent,
    rgba(255, 255, 255, 0.5),
    transparent
  );
}
.irep-flats-sidebar__item:hover .irep-flats-sidebar__item-image-wrapper::after {
  animation: irep-card-sheen 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes irep-card-sheen {
  0% {
    opacity: 0;
    transform: translateX(-180%) skewX(-14deg);
  }
  16% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translateX(280%) skewX(-14deg);
  }
}

/* Eye button: gentle pop + a single accent ring pulse */
.irep-flats-sidebar__item-focus-btn {
  transition:
    transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1),
    color 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease;
}
.irep-flats-sidebar__item:hover .irep-flats-sidebar__item-focus-btn {
  transform: scale(1.08);
}
.irep-flats-sidebar__item-focus-btn::after {
  content: "";
  position: absolute;
  inset: -1px;
  border-radius: 9999px;
  border: 1.5px solid var(--primary-color);
  opacity: 0;
  pointer-events: none;
}
.irep-flats-sidebar__item:hover .irep-flats-sidebar__item-focus-btn::after {
  animation: irep-eye-ping 1.1s ease-out;
}

@keyframes irep-eye-ping {
  0% {
    opacity: 0.55;
    transform: scale(1);
  }
  100% {
    opacity: 0;
    transform: scale(1.75);
  }
}

@media (prefers-reduced-motion: reduce) {
  .irep-flats-sidebar__item:hover .irep-flats-sidebar__item-image-wrapper::after,
  .irep-flats-sidebar__item:hover .irep-flats-sidebar__item-focus-btn::after {
    animation: none;
  }
  .irep-flats-sidebar__item:hover .irep-flats-sidebar__item-focus-btn {
    transform: none;
  }
}
</style>
