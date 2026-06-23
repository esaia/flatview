<script setup lang="ts">
defineProps<{
  editor: string | undefined;
}>();
</script>

<template>
  <div
    class="irep-base-editor [&_ul]:ire-columns-1 [&_a]:ire-cursor-pointer [&_a]:ire-text-[#2980b9] [&_a]:ire-underline [&_ul]:ire-flex [&_ul]:ire-flex-col [&_ul]:ire-gap-2 [&_ul_li]:ire-relative [&_ul_li]:ire-pl-[20px] [&_ul_li]:after:ire-absolute [&_ul_li]:after:ire-left-2 [&_ul_li]:after:ire-top-[10px] [&_ul_li]:after:ire-h-[4px] [&_ul_li]:after:ire-w-[4px] [&_ul_li]:after:ire-rounded-[50%] [&_ul_li]:after:ire-bg-black"
    v-html="editor"
  ></div>
</template>
