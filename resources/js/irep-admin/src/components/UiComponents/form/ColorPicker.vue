<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

const emit = defineEmits<{
  (e: "update:modelValue", params: typeof props.modelValue): void;
}>();

const props = defineProps<{
  modelValue: string;
  label?: string;
  required?: boolean;
  disabled?: boolean;
  defaultColor?: string;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
let isInitializing = false;
let retryTimer: ReturnType<typeof setTimeout> | null = null;
let isSyncingFromPicker = false;

const getJquery = () => (window as Window & { jQuery?: any }).jQuery;

const initWpColorPicker = async () => {
  if (props.disabled || !inputRef.value) return;

  await nextTick();
  const $ = getJquery();
  if (!$?.fn?.wpColorPicker) {
    if (!retryTimer) {
      retryTimer = setTimeout(() => {
        retryTimer = null;
        initWpColorPicker();
      }, 150);
    }
    return;
  }

  const $input = $(inputRef.value);
  isInitializing = true;
  $input.wpColorPicker({
    defaultColor: props.defaultColor || "#000000",
    palettes: true,
    // If alpha extension is loaded, this enables opacity control.
    alpha: true,
    change: (_event: unknown, ui?: { color?: { toString?: () => string; to_s?: (type?: string) => string } }) => {
      if (isSyncingFromPicker) return;
      const nextFromUi =
        ui?.color?.to_s?.("rgb") ||
        ui?.color?.toString?.() ||
        "";
      const next = String(nextFromUi || $input.val() || "").trim();
      if (!next || next === props.modelValue) return;
      emit("update:modelValue", next);
    },
    clear: () => emit("update:modelValue", "")
  });

  $input.attr("data-alpha-enabled", "true");
  $input.wpColorPicker("color", props.modelValue || props.defaultColor || "#000000");
  isInitializing = false;
};

const destroyWpColorPicker = () => {
  if (retryTimer) {
    clearTimeout(retryTimer);
    retryTimer = null;
  }
  const $ = getJquery();
  if (!$?.fn?.wpColorPicker || !inputRef.value) return;
  $(inputRef.value).wpColorPicker("close");
};

onMounted(() => {
  initWpColorPicker();
});

watch(
  () => props.modelValue,
  (next) => {
    if (isInitializing || props.disabled || !inputRef.value) return;
    const $ = getJquery();
    if (!$?.fn?.wpColorPicker) return;
    const $input = $(inputRef.value);
    const nextColor = next || props.defaultColor || "#000000";
    const current = String($input.val() || "").trim();
    if (current === nextColor) return;

    isSyncingFromPicker = true;
    $input.wpColorPicker("color", nextColor);
    setTimeout(() => {
      isSyncingFromPicker = false;
    }, 0);
  }
);

watch(
  () => props.disabled,
  (isDisabled) => {
    if (isDisabled) {
      destroyWpColorPicker();
      return;
    }
    initWpColorPicker();
  }
);

onBeforeUnmount(() => {
  destroyWpColorPicker();
});
</script>
<template>
  <div>
    <p v-if="label" class="label !mb-1 !text-[11px] !font-medium !uppercase !tracking-wide !text-gray-600">
      {{ label }} <span v-if="required" class="text-red-600">*</span>
    </p>
    <div v-if="disabled" class="h-8 w-12 rounded-sm border border-gray-200" :style="{ backgroundColor: props.modelValue }"></div>
    <input
      v-else
      ref="inputRef"
      type="text"
      class="ire-wp-color-picker wp-color-picker-field"
      data-alpha-enabled="true"
      data-alpha-color-type="rgb"
      :data-default-color="props.defaultColor || '#000000'"
    />
  </div>
</template>
