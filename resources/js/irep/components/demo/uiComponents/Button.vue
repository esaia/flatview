<script setup lang="ts">
import { tr } from "../../../composable/helper";
import { useSlots } from "vue";

withDefaults(
  defineProps<{
    title: string;
    type?: "button" | "submit" | "reset";
    disable?: boolean;
    variant?: "default" | "outline";
  }>(),
  {
    variant: "default",
  },
);

const slots = useSlots();
</script>

<template>
  <div
    class="irep-button ire-group ire-flex ire-w-fit ire-cursor-pointer ire-items-center ire-justify-center ire-gap-2 ire-rounded-full ire-px-4 ire-py-2 ire-text-center ire-outline-none !ire-transition-all ire-duration-500 focus:!ire-shadow-none active:ire-scale-95 active:ire-bg-[color-mix(in_srgb,var(--primary-color),white_0.2%)] active:!ire-shadow-none active:!ire-outline-none"
    :class="{
      'ire-border-none ire-bg-[var(--primary-color)] hover:ire-bg-[color-mix(in_srgb,var(--primary-color),white_20%)]':
        variant === 'default',
      'ire-bg-transparent ire-ring-1 ire-ring-black hover:ire-bg-[var(--primary-color)] hover:ire-ring-transparent':
        variant === 'outline',
      'ire-pointer-events-none ire-bg-gray-500': disable,
    }"
    :type="type || 'button'"
    :disable="disable"
  >
    <div
      v-if="slots.icon"
      class="irep-button__icon ire-flex ire-items-center ire-justify-center group-active:[&_path]:ire-stroke-white"
    >
      <slot name="icon" />
    </div>

    <div
      class="irep-button__text ire-w-fit ire-text-center ire-text-base ire-transition-all group-hover:ire-text-white group-active:ire-text-white"
      :class="{
        'ire-text-white': variant === 'default',
        'ire-text-black group-hover:ire-text-white': variant === 'outline',
      }"
    >
      {{ tr(title) }}
    </div>
  </div>
</template>
