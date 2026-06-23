import type {
  ActionItem,
  BlockItem,
  FlatItem,
  FloorItem,
  ImageInterface360,
  PolygonDataCollection,
} from "../types/DemoTypes";
import { useGlobalStore } from "../store/useGlobal";
import { storeToRefs } from "pinia";
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import type { Ref } from "vue";

export interface UseProject360Options {
  frames: Ref<ImageInterface360[]>;
  sensitivity: Ref<number>;
  snapDurationMs: Ref<number>;
  containerRef: Ref<HTMLDivElement | null>;
  canvasRef: Ref<HTMLCanvasElement | null>;
  svgRef: Ref<HTMLDivElement | null>;
  onPolygonClick?: (polygon: PolygonDataCollection, type: string) => void;
  /** When true, flat polygons not in `svgVisibleFlatIds` are hidden in the overlay SVG. */
  svgFlatFilterActive?: Ref<boolean>;
  svgVisibleFlatIds?: Ref<ReadonlySet<string>>;
}

const CLICK_DRAG_THRESHOLD = 4;
const PRELOAD_RADIUS = 3;
const AUTO_RELEASE_MS = 2000;
const SVG_FLAT_FILTER_EASE_MS = 220;
const FLAT_FOCUS_CLASS = "ire-flat-focus";

function applySvgFlatFilterPresentation(
  gEl: SVGGElement,
  hidden: boolean,
  filterActive: boolean,
) {
  if (!filterActive) {
    const wasFilteredHidden =
      gEl.style.opacity === "0" || gEl.style.pointerEvents === "none";
    if (wasFilteredHidden) {
      gEl.style.transition = `opacity ${SVG_FLAT_FILTER_EASE_MS}ms ease`;
      gEl.style.opacity = "1";
      gEl.style.pointerEvents = "";
    } else {
      gEl.style.removeProperty("transition");
      gEl.style.removeProperty("opacity");
      gEl.style.removeProperty("pointer-events");
    }
    gEl.style.removeProperty("display");
    return;
  }
  gEl.style.transition = `opacity ${SVG_FLAT_FILTER_EASE_MS}ms ease`;
  if (hidden) {
    gEl.style.opacity = "0";
    gEl.style.pointerEvents = "none";
  } else {
    gEl.style.opacity = "1";
    gEl.style.pointerEvents = "";
  }
}

const mod = (n: number, m: number) => {
  if (m === 0) return 0;
  return ((n % m) + m) % m;
};

const easeOutCubic = (t: number) => 1 - Math.pow(1 - t, 3);

export function useProject360(options: UseProject360Options) {
  const {
    frames,
    sensitivity,
    snapDurationMs,
    containerRef,
    canvasRef,
    svgRef,
    onPolygonClick,
    svgFlatFilterActive,
    svgVisibleFlatIds,
  } = options;

  const globalStore = useGlobalStore();
  const { shortcodeData, flats: storeFlats } = storeToRefs(globalStore);

  const getConfValue = (conf: string) => {
    const customTypes = globalStore.getMetaValue("custom_types");
    const customType = customTypes?.find((t: any) => t.title === conf);
    return customType ? customType.value : conf;
  };

  // ---------------------------------------------------------------------------
  // Reactive state
  // ---------------------------------------------------------------------------

  const total = computed(() => frames.value.length);
  const frame = ref(0);
  const renderFrame = ref(0);
  const settledFrame = ref(0);
  const isDragging = ref(false);
  const isAnimating = ref(false);

  const hoveredData = ref<any>(null);
  const activePolygonType = ref<PolygonDataCollection["type"] | "">("");

  const dragStartX = ref(0);
  const dragStartIdx = ref(0);
  const dragMaxDist = ref(0);
  const lastClientX = ref(0);
  const activePointerId = ref<number | null>(null);

  let rafId: number | null = null;
  let dragRafId: number | null = null;
  let animToken = 0;
  let autoReleaseTimeoutId: number | null = null;
  let lastHoveredPath: SVGPathElement | null = null;
  let pressedG: SVGGElement | null = null;
  let resizeObserver: ResizeObserver | null = null;
  let svgFlatFilterRevealTimer: ReturnType<typeof setTimeout> | null = null;

  type FlatOccurrence = { frameIndex: number; key: string };
  type FlatFocusPending = { frameIndex: number; gKey: string; flatId: string };
  let lastFlatFocusGroup: SVGGElement | null = null;
  let flatFocusPending: FlatFocusPending | null = null;

  // ---------------------------------------------------------------------------
  // Hover helpers
  // ---------------------------------------------------------------------------

  const clearHoverState = () => {
    lastHoveredPath = null;
    globalStore.hoverdSvg = undefined;
    hoveredData.value = null;
    activePolygonType.value = "";
    if (pressedG) {
      pressedG.classList.remove("ire-path-active");
      pressedG = null;
    }
  };

  // ---------------------------------------------------------------------------
  // Timer / rAF cleanup helpers
  // ---------------------------------------------------------------------------

  const clearAutoReleaseTimer = () => {
    if (autoReleaseTimeoutId !== null) {
      clearTimeout(autoReleaseTimeoutId);
      autoReleaseTimeoutId = null;
    }
  };

  const cancelDragRaf = () => {
    if (dragRafId !== null) {
      cancelAnimationFrame(dragRafId);
      dragRafId = null;
    }
  };

  const stopAnimation = () => {
    if (rafId !== null) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
    isAnimating.value = false;
  };

  const cancelAnimation = () => {
    stopAnimation();
    animToken += 1;
  };

  // ---------------------------------------------------------------------------
  // Image preloading — caches HTMLImageElement objects for canvas drawImage()
  // ---------------------------------------------------------------------------

  const imageCache = new Map<number, HTMLImageElement>();
  const loadingPromises = new Map<number, Promise<HTMLImageElement | null>>();

  const ensureFrameImageLoaded = async (
    idx: number,
  ): Promise<HTMLImageElement | null> => {
    if (imageCache.has(idx)) return imageCache.get(idx)!;
    if (loadingPromises.has(idx)) return loadingPromises.get(idx)!;

    const url = frames.value[idx]?.img;
    if (!url) return null;

    const p = new Promise<HTMLImageElement | null>((resolve) => {
      const img = new Image();
      img.decoding = "async";
      img.onload = () => {
        imageCache.set(idx, img);
        resolve(img);
      };
      img.onerror = () => resolve(null);
      img.src = url;
    });

    loadingPromises.set(idx, p);
    try {
      return await p;
    } finally {
      loadingPromises.delete(idx);
    }
  };

  const preloadWindow = (
    centerIdx: number,
    radius: number = PRELOAD_RADIUS,
  ) => {
    const len = total.value;
    if (len <= 0) return;
    for (let offset = -radius; offset <= radius; offset++) {
      void ensureFrameImageLoaded(mod(centerIdx + offset, len));
    }
  };

  const preloadAll = (centerIdx: number) => {
    const len = total.value;
    if (len <= 0) return;
    // Load outward from center so nearby frames are prioritized
    for (let offset = 0; offset < len; offset++) {
      void ensureFrameImageLoaded(mod(centerIdx + offset, len));
      if (offset > 0) void ensureFrameImageLoaded(mod(centerIdx - offset, len));
    }
  };

  // ---------------------------------------------------------------------------
  // Canvas rendering
  // ---------------------------------------------------------------------------

  const drawFrame = (idx: number) => {
    const canvas = canvasRef.value;
    const container = containerRef.value;
    if (!canvas) return;
    if (!container) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    const img = imageCache.get(idx);
    if (!img) return;

    const dpr = window.devicePixelRatio || 1;
    const viewW = Math.max(1, Math.round(container.clientWidth));
    const viewH = Math.max(1, Math.round(container.clientHeight));

    if (canvas.width !== viewW * dpr || canvas.height !== viewH * dpr) {
      canvas.width = viewW * dpr;
      canvas.height = viewH * dpr;
    }

    const scale = Math.max(viewW / img.naturalWidth, viewH / img.naturalHeight);
    const drawW = img.naturalWidth * scale;
    const drawH = img.naturalHeight * scale;
    const offsetX = (viewW - drawW) / 2;
    const offsetY = (viewH - drawH) / 2;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(
      img,
      Math.round(offsetX * dpr),
      Math.round(offsetY * dpr),
      Math.round(drawW * dpr),
      Math.round(drawH * dpr),
    );
  };

  const syncCanvasSize = () => {
    drawFrame(renderFrame.value);
  };

  // ---------------------------------------------------------------------------
  // Snap animation
  // ---------------------------------------------------------------------------

  const animateTo = (targetIdx: number, direction: -1 | 0 | 1 = 0) => {
    stopAnimation();
    const token = ++animToken;

    const startIdx = frame.value;
    const len = total.value;
    if (len <= 0) return;

    const goal = mod(targetIdx, len);
    if (startIdx === goal) {
      const cached = imageCache.get(goal);
      if (cached) {
        renderFrame.value = goal;
        drawFrame(goal);
        settledFrame.value = goal;
      } else {
        void ensureFrameImageLoaded(goal).then((img) => {
          if (token === animToken && !isDragging.value) {
            renderFrame.value = goal;
            if (img) drawFrame(goal);
            settledFrame.value = goal;
          }
        });
      }
      return;
    }

    const forwardSteps = mod(goal - startIdx, len);
    const backwardSteps = forwardSteps === 0 ? 0 : forwardSteps - len;

    let deltaSteps: number;
    if (direction > 0) {
      deltaSteps = forwardSteps !== 0 ? forwardSteps : backwardSteps;
    } else if (direction < 0) {
      deltaSteps = backwardSteps !== 0 ? backwardSteps : forwardSteps;
    } else {
      deltaSteps =
        forwardSteps <= Math.abs(backwardSteps) ? forwardSteps : backwardSteps;
    }

    const stepCount = Math.abs(deltaSteps);
    const stepSign = deltaSteps >= 0 ? 1 : -1;

    for (let k = 0; k <= stepCount; k++) {
      void ensureFrameImageLoaded(mod(startIdx + stepSign * k, len));
    }

    clearHoverState();
    isAnimating.value = true;

    const duration = snapDurationMs.value;
    const startTime = performance.now();

    const tick = (now: number) => {
      if (token !== animToken) return;
      const elapsed = now - startTime;
      const t = duration <= 0 ? 1 : Math.min(1, elapsed / duration);
      const eased = easeOutCubic(t);
      const step = Math.min(stepCount, Math.round(stepCount * eased));
      frame.value = mod(startIdx + stepSign * step, len);

      if (t < 1) {
        rafId = requestAnimationFrame(tick);
      } else {
        frame.value = goal;
        rafId = null;

        const cached = imageCache.get(goal);
        if (cached) {
          renderFrame.value = goal;
          drawFrame(goal);
          settledFrame.value = goal;
          isAnimating.value = false;
        } else {
          void ensureFrameImageLoaded(goal).then((img) => {
            if (token !== animToken || isDragging.value) return;
            renderFrame.value = goal;
            if (img) drawFrame(goal);
            settledFrame.value = goal;
            isAnimating.value = false;
          });
        }
      }
    };

    rafId = requestAnimationFrame(tick);
  };

  // ---------------------------------------------------------------------------
  // Polygon-aware snapping
  // ---------------------------------------------------------------------------

  const snapToNearestWithPolygons = (direction: -1 | 0 | 1) => {
    const arr = frames.value;
    const here = frame.value;
    const len = total.value;
    if (len <= 0) return;

    const validIndices: number[] = [];
    for (let i = 0; i < arr.length; i++) {
      if (arr[i]?.polygon_data?.length) validIndices.push(i);
    }
    if (!validIndices.length) return;

    const forwardDist = (i: number) => mod(i - here, len);
    const backwardDist = (i: number) => mod(here - i, len);

    const pickNearest = (excludeHere: boolean) => {
      let bestIdx = here;
      let bestDist = Infinity;
      for (const i of validIndices) {
        if (excludeHere && i === here) continue;
        const d = Math.min(forwardDist(i), backwardDist(i));
        if (d < bestDist) {
          bestDist = d;
          bestIdx = i;
        }
      }
      return bestIdx;
    };

    if (direction > 0) {
      const ahead = validIndices.filter(
        (i) => i !== here && forwardDist(i) > 0,
      );
      if (ahead.length) {
        const best = ahead.reduce((a, b) =>
          forwardDist(a) <= forwardDist(b) ? a : b,
        );
        animateTo(best, direction);
        return;
      }
      animateTo(pickNearest(true), direction);
      return;
    }

    if (direction < 0) {
      const behind = validIndices.filter(
        (i) => i !== here && backwardDist(i) > 0,
      );
      if (behind.length) {
        const best = behind.reduce((a, b) =>
          backwardDist(a) <= backwardDist(b) ? a : b,
        );
        animateTo(best, direction);
        return;
      }
      animateTo(pickNearest(true), direction);
      return;
    }

    animateTo(pickNearest(true), direction);
  };

  // ---------------------------------------------------------------------------
  // Per-frame polygon maps
  // ---------------------------------------------------------------------------

  const polygonMapsByFrame = computed(() => {
    const arr = frames.value;
    const result: Array<Map<string, PolygonDataCollection>> = new Array(
      arr.length,
    );
    for (let i = 0; i < arr.length; i++) {
      const map = new Map<string, PolygonDataCollection>();
      const polys = arr[i]?.polygon_data ?? [];
      for (const p of polys) {
        if (p?.key) map.set(p.key, p);
      }
      result[i] = map;
    }
    return result;
  });

  const polygonMapForSettled = () =>
    polygonMapsByFrame.value[settledFrame.value] ?? null;

  const findFlatOccurrences = (flatId: string): FlatOccurrence[] => {
    const arr = frames.value;
    const fid = String(flatId);
    const out: FlatOccurrence[] = [];
    for (let i = 0; i < arr.length; i++) {
      const polys = arr[i]?.polygon_data ?? [];
      for (const p of polys) {
        if (p?.type === "flat" && String(p.id) === fid && p.key) {
          out.push({ frameIndex: i, key: p.key });
          break;
        }
      }
    }
    return out;
  };

  const pickNearestFlatOccurrence = (
    occurrences: FlatOccurrence[],
    fromIndex: number,
  ): FlatOccurrence | null => {
    const len = total.value;
    if (!occurrences.length || len <= 0) return null;
    let best = occurrences[0];
    let bestDist = Infinity;
    for (const o of occurrences) {
      const forward = mod(o.frameIndex - fromIndex, len);
      const backward = mod(fromIndex - o.frameIndex, len);
      const d = Math.min(forward, backward);
      if (d < bestDist) {
        bestDist = d;
        best = o;
      }
    }
    return best;
  };

  // ---------------------------------------------------------------------------
  // Lookup maps for store entities
  // ---------------------------------------------------------------------------

  const floorsById = computed(() => {
    const m = new Map<string, FloorItem>();
    for (const f of shortcodeData.value?.floors ?? []) m.set(String(f.id), f);
    return m;
  });

  const blocksById = computed(() => {
    const m = new Map<string, BlockItem>();
    for (const b of shortcodeData.value?.blocks ?? []) m.set(String(b.id), b);
    return m;
  });

  const flatsById = computed(() => {
    const m = new Map<string, FlatItem>();
    for (const fl of storeFlats.value ?? []) m.set(String(fl.id), fl);
    return m;
  });

  const actionsById = computed(() => {
    const m = new Map<string, ActionItem>();
    for (const a of shortcodeData.value?.actions ?? []) m.set(String(a.id), a);
    return m;
  });

  // ---------------------------------------------------------------------------
  // SVG attribute setup
  // ---------------------------------------------------------------------------

  const setPathAttributes = () => {
    const root = svgRef.value;
    if (!root) return;

    const filterOn = svgFlatFilterActive?.value ?? false;
    const visibleIds = svgVisibleFlatIds?.value;
    const svgFilterOn = Boolean(filterOn && visibleIds);

    if (svgFilterOn && svgFlatFilterRevealTimer !== null) {
      clearTimeout(svgFlatFilterRevealTimer);
      svgFlatFilterRevealTimer = null;
    }

    const svgEl = root.querySelector("svg");
    if (svgEl) {
      svgEl.setAttribute("width", "100%");
      svgEl.setAttribute("height", "100%");
      svgEl.setAttribute("preserveAspectRatio", "xMidYMid slice");
      svgEl.style.width = "100%";
      svgEl.style.height = "100%";
    }

    const map = polygonMapForSettled();
    const gTags = root.querySelectorAll("g");

    gTags.forEach((g) => {
      const gEl = g as SVGGElement;
      const gId = g.getAttribute("id");
      const found = gId && map ? map.get(gId) : undefined;

      if (!found?.type) {
        g.removeAttribute("polygon-type");
        g.removeAttribute("conf");
        applySvgFlatFilterPresentation(gEl, true, svgFilterOn);
        return;
      }

      let conf = "";
      switch (found.type) {
        case "floor": {
          const floor = floorsById.value.get(String(found.id));
          conf = getConfValue(floor?.conf || "");
          break;
        }
        case "block": {
          const block = blocksById.value.get(String(found.id));
          if (block) {
            g.setAttribute("polygon-type", found.type);
          }
          conf = getConfValue(block?.conf || "");
          break;
        }
        case "flat": {
          const flat = flatsById.value.get(String(found.id));
          if (flat) {
            g.setAttribute("polygon-type", found.type);
          }
          conf = getConfValue((flat?.conf as string) || "");
          break;
        }
        default:
          conf = "";
      }

      g.setAttribute("conf", conf || "");

      const showFlat =
        found.type === "flat" &&
        (Boolean(visibleIds?.has(String(found.id))) ||
          gEl.classList.contains(FLAT_FOCUS_CLASS));
      applySvgFlatFilterPresentation(gEl, !showFlat, svgFilterOn);
    });

    if (!svgFilterOn) {
      if (svgFlatFilterRevealTimer !== null) {
        clearTimeout(svgFlatFilterRevealTimer);
      }
      svgFlatFilterRevealTimer = window.setTimeout(() => {
        svgFlatFilterRevealTimer = null;
        const r = svgRef.value;
        if (!r) return;
        r.querySelectorAll("g").forEach((node) => {
          const el = node as SVGGElement;
          el.style.removeProperty("transition");
          el.style.removeProperty("opacity");
          el.style.removeProperty("pointer-events");
        });
      }, SVG_FLAT_FILTER_EASE_MS + 40);
    }
  };

  const clearFlatFocusHighlight = () => {
    if (lastFlatFocusGroup) {
      lastFlatFocusGroup.classList.remove(FLAT_FOCUS_CLASS);
      lastFlatFocusGroup.style.removeProperty("opacity");
      lastFlatFocusGroup.style.removeProperty("pointer-events");
      lastFlatFocusGroup = null;
    }
    void nextTick(() => setPathAttributes());
  };

  const applyFlatFocusHighlight = (gEl: SVGGElement) => {
    if (lastFlatFocusGroup && lastFlatFocusGroup !== gEl) {
      lastFlatFocusGroup.classList.remove(FLAT_FOCUS_CLASS);
      lastFlatFocusGroup.style.removeProperty("opacity");
      lastFlatFocusGroup.style.removeProperty("pointer-events");
    }
    gEl.classList.add(FLAT_FOCUS_CLASS);
    lastFlatFocusGroup = gEl;
    void nextTick(() => setPathAttributes());
  };

  const tryApplyFlatFocusHighlight = () => {
    const p = flatFocusPending;
    if (!p) return;
    if (isDragging.value || isAnimating.value) return;
    if (settledFrame.value !== p.frameIndex || frame.value !== p.frameIndex) {
      return;
    }

    const root = svgRef.value;
    if (!root) return;
    const escaped =
      typeof CSS !== "undefined" && "escape" in CSS
        ? CSS.escape(p.gKey)
        : p.gKey.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
    const g = root.querySelector(`g#${escaped}`) as SVGGElement | null;
    if (!g) {
      flatFocusPending = null;
      if (import.meta.env.DEV) {
        console.warn(
          `[useProject360] Missing SVG group for flat focus: g#${p.gKey}`,
        );
      }
      return;
    }
    flatFocusPending = null;
    applyFlatFocusHighlight(g);
  };

  const focusFlatOnViewer = (flatId: string) => {
    if (!flatId) return;
    flatFocusPending = null;
    clearFlatFocusHighlight();
    const occ = findFlatOccurrences(flatId);
    if (!occ.length) {
      if (import.meta.env.DEV) {
        console.warn(
          `[useProject360] Flat id="${flatId}" not found in any 360 frame polygon_data`,
        );
      }
      return;
    }
    const picked = pickNearestFlatOccurrence(occ, frame.value);
    if (!picked) return;
    flatFocusPending = {
      frameIndex: picked.frameIndex,
      gKey: picked.key,
      flatId: String(flatId),
    };
    animateTo(picked.frameIndex, 0);
    void nextTick(() => {
      void nextTick(() => tryApplyFlatFocusHighlight());
    });
  };

  // ---------------------------------------------------------------------------
  // Click on polygon
  // ---------------------------------------------------------------------------

  const handlePolygonClick = (e: PointerEvent) => {
    const root = svgRef.value;
    if (root) root.style.pointerEvents = "auto";

    const hit = document.elementFromPoint(
      e.clientX,
      e.clientY,
    ) as HTMLElement | null;

    if (root) root.style.pointerEvents = "";

    const g = hit?.closest("g[polygon-type]") as SVGGElement | null;
    if (!g) return;

    const id = g.getAttribute("id");
    const map = polygonMapForSettled();
    const p = id && map ? (map.get(id) ?? null) : null;

    if (!p?.type) return;

    if (p.type === "flat") {
      activePolygonType.value = "flat";
      hoveredData.value = flatsById.value.get(String(p.id)) ?? null;
    }

    if (pressedG) {
      pressedG.classList.remove("ire-path-active");
      pressedG = null;
    }

    onPolygonClick?.(p, p.type);
  };

  // ---------------------------------------------------------------------------
  // Hover detection for tooltips
  // ---------------------------------------------------------------------------

  const onSvgMouseOver = (e: any) => {
    if (isDragging.value || isAnimating.value) return;

    const target: HTMLElement | null = e?.target ?? null;
    if (!target || target.nodeName !== "path") {
      if (lastHoveredPath) clearHoverState();
      return;
    }

    const pathEl = target as unknown as SVGPathElement;
    if (lastHoveredPath === pathEl) return;
    lastHoveredPath = pathEl;
    globalStore.hoverdSvg = pathEl;

    const g = pathEl.parentElement;
    if (!g || g.nodeName !== "g") {
      clearHoverState();
      return;
    }

    const gId = g.getAttribute("id");
    if (!gId) {
      clearHoverState();
      return;
    }

    const poly = polygonMapForSettled()?.get(gId);
    if (!poly?.type) {
      clearHoverState();
      return;
    }

    activePolygonType.value = poly.type;

    switch (poly.type) {
      case "floor":
        hoveredData.value = floorsById.value.get(String(poly.id)) ?? null;
        break;
      case "block":
        hoveredData.value = blocksById.value.get(String(poly.id)) ?? null;
        break;
      case "flat":
        hoveredData.value = flatsById.value.get(String(poly.id)) ?? null;
        break;
      case "tooltip":
        hoveredData.value = actionsById.value.get(String(poly.id)) ?? null;
        break;
      default:
        hoveredData.value = null;
        break;
    }
  };

  // ---------------------------------------------------------------------------
  // Drag handlers
  // ---------------------------------------------------------------------------

  let pointerIsDown = false;

  const applyDragDelta = () => {
    dragRafId = null;
    if (!isDragging.value) return;
    const dx = lastClientX.value - dragStartX.value;
    dragMaxDist.value = Math.max(dragMaxDist.value, Math.abs(dx));
    const sens = sensitivity.value > 0 ? sensitivity.value : 12;
    const len = total.value;
    if (len <= 0) return;
    const steps = Math.round(-dx / sens);
    frame.value = mod(dragStartIdx.value + steps, len);
  };

  const onPointerMove = (e: PointerEvent) => {
    if (!pointerIsDown) return;
    e.preventDefault();
    lastClientX.value = e.clientX;

    const dist = Math.abs(e.clientX - dragStartX.value);
    dragMaxDist.value = Math.max(dragMaxDist.value, dist);

    if (!isDragging.value && dist >= CLICK_DRAG_THRESHOLD) {
      isDragging.value = true;
      clearHoverState();
    }

    if (isDragging.value && dragRafId === null) {
      dragRafId = requestAnimationFrame(applyDragDelta);
    }
  };

  const detachPointerListeners = () => {
    window.removeEventListener("pointermove", onPointerMove);
    window.removeEventListener("pointerup", onPointerUp);
    window.removeEventListener("pointercancel", onPointerUp);
  };

  const releaseCapturedPointer = () => {
    const pid = activePointerId.value;
    if (pid === null) return;
    try {
      containerRef.value?.releasePointerCapture(pid);
    } catch {
      /* ignore */
    }
  };

  const endInteraction = (clientX: number, e?: PointerEvent) => {
    if (!pointerIsDown) return;

    cancelDragRaf();
    if (clientX !== lastClientX.value) {
      lastClientX.value = clientX;
    }

    const wasDragging = isDragging.value;

    pointerIsDown = false;
    isDragging.value = false;
    clearAutoReleaseTimer();
    detachPointerListeners();
    releaseCapturedPointer();
    activePointerId.value = null;

    if (wasDragging) {
      clearHoverState();
      const sens = sensitivity.value > 0 ? sensitivity.value : 12;
      const dx = lastClientX.value - dragStartX.value;
      const len = total.value;
      const steps = Math.round(-dx / (sens > 0 ? sens : 12));
      const direction: -1 | 0 | 1 = steps === 0 ? 0 : steps > 0 ? 1 : -1;

      if (len > 0) {
        frame.value = mod(dragStartIdx.value + steps, len);
      }

      snapToNearestWithPolygons(direction);
    } else if (e) {
      handlePolygonClick(e);
    }
  };

  function onPointerUp(e: PointerEvent) {
    endInteraction(e.clientX, e);
  }

  const onPointerDown = (e: PointerEvent) => {
    if (e.button !== 0) return;
    e.preventDefault();

    cancelAnimation();
    cancelDragRaf();

    flatFocusPending = null;
    clearFlatFocusHighlight();

    pointerIsDown = true;
    lastClientX.value = e.clientX;
    activePointerId.value = e.pointerId;
    dragStartX.value = e.clientX;
    dragStartIdx.value = frame.value;
    dragMaxDist.value = 0;

    void ensureFrameImageLoaded(frame.value);
    preloadWindow(frame.value);

    clearAutoReleaseTimer();
    autoReleaseTimeoutId = window.setTimeout(() => {
      endInteraction(lastClientX.value);
    }, AUTO_RELEASE_MS);

    window.addEventListener("pointermove", onPointerMove, { passive: false });
    window.addEventListener("pointerup", onPointerUp);
    window.addEventListener("pointercancel", onPointerUp);

    if (lastHoveredPath) {
      const g = lastHoveredPath.parentElement as SVGGElement | null;
      if (g && g.nodeName === "g") {
        pressedG = g;
        g.classList.add("ire-path-active");
      }
    }

    try {
      containerRef.value?.setPointerCapture(e.pointerId);
    } catch {
      /* ignore */
    }
  };

  // ---------------------------------------------------------------------------
  // Computed template bindings
  // ---------------------------------------------------------------------------

  const currentSvg = computed(() => {
    const raw = frames.value[settledFrame.value]?.svg ?? "";
    if (!raw) return "";
    if (!raw.trimStart().startsWith("<")) {
      try {
        return atob(raw);
      } catch {
        // not valid base64, fall through
      }
    }
    return raw;
  });

  // ---------------------------------------------------------------------------
  // Watchers
  // ---------------------------------------------------------------------------

  watch(
    () => [isDragging.value, isAnimating.value] as const,
    ([dragging, animating]) => {
      if (dragging || animating) clearHoverState();
    },
  );

  watch(
    () => frame.value,
    (target) => {
      preloadWindow(target, PRELOAD_RADIUS);

      if (imageCache.has(target)) {
        renderFrame.value = target;
        drawFrame(target);
        return;
      }

      void ensureFrameImageLoaded(target).then((img) => {
        if (frame.value === target) {
          renderFrame.value = target;
          if (img) drawFrame(target);
        }
      });
    },
    { flush: "post" },
  );

  watch(
    () => [isDragging.value, isAnimating.value, frame.value] as const,
    ([dragging, animating, targetFrame]) => {
      if (!dragging && !animating) {
        settledFrame.value = targetFrame;
      }
    },
  );

  watch(
    () => settledFrame.value,
    () => {
      clearFlatFocusHighlight();
      clearHoverState();
      void nextTick(() => {
        setPathAttributes();
        void nextTick(() => tryApplyFlatFocusHighlight());
      });
    },
  );

  watch(
    () => isAnimating.value,
    (animating) => {
      if (!animating) void nextTick(() => tryApplyFlatFocusHighlight());
    },
  );

  watch(
    () => frames.value,
    () => {
      const len = total.value;
      if (len <= 0) return;
      frame.value = mod(frame.value, len);
      renderFrame.value = mod(renderFrame.value, len);
      settledFrame.value = mod(settledFrame.value, len);
      preloadWindow(renderFrame.value, PRELOAD_RADIUS);
      void ensureFrameImageLoaded(renderFrame.value).then((img) => {
        if (img) drawFrame(renderFrame.value);
      });
      void nextTick(() => setPathAttributes());
    },
  );

  if (svgFlatFilterActive && svgVisibleFlatIds) {
    watch(
      () =>
        [
          svgFlatFilterActive.value,
          svgVisibleFlatIds.value,
          settledFrame.value,
          frames.value,
        ] as const,
      () => {
        void nextTick(() => setPathAttributes());
      },
    );
  }

  // ---------------------------------------------------------------------------
  // Lifecycle
  // ---------------------------------------------------------------------------

  onMounted(() => {
    renderFrame.value = frame.value;
    settledFrame.value = frame.value;

    void ensureFrameImageLoaded(frame.value).then((img) => {
      if (img) drawFrame(frame.value);
      preloadAll(frame.value);
    });
    preloadWindow(frame.value, PRELOAD_RADIUS);

    if (containerRef.value) {
      resizeObserver = new ResizeObserver(syncCanvasSize);
      resizeObserver.observe(containerRef.value);
    }

    void nextTick(() => setPathAttributes());
    document.addEventListener("mousemove", onSvgMouseOver);
  });

  onUnmounted(() => {
    if (svgFlatFilterRevealTimer !== null) {
      clearTimeout(svgFlatFilterRevealTimer);
      svgFlatFilterRevealTimer = null;
    }
    flatFocusPending = null;
    clearFlatFocusHighlight();
    cancelAnimation();
    cancelDragRaf();
    clearAutoReleaseTimer();
    detachPointerListeners();
    releaseCapturedPointer();
    resizeObserver?.disconnect();
    resizeObserver = null;
    document.removeEventListener("mousemove", onSvgMouseOver);
    clearHoverState();
  });

  // ---------------------------------------------------------------------------
  // Public API
  // ---------------------------------------------------------------------------

  return {
    total,
    frame,
    renderFrame,
    settledFrame,
    isDragging,
    isAnimating,
    hoveredData,
    activePolygonType,
    currentSvg,
    onPointerDown,
    snapToNearestWithPolygons,
    focusFlatOnViewer,
  };
}
