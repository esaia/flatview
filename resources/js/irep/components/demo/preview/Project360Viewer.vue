<script setup lang="ts">
import type {
    PolygonDataCollection,
    ProjectInterface,
} from "../../../types/DemoTypes";
import { useProject360 } from "../../../composable/useProject360";
import { usePinchZoom } from "../../../composable/usePinchZoom";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import {
    computed,
    onMounted,
    provide,
    ref,
    shallowRef,
    toRef,
    watch,
} from "vue";
import MouseTracker from "../layout/MouseTracker.vue";
import PreviewLayout from "../layout/PreviewLayout.vue";
import FlatPreview from "./FlatPreview.vue";
import NavigationArrows from "./NavigationArrows.vue";
import PreviewModal from "../uiComponents/PreviewModal.vue";
import FlatsSidebar from "../uiComponents/FlatsSidebar/FlatsSidebar.vue";
import ArrowRight from "../../../components/icons/ArrowRight.vue";
import EyeIcon from "../../../components/icons/EyeIcon.vue";
import EyeOffIcon from "../../../components/icons/EyeOffIcon.vue";
import Floors360 from "./Floors360.vue";
import { tr } from "../../../composable/helper";

const props = withDefaults(
    defineProps<{
        project: ProjectInterface;
        sensitivity?: number;
        snapDurationMs?: number;
        /** When true, SVG polygon fills are transparent until the parent g is hovered. */
        pathsFillOnHoverOnly?: boolean;
    }>(),
    {
        sensitivity: 100,
        snapDurationMs: 300,
        pathsFillOnHoverOnly: false,
    },
);

const emit = defineEmits<{
    (
        e: "changeComponent",
        type: PolygonDataCollection["type"],
        polygon: PolygonDataCollection | null,
    ): void;
}>();

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { shortcodeData, flats, openReservedFlat, openSoldFlat } =
    storeToRefs(globalStore);

const activeView = ref<"360" | "floors">("360");
const pathsVisible = ref(true);

const cursor360 = `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='36' viewBox='0 0 120 36'%3E%3Ctext x='60' y='26' text-anchor='middle' font-family='Arial,sans-serif' font-size='22' font-weight='700' fill='white'%3E%E2%80%B9 360%C2%B0 %E2%80%BA%3C/text%3E%3C/svg%3E") 60 18, auto`;

const nearToggle = ref(false);
const nearNav = ref(false);
const nearUi = computed(() => nearToggle.value || nearNav.value);
const uiToggleRef = ref<HTMLElement | null>(null);

function distanceToRect(rect: DOMRect, x: number, y: number): number {
    const cx = Math.max(rect.left, Math.min(x, rect.right));
    const cy = Math.max(rect.top, Math.min(y, rect.bottom));
    return Math.sqrt((x - cx) ** 2 + (y - cy) ** 2);
}

function onContainerMouseMove(e: MouseEvent) {
    nearToggle.value = uiToggleRef.value
        ? distanceToRect(
              uiToggleRef.value.getBoundingClientRect(),
              e.clientX,
              e.clientY,
          ) <= 28
        : false;
}

function onContainerMouseLeave() {
    nearToggle.value = false;
    nearNav.value = false;
}

const floorHoveredData = ref<any>(null);
const floorHoveredType = ref<string>("");
const floors360SelectedFloorId = ref<string | null>(null);
const floors360SelectedBlockId = ref<string | null>(null);

provide("setFloorHoverData", (data: any, type: string) => {
    floorHoveredData.value = data;
    floorHoveredType.value = type;
});

provide(
    "setFloors360Selection",
    (floorId: string | null, blockId: string | null) => {
        floors360SelectedFloorId.value = floorId;
        floors360SelectedBlockId.value = blockId;
    },
);

const showOnlyFilteredOnSvg = ref(false);
const filteredFlatIds = shallowRef<ReadonlySet<string>>(new Set());

const onFilteredFlatIdsUpdate = (ids: ReadonlySet<string>) => {
    filteredFlatIds.value = ids;
};

const showFlatModal = ref(false);
const modalData = ref<any>(null);
provide("showFlatModal", showFlatModal);

const activateFlat = (flatItem: any | null) => {
    if (!flatItem) return;

    if (flatItem?.click_action === "follow_link") {
        const { link, target } = flatItem.follow_link ?? {};
        if (link) window.open(link, target ? "_blank" : "_self");
    } else {
        if (flatItem?.conf === "reserved" && !openReservedFlat.value) return;
        if (flatItem?.conf === "sold" && !openSoldFlat.value) return;

        const customTypes = getMetaValue("custom_types");
        const customType = customTypes?.find(
            (t: any) => t.title === flatItem?.conf,
        );
        if (customType && !customType?.open_flat_modal) return;

        modalData.value = flatItem;
        showFlatModal.value = true;
    }
};

const floors = computed(() => shortcodeData.value?.floors ?? []);
const blocks = computed(() => shortcodeData.value?.blocks ?? []);
const actions = computed(() => shortcodeData.value?.actions);

const containerRef = ref<HTMLDivElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const svgRef = ref<HTMLDivElement | null>(null);
const isSidebarOpen = ref(false);
const isNavigating = ref(false);

const frames = computed(() => props.project["360images"] ?? []);

const frameCircumference = 2 * Math.PI * 12;
const frameStrokeDashoffset = computed(() =>
    total.value > 1
        ? frameCircumference * (1 - renderFrame.value / (total.value - 1))
        : frameCircumference,
);

const {
    total,
    renderFrame,
    isDragging,
    isAnimating,
    hoveredData,
    activePolygonType,
    currentSvg,
    onPointerDown,
    onTapClick,
    snapToNearestWithPolygons,
    focusFlatOnViewer,
} = useProject360({
    frames,
    sensitivity: toRef(props, "sensitivity"),
    snapDurationMs: toRef(props, "snapDurationMs"),
    containerRef,
    canvasRef,
    svgRef,
    svgFlatFilterActive: showOnlyFilteredOnSvg,
    svgVisibleFlatIds: filteredFlatIds,
    onPolygonClick(polygon, type) {
        if (type === "flat") {
            activateFlat(hoveredData.value);
        } else if (type === "floor") {
            floors360FocusFloorId.value = null;
            activeView.value = "floors";
            floors360FocusFloorId.value = String(polygon.id);
        } else if (type === "block") {
            emit(
                "changeComponent",
                type as PolygonDataCollection["type"],
                polygon,
            );
        }
    },
});

const { containerRef: pinchContainerRef, contentStyle: pinchContentStyle } =
    usePinchZoom();

watch(isAnimating, (animating) => {
    if (!animating) isNavigating.value = false;
});

const handleNav = (dir: -1 | 1) => {
    isNavigating.value = true;
    snapToNearestWithPolygons(dir);
};

onMounted(() => {
    isSidebarOpen.value = false;

    const params = new URLSearchParams(window.location.search);
    const flatId = params.get("flatId");
    const projectId = params.get("projectId");

    if (!flatId) return;
    if (projectId && String(props.project.id) !== projectId) return;

    const tryOpen = () => {
        const flat = flats.value?.find((f: any) => String(f.id) === flatId);
        if (flat) {
            activateFlat(flat);
            return true;
        }
        return false;
    };

    if (!tryOpen()) {
        const stop = watch(flats, () => {
            if (tryOpen()) stop();
        });
    }
});

const floors360FocusFlatId = ref<string | null>(null);
const floors360FocusFloorId = ref<string | null>(null);

provide("activateFlat", activateFlat);
provide("focusFlatOnViewer", (flat: { id?: string } | null) => {
    if (flat?.id == null || flat.id === "") return;
    if (activeView.value === "floors") {
        floors360FocusFlatId.value = String(flat.id);
    } else {
        focusFlatOnViewer(String(flat.id));
    }
});
</script>

<template>
    <div
        class="irep-project-360-viewer building-360-viewer ire-relative ire-overflow-hidden"
    >
        <Transition name="ire-fade-in-out">
            <div
                v-if="isSidebarOpen"
                class="irep-project-360-viewer__backdrop ire-absolute ire-inset-0 ire-z-20 ire-bg-black/40 md:ire-hidden"
                @click="isSidebarOpen = false"
            />
        </Transition>

        <!-- Mobile toggle -->
        <div
            class="irep-project-360-viewer__mobile-toggle ire-absolute ire-right-0 ire-top-1/2 ire-z-40 -ire-translate-y-1/2 ire-cursor-pointer ire-rounded-l-md ire-rounded-r-none ire-bg-white/95 ire-px-1 ire-py-3 ire-shadow-md md:ire-hidden"
            role="button"
            tabindex="0"
            :aria-label="isSidebarOpen ? 'Hide sidebar' : 'Show sidebar'"
            @click="isSidebarOpen = !isSidebarOpen"
        >
            <ArrowRight :class="!isSidebarOpen && 'ire-rotate-180'" />
        </div>

        <!-- Desktop toggle — tracks the sidebar edge via right transition -->
        <div
            class="irep-project-360-viewer__desktop-toggle-wrapper ire-absolute ire-top-1/2 ire-z-[5] ire-hidden -ire-translate-y-1/2 ire-transition-all ire-duration-300 md:ire-block"
            :class="isSidebarOpen ? 'ire-right-[450px]' : 'ire-right-0'"
        >
            <div
                class="irep-project-360-viewer__desktop-toggle ire-cursor-pointer ire-rounded-l-md ire-rounded-r-none ire-bg-white/95 ire-px-1 ire-py-3 ire-shadow-md"
                role="button"
                tabindex="0"
                :aria-label="isSidebarOpen ? 'Hide sidebar' : 'Show sidebar'"
                @click="isSidebarOpen = !isSidebarOpen"
            >
                <ArrowRight :class="!isSidebarOpen && 'ire-rotate-180'" />
            </div>
        </div>

        <MouseTracker class="interactive-real-estate ire-h-full ire-text-base">
            <div
                class="irep-project-360-viewer__layout ire-flex ire-h-full ire-items-stretch"
            >
                <PreviewLayout
                    class="ire-min-w-0 ire-flex-1"
                    :hoverdData="
                        activeView === 'floors' ? floorHoveredData : hoveredData
                    "
                    :type="
                        activeView === 'floors'
                            ? floorHoveredType
                            : activePolygonType
                    "
                >
                    <div
                        v-if="total > 0"
                        class="irep-project-360-viewer__content ire-relative ire-h-full ire-w-full"
                    >
                        <!-- View toggle buttons + eye toggle -->
                        <div
                            ref="uiToggleRef"
                            class="irep-project-360-viewer__controls ire-absolute ire-right-2 ire-top-2 ire-z-20 ire-flex ire-items-center ire-gap-2"
                        >
                            <div
                                class="irep-project-360-viewer__eye-toggle ire-flex ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-lg ire-p-1.5 ire-shadow-md ire-transition-colors"
                                :class="
                                    pathsVisible
                                        ? 'ire-bg-white ire-text-black hover:ire-bg-gray-100'
                                        : 'ire-bg-black ire-text-white'
                                "
                                @click="pathsVisible = !pathsVisible"
                            >
                                <EyeOffIcon
                                    v-if="pathsVisible"
                                    class="ire-size-4"
                                />
                                <EyeIcon v-else class="ire-size-4" />
                            </div>
                            <div
                                v-if="floors.length > 0"
                                class="irep-project-360-viewer__view-tabs ire-flex ire-overflow-hidden ire-rounded-lg ire-shadow-md"
                            >
                                <div
                                    class="irep-project-360-viewer__view-tab ire-cursor-pointer ire-px-3 ire-py-1.5 ire-text-xs ire-font-medium ire-capitalize ire-transition-colors"
                                    :class="
                                        activeView === '360'
                                            ? 'ire-bg-black ire-text-white'
                                            : 'ire-bg-white ire-text-black hover:ire-bg-gray-100'
                                    "
                                    @click="activeView = '360'"
                                >
                                    {{ tr("3d master plan") }}
                                </div>
                                <div
                                    class="irep-project-360-viewer__view-tab ire-cursor-pointer ire-px-3 ire-py-1.5 ire-text-xs ire-font-medium ire-capitalize ire-transition-colors"
                                    :class="
                                        activeView === 'floors'
                                            ? 'ire-bg-black ire-text-white'
                                            : 'ire-bg-white ire-text-black hover:ire-bg-gray-100'
                                    "
                                    @click="activeView = 'floors'"
                                >
                                    {{ tr("floors") }}
                                </div>
                            </div>
                        </div>

                        <!-- 360 canvas view -->
                        <div
                            v-show="activeView === '360'"
                            ref="containerRef"
                            class="irep-project-360-viewer__canvas ire-h-full ire-w-full ire-select-none"
                            :style="{
                                cursor: nearUi
                                    ? 'default'
                                    : !hoveredData || isDragging
                                      ? cursor360
                                      : 'default',
                            }"
                            @pointerdown="onPointerDown"
                            @click="onTapClick"
                            @mousemove="onContainerMouseMove"
                            @mouseleave="onContainerMouseLeave"
                        >
                            <!-- Only the zoomable content (canvas + SVG overlay)
                                 is wrapped in the pinch structure; UI chrome
                                 (frame indicator, navigation arrows) stays
                                 outside so it doesn't scale. -->
                            <div
                                ref="pinchContainerRef"
                                class="ire-relative ire-h-full ire-w-full ire-overflow-hidden"
                            >
                                <div
                                    :style="pinchContentStyle"
                                    class="ire-relative ire-h-full ire-w-full"
                                >
                                    <canvas
                                        ref="canvasRef"
                                        class="ire-pointer-events-none ire-block ire-h-full ire-w-full"
                                    />
                                    <div
                                        ref="svgRef"
                                        v-html="currentSvg"
                                        class="canvas path-color ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full"
                                        :class="{
                                            'path-hover-fill-only':
                                                pathsFillOnHoverOnly,
                                        }"
                                        :style="{
                                            opacity:
                                                isDragging ||
                                                isAnimating ||
                                                !pathsVisible
                                                    ? 0
                                                    : 1,
                                            transition: isNavigating
                                                ? 'none'
                                                : 'opacity 160ms ease',
                                            pointerEvents:
                                                isDragging ||
                                                isAnimating ||
                                                !pathsVisible
                                                    ? 'none'
                                                    : 'auto',
                                        }"
                                    />
                                </div>
                            </div>
                            <div
                                class="irep-project-360-viewer__frame-indicator ire-pointer-events-none ire-absolute ire-left-2 ire-top-2 ire-z-10 ire-size-8"
                            >
                                <svg viewBox="0 0 32 32">
                                    <circle
                                        cx="16"
                                        cy="16"
                                        r="12"
                                        class="ire-fill-black/0"
                                        stroke="rgba(255,255,255,0.50)"
                                        stroke-width="2.5"
                                    />
                                    <circle
                                        cx="16"
                                        cy="16"
                                        r="12"
                                        fill="none"
                                        stroke="black"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        transform="rotate(-90 16 16)"
                                        :stroke-dasharray="frameCircumference"
                                        :stroke-dashoffset="
                                            frameStrokeDashoffset
                                        "
                                        style="
                                            transition: stroke-dashoffset 150ms
                                                ease;
                                        "
                                    />
                                </svg>
                            </div>

                            <NavigationArrows
                                @prev="handleNav(-1)"
                                @next="handleNav(1)"
                                @mouseenter="nearNav = true"
                                @mouseleave="nearNav = false"
                            />
                        </div>

                        <!-- Floors view -->
                        <Floors360
                            v-if="activeView === 'floors'"
                            :flats="flats ?? []"
                            :floors="floors"
                            :blocks="blocks"
                            :actions="actions"
                            :svg-flat-filter-active="showOnlyFilteredOnSvg"
                            :svg-visible-flat-ids="filteredFlatIds"
                            :focus-flat-id="floors360FocusFlatId"
                            :focus-floor-id="floors360FocusFloorId"
                            :paths-visible="pathsVisible"
                            class="ire-h-full ire-w-full"
                        />
                    </div>
                </PreviewLayout>

                <div
                    class="irep-project-360-viewer__sidebar ire-absolute ire-right-0 ire-top-0 ire-z-30 ire-h-full ire-overflow-hidden ire-transition-all ire-duration-300 md:ire-relative md:ire-z-auto"
                    :class="
                        isSidebarOpen
                            ? 'ire-w-full md:ire-w-[450px]'
                            : 'ire-w-0'
                    "
                >
                    <div
                        class="irep-project-360-viewer__sidebar-inner ire-h-full ire-w-full ire-overflow-hidden ire-bg-white ire-shadow-xl md:ire-w-[450px]"
                    >
                        <FlatsSidebar
                            v-model:show-only-filtered-on-svg="
                                showOnlyFilteredOnSvg
                            "
                            @update:filtered-flat-ids="onFilteredFlatIdsUpdate"
                            :active-view="activeView"
                            :floors360-floor-id="floors360SelectedFloorId"
                            :floors360-block-id="floors360SelectedBlockId"
                        />
                    </div>
                </div>
            </div>
        </MouseTracker>

        <teleport to="body">
            <Transition name="ire-fade-in-out" appear>
                <PreviewModal
                    v-if="showFlatModal"
                    @close="showFlatModal = false"
                >
                    <FlatPreview :flat="modalData" :floors="floors" />
                </PreviewModal>
            </Transition>
        </teleport>
    </div>
</template>
