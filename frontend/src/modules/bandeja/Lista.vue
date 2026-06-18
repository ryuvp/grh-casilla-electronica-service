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
          De:
          <span class="text-muted de-remitente" :title="getDeTexto(item)">
            {{ getDeTexto(item) }}
          </span>
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

<script setup>
import { computed, ref, watch } from 'vue'
import TablaBackend from '@/components/tabla/TablaBackend.vue'
import useDesignacionStore from '@/stores/designaciones/designacionStore'
import { formatDateTimeLima } from '@/core/utils/dateTime'

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

const emit = defineEmits(['seleccionar', 'page-change', 'items-per-page-change', 'sort'])
const designacionStore = useDesignacionStore()

// Estado de orden administrado por TablaBackend.
const sortLabel = ref('created_at')
const sortOrder = ref('desc')
const deTextoByMensajeId = ref({})
let cargaActual = 0

// Configuracion de columnas para la bandeja de entrada.
const columns = ref([
  { columnName: 'Mensaje', columnLabel: 'asunto', sortEnabled: true, width: '75%' },
  { columnName: 'Fecha', columnLabel: 'created_at', sortEnabled: true, width: '25%' },
])

// TablaBackend espera arreglo para seleccion; se adapta desde seleccionado simple.
const selectedItems = computed(() => (props.selected ? [props.selected] : []))

const pagination = computed(() => props.pagination)

// Con ordenamiento en backend, retornamos directamente la lista del prop
const mensajesOrdenados = computed(() => props.mensajes)

// Emite al padre el item seleccionado por TablaBackend.
function handleSeleccion({ item }) {
  if (!item) return
  emit('seleccionar', item)
}

// Sincroniza estado de orden cuando el usuario hace click en el encabezado y lo emite al padre.
function handleSort({ label, order }) {
  sortLabel.value = label
  sortOrder.value = order
  emit('sort', { label, order })
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

function getDeTexto(item) {
  return deTextoByMensajeId.value[item.id] || `Casilla ${item.casilla_origen_id}`
}

async function cargarRemitentes(mensajes = []) {
  const mensajesValidos = mensajes.filter(m => m?.id && m?.casilla_origen_id)
  if (!mensajesValidos.length) {
    deTextoByMensajeId.value = {}
    return
  }

  const cargaId = ++cargaActual
  const actores = await Promise.all(
    mensajesValidos.map((mensaje) => designacionStore.resolveActorByCasillaId(mensaje.casilla_origen_id))
  )

  if (cargaId !== cargaActual) return

  const nuevosTextos = {}
  mensajesValidos.forEach((mensaje, index) => {
    const actor = actores[index]
    nuevosTextos[mensaje.id] = actor?.usuario_nombre || `Casilla ${mensaje.casilla_origen_id}`
  })

  deTextoByMensajeId.value = nuevosTextos
}

watch(
  () => props.mensajes.map(m => `${m.id}:${m.casilla_origen_id}`).join('|'),
  () => {
    cargarRemitentes(props.mensajes)
  },
  { immediate: true }
)
</script>

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

.de-remitente {
  display: inline-block;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: bottom;
}
</style>