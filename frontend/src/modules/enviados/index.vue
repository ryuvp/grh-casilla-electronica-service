<template>
  <div ref="containerRef" class="enviados-layout d-flex flex-column w-100 h-100">

    <!-- Fila superior con Filtro + Botón Nuevo mensaje -->
    <div class="d-flex justify-content-between align-items-center p-3 bg-white flex-shrink-0">
      <Filtro
        @buscar="handleBuscar"
        class="flex-grow-1"
      />
      <button
        v-show="!selected && canWriteNotifications"
        class="btn btn-primary ms-3" 
        @click="nuevoMensaje"
      >
        Nuevo mensaje
      </button>
    </div>

    <!-- Contenedor principal para los paneles -->
    <div class="d-flex flex-grow-1 min-h-0">
      <!-- Panel izquierdo (Lista) -->
      <div
        v-show="!selected"
        class="w-100 d-flex flex-column h-100 overflow-hidden bg-white"
      >
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
        <!-- Botón Volver a enviados -->
        <div class="px-4 pt-3 pb-2 border-bottom bg-light bg-opacity-50 flex-shrink-0">
          <button class="btn btn-sm btn-light border px-4" @click="selected = null">
            <i class="bi bi-arrow-left me-1"></i> Volver a enviados
          </button>
        </div>
        <div class="flex-grow-1 overflow-auto">
          <Contenido :mensaje="selected" @cerrar="selected = null" />
        </div>
      </div>
    </div>

    <!-- Modal para redactar nuevo mensaje -->
    <Formulario
      v-if="canWriteNotifications"
      ref="formularioRef"
      :item="item"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, useTemplateRef } from 'vue'
import Filtro from './Filtro.vue'
import Lista from './Lista.vue'
import Contenido from './Contenido.vue'
import Formulario from './Formulario.vue'
import { useMensajesStore } from '@/stores/mensajes/mensajesStore'
import useAuthStore from '@/stores/auth/authStore'

// Store central de mensajes para bandeja de enviados.
const store = useMensajesStore()
const authStore = useAuthStore()

// Item utilizado por el formulario de nuevo mensaje.
const item = ref(store.default)
const formularioRef = useTemplateRef('formularioRef')

// Estado local de filtros y orden (para backend)
const queryFilters = ref({})
const sortParams = ref({
  sort  : 'created_at',
  order : 'desc'
})

// Carga inicial de mensajes enviados.
onMounted(async () => {
  await cargarBandeja()
})

// Solo perfiles admin/notificador pueden emitir notificaciones.
const canWriteNotifications = computed(() => authStore.canWriteNotifications)

// Mensaje seleccionado para detalle.
const selected = ref(null)

// Retorna directamente los mensajes del store (ya filtrados por el backend).
const mensajesFiltrados = computed(() => store.mensajes)

// Selecciona mensaje para mostrar su contenido en panel derecho.
function seleccionarMensaje(mensaje) {
  selected.value = mensaje
}

// Prepara formulario para crear un nuevo mensaje.
function nuevoMensaje() {
  if (!canWriteNotifications.value) return
  item.value = { ...store.default }
  formularioRef.value.abrir()
}

async function cargarBandeja(extraParams = {}) {
  const params = {
    page     : extraParams.page || store.pagination.current_page || 1,
    per_page : extraParams.per_page || store.pagination.per_page || 10,
    ...queryFilters.value,
    ...sortParams.value
  }

  await store.fetchMensajes('enviados', params)
  await store.fetchCounts()
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
.enviados-layout {
  height: calc(100dvh - 145px);
  min-height: 540px;
}
</style>
