<script setup lang="ts">
defineProps<{
  keyName: string;
  value: string;
  keyClass?: string;
  valueClass?: string;
}>();
</script>
<template>
  <div
    v-if="value"
    class="irep-flat-kv ire-flex ire-h-fit ire-w-fit ire-flex-col ire-items-center"
  >
    <slot name="top" />

    <div
      class="irep-flat-kv__value ire-text-center ire-text-2xl !ire-font-normal ire-text-black"
      :class="keyClass"
    >
      {{ value }}
      <slot name="sufix" />
    </div>
    <div
      class="irep-flat-kv__key ire-pt-2 ire-text-sm ire-uppercase ire-text-gray-500"
      :class="valueClass"
    >
      {{ keyName }}
    </div>
  </div>
</template>
