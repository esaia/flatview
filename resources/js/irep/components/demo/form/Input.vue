<script setup lang="ts">
import { ref, computed } from "vue";
import { tr } from "../../../composable/helper";

defineProps<{
  label: string;
  type?: string;
  required?: boolean;
  error?: string;
}>();

const model = defineModel();
const focused = ref(false);
const isFloated = computed(() => focused.value || !!model.value);
</script>
<template>
  <label
    class="irep-input ire-flex ire-w-full ire-flex-col ire-items-start ire-text-black"
  >
    <div class="irep-input__field-wrapper ire-relative ire-w-full">
      <input
        v-model="model"
        :type="type || 'text'"
        :required="required"
        @focus="focused = true"
        @blur="focused = false"
        class="no-spinner ire-w-full ire-rounded-md !ire-border-none ire-px-3 ire-pb-2 ire-pt-5 ire-text-base !ire-outline-none ire-ring-[1px] ire-transition-all"
        :class="
          error
            ? 'ire-ring-red-400 focus:ire-ring-red-500'
            : 'ire-ring-gray-200 focus:ire-ring-black'
        "
      />

      <span
        class="ire-pointer-events-none ire-absolute ire-left-3 ire-text-gray-600 ire-transition-all ire-duration-200"
        :class="
          isFloated
            ? 'ire-top-1.5 ire-text-xs'
            : 'ire-top-1/2 -ire-translate-y-1/2 ire-text-base'
        "
      >
        {{ tr(label) }}
      </span>

      <div class="irep-input__right-icon ire-absolute ire-right-3 ire-top-1/2 -ire-translate-y-1/2">
        <slot name="right-icon" />
      </div>
    </div>

    <Transition name="ire-error-slide">
      <div v-if="error" class="irep-input__error ire-mt-1 ire-text-xs ire-text-red-600">
        {{ error }}
      </div>
    </Transition>
  </label>
</template>

<style scoped>
.ire-error-slide-enter-active,
.ire-error-slide-leave-active {
  transition: all 0.2s ease;
}
.ire-error-slide-enter-from,
.ire-error-slide-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
