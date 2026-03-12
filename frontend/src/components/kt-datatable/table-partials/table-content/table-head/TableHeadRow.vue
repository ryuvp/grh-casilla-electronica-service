<template>
  <thead>
    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
      <th v-if="checkboxEnabled" :style="{ width: '30px' }">
        <div
          class="form-check form-check-sm form-check-custom form-check-solid me-3"
        >
          <input
            class="form-check-input"
            type="checkbox"
            v-model="checked"
            @change="selectAll()"
          />
        </div>
      </th>
      <template v-for="(column, i) in header" :key="i">
        <th
          @click="onSort(column.columnLabel, column.sortEnabled)"
          :style="{
            minWidth: column.columnWidth ? `${column.columnWidth}px` : '0',
            width: 'auto',
            cursor: column.sortEnabled ? 'pointer' : 'auto',
          }"
        >
          {{ column.columnName }}
          <span
            v-if="
              columnLabelAndOrder.label === column.columnLabel &&
              column.sortEnabled
            "
            v-html="sortArrow"
          ></span>
        </th>
      </template>
    </tr>
  </thead>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
  checkboxEnabledValue: { type: Boolean, required: false, default: false },
  checkboxEnabled: { type: Boolean, required: false, default: false },
  sortLabel: { type: String, required: false, default: null },
  sortOrder: {
    type: String,
    required: false,
    default: "asc",
  },
  header: { type: Array, required: true },
});

const emit = defineEmits(["on-select", "on-sort"]);

const checked = ref(false);
const columnLabelAndOrder = ref({
  label: props.sortLabel,
  order: props.sortOrder,
});

onMounted(() => {
  emit("on-sort", columnLabelAndOrder.value);
});

watch(
  () => props.checkboxEnabledValue,
  (currentValue) => {
    checked.value = currentValue;
  }
);

const selectAll = () => {
  emit("on-select", checked.value);
};

const onSort = (label, sortEnabled) => {
  if (sortEnabled) {
    if (columnLabelAndOrder.value.label === label) {
      if (columnLabelAndOrder.value.order === "asc") {
        columnLabelAndOrder.value.order = "desc";
      } else if (columnLabelAndOrder.value.order === "desc") {
        columnLabelAndOrder.value.order = "asc";
      }
    } else {
      columnLabelAndOrder.value.order = "asc";
      columnLabelAndOrder.value.label = label;
    }
    emit("on-sort", columnLabelAndOrder.value);
  }
};

const sortArrow = computed(() => {
  return columnLabelAndOrder.value.order === "asc" ? "&#x2191;" : "&#x2193;";
});
</script>
