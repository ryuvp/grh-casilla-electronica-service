<template>
  <div class="d-flex flex-column flex-xxl-row gap-3 w-100 align-items-start">
    <!-- Campos de búsqueda -->
    <template v-if="showSearch">
      <div class="d-flex flex-column flex-md-row gap-3 w-100">
        <div class="flex-shrink-0 w-100 w-md-auto" style="min-width: 0;">
          <label class="form-label">Buscar en:</label>
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
        <div class="flex-grow-1" style="min-width: 0;">
          <label class="form-label">Término:</label>
          <input
            v-model="search"
            type="text"
            class="form-control w-100"
            :placeholder="computedPlaceholder"
            @keyup.enter="emitBuscar"
            @blur="emitBuscar"
          />
        </div>
      </div>
    </template>

    <!-- Filtros de fecha -->
    <template v-if="showDateFilter">
      <div class="d-flex flex-column flex-md-row gap-3 w-100">
        <div class="flex-shrink-0 w-100 w-md-auto" style="min-width: 0;">
          <label class="form-label">Fecha por:</label>
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

        <div class="flex-shrink-0 w-100 w-md-auto" style="min-width: 0;">
          <label class="form-label">Desde:</label>
          <el-date-picker
            v-model="dateStart"
            type="date"
            placeholder="Fecha inicio"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            class="w-100"
          />
        </div>

        <div class="flex-shrink-0 w-100 w-md-auto" style="min-width: 0;">
          <label class="form-label">Hasta:</label>
          <el-date-picker
            v-model="dateEnd"
            type="date"
            placeholder="Fecha fin"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            class="w-100"
          />
        </div>
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

// Dispara búsqueda manualmente (usado en enter/blur)
function emitBuscar() {
  emit('buscar', buildQuery())
}

// Opcional: escucha cambios en filtros (no en el texto)
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
  return `Buscar por ${searchField.value.join(', ')}`
})
</script>
<style>
.el-date-editor.el-input{
  min-height: 44px !important;
}
</style>
