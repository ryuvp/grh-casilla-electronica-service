<template>
  <div class="visor-fullscreen">

    <!-- Botón de cierre -->
    <button class="close-btn" title="Cerrar visor" @click="emit('close')">
      <i class="bi bi-x-lg"></i>
    </button>

    <!-- Botón flotante toggle panel en desktop -->
    <div v-if="!isMobile" class="floating-btn-desktop">
      <button
        class="btn btn-primary rounded-circle p-3 shadow-lg"
        :title="panelVisible ? 'Ocultar panel' : 'Mostrar panel'"
        @click="togglePanel"
      >
        <i class="bi" :class="panelVisible ? 'bi-layout-sidebar' : 'bi-layout-sidebar-reverse'" style="font-size: 1.5rem;"></i>
      </button>
    </div>

    <!-- Contenido principal -->
    <div class="layout-container">

      <!-- Panel PDF -->
      <div class="pdf-panel" :class="{ 'full-width': !panelVisible && !isMobile }">
        <!-- Header móvil -->
        <div class="pdf-mobile-header d-lg-none bg-body border-bottom p-3 d-flex align-items-center justify-content-between shadow-sm">
          <div class="d-flex align-items-center flex-fill min-w-0">
            <i class="bi bi-file-pdf text-danger me-2 flex-shrink-0"></i>
            <span class="fw-medium text-truncate">{{ props.nombre || 'Documento PDF' }}</span>
          </div>
          <button class="btn btn-sm btn-outline-primary ms-2 flex-shrink-0" @click="togglePanel">
            <i class="bi bi-info-circle"></i>
          </button>
        </div>

        <!-- Contenido PDF -->
        <div class="pdf-content">
          <VisorPdf
            :key="`visor-${props.fileId}`"
            :file-id="props.fileId"
            :pdf-url="props.pdfUrl"
            :nombre="props.nombre || ''"
          />
        </div>
      </div>

      <!-- Panel lateral derecho -->
      <div v-if="panelVisible && !isMobile" class="info-panel">

        <!-- Sección Verificación -->
        <div class="panel-section" :class="{ 'collapsed': collapseVerificador }">
          <div class="section-header bg-body-secondary p-2 d-flex align-items-center justify-content-between border-bottom">
            <div class="d-flex align-items-center">
              <i class="bi bi-shield-check text-primary me-2"></i>
              <h6 class="mb-0 fw-semibold">Verificación</h6>
            </div>
            <button class="btn btn-sm btn-outline-secondary" @click="collapseVerificador = !collapseVerificador">
              <i class="bi" :class="collapseVerificador ? 'bi-chevron-down' : 'bi-chevron-up'"></i>
            </button>
          </div>
          <div v-show="!collapseVerificador" class="section-content">
            <div class="p-2">
              <VerificadorFirma :file-id="props.fileId" :file-name="props.nombre" />
            </div>
          </div>
        </div>

        <!-- Sección Referencias -->
        <div class="panel-section flex-fill" :class="{ 'collapsed': collapseReferencias }">
          <div class="section-header bg-body-secondary p-2 d-flex align-items-center justify-content-between border-bottom">
            <div class="d-flex align-items-center">
              <i class="bi bi-link-45deg text-info me-2"></i>
              <h6 class="mb-0 fw-semibold">Referencias</h6>
            </div>
            <button class="btn btn-sm btn-outline-secondary" @click="collapseReferencias = !collapseReferencias">
              <i class="bi" :class="collapseReferencias ? 'bi-chevron-down' : 'bi-chevron-up'"></i>
            </button>
          </div>
          <div v-show="!collapseReferencias" class="section-content">
            <ReferenciasDocumento
              key="referencias-unica"
              :documento-id="null"
              :documento-archivos="[]"
            />
          </div>
        </div>

      </div>
    </div>

    <!-- Overlay móvil -->
    <div v-if="panelVisible && isMobile" class="mobile-overlay" @click="panelVisible = false"></div>

    <!-- Panel móvil desde la derecha -->
    <div v-if="panelVisible && isMobile" class="lateral-panel-mobile">
      <div class="panel-mobile-header bg-body-secondary p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">Información del Documento</h6>
        <button class="btn btn-sm btn-outline-secondary" @click="panelVisible = false">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="mobile-panel-content">
        <div class="p-2">
          <VerificadorFirma :file-id="props.fileId" :file-name="props.nombre" />
        </div>
      </div>
    </div>

    <!-- Botón flotante solo en móvil -->
    <div v-if="isMobile && !panelVisible" class="floating-btn-mobile">
      <button class="btn btn-primary rounded-circle p-3 shadow-lg" title="Ver información" @click="togglePanel">
        <i class="bi bi-info-circle fs-5"></i>
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import VisorPdf from '@/components/visor/VisorPdf.vue'
import VerificadorFirma from '@/components/visor/VerificadorFirma.vue'
import ReferenciasDocumento from '@/components/visor/ReferenciasDocumento.vue'

const props = defineProps({
  fileId : { type: Number, default: null },
  pdfUrl : { type: String, default: '' },
  nombre : { type: String, default: '' },
})

const emit = defineEmits(['close'])

const panelVisible        = ref(true)
const collapseVerificador = ref(false)
const collapseReferencias = ref(false)
const windowWidth         = ref(window.innerWidth)

const isMobile = computed(() => windowWidth.value < 992)

function togglePanel() { panelVisible.value = !panelVisible.value }
function handleResize() { windowWidth.value = window.innerWidth }

onMounted(() => window.addEventListener('resize', handleResize))
onUnmounted(() => window.removeEventListener('resize', handleResize))
</script>

<style scoped>
.close-btn {
  position: fixed;
  top: 18px;
  right: 18px;
  z-index: 10002;
  background: rgba(255,255,255,0.95);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.close-btn:hover { background: #f8d7da; transform: scale(1.05); }

[data-bs-theme="dark"] .close-btn { background: rgba(33,37,41,0.95); color: white; }
[data-bs-theme="dark"] .close-btn:hover { background: #dc3545; }

.visor-fullscreen {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  width: 100vw;
  height: 100vh;
  height: 100dvh;
  margin: 0; padding: 0;
  z-index: 9999;
  background: var(--bs-body-bg);
}

.layout-container {
  display: flex;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.pdf-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: var(--bs-body-tertiary);
  overflow: hidden;
  transition: all 0.3s ease;
}

.pdf-panel.full-width { flex: 1 1 100%; }

.pdf-content {
  flex: 1;
  overflow: hidden;
  position: relative;
}

.info-panel {
  width: 350px;
  max-width: 25%;
  display: flex;
  flex-direction: column;
  background: var(--bs-body);
  border-left: 1px solid var(--bs-border-color);
  overflow: hidden;
}

@media (min-width: 1400px) { .info-panel { width: 400px; } }

.panel-section {
  display: flex;
  flex-direction: column;
  min-height: 0;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.panel-section.collapsed { flex-grow: 0; flex-shrink: 0; }
.panel-section:not(.collapsed) { flex-grow: 1; flex-shrink: 1; }

.section-header {
  flex-shrink: 0;
  cursor: pointer;
  user-select: none;
}

.section-header:hover { background-color: var(--bs-secondary-bg) !important; }

.section-content {
  overflow-y: auto;
  overflow-x: hidden;
  flex: 1;
  min-height: 0;
}

.section-content::-webkit-scrollbar { width: 8px; }
.section-content::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 4px; }
.section-content::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 4px; }
.section-content::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.3); }

.floating-btn-desktop,
.floating-btn-mobile {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 10000;
}

.floating-btn-desktop { display: none; }

@media (min-width: 992px) { .floating-btn-desktop { display: block; } }

.floating-btn-desktop .btn,
.floating-btn-mobile .btn {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.floating-btn-desktop .btn:hover,
.floating-btn-mobile .btn:hover {
  transform: scale(1.05);
  box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.3) !important;
}

.mobile-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 10000;
}

.lateral-panel-mobile {
  position: fixed;
  top: 0; right: 0; bottom: 0;
  width: 90%;
  max-width: 400px;
  background: var(--bs-body);
  box-shadow: -4px 0 16px rgba(0,0,0,0.2);
  z-index: 10001;
  display: flex;
  flex-direction: column;
}

.mobile-panel-content { flex: 1; overflow-y: auto; }
.panel-mobile-header { flex-shrink: 0; }

@media (max-width: 991.98px) { .info-panel { display: none; } }

[data-bs-theme="dark"] .section-content::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
[data-bs-theme="dark"] .section-content::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); }
[data-bs-theme="dark"] .section-content::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
</style>
