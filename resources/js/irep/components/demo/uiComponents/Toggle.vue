<script setup lang="ts">
const model = defineModel<boolean>({ default: false });

const props = withDefaults(
  defineProps<{
    /** Exposed on the switch for screen readers */
    ariaLabel?: string;
    /** Native title tooltip */
    title?: string;
    disabled?: boolean;
  }>(),
  {
    ariaLabel: "Toggle",
    disabled: false,
  },
);
</script>

<template>
  <div
    role="switch"
    :aria-checked="model"
    :aria-disabled="props.disabled"
    :disabled="props.disabled"
    :aria-label="props.ariaLabel"
    :title="props.title"
    class="irep-toggle ire-group/toggle ire-relative ire-inline-flex ire-h-[22px] ire-w-[40px] ire-shrink-0 ire-cursor-pointer ire-items-center ire-rounded-full ire-border-0 ire-p-0 ire-transition-[background-color,box-shadow] ire-duration-300 ire-ease-[cubic-bezier(0.4,0,0.2,1)] focus-visible:ire-outline-none focus-visible:ire-ring-2 focus-visible:ire-ring-[var(--primary-color)] focus-visible:ire-ring-offset-2 focus-visible:ire-ring-offset-white disabled:ire-cursor-not-allowed disabled:ire-opacity-40"
    :class="
      model
        ? 'ire-bg-[var(--primary-color)] ire-shadow-[inset_0_1px_1px_rgba(255,255,255,0.22)]'
        : 'ire-bg-slate-200 ire-shadow-[inset_0_1px_2px_rgba(15,23,42,0.08)] hover:ire-bg-slate-300/95'
    "
    @click="!props.disabled && (model = !model)"
  >
    <span
      aria-hidden="true"
      class="irep-toggle__thumb ire-pointer-events-none ire-absolute ire-left-[3px] ire-top-1/2 ire-block ire-size-[14px] -ire-translate-y-1/2 ire-rounded-full ire-bg-white ire-shadow-[0_1px_2px_rgba(15,23,42,0.15),0_1px_1px_rgba(15,23,42,0.06)] ire-ring-1 ire-ring-white/90 ire-transition-[transform,box-shadow] ire-duration-300 ire-ease-[cubic-bezier(0.4,0,0.2,1)] group-hover/toggle:ire-shadow-[0_2px_5px_rgba(15,23,42,0.14),0_1px_2px_rgba(15,23,42,0.06)]"
      :class="model ? 'ire-translate-x-[20px]' : 'ire-translate-x-0'"
    />
  </div>
</template>
