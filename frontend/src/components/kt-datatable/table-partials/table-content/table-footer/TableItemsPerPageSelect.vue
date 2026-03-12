<template>
  <div
    class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start"
  >
    <label for="items-per-page">
      <select
        class="form-select form-select-sm form-select-solid"
        v-if="itemsPerPageDropdownEnabled"
        v-model="itemsCountInTable"
        name="items-per-page"
        id="items-per-page"
      >
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
      </select>
    </label>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";

const props = defineProps({
  itemsPerPage: { type: Number, default: 10 },
  itemsPerPageDropdownEnabled: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const emit = defineEmits(["update:itemsPerPage"]);

const inputItemsPerPage = ref(10);

onMounted(() => {
  inputItemsPerPage.value = props.itemsPerPage;
});

const itemsCountInTable = computed({
  get() {
    return props.itemsPerPage;
  },
  set(value) {
    emit("update:itemsPerPage", value);
  },
});
</script>
