<script setup lang="ts">
import type { FlatItem, FloorItem } from "../../../../../types/DemoTypes";
import { tr } from "../../../../../composable/helper";
import Button from "../../../../../components/demo/uiComponents/Button.vue";
import PhoneIcon from "../../../../../components/icons/PhoneIcon.vue";
import DownloadIcon from "../../../../../components/icons/DownloadIcon.vue";

const emit = defineEmits<{
  (e: "showForm"): void;
}>();

const props = defineProps<{
  flat: FlatItem | undefined;
  floors?: FloorItem[] | undefined;
  showCallbackButton: boolean;
}>();
</script>
<template>
  <div
    v-if="flat?.files?.[0]?.url || showCallbackButton"
    class="irep-flat-preview-two-bottom ire-left-0 ire-right-0 ire-z-20 ire-flex ire-flex-col ire-justify-center ire-gap-4 ire-bg-white ire-p-4 ire-shadow-lg sm:ire-flex-row"
  >
    <a
      v-if="flat?.files?.[0]?.url"
      :href="flat?.files?.[0]?.url"
      target="_blank"
      class="irep-flat-moda-action-buttons__download-file ire-w-full !ire-no-underline hover:!ire-no-underline"
    >
      <Button :title="tr('download file')" variant="outline" class="ire-w-full">
        <template #icon>
          <DownloadIcon
            class="ire-size-4 group-hover:[&_path]:ire-stroke-white"
          />
        </template>
      </Button>
    </a>

    <div
      v-if="showCallbackButton"
      class="irep-flat-moda-action-buttons__request-callback ire-w-full !ire-no-underline hover:!ire-no-underline"
      @click="emit('showForm')"
    >
      <Button :title="tr('Request callback')" active class="!ire-w-full">
        <template #icon>
          <PhoneIcon class="ire-size-4 [&_path]:ire-fill-white" />
        </template>
      </Button>
    </div>
  </div>
</template>
