<script setup lang="ts">
import type {
  ActionItem,
  BlockItem,
  FlatItem,
  FloorItem,
  PolygonDataCollection,
  ProjectInterface,
  ProjectMeta,
} from "../../../types/DemoTypes";

import { computed, inject, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import PreviewLayout from "../layout/PreviewLayout.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { useGetConfValue } from "../../../composable/helper";
import { usePinchZoom } from "../../../composable/usePinchZoom";

const emits = defineEmits<{
  (
    e: "changeComponent",
    flowComponent: "flat" | "floor" | "block" | "tooltip" | "",
    hoveredData: any,
  ): void;
}>();

const props = defineProps<{
  project: ProjectInterface | undefined;
  floors: FloorItem[] | undefined;
  blocks: BlockItem[] | undefined;
  flats: FlatItem[] | undefined;
  actions: ActionItem[] | undefined;
  projectMeta: ProjectMeta[] | undefined;
}>();

const showFlatModal = inject<any>("showFlatModal");
const globalStore = useGlobalStore();
const getConfValue = useGetConfValue();
const { openReservedFlat, openSoldFlat } = storeToRefs(globalStore);

const { containerRef: pinchContainerRef, contentStyle: pinchContentStyle } = usePinchZoom();

const svgRef = ref();
const hoveredSvg = ref<HTMLElement | null>(null);
const hoveredData = ref();
const activePolygon = ref<PolygonDataCollection | null>(null);

const projectSvg = computed(() => {
  if (!props.project) return;

  return props.project.svg;
});

const projectRasterImage = computed(
  () => props.project?.project_image?.[0] ?? null,
);

const projectRasterIntrinsic = computed(() => {
  const img = projectRasterImage.value;
  if (!img) return null;
  const w = Number(img.width);
  const h = Number(img.height);
  if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) {
    return null;
  }
  return { width: Math.round(w), height: Math.round(h) };
});

const onSvgMouseOver = (e: any) => {
  const target: HTMLElement | null = e?.target;
  if (target) {
    hoveredSvg.value = target;
  }
};

const setPathAttributes = () => {
  if (!svgRef.value) return;

  const gTags = svgRef.value?.querySelectorAll("g");

  gTags.forEach((g: SVGGElement) => {
    const gId = g?.getAttribute("id");

    const findedPolygon = props.project?.polygon_data?.find(
      (polygon) => polygon?.key === gId,
    );

    const polygonId = findedPolygon?.id;
    let conf = "";

    switch (findedPolygon?.type) {
      case "block": {
        const block = props.blocks?.find((block) => String(block.id) === String(polygonId));
        conf = getConfValue(block?.conf || "");
        break;
      }
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

const onPathClick = (e: any) => {
  const target = e.target as SVGElement;
  if (!["path", "circle"].includes(target?.nodeName)) return;

  if (activePolygon.value?.type === "flat") {
    if (hoveredData.value?.conf === "reserved" && !openReservedFlat.value) return;
    if (hoveredData.value?.conf === "sold" && !openSoldFlat.value) return;
  }

  emits("changeComponent", activePolygon.value?.type || "", hoveredData?.value);
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

    const activeG = ns.parentElement;

    if (activeG && activeG?.nodeName === "g") {
      const id = activeG?.getAttribute("id");
      if (!id) return;

      activePolygon.value =
        props.project?.polygon_data?.find((item) => item?.key === id) || null;
      if (!activePolygon.value) return;
      const polygonId = activePolygon.value?.id;

      switch (activePolygon.value?.type) {
        case "floor":
          const activeFloor = props.floors?.find(
            (floor) => String(floor.id) === String(polygonId),
          );
          hoveredData.value = activeFloor;
          break;
        case "block":
          const activeBlock = props.blocks?.find(
            (block) => String(block?.id) === String(polygonId),
          );
          hoveredData.value = activeBlock;

          break;

        case "flat":
          const activeFlat = props.flats?.find(
            (flat) => String(flat?.id) === String(polygonId),
          );
          hoveredData.value = activeFlat;

          break;

        case "tooltip":
          const activeAction = props.actions?.find(
            (action) => String(action?.id) === String(polygonId),
          );

          hoveredData.value = activeAction;
          break;

        default:
          hoveredData.value = null;
          break;
      }
    } else {
      activePolygon.value = null;
      hoveredData.value = null;
    }
  },
);

watch(projectSvg, async () => {
  await nextTick();
  setPathAttributes();
});

onMounted(() => {
  document.addEventListener("mousemove", onSvgMouseOver);
  setPathAttributes();
});

onUnmounted(() => {
  document.removeEventListener("mousemove", onSvgMouseOver);
});
</script>

<template>
  <PreviewLayout :hoverdData="hoveredData" :type="activePolygon?.type">
    <div ref="pinchContainerRef" class="irep-project-preview__canvas ire-relative ire-w-full ire-select-none ire-overflow-hidden">
      <div :style="pinchContentStyle" class="ire-relative">
        <img
          v-if="projectRasterImage?.url"
          :src="projectRasterImage.url"
          :alt="projectRasterImage.alt || ''"
          :width="projectRasterIntrinsic?.width"
          :height="projectRasterIntrinsic?.height"
          class="ire-block ire-h-auto ire-w-full ire-max-w-full"
          decoding="async"
        />

        <div
          v-html="projectSvg"
          :key="projectSvg"
          ref="svgRef"
          class="irep-project-preview__svg-overlay canvas path-color ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full"
          @click="onPathClick"
        ></div>
      </div>
    </div>
  </PreviewLayout>
</template>
