<script setup>
import FloatingMenu from '@/Components/FloatingMenu.vue'
import { ref } from 'vue'

defineProps({
    activePage: { type: String, default: '' },
})

const menuOpen = ref(false)
</script>

<template>
    <div class="min-h-screen bg-white overflow-hidden">
        <div
            :style="menuOpen
                ? { transform: 'translateY(-38vh)', transition: 'transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94)' }
                : { transition: 'transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94)' }"
        >
            <slot />
        </div>
        <FloatingMenu :activePage="activePage" @update:menuOpen="menuOpen = $event" />
    </div>
</template>
