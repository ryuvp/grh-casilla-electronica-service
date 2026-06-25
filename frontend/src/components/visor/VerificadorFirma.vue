<template>
  <div class="verificador-firma d-flex flex-column flex-fill overflow-auto">
    <div class="card border-0 shadow-sm flex-fill d-flex flex-column">
      <div class="card-header bg-body-secondary border-bottom py-2">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="mb-0 fw-semibold d-flex align-items-center">
            <i class="bi bi-shield-check me-2 text-primary"></i> Verificación de Firma
          </h6>
          <div class="d-flex align-items-center gap-1">
            <button
              class="btn btn-sm btn-outline-primary px-2"
              :disabled="loading || !fileId"
              @click="revalidar"
            >
              <i class="bi bi-arrow-repeat" :class="{ 'spin': loading }"></i>
              <span class="d-none d-lg-inline ms-1 small">Revalidar</span>
            </button>
            <button
              class="btn btn-sm btn-outline-secondary px-2"
              @click="collapsed = !collapsed"
            >
              <i class="bi" :class="collapsed ? 'bi-chevron-down' : 'bi-chevron-up'"></i>
            </button>
          </div>
        </div>
      </div>

      <div v-show="!collapsed" class="card-body p-2 flex-fill overflow-auto">
        <!-- Cargando -->
        <div v-if="loading" class="estado-card estado-loading">
          <i class="bi bi-hourglass-split me-2"></i>
          <span>Validando firma digital...</span>
        </div>

        <!-- Error -->
        <div v-else-if="resultado && (resultado.error || resultado.errorMessage)" class="estado-card estado-error">
          <div class="estado-header">
            <i class="bi bi-x-octagon-fill"></i>
            <span class="fw-semibold">Error en validación</span>
          </div>
          <div class="estado-message">{{ resultado.error || resultado.errorMessage }}</div>
        </div>

        <!-- Sin firmas -->
        <div v-else-if="resultado && resultado.result === 'SIN FIRMAS DIGITALES'" class="estado-card estado-warning">
          <div class="estado-header">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span class="fw-semibold">Sin firmas digitales</span>
          </div>
          <div class="estado-message">Este documento no contiene firmas digitales</div>
        </div>

        <!-- Válido -->
        <div v-else-if="resultado && resultado.result === 'RESULTADO VÁLIDO'" class="estado-card estado-success">
          <div class="estado-header">
            <i class="bi bi-patch-check-fill"></i>
            <span class="fw-semibold">Firma digital válida</span>
          </div>
          
          <div class="info-section">
            <div class="info-item">
              <span class="info-label">Fecha:</span>
              <span class="info-value">{{ resultado.validationDate || '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Documento:</span>
              <span class="info-value">{{ resultado.file || fileName || 'PDF' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Integridad:</span>
              <span class="info-value">{{ resultado.integrity || '—' }}</span>
            </div>
            <div v-if="resultado.observations && resultado.observations.length" class="info-item">
              <span class="info-label">Observaciones:</span>
              <span class="info-value">{{ resultado.observations.join(' ') }}</span>
            </div>
          </div>

          <div v-if="resultado.listSignatures && resultado.listSignatures.length" class="firmantes-section">
            <div class="firmantes-header">Firmantes ({{ resultado.listSignatures.length }})</div>
            <div class="firmantes-list">
              <div v-for="sig in resultado.listSignatures" :key="sig.number" class="firmante-item firmante-valido">
                <div class="firmante-info">
                  <div class="firmante-nombre">{{ sig.number }}. {{ sig.signer }}</div>
                  <div class="firmante-meta">
                    <span class="badge-status badge-success">VÁLIDO</span>
                    <span class="firmante-fecha">{{ sig.date }}</span>
                  </div>
                  <div v-if="sig.notes && sig.notes.length" class="firmante-nota">{{ sig.notes[0] }}</div>
                </div>
                <i class="bi bi-check-circle-fill text-success firmante-icon"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Indeterminado -->
        <div v-else-if="resultado && resultado.result === 'RESULTADO INDETERMINADO'" class="estado-card estado-warning">
          <div class="estado-header">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
              <span class="fw-semibold">Resultado indeterminado</span>
              <div v-if="resultado.signatures && resultado.validSignatures" class="estado-subtitle">
                {{ resultado.validSignatures }} de {{ resultado.signatures }} firmas válidas
              </div>
            </div>
          </div>

          <div class="info-section">
            <div class="info-item">
              <span class="info-label">Fecha:</span>
              <span class="info-value">{{ resultado.validationDate || '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Archivo:</span>
              <span class="info-value">{{ resultado.file || fileName || 'PDF' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Integridad:</span>
              <span class="info-value">{{ resultado.integrity || '—' }}</span>
            </div>
          </div>

          <div v-if="resultado.observations && resultado.observations.length" class="observaciones-section">
            <div class="observaciones-header">Observaciones:</div>
            <ul class="observaciones-list">
              <li v-for="(obs, idx) in resultado.observations" :key="idx">{{ obs }}</li>
            </ul>
          </div>

          <div v-if="resultado.listSignatures && resultado.listSignatures.length" class="firmantes-section">
            <div class="firmantes-header">Firmantes ({{ resultado.listSignatures.length }})</div>
            <div class="firmantes-list">
              <div 
                v-for="sig in resultado.listSignatures" 
                :key="sig.number" 
                class="firmante-item"
                :class="{
                  'firmante-valido': sig.status === 'VÁLIDO',
                  'firmante-indeterminado': sig.status === 'INDETERMINADO',
                  'firmante-invalido': sig.status === 'INVÁLIDO'
                }"
              >
                <div class="firmante-info">
                  <div class="firmante-nombre">{{ sig.number }}. {{ sig.signer }}</div>
                  <div class="firmante-meta">
                    <span 
                      class="badge-status"
                      :class="{
                        'badge-success': sig.status === 'VÁLIDO',
                        'badge-warning': sig.status === 'INDETERMINADO',
                        'badge-danger': sig.status === 'INVÁLIDO'
                      }"
                    >
                      {{ sig.status || 'Desconocido' }}
                    </span>
                    <span class="firmante-fecha">{{ sig.date }}</span>
                  </div>
                  <div v-if="sig.errors && sig.errors.length" class="firmante-error">
                    <i class="bi bi-exclamation-circle"></i> {{ sig.errors[0] }}
                  </div>
                  <div v-if="sig.notes && sig.notes.length" class="firmante-nota">{{ sig.notes[0] }}</div>
                </div>
                <i 
                  class="bi firmante-icon"
                  :class="{
                    'bi-check-circle-fill text-success': sig.status === 'VÁLIDO',
                    'bi-exclamation-circle-fill text-warning': sig.status === 'INDETERMINADO',
                    'bi-x-circle-fill text-danger': sig.status === 'INVÁLIDO'
                  }"
                ></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado desconocido -->
        <div v-else class="estado-card estado-unknown">
          <div class="estado-header">
            <i class="bi bi-question-circle-fill"></i>
            <span class="fw-semibold">Estado desconocido</span>
          </div>
          <button class="btn btn-sm btn-outline-secondary mt-2" @click="revalidar">
            <i class="bi bi-arrow-repeat me-1"></i> Intentar nuevamente
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useValidacionFirma } from '@/composables/useValidacionFirma'

const props = defineProps({
  fileId   : { type: [Number, String], required: true },
  fileName : { type: String, default: '' }
})

const { loading, resultado, validarFirmaConCache, forzarRevalidacion } = useValidacionFirma()
const collapsed = ref(false)
const fid = computed(() => props.fileId ? parseInt(props.fileId, 10) : null)

async function cargar() { if (fid.value) await validarFirmaConCache(fid.value) }
async function revalidar() { if (fid.value) await forzarRevalidacion(fid.value) }

onMounted(cargar)
watch(fid, (n, o) => { if (n && n !== o) cargar() })
</script>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.spin {
  animation: spin 1s linear infinite;
}

/* Tarjetas de estado */
.estado-card {
  padding: 0.75rem;
  border-radius: 0.375rem;
  border: 1px solid;
}

.estado-loading {
  background-color: rgba(13, 110, 253, 0.1);
  border-color: rgba(13, 110, 253, 0.3);
  color: #0d6efd;
}

.estado-error {
  background-color: rgba(220, 53, 69, 0.1);
  border-color: rgba(220, 53, 69, 0.3);
  color: #dc3545;
}

.estado-warning {
  background-color: rgba(255, 193, 7, 0.1);
  border-color: rgba(255, 193, 7, 0.3);
  color: #856404;
}

.estado-success {
  background-color: rgba(25, 135, 84, 0.1);
  border-color: rgba(25, 135, 84, 0.3);
  color: #0f5132;
}

.estado-unknown {
  background-color: rgba(108, 117, 125, 0.1);
  border-color: rgba(108, 117, 125, 0.3);
  color: #495057;
}

/* Modo oscuro */
:deep(.dark-mode) .estado-warning,
[data-bs-theme="dark"] .estado-warning {
  color: #ffc107;
}

:deep(.dark-mode) .estado-success,
[data-bs-theme="dark"] .estado-success {
  color: #198754;
}

:deep(.dark-mode) .estado-unknown,
[data-bs-theme="dark"] .estado-unknown {
  color: #adb5bd;
}

/* Headers */
.estado-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9375rem;
  margin-bottom: 0.5rem;
}

.estado-header i {
  font-size: 1.125rem;
}

.estado-subtitle {
  font-size: 0.8125rem;
  font-weight: normal;
  margin-top: 0.125rem;
  opacity: 0.9;
}

.estado-message {
  font-size: 0.875rem;
  margin-top: 0.375rem;
}

/* Sección de información */
.info-section {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

[data-bs-theme="dark"] .info-section {
  border-top-color: rgba(255, 255, 255, 0.1);
}

.info-item {
  display: flex;
  gap: 0.5rem;
  font-size: 0.8125rem;
  line-height: 1.4;
}

.info-label {
  font-weight: 600;
  color: var(--bs-body-color);
  min-width: 80px;
  flex-shrink: 0;
}

.info-value {
  color: var(--bs-secondary-color);
  flex: 1;
  word-break: break-word;
}

/* Observaciones */
.observaciones-section {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

[data-bs-theme="dark"] .observaciones-section {
  border-top-color: rgba(255, 255, 255, 0.1);
}

.observaciones-header {
  font-weight: 600;
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
  color: var(--bs-body-color);
}

.observaciones-list {
  margin: 0;
  padding-left: 1.25rem;
  font-size: 0.8125rem;
  color: var(--bs-secondary-color);
}

.observaciones-list li {
  margin-bottom: 0.25rem;
}

/* Firmantes */
.firmantes-section {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

[data-bs-theme="dark"] .firmantes-section {
  border-top-color: rgba(255, 255, 255, 0.1);
}

.firmantes-header {
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 0.625rem;
  color: var(--bs-body-color);
}

.firmantes-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.firmante-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.625rem;
  border-radius: 0.375rem;
  background-color: rgba(0, 0, 0, 0.02);
  border: 1px solid rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

[data-bs-theme="dark"] .firmante-item {
  background-color: rgba(255, 255, 255, 0.02);
  border-color: rgba(255, 255, 255, 0.1);
}

.firmante-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
  border-color: rgba(0, 0, 0, 0.2);
}

[data-bs-theme="dark"] .firmante-item:hover {
  background-color: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.2);
}

.firmante-valido {
  border-left: 3px solid #198754;
}

.firmante-indeterminado {
  border-left: 3px solid #ffc107;
}

.firmante-invalido {
  border-left: 3px solid #dc3545;
}

.firmante-info {
  flex: 1;
  min-width: 0;
}

.firmante-nombre {
  font-weight: 500;
  font-size: 0.8125rem;
  color: var(--bs-body-color);
  margin-bottom: 0.375rem;
  line-height: 1.3;
  word-break: break-word;
}

.firmante-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge-status {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.badge-success {
  background-color: #198754;
  color: white;
}

.badge-warning {
  background-color: #ffc107;
  color: #000;
}

.badge-danger {
  background-color: #dc3545;
  color: white;
}

.firmante-fecha {
  font-size: 0.75rem;
  color: var(--bs-secondary-color);
}

.firmante-error {
  font-size: 0.75rem;
  color: #dc3545;
  margin-top: 0.375rem;
  line-height: 1.3;
}

.firmante-nota {
  font-size: 0.75rem;
  color: var(--bs-secondary-color);
  margin-top: 0.375rem;
  font-style: italic;
  line-height: 1.3;
}

.firmante-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

/* Scrollbar personalizado */
.firmantes-list::-webkit-scrollbar {
  width: 6px;
}

.firmantes-list::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 3px;
}

.firmantes-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.firmantes-list::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

[data-bs-theme="dark"] .firmantes-list::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

[data-bs-theme="dark"] .firmantes-list::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
}

[data-bs-theme="dark"] .firmantes-list::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}
</style>