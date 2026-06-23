<script setup lang="ts">
import type { FlatItem, TableFieldsType } from "../../../types/DemoTypes";
import Table from "./table/Table.vue";
import Column from "./table/Column.vue";
import { useGlobalStore } from "../../../store/useGlobal";
import { storeToRefs } from "pinia";
import { computed } from "vue";
import { getNested, tr } from "../../../composable/helper";
import TableAction from "./table/TableAction.vue";
import Badge from "./Badge.vue";
import TablePrice from "./TablePrice.vue";

const emit = defineEmits<{
  (e: "sortColumn", field: string, sortOrder: "" | "asc" | "desc"): void;
  (e: "openFlat", flatId: string): void;
}>();

defineProps<{
  flats: FlatItem[];
}>();

const globalStore = useGlobalStore();
const { shortcodeData } = storeToRefs(globalStore);

const tableFields = computed<TableFieldsType[]>(
  () => shortcodeData.value?.configs?.tableFields || [],
);

const hasTableOneColumn = computed<boolean>(
  () => shortcodeData.value?.configs?.hasTableOneColumn ?? false,
);

const hasTableWholeRowClickable = computed<boolean>(
  () => shortcodeData.value?.configs?.hasTableWholeRowClickable ?? false,
);

const handleSortColumn = (field: string, sortOrder: any) => {
  emit("sortColumn", field, sortOrder);
};

const handleOpenFlat = (flatId: string) => {
  emit("openFlat", flatId);
};
</script>

<template>
  <div class="irep-flats-table">
    <template v-if="hasTableOneColumn">
      <div class="irep-flats-table__mobile-list ire-flex ire-flex-col md:ire-hidden">
        <div
          v-for="flat in flats"
          :key="flat.id"
          class="irep-flats-table__responsive-row ire-flex ire-items-center ire-justify-between !ire-border-b ire-border-solid !ire-border-b-black ire-py-4 last:ire-border-none"
          :class="{ 'ire-cursor-pointer': hasTableWholeRowClickable }"
          @click="hasTableWholeRowClickable && handleOpenFlat(flat.id)"
        >
          <div class="irep-flats-table__responsive-row-left ire-space-y-2">
            <div
              v-for="field in tableFields?.filter((f) => f.field)"
              :key="field.field"
              class="irep-flats-table__responsive-row-left-item ire-flex ire-flex-wrap ire-items-center ire-gap-1 ire-text-left"
            >
              <TablePrice
                v-if="field.field === 'price'"
                :slot-props="{ data: flat }"
                :item="field"
              />

              <div
                v-else-if="field.field === 'conf'"
                class="irep-flats-table__cell-conf ire-flex ire-items-center ire-gap-2 ire-uppercase"
              >
                <Badge :conf="getNested(flat, 'conf')" />
              </div>

              <div v-else class="irep-flats-table__cell-value ire-text-black">
                {{ getNested(flat, field.field) }}
              </div>
            </div>
          </div>

          <TableAction :flat="flat" @open-flat="handleOpenFlat" />
        </div>
      </div>

      <Table
        :data="flats"
        class="ire-hidden md:ire-block"
        :rowClickHandler="hasTableWholeRowClickable ? (row: any) => handleOpenFlat(row.id) : undefined"
        @sort-column="handleSortColumn"
      >
        <Column
          v-for="item in tableFields"
          :key="item.field"
          :field="item.field"
          :header="item.header"
          :sortable="item?.sortable"
        >
          <template v-if="item.field === 'price'" #body="slotProps">
            <TablePrice :slot-props="slotProps" :item="item" />
          </template>

          <template v-else-if="item.field === 'conf'" #body="slotProps">
            <div class="irep-flats-table__conf-cell ire-flex ire-items-center ire-gap-2 ire-uppercase">
              <Badge :conf="getNested(slotProps.data, 'conf')" />
            </div>
          </template>
        </Column>

        <Column :header="tr('action')" sortable>
          <template #body="slotProps">
            <TableAction :flat="slotProps.data" @open-flat="handleOpenFlat" />
          </template>
        </Column>
      </Table>
    </template>

    <Table
      v-else
      :data="flats"
      :rowClickHandler="hasTableWholeRowClickable ? (row: any) => handleOpenFlat(row.id) : undefined"
      @sort-column="handleSortColumn"
    >
      <Column
        v-for="item in tableFields"
        :key="item.field"
        :field="item.field"
        :header="item.header"
        :sortable="item?.sortable"
      >
        <template v-if="item.field === 'price'" #body="slotProps">
          <TablePrice :slot-props="slotProps" :item="item" />
        </template>

        <template v-else-if="item.field === 'conf'" #body="slotProps">
          <div
            v-if="getNested(slotProps.data, 'conf')"
            class="ire-flex ire-items-center ire-gap-2 ire-uppercase"
          >
            <Badge :conf="getNested(slotProps.data, 'conf')" />
          </div>
        </template>
      </Column>

      <Column :header="tr('action')" sortable>
        <template #body="slotProps">
          <TableAction :flat="slotProps.data" @open-flat="handleOpenFlat" />
        </template>
      </Column>
    </Table>
  </div>
</template>
