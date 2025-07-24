<template>
  <div ref="containerRef" class="d-flex flex-column" style="height: 100%;"> 

    <!-- Fila superior con Filtro + Botón Nuevo mensaje -->
    <div class="d-flex justify-content-between align-items-center p-3">
      <Filtro
        :filtro="filtro"
        :orden="orden"
        class="flex-grow-1"
        @update-filtro="filtro = $event"
        @update-orden="orden = $event"
      />
      <button 
        class="btn btn-primary ms-3" 
        @click="nuevoMensaje"
      >
        Nuevo mensaje
      </button>
    </div>

    <!-- Contenedor principal para los paneles -->
    <div class="d-flex" style="flex-grow: 1;">
      <!-- Panel izquierdo (Filtro + Lista) -->
      <div ref="leftPanel" :style="{ width: leftWidth + 'px' }" class="border-end pe-3 bg-white">
        <Lista :mensajes="mensajesFiltrados" :selected="selected" @seleccionar="seleccionarMensaje" />
      </div>

      <!-- Separador -->
      <div
        ref="resizer"
        class="resizer"
        @mousedown="startResizing"
      ></div>

      <!-- Panel derecho (Contenido) -->
      <div class="flex-grow-1 ps-3 bg-white">
        <Contenido :mensaje="selected" @cerrar="selected = null" />
      </div>
    </div>

    <!-- Modal para redactar nuevo mensaje -->
    <Formulario
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

const store = useMensajesStore()

const item = ref(store.default)
const formularioRef = useTemplateRef('formularioRef')

onMounted(async () => {
  await store.fetchMensajes('enviados');  // Cargar mensajes de la bandeja de enviados
  console.log(store.mensajes);
})

const selected = ref(null)

const filtro = ref('')
const orden = ref('fecha')

const mensajesFiltrados = computed(() => {
  return store.mensajes
    .filter(m => m.asunto.toLowerCase().includes(filtro.value.toLowerCase()))
    .sort((a, b) => {
      if (orden.value === 'fecha') return new Date(b.fecha_envio) - new Date(a.fecha_envio)
      if (orden.value === 'asunto') return a.asunto.localeCompare(b.asunto)
      return 0
    })
})

function seleccionarMensaje(mensaje) {
  selected.value = mensaje
}

function nuevoMensaje() {
  item.value = { ...store.default }
  formularioRef.value.abrir()
}

/* function editarMensaje(rowItem) {
  if (!rowItem) return
  item.value = { ...store.default, ...rowItem }
  formularioRef.value.abrir()
} */

// Resize logic
const containerRef = ref(null)
const leftWidth = ref(400) // ancho inicial en px

let isResizing = false

function startResizing() {
  isResizing = true
  document.addEventListener('mousemove', resize)
  document.addEventListener('mouseup', stopResizing)
}

function resize(e) {
  if (!isResizing || !containerRef.value) return
  const containerLeft = containerRef.value.getBoundingClientRect().left
  const newWidth = e.clientX - containerLeft
  if (newWidth >= 200 && newWidth <= 800) {
    leftWidth.value = newWidth
  }
}

function stopResizing() {
  isResizing = false
  document.removeEventListener('mousemove', resize)
  document.removeEventListener('mouseup', stopResizing)
}
</script>

<style scoped>
.resizer {
  width: 6px;
  cursor: col-resize;
  background-color: #e0e0e0;
  position: relative;
  z-index: 10;
}
</style>
