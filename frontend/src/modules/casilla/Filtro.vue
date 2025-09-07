<template>
  <div>
    <filtro-backend
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
import useCasillaStore from '@/stores/casillas/casillasPaginadoStore.js'

const store = useCasillaStore();

const searchableFields = [
  { label: 'Número', value: 'numero' },
]

const dateFields = [
  { label: 'Fecha Inicio', value: 'fecha_inicio' },
  { label: 'Fecha Fin', value: 'fecha_fin' },
]

const {
  search,
  searchField,
  dateStart,
  dateEnd,
  dateField,
} = useQueryBuilder({
  search      : '',
  searchField : [],
  dateStart   : '',
  dateEnd     : '',
  dateField   : '',
  searchableFields,
}, (event, value) => {
  if (event === 'buscar') handleBuscar(value)
})

const handleBuscar = async (filtros) => {
  await store.get(filtros)
}

</script>