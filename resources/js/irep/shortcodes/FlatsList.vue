<script setup lang="ts">
/**
 * Filterable list of every unit in a project, for use inside <IrepProvider>.
 *
 * Filtering, price/area formatting and status badges come from the plugin, but
 * the layout is the site's own: the plugin's card and table markup relies on
 * `ire-` utilities that its prebuilt stylesheet does not actually ship, so they
 * fall apart outside the editor.
 */
import { computed, provide, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import Filters from "../components/demo/uiComponents/Filters.vue";
import Badge from "../components/demo/uiComponents/Badge.vue";
import PreviewModal from "../components/demo/uiComponents/PreviewModal.vue";
import FlatPreview from "../components/demo/preview/FlatPreview.vue";
import FlatPriceHistoryModal from "../components/demo/uiComponents/FlatPriceHistoryModal.vue";
import Area from "../components/icons/Area.vue";
import Bed from "../components/icons/Bed.vue";
import Floor from "../components/icons/Floor.vue";
import LineChartIcon from "../components/icons/LineChartIcon.vue";
import {
  currencySymbol,
  getArea,
  getAreaUnitLabel,
  getPrice,
  getRoomCount,
  isVideoMedia,
  mediaThumbUrl,
  normalizeFilterOptionsMeta,
  normalizeRangeOption,
  tr,
  useGetFloorById,
  withRenderableMedia,
} from "../composable/helper";
import { useGlobalStore } from "../store/useGlobal";

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { flats, shortcodeData, openReservedFlat, openSoldFlat, hasPriceHistoryAddon } =
  storeToRefs(globalStore);
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

// Statuses switched off in Manage filters are dropped from the list entirely,
// not just from the dropdown.
const hiddenStatuses = Array.isArray(filterOptions.hidden_statuses)
  ? (filterOptions.hidden_statuses as string[])
  : [];

const isHiddenStatus = (flat: any) => {
  if (!hiddenStatuses.length) return false;

  // No status means "available"; a custom status is stored by value but shown
  // by title, so match either.
  if (!flat?.conf) return hiddenStatuses.includes("available");

  const customTypes = getMetaValue("custom_types");
  const customType = Array.isArray(customTypes)
    ? customTypes.find((t: any) => t.title === flat.conf || t.value === flat.conf)
    : null;

  return hiddenStatuses.includes(customType?.value ?? flat.conf);
};

const configuredFloorOptions = filterOptions.floor_filter_options
  ? normalizeRangeOption(filterOptions.floor_filter_options, { min: 0, max: 16, step: 1 })
  : null;

const floorsMinMax = computed(() => {
  if (configuredFloorOptions) return configuredFloorOptions;

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

// A flat either points at a shared type or carries its own inline one, exactly
// as the flat modal resolves it.
const flatType = (flat: any) => {
  const useType = flat?.use_type === true || String(flat?.use_type) === "true";
  return withRenderableMedia(
    useType ? (flat?.type ?? flat?.flat_type) : (flat?.flat_type ?? flat?.type),
  );
};

// An offer price of "0.00" means "no offer", so take the first *positive*
// value rather than the first non-null one — `??` would stop at the zero.
const flatPrice = (flat: any) => {
  const candidates = [
    flat?.offer_price_n ?? flat?.offer_price,
    flat?.price_n ?? flat?.price,
  ];

  for (const candidate of candidates) {
    const value = Number(candidate);
    if (Number.isFinite(value) && value > 0) return value;
  }

  return 0;
};
const flatArea = (flat: any) => {
  const type = flatType(flat);
  return Number(type?.area_m2_n ?? type?.area_m2);
};
const flatRooms = (flat: any) =>
  parseFloat(String(flatType(flat)?.rooms_count ?? "").replace(",", "."));
const flatFloorNumber = (flat: any) => getFloorById(flat?.floor_id)?.floor_number ?? "";

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
      const area = flatArea(flat);
      const floor = Number(flatFloorNumber(flat));
      const rooms = flatRooms(flat);

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

      return (
        !isHiddenStatus(flat) &&
        priceMatch &&
        areaMatch &&
        floorMatch &&
        roomMatch &&
        configMatch
      );
    })
    .map((flat: any) => {
      const customType = Array.isArray(customTypes)
        ? customTypes.find((t: any) => t.value === flat.conf)
        : null;

      return customType ? { ...flat, conf: customType.title } : flat;
    });
});

/* ── Card media ────────────────────────────────────────────────────────── */
// Cards lead with the plan the project prefers, then fall back to the other.
const flatThumb = (flat: any) => {
  const type = flatType(flat) ?? {};
  const media =
    getMetaValue("flat_list_default_plan") === "2d"
      ? [...(type.image_2d ?? []), ...(type.image_3d ?? [])]
      : [...(type.image_3d ?? []), ...(type.image_2d ?? [])];

  for (const item of media) {
    const url = mediaThumbUrl(item);
    if (url) return { type: "image" as const, url };
  }

  const first = media[0];
  if (first && isVideoMedia(first)) return { type: "video" as const, url: first.url };

  return null;
};

const priceLabel = (flat: any) => {
  if (flat?.request_price) return tr("request price");

  // Same resolution as the filters use: `price_n` is a WordPress-era field and
  // is null here, so the plain `price` has to be the fallback.
  const price = flatPrice(flat);
  if (!Number.isFinite(price) || price <= 0) return "";

  return `${getPrice(price)} ${currencySymbol()}`;
};

// "Request price" is a call to action rather than a value, so it takes the
// project's primary colour like it does in the flat modal.
const isRequestPrice = (flat: any) => Boolean(flat?.request_price);

// A discounted unit shows its old price struck through above the new one, and
// the new one in the project's primary colour — same as the flat modal.
const hasOfferPrice = (flat: any) => {
  const offer = Number(flat?.offer_price_n ?? flat?.offer_price);
  const original = Number(flat?.price_n ?? flat?.price);

  return Number.isFinite(offer) && offer > 0 && Number.isFinite(original) && original > 0;
};

const originalPriceLabel = (flat: any) => {
  const original = Number(flat?.price_n ?? flat?.price);

  return `${getPrice(original)} ${currencySymbol()}`;
};

const isHighlightedPrice = (flat: any) => isRequestPrice(flat) || hasOfferPrice(flat);

// The chart next to a price, on both the cards and the table. Two recorded
// points are the minimum that draws a line, which is the rule the flat modal
// uses too; units showing a status badge or "request price" have no figure to
// chart, so they get no button either.
const priceHistoryFlat = ref<any>(null);

const hasPriceHistory = (flat: any) =>
  hasPriceHistoryAddon.value &&
  (flat?.price_history?.length ?? 0) >= 2 &&
  !flat?.conf &&
  !isRequestPrice(flat);

const openPriceHistory = (flat: any) => {
  priceHistoryFlat.value = flat;
};

const areaLabel = (flat: any) => {
  const type = flatType(flat);
  if (!type?.area_m2) return "";

  return `${getArea(type.area_m2_n ?? type.area_m2)} ${getAreaUnitLabel()}`;
};

const roomsLabel = (flat: any) => {
  const type = flatType(flat);
  if (!type?.rooms_count) return "";

  return `${getRoomCount(type.rooms_count)} ${tr("room")}`;
};

/* ── View mode, sorting, paging ────────────────────────────────────────── */
const view = ref<"grid" | "list">("grid");

type SortField = "flat_number" | "area" | "rooms" | "floor" | "price";
const sort = ref<{ field: SortField; order: "asc" | "desc" }>({
  field: "flat_number",
  order: "asc",
});

const columns: { field: SortField; label: string; align?: string }[] = [
  { field: "flat_number", label: "unit" },
  { field: "area", label: "area" },
  { field: "rooms", label: "rooms" },
  { field: "floor", label: "floor" },
  { field: "price", label: "price", align: "text-right" },
];

const sortValue = (flat: any, field: SortField) => {
  switch (field) {
    case "area":
      return flatArea(flat);
    case "rooms":
      return flatRooms(flat);
    case "floor":
      return Number(flatFloorNumber(flat));
    case "price":
      return flatPrice(flat);
    default:
      return String(flat?.flat_number ?? "");
  }
};

const sortedFlats = computed(() => {
  const direction = sort.value.order === "asc" ? 1 : -1;

  return [...filteredFlats.value].sort((a: any, b: any) => {
    const left = sortValue(a, sort.value.field);
    const right = sortValue(b, sort.value.field);

    if (typeof left === "number" && typeof right === "number") {
      const leftValue = Number.isFinite(left) ? left : Number.NEGATIVE_INFINITY;
      const rightValue = Number.isFinite(right) ? right : Number.NEGATIVE_INFINITY;
      return (leftValue - rightValue) * direction;
    }

    return String(left).localeCompare(String(right), undefined, { numeric: true }) * direction;
  });
});

const toggleSort = (field: SortField) => {
  sort.value =
    sort.value.field === field
      ? { field, order: sort.value.order === "asc" ? "desc" : "asc" }
      : { field, order: "asc" };
};

const PAGE_SIZE = 12;
const visibleCount = ref(PAGE_SIZE);
const visibleFlats = computed(() => sortedFlats.value.slice(0, visibleCount.value));
const remaining = computed(() => Math.max(0, sortedFlats.value.length - visibleCount.value));

watch([filtersObject, view], () => (visibleCount.value = PAGE_SIZE), { deep: true });

/* ── Unit modal ───────────────────────────────────────────────────────── */
const activeFlat = ref<any>(null);
const showFlatModal = ref(false);

/**
 * Whether a unit's modal may be opened at all — the same rules the viewer
 * applies to a polygon click: reserved and sold units can be closed off, and a
 * custom status can opt out of the modal entirely.
 */
const canOpenFlat = (flat: any) => {
  if (flat?.conf === "reserved" && !openReservedFlat.value) return false;
  if (flat?.conf === "sold" && !openSoldFlat.value) return false;

  const customTypes = getMetaValue("custom_types");
  const customType = Array.isArray(customTypes)
    ? customTypes.find((t: any) => t.title === flat?.conf || t.value === flat?.conf)
    : null;

  return customType ? Boolean(customType.open_flat_modal) : true;
};

const openFlat = (flat: any) => {
  if (!canOpenFlat(flat)) return;

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
  <div class="irep-flats-list">
    <!-- Header: count + view switch -->
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-black/10 pb-5">
      <p class="text-[11px] uppercase tracking-[0.2em] text-black/45">
        <span class="text-black">{{ filteredFlats.length }}</span>
        / {{ flats?.length ?? 0 }} units
      </p>

      <div class="flex items-center border border-black/10">
        <button
          v-for="mode in (['grid', 'list'] as const)"
          :key="mode"
          type="button"
          class="px-5 py-2 text-[11px] uppercase tracking-[0.2em] transition-colors duration-300"
          :class="view === mode ? 'bg-black text-white' : 'text-black/45 hover:text-black'"
          @click="view = mode"
        >
          {{ mode }}
        </button>
      </div>
    </div>

    <!-- Filters (plugin component: ranges + status select) -->
    <div class="irep-flats-list__filters border-b border-black/10 py-6">
      <Filters v-model:filters-object="filtersObject" />
    </div>

    <div
      v-if="!filteredFlats.length"
      class="py-20 text-center text-sm font-light text-black/45"
    >
      No units match these filters.
    </div>

    <!-- Grid -->
    <div
      v-else-if="view === 'grid'"
      class="mt-10 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3"
    >
      <article
        v-for="flat in visibleFlats"
        :key="flat.id"
        class="group"
        :class="canOpenFlat(flat) ? 'cursor-pointer' : 'cursor-default'"
        @click="openFlat(flat)"
      >
        <!-- Floor plans are irregular shapes, so they are fitted whole inside the
             frame rather than cropped to fill it. -->
        <div class="relative overflow-hidden bg-[#f4f1ec] p-4" style="aspect-ratio: 4 / 3;">
          <img
            v-if="flatThumb(flat)?.type === 'image'"
            :src="flatThumb(flat)!.url"
            :alt="flat.flat_number"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-contain transition-transform duration-[1200ms] ease-out group-hover:scale-[1.04]"
          />
          <video
            v-else-if="flatThumb(flat)?.type === 'video'"
            :src="flatThumb(flat)!.url"
            muted
            loop
            playsinline
            preload="metadata"
            class="h-full w-full object-contain"
          />

        </div>

        <div class="mt-4 flex items-baseline justify-between gap-3">
          <h3 class="display text-lg leading-snug text-black" style="font-weight: 400;">
            {{ flat.flat_number }}
          </h3>

          <!-- A reserved or sold unit shows its status where the price would
               be: the price no longer means anything to a buyer. -->
          <Badge v-if="flat.conf" :conf="flat.conf" />
          <span v-else class="shrink-0 text-right leading-tight">
            <span
              v-if="hasOfferPrice(flat)"
              class="block text-sm tabular-nums text-black/35 line-through"
            >
              {{ originalPriceLabel(flat) }}
            </span>
            <!-- Prices carry the project's primary colour, as they do in the
                 flat modal; a discounted one is emphasised further. -->
            <span
              class="flex items-center justify-end gap-1.5 text-lg md:text-xl tabular-nums"
              :class="isHighlightedPrice(flat) ? 'font-medium' : ''"
              style="color: var(--primary-color);"
            >
              <span :class="isRequestPrice(flat) ? 'capitalize' : ''">{{ priceLabel(flat) }}</span>
              <button
                v-if="hasPriceHistory(flat)"
                type="button"
                class="shrink-0 opacity-55 transition-opacity duration-300 hover:opacity-100"
                :title="tr('price history')"
                :aria-label="tr('price history')"
                @click.stop="openPriceHistory(flat)"
              >
                <LineChartIcon class="size-[18px] [&_g]:fill-current" />
              </button>
            </span>
          </span>
        </div>

        <div class="mt-2 flex items-center gap-5 text-sm font-light text-black/45">
          <span v-if="areaLabel(flat)" class="flex items-center gap-1.5">
            <Area class="size-5 opacity-50" />
            {{ areaLabel(flat) }}<sup class="text-[10px]">2</sup>
          </span>
          <span v-if="roomsLabel(flat)" class="flex items-center gap-1.5">
            <Bed class="size-4 opacity-50" />
            {{ roomsLabel(flat) }}
          </span>
          <span v-if="flatFloorNumber(flat)" class="flex items-center gap-1.5">
            <Floor class="size-4 opacity-50" />
            {{ flatFloorNumber(flat) }}
          </span>
        </div>
      </article>
    </div>

    <!-- List -->
    <div v-else class="mt-10 overflow-x-auto">
      <table class="w-full min-w-[640px] border-collapse text-left">
        <thead>
          <tr class="border-b border-black/10">
            <th
              v-for="column in columns"
              :key="column.field"
              scope="col"
              class="pb-3 text-[11px] uppercase tracking-[0.2em] text-black/45"
              :class="column.align"
            >
              <button
                type="button"
                class="inline-flex items-center gap-1.5 transition-colors hover:text-black"
                :class="sort.field === column.field ? 'text-black' : ''"
                @click="toggleSort(column.field)"
              >
                {{ column.label }}
                <span v-if="sort.field === column.field" class="text-[9px]">
                  {{ sort.order === "asc" ? "▲" : "▼" }}
                </span>
              </button>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="flat in visibleFlats"
            :key="flat.id"
            class="border-b border-black/10 transition-colors duration-300"
            :class="
              canOpenFlat(flat)
                ? 'cursor-pointer hover:bg-black/[0.02]'
                : 'cursor-default'
            "
            @click="openFlat(flat)"
          >
            <td class="py-4">
              <span class="display text-base text-black" style="font-weight: 400;">
                {{ flat.flat_number }}
              </span>
            </td>
            <td class="py-4 text-sm font-light tabular-nums text-black/60">
              <template v-if="areaLabel(flat)">
                {{ areaLabel(flat) }}<sup class="text-[10px]">2</sup>
              </template>
              <template v-else>—</template>
            </td>
            <td class="py-4 text-sm font-light tabular-nums text-black/60">
              {{ roomsLabel(flat) || "—" }}
            </td>
            <td class="py-4 text-sm font-light tabular-nums text-black/60">
              {{ flatFloorNumber(flat) || "—" }}
            </td>
            <td class="py-4 text-right leading-tight">
              <!-- Status takes the price column, as it does on the cards. -->
              <Badge v-if="flat.conf" :conf="flat.conf" />
              <template v-else>
                <span
                  v-if="hasOfferPrice(flat)"
                  class="block text-xs tabular-nums text-black/35 line-through"
                >
                  {{ originalPriceLabel(flat) }}
                </span>
                <span
                  class="flex items-center justify-end gap-1.5 text-lg tabular-nums"
                  :class="isHighlightedPrice(flat) ? 'font-medium capitalize' : ''"
                  style="color: var(--primary-color);"
                >
                  {{ priceLabel(flat) || "—" }}
                  <button
                    v-if="hasPriceHistory(flat)"
                    type="button"
                    class="shrink-0 opacity-55 transition-opacity duration-300 hover:opacity-100"
                    :title="tr('price history')"
                    :aria-label="tr('price history')"
                    @click.stop="openPriceHistory(flat)"
                  >
                    <LineChartIcon class="size-[18px] [&_g]:fill-current" />
                  </button>
                </span>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="remaining" class="mt-12 flex justify-center">
      <button
        type="button"
        class="border border-black px-7 py-3 text-[11px] uppercase tracking-[0.25em] transition-colors duration-300 hover:bg-black hover:text-white"
        @click="visibleCount += PAGE_SIZE"
      >
        <!-- Dimmed by opacity rather than a fixed colour, so the count stays
             visible once the button inverts on hover. -->
        Show more <span class="opacity-50">({{ remaining }})</span>
      </button>
    </div>

    <teleport to="body">
      <Transition name="ire-fade-in-out" appear>
        <PreviewModal v-if="showFlatModal && activeFlat" @close="closeFlat">
          <FlatPreview :flat="activeFlat" :floors="floors" />
        </PreviewModal>
      </Transition>
    </teleport>

    <teleport to="body">
      <Transition name="ire-fade-in-out" mode="out-in">
        <FlatPriceHistoryModal
          v-if="priceHistoryFlat"
          :price-history="priceHistoryFlat.price_history"
          @close="priceHistoryFlat = null"
        />
      </Transition>
    </teleport>
  </div>
</template>

<style scoped>
.tabular-nums {
  font-variant-numeric: tabular-nums;
}

/* The plugin's filter row is built for its own dense admin layout; give it the
   page's rhythm and typography without touching the component itself. */
.irep-flats-list__filters :deep(.irep-flats-list-filters) {
  gap: 2.5rem 2rem;
}
.irep-flats-list__filters :deep(label),
.irep-flats-list__filters :deep(.irep-flats-list-filters > div > p) {
  letter-spacing: 0.2em;
  text-transform: uppercase;
  font-size: 11px;
  color: rgba(0, 0, 0, 0.45);
}
</style>
