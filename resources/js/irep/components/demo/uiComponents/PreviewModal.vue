<script setup lang="ts">
import Close from "../../../components/icons/Close.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { computed, onMounted, onUnmounted, ref } from "vue";

defineEmits<{
  (e: "close"): void;
}>();

const globalStore = useGlobalStore();

const { getMetaValue } = globalStore;
const { cssVariables } = storeToRefs(globalStore);

let scrollY = 0;
let savedBodyStyle: string | null = null;
let savedHtmlStyle: string | null = null;

const flatPreview = computed(() => {
  return getMetaValue("flat_preview");
});

onMounted(() => {
  scrollY = window.scrollY;
  const { body, documentElement: html } = document;
  // Measure scrollbar width while it's still visible
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
    class="irep-modal ire-fixed ire-left-0 ire-top-0 ire-z-[99999] ire-flex ire-h-full ire-w-full ire-cursor-pointer ire-items-center ire-justify-center ire-p-4 lg:ire-px-10 lg:ire-py-32"
  >
    <div
      class="irep-preview-modal__backdrop ire-absolute ire-left-0 ire-top-0 ire-h-full ire-w-full ire-bg-black/40 ire-transition-all"
      @click="$emit('close')"
    ></div>

    <div
      class="irep-preview-modal__container ire-relative ire-w-full ire-min-w-0 ire-max-w-full ire-cursor-default ire-overflow-hidden ire-bg-white"
      :class="{
        'ire-rounded-2xl lg:ire-min-w-[200px] lg:ire-max-w-[700px]':
          flatPreview === '2',
        'ire-rounded-lg lg:ire-min-w-[500px] xl:ire-max-w-[1200px]':
          flatPreview !== '2',
      }"
    >
      <div
        class="irep-preview-modal__close-wrapper ire-absolute ire-right-0 ire-top-0 ire-flex ire-w-fit ire-flex-row-reverse ire-p-2"
      >
        <div
          class="irep-preview-modal__close ire-z-[999] ire-flex ire-aspect-[1/1] ire-w-fit ire-cursor-pointer ire-justify-center ire-rounded-full ire-bg-gray-100 ire-p-2 ire-text-center ire-transition-all hover:ire-bg-gray-600 [&_path]:ire-fill-gray-400 [&_path]:hover:ire-fill-white [&_svg]:ire-size-4"
          @click="$emit('close')"
        >
          <Close />
        </div>
      </div>

      <div
        class="irep-preview-modal__content ire-h-fit ire-max-h-[95svh] ire-min-w-0 ire-max-w-full ire-overflow-x-hidden"
        :class="{
          'ire-overflow-y-auto': flatPreview !== '2',
        }"
      >
        <slot />
      </div>
    </div>
  </div>
</template>
