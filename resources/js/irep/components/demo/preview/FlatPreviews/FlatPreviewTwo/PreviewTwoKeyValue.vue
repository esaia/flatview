<script setup lang="ts">
const props = defineProps<{
  keyName: string;
  value: string | number;
}>();
</script>
<template>
  <div
    class="irep-preview-kv ire-flex ire-h-fit ire-min-w-0 ire-max-w-full ire-flex-col"
  >
    <div
      class="irep-preview-kv__key ire-text-sm ire-uppercase ire-text-gray-700"
    >
      {{ keyName }}
    </div>
    <div
      class="irep-preview-kv__value ire-flex ire-min-w-0 ire-items-center ire-gap-1 ire-break-words ire-text-lg ire-font-semibold"
    >
      {{ value }}

      <slot name="sufix" />
    </div>
  </div>
</template>
