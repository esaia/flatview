<script setup lang="ts">
defineEmits<{
  (e: "prev"): void;
  (e: "next"): void;
}>();

// When the viewer fills the whole screen (standalone /project page), pin the
// arrows to the visual viewport with `fixed` instead of `absolute` — but only
// on mobile (`md:ire-absolute` restores canvas-relative centering on desktop).
// iOS in-app browsers (e.g. X/WKWebView with a persistent bottom toolbar) can
// report the viewer's `100svh` height as taller than the visible area, pushing
// an `absolute` bottom-anchored bar under the toolbar; `fixed` tracks the real
// viewport, like the site's floating buttons. On mobile the sidebar is a
// full-width overlay so the canvas spans the viewport and `left-1/2` still
// centers correctly; on desktop the sidebar is inline, so we must stay
// `absolute` to center within the (narrower) canvas rather than the viewport.
// `prevDisabled`/`nextDisabled` are set when the image set does not loop and
// has no further snap point that way — the button is dimmed and inert rather
// than clickable with nothing to rotate to.
withDefaults(
  defineProps<{
    pinToViewport?: boolean;
    prevDisabled?: boolean;
    nextDisabled?: boolean;
  }>(),
  {
    pinToViewport: false,
    prevDisabled: false,
    nextDisabled: false,
  },
);
</script>

<template>
  <div
    class="irep-navigation-arrows ire-absolute ire-bottom-4 ire-left-1/2 ire-z-10 ire-flex -ire-translate-x-1/2 ire-items-center ire-gap-3 ire-rounded-xl ire-bg-white/20 ire-px-6 ire-py-4"
    :class="{ 'irep-navigation-arrows--pinned': pinToViewport }"
  >
    <div
      class="irep-navigation-arrows__prev ire-flex ire-h-12 ire-w-12 ire-items-center ire-justify-center ire-rounded-xl ire-bg-white/70 ire-shadow-md ire-backdrop-blur-sm ire-transition"
      :class="
        prevDisabled
          ? 'ire-cursor-default ire-opacity-40'
          : 'ire-cursor-pointer hover:ire-bg-white'
      "
      @pointerdown.stop
      @click.stop="!prevDisabled && $emit('prev')"
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
      class="irep-navigation-arrows__next ire-flex ire-h-12 ire-w-12 ire-items-center ire-justify-center ire-rounded-xl ire-bg-white/70 ire-shadow-md ire-backdrop-blur-sm ire-transition"
      :class="
        nextDisabled
          ? 'ire-cursor-default ire-opacity-40'
          : 'ire-cursor-pointer hover:ire-bg-white'
      "
      @pointerdown.stop
      @click.stop="!nextDisabled && $emit('next')"
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

<style scoped>
/* The site pins a floating menu button to bottom-centre on mobile, exactly
   where these centred nav arrows sit. Lift the arrows above it (clearing the
   button height + the device safe-area) on small screens; desktop keeps the
   default bottom-4 from the utility class. */
@media (max-width: 767px) {
  .irep-navigation-arrows {
    bottom: calc(5.5rem + env(safe-area-inset-bottom));
  }

  /* On the standalone full-screen project page, pin to the visual viewport so
     iOS in-app browsers (persistent bottom toolbar) don't clip the arrows under
     it. Mobile only: the sidebar is a full-width overlay there, so the canvas
     spans the viewport and `left-1/2` still centres correctly. On desktop the
     sidebar is inline, so we must stay `absolute` (centred within the narrower
     canvas) — hence this override lives inside the mobile media query. */
  .irep-navigation-arrows--pinned {
    position: fixed;
  }
}
</style>
