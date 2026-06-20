<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number | undefined | null;
}>();
</script>

<template>
    <div
        v-if="value"
        class="irep-flat-preview__attr-row ire-flex ire-items-center ire-justify-between ire-border-b ire-border-gray-100 ire-py-2.5"
    >
        <span class="ire-text-sm ire-text-gray-400 ire-uppercase">{{
            label
        }}</span>
        <span class="ire-text-sm ire-font-medium ire-text-gray-900">
            <slot>{{ value }}</slot>
        </span>
    </div>
</template>
