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

/* ── Additional views ───────────────────────────────────────────────────────
 * View 1 is the project's own image and polygons; views 2..N come from the
 * project's `views` array. Each view can carry a separate image for screens
 * narrower than the project's mobile breakpoint.
 */
const mobileBreakpoint = computed(() => {
  const raw = Number(globalStore.getMetaValue("mobile_breakpoint"));
  return Number.isFinite(raw) && raw > 0 ? raw : 768;
});

const isMobileViewport = ref(false);
let breakpointQuery: MediaQueryList | null = null;
const onBreakpointChange = (event: MediaQueryListEvent | MediaQueryList) => {
  isMobileViewport.value = event.matches;
};

const bindBreakpointQuery = () => {
  breakpointQuery?.removeEventListener("change", onBreakpointChange);
  breakpointQuery = window.matchMedia(
    `(max-width: ${mobileBreakpoint.value}px)`,
  );
  breakpointQuery.addEventListener("change", onBreakpointChange);
  onBreakpointChange(breakpointQuery);
};

const views = computed(() => {
  const project = props.project as any;
  if (!project) return [];

  const first = {
    label: project.view_label || "View 1",
    image: project.project_image?.[0] ?? null,
    mobile_image: project.mobile_image?.[0] ?? null,
    svg: project.svg ?? "",
    polygon_data: project.polygon_data ?? [],
    mobile_svg: project.mobile_svg ?? "",
    mobile_polygon_data: project.mobile_polygon_data ?? [],
  };

  const extra = (Array.isArray(project.views) ? project.views : []).map(
    (view: any, index: number) => ({
      label: view?.label || `View ${index + 2}`,
      image: view?.image ?? null,
      mobile_image: view?.mobile_image ?? null,
      svg: view?.svg ?? "",
      polygon_data: view?.polygon_data ?? [],
      mobile_svg: view?.mobile_svg ?? "",
      mobile_polygon_data: view?.mobile_polygon_data ?? [],
    }),
  );

  return [first, ...extra.filter((view: any) => view.image?.url)];
});

const activeViewIndex = ref(0);
const activeView = computed(
  () => views.value[activeViewIndex.value] ?? views.value[0],
);

// A mobile image is usually a different crop, so it brings its own SVG and
// polygons; an empty mobile SVG falls back to the desktop drawing.
const showsMobileView = computed(
  () => isMobileViewport.value && Boolean(activeView.value?.mobile_image?.url),
);

const projectSvg = computed(() => {
  const view = activeView.value;
  if (!view) return "";

  return showsMobileView.value && view.mobile_svg ? view.mobile_svg : view.svg;
});

const activePolygons = computed(() => {
  const view = activeView.value;
  if (!view) return [];

  return showsMobileView.value && view.mobile_svg
    ? view.mobile_polygon_data ?? []
    : view.polygon_data ?? [];
});

const projectRasterImage = computed(() => {
  const view = activeView.value;
  if (!view) return null;

  return (showsMobileView.value ? view.mobile_image : view.image) ?? null;
});

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

    const findedPolygon = activePolygons.value?.find(
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
        activePolygons.value?.find((item) => item?.key === id) || null;
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

watch([projectSvg, activeViewIndex], async () => {
  await nextTick();
  setPathAttributes();
});

// A project whose views changed (or shrank) must not stay on a missing index.
watch(views, (list) => {
  if (activeViewIndex.value >= list.length) activeViewIndex.value = 0;
});

watch(mobileBreakpoint, bindBreakpointQuery);

onMounted(() => {
  document.addEventListener("mousemove", onSvgMouseOver);
  bindBreakpointQuery();
  setPathAttributes();
});

onUnmounted(() => {
  document.removeEventListener("mousemove", onSvgMouseOver);
  breakpointQuery?.removeEventListener("change", onBreakpointChange);
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

      <!-- View switcher — only when the project has more than one view -->
      <div
        v-if="views.length > 1"
        class="irep-project-preview__views ire-absolute ire-bottom-4 ire-left-1/2 ire-z-10 -ire-translate-x-1/2 ire-flex ire-items-center ire-gap-1 ire-rounded-full ire-bg-white/90 ire-p-1 ire-shadow-lg ire-backdrop-blur-sm"
      >
        <button
          v-for="(view, index) in views"
          :key="index"
          type="button"
          class="irep-project-preview__view-tab ire-cursor-pointer ire-rounded-full ire-px-4 ire-py-1.5 ire-text-sm ire-transition-colors"
          :class="
            index === activeViewIndex
              ? 'ire-bg-white ire-text-black ire-shadow-sm ire-font-medium'
              : 'ire-text-gray-500 hover:ire-text-black'
          "
          @click="activeViewIndex = index"
        >
          {{ view.label }}
        </button>
      </div>
    </div>
  </PreviewLayout>
</template>
