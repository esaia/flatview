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
            :style="{
                transform: menuOpen ? 'translateY(-25vh)' : 'translateY(0)',
                transition: 'transform 0.7s cubic-bezier(0.76, 0, 0.24, 1)'
            }"
        >
            <slot />
        </div>
        <FloatingMenu :activePage="activePage" @update:menuOpen="menuOpen = $event" />
    </div>
</template>
