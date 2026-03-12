<template>
  <TablaBackend
    ref="tablaRef"
    :items="mensajesOrdenados"
    :columns="columns"
    :pagination="pagination"
    :multi-select="false"
    :selected-items="selectedItems"
    :sort-label="sortLabel"
    :sort-order="sortOrder"
    @row-select="handleSeleccion"
    @sort="handleSort"
    @page-change="handlePageChange"
    @items-per-page-change="handleSizeChange"
  >
    <template #row="{ item }">
      <td class="cell-ellipsis">
        <div class="fw-bold d-flex align-items-center gap-2">
          <i :class="item.leido ? 'bi-envelope-open-fill text-muted' : 'bi-envelope-fill text-primary'"></i>
          {{ institutionalSenderLabel }}
        </div>
        <div :class="{ 'fw-semibold': !item.leido }">
          {{ item.asunto }}
        </div>
      </td>
      <td class="text-muted small">
        {{ formatDate(item.created_at) }}
      </td>
    </template>
  </TablaBackend>
</template>

<style scoped>
/* Permite que TablaBackend se estire y use todo el alto disponible del panel. */
:deep(.table-container) {
  height: 100%;
  display: flex;
  flex-direction: column;
}

:deep(.custom-scrollbar) {
  flex: 1 1 auto;
  min-height: 0;
}

:deep(.table-responsive) {
  flex: 1 1 auto;
  min-height: 0;
}

:deep(table) {
  margin-bottom: 0;
}

:deep(.pagination) {
  margin-bottom: 0;
}

:deep(.table-container > .d-flex.justify-content-between) {
  padding-top: 0.5rem;
}
</style>

<script setup>
import { computed, ref } from 'vue'
import TablaBackend from '@/components/tabla/TablaBackend.vue'
import { formatDateTimeLima, toTimestamp } from '@/core/utils/dateTime'

const props = defineProps({
  mensajes : {
    type     : Array,
    required : true
  },
  pagination : {
    type     : Object,
    required : true
  },
  selected : {
    type    : Object,
    default : null
  }
})

const emit = defineEmits(['seleccionar', 'page-change', 'items-per-page-change'])
const institutionalSenderLabel = (import.meta.env.VITE_INSTITUCION_NOMBRE || 'GOBIERNO REGIONAL DE HUANUCO').replace(/^"|"$/g, '')

// Estado de orden administrado por TablaBackend.
const sortLabel = ref('created_at')
const sortOrder = ref('desc')

// Configuracion de columnas para la bandeja de entrada.
const columns = ref([
  { columnName: 'Mensaje', columnLabel: 'mensaje', sortEnabled: true, width: '75%' },
  { columnName: 'Fecha', columnLabel: 'created_at', sortEnabled: true, width: '25%' },
])

// TablaBackend espera arreglo para seleccion; se adapta desde seleccionado simple.
const selectedItems = computed(() => (props.selected ? [props.selected] : []))

const pagination = computed(() => props.pagination)

// Aplica orden local en base al estado emitido por TablaBackend.
const mensajesOrdenados = computed(() => {
  const data = [...props.mensajes]
  const label = sortLabel.value
  const order = sortOrder.value

  const normalize = (value) => {
    if (label === 'created_at') {
      return toTimestamp(value)
    }
    return String(value ?? '').toLowerCase()
  }

  data.sort((a, b) => {
    const av = normalize(a?.[label])
    const bv = normalize(b?.[label])

    if (av < bv) return order === 'asc' ? -1 : 1
    if (av > bv) return order === 'asc' ? 1 : -1
    return 0
  })

  return data
})

// Emite al padre el item seleccionado por TablaBackend.
function handleSeleccion({ item }) {
  if (!item) return
  emit('seleccionar', item)
}

// Sincroniza estado de orden cuando el usuario hace click en el encabezado.
function handleSort({ label, order }) {
  sortLabel.value = label
  sortOrder.value = order
}

function handlePageChange(page) {
  emit('page-change', page)
}

function handleSizeChange(perPage) {
  emit('items-per-page-change', perPage)
}

// Formatea fecha para visualizacion consistente de bandeja.
function formatDate(fechaStr) {
  return formatDateTimeLima(fechaStr)
}
</script>
