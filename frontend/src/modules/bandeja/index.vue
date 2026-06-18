<template>
  <div ref="containerRef" class="bandeja-layout d-flex position-relative w-100">

    <!-- Panel izquierdo (Filtro + Lista) -->
    <div
      ref="leftPanel"
      :style="{ width: leftWidth + 'px' }"
      class="border-end pe-3 bg-white d-flex flex-column h-100 overflow-hidden"
    >
      <Filtro
        :key="props.trayType"
        @buscar="handleBuscar"
      />

      <!-- La lista toma el alto restante del panel izquierdo. -->
      <Lista
        class="flex-grow-1 min-h-0"
        :mensajes="mensajesFiltrados"
        :pagination="store.pagination"
        :selected="selected"
        @seleccionar="seleccionarMensaje"
        @page-change="handlePageChange"
        @items-per-page-change="handleSizeChange"
        @sort="handleSort"
      />
    </div>

    <!-- Separador -->
    <div
      ref="resizer"
      class="resizer"
      @mousedown="startResizing"
    ></div>

    <!-- Panel derecho (Contenido) -->
    <div class="flex-grow-1 ps-3 bg-white h-100 overflow-auto">
      <Contenido
        :mensaje="selected"
        :tray-type="trayType"
        @cerrar="selected = null"
        @mensaje-cambiado="refrescarBandeja"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount, computed, onMounted, watch } from 'vue'
import Filtro from './Filtro.vue'
import Lista from './Lista.vue'
import Contenido from './Contenido.vue'

import { useMensajesStore } from '@/stores/mensajes/mensajesStore'

const props = defineProps({
  trayType : {
    type    : String,
    default : 'entrada'
  }
})

// Store central de mensajes para bandeja de entrada.
const store = useMensajesStore()

// Mensaje activo mostrado en el panel derecho.
const selected = ref(null)

// Estado local de filtros de la bandeja (para backend).
const queryFilters = ref({})
const sortParams = ref({
  sort  : 'created_at',
  order : 'desc'
})

// Carga inicial de mensajes al montar la vista.
onMounted(async () => {
  await cargarBandeja()
})

watch(() => props.trayType, async () => {
  selected.value = null
  queryFilters.value = {}
  sortParams.value = { sort: 'created_at', order: 'desc' }
  await cargarBandeja({ page: 1 })
})

// Retorna directamente los mensajes del store (ya filtrados por el backend).
const mensajesFiltrados = computed(() => store.mensajes)

// Selecciona mensaje y marca como leido si aun no fue abierto.
function seleccionarMensaje(mensaje) {
  selected.value = mensaje

  if (!mensaje.leido) {
    store.marcarLeido(mensaje.id)
  }
}

async function cargarBandeja(extraParams = {}) {
  const params = {
    page     : extraParams.page || store.pagination.current_page || 1,
    per_page : extraParams.per_page || store.pagination.per_page || 10,
    ...queryFilters.value,
    ...sortParams.value
  }

  await store.fetchMensajes(props.trayType, params)
  await store.fetchCounts()
}

async function refrescarBandeja() {
  await cargarBandeja({ page: 1, per_page: store.pagination.per_page })
  selected.value = null
}

async function handlePageChange(page) {
  await cargarBandeja({ page })
}

async function handleSizeChange(perPage) {
  await cargarBandeja({ page: 1, per_page: perPage })
}

async function handleBuscar(filtros) {
  queryFilters.value = filtros
  await cargarBandeja({ page: 1 })
}

async function handleSort({ label, order }) {
  sortParams.value = {
    sort  : label,
    order : order
  }
  await cargarBandeja({ page: 1 })
}

// Referencias y estado para redimensionar paneles.
const containerRef = ref(null)
const leftPanel = ref(null)
const resizer = ref(null)
const leftWidth = ref(400) // ancho inicial en px

let isResizing = false

// Inicia el modo de redimension y sus listeners globales.
function startResizing() {
  isResizing = true
  document.addEventListener('mousemove', resize)
  document.addEventListener('mouseup', stopResizing)
}

// Ajusta ancho del panel izquierdo dentro de limites operativos.
function resize(e) {
  if (!isResizing || !containerRef.value) return
  const containerLeft = containerRef.value.getBoundingClientRect().left
  const newWidth = e.clientX - containerLeft
  if (newWidth >= 200 && newWidth <= 800) {
    leftWidth.value = newWidth
  }
}

// Finaliza redimension y libera listeners para evitar fugas.
function stopResizing() {
  isResizing = false
  document.removeEventListener('mousemove', resize)
  document.removeEventListener('mouseup', stopResizing)
}

// Limpieza de listeners al desmontar la vista.
onBeforeUnmount(() => stopResizing())
</script>

<style scoped>
/* Usa toda la altura util del viewport dentro del layout (sin fullscreen real). */
.bandeja-layout {
  height: calc(100dvh - 145px);
  min-height: 540px;
}

.resizer {
  width: 6px;
  cursor: col-resize;
  background-color: #e0e0e0;
  position: relative;
  z-index: 10;
}
</style>
