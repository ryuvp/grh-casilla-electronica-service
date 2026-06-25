<template>
  <div class="referencias-documento d-flex flex-column flex-fill overflow-auto">

    <!-- Mensaje de error en descargas -->
    <div v-if="mensajeError" class="alert alert-danger alert-dismissible fade show m-3" role="alert">
      <i class="bi bi-exclamation-octagon me-2"></i> {{ mensajeError }}
      <button type="button" class="btn-close" @click="mensajeError = ''"></button>
    </div>

    <!-- Estados de carga y error -->
    <div v-if="loading" class="flex-fill d-flex align-items-center justify-content-center p-4">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h6 class="text-body-secondary mb-2">Cargando referencias...</h6>
        <small v-if="props.documentoId" class="text-body-tertiary">ID: {{ props.documentoId }}</small>
      </div>
    </div>

    <div v-else-if="error" class="flex-fill d-flex align-items-center justify-content-center p-4">
      <div class="text-center">
        <div class="mb-3">
          <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
        </div>
        <h6 class="text-body-secondary mb-2">Error al cargar referencias</h6>
        <p class="text-body-tertiary small mb-3">{{ error }}</p>
        <button class="btn btn-sm btn-outline-primary" @click="recargar">
          <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
        </button>
      </div>
    </div>

    <div
      v-else-if="!tieneReferencias && archivosDocumentoPrincipal.length === 0"
      class="flex-fill d-flex align-items-center justify-content-center p-4"
    >
      <div class="text-center">
        <div class="mb-3">
          <i class="bi bi-link-45deg fs-1 text-body-tertiary opacity-50"></i>
        </div>
        <h6 class="text-body-secondary mb-2">Sin referencias ni archivos</h6>
        <p class="text-body-tertiary small">Este documento no tiene referencias ni archivos asociados</p>
      </div>
    </div>

    <!-- Contenido principal -->
    <div v-else class="flex-fill overflow-auto">
      <!-- Resumen -->
      <div class="sticky-top bg-body border-bottom px-3 py-2">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-link-45deg text-primary"></i>
            <span class="fw-semibold small">Referencias</span>
            <small class="text-body-secondary">({{ totalReferencias }})</small>
          </div>
          <button
            class="btn btn-sm btn-outline-secondary py-0 px-1"
            title="Expandir/colapsar todo"
            @click="toggleExpandirTodo"
          >
            <i class="bi small" :class="todoExpandido ? 'bi-arrows-collapse' : 'bi-arrows-expand'"></i>
          </button>
        </div>
      </div>

      <div class="p-3 pt-0">
        <!-- Documento Principal -->
        <div v-if="documentoPrincipal && archivosDocumentoPrincipal.length" class="mb-3">
          <button
            class="btn btn-sm w-100 text-start p-2 border rounded d-flex align-items-center justify-content-between"
            :class="acordeonPrincipalAbierto ? 'btn-primary' : 'btn-outline-primary'"
            @click="acordeonPrincipalAbierto = !acordeonPrincipalAbierto"
          >
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-text flex-shrink-0"></i>
              <span class="fw-medium">Documento Principal</span>
              <small class="opacity-75">({{ archivosDocumentoPrincipal.length }})</small>
            </div>
            <i class="bi flex-shrink-0" :class="acordeonPrincipalAbierto ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
          </button>

          <div v-show="acordeonPrincipalAbierto" class="mt-2 px-1">
            <div class="text-body-secondary small mb-2 ps-1">
              <i class="bi bi-file-earmark-text me-1"></i>{{ documentoPrincipal.nombre || formatearIdDocumento(documentoPrincipal.id) }}
              <div v-if="documentoPrincipal.remitente" class="d-flex flex-wrap gap-3 mt-1" style="font-size:0.72rem">
                <span v-if="documentoPrincipal.remitente.cargo">
                  <i class="bi bi-briefcase me-1"></i>{{ documentoPrincipal.remitente.cargo }}
                </span>
                <span v-if="documentoPrincipal.remitente.dependencia">
                  <i class="bi bi-building me-1"></i>{{ documentoPrincipal.remitente.dependencia }}
                </span>
              </div>
            </div>
            <div class="d-flex flex-column gap-1">
              <!-- Archivo Principal -->
              <button
                v-if="archivoPrincipalDocPrincipal"
                class="btn btn-sm text-start d-flex align-items-center gap-2 w-100"
                :class="getClaseBotonArchivo(archivoPrincipalDocPrincipal)"
                :title="esPdf(archivoPrincipalDocPrincipal) ? 'Ver en visor' : 'Descargar archivo'"
                @click="esPdf(archivoPrincipalDocPrincipal) ? emitAbrirPdf(archivoPrincipalDocPrincipal) : handleDescargarArchivo(archivoPrincipalDocPrincipal)"
              >
                <i class="bi flex-shrink-0" :class="getIconoArchivo(archivoPrincipalDocPrincipal)"></i>
                <span class="text-truncate flex-grow-1 small">{{ archivoPrincipalDocPrincipal.nombre_original || archivoPrincipalDocPrincipal.nombre || archivoPrincipalDocPrincipal.original_name || archivoPrincipalDocPrincipal.file_name || 'Documento' }}</span>
                <span class="badge flex-shrink-0" :class="getBadgeClaseArchivo(archivoPrincipalDocPrincipal)" style="font-size:0.6rem">
                  {{ esPdf(archivoPrincipalDocPrincipal) ? 'PDF' : (archivoPrincipalDocPrincipal.extension?.toUpperCase() || '?') }}
                </span>
              </button>

              <!-- Anexos -->
              <template v-if="anexosDocPrincipal.length > 0">
                <button
                  v-for="(anexo, idx) in anexosDocPrincipal"
                  :key="anexo.id"
                  class="btn btn-sm text-start d-flex align-items-center gap-2 w-100"
                  :class="getClaseBotonArchivo(anexo)"
                  :title="esPdf(anexo) ? 'Ver en visor' : 'Descargar archivo'"
                  @click="esPdf(anexo) ? emitAbrirPdf(anexo) : handleDescargarArchivo(anexo)"
                >
                  <i class="bi flex-shrink-0" :class="getIconoArchivo(anexo)"></i>
                  <span class="text-truncate flex-grow-1 small">{{ anexo.nombre_original || anexo.nombre || anexo.original_name || anexo.file_name || `Anexo ${idx + 1}` }}</span>
                  <span class="badge flex-shrink-0" :class="getBadgeClaseArchivo(anexo)" style="font-size:0.6rem">
                    {{ esPdf(anexo) ? 'PDF' : (anexo.extension?.toUpperCase() || '?') }}
                  </span>
                </button>
              </template>

              <!-- Sin archivos -->
              <div v-if="!archivoPrincipalDocPrincipal && anexosDocPrincipal.length === 0" class="text-muted small text-center py-2">
                <i class="bi bi-file-earmark-x"></i> Sin archivos
              </div>
            </div>
          </div>
        </div>

        <!-- Referencias por tipo -->
        <div
          v-for="{ tipoKey, list } in referenciasPorTipoConContenido"
          :key="tipoKey"
          class="mb-3"
        >
          <button
            class="btn btn-sm w-100 text-start p-2 border rounded d-flex align-items-center justify-content-between"
            :class="acordeonesTipos[tipoKey] ? getBotonActivoClass(tipoKey) : getBotonInactivoClass(tipoKey)"
            @click="toggleTipo(tipoKey)"
          >
            <div class="d-flex align-items-center gap-2">
              <i :class="getIconoTipo(tipoKey)" class="flex-shrink-0"></i>
              <span class="fw-medium">{{ getTituloTipo(tipoKey) }}</span>
              <small class="opacity-75">({{ list.length }})</small>
            </div>
            <i class="bi flex-shrink-0" :class="acordeonesTipos[tipoKey] ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
          </button>

          <div v-show="acordeonesTipos[tipoKey]" class="mt-2">
            <div class="d-flex flex-column gap-2">
              <div v-for="referencia in list" :key="referencia.id" class="border rounded p-2">

                <!-- Header -->
                <div v-if="referencia.tipo === 'ley' && referencia.url" class="d-flex align-items-start gap-1">
                  <i class="bi bi-box-arrow-up-right text-primary flex-shrink-0 mt-1" style="font-size:0.75rem"></i>
                  <a
                    :href="referencia.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="small fw-medium text-decoration-none link-primary text-truncate"
                  >
                    {{ referencia.titulo?.trim() || ('Ley ID: ' + (referencia.referencia_id || 'N/E')) }}
                  </a>
                </div>
                <div v-else class="mb-1">
                  <div v-if="referencia.tipo === 'documento'" class="small fw-semibold text-body">
                    {{ referencia.documento_referencia?.tipo_documento_nombre || formatearIdDocumento(referencia.referencia_id) }}
                    <template v-if="referencia.documento_referencia?.numero"> - {{ referencia.documento_referencia.numero }}</template>
                    <template v-if="referencia.documento_referencia?.siglas"> - {{ referencia.documento_referencia.siglas }}</template>
                  </div>
                  <div v-else class="small fw-semibold text-body">
                    {{ referencia.titulo?.trim() || 'Referencia' }}
                  </div>
                  <div v-if="referencia.tipo === 'documento' && referencia.documento_referencia?.remitente" class="d-flex flex-wrap gap-3 text-body-secondary mt-1" style="font-size:0.72rem">
                    <span v-if="referencia.documento_referencia.remitente.cargo">
                      <i class="bi bi-briefcase me-1"></i>{{ referencia.documento_referencia.remitente.cargo }}
                    </span>
                    <span v-if="referencia.documento_referencia.remitente.dependencia">
                      <i class="bi bi-building me-1"></i>{{ referencia.documento_referencia.remitente.dependencia }}
                    </span>
                  </div>
                  <div v-if="referencia.tipo === 'documento' && referencia.titulo" class="text-body-secondary mt-1" style="font-size:0.72rem">
                    {{ referencia.titulo.trim() }}
                  </div>
                </div>

                <div v-if="referencia.tipo === 'normativa' && referencia.url" class="mt-1">
                  <a
                    :href="referencia.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-sm btn-outline-secondary py-0 d-inline-flex align-items-center gap-1"
                    style="font-size:0.72rem"
                  >
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Ver normativa</span>
                  </a>
                </div>

                <div v-if="referencia.observaciones" class="text-info small mt-1">
                  <i class="bi bi-info-circle me-1"></i>{{ referencia.observaciones }}
                </div>

                <!-- Archivos -->
                <div v-if="obtenerArchivosReferencia(referencia.documento_referenciado_id).length" class="mt-2 pt-1 border-top">
                  <div class="d-flex flex-column gap-1">
                    <button
                      v-for="archivo in obtenerArchivosReferencia(referencia.documento_referenciado_id)"
                      :key="archivo.id"
                      class="btn btn-sm text-start d-flex align-items-center gap-2 w-100"
                      :class="getClaseBotonArchivo(archivo)"
                      :title="esPdf(archivo) ? 'Ver en visor' : 'Descargar archivo'"
                      @click="esPdf(archivo) ? emitAbrirPdf(archivo) : handleDescargarArchivo(archivo)"
                    >
                      <i class="bi flex-shrink-0" :class="getIconoArchivo(archivo)"></i>
                      <span class="text-truncate flex-grow-1 small">{{ archivo.nombre_original || archivo.nombre || archivo.original_name || archivo.file_name || 'Archivo' }}</span>
                      <span class="badge flex-shrink-0" :class="getBadgeClaseArchivo(archivo)" style="font-size:0.6rem">
                        {{ esPdf(archivo) ? 'PDF' : (archivo.extension?.toUpperCase() || '?') }}
                      </span>
                    </button>
                  </div>
                </div>
                <div v-else-if="referencia.documento_referenciado_id" class="text-body-secondary mt-1" style="font-size:0.72rem">
                  <i class="bi bi-info-circle me-1"></i>Sin archivos
                </div>
              </div>
            </div>
          </div>
        </div> <!-- /referencias -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useReferenciaDocumentos } from '@/composables/useReferenciaDocumentos'
import { usePdfPopup } from '@/composables/usePdfPopup'
import { Files } from '@/core/models'

const emit = defineEmits(['abrir-pdf-referencia'])
const props = defineProps({
  documentoId       : { type: Number, default: null },
  documentoArchivos : { type: Array, default: null }
})

const {
  loading, error, referenciasPorTipo, archivosDocumentoPrincipal,
  archivoPrincipalDocPrincipal, anexosDocPrincipal,
  documentoPrincipal, tieneReferencias, cargarReferenciasDocumento,
  documentosConArchivos, formatearIdDocumento, limpiarEstadoCompleto,
  establecerArchivosDirectos, getTituloTipo, getIconoTipo,
  getBotonActivoClass, getBotonInactivoClass
} = useReferenciaDocumentos()

const { esPdf, descargarArchivo } = usePdfPopup()

const acordeonPrincipalAbierto = ref(true)
const acordeonesTipos = ref({ normativas: true, leyes: true, documentos: false })
const todoExpandido = ref(true)
const mensajeError = ref('')

const referenciasPorTipoConContenido = computed(() =>
  Object.entries(referenciasPorTipo.value)
    .filter(([, list]) => list.length > 0)
    .map(([tipoKey, list]) => ({ tipoKey, list }))
)

const totalReferencias = computed(() =>
  archivosDocumentoPrincipal.value.length +
  referenciasPorTipo.value.normativas.length +
  referenciasPorTipo.value.leyes.length +
  referenciasPorTipo.value.documentos.length
)

function getExtension(archivo) {
  return (archivo?.extension || archivo?.mime_type?.split('/')[1] || '').toLowerCase()
}

function getIconoArchivo(archivo) {
  const ext = getExtension(archivo)
  if (ext === 'pdf') return 'bi-file-earmark-pdf text-danger'
  if (['doc', 'docx'].includes(ext)) return 'bi-file-earmark-word text-primary'
  if (['xls', 'xlsx'].includes(ext)) return 'bi-file-earmark-excel text-success'
  if (['ppt', 'pptx'].includes(ext)) return 'bi-file-earmark-ppt text-warning'
  return 'bi-file-earmark-arrow-down text-secondary'
}

function getClaseBotonArchivo(archivo) {
  const ext = getExtension(archivo)
  if (ext === 'pdf') return 'btn-outline-danger'
  if (['doc', 'docx'].includes(ext)) return 'btn-outline-primary'
  if (['xls', 'xlsx'].includes(ext)) return 'btn-outline-success'
  if (['ppt', 'pptx'].includes(ext)) return 'btn-outline-warning'
  return 'btn-outline-secondary'
}

function getBadgeClaseArchivo(archivo) {
  const ext = getExtension(archivo)
  if (ext === 'pdf') return 'bg-danger text-white'
  if (['doc', 'docx'].includes(ext)) return 'bg-primary text-white'
  if (['xls', 'xlsx'].includes(ext)) return 'bg-success text-white'
  if (['ppt', 'pptx'].includes(ext)) return 'bg-warning text-dark'
  return 'bg-secondary text-white'
}

function obtenerArchivosReferencia(docId) {
  if (!docId) return []
  return documentosConArchivos.value[parseInt(docId, 10)] || []
}
function toggleTipo(tipo) { acordeonesTipos.value[tipo] = !acordeonesTipos.value[tipo] }
function toggleExpandirTodo() {
  todoExpandido.value = !todoExpandido.value
  const nuevoEstado = todoExpandido.value
  acordeonPrincipalAbierto.value = nuevoEstado
  Object.keys(acordeonesTipos.value).forEach(tipo => acordeonesTipos.value[tipo] = nuevoEstado)
}
function emitAbrirPdf(archivo) {
  const id = archivo.id || archivo.archivo_id
  emit('abrir-pdf-referencia', { archivo  : { ...archivo, url: archivo.url || Files.buildVisualizarUrl(id) },
    opciones : { esReferencia: true, noCargarReferencias: true } })
}

async function handleDescargarArchivo(archivo) {
  const resultado = await descargarArchivo(archivo)
  if (!resultado.éxito) {
    mensajeError.value = 'Error al descargar: ' + resultado.error
  }
}

function recargar() { if (props.documentoId) cargarReferenciasDocumento(props.documentoId) }

onMounted(async () => {
  if (props.documentoId) await cargarReferenciasDocumento(props.documentoId)
})

watch(() => props.documentoId, async (newVal, oldVal) => {
  if (!newVal) return limpiarEstadoCompleto()
  if (oldVal && oldVal !== newVal) limpiarEstadoCompleto()
  await cargarReferenciasDocumento(newVal)
})

// Watch PRINCIPAL para archivos - con immediate:true se ejecutará al montar
watch(() => props.documentoArchivos, (newVal) => {
  if (newVal && newVal.length > 0) {
    establecerArchivosDirectos(newVal)
  }
}, { deep: true, immediate: true })
</script>