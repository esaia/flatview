<script setup lang="ts">
import type {
  ActionItem,
  BlockItem,
  FlatItem,
  FloorItem,
} from "../../../types/DemoTypes";

import { computed, inject, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import PreviewLayout from "../layout/PreviewLayout.vue";
import PreviewSelect from "../form/PreviewSelect.vue";
import BackButton from "../uiComponents/BackButton.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { useGetConfValue, tr } from "../../../composable/helper";
import { storeToRefs } from "pinia";
import { usePinchZoom } from "../../../composable/usePinchZoom";

const emits = defineEmits<{
  (
    e: "changeComponent",
    flow: "" | "flat" | "floor" | "block" | "project",
    hoveredData: any,
  ): void;
}>();

const props = defineProps<{
  flats: FlatItem[] | undefined;
  floor: FloorItem;
  floors: FloorItem[];
  blocks?: BlockItem[];
  actions: ActionItem[] | undefined;
}>();

const showFlatModal = inject<any>("showFlatModal");
const globalStore = useGlobalStore();
const getConfValue = useGetConfValue();
const { openReservedFlat, openSoldFlat } = storeToRefs(globalStore);

const { containerRef: pinchContainerRef, contentStyle: pinchContentStyle } =
  usePinchZoom();

const svgRef = ref();
const hoveredSvg = ref<HTMLElement | null>(null);
const activePolygon = ref();
const activeFlat = ref<FlatItem | ActionItem | null>(null);
const selectedFloor = ref();
const floorBlock = ref<BlockItem>();

const floorSvg = computed(() => {
  if (!props.floor?.svg) return;

  return props.floor.svg;
});

/** First floor raster image (under SVG overlay). */
const floorRasterImage = computed(() => props.floor?.floor_image?.[0] ?? null);

/**
 * Intrinsic pixel size from API — lets the browser reserve aspect ratio before
 * the image loads (avoids layout shift without a fixed min-height).
 */
const floorRasterIntrinsic = computed(() => {
  const img = floorRasterImage.value;
  if (!img) return null;
  const w = Number(img.width);
  const h = Number(img.height);
  if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) {
    return null;
  }
  return { width: Math.round(w), height: Math.round(h) };
});

const floorsSelect = computed(() => {
  return (
    props.floors
      .filter((floorItem) =>
        // floorItem.conf !== "reserved" &&
        // floorItem.conf !== "sold" &&
        props.floor?.block_id
          ? floorItem?.block_id === props.floor?.block_id
          : !floorItem?.block_id,
      )
      .sort((a, b) => a.floor_number - b.floor_number)
      .map((floor) => {
        const block = props.blocks?.find(
          (block) => block?.id === floor?.block_id?.toString(),
        );

        return {
          title:
            floor?.floor_number?.toString() +
            ` ${tr("floor")} ` +
            (block?.id ? ` - ${block?.title}` : "") +
            (floor?.conf ? " " + floor.conf : ""),
          value: floor?.id,
        };
      }) || []
  );
});

const onSvgMouseOver = (e: any) => {
  const target: HTMLElement | null = e.target;

  if (target) {
    hoveredSvg.value = target;
  }
};

const onPathClick = (e: any) => {
  const target = e.target as SVGElement;
  if (!["path", "circle"].includes(target?.nodeName)) return;

  if (activeFlat.value && "conf" in activeFlat.value) {
    if (activeFlat.value?.conf === "reserved" && !openReservedFlat.value)
      return;
    if (activeFlat.value?.conf === "sold" && !openSoldFlat.value) return;
  }

  emits("changeComponent", activePolygon.value?.type || "", activeFlat.value);
};

const setPathAttributes = () => {
  if (svgRef.value) {
    const gTags = svgRef.value?.querySelectorAll("g");

    gTags.forEach((g: SVGGElement) => {
      const gId = g?.getAttribute("id");

      const findedPolygon = props.floor?.polygon_data?.find(
        (polygon) => polygon?.key === gId,
      );

      if (!props.flats) return;
      let conf = "";

      if (props.floor?.conf) {
        conf = getConfValue(props.floor?.conf || "");
        g.setAttribute("conf", conf || "");
      } else {
        const activeFlat = props.flats?.find(
          (flat) => String(flat?.id) === String(findedPolygon?.id),
        );

        conf = getConfValue(activeFlat?.conf || "");
        g?.setAttribute("conf", conf || "");
      }

      if (findedPolygon?.type) {
        g.setAttribute("polygon-type", findedPolygon?.type);
      }
    });
  }
};

const goBack = () => {
  if (props.floor?.block_id) {
    emits("changeComponent", "block", floorBlock.value);
  } else {
    emits("changeComponent", "project", null);
  }
};

watch(
  () => hoveredSvg.value,
  (ns) => {
    if (!ns) return;

    globalStore.hoverdSvg = ns;

    const activeG = ns?.parentElement;

    if (activeG && activeG?.nodeName === "g") {
      const id = activeG.getAttribute("id");
      if (!id) return;
      activePolygon.value =
        props.floor?.polygon_data?.find((item) => item?.key === id) || null;
      if (!activePolygon.value) return;

      if (activePolygon.value?.type === "flat") {
        const activeFindedflat = props.flats?.find(
          (flat) => String(flat?.id) === String(activePolygon.value?.id),
        );

        const perparedFlat: any = activeFindedflat
          ? {
              ...activeFindedflat,
              conf: activeFindedflat.conf || props.floor?.conf || "",
            }
          : null;

        activeFlat.value = perparedFlat;
      } else if (activePolygon.value?.type === "tooltip") {
        const activeFindedAction = props.actions?.find(
          (action) => String(action?.id) === String(activePolygon.value?.id),
        );
        activeFlat.value = activeFindedAction ?? null;
      } else {
        activeFlat.value = null;
      }
    } else {
      activePolygon.value = null;
    }
  },
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

watch(
  () => selectedFloor.value,
  () => {
    const chosenFloor = props.floors?.find(
      (floor) => floor?.id === selectedFloor?.value,
    );

    if (chosenFloor) {
      emits("changeComponent", "floor", chosenFloor);
    }

    setTimeout(() => {
      setPathAttributes();
    }, 0);
  },
);

watch(floorSvg, async () => {
  await nextTick();
  setPathAttributes();
});

onMounted(() => {
  floorBlock.value = props.blocks?.find(
    (block) => String(block?.id) === String(props.floor?.block_id),
  );
  selectedFloor.value = floorsSelect.value?.find(
    (floorSelect) => String(floorSelect?.value) === String(props.floor?.id),
  )?.value;

  setPathAttributes();
  document.addEventListener("mousemove", onSvgMouseOver);
});

onUnmounted(() => {
  document.removeEventListener("mousemove", onSvgMouseOver);
});
</script>

<template>
  <PreviewLayout :hoverdData="activeFlat" :type="activePolygon?.type">
    <template #header>
      <BackButton @click="goBack" />

      <div
        class="irep-floor-preview__select-wrapper ire-w-fit ire-max-w-[150px] ire-bg-white md:ire-max-w-[unset]"
      >
        <PreviewSelect v-model="selectedFloor" :data="floorsSelect" />
      </div>
    </template>

    <div
      ref="pinchContainerRef"
      class="irep-floor-preview__canvas ire-relative ire-w-full ire-select-none ire-overflow-hidden"
    >
      <div :style="pinchContentStyle" class="ire-relative">
        <img
          v-if="floorRasterImage?.url"
          :src="floorRasterImage.url"
          :alt="floorRasterImage.alt || ''"
          :width="floorRasterIntrinsic?.width"
          :height="floorRasterIntrinsic?.height"
          class="ire-block ire-h-auto ire-w-full ire-max-w-full"
          decoding="async"
        />

        <div
          ref="svgRef"
          v-html="floorSvg"
          :key="floorSvg"
          class="irep-floor-preview__svg-overlay canvas path-color ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full"
          @click="onPathClick"
        ></div>
      </div>
    </div>
  </PreviewLayout>
</template>
