<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { getPrice, tr } from "../../../composable/helper";
interface Props {
  min?: number;
  max?: number;
  step?: number;
  unit?: string;
  label?: string;
  isPrice?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  min: 0,
  max: 800,
  step: 1,
  unit: "m²",
  label: "Range",
  isPrice: false,
});

const model = defineModel<[number, number]>({ default: () => [0, 800] });

const trackRef = ref<HTMLElement | null>(null);
const dragging = ref<"min" | "max" | null>(null);

const MAX_STEP_DECIMALS = 10;

const clamp = (value: number, min: number, max: number) =>
  Math.min(max, Math.max(min, value));

/** Decimal places implied by the step (e.g. 0.4 → 1) — used with fractional snapping below. */
function fractionalDecimals(step: number): number {
  if (!Number.isFinite(step) || step <= 0) return 0;
  const abs = Math.abs(step);
  const s = abs.toString().toLowerCase();
  if (s.includes("e-")) {
    const m = /e-(\d+)/.exec(s);
    return m ? Math.min(Number(m[1]), MAX_STEP_DECIMALS) : MAX_STEP_DECIMALS;
  }
  const dot = s.indexOf(".");
  if (dot === -1) return 0;
  return Math.min(s.length - dot - 1, MAX_STEP_DECIMALS);
}

/**
 * For fractional steps (e.g. 0.4), use at least 2 decimal digits for snap + display so
 * values show as 241.20 and float noise does not appear as 241.20000000000002.
 * Integer steps (e.g. 1) stay whole numbers.
 */
function decimalsForFractionalRange(): number {
  const fd = fractionalDecimals(props.step);
  if (fd === 0) return 0;
  return Math.max(fd, 2);
}

function snapToStepPrecision(value: number): number {
  const d = decimalsForFractionalRange();
  if (d === 0) return Math.round(value);
  return Number.parseFloat(value.toFixed(d));
}

const alignToStep = (value: number) => {
  if (value <= props.min) return snapToStepPrecision(props.min);
  if (value >= props.max) return snapToStepPrecision(props.max);
  const stepped =
    Math.round((value - props.min) / props.step) * props.step + props.min;
  return snapToStepPrecision(clamp(stepped, props.min, props.max));
};

const normalizeModelRange = (range: [number, number]): [number, number] => {
  const rawMin = Number(range?.[0]);
  const rawMax = Number(range?.[1]);

  const safeMin = Number.isFinite(rawMin) ? alignToStep(rawMin) : props.min;
  const safeMax = Number.isFinite(rawMax) ? alignToStep(rawMax) : props.max;

  return [Math.min(safeMin, safeMax), Math.max(safeMin, safeMax)];
};

const minVal = computed(() => model.value[0]);
const maxVal = computed(() => model.value[1]);
const isActive = computed(
  () => minVal.value > props.min || maxVal.value < props.max,
);

const pct = (v: number) => {
  const span = props.max - props.min;
  if (!Number.isFinite(span) || span <= 0) return 0;
  return ((v - props.min) / span) * 100;
};

const thumbRadiusPx = 10; // size-5 => 20px thumb
const trackPosition = (value: number) => {
  const percentage = pct(value);
  const pxOffset = thumbRadiusPx - (percentage / 100) * thumbRadiusPx * 2;
  return `calc(${percentage}% + ${pxOffset}px)`;
};

const fillStyle = computed(() => {
  const minP = pct(minVal.value);
  const maxP = pct(maxVal.value);
  const minOff = thumbRadiusPx - (minP / 100) * thumbRadiusPx * 2;
  const maxOff = thumbRadiusPx - (maxP / 100) * thumbRadiusPx * 2;
  return {
    left: trackPosition(minVal.value),
    width: `calc(${maxP - minP}% + ${maxOff - minOff}px)`,
  };
});

const thumbMinStyle = computed(() => ({ left: trackPosition(minVal.value) }));
const thumbMaxStyle = computed(() => ({ left: trackPosition(maxVal.value) }));

const getVal = (clientX: number) => {
  const rect = trackRef.value!.getBoundingClientRect();
  const startX = rect.left + thumbRadiusPx;
  const usableWidth = Math.max(1, rect.width - thumbRadiusPx * 2);
  const raw = (clientX - startX) / usableWidth;
  if (raw <= 0) return props.min;
  if (raw >= 1) return props.max;
  const rawValue = props.min + raw * (props.max - props.min);
  const stepped =
    Math.round((rawValue - props.min) / props.step) * props.step + props.min;
  return snapToStepPrecision(clamp(stepped, props.min, props.max));
};

const onMove = (clientX: number) => {
  if (!dragging.value) return;
  const v = getVal(clientX);
  if (dragging.value === "min") {
    model.value = [
      alignToStep(Math.min(v, maxVal.value - props.step)),
      alignToStep(maxVal.value),
    ];
  } else {
    model.value = [
      alignToStep(minVal.value),
      alignToStep(Math.max(v, minVal.value + props.step)),
    ];
  }
};

const onMouseMove = (e: MouseEvent) => onMove(e.clientX);
const onTouchMove = (e: TouchEvent) => onMove(e.touches[0].clientX);
const stopDrag = () => {
  dragging.value = null;
};

watch(
  () => [props.min, props.max, props.step, model.value] as const,
  () => {
    const normalized = normalizeModelRange(model.value);
    if (normalized[0] !== model.value[0] || normalized[1] !== model.value[1]) {
      model.value = normalized;
    }
  },
  { immediate: true, deep: true },
);

onMounted(() => {
  window.addEventListener("mousemove", onMouseMove);
  window.addEventListener("mouseup", stopDrag);
  window.addEventListener("touchmove", onTouchMove, { passive: false });
  window.addEventListener("touchend", stopDrag);
});

onUnmounted(() => {
  window.removeEventListener("mousemove", onMouseMove);
  window.removeEventListener("mouseup", stopDrag);
  window.removeEventListener("touchmove", onTouchMove);
  window.removeEventListener("touchend", stopDrag);
});

const ticks = computed(() =>
  Array.from({ length: 5 }, (_, i) =>
    Math.round(props.min + (i / 4) * (props.max - props.min)),
  ),
);

function displayAxisValue(v: number) {
  if (props.isPrice) return getPrice(v);
  const d = decimalsForFractionalRange();
  if (d === 0) return String(Math.round(v));
  return Number(v).toFixed(d);
}
</script>

<template>
  <div class="irep-range ire-w-full md:ire-w-auto md:ire-min-w-[200px]">
    <div class="irep-range__header ire-mb-1 ire-flex ire-items-baseline ire-justify-between">
      <span
        class="ire-text-sm ire-font-medium ire-capitalize ire-text-gray-500"
      >
        {{ tr(label) }}
      </span>

      <div class="irep-range__values ire-flex ire-items-center ire-gap-1">
        <div
          class="irep-range__values-inner ire-flex ire-items-baseline ire-gap-1 ire-bg-gray-100 ire-p-1"
        >
          <span class="ire-text-sm ire-font-semibold ire-text-gray-700">
            {{ displayAxisValue(minVal) }}
          </span>
          <span class="ire-text-xs ire-text-gray-500">–</span>
          <span class="ire-text-sm ire-font-semibold ire-text-gray-700">
            {{ displayAxisValue(maxVal) }}
          </span>
        </div>

        <span class="ire-text-xs ire-text-gray-500">{{ unit }}</span>
      </div>
    </div>

    <div
      ref="trackRef"
      class="irep-range__track ire-relative ire-flex ire-h-9 ire-cursor-default ire-select-none ire-items-center"
    >
      <div
        class="irep-range__rail ire-absolute ire-inset-y-auto ire-left-[5px] ire-right-[5px] ire-h-1 ire-rounded-full ire-bg-gray-200"
      />

      <div
        class="irep-range__fill ire-absolute ire-h-1 ire-rounded-full ire-ease-out"
        :class="[
          isActive ? 'ire-bg-[var(--primary-color)]' : 'ire-bg-gray-200',
          dragging
            ? 'ire-transition-colors ire-duration-200'
            : 'ire-transition-[left,width,background-color] ire-duration-200',
        ]"
        :style="fillStyle"
      />

      <div
        class="irep-range__thumb irep-range__thumb--min ire-absolute ire-z-10 -ire-mt-px ire-size-5 -ire-translate-x-1/2 ire-cursor-grab ire-rounded-full ire-border ire-border-solid ire-border-black/20 ire-bg-white ire-shadow-lg ire-transition-shadow hover:ire-ring-4 hover:ire-ring-blue-500/10 active:ire-cursor-grabbing"
        :class="{
          'ire-cursor-grabbing ire-ring-4 ire-ring-blue-500/20':
            dragging === 'min',
        }"
        :style="thumbMinStyle"
        @mousedown.prevent="dragging = 'min'"
        @touchstart.prevent="dragging = 'min'"
      >
        <div
          v-if="isActive"
          class="irep-range__thumb-dot ire-absolute ire-left-1/2 ire-top-1/2 ire-size-1 -ire-translate-x-1/2 -ire-translate-y-1/2 ire-rounded-full ire-bg-[var(--primary-color)] ire-transition-all"
        />
      </div>

      <div
        class="irep-range__thumb irep-range__thumb--max ire-absolute ire-z-10 -ire-mt-px ire-size-5 -ire-translate-x-1/2 ire-cursor-grab ire-rounded-full ire-border ire-border-solid ire-border-black/20 ire-bg-white ire-shadow-lg ire-transition-shadow hover:ire-ring-4 hover:ire-ring-blue-500/10 active:ire-cursor-grabbing"
        :class="{
          'ire-cursor-grabbing ire-ring-4 ire-ring-blue-500/20':
            dragging === 'max',
        }"
        :style="thumbMaxStyle"
        @mousedown.prevent="dragging = 'max'"
        @touchstart.prevent="dragging = 'max'"
      >
        <div
          v-if="isActive"
          class="irep-range__thumb-dot ire-absolute ire-left-1/2 ire-top-1/2 ire-size-1 -ire-translate-x-1/2 -ire-translate-y-1/2 ire-rounded-full ire-bg-[var(--primary-color)] ire-transition-all"
        />
      </div>
    </div>

    <!-- <div class="ire-mt-2 ire-flex ire-justify-between ire-px-0.5">
      <span
        v-for="tick in ticks"
        :key="tick"
        class="ire-text-[11px] ire-text-gray-300"
      >
        {{ tick }}
      </span>
    </div> -->
  </div>
</template>
