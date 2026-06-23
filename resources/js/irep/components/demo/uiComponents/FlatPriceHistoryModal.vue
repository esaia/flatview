<script setup lang="ts">
import Close from "../../../components/icons/Close.vue";
import EyeIcon from "../../../components/icons/EyeIcon.vue";
import EyeOffIcon from "../../../components/icons/EyeOffIcon.vue";
import LineChartIcon from "../../../components/icons/LineChartIcon.vue";
import { useGetPrice, useCurrencySymbol, tr } from "../../../composable/helper";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { computed, onMounted, onUnmounted, ref, useId } from "vue";

const gradId = `ire-ph-${useId().replace(/[^a-zA-Z0-9_-]/g, "")}`;

const props = defineProps<{
  priceHistory: { price: string; date: string; timestamp?: number }[];
}>();

defineEmits<{
  (e: "close"): void;
}>();

const globalStore = useGlobalStore();
const { cssVariables } = storeToRefs(globalStore);
const getPrice = useGetPrice();
const currencySymbol = useCurrencySymbol();

const key = ref(0);

let scrollY = 0;
let savedBodyStyle: string | null = null;
let savedHtmlStyle: string | null = null;

function parseDate(d: string): Date {
  const x = new Date(d);
  return Number.isNaN(x.getTime()) ? new Date(0) : x;
}

/** Prefer API `timestamp` (ms); fall back to parsed `date` for older payloads. */
function sortTimeMs(row: { date: string; timestamp?: number }): number {
  const t = row.timestamp;
  if (typeof t === "number" && Number.isFinite(t)) return t;
  return parseDate(row.date).getTime();
}

const sorted = computed(() => {
  const list = [...props.priceHistory].sort((a, b) => {
    const ta = sortTimeMs(a);
    const tb = sortTimeMs(b);
    if (ta !== tb) return ta - tb;
    return Number(a.price) - Number(b.price);
  });
  return list.filter((row) => Number.isFinite(Number(row.price)));
});

/** Newest first for the detail list (latest on top, entry at bottom). */
const historyListNewestFirst = computed(() => [...sorted.value].reverse());

/** % change vs the next-older snapshot (same index order as `historyListNewestFirst`). */
const rowPercentsVsOlder = computed(() => {
  const rows = historyListNewestFirst.value;
  return rows.map((_, idx) => {
    if (idx >= rows.length - 1) return null;
    const newer = Number(rows[idx].price);
    const older = Number(rows[idx + 1].price);
    if (!Number.isFinite(newer) || !Number.isFinite(older) || older <= 0)
      return null;
    const pct = ((newer - older) / older) * 100;
    return Number.isFinite(pct) ? pct : null;
  });
});

function formatRowPercent(p: number): string {
  const sign = p > 0 ? "+" : "";
  return `${sign}${p.toFixed(1)}%`;
}

function formatMonthYear(dateStr: string): string {
  const d = parseDate(dateStr);
  if (d.getTime() === 0 && dateStr) return dateStr;
  const day = d.getDate();
  const month = d.toLocaleDateString("en-US", { month: "short" }).toUpperCase();
  const year = d.getFullYear();
  return `${day} ${month} ${year}`;
}

function axisMonthLabel(dateStr: string): string {
  const d = parseDate(dateStr);
  if (d.getTime() === 0 && dateStr) return dateStr.slice(0, 3).toUpperCase();
  return d.toLocaleDateString("en-US", { month: "short" }).toUpperCase();
}

/** When several points fall in the same month, show day + month so labels do not look identical. */
function axisLabelForRow(
  dateStr: string,
  rows: { date: string; price: string }[],
): string {
  const d = parseDate(dateStr);
  if (d.getTime() === 0 && dateStr) return dateStr.slice(0, 8);
  const monthKey = (x: Date) => `${x.getFullYear()}-${x.getMonth()}`;
  const key = monthKey(d);
  const sameMonth = rows.filter(
    (r) => monthKey(parseDate(r.date)) === key,
  ).length;
  if (sameMonth > 1) {
    return d
      .toLocaleDateString("en-GB", { day: "numeric", month: "short" })
      .toUpperCase()
      .replace(/\s+/g, " ");
  }
  return axisMonthLabel(dateStr);
}

/** ~max width of axis text at 9px semibold; keeps labels from crowding after index dedupe. */
const MIN_AXIS_LABEL_GAP_PX = 52;

function enforceAxisLabelMinGap<
  T extends { date: string; price: string; timestamp?: number },
>(
  ticks: Array<{ index: number; x: number; row: T }>,
  minGapPx: number,
  lastIndex: number,
): Array<{ index: number; x: number; row: T }> {
  const sorted = [...ticks].sort((a, b) => a.x - b.x);
  const out: Array<{ index: number; x: number; row: T }> = [];

  for (const t of sorted) {
    if (out.length === 0) {
      out.push(t);
      continue;
    }
    const prev = out[out.length - 1]!;
    if (t.x - prev.x >= minGapPx) {
      out.push(t);
      continue;
    }
    if (t.index !== lastIndex) continue;

    out[out.length - 1] = t;

    while (
      out.length >= 2 &&
      out[out.length - 1]!.x - out[out.length - 2]!.x < minGapPx
    ) {
      const a = out[out.length - 2]!;
      const b = out[out.length - 1]!;
      if (b.index === lastIndex && a.index !== 0) {
        out.splice(out.length - 2, 1);
      } else if (a.index === 0) {
        out.splice(out.length - 1, 1);
        break;
      } else {
        out.splice(out.length - 2, 1);
      }
    }
  }

  return out;
}

const chartMetrics = computed(() => {
  const rows = sorted.value;
  if (rows.length < 2) return null;

  const prices = rows.map((r) => Number(r.price));
  const minP = Math.min(...prices);
  const maxP = Math.max(...prices);
  const padY = 14;
  const padX = 16;
  const bottomLabel = 32;
  const w = 360;
  const h = 128;
  const innerW = w - padX * 2;
  const innerH = h - padY - bottomLabel;
  const span = maxP - minP;
  const padFrac = span > 0 ? Math.max(span * 0.06, 1) : 1;
  const domainLo = minP - padFrac;
  const domainHi = maxP + padFrac;
  const range = domainHi - domainLo || 1;

  const normY = (price: number) =>
    padY + innerH - ((price - domainLo) / range) * innerH;
  const normX = (i: number) =>
    padX + (rows.length === 1 ? innerW / 2 : (i / (rows.length - 1)) * innerW);

  const pts = rows.map((r, i) => ({
    x: normX(i),
    y: normY(Number(r.price)),
  }));

  const maxAxisLabels = 8;
  const axisLabelIndices =
    rows.length <= maxAxisLabels
      ? rows.map((_, i) => i)
      : Array.from({ length: maxAxisLabels }, (_, j) =>
          Math.round((j * (rows.length - 1)) / (maxAxisLabels - 1)),
        );
  const axisLabelTicksRaw = [...new Set(axisLabelIndices)]
    .sort((a, b) => a - b)
    .map((i) => ({ index: i, x: pts[i]!.x, row: rows[i]! }));
  const axisLabelTicks =
    rows.length > maxAxisLabels
      ? enforceAxisLabelMinGap(
          axisLabelTicksRaw,
          MIN_AXIS_LABEL_GAP_PX,
          rows.length - 1,
        )
      : axisLabelTicksRaw;

  let pathD = "";
  if (pts.length >= 2) {
    pathD = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 0; i < pts.length - 1; i++) {
      const p0 = pts[i === 0 ? i : i - 1];
      const p1 = pts[i];
      const p2 = pts[i + 1];
      const p3 = pts[i + 2] ?? p2;
      const cp1x = p1.x + (p2.x - p0.x) / 6;
      const cp1y = p1.y + (p2.y - p0.y) / 6;
      const cp2x = p2.x - (p3.x - p1.x) / 6;
      const cp2y = p2.y - (p3.y - p1.y) / 6;
      pathD += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`;
    }
  }

  return {
    w,
    h,
    pathD,
    pts,
    rows,
    axisLabelTicks,
    padX,
    bottomLabel,
    axisLabelForRow,
  };
});

/** When false: no hover tooltip, no point circles (line only). */
const chartInteractivityEnabled = ref(false);

/** Nearest data point by horizontal distance (for hover tooltip). */
const chartHoverIndex = ref<number | null>(null);

const chartTooltip = computed(() => {
  if (!chartInteractivityEnabled.value) return null;
  const m = chartMetrics.value;
  const i = chartHoverIndex.value;
  if (m == null || i === null || i < 0 || i >= m.rows.length) return null;
  const pt = m.pts[i];
  const row = m.rows[i];
  const priceLine = `${getPrice(Number(row.price))} ${currencySymbol()}`;
  const dateLine = formatMonthYear(row.date);
  const boxW = 118;
  const boxH = 40;
  const cx = Math.min(Math.max(pt.x, boxW / 2 + 4), m.w - boxW / 2 - 4);
  const boxX = cx - boxW / 2;
  const boxY = Math.max(4, pt.y - boxH - 10);
  return {
    boxX,
    boxY,
    boxW,
    boxH,
    cx,
    textY1: boxY + 16,
    textY2: boxY + 30,
    priceLine,
    dateLine,
  };
});

function toggleChartInteractivity() {
  chartInteractivityEnabled.value = !chartInteractivityEnabled.value;
  if (!chartInteractivityEnabled.value) {
    chartHoverIndex.value = null;
  }
}

function onChartPointerMove(e: PointerEvent) {
  if (!chartInteractivityEnabled.value) return;
  const svg = e.currentTarget as SVGSVGElement;
  const m = chartMetrics.value;
  if (!m) return;
  const svgPt = svg.createSVGPoint();
  svgPt.x = e.clientX;
  svgPt.y = e.clientY;
  const ctm = svg.getScreenCTM();
  if (!ctm) return;
  const p = svgPt.matrixTransform(ctm.inverse());
  let best = 0;
  let bestDx = Infinity;
  for (let i = 0; i < m.pts.length; i++) {
    const dx = Math.abs(m.pts[i].x - p.x);
    if (dx < bestDx) {
      bestDx = dx;
      best = i;
    }
  }
  chartHoverIndex.value = best;
}

function onChartPointerLeave() {
  if (!chartInteractivityEnabled.value) return;
  chartHoverIndex.value = null;
}

onMounted(() => {
  scrollY = window.scrollY;
  const { body, documentElement: html } = document;
  const scrollbarWidth = window.innerWidth - html.clientWidth;
  const currentPadding =
    parseFloat(getComputedStyle(body).paddingRight || "0") || 0;
  const paddingRight = currentPadding + scrollbarWidth;

  savedBodyStyle = body.getAttribute("style");
  savedHtmlStyle = html.getAttribute("style");

  body.setAttribute(
    "style",
    `overflow: hidden; padding-right: ${paddingRight}px;`,
  );
  html.setAttribute("style", "overflow: hidden;");
  setTimeout(() => {
    key.value++;
  }, 50);
});

onUnmounted(() => {
  setTimeout(() => {
    const { body, documentElement: html } = document;

    if (savedBodyStyle !== null) {
      body.setAttribute("style", savedBodyStyle);
    } else {
      body.removeAttribute("style");
    }

    if (savedHtmlStyle !== null) {
      html.setAttribute("style", savedHtmlStyle);
    } else {
      html.removeAttribute("style");
    }

    window.scrollTo(0, scrollY);
  }, 250);
});
</script>

<template>
  <div
    :style="cssVariables"
    class="irep-modal ire-fixed ire-left-0 ire-top-0 ire-z-[99999] ire-flex ire-h-full ire-w-full ire-cursor-pointer ire-items-center ire-justify-center ire-p-4 lg:ire-px-10 lg:ire-py-16"
  >
    <transition name="ire-fade-in-out" mode="out-in">
      <div
        v-if="key"
        :key="key"
        class="irep-price-history-modal__backdrop ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-bg-black/40 ire-backdrop-blur-sm ire-transition-all"
        @click="$emit('close')"
      ></div>
    </transition>

    <div
      class="irep-price-history-modal ire-relative ire-flex ire-max-h-[min(92svh,720px)] ire-w-full ire-max-w-[420px] ire-cursor-default ire-flex-col ire-rounded-2xl ire-bg-[#F3F4F6] ire-shadow-lg"
      @click.stop
    >
      <div
        class="irep-price-history-modal__close-wrapper ire-absolute ire-right-2 ire-top-2 ire-z-[999] ire-flex ire-w-fit"
      >
        <div
          type="button"
          class="irep-price-history-modal__close ire-flex ire-aspect-square ire-w-9 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-full ire-bg-white/90 ire-p-2 ire-text-center ire-shadow-sm ire-transition-all hover:ire-bg-gray-200 [&_path]:ire-fill-gray-500 [&_svg]:ire-size-4"
          :aria-label="tr('close')"
          @click="$emit('close')"
        >
          <Close />
        </div>
      </div>

      <div
        class="irep-price-history-modal__header ire-shrink-0 ire-px-5 ire-pt-4"
      >
        <div class="irep-price-history-modal__header-inner ire-mb-4">
          <div
            class="irep-price-history-modal__title ire-text-lg ire-font-bold ire-capitalize ire-text-gray-900"
          >
            {{ tr("price history") }}
          </div>
          <div
            class="irep-price-history-modal__subtitle ire-mt-1 ire-flex ire-items-center ire-gap-1.5 ire-text-sm ire-font-medium ire-capitalize"
            style="color: #336b73"
          >
            <LineChartIcon class="[] ire-size-4 ire-shrink-0" />
            <span>{{ tr("last 6 months") }}</span>
          </div>
        </div>

        <div
          v-if="chartMetrics"
          class="irep-price-history-modal__chart ire-relative ire-mb-5 ire-rounded-xl ire-bg-white/60 ire-px-2 ire-pb-1 ire-pt-3"
        >
          <div
            type="button"
            class="irep-price-history-modal__chart-toggle ire-absolute ire-left-2 ire-top-2 ire-z-10 ire-flex ire-size-8 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-full ire-border ire-border-gray-200 ire-bg-white/95 ire-text-gray-600 ire-shadow-sm ire-transition-colors hover:ire-bg-gray-50 hover:ire-text-gray-900"
            :aria-pressed="chartInteractivityEnabled"
            :aria-label="
              chartInteractivityEnabled
                ? tr('Disable chart details')
                : tr('Enable chart details')
            "
            :title="
              chartInteractivityEnabled
                ? tr('Disable chart details')
                : tr('Enable chart details')
            "
            @click.stop="toggleChartInteractivity"
          >
            <EyeIcon v-if="chartInteractivityEnabled" class="ire-size-4" />
            <EyeOffIcon v-else class="ire-size-4" />
          </div>
          <svg
            :viewBox="`0 0 ${chartMetrics.w} ${chartMetrics.h}`"
            class="ire-block ire-h-auto ire-w-full"
            :class="
              chartInteractivityEnabled
                ? 'ire-cursor-crosshair'
                : 'ire-cursor-default'
            "
            preserveAspectRatio="xMidYMid meet"
            @pointermove="onChartPointerMove"
            @pointerleave="onChartPointerLeave"
          >
            <defs>
              <linearGradient :id="gradId" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#7eb8c8" />
                <stop offset="100%" stop-color="#336b73" />
              </linearGradient>
            </defs>
            <path
              v-if="chartMetrics.pathD"
              :d="chartMetrics.pathD"
              fill="none"
              :stroke="`url(#${gradId})`"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <g v-if="chartInteractivityEnabled">
              <circle
                v-for="(pt, idx) in chartMetrics.pts"
                :key="idx"
                :cx="pt.x"
                :cy="pt.y"
                :r="
                  chartHoverIndex === idx
                    ? 6.5
                    : idx === chartMetrics.pts.length - 1
                      ? 5
                      : 3.5
                "
                :fill="
                  idx === chartMetrics.pts.length - 1 ? '#336b73' : '#5a9aaa'
                "
                stroke="#fff"
                stroke-width="1.5"
              />
            </g>
            <text
              v-for="tick in chartMetrics.axisLabelTicks"
              :key="'lbl-' + tick.index"
              :x="tick.x"
              :y="chartMetrics.h - 4"
              text-anchor="middle"
              class="ire-fill-gray-600"
              style="font-size: 9px; font-weight: 600"
            >
              {{
                chartMetrics.axisLabelForRow(tick.row.date, chartMetrics.rows)
              }}
            </text>
            <g v-if="chartTooltip" pointer-events="none">
              <rect
                :x="chartTooltip.boxX"
                :y="chartTooltip.boxY"
                :width="chartTooltip.boxW"
                :height="chartTooltip.boxH"
                rx="6"
                fill="#111827"
                opacity="0.92"
              />
              <text
                :x="chartTooltip.cx"
                :y="chartTooltip.textY1"
                fill="#ffffff"
                font-size="11"
                font-weight="600"
                text-anchor="middle"
                style="font-family: system-ui, sans-serif"
              >
                {{ chartTooltip.priceLine }}
              </text>
              <text
                :x="chartTooltip.cx"
                :y="chartTooltip.textY2"
                fill="#d1d5db"
                font-size="9"
                text-anchor="middle"
                style="font-family: system-ui, sans-serif"
              >
                {{ chartTooltip.dateLine }}
              </text>
            </g>
          </svg>
        </div>
      </div>

      <div
        class="irep-price-history-modal__list ire-min-h-0 ire-flex-1 ire-overflow-y-auto ire-px-5 ire-py-2"
      >
        <div
          class="irep-price-history-modal__list-inner ire-flex ire-flex-col ire-gap-3 ire-pb-1"
        >
          <div
            v-for="(entry, idx) in historyListNewestFirst"
            :key="`${idx}-${entry.date}-${entry.price}`"
            class="irep-price-history-modal__item ire-flex ire-items-center ire-justify-between ire-gap-3 ire-rounded-xl ire-px-4 ire-py-3"
            :class="
              idx === 0
                ? 'ire-ring-[var(--primary-color)]/45 ire-bg-white ire-shadow-md ire-ring-2 ire-ring-offset-2 ire-ring-offset-[#F3F4F6]'
                : 'ire-bg-white ire-shadow-sm'
            "
          >
            <div class="irep-price-history-modal__item-info">
              <div
                class="irep-price-history-modal__item-date ire-flex ire-flex-wrap ire-items-center ire-gap-2 ire-text-xs ire-font-medium ire-uppercase ire-text-gray-500"
              >
                <span>{{ formatMonthYear(entry.date) }}</span>
                <span
                  v-if="idx === 0"
                  class="ire-bg-[var(--primary-color)]/12 ire-rounded-full ire-py-0.5 ire-text-[10px] ire-font-bold ire-tracking-wide ire-text-[var(--primary-color)]"
                >
                  {{ tr("current") }}
                </span>
              </div>
              <div
                class="irep-price-history-modal__item-price ire-flex ire-gap-1 ire-text-lg ire-font-semibold ire-text-gray-900 sm:ire-text-xl"
                :class="idx === 0 ? 'ire-text-[var(--primary-color)]' : ''"
              >
                {{ getPrice(Number(entry.price)) }}
                <span class="ire-text-lg ire-font-semibold">{{
                  currencySymbol()
                }}</span>
              </div>
            </div>
            <div
              v-if="idx === historyListNewestFirst.length - 1"
              class="irep-price-history-modal__item-change irep-price-history-modal__item-change--entry ire-shrink-0 ire-rounded-full ire-bg-gray-200 ire-px-2.5 ire-py-1 ire-text-xs ire-font-semibold ire-text-gray-700"
            >
              — {{ tr("Entry") }}
            </div>
            <div
              v-else-if="rowPercentsVsOlder[idx] !== null"
              class="irep-price-history-modal__item-change ire-shrink-0 ire-rounded-full ire-px-2.5 ire-py-1 ire-text-xs ire-font-medium"
              :class="
                (rowPercentsVsOlder[idx] ?? 0) >= 0
                  ? 'ire-bg-red-100 ire-text-red-800'
                  : 'ire-bg-emerald-100 ire-text-emerald-800'
              "
            >
              <span v-if="(rowPercentsVsOlder[idx] ?? 0) >= 0">↑</span>
              <span v-else>↓</span>
              {{ formatRowPercent(rowPercentsVsOlder[idx]!) }}
            </div>
            <div
              v-else
              class="irep-price-history-modal__item-change irep-price-history-modal__item-change--neutral ire-shrink-0 ire-rounded-full ire-bg-gray-200 ire-px-2.5 ire-py-1 ire-text-xs ire-font-medium ire-text-gray-700"
            >
              —
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
