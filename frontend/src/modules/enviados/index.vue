<template>
  <div ref="containerRef" class="enviados-layout d-flex flex-column w-100">

    <!-- Fila superior con Filtro + Botón Nuevo mensaje -->
    <div class="d-flex justify-content-between align-items-center p-3">
      <Filtro
        :filtro="filtro"
        class="flex-grow-1"
        @update-filtro="filtro = $event"
      />
      <button
        v-if="canWriteNotifications"
        class="btn btn-primary ms-3" 
        @click="nuevoMensaje"
      >
        Nuevo mensaje
      </button>
    </div>

    <!-- Contenedor principal para los paneles -->
    <div class="d-flex flex-grow-1 min-h-0">
      <!-- Panel izquierdo (Filtro + Lista) -->
      <div
        ref="leftPanel"
        :style="{ width: leftWidth + 'px' }"
        class="border-end pe-3 bg-white d-flex flex-column h-100 overflow-hidden"
      >
        <Lista
          class="flex-grow-1 min-h-0"
          :mensajes="mensajesFiltrados"
          :pagination="store.pagination"
          :selected="selected"
          @seleccionar="seleccionarMensaje"
          @page-change="handlePageChange"
          @items-per-page-change="handleSizeChange"
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
        <Contenido :mensaje="selected" @cerrar="selected = null" />
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
import { ref, computed, onMounted, onBeforeUnmount, useTemplateRef } from 'vue'
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

// Carga inicial de mensajes enviados.
onMounted(async () => {
  await store.fetchMensajes('enviados', { page: 1, per_page: store.pagination.per_page || 10 })
  await store.fetchCounts()
})

// Solo perfiles admin/notificador pueden emitir notificaciones.
const canWriteNotifications = computed(() => authStore.canWriteNotifications)

// Mensaje seleccionado para detalle.
const selected = ref(null)

// Estado local de filtros del listado.
const filtro = ref('')

// Filtra mensajes por asunto; el orden lo gestiona TablaBackend.
const mensajesFiltrados = computed(() => {
  return store.mensajes
    .filter(m => m.asunto.toLowerCase().includes(filtro.value.toLowerCase()))
})

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

async function handlePageChange(page) {
  await store.fetchMensajes('enviados', { page, per_page: store.pagination.per_page || 10 })
}

async function handleSizeChange(perPage) {
  await store.fetchMensajes('enviados', { page: 1, per_page: perPage })
}

// Referencia del contenedor y ancho base del panel izquierdo.
const containerRef = ref(null)
const leftPanel = ref(null)
const resizer = ref(null)
const leftWidth = ref(400) // ancho inicial en px

let isResizing = false

// Inicia redimension y listeners globales de mouse.
function startResizing() {
  isResizing = true
  document.addEventListener('mousemove', resize)
  document.addEventListener('mouseup', stopResizing)
}

// Recalcula ancho del panel izquierdo dentro de limites definidos.
function resize(e) {
  if (!isResizing || !containerRef.value) return
  const containerLeft = containerRef.value.getBoundingClientRect().left
  const newWidth = e.clientX - containerLeft
  if (newWidth >= 200 && newWidth <= 800) {
    leftWidth.value = newWidth
  }
}

// Finaliza redimension y limpia listeners.
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
.enviados-layout {
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
