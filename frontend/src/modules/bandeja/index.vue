<template>
  <div ref="containerRef" class="bandeja-layout d-flex flex-column position-relative w-100 h-100">

    <!-- El Filtro siempre arriba -->
    <Filtro
      :key="props.trayType"
      @buscar="handleBuscar"
    />

    <!-- Contenedor principal para los paneles -->
    <div class="d-flex flex-grow-1 min-h-0 w-100 position-relative">
      <!-- Panel izquierdo (Lista) -->
      <div
        v-show="!selected"
        class="w-100 d-flex flex-column h-100 overflow-hidden bg-white"
      >
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

      <!-- Panel derecho (Contenido) -->
      <div v-if="selected" class="w-100 bg-white h-100 d-flex flex-column overflow-hidden">
        <!-- Botón Volver a la lista -->
        <div class="px-4 pt-3 pb-2 border-bottom bg-light bg-opacity-50 flex-shrink-0">
          <button class="btn btn-sm btn-light border px-4" @click="selected = null">
            <i class="bi bi-arrow-left me-1"></i> Volver a la bandeja
          </button>
        </div>
        <div class="flex-grow-1 overflow-auto">
          <Contenido
            :mensaje="selected"
            :tray-type="trayType"
            @cerrar="selected = null"
            @mensaje-cambiado="refrescarBandeja"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
</script>

<style scoped>
/* Usa toda la altura util del viewport dentro del layout (sin fullscreen real). */
.bandeja-layout {
  height: calc(100dvh - 145px);
  min-height: 540px;
}
</style>
