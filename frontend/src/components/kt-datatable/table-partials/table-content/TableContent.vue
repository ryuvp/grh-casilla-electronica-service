<template>
  <div class="table-responsive">
    <table
      :class="[loading && 'overlay overlay-block']"
      class="table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer"
    >
      <TableHeadRow
        @onSort="onSort"
        @onSelect="selectAll"
        :checkboxEnabledValue="check"
        :checkboxEnabled="checkboxEnabled"
        :sort-label="sortLabel"
        :sort-order="sortOrder"
        :header="header"
      />
      <TableBodyRow
        v-if="data.length !== 0"
        @onSelect="itemsSelect"
        :currentlySelectedItems="selectedItems"
        :data="data"
        :header="header"
        :checkbox-enabled="checkboxEnabled"
        :checkbox-label="checkboxLabel"
      >
        <template v-for="(_, name) in $slots" v-slot:[name]="{ row: item }">
          <slot :name="name" :row="item" />
        </template>
      </TableBodyRow>
      <template v-else>
        <tr class="odd">
          <td colspan="7" class="dataTables_empty">
            {{ emptyTableText }}
          </td>
        </tr>
      </template>
      <Loading v-if="loading" />
    </table>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue";
import TableHeadRow from "@/components/kt-datatable/table-partials/table-content/table-head/TableHeadRow.vue";
import TableBodyRow from "@/components/kt-datatable/table-partials/table-content/table-body/TableBodyRow.vue";
import Loading from "@/components/kt-datatable/table-partials/Loading.vue";
const props = defineProps({
  header: { type: Array, required: true },
  data: { type: Array, required: true },
  emptyTableText: { type: String, default: "No data found" },
  sortLabel: { type: String, required: false, default: null },
  sortOrder: {
    type: String,
    required: false,
    default: "asc",
  },
  checkboxEnabled: { type: Boolean, required: false, default: false },
  checkboxLabel: { type: String, required: false, default: "id" },
  loading: { type: Boolean, required: false, default: false },
});

const emit = defineEmits(["on-sort", "on-items-select"]);

const selectedItems = ref([]);
const allSelectedItems = ref([]);
const check = ref(false);

watch(
  () => props.data,
  () => {
    selectedItems.value = [];
    allSelectedItems.value = [];
    check.value = false;
    // eslint-disable-next-line
    props.data.forEach((item) => {
      if (item[props.checkboxLabel]) {
        allSelectedItems.value.push(item[props.checkboxLabel]);
      }
    });
  }
);

// eslint-disable-next-line
const selectAll = (checkedVal) => {
  check.value = checkedVal;
  if (checkedVal) {
    selectedItems.value = [
      ...new Set([...selectedItems.value, ...allSelectedItems.value]),
    ];
  } else {
    selectedItems.value = [];
  }
};

//eslint-disable-next-line
const itemsSelect = (value) => {
  selectedItems.value = [];
  //eslint-disable-next-line
  value.forEach((item) => {
    if (!selectedItems.value.includes(item)) selectedItems.value.push(item);
  });
};

const onSort = (sort) => {
  emit("on-sort", sort);
};

watch(
  () => [...selectedItems.value],
  (currentValue) => {
    if (currentValue) {
      emit("on-items-select", currentValue);
    }
  }
);

onMounted(() => {
  selectedItems.value = [];
  allSelectedItems.value = [];
  check.value = false;
  // eslint-disable-next-line
  props.data.forEach((item) => {
    if (item[props.checkboxLabel]) {
      allSelectedItems.value.push(item[props.checkboxLabel]);
    }
  });
});
</script>
