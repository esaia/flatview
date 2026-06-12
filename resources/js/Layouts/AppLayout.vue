<script setup>
import FloatingMenu from '@/Components/FloatingMenu.vue'

defineProps({
    activePage: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div class="min-h-screen bg-white">
        <slot />
        <FloatingMenu :activePage="activePage" />
    </div>
</template>
