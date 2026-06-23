<script setup lang="ts">
import { ref, onMounted } from "vue";
import Project from "./Project.vue";

const props = defineProps<{
  projectId: string | number;
}>();

const shortcodeData = ref<any>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const irePlugin = {
  nonce: "",
  ajax_url: "/irep/reservation",
  translations: {},
  is_premium: true,
  is_gold: true,
  price_history_addon: false,
};

onMounted(async () => {
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
      <div class="text-gray-500 text-sm">Loading project...</div>
    </div>
    <div v-else-if="error" class="flex items-center justify-center h-[600px] bg-gray-100">
      <div class="text-red-500 text-sm">{{ error }}</div>
    </div>
    <Project
      v-else-if="shortcodeData"
      :data="shortcodeData"
      :ire-plugin="irePlugin"
    />
  </div>
</template>
