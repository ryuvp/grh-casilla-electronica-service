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
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
          <i class="bi-send-fill text-success"></i>
          Para:
          <span
            class="text-muted para-destinatario"
            :title="getParaTexto(item)"
          >
            {{ getParaTexto(item) }}
          </span>
        </div>
        <div>{{ item.asunto }}</div>
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
const paraTextoByMensajeId = ref({})
let cargaActual = 0

// Configuracion de columnas para bandeja de enviados.
const columns = ref([
  { columnName: 'Mensaje Enviado', columnLabel: 'asunto', sortEnabled: true, width: '75%' },
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

// Sincroniza estado de orden cuando el usuario hace click en el encabezado y emite al padre.
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

// Formatea fecha para visualizacion consistente de enviados.
function formatDate(fechaStr) {
  return formatDateTimeLima(fechaStr)
}

function getParaTexto(item) {
  return paraTextoByMensajeId.value[item.id] || `Casilla ${item.casilla_destino_id}`
}

async function cargarDestinatarios(mensajes = []) {
  const mensajesValidos = mensajes.filter(m => m?.id && m?.casilla_destino_id)
  if (!mensajesValidos.length) {
    paraTextoByMensajeId.value = {}
    return
  }

  const cargaId = ++cargaActual
  const actores = await Promise.all(
    mensajesValidos.map((mensaje) => designacionStore.resolveActorByCasillaId(mensaje.casilla_destino_id))
  )

  if (cargaId !== cargaActual) return

  const nuevosTextos = {}
  mensajesValidos.forEach((mensaje, index) => {
    const actor = actores[index]
    nuevosTextos[mensaje.id] = actor?.usuario_nombre || `Casilla ${mensaje.casilla_destino_id}`
  })

  paraTextoByMensajeId.value = nuevosTextos
}

watch(
  () => props.mensajes.map(m => `${m.id}:${m.casilla_destino_id}`).join('|'),
  () => {
    cargarDestinatarios(props.mensajes)
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

.para-destinatario {
  display: inline-block;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: bottom;
}
</style>
