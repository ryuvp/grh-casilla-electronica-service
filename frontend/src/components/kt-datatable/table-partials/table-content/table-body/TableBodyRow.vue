<template>
  <tbody class="fw-semibold text-gray-600">
    <template v-for="(row, i) in data" :key="i">
      <tr>
        <td v-if="checkboxEnabled">
          <div
            class="form-check form-check-sm form-check-custom form-check-solid"
          >
            <input
              class="form-check-input"
              type="checkbox"
              :value="row[checkboxLabel]"
              v-model="selectedItems"
              @change="onChange"
            />
          </div>
        </td>
        <template v-for="(properties, j) in header" :key="j">
          <td>
            <slot :name="`${properties.columnLabel}`" :row="row">
              {{ row }}
            </slot>
          </td>
        </template>
      </tr>
    </template>
  </tbody>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  header: { type: Array, required: true },
  data: { type: Array, required: true },
  currentlySelectedItems: { type: Array, required: false, default: () => [] },
  checkboxEnabled: { type: Boolean, required: false, default: false },
  checkboxLabel: {
    type: String,
    required: false,
    default: "id",
  },
});

const emit = defineEmits(["on-select"]);

const selectedItems = ref([]);

watch(
  () => [...props.currentlySelectedItems],
  (currentValue) => {
    if (props.currentlySelectedItems.length !== 0) {
      selectedItems.value = [
        ...new Set([...selectedItems.value, ...currentValue]),
      ];
    } else {
      selectedItems.value = [];
    }
  }
);

const onChange = () => {
  emit("on-select", selectedItems.value);
};
</script>
