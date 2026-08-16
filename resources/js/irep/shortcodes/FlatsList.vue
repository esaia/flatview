<script setup lang="ts">
/**
 * Filterable list of every unit in a project, for use inside <IrepProvider>.
 *
 * Reuses the plugin's own Filters / FlatCard / FlatsTable pieces so the
 * filtering, badges and price formatting behave exactly like they do inside the
 * viewer; only the section chrome around them belongs to the site.
 */
import { computed, provide, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import Filters from "../components/demo/uiComponents/Filters.vue";
import FlatCard from "../components/demo/uiComponents/FlatCard.vue";
import FlatsTable from "../components/demo/uiComponents/FlatsTable.vue";
import PreviewModal from "../components/demo/uiComponents/PreviewModal.vue";
import FlatPreview from "../components/demo/preview/FlatPreview.vue";
import GridIcon from "../components/icons/GridIcon.vue";
import TableIcon from "../components/icons/TableIcon.vue";
import {
  getNested,
  normalizeFilterOptionsMeta,
  normalizeRangeOption,
  tr,
  useGetFloorById,
} from "../composable/helper";
import { useGlobalStore } from "../store/useGlobal";

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { flats, shortcodeData } = storeToRefs(globalStore);
const getFloorById = useGetFloorById();

const filterOptions = normalizeFilterOptionsMeta(getMetaValue("filter_options"));
const priceOptions = normalizeRangeOption(filterOptions.price_filter_options, {
  min: 0,
  max: 1000000,
  step: 1000,
});
const areaOptions = normalizeRangeOption(filterOptions.area_filter_options, {
  min: 0,
  max: 300,
  step: 10,
});
const roomOptions = normalizeRangeOption(filterOptions.rooms_filter_options, {
  min: 0,
  max: 10,
  step: 1,
});

const floorsMinMax = computed(() => {
  const floorNumbers = (shortcodeData.value?.floors ?? [])
    .map((floor: any) => Number(floor.floor_number))
    .filter((value: number) => Number.isFinite(value));

  if (!floorNumbers.length) return { min: 0, max: 16 };

  return { min: Math.min(...floorNumbers), max: Math.max(...floorNumbers) };
});

const filtersObject = ref({
  priceRange: [priceOptions.min, priceOptions.max] as [number, number],
  areaRange: [areaOptions.min, areaOptions.max] as [number, number],
  floorRange: [floorsMinMax.value.min, floorsMinMax.value.max] as [number, number],
  roomRange: [roomOptions.min, roomOptions.max] as [number, number],
  config: "all",
});

// A range that still sits at its extremes is treated as "not filtering", so
// units with a missing price/area/room value are not hidden by default.
const isUntouched = (range: [number, number], bounds: { min: number; max: number }) =>
  range[0] === bounds.min && range[1] === bounds.max;

const flatPrice = (flat: any) =>
  Number(flat?.offer_price ?? flat?.price_n ?? flat?.price);

const filteredFlats = computed(() => {
  const list = flats.value ?? [];
  if (!list.length) return [];

  const [pMin, pMax] = filtersObject.value.priceRange;
  const [aMin, aMax] = filtersObject.value.areaRange;
  const [fMin, fMax] = filtersObject.value.floorRange;
  const [rMin, rMax] = filtersObject.value.roomRange;
  const configFilter = filtersObject.value.config ?? "all";

  const priceTouched = !isUntouched(filtersObject.value.priceRange, priceOptions);
  const areaTouched = !isUntouched(filtersObject.value.areaRange, areaOptions);
  const roomTouched = !isUntouched(filtersObject.value.roomRange, roomOptions);
  const floorTouched = !isUntouched(filtersObject.value.floorRange, floorsMinMax.value);

  const customTypes = getMetaValue("custom_types");

  return list
    .filter((flat: any) => {
      const price = flatPrice(flat);
      const area = Number(flat.type?.area_m2_n ?? flat.type?.area_m2);
      const floor = Number(getFloorById(flat.floor_id)?.floor_number);
      const rooms = parseFloat(String(flat.type?.rooms_count ?? "0").replace(",", "."));

      const priceMatch =
        !priceTouched || (Number.isFinite(price) && price >= pMin && price <= pMax);
      const areaMatch =
        !areaTouched || (Number.isFinite(area) && area >= aMin && area <= aMax);
      const floorMatch =
        !floorTouched || (Number.isFinite(floor) && floor >= fMin && floor <= fMax);
      const roomMatch =
        !roomTouched || (Number.isFinite(rooms) && rooms >= rMin && rooms <= rMax);

      let configMatch = true;
      if (configFilter !== "all") {
        if (configFilter === "available") {
          configMatch = !flat.conf;
        } else {
          const customType = Array.isArray(customTypes)
            ? customTypes.find((t: any) => t.value === configFilter)
            : null;
          configMatch = customType
            ? flat.conf === customType.title
            : flat.conf === configFilter;
        }
      }

      return priceMatch && areaMatch && floorMatch && roomMatch && configMatch;
    })
    .map((flat: any) => {
      const customType = Array.isArray(customTypes)
        ? customTypes.find((t: any) => t.value === flat.conf)
        : null;

      return customType ? { ...flat, conf: customType.title } : flat;
    });
});

/* ── View mode, sorting, paging ────────────────────────────────────────── */
const view = ref<"grid" | "list">("grid");
const sort = ref<{ field: string; order: "" | "asc" | "desc" }>({ field: "", order: "" });

const sortedFlats = computed(() => {
  const list = [...filteredFlats.value];
  if (!sort.value.field || !sort.value.order) return list;

  const direction = sort.value.order === "asc" ? 1 : -1;

  return list.sort((a: any, b: any) => {
    const left = getNested(a, sort.value.field);
    const right = getNested(b, sort.value.field);
    const leftNum = Number(left);
    const rightNum = Number(right);

    if (Number.isFinite(leftNum) && Number.isFinite(rightNum)) {
      return (leftNum - rightNum) * direction;
    }

    return String(left ?? "").localeCompare(String(right ?? "")) * direction;
  });
});

const PAGE_SIZE = 12;
const visibleCount = ref(PAGE_SIZE);
const visibleFlats = computed(() => sortedFlats.value.slice(0, visibleCount.value));
const hasMore = computed(() => visibleCount.value < sortedFlats.value.length);

watch([filtersObject, view], () => (visibleCount.value = PAGE_SIZE), { deep: true });

/* ── Unit modal ───────────────────────────────────────────────────────── */
const activeFlat = ref<any>(null);
const showFlatModal = ref(false);

const openFlat = (flatId: string) => {
  const flat = sortedFlats.value.find((item: any) => String(item.id) === String(flatId));
  if (!flat) return;
  activeFlat.value = flat;
  showFlatModal.value = true;
};

const closeFlat = () => {
  showFlatModal.value = false;
  activeFlat.value = null;
};

// Opening a unit from the list must not rewrite the page URL the way the
// in-viewer flow does — the list is not a shareable viewer state.
provide("fromListView", true);
provide("showFlatModal", showFlatModal);

const floors = computed(() => shortcodeData.value?.floors);
</script>

<template>
  <div class="irep-flats-list ire-text-base">
    <div class="ire-mb-8 ire-flex ire-flex-wrap ire-items-center ire-justify-between ire-gap-4">
      <p class="ire-text-sm ire-uppercase ire-tracking-[0.2em] ire-text-black/45">
        {{ filteredFlats.length }} / {{ flats?.length ?? 0 }} {{ tr("units") }}
      </p>

      <div class="ire-flex ire-items-center ire-gap-0 ire-border ire-border-black/10">
        <button
          type="button"
          class="ire-flex ire-items-center ire-gap-2 ire-px-4 ire-py-2 ire-text-xs ire-uppercase ire-tracking-[0.15em] ire-transition-colors"
          :class="view === 'grid' ? 'ire-bg-black ire-text-white' : 'ire-text-black/50 hover:ire-text-black'"
          @click="view = 'grid'"
        >
          <GridIcon class="ire-size-4" /> {{ tr("grid") }}
        </button>
        <button
          type="button"
          class="ire-flex ire-items-center ire-gap-2 ire-px-4 ire-py-2 ire-text-xs ire-uppercase ire-tracking-[0.15em] ire-transition-colors"
          :class="view === 'list' ? 'ire-bg-black ire-text-white' : 'ire-text-black/50 hover:ire-text-black'"
          @click="view = 'list'"
        >
          <TableIcon class="ire-size-4" /> {{ tr("list") }}
        </button>
      </div>
    </div>

    <Filters v-model:filters-object="filtersObject" class="ire-mb-10" />

    <div
      v-if="!filteredFlats.length"
      class="ire-border ire-border-black/10 ire-py-16 ire-text-center ire-text-sm ire-text-black/45"
    >
      {{ tr("no results") }}
    </div>

    <template v-else>
      <div
        v-if="view === 'grid'"
        class="ire-grid ire-grid-cols-1 ire-gap-x-6 ire-gap-y-10 sm:ire-grid-cols-2 lg:ire-grid-cols-3"
      >
        <FlatCard
          v-for="flat in visibleFlats"
          :key="flat.id"
          :flat="flat"
          @open-flat="openFlat"
        />
      </div>

      <FlatsTable
        v-else
        :flats="visibleFlats"
        @open-flat="openFlat"
        @sort-column="(field, order) => (sort = { field, order })"
      />

      <div v-if="hasMore" class="ire-mt-12 ire-flex ire-justify-center">
        <button
          type="button"
          class="ire-border ire-border-black ire-px-7 ire-py-3 ire-text-xs ire-uppercase ire-tracking-[0.25em] ire-transition-colors hover:ire-bg-black hover:ire-text-white"
          @click="visibleCount += PAGE_SIZE"
        >
          {{ tr("show more") }}
          <span class="ire-text-black/40">({{ sortedFlats.length - visibleCount }})</span>
        </button>
      </div>
    </template>

    <teleport to="body">
      <Transition name="ire-fade-in-out" appear>
        <PreviewModal v-if="showFlatModal && activeFlat" @close="closeFlat">
          <FlatPreview :flat="activeFlat" :floors="floors" />
        </PreviewModal>
      </Transition>
    </teleport>
  </div>
</template>
