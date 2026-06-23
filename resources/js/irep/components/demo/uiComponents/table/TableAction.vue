<script setup lang="ts">
import ContactIcon from "../../../../components/icons/ContactIcon.vue";
import DownloadIcon from "../../../../components/icons/DownloadIcon.vue";
import MagnifyIcon from "../../../../components/icons/MagnifyIcon.vue";
import { useGlobalStore } from "../../../../store/useGlobal";
import type { FlatItem } from "../../../../types/DemoTypes";
import { storeToRefs } from "pinia";
import { computed } from "vue";
import { getNested } from "../../../../composable/helper";

const emit = defineEmits<{
  (e: "openFlat", flatId: string): void;
}>();

defineProps<{
  flat: FlatItem;
}>();

const globalStore = useGlobalStore();
const { shortcodeData } = storeToRefs(globalStore);

const tableActionSvgs = computed(() => {
  return shortcodeData.value?.configs.tableActionSvgs;
});

const tableContactUrl = computed(() => {
  return shortcodeData.value?.configs.tableContactUrl;
});

const hasTableWholeRowClickable = computed<boolean>(
  () => shortcodeData.value?.configs?.hasTableWholeRowClickable ?? false,
);
</script>
<template>
  <div class="irep-table-action">
    <div
      class="irep-table-action__buttons ire-flex ire-items-center ire-justify-start ire-gap-3 ire-text-center [&_svg]:ire-size-6"
    >
      <template v-if="!hasTableWholeRowClickable">
        <div
          v-if="tableActionSvgs?.magnifySvg"
          v-html="tableActionSvgs?.magnifySvg"
          class="irep-table-action__btn ire-flex ire-cursor-pointer ire-justify-center ire-text-center [&_svg]:ire-size-6"
        ></div>

        <MagnifyIcon
          v-else
          class="ire-cursor-pointer ire-fill-gray-900 ire-stroke-transparent"
          @click="emit('openFlat', flat?.id)"
        />
      </template>

      <a
        v-if="tableContactUrl"
        :href="`${tableContactUrl}${getNested(flat, shortcodeData?.configs?.flatFieldQueryParameter || '') || flat?.id}`"
        target="_blank"
        class="irep-table-action__contact ire-flex ire-justify-center ire-text-center [&_svg]:ire-size-6"
        @click.stop
      >
        <div
          v-if="tableActionSvgs?.contactSvg"
          v-html="tableActionSvgs?.contactSvg"
          class="irep-table-action__btn ire-flex ire-cursor-pointer ire-justify-center ire-text-center [&_svg]:ire-size-6"
        ></div>
        <ContactIcon
          v-else
          class="ire-cursor-pointer ire-fill-transparent ire-stroke-transparent [&_path]:ire-stroke-gray-900"
        />
      </a>

      <a
        v-if="flat?.files?.[0]?.url"
        :href="flat.files[0].url"
        target="_blank"
        class="irep-table-action__download ire-flex ire-justify-center ire-text-center"
        @click.stop
      >
        <div
          v-if="tableActionSvgs?.downloadSvg"
          v-html="tableActionSvgs?.downloadSvg"
          class="irep-table-action__btn ire-flex ire-cursor-pointer ire-justify-center ire-text-center [&_svg]:ire-size-6"
        ></div>

        <DownloadIcon
          v-else
          class="ire-cursor-pointer ire-fill-transparent ire-stroke-transparent [&_path]:ire-stroke-gray-900"
        />
      </a>
    </div>
  </div>
</template>
