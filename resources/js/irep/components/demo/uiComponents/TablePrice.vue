<script setup lang="ts">
import { getNested } from "../../../composable/helper";
import Badge from "./Badge.vue";
import IconButton from "./IconButton.vue";
import LineChartIcon from "../../../components/icons/LineChartIcon.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { ref } from "vue";
import FlatPriceHistoryModal from "./FlatPriceHistoryModal.vue";

defineProps<{
  slotProps: any;
  item: any;
}>();

const globalStore = useGlobalStore();
const { hasPriceHistoryAddon } = storeToRefs(globalStore);

const showPriceHistoryModal = ref(false);
const priceHistory = ref<any>(null);

const hasPriceHistory = (item: any) => {
  return item?.price_history?.length >= 2 && hasPriceHistoryAddon.value;
};

const handleClickPriceHistory = (item: any) => {
  showPriceHistoryModal.value = true;
  priceHistory.value = item?.price_history;
};

const handleClosePriceHistory = () => {
  showPriceHistoryModal.value = false;
  priceHistory.value = null;
};
</script>
<template>
  <div class="irep-table-price ire-flex ire-items-center ire-gap-2 ire-uppercase">
    <Badge
      v-if="
        getNested(slotProps.data, 'conf') ||
        +getNested(slotProps.data, 'price_n') === 0
      "
      :conf="getNested(slotProps.data, 'conf')"
    />

    <span v-else>
      {{ getNested(slotProps.data, item.field) }}
    </span>

    <IconButton
      v-if="
        hasPriceHistory(slotProps.data) && getNested(slotProps.data, item.field)
      "
      class="price-history-button ire-w-fit"
      @click.stop.prevent="handleClickPriceHistory(slotProps.data)"
    >
      <LineChartIcon />
    </IconButton>

    <Teleport to="body">
      <Transition name="ire-fade-in-out" mode="out-in">
        <FlatPriceHistoryModal
          v-if="showPriceHistoryModal && priceHistory && hasPriceHistoryAddon"
          :price-history="priceHistory"
          @close="handleClosePriceHistory"
        />
      </Transition>
    </Teleport>
  </div>
</template>
