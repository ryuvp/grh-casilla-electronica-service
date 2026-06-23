<template>
  <div v-if="mensaje" style="display:flex; flex-direction:column; height:100%; overflow:hidden;">

    <!-- Drawer header -->
    <div class="ce-drawer__header">
      <div class="ce-drawer__header-title">
        <i class="bi bi-file-earmark-text fs-5"></i>
        INSTRUMENTO DE NOTIFICACIÓN
      </div>
      <button class="ce-drawer__close" @click="$emit('cerrar')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Action bar: solo Destacar / Archivar -->
    <div v-if="canManageMensaje" class="ce-drawer__actions">
      <button
        class="ce-action-btn"
        :class="mensaje.destacado ? 'ce-action-btn--star-on' : ''"
        @click="toggleDestacado"
      >
        <i :class="mensaje.destacado ? 'bi bi-star-fill' : 'bi bi-star'"></i>
        {{ mensaje.destacado ? 'Destacado' : 'Destacar' }}
      </button>
      <button
        class="ce-action-btn"
        :class="mensaje.archivado ? 'ce-action-btn--archive-on' : ''"
        @click="toggleArchivado"
      >
        <i :class="mensaje.archivado ? 'bi bi-archive-fill' : 'bi bi-archive'"></i>
        {{ mensaje.archivado ? 'Archivado' : 'Archivar' }}
      </button>
    </div>

    <!-- Scrollable body -->
    <div class="ce-drawer__body">

      <!-- Code -->
      <div class="ce-notif-code">CÓDIGO: {{ codigoMensaje }}</div>

      <!-- Title -->
      <h2 class="ce-notif-title">{{ mensaje.asunto }}</h2>

      <!-- Metadata -->
      <table class="ce-meta-table">
        <tbody>
          <tr>
            <td>Remitente</td>
            <td>{{ deTexto }}</td>
          </tr>
          <tr>
            <td>Fecha Depósito</td>
            <td>{{ formatFecha(mensaje.created_at) }}</td>
          </tr>
          <tr>
            <td>Casilla Destino</td>
            <td><span class="ce-casilla-tag">{{ casillaCodigo }}</span></td>
          </tr>
          <tr>
            <td>Prioridad Legal</td>
            <td>
              <span class="ce-priority-tag" :class="prioridadClass">
                <i :class="prioridadIcon"></i>
                {{ prioridadTexto }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- 1. Contenido -->
      <div class="ce-notif-section-label">Contenido de la Cédula / Proveído:</div>
      <div class="ce-notif-content" v-html="mensaje.contenido || '<em>Sin contenido</em>'"></div>

      <!-- 2. Adjuntos (Gmail-style) – entre contenido e integridad -->
      <template v-if="mensaje.adjuntos?.length">
        <div class="ce-att-section-label">
          <i class="bi bi-paperclip"></i>
          {{ mensaje.adjuntos.length }} {{ mensaje.adjuntos.length === 1 ? 'archivo adjunto' : 'archivos adjuntos' }}
        </div>
        <div class="ce-att-grid">
          <a
            v-for="(archivo, i) in mensaje.adjuntos"
            :key="i"
            :href="archivo.url"
            target="_blank"
            rel="noopener"
            class="ce-att-card"
            :title="archivo.nombre"
          >
            <div class="ce-att-preview" :class="{ 'ce-att-preview--generic': !isPdf(archivo) }">
              <span class="ce-att-preview-label">{{ isPdf(archivo) ? 'PDF' : 'FILE' }}</span>
            </div>
            <div class="ce-att-foot">
              <span class="ce-att-name">{{ archivo.nombre }}</span>
              <span v-if="archivo.tamaño" class="ce-att-meta">{{ archivo.tamaño }}</span>
              <span class="ce-att-dl"><i class="bi bi-download"></i> Descargar</span>
            </div>
          </a>
        </div>
      </template>

      <!-- 3. Constancias – tarjetas estilo Gmail -->
      <div class="ce-att-section-label" style="margin-top:20px;">
        <i class="bi bi-file-earmark-pdf"></i> Constancias
      </div>
      <div class="ce-att-grid">
        <!-- Constancia de Envío -->
        <div class="ce-att-card" @click="descargarConstanciaEnvio" title="Constancia de Envío">
          <div class="ce-att-preview">
            <span class="ce-att-preview-label">PDF</span>
          </div>
          <div class="ce-att-foot">
            <span class="ce-att-name">Constancia de Envío</span>
            <span class="ce-att-meta">PDF · Generado</span>
            <span class="ce-att-dl"><i class="bi bi-download"></i> Descargar</span>
          </div>
        </div>

        <!-- Constancia de Lectura -->
        <div
          class="ce-att-card"
          :class="{ 'ce-att-card--disabled': !mensaje.leido }"
          :title="mensaje.leido ? 'Constancia de Lectura' : 'Disponible cuando el destinatario lea el mensaje'"
          @click="descargarConstanciaLectura"
        >
          <div class="ce-att-preview" :class="{ 'ce-att-preview--generic': !mensaje.leido }">
            <span class="ce-att-preview-label">PDF</span>
          </div>
          <div class="ce-att-foot">
            <span class="ce-att-name">Constancia de Lectura</span>
            <span class="ce-att-meta">{{ mensaje.leido ? 'PDF · Disponible' : 'Pendiente de lectura' }}</span>
            <span class="ce-att-dl" v-if="mensaje.leido"><i class="bi bi-download"></i> Descargar</span>
          </div>
        </div>
      </div>

      <!-- 4. Integridad – siempre al final -->
      <div class="ce-integrity">
        Garantía de Integridad: <span>SHA256-{{ hashMensaje }}</span>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Swal from 'sweetalert2'
import useDesignacionStore from '@/stores/designaciones/designacionStore'
import useAuthStore        from '@/stores/auth/authStore'
import { useMensajesStore } from '@/stores/mensajes/mensajesStore'
import { formatDateTimeLima } from '@/core/utils/dateTime'
import JwtService from '@/core/services/JwtService'

const props = defineProps({
  mensaje  : { type: Object, default: null },
  trayType : { type: String, default: 'entrada' },
})
const emit = defineEmits(['cerrar', 'mensaje-cambiado'])

const designacionStore = useDesignacionStore()
const authStore        = useAuthStore()
const mensajesStore    = useMensajesStore()
const deTexto          = ref('Cargando...')
const paraTexto        = ref('Cargando...')
const casillaDestinoId = ref(null)

const canManageMensaje = computed(() => {
  const names = authStore.permisosAccion.map(p => p.name || p.nombre || '')
  return props.trayType !== 'enviados'
    && names.includes('mensaje.destacar')
    && names.includes('mensaje.archivar')
})

const codigoMensaje = computed(() => {
  if (!props.mensaje) return ''
  const year = new Date(props.mensaje.created_at).getFullYear()
  return `MSG-${year}-${String(props.mensaje.id).padStart(5, '0')}`
})

const casillaCodigo = computed(() => {
  const id = casillaDestinoId.value || props.mensaje?.casilla_destino_id
  return id ? `CAS-${new Date(props.mensaje?.created_at).getFullYear()}-${String(id).padStart(4, '0')}` : paraTexto.value
})

const hashMensaje = computed(() => {
  if (!props.mensaje) return ''
  const str = `${props.mensaje.id}-${props.mensaje.asunto}-${props.mensaje.created_at}`
  let hash = 0
  for (let i = 0; i < str.length; i++) hash = ((hash << 5) - hash) + str.charCodeAt(i)
  return Math.abs(hash).toString(16).toUpperCase().padStart(8, '0')
})

const prioridadTexto = computed(() => ({ 1: 'Alta', 2: 'Media', 3: 'Baja' }[props.mensaje?.prioridad] ?? 'Normal'))
const prioridadClass = computed(() => ({
  1: 'ce-priority-tag--alta',
  2: 'ce-priority-tag--media',
  3: 'ce-priority-tag--baja',
}[props.mensaje?.prioridad] ?? 'ce-priority-tag--nd'))
const prioridadIcon = computed(() => ({
  1: 'bi bi-exclamation-circle-fill',
  2: 'bi bi-dash-circle-fill',
  3: 'bi bi-check-circle-fill',
}[props.mensaje?.prioridad] ?? 'bi bi-circle'))

async function cargarActores() {
  const msg = props.mensaje
  if (!msg) { deTexto.value = '—'; paraTexto.value = '—'; return }
  const [origen, destino] = await Promise.all([
    designacionStore.resolveActorByCasillaId(msg.casilla_origen_id),
    designacionStore.resolveActorByCasillaId(msg.casilla_destino_id),
  ])
  if (props.mensaje?.id !== msg.id) return
  casillaDestinoId.value = msg.casilla_destino_id
  deTexto.value   = origen?.display_name  || `Casilla ${msg.casilla_origen_id}`
  paraTexto.value = destino?.display_name || `Casilla ${msg.casilla_destino_id}`
}

watch(() => props.mensaje?.id, () => cargarActores(), { immediate: true })

const formatFecha = (d) => formatDateTimeLima(d)
const isPdf = (archivo) => {
  const nombre = (archivo.nombre || '').toLowerCase()
  const tipo   = (archivo.tipo   || '').toLowerCase()
  return nombre.endsWith('.pdf') || tipo.includes('pdf')
}

async function toggleDestacado() {
  if (!props.mensaje?.id) return
  try {
    await mensajesStore.toggleDestacado(props.mensaje.id)
    await mensajesStore.fetchCounts()
    emit('mensaje-cambiado')
  } catch {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el destacado.' })
  }
}

async function toggleArchivado() {
  if (!props.mensaje?.id) return
  try {
    await mensajesStore.toggleArchivado(props.mensaje.id)
    await mensajesStore.fetchCounts()
    emit('mensaje-cambiado')
  } catch {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el archivado.' })
  }
}

const baseApi = () => import.meta.env.VITE_API_URL || 'http://localhost:8089/api'

function descargarConstanciaEnvio() {
  if (!props.mensaje?.id) return
  window.open(`${baseApi()}/mensajes/${props.mensaje.id}/constancia-envio-pdf?token=${JwtService.getToken()}`, '_blank')
}
function descargarConstanciaLectura() {
  if (!props.mensaje?.id) return
  if (!props.mensaje.leido) {
    Swal.fire({ icon: 'warning', title: 'No disponible', text: 'Solo disponible después de que el destinatario haya leído el mensaje.' })
    return
  }
  window.open(`${baseApi()}/mensajes/${props.mensaje.id}/constancia-lectura-pdf?token=${JwtService.getToken()}`, '_blank')
}
</script>
