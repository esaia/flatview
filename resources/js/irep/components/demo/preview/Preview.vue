<script setup lang="ts">
import { computed, onMounted, provide, ref } from "vue";
import type { BlockItem, FloorItem } from "../../../types/DemoTypes";
import ProjectPreview from "./ProjectPreview.vue";
import FloorPreview from "./FloorPreview.vue";
import FlatPreview from "./FlatPreview.vue";
import PreviewModal from "../uiComponents/PreviewModal.vue";
import ActionModal from "./ActionModal.vue";
import BlockPreview from "./BlockPreview.vue";
import MouseTracker from "../layout/MouseTracker.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { getQuery } from "../../../composable/helper";
import { storeToRefs } from "pinia";

const globalStore = useGlobalStore();
const { getMetaValue } = globalStore;
const { irePlaginWp, shortcodeData, openReservedFlat, openSoldFlat } = storeToRefs(globalStore);

const flow = ref<"projectFlow" | "floorFlow" | "blockFlow" | "flatFlow">(
  "projectFlow",
);
const hoveredData = ref();
const showModal = ref(false);
const activeBlock = ref<BlockItem>();
const activeFloor = ref<FloorItem>();
const showFlatModal = ref();

const project = computed(() => {
  if (!shortcodeData.value) return;

  return shortcodeData.value?.project;
});

const floors = computed(() => {
  if (!shortcodeData.value) return;

  shortcodeData.value.floors?.forEach((floor) => {
    const flats = shortcodeData.value?.flats?.filter((flat) => {
      if (flat?.floor_id !== floor?.id?.toString()) {
        return false;
      }

      if (floor?.polygon_data) {
        const matchesPolygon = Object?.values(floor?.polygon_data).some(
          (polygon) => {
            if (
              polygon?.type &&
              polygon?.type === "flat" &&
              polygon?.id === flat?.id
            ) {
              if (floor?.block_id) {
                return flat?.block_id === floor?.block_id?.toString();
              } else {
                return !flat?.block_id;
              }
            }
            return false;
          },
        );
        return matchesPolygon;
      } else {
        return false;
      }
    });

    floor.flats = flats;

    if (!floor.counts?.minimum_price) {
      const availableFlats = flats?.filter((flat) => !flat?.conf && !flat?.request_price);
      const prices = availableFlats?.map((flat) => Number(flat?.price)).filter((p) => p > 0);
      const minPrice = prices?.length ? Math.min(...prices) : 0;
      floor.counts = { ...floor.counts, minimum_price: minPrice };
    }

    const { conf } = floor || {};

    if (flats?.length && !conf) {
      const allReserved = flats?.every((flat) => flat?.conf === "reserved");
      const allSold = flats?.every((flat) => flat?.conf === "sold");

      if (allReserved) {
        floor.conf = "reserved";
      } else if (allSold) {
        floor.conf = "sold";
      }
    }
  });

  return shortcodeData.value?.floors;
});

const blocks = computed(() => {
  if (!shortcodeData.value) return;

  return shortcodeData.value.blocks;
});

const types = computed(() => {
  if (!shortcodeData.value) return;

  return shortcodeData.value.types;
});

const flats = computed(() => {
  if (!shortcodeData.value) return;

  return shortcodeData.value?.flats?.map((flat) => {
    if (flat?.use_type || !flat?.type) {
      const flatType = types.value?.find((type) => type?.id === flat?.type_id);
      if (flatType) {
        flat.type = flatType;
      }
    }

    const customTypes = getMetaValue("custom_types");
    const customType = customTypes?.find((t: any) => t.value === flat.conf);
    flat.conf = customType ? customType.title : flat.conf;

    return flat;
  });
});

const actions = computed(() => {
  if (!shortcodeData.value) return;

  return shortcodeData.value?.actions;
});

const projectMeta = computed(() => {
  if (!shortcodeData.value) return;
  return shortcodeData.value?.meta;
});

const isGoldAndSharable = computed(() => {
  return (
    getMetaValue("shareable_link") === "true" && irePlaginWp.value?.is_gold
  );
});

const openNewTab = (url: string, target: boolean = true) => {
  window.open(url, target ? "_blank" : "_self");
};

const changeRoute = (flowType: string, polygonItem: any) => {
  switch (flowType) {
    case "project":
      flow.value = "projectFlow";
      break;

    case "floor":
      if (polygonItem?.conf) break;
      flow.value = "floorFlow";
      hoveredData.value = polygonItem;
      activeFloor.value = polygonItem;
      break;

    case "block":
      if (polygonItem?.conf) break;
      flow.value = "blockFlow";
      hoveredData.value = polygonItem;
      activeBlock.value = polygonItem;
      break;

    case "flat":
      if (polygonItem?.click_action === "follow_link") {
        const { link, target } = polygonItem?.follow_link;
        openNewTab(link, target);
      } else {
        if (polygonItem?.conf === "reserved" && !openReservedFlat.value) break;
        if (polygonItem?.conf === "sold" && !openSoldFlat.value) break;

        const customTypes = getMetaValue("custom_types");
        const customType = customTypes?.find(
          (t: any) => t.title === polygonItem?.conf,
        );

        if (customType && !customType?.open_flat_modal) return;

        if (polygonItem) {
          hoveredData.value = polygonItem;
          showFlatModal.value = true;
        }
      }
      // hoveredData.value = polygonItem;

      break;
    case "tooltip":
      const actionData = polygonItem?.data;
      hoveredData.value = actionData;

      if (actionData?.actionType === "url") {
        openNewTab(actionData?.url);
      } else if (actionData?.actionType === "modal") {
        showModal.value = true;
      } else if (actionData?.actionType === "script") {
        try {
          eval(actionData?.script);
        } catch (error) {
          console.error("Error executing script:", error);
        }
      }

      break;

    default:
      break;
  }
};

const actionFromQuery = () => {
  if (!isGoldAndSharable.value) return;
  const floorId = getQuery("floorId");
  const flatId = getQuery("flatId");
  const projectId = getQuery("projectId");

  const projectWrapperDiv = document.querySelector(
    `[data-project-id="${projectId}"]`,
  );

  const floor = shortcodeData.value?.floors?.find((i) => i.id === floorId);
  const flat = shortcodeData.value?.flats?.find((i) => i.id === flatId);

  const scrollToProject = () => projectWrapperDiv?.scrollIntoView();

  if (flat) {
    if (floor) changeRoute("floor", floor);

    setTimeout(() => {
      scrollToProject();
      changeRoute("flat", flat);
    }, 400);
  } else if (floor) {
    changeRoute("floor", floor);
    scrollToProject();
  }
};

onMounted(() => {
  actionFromQuery();
});

provide("showFlatModal", showFlatModal);
</script>

<template>
  <MouseTracker class="irep-preview interactive-real-estate ire-text-base">
    <Transition name="ire-fade-in-out" mode="out-in">
      <div v-if="shortcodeData" :key="flow">
        <ProjectPreview
          v-if="flow === 'projectFlow'"
          :project="project"
          :floors="floors"
          :flats="flats"
          :projectMeta="projectMeta"
          :blocks="blocks"
          :actions="actions"
          @changeComponent="(x: any, y: any) => changeRoute(x, y)"
        />

        <BlockPreview
          v-else-if="
            flow === 'blockFlow' && flats && floors && blocks && activeBlock
          "
          :block="activeBlock"
          :flats="flats"
          :floors="floors"
          :actions="actions"
          @changeComponent="(x: any, y: any) => changeRoute(x, y)"
        />

        <FloorPreview
          v-else-if="flow === 'floorFlow' && floors && activeFloor"
          :flats="flats"
          :floor="activeFloor"
          :floors="floors"
          :blocks="blocks"
          :actions="actions"
          @changeComponent="(x: any, y: any) => changeRoute(x, y)"
        />
      </div>
    </Transition>

    <teleport to="body">
      <Transition name="ire-fade-in-out" appear>
        <PreviewModal v-if="showFlatModal" @close="showFlatModal = false">
          <FlatPreview
            :flat="hoveredData"
            :floors="floors"
            @changeComponent="(x: any, y: any) => changeRoute(x, y)"
          />
        </PreviewModal>
      </Transition>
    </teleport>

    <teleport to="body">
      <Transition name="ire-fade-in-out" appear>
        <PreviewModal v-if="showModal" @close="showModal = false">
          <ActionModal :modalData="hoveredData" />
        </PreviewModal>
      </Transition>
    </teleport>
  </MouseTracker>
</template>
