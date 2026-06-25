<template>
  <div class="mb-3 w-100">
    <FiltroBackend
      :search="search"
      :search-field="searchField"
      :date-start="dateStart"
      :date-end="dateEnd"
      :date-field="dateField"
      :searchable-fields="searchableFields"
      :date-fields="dateFields"
      :show-search="true"
      :show-date-filter="true"
      @buscar="handleBuscar"
    />
  </div>
</template>

<script setup>
import useQueryBuilder from '@/composables/useQueryBuilder'
import FiltroBackend from '@/components/tabla/FiltroBackend.vue'

const emit = defineEmits(['buscar'])

const searchableFields = [
  { label: 'Asunto', value: 'asunto' },
  { label: 'Contenido', value: 'contenido' },
]

const dateFields = [
  { label: 'Fecha de Envío', value: 'created_at' },
]

const {
  search,
  searchField,
  dateStart,
  dateEnd,
  dateField,
} = useQueryBuilder({
  search      : '',
  searchField : ['asunto'],
  dateStart   : '',
  dateEnd     : '',
  dateField   : 'created_at',
  searchableFields,
})

const handleBuscar = (filtros) => {
  emit('buscar', filtros)
}
</script>
