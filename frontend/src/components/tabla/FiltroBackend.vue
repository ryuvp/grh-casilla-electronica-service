<template>
  <div class="filtro-backend d-flex gap-2 w-100 align-items-end flex-wrap">
    <!-- Campo: Buscar en -->
    <template v-if="showSearch">
      <div class="filtro-backend__campo" style="min-width: 160px; flex: 1 1 200px;">
        <label class="form-label mb-1 fs-7 text-gray-500 fw-semibold">Buscar en:</label>
        <el-select
          v-model="searchField"
          multiple
          collapse-tags
          collapse-tags-tooltip
          placeholder="Seleccionar campos"
          class="w-100"
        >
          <el-option
            v-for="campo in searchableFields"
            :key="campo.value"
            :label="campo.label"
            :value="campo.value"
          />
        </el-select>
      </div>
    </template>

    <!-- Campo Término - crece para ocupar espacio disponible -->
    <template v-if="showSearch">
      <div style="min-width: 180px; flex: 2 1 250px;">
        <label class="form-label mb-1 fs-7 text-gray-500 fw-semibold">Término:</label>
        <input
          v-model="search"
          type="text"
          class="form-control w-100"
          :placeholder="computedPlaceholder"
          @keyup.enter="emitBuscar"
          @blur="emitBuscar"
        />
      </div>
    </template>

    <!-- Filtros de fecha -->
    <template v-if="showDateFilter">
      <div v-if="dateFields && dateFields.length > 1" class="filtro-backend__campo" style="min-width: 130px; flex: 1 1 160px;">
        <label class="form-label mb-1 fs-7 text-gray-500 fw-semibold">Fecha por:</label>
        <el-select
          v-model="dateField"
          placeholder="Fecha por"
          class="w-100"
        >
          <el-option
            v-for="campo in dateFields"
            :key="campo.value"
            :label="campo.label"
            :value="campo.value"
          />
        </el-select>
      </div>

      <div class="filtro-backend__campo" style="min-width: 140px; flex: 1 1 160px;">
        <label class="form-label mb-1 fs-7 text-gray-500 fw-semibold">Desde:</label>
        <el-date-picker
          v-model="dateStart"
          type="date"
          placeholder="Fecha inicio"
          format="DD/MM/YYYY"
          value-format="YYYY-MM-DD"
          class="w-100"
        />
      </div>

      <div class="filtro-backend__campo" style="min-width: 140px; flex: 1 1 160px;">
        <label class="form-label mb-1 fs-7 text-gray-500 fw-semibold">Hasta:</label>
        <el-date-picker
          v-model="dateEnd"
          type="date"
          placeholder="Fecha fin"
          format="DD/MM/YYYY"
          value-format="YYYY-MM-DD"
          class="w-100"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import useQueryBuilder from '@/composables/useQueryBuilder.js'

const emit = defineEmits(['buscar'])

const props = defineProps({
  searchableFields : { type: Array, default: () => [] },
  showSearch       : { type: Boolean, default: true },
  showDateFilter   : { type: Boolean, default: true },
  search           : { type: String, default: '' },
  searchField      : { type: [String, Array], default: () => [] },
  dateStart        : { type: String, default: '' },
  dateEnd          : { type: String, default: '' },
  dateField        : { type: String, default: '' },
  dateFields       : { type: Array, default: () => [] }
})

const {
  search,
  searchField,
  dateStart,
  dateEnd,
  dateField,
  buildQuery
} = useQueryBuilder(props, emit)

// Auto-seleccionar el campo de fecha cuando solo hay uno
if (props.dateFields.length === 1 && !dateField.value) {
  dateField.value = props.dateFields[0].value
}

function emitBuscar() {
  emit('buscar', buildQuery())
}

watch(
  [searchField, dateStart, dateEnd, dateField],
  () => emitBuscar(),
  { deep: true }
)

watch(search, (newVal, oldVal) => {
  if (newVal === '' && oldVal !== '') {
    emitBuscar()
  }
})

const computedPlaceholder = computed(() => {
  if (!searchField.value || searchField.value.length === 0) {
    return 'Buscar...'
  }
  const labels = searchField.value.map(val => {
    const field = props.searchableFields.find(f => f.value === val)
    return field ? field.label : val
  })
  return `Buscar por ${labels.join(', ')}`
})
</script>

<style>
.el-date-editor.el-input {
  min-height: 44px !important;
}
.filtro-backend__campo {
  flex: 1 1 auto;
}
</style>
