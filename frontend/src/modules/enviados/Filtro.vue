<template>
  <div class="d-flex justify-content-between align-items-center mb-3 gap-3">
    <!-- Búsqueda -->
    <input
      type="text"
      class="form-control"
      placeholder="Buscar..."
      :value="filtro"
      @input="$emit('update-filtro', $event.target.value)"
    />

    <!-- Orden -->
    <select
      class="form-select w-auto"
      :value="orden"
      @change="$emit('update-orden', $event.target.value)"
    >
      <option value="fecha">Fecha</option>
      <option value="asunto">Asunto</option>
    </select>
  </div>
</template>

<script setup>
defineProps({
  filtro : String,
  orden  : String
})
defineEmits(['update-filtro', 'update-orden'])
</script>
