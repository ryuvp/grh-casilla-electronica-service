<template>
  <div class="row">
    <TableItemsPerPageSelect
      v-model:itemsPerPage="itemsCountInTable"
      :items-per-page-dropdown-enabled="itemsPerPageDropdownEnabled"
    />
    <TablePagination
      v-if="pageCount > 1"
      :total-pages="pageCount"
      :total="count"
      :per-page="itemsPerPage"
      :current-page="page"
      @page-change="pageChange"
    />
  </div>
</template>

<script setup>
import TableItemsPerPageSelect from "@/components/kt-datatable/table-partials/table-content/table-footer/TableItemsPerPageSelect.vue";
import TablePagination from "./table-content/table-footer/TablePagination.vue";
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
  count: { type: Number, required: false, default: 5 },
  itemsPerPage: { type: Number, default: 5 },
  itemsPerPageDropdownEnabled: {
    type: Boolean,
    required: false,
    default: true,
  },
  currentPage: { type: Number, required: false, default: 1 },
});

const emit = defineEmits(["update:itemsPerPage", "page-change"]);

const page = ref(props.currentPage);
const inputItemsPerPage = ref(props.itemsPerPage);

watch(
  () => props.count,
  () => {
    page.value = 1;
  }
);

watch(
  () => inputItemsPerPage.value,
  () => {
    page.value = 1;
  }
);

onMounted(() => {
  inputItemsPerPage.value = props.itemsPerPage;
});

const pageChange = (newPage) => {
  page.value = newPage;
  emit("page-change", page.value);
};

const itemsCountInTable = computed({
  get() {
    return props.itemsPerPage;
  },
  set(value) {
    inputItemsPerPage.value = value;
    emit("update:itemsPerPage", value);
  },
});

const pageCount = computed(() => {
  return Math.ceil(props.count / itemsCountInTable.value);
});
</script>
