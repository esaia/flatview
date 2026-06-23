<script setup lang="ts">
import { computed, inject, ref, useSlots, type Ref } from "vue";
import TableCol from "../../../../components/demo/uiComponents/table/TableCol.vue";
import SortingArrow from "../../../../components/icons/SortingArrow.vue";
import SortingArrowAsc from "../../../../components/icons/SortingArrowAsc.vue";
import { tr } from "../../../../composable/helper";

const emit = defineEmits<{
  (e: "sortColumn", field: string, sortOrder: "" | "asc" | "desc"): void;
}>();

const props = defineProps<{
  data: any;
  rowClickHandler?: (row: any) => void;
}>();

const slots: any = useSlots();

const sortField = inject<Ref<string>>("sortField", ref(""));
const sortOrder = inject<Ref<"" | "asc" | "desc">>("sortOrder", ref("asc"));

const vnodes = computed(() => {
  if (!slots.default) return;
  const columnElements = Array.from(slots?.default ? slots.default({}) : []);

  const vnodes: any = [];
  columnElements.forEach((vnode: any) => {
    if (vnode?.type?.toString() === "Symbol(v-fgt)" && vnode?.children) {
      vnodes.push(...vnode.children);
    } else {
      vnodes.push(vnode);
    }
  });

  return vnodes;
});

const fields = computed(() => {
  const arr = vnodes.value.map((vnode: any) => {
    // Prefer custom #body slot when present, so column uses it instead of plain field
    const bodySlot = vnode.children?.body;
    return typeof bodySlot === "function" ? bodySlot : vnode.props?.field;
  });

  return arr;
});

const headings = computed(() => {
  const arr = vnodes.value.map((vnode: any) => {
    return {
      ...vnode.props,
      // sortable: typeof vnode.props.sortable !== "undefined",
    };
  });

  return arr;
});

const handleSortCol = (heading: {
  field: string;
  header: string;
  sortable: boolean;
  sortableField: string;
}) => {
  if (heading.sortable) {
    if (sortField.value !== (heading.field || heading.sortableField)) {
      sortOrder.value = "";
    }

    sortOrder.value =
      sortOrder.value === "asc"
        ? "desc"
        : sortOrder.value === "desc"
          ? ""
          : "asc";

    if (!sortOrder.value) {
      sortField.value = "";
    } else {
      sortField.value = heading.field ?? heading.sortableField;
    }

    emit("sortColumn", sortField.value, sortOrder.value);
  }
};
</script>

<template>
  <div class="irep-table-wrapper ire-overflow-x-auto">
    <table
      class="irep-flats-list__table !ire-m-0 ire-w-full ire-border-collapse ire-border-spacing-0"
    >
      <thead class="irep-flats-list__table-thead">
        <tr class="ire-border-b ire-border-b-gray-200">
          <th
            v-for="heading in headings"
            :key="heading"
            class="ire-p-3 ire-text-left ire-capitalize ire-transition-all"
            :class="{
              'irep-flats-list__table-heading--sortable ire-cursor-pointer':
                heading?.sortable,
              'ire-text-black': !(
                heading?.sortable &&
                sortField === (heading.field || heading.sortableField)
              ),
              'irep-flats-list__table-heading--active ire-cursor-pointer ire-bg-black ire-text-white':
                heading?.sortable &&
                sortField === (heading.field || heading.sortableField),
              'hover:ire-bg-gray-100':
                heading?.sortable &&
                sortField !== (heading.field || heading.sortableField),
            }"
            @click="handleSortCol(heading)"
          >
            <div
              class="irep-table__header-inner ire-flex ire-min-w-max ire-items-center ire-gap-3"
            >
              <div class="irep-flats-list__table-heading ire-font-semibold">
                {{ tr(heading?.header) }}
              </div>

              <div
                class="irep-table__sort-icon ire-flex ire-justify-center ire-text-center [&_svg]:ire-size-3"
                :class="{
                  '[&_path]:ire-fill-white':
                    sortField === (heading.field || heading.sortableField) &&
                    sortOrder,
                  'ire-rotate-180': sortOrder === 'desc',
                }"
              >
                <SortingArrowAsc
                  v-if="
                    sortField === (heading.field || heading.sortableField) &&
                    sortOrder
                  "
                />
                <SortingArrow v-else-if="heading.sortable" />
              </div>
            </div>
          </th>
        </tr>
      </thead>

      <tbody>
        <TableCol
          v-for="(row, rowIndex) in data"
          :key="row?.id ?? rowIndex"
          :row="row"
          :fields="fields"
          :row-index="rowIndex"
          :rowClickHandler="props.rowClickHandler"
        />
      </tbody>
    </table>
  </div>
</template>
