<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import IreProject360 from '@/irep/shortcodes/IreProject360.vue'

defineProps({
    projectId: {
        type: [String, Number],
        required: true,
    },
    projectData: {
        type: Object,
        default: null,
    },
})
</script>

<template>
    <AppLayout :always-visible="true">
        <div class="project-360-page h-[100svh] w-full overflow-hidden">
            <IreProject360 :project-id="projectId" :data="projectData" />
        </div>
    </AppLayout>
</template>

<style>
/* Lock the standalone viewer to exactly one viewport — no page scroll. */
html:has(.project-360-page),
body:has(.project-360-page) {
    overflow: hidden;
}

/* The viewer's children all use `h-full` (height:100%), so the viewer needs a
   *definite* height to resolve against. A chained `height:100%` from the page
   down doesn't resolve reliably, so set an absolute svh height directly on the
   viewer (this is what the template app does in its global stylesheet). */
.project-360-page .irep-project-360-viewer {
    height: 100svh;
}
</style>
