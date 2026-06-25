<template>
  <div class="visor-pdf h-100 d-flex flex-column">
    <!-- Estado de error -->
    <div 
      v-if="error && !loading" 
      class="error-container d-flex align-items-center justify-content-center h-100 bg-light p-4"
    >
      <div class="text-center">
        <div class="mb-3">
          <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
        </div>
        <h5 class="text-muted mb-3">Error al cargar el documento</h5>
        <p class="text-secondary mb-4">{{ error }}</p>
        <button 
          class="btn btn-primary"
          @click="recargar"
        >
          <i class="bi bi-arrow-clockwise me-2"></i>
          Intentar de nuevo
        </button>
      </div>
    </div>

    <!-- PDF Iframe - siempre presente pero se muestra un overlay de carga -->
    <div 
      v-if="!error || loading"
      class="pdf-container flex-fill position-relative bg-light"
    >
      <!-- Overlay de carga -->
      <div 
        v-if="loading" 
        class="loading-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light bg-opacity-90 loading-overlay-zindex"
      >
        <div class="text-center text-muted">
          <div class="spinner-border mb-3" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <div class="fw-medium mb-1">Cargando documento...</div>
          <small>Por favor espere mientras se carga el PDF</small>
        </div>
      </div>
      
      <iframe 
        ref="iframeRef" 
        class="pdf-iframe w-100 h-100 border-0 rounded"
        frameborder="0"
        :title="`Documento PDF: ${props.nombre || 'Sin título'}`"
      ></iframe>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import JwtService from '@/core/services/JwtService'
import { Files } from '@/core/models'

const props = defineProps({
  pdfUrl : {
    type    : String,
    default : ''
  },
  fileId : {
    type    : Number,
    default : null
  },
  nombre : {
    type    : String,
    default : ''
  }
})

const iframeRef = ref(null)
const blobUrl = ref('')
const loading = ref(true)
const error = ref('')

async function cargar(url) {
  try {
    loading.value = true
    error.value = ''
    
    // Limpiar blob URL anterior
    if (blobUrl.value) {
      URL.revokeObjectURL(blobUrl.value)
      blobUrl.value = ''
    }

    const token = JwtService.getToken()
    const res = await window.fetch(url, {
      cache   : 'no-store',
      headers : {
        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        'Accept' : 'application/pdf'
      }
    })
    
    if (!res.ok) throw new Error(`HTTP ${res.status}`)

    const blob = await res.blob()
    blobUrl.value = URL.createObjectURL(blob)
    
    // Usar nextTick para asegurar que el DOM esté actualizado
    await nextTick()
    
    if (iframeRef.value) {
      iframeRef.value.src = blobUrl.value
    } else {
      // Intentar nuevamente después de un pequeño delay
      setTimeout(() => {
        if (iframeRef.value) {
          iframeRef.value.src = blobUrl.value
        }
      }, 100)
    }
  } catch (e) {
    console.error('Error al cargar PDF:', e)
    error.value = e.message || 'Error al cargar el documento PDF'
  } finally {
    loading.value = false
  }
}

function recargar() {
  if (props.pdfUrl) {
    cargar(props.pdfUrl)
  } else if (props.fileId) {
    cargar(Files.buildVisualizarUrl(props.fileId))
  }
}

// Watchers
watch(() => props.pdfUrl, (newUrl) => {
  if (newUrl) cargar(newUrl)
}, { immediate: true })

watch(() => props.fileId, (newId) => {
  if (newId && !props.pdfUrl) {
    cargar(Files.buildVisualizarUrl(newId))
  }
})

// Lifecycle
onMounted(() => {
  if (props.pdfUrl) {
    cargar(props.pdfUrl)
  } else if (props.fileId) {
    cargar(Files.buildVisualizarUrl(props.fileId))
  }
})

onBeforeUnmount(() => {
  if (blobUrl.value) {
    URL.revokeObjectURL(blobUrl.value)
  }
})

// Exponer para que componentes padre puedan forzar recarga
defineExpose({ recargar })
</script>

