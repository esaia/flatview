<script setup lang="ts">
import { getNested } from "../../../../composable/helper";

defineProps<{
  row: any;
  fields: any;
  rowClickHandler?: (row: any) => void;
}>();
</script>

<template>
  <tr
    class="ire-border-b ire-border-gray-200 ire-transition-all ire-duration-300 hover:ire-bg-gray-100 active:ire-bg-gray-200"
    :class="{ 'ire-cursor-pointer': rowClickHandler }"
    @click="rowClickHandler?.(row)"
  >
    <td
      v-for="(item, colIndex) in fields"
      :key="colIndex"
      class="irep-table-item !ire-p-4 ire-text-black [&_div]:ire-min-w-max"
    >
      <template v-if="typeof item === 'string'">
        <div v-if="item.includes('.')">
          {{ getNested(row, item) }}
        </div>

        <div v-else>
          {{ row[item] }}
        </div>
      </template>
      <template v-else>
        <component :is="item" :data="row" />
      </template>
    </td>
  </tr>
</template>
