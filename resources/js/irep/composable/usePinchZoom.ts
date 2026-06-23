import { computed, onMounted, onUnmounted, ref } from "vue";

export function usePinchZoom() {
  const containerRef = ref<HTMLElement | null>(null);
  const scale = ref(1);
  const translateX = ref(0);
  const translateY = ref(0);
  const isInteracting = ref(false);

  let startDist = 0;
  let startScale = 1;
  let startMidX = 0;
  let startMidY = 0;
  let startTx = 0;
  let startTy = 0;
  let lastTouchX = 0;
  let lastTouchY = 0;
  let didPinch = false;

  const MIN_SCALE = 1;
  const MAX_SCALE = 4;

  const contentStyle = computed(() => ({
    transformOrigin: "0 0",
    transform: `translate(${translateX.value}px, ${translateY.value}px) scale(${scale.value})`,
    transition: isInteracting.value ? "none" : "transform 0.2s ease",
    willChange: "transform",
  }));

  function getDist(t1: Touch, t2: Touch) {
    return Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
  }

  function clamp(val: number, min: number, max: number) {
    return Math.max(min, Math.min(max, val));
  }

  function clampTranslate(tx: number, ty: number, s: number) {
    const el = containerRef.value;
    if (!el) return { x: tx, y: ty };
    const w = el.offsetWidth;
    const h = el.offsetHeight;
    return {
      x: clamp(tx, w * (1 - s), 0),
      y: clamp(ty, h * (1 - s), 0),
    };
  }

  function updateTouchAction() {
    const el = containerRef.value;
    if (!el) return;
    el.style.touchAction = scale.value > 1 ? "none" : "pan-x pan-y";
  }

  function resetZoom() {
    scale.value = MIN_SCALE;
    translateX.value = 0;
    translateY.value = 0;
  }

  function onTouchStart(e: TouchEvent) {
    if (e.touches.length === 2) {
      isInteracting.value = true;
      startDist = getDist(e.touches[0], e.touches[1]);
      startScale = scale.value;
      startTx = translateX.value;
      startTy = translateY.value;
      const rect = containerRef.value!.getBoundingClientRect();
      startMidX =
        (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left;
      startMidY =
        (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top;
      didPinch = false;
      e.preventDefault();
    } else if (e.touches.length === 1) {
      lastTouchX = e.touches[0].clientX;
      lastTouchY = e.touches[0].clientY;
      if (scale.value > 1) {
        isInteracting.value = true;
      }
    }
  }

  function onTouchMove(e: TouchEvent) {
    if (e.touches.length === 2) {
      e.preventDefault();
      didPinch = true;

      const dist = getDist(e.touches[0], e.touches[1]);
      const newScale = clamp(startScale * (dist / startDist), MIN_SCALE, MAX_SCALE);

      const rect = containerRef.value!.getBoundingClientRect();
      const curMidX =
        (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left;
      const curMidY =
        (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top;

      // Keep the content point under the pinch midpoint fixed
      const contentX = (startMidX - startTx) / startScale;
      const contentY = (startMidY - startTy) / startScale;
      let newTx = curMidX - contentX * newScale;
      let newTy = curMidY - contentY * newScale;

      const clamped = clampTranslate(newTx, newTy, newScale);
      scale.value = newScale;
      translateX.value = clamped.x;
      translateY.value = clamped.y;
    } else if (e.touches.length === 1 && scale.value > 1) {
      e.preventDefault();
      const dx = e.touches[0].clientX - lastTouchX;
      const dy = e.touches[0].clientY - lastTouchY;
      lastTouchX = e.touches[0].clientX;
      lastTouchY = e.touches[0].clientY;

      const clamped = clampTranslate(
        translateX.value + dx,
        translateY.value + dy,
        scale.value,
      );
      translateX.value = clamped.x;
      translateY.value = clamped.y;
    }
  }

  function onTouchEnd(e: TouchEvent) {
    isInteracting.value = false;

    if (scale.value <= MIN_SCALE) {
      resetZoom();
    }

    updateTouchAction();

    if (didPinch) {
      const cancelClick = (ev: Event) => {
        ev.stopPropagation();
        ev.preventDefault();
      };
      containerRef.value?.addEventListener("click", cancelClick, {
        once: true,
        capture: true,
      });
      didPinch = false;
    }
  }

  onMounted(() => {
    const el = containerRef.value;
    if (!el) return;
    el.style.touchAction = "pan-x pan-y";
    el.addEventListener("touchstart", onTouchStart, { passive: false });
    el.addEventListener("touchmove", onTouchMove, { passive: false });
    el.addEventListener("touchend", onTouchEnd);
  });

  onUnmounted(() => {
    const el = containerRef.value;
    if (!el) return;
    el.removeEventListener("touchstart", onTouchStart);
    el.removeEventListener("touchmove", onTouchMove);
    el.removeEventListener("touchend", onTouchEnd);
  });

  return { containerRef, contentStyle };
}
