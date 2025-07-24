<template>
  <div v-if="props.mensaje" class="position-relative p-4">
    <!-- Botón para cerrar -->
    <button
      class="btn btn-sm btn-light position-absolute top-0 end-0 m-2"
      @click="$emit('cerrar')"
    >
      ❌
    </button>

    <!-- Cabecera -->
    <div class="border-bottom pb-3 mb-3">
      <h4 class="mb-1">{{ props.mensaje.asunto }}</h4>
      <div class="text-muted small">
        <span><strong>De:</strong> Yo, noreply</span><br />
        <span><strong>Para:</strong> Usuario destinatario (ID: {{ props.mensaje.usuario_destino_id }})</span><br />
        <span><strong>Fecha:</strong> {{ formatFecha(props.mensaje.fecha_envio) }}</span><br />
        <span><strong>Prioridad:</strong>
          <span
            :class="{
              'badge bg-danger': props.mensaje.prioridad === 1,
              'badge bg-warning text-dark': props.mensaje.prioridad === 2,
              'badge bg-secondary': props.mensaje.prioridad === 3
            }"
          >
            {{ prioridadTexto(props.mensaje.prioridad) }}
          </span>
        </span>
      </div>
    </div>
    <div v-if="props.mensaje.archivos.length > 0" class="mb-3 px-5">
      <div class="d-flex flex-wrap gap-2">
        <div
          v-for="(file, index) in props.mensaje.archivos"
          :key="file.id"
          class="card p-2 mb-2 bg-light bg-opacity-50"
          style="width: 200px; max-height: 200px; overflow: hidden;"
        >
          <div class="d-flex align-items-center">
            <div class="badge text-white text-uppercase fw-bold py-3" :class="getFileIconClass(file)">
              {{ file.extension }}
            </div>
            <div class="ms-2">
              <p class="text-truncate text-muted m-0" style="font-size: 12px; max-width: 140px;" :title="file.nombre_original">
                {{ file.nombre_original }}
              </p>
              <p class="d-flex justify-content-between m-0 w-100">
                <span class="text-muted" style="font-size: 10px;">{{ formatFileSize(file.tamanio) }}</span>
                <a class="text-danger ms-2 px-2" style="cursor: pointer; font-size: 11px;" @click="downloadUploadedFile(index)">
                  <i class="bi bi-x-circle"></i>
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenido HTML -->
    <div class="mb-4 border rounded p-3 bg-light" v-html="props.mensaje.contenido"></div>
  </div>

  <div v-else class="text-muted text-center mt-5">
    <i>Selecciona un mensaje para ver su contenido.</i>
  </div>
</template>

<script setup>
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

const props = defineProps({
  mensaje : {
    type    : Object,
    default : null
  }
})
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

const formatFileSize = (bytes) => {
  const kb = bytes / 1024
  return kb < 1024 ? `${kb.toFixed(2)} KB` : `${(kb / 1024).toFixed(2)} MB`
}
const getFileIconClass = (file) => {
  const classes = {
    ods     : 'bg-success', doc     : 'bg-primary', docx    : 'bg-primary',
    xls     : 'bg-success', xlsx    : 'bg-success', ppt     : 'bg-warning',
    pptx    : 'bg-warning', mp4     : 'bg-primary', pdf     : 'bg-warning',
    jpg     : 'bg-info', png     : 'bg-info', unknown : 'bg-danger',
  }
  return classes[file.extension] || 'bg-dark'
}

const downloadUploadedFile = (index) => {
  const file = props.mensaje.archivos[index]
  window.open(file.url, '_blank')
}
</script>
