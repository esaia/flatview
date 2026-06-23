<script setup lang="ts">
import type {
  ActionItem,
  BlockItem,
  FlatItem,
  FloorItem,
} from "../../../types/DemoTypes";
import { computed, inject, onMounted, onUnmounted, ref, watch } from "vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { useGetConfValue, tr } from "../../../composable/helper";
import { storeToRefs } from "pinia";
import ChevronUp from "../../../components/icons/ChevronUp.vue";
import ChevronDown from "../../../components/icons/ChevronDown.vue";

const props = defineProps<{
  flats: FlatItem[];
  floors: FloorItem[];
  blocks: BlockItem[];
  actions: ActionItem[] | undefined;
  svgFlatFilterActive?: boolean;
  svgVisibleFlatIds?: ReadonlySet<string>;
  focusFlatId?: string | null;
  focusFloorId?: string | null;
  pathsVisible?: boolean;
}>();

const showFlatModal = inject<any>("showFlatModal");
const activateFlat = inject<(flat: any) => void>("activateFlat");
const setFloorHoverData =
  inject<(data: any, type: string) => void>("setFloorHoverData");
const setFloors360Selection = inject<
  (floorId: string | null, blockId: string | null) => void
>("setFloors360Selection");
const globalStore = useGlobalStore();
const getConfValue = useGetConfValue();
const { openReservedFlat, openSoldFlat } = storeToRefs(globalStore);

const selectedBlockId = ref<string | null>(
  props.blocks.length > 0 ? String(props.blocks[0].id) : null,
);
const selectedFloor = ref<FloorItem | null>(null);
const svgRef = ref<HTMLDivElement | null>(null);
const floorListRef = ref<HTMLDivElement | null>(null);
const hoveredSvg = ref<HTMLElement | null>(null);
const activePolygon = ref<any>(null);
const activeFlat = ref<FlatItem | ActionItem | null>(null);

const isDraggingList = ref(false);
const dragStartY = ref(0);
const dragStartScrollTop = ref(0);
let listPointerId: number | null = null;
let lastFocusGroup: SVGGElement | null = null;

const hasUnblockedFloors = computed(() =>
  props.floors.some((f) => !f.block_id),
);

const floorsForBlock = computed(() =>
  props.floors
    .filter((f) =>
      selectedBlockId.value != null
        ? String(f.block_id) === String(selectedBlockId.value)
        : !f.block_id,
    )
    .sort((a, b) => a.floor_number - b.floor_number),
);

const floorSvg = computed(() => selectedFloor.value?.svg ?? null);

const floorRasterImage = computed(
  () => selectedFloor.value?.floor_image?.[0] ?? null,
);

const floorRasterIntrinsic = computed(() => {
  const img = floorRasterImage.value;
  if (!img) return null;
  const w = Number(img.width);
  const h = Number(img.height);
  if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0)
    return null;
  return { width: Math.round(w), height: Math.round(h) };
});

const setPathAttributes = () => {
  if (!svgRef.value || !selectedFloor.value) return;

  const filterActive = props.svgFlatFilterActive ?? false;
  const visibleIds = props.svgVisibleFlatIds;
  const gTags = svgRef.value.querySelectorAll("g");

  gTags.forEach((g: SVGGElement) => {
    const gId = g.getAttribute("id");
    const findedPolygon = selectedFloor.value?.polygon_data?.find(
      (polygon) => polygon.key === gId,
    );

    if (!props.flats) return;
    let conf = "";

    if (selectedFloor.value?.conf) {
      conf = getConfValue(selectedFloor.value.conf);
      g.setAttribute("conf", conf);
    } else {
      const flat = props.flats.find((f) => String(f.id) === String(findedPolygon?.id));
      conf = getConfValue(flat?.conf ?? "");
      g.setAttribute("conf", conf);
    }

    if (findedPolygon?.type) {
      g.setAttribute("polygon-type", findedPolygon.type);
    }

    g.style.transition = "opacity 220ms ease";
    if (findedPolygon?.type === "flat" && findedPolygon.id) {
      const hidden =
        filterActive &&
        visibleIds != null &&
        !visibleIds.has(String(findedPolygon.id));
      g.style.opacity = hidden ? "0" : "";
      g.style.pointerEvents = hidden ? "none" : "";
    } else {
      // not connected to a flat — hide when filter is active
      g.style.opacity = filterActive ? "0" : "";
      g.style.pointerEvents = filterActive ? "none" : "";
    }
  });
};

const onSvgMouseOver = (e: MouseEvent) => {
  const target = e.target as HTMLElement;
  hoveredSvg.value = target;

  // Set globalStore.hoverdSvg directly here (not via watcher) so it always
  // fires AFTER useProject360's listener clears it on the same event cycle.
  // MouseTracker reads this value on the next mousemove to position the tooltip.
  if (target?.nodeName === "path") {
    globalStore.hoverdSvg = target;
  }
};

const onPathClick = (e: MouseEvent) => {
  const target = e.target as SVGPathElement;
  if (target?.nodeName !== "path") return;

  if (activeFlat.value && "conf" in activeFlat.value) {
    if (activeFlat.value.conf === "reserved" && !openReservedFlat.value) return;
    if (activeFlat.value.conf === "sold" && !openSoldFlat.value) return;
  }

  if (activePolygon.value?.type === "flat" && activeFlat.value) {
    activateFlat?.(activeFlat.value);
  }
};

const scrollFloorList = (dir: -1 | 1) => {
  if (!floorListRef.value) return;
  floorListRef.value.scrollTop += dir * 80;
};

const DRAG_THRESHOLD = 4;

const detachListPointerListeners = () => {
  window.removeEventListener("pointermove", onListPointerMove);
  window.removeEventListener("pointerup", onListPointerUp);
  window.removeEventListener("pointercancel", onListPointerUp);
};

const onListPointerDown = (e: PointerEvent) => {
  if (!floorListRef.value) return;
  dragStartY.value = e.clientY;
  dragStartScrollTop.value = floorListRef.value.scrollTop;
  listPointerId = e.pointerId;
  window.addEventListener("pointermove", onListPointerMove);
  window.addEventListener("pointerup", onListPointerUp);
  window.addEventListener("pointercancel", onListPointerUp);
};

const onListPointerMove = (e: PointerEvent) => {
  if (e.pointerId !== listPointerId || !floorListRef.value) return;
  const delta = e.clientY - dragStartY.value;
  if (!isDraggingList.value && Math.abs(delta) < DRAG_THRESHOLD) return;
  isDraggingList.value = true;
  floorListRef.value.scrollTop = dragStartScrollTop.value - delta;
};

const onListPointerUp = (e: PointerEvent) => {
  if (e.pointerId !== listPointerId) return;
  isDraggingList.value = false;
  listPointerId = null;
  detachListPointerListeners();
};

const selectBlock = (blockId: string | number | null) => {
  const currentFloorNumber = selectedFloor.value?.floor_number;
  selectedBlockId.value = blockId != null ? String(blockId) : null;
  const newFloors = props.floors
    .filter((f) => (blockId != null ? String(f.block_id) === String(blockId) : !f.block_id))
    .sort((a, b) => a.floor_number - b.floor_number);
  const match =
    currentFloorNumber != null
      ? newFloors.find((f) => f.floor_number === currentFloorNumber)
      : null;
  selectedFloor.value = match ?? newFloors[0] ?? null;
};

watch(
  () => hoveredSvg.value,
  (ns) => {
    if (!ns) return;

    const activeG = ns.parentElement;
    if (activeG && activeG.nodeName === "g") {
      const id = activeG.getAttribute("id");
      if (!id) return;

      activePolygon.value =
        selectedFloor.value?.polygon_data?.find((item) => item.key === id) ??
        null;
      if (!activePolygon.value) return;

      if (activePolygon.value.type === "flat") {
        const found = props.flats.find(
          (flat) => String(flat.id) === String(activePolygon.value.id),
        );
        activeFlat.value = found
          ? {
              ...found,
              conf: found.conf || selectedFloor.value?.conf || "",
            }
          : null;
      } else if (activePolygon.value.type === "tooltip") {
        activeFlat.value =
          props.actions?.find(
            (action) => String(action.id) === String(activePolygon.value.id),
          ) ?? null;
      } else {
        activeFlat.value = null;
      }
      setFloorHoverData?.(activeFlat.value, activePolygon.value?.type ?? "");
    } else {
      activePolygon.value = null;
      activeFlat.value = null;
      setFloorHoverData?.(null, "");
    }
  },
);

watch(
  () => selectedFloor.value,
  () => {
    if (lastFocusGroup) {
      lastFocusGroup.classList.remove("ire-flat-focus");
      lastFocusGroup = null;
    }
    setTimeout(() => setPathAttributes(), 0);
    setFloors360Selection?.(
      selectedFloor.value?.id ?? null,
      selectedBlockId.value,
    );
  },
);

watch(
  () => [props.svgFlatFilterActive, props.svgVisibleFlatIds] as const,
  () => {
    setTimeout(() => setPathAttributes(), 0);
  },
);

watch(
  () => props.focusFlatId,
  (flatId) => {
    if (!flatId) return;
    const flat = props.flats.find((f) => String(f.id) === String(flatId));
    if (!flat) return;
    const targetFloor = props.floors.find(
      (f) => String(f.id) === String(flat.floor_id),
    );
    if (!targetFloor) return;
    const targetBlockId = targetFloor.block_id
      ? String(targetFloor.block_id)
      : null;
    if (targetBlockId !== selectedBlockId.value) {
      selectBlock(targetBlockId);
    }
    selectedFloor.value = targetFloor;

    setTimeout(() => {
      if (!svgRef.value) return;
      const polygon = targetFloor.polygon_data?.find(
        (p) => p.type === "flat" && String(p.id) === String(flatId),
      );
      if (!polygon) return;
      const escaped =
        typeof CSS !== "undefined" && "escape" in CSS
          ? CSS.escape(polygon.key)
          : polygon.key;
      const g = svgRef.value.querySelector(
        `g#${escaped}`,
      ) as SVGGElement | null;
      if (!g) return;
      if (lastFocusGroup) lastFocusGroup.classList.remove("ire-flat-focus");
      g.classList.add("ire-flat-focus");
      lastFocusGroup = g;
    }, 50);
  },
);

watch(
  () => selectedBlockId.value,
  () => {
    setFloors360Selection?.(
      selectedFloor.value?.id ?? null,
      selectedBlockId.value,
    );
  },
);

watch(
  () => props.focusFloorId,
  (floorId) => {
    if (!floorId) return;
    const targetFloor = props.floors.find((f) => String(f.id) === String(floorId));
    if (!targetFloor) return;
    const targetBlockId = targetFloor.block_id ? String(targetFloor.block_id) : null;
    if (targetBlockId !== selectedBlockId.value) {
      selectBlock(targetBlockId);
    } else {
      selectedFloor.value = targetFloor;
    }
  },
  { immediate: true },
);

watch(
  () => showFlatModal?.value,
  () => {
    if (!showFlatModal?.value) {
      hoveredSvg.value = null;
      activePolygon.value = null;
    }
  },
);

onMounted(() => {
  if (!selectedFloor.value) {
    selectedFloor.value = floorsForBlock.value[0] ?? null;
  }
  setFloors360Selection?.(
    selectedFloor.value?.id ?? null,
    selectedBlockId.value,
  );

  document.addEventListener("mousemove", onSvgMouseOver);
});

onUnmounted(() => {
  document.removeEventListener("mousemove", onSvgMouseOver);
  detachListPointerListeners();
  if (lastFocusGroup) lastFocusGroup.classList.remove("ire-flat-focus");
});
</script>

<template>
  <div
    class="irep-floors-360 ire-relative ire-h-full ire-w-full ire-overflow-hidden ire-bg-white"
  >
    <!-- Block selector tabs -->
    <div
      v-if="blocks.length > 0"
      class="irep-floors-360__block-tabs ire-absolute ire-left-2 ire-top-2 ire-z-10 ire-flex ire-flex-wrap ire-gap-1"
    >
      <div
        v-for="block in blocks"
        :key="block.id"
        class="irep-floors-360__block-tab ire-cursor-pointer ire-rounded ire-px-3 ire-py-1 ire-text-sm ire-font-medium ire-shadow ire-transition-colors"
        :class="
          String(selectedBlockId) === String(block.id)
            ? 'ire-bg-black ire-text-white'
            : 'ire-bg-white ire-text-black hover:ire-bg-gray-100'
        "
        @click="selectBlock(block.id)"
      >
        {{ block.title }}
      </div>
      <div
        v-if="hasUnblockedFloors"
        class="irep-floors-360__block-tab ire-cursor-pointer ire-rounded ire-px-3 ire-py-1 ire-text-sm ire-font-medium ire-shadow ire-transition-colors"
        :class="
          selectedBlockId === null
            ? 'ire-bg-black ire-text-white'
            : 'ire-bg-white ire-text-black hover:ire-bg-gray-100'
        "
        @click="selectBlock(null)"
      >
        {{ tr("no block") }}
      </div>
    </div>

    <!-- Floor plan image + SVG overlay -->
    <div
      v-if="selectedFloor"
      class="irep-floors-360__canvas ire-relative ire-h-full ire-w-full ire-select-none ire-overflow-hidden"
    >
      <img
        v-if="floorRasterImage?.url"
        :src="floorRasterImage.url"
        :alt="floorRasterImage.alt || ''"
        :width="floorRasterIntrinsic?.width"
        :height="floorRasterIntrinsic?.height"
        class="ire-block ire-h-full ire-w-full ire-object-contain"
        decoding="async"
      />

      <div
        ref="svgRef"
        v-html="floorSvg"
        :key="floorSvg ?? ''"
        class="irep-floors-360__svg-overlay canvas path-color ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full"
        :style="{
          opacity: pathsVisible === false ? 0 : 1,
          transition: 'opacity 160ms ease',
          pointerEvents: pathsVisible === false ? 'none' : 'auto',
        }"
        @click="onPathClick"
      />
    </div>

    <div
      v-else
      class="irep-floors-360__empty ire-flex ire-h-full ire-w-full ire-items-center ire-justify-center ire-text-gray-400"
    >
      {{ tr("no floors available") }}
    </div>

    <!-- Vertical floor number picker -->
    <div
      v-if="floorsForBlock.length > 0"
      class="irep-floors-360__floor-picker ire-absolute ire-left-3 ire-top-1/2 ire-z-10 ire-flex -ire-translate-y-1/2 ire-flex-col ire-items-center ire-gap-1 ire-rounded-md ire-bg-white ire-p-1"
    >
      <!-- Arrow up -->
      <div
        class="irep-floors-360__arrow-up ire-flex ire-h-7 ire-w-7 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-full ire-bg-white ire-shadow ire-transition-colors hover:ire-bg-gray-100"
        @click="scrollFloorList(-1)"
      >
        <ChevronUp />
      </div>

      <!-- Scrollable floor number list -->
      <div
        ref="floorListRef"
        class="irep-floors-360__floor-list ire-flex ire-max-h-[30vh] ire-flex-col ire-items-center ire-gap-1 ire-overflow-y-auto ire-overscroll-contain ire-rounded-lg ire-py-1"
        style="scrollbar-width: none; -ms-overflow-style: none"
        :class="isDraggingList ? 'ire-cursor-grabbing' : 'ire-cursor-grab'"
        @pointerdown="onListPointerDown"
      >
        <div
          v-for="floor in floorsForBlock"
          :key="floor.id"
          class="irep-floors-360__floor-item ire-flex ire-h-9 ire-w-9 ire-shrink-0 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-full ire-text-sm ire-font-semibold ire-shadow ire-transition-colors"
          :class="
            selectedFloor?.id === floor.id
              ? 'ire-bg-black ire-text-white'
              : 'ire-bg-white ire-text-black hover:ire-bg-gray-100'
          "
          @click="selectedFloor = floor"
        >
          {{ floor.floor_number }}
        </div>
      </div>

      <!-- Arrow down -->
      <div
        class="irep-floors-360__arrow-down ire-flex ire-h-7 ire-w-7 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-full ire-bg-white ire-shadow ire-transition-colors hover:ire-bg-gray-100"
        @click="scrollFloorList(1)"
      >
        <ChevronDown />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* hide scrollbar cross-browser */
div::-webkit-scrollbar {
  display: none;
}
</style>
