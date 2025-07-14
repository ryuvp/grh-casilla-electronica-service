<template>
  <div v-if="mensaje" class="position-relative p-4">
    <!-- Botón para cerrar -->
    <button
      class="btn btn-sm btn-light position-absolute top-0 end-0 m-2"
      @click="$emit('cerrar')"
    >
      ❌
    </button>

    <!-- Cabecera -->
    <div class="border-bottom pb-3 mb-3">
      <h4 class="mb-1">{{ mensaje.asunto }}</h4>
      <div class="text-muted small">
        <span><strong>De:</strong> Yo, noreply</span><br />
        <span><strong>Para:</strong> Usuario destinatario (ID: {{ mensaje.usuario_destino_id }})</span><br />
        <span><strong>Fecha:</strong> {{ formatFecha(mensaje.fecha_envio) }}</span><br />
        <span><strong>Prioridad:</strong>
          <span
            :class="{
              'badge bg-danger': mensaje.prioridad === 1,
              'badge bg-warning text-dark': mensaje.prioridad === 2,
              'badge bg-secondary': mensaje.prioridad === 3
            }"
          >
            {{ prioridadTexto(mensaje.prioridad) }}
          </span>
        </span>
      </div>
    </div>

    <!-- Contenido HTML -->
    <div class="mb-4 border rounded p-3 bg-light" v-html="mensaje.contenido"></div>

    <!-- Archivos adjuntos -->
    <div v-if="mensaje.adjuntos && mensaje.adjuntos.length">
      <h6 class="fw-semibold">📎 Archivos adjuntos</h6>
      <ul class="list-group">
        <li
          v-for="(archivo, index) in mensaje.adjuntos"
          :key="index"
          class="list-group-item d-flex justify-content-between align-items-center"
        >
          <span>
            <i class="bi bi-paperclip me-2"></i>{{ archivo.nombre }}
          </span>
          <a
            :href="archivo.url"
            class="btn btn-sm btn-outline-primary"
            target="_blank"
            rel="noopener noreferrer"
          >
            Descargar
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div v-else class="text-muted text-center mt-5">
    <i>Selecciona un mensaje para ver su contenido.</i>
  </div>
</template>

<script setup>
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

defineProps(['mensaje'])
defineEmits(['cerrar'])

function formatFecha(fechaStr) {
  if (!fechaStr) return ''
  return format(new Date(fechaStr), 'dd/MM/yyyy HH:mm', { locale: es })
}

function prioridadTexto(prioridad) {
  switch (prioridad) {
  case 1: return 'Alta'
  case 2: return 'Media'
  case 3: return 'Baja'
  default: return 'N/D'
  }
}
</script>

<style scoped>
h4 {
  font-weight: 600;
}
</style>
