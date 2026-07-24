<script setup>
import FloatingMenu from '@/Components/FloatingMenu.vue'
import WhatsAppButton from '@/Components/WhatsAppButton.vue'
import { ref } from 'vue'

defineProps({
    activePage: { type: String, default: '' },
    alwaysVisible: { type: Boolean, default: true },
})

const menuOpen = ref(false)
</script>

<template>
    <div class="min-h-screen bg-white overflow-hidden">
        <div
            :style="{
                transform: menuOpen ? 'translateY(var(--menu-shift))' : 'none',
                transition: 'transform var(--menu-duration) var(--menu-ease)',
            }"
        >
            <slot />
        </div>
        <FloatingMenu :alwaysVisible="alwaysVisible" @update:menuOpen="menuOpen = $event" />
        <WhatsAppButton :menuOpen="menuOpen" />
    </div>
</template>
