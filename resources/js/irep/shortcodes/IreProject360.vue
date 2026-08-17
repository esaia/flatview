<script setup lang="ts">
// Viewer-only stylesheets, code-split into this async chunk instead of the
// global app.css. Fancybox base CSS must load BEFORE irep.css so irep.css's
// overrides win — notably the lightbox z-index (999999), which must sit above
// the flat modal (99999); the standalone sheet defaults to 1050 and would
// otherwise render the lightbox behind the modal.
import "@fancyapps/ui/dist/fancybox/fancybox.css";
import "../../../css/irep.css";
import { ref, onMounted } from "vue";
import Project360 from "./Project360.vue";

const props = defineProps<{
  projectId: string | number;
  // When provided (e.g. server-rendered via Inertia), the viewer renders
  // immediately and skips the client-side fetch + loading flash.
  data?: any;
}>();

const emit = defineEmits<{ (e: "ready"): void }>();

const shortcodeData = ref<any>(props.data ?? null);
const loading = ref(false);
const error = ref<string | null>(null);

const irePlugin = {
  nonce: "",
  ajax_url: "/irep/reservation",
  translations: {},
  is_premium: true,
  is_gold: true,
  price_history_addon: true,
};

onMounted(async () => {
  if (shortcodeData.value) return;

  loading.value = true;
  try {
    const res = await fetch(`/irep/shortcode-data/${props.projectId}`);
    const json = await res.json();
    if (json.success) {
      shortcodeData.value = json.data;
    } else {
      error.value = json.message ?? "Failed to load project data.";
    }
  } catch (e) {
    error.value = "Failed to load project data.";
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div>
    <div v-if="loading" class="flex items-center justify-center h-[600px] bg-gray-100">
      <div class="text-gray-500 text-sm">Loading 360 viewer...</div>
    </div>

    <div v-else-if="error" class="flex items-center justify-center h-[600px] bg-gray-100">
      <div class="text-red-500 text-sm">{{ error }}</div>
    </div>

    <Project360
      v-else-if="shortcodeData"
      :data="shortcodeData"
      :ire-plugin="irePlugin"
      @ready="emit('ready')"
    />
  </div>
</template>
