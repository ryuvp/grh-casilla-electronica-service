<template>
  <div class="d-flex justify-content-between align-items-center mb-3 gap-3">
    <!-- Filtro por texto para asunto de mensaje. -->
    <input
      type="text"
      class="form-control"
      placeholder="Buscar..."
      :value="filtro"
      @input="$emit('update-filtro', $event.target.value)"
    />
  </div>
</template>

<script setup>
defineProps({
  filtro : String
})
defineEmits(['update-filtro'])
</script>
