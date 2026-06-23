<script setup lang="ts">
import { computed, inject, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import PreviewLayout from "../layout/PreviewLayout.vue";
import BackButton from "../uiComponents/BackButton.vue";
import type {
  ActionItem,
  BlockItem,
  FlatItem,
  FloorItem,
} from "../../../types/DemoTypes";
import { useGetConfValue } from "../../../composable/helper";
import { useGlobalStore } from "../../../store/useGlobal";
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
  block: BlockItem;
  flats: FlatItem[];
  floors: FloorItem[];
  actions: ActionItem[] | undefined;
}>();

const showFlatModal = inject<any>("showFlatModal");
const globalStore = useGlobalStore();
const getConfValue = useGetConfValue();
const { openReservedFlat, openSoldFlat } = storeToRefs(globalStore);

const { containerRef: pinchContainerRef, contentStyle: pinchContentStyle } = usePinchZoom();

const svgRef = ref();
const hoveredSvg = ref<HTMLElement | null>(null);
const activePolygon = ref();
const activeFlatOrFloor = ref();

const blockSvg = computed(() => {
  if (!props.block?.svg) return;

  return props.block?.svg;
});

const blockRasterImage = computed(() => props.block?.block_image?.[0] ?? null);

const blockRasterIntrinsic = computed(() => {
  const img = blockRasterImage.value;
  if (!img) return null;
  const w = Number(img.width);
  const h = Number(img.height);
  if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) {
    return null;
  }
  return { width: Math.round(w), height: Math.round(h) };
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

  if (activePolygon.value?.type === "flat") {
    if (activeFlatOrFloor.value?.conf === "reserved" && !openReservedFlat.value) return;
    if (activeFlatOrFloor.value?.conf === "sold" && !openSoldFlat.value) return;
  }

  emits(
    "changeComponent",
    activePolygon.value?.type || "",
    activeFlatOrFloor.value,
  );
};

const setPathAttributes = () => {
  if (!svgRef.value) return;

  const gTags = svgRef.value?.querySelectorAll("g");

  gTags.forEach((g: SVGGElement) => {
    const gId = g?.getAttribute("id");

    const findedPolygon = props.block?.polygon_data?.find(
      (polygon) => polygon?.key === gId,
    );

    const polygonId = findedPolygon?.id;
    let conf = "";

    switch (findedPolygon?.type) {
      case "floor": {
        const floor = props.floors?.find((floor) => String(floor.id) === String(polygonId));
        conf = getConfValue(floor?.conf || "");
        break;
      }
      case "flat": {
        const flat = props.flats?.find((flat) => String(flat.id) === String(polygonId));
        conf = getConfValue(flat?.conf || "");
        break;
      }
      default:
        break;
    }

    g.setAttribute("conf", conf || "");

    if (findedPolygon?.type) {
      g.setAttribute("polygon-type", findedPolygon?.type);
    }
  });
};

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
  () => hoveredSvg.value,
  (ns) => {
    if (!ns) return;

    globalStore.hoverdSvg = ns;

    const activeG = ns?.parentElement;

    if (activeG && activeG?.nodeName === "g") {
      const id = activeG?.getAttribute("id");
      if (!id) return;

      activePolygon.value =
        props.block?.polygon_data?.find((item) => item?.key === id) || null;
      if (!activePolygon.value) return;

      if (activePolygon.value?.type === "floor") {
        const activeFindedfloor = props.floors?.find(
          (floor) => String(floor?.id) === String(activePolygon.value?.id),
        );
        activeFlatOrFloor.value = activeFindedfloor;
      } else if (activePolygon.value?.type === "flat") {
        const activeFindedflat = props.flats?.find(
          (flat) => String(flat?.id) === String(activePolygon.value?.id),
        );
        activeFlatOrFloor.value = activeFindedflat;
      } else if (activePolygon.value?.type === "tooltip") {
        const activeFindedflat = props.actions?.find(
          (action) => String(action?.id) === String(activePolygon.value?.id),
        );
        activeFlatOrFloor.value = activeFindedflat;
      } else {
        activeFlatOrFloor.value = null;
      }
    } else {
      activePolygon.value = null;
      activeFlatOrFloor.value = null;
    }
  },
);

watch(blockSvg, async () => {
  await nextTick();
  setPathAttributes();
});

onMounted(() => {
  setPathAttributes();
  document.addEventListener("mousemove", onSvgMouseOver);
});

onUnmounted(() => {
  document.removeEventListener("mousemove", onSvgMouseOver);
});
</script>

<template>
  <PreviewLayout :hoverdData="activeFlatOrFloor" :type="activePolygon?.type">
    <template #header>
      <BackButton @click="$emit('changeComponent', 'project', null)" />
      <div class="irep-block-preview__title block-title lg:!ire-text-xl">{{ block?.title }}</div>
    </template>

    <div ref="pinchContainerRef" class="irep-block-preview__canvas ire-relative ire-w-full ire-select-none ire-overflow-hidden">
      <div :style="pinchContentStyle" class="ire-relative">
        <img
          v-if="blockRasterImage?.url"
          :src="blockRasterImage.url"
          :alt="blockRasterImage.alt || ''"
          :width="blockRasterIntrinsic?.width"
          :height="blockRasterIntrinsic?.height"
          class="ire-block ire-h-auto ire-w-full ire-max-w-full"
          decoding="async"
        />

        <div
          ref="svgRef"
          v-html="blockSvg"
          :key="blockSvg"
          class="irep-block-preview__svg-overlay canvas path-color ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full"
          @click="onPathClick"
        ></div>
      </div>
    </div>
  </PreviewLayout>
</template>
