<script setup lang="ts">
defineEmits<{
  (e: "prev"): void;
  (e: "next"): void;
}>();
</script>

<template>
  <div
    class="irep-navigation-arrows ire-absolute ire-bottom-4 ire-left-1/2 ire-z-10 ire-flex -ire-translate-x-1/2 ire-items-center ire-gap-3 ire-rounded-xl ire-bg-white/20 ire-px-6 ire-py-4"
  >
    <div
      class="irep-navigation-arrows__prev ire-flex ire-h-12 ire-w-12 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-xl ire-bg-white/70 ire-shadow-md ire-backdrop-blur-sm ire-transition hover:ire-bg-white"
      @pointerdown.stop
      @click.stop="$emit('prev')"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M19 12H5M12 19l-7-7 7-7" />
      </svg>
    </div>
    <div
      class="irep-navigation-arrows__next ire-flex ire-h-12 ire-w-12 ire-cursor-pointer ire-items-center ire-justify-center ire-rounded-xl ire-bg-white/70 ire-shadow-md ire-backdrop-blur-sm ire-transition hover:ire-bg-white"
      @pointerdown.stop
      @click.stop="$emit('next')"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M5 12h14M12 5l7 7-7 7" />
      </svg>
    </div>
  </div>
</template>
