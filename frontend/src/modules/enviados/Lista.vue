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
      <td class="align-middle py-3 px-4 position-relative border-bottom">
        <!-- Sent green indicator bar -->
        <div class="sent-indicator"></div>
        
        <div class="d-flex align-items-center gap-3">
          <!-- Initials Avatar -->
          <div class="avatar-circle shadow-sm" :style="getAvatarStyle(getParaTexto(item))">
            {{ getInitials(getParaTexto(item)) }}
          </div>
          
          <!-- Message text details -->
          <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-bold text-dark text-uppercase fs-7 para-destinatario" :title="getParaTexto(item)">
                Para: {{ getParaTexto(item) }}
              </span>
              <span class="text-muted small text-nowrap ms-2">
                <i class="bi bi-clock me-1"></i>{{ formatDate(item.created_at) }}
              </span>
            </div>
            <div class="text-truncate text-muted" style="font-size: 13px;">
              {{ item.asunto }}
            </div>
          </div>
        </div>
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

const sortLabel = ref('created_at')
const sortOrder = ref('desc')
const paraTextoByMensajeId = ref({})
let cargaActual = 0

const columns = ref([
  { columnName: 'Mensaje Enviado', columnLabel: 'asunto', sortEnabled: false, width: '100%' }
])

const selectedItems = computed(() => (props.selected ? [props.selected] : []))
const pagination = computed(() => props.pagination)
const mensajesOrdenados = computed(() => props.mensajes)

function handleSeleccion({ item }) {
  if (!item) return
  emit('seleccionar', item)
}

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

function formatDate(fechaStr) {
  return formatDateTimeLima(fechaStr)
}

function getParaTexto(item) {
  return paraTextoByMensajeId.value[item.id] || `Casilla ${item.casilla_destino_id}`
}

function getAvatarStyle(name) {
  const colors = [
    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
    '#5a5c69', '#fd7e14', '#6f42c1', '#e83e8c', '#20c997'
  ];
  let hash = 0;
  const str = String(name || '');
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash);
  }
  const index = Math.abs(hash) % colors.length;
  return {
    backgroundColor: colors[index]
  };
}

function getInitials(name) {
  const str = String(name || '').trim();
  if (!str || str.startsWith('Casilla')) return 'C';
  const parts = str.split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return str[0].toUpperCase();
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
  border-collapse: separate;
  border-spacing: 0;
}

:deep(thead) {
  display: none;
}

:deep(tbody tr) {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

:deep(tbody tr:hover) {
  background-color: #f8f9fa !important;
}

:deep(tbody tr.table-success) {
  background-color: #e8f0fe !important;
}

:deep(tbody tr.table-success .text-dark),
:deep(tbody tr.table-success .text-muted) {
  color: #1a73e8 !important;
}

.sent-indicator {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: #1cc88a;
}

.avatar-circle {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  color: white;
  flex-shrink: 0;
  text-transform: uppercase;
}

.para-destinatario {
  display: inline-block;
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
