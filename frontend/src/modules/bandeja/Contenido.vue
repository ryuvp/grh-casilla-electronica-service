<template>
  <div v-if="mensaje" class="position-relative p-4">
    <div class="position-absolute top-0 end-0 m-2 d-flex align-items-center gap-2">
      <button
        v-if="canManageMensaje"
        class="btn btn-sm btn-light"
        :title="mensaje.destacado ? 'Quitar de destacados' : 'Agregar a destacados'"
        @click="toggleDestacado"
      >
        <i :class="mensaje.destacado ? 'bi bi-star-fill text-warning' : 'bi bi-star'"></i>
      </button>
      <button
        v-if="canManageMensaje"
        class="btn btn-sm btn-light"
        :title="mensaje.archivado ? 'Desarchivar' : 'Archivar'"
        @click="toggleArchivado"
      >
        <i :class="mensaje.archivado ? 'bi bi-archive-fill text-primary' : 'bi bi-archive'"></i>
      </button>
      <button
        class="btn btn-sm btn-light"
        @click="$emit('cerrar')"
      >
        ❌
      </button>
    </div>

    <!-- Cabecera -->
    <div class="border-bottom pb-3 mb-3">
      <h4 class="mb-1">{{ mensaje.asunto }}</h4>
      <div class="text-muted small">
        <span><strong>De:</strong> {{ deTexto }}</span><br />
        <span><strong>Para:</strong> {{ paraTexto }}</span><br />
        <span><strong>Fecha:</strong> {{ formatFecha(mensaje.created_at) }}</span><br />
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
import { computed, ref, watch } from 'vue'
import Swal from 'sweetalert2'
import useDesignacionStore from '@/stores/designaciones/designacionStore'
import useAuthStore from '@/stores/auth/authStore'
import { useMensajesStore } from '@/stores/mensajes/mensajesStore'
import { formatDateTimeLima } from '@/core/utils/dateTime'

const props = defineProps({
  mensaje : {
    type    : Object,
    default : null
  },
  trayType : {
    type    : String,
    default : 'entrada'
  }
})
const emit = defineEmits(['cerrar', 'mensaje-cambiado'])

const designacionStore = useDesignacionStore()
const authStore = useAuthStore()
const mensajesStore = useMensajesStore()
const deTexto = ref('No disponible')
const paraTexto = ref('No disponible')
const canManageMensaje = computed(() => {
  const permissionNames = authStore.permisosAccion.map((permiso) => permiso.name || permiso.nombre || '')
  return props.trayType !== 'enviados' && permissionNames.includes('mensaje.destacar') && permissionNames.includes('mensaje.archivar')
})

async function cargarActores() {
  const mensaje = props.mensaje
  if (!mensaje) {
    deTexto.value = 'No disponible'
    paraTexto.value = 'No disponible'
    return
  }

  const [origen, destino] = await Promise.all([
    designacionStore.resolveActorByCasillaId(mensaje.casilla_origen_id),
    designacionStore.resolveActorByCasillaId(mensaje.casilla_destino_id),
  ])

  // Evita pintar datos obsoletos si el usuario cambio de mensaje durante la carga.
  if (props.mensaje?.id !== mensaje.id) return

  deTexto.value = origen?.display_name || `Casilla ${mensaje.casilla_origen_id}`
  paraTexto.value = destino?.display_name || `Casilla ${mensaje.casilla_destino_id}`
}

watch(() => props.mensaje?.id, () => {
  cargarActores()
}, { immediate: true })

function formatFecha(fechaStr) {
  return formatDateTimeLima(fechaStr)
}

function prioridadTexto(prioridad) {
  switch (prioridad) {
  case 1: return 'Alta'
  case 2: return 'Media'
  case 3: return 'Baja'
  default: return 'N/D'
  }
}

async function toggleDestacado() {
  if (!props.mensaje?.id) return

  try {
    await mensajesStore.toggleDestacado(props.mensaje.id)
    await mensajesStore.fetchCounts()
    emit('mensaje-cambiado')
  } catch (error) {
    console.error('Error alternando destacado:', error)
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el destacado.' })
  }
}

async function toggleArchivado() {
  if (!props.mensaje?.id) return

  try {
    await mensajesStore.toggleArchivado(props.mensaje.id)
    await mensajesStore.fetchCounts()
    emit('mensaje-cambiado')
  } catch (error) {
    console.error('Error alternando archivado:', error)
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el archivado.' })
  }
}
</script>

<style scoped>
h4 {
  font-weight: 600;
}
</style>
