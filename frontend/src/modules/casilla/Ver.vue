<template>
  <div ref="modalRef" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <div class="modal-content rounded border-0 shadow">
        <HeaderColor
          accion="ver"
          bg-color="#d1e7dd"
          text-color="#08632a"
          data-bs-dismiss="modal"
          aria-label="Cerrar"
        >
          <i class="bi bi-eye-fill fs-3 me-2" style="color: #08632a"></i>
          <span class="fw-bold">Detalle de la Casilla Electrónica</span>
        </HeaderColor>
        <div class="modal-body px-5 py-4">
          <div v-if="loadingResumen" class="text-center py-5">
            <span class="spinner-border spinner-border-sm text-primary"></span> Cargando datos del usuario...
          </div>
          <div v-else class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-0">Número de Casilla</label>
              <div class="form-control-plaintext text-dark fw-bold fs-5 py-0">{{ item?.numero || 'N/A' }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-1">Estado</label>
              <div>
                <span v-if="item?.activo" class="badge bg-success bg-opacity-25 text-success border border-success px-3 py-1">
                  ACTIVO
                </span>
                <span v-else class="badge bg-danger bg-opacity-25 text-danger border border-danger px-3 py-1">
                  INACTIVO
                </span>
              </div>
            </div>

            <div class="col-12"><hr class="my-2 text-muted"></div>

            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-0">Usuario Asociado</label>
              <div class="form-control-plaintext text-dark text-uppercase fw-bold py-0">{{ resumen?.usuario_nombre || 'N/A' }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-0">Documento de Identidad</label>
              <div class="form-control-plaintext text-dark py-0">{{ resumen?.numero_documento || 'N/A' }}</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-0">Cargo / Función</label>
              <div class="form-control-plaintext text-dark text-uppercase py-0">{{ resumen?.cargo_nombre || 'N/A' }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-0">Dependencia</label>
              <div class="form-control-plaintext text-dark text-uppercase py-0">{{ resumen?.dependencia_nombre || 'N/A' }}</div>
            </div>

            <div class="col-12"><hr class="my-2 text-muted"></div>

            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-0">Vigencia Desde</label>
              <div class="form-control-plaintext text-dark py-0">{{ formatFecha(item?.fecha_inicio) }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-muted mb-0">Vigencia Hasta</label>
              <div class="form-control-plaintext text-dark py-0">{{ formatFecha(item?.fecha_fin) }}</div>
            </div>
          </div>
          
          <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
            <button type="button" class="btn btn-secondary btn-sm px-6" data-bs-dismiss="modal">
              Cerrar
            </button>
            <button 
              type="button" 
              class="btn btn-primary btn-sm px-6" 
              @click="onEditar"
            >
              <i class="bi bi-pencil me-1"></i> Editar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { Modal } from 'bootstrap';
import HeaderColor from '@/components/tabla/HeaderColor.vue';
import { formatDateLima } from '@/core/utils/dateTime';
import useDesignacionStore from '@/stores/designaciones/designacionStore';

const props = defineProps({
  item : {
    type     : Object,
    required : false,
    default  : null
  }
});

const emit = defineEmits(['editar']);

let modal = null;
const modalRef = ref(null);

const designacionStore = useDesignacionStore();
const resumen = ref(null);
const loadingResumen = ref(false);

watch(() => props.item?.designacion_id, async (newVal) => {
  if (newVal) {
    loadingResumen.value = true;
    try {
      resumen.value = await designacionStore.fetchResumenByDesignacionId(newVal);
    } catch (e) {
      console.error(e);
      resumen.value = null;
    } finally {
      loadingResumen.value = false;
    }
  } else {
    resumen.value = null;
  }
}, { immediate: true });

onMounted( async () => {
  if (modalRef.value) {
    modal = new Modal(modalRef.value);
  }
});

const abrir = () => {
  if (modal) modal.show();
}
const cerrar = () => {
  if (modal) modal.hide();
}
defineExpose({ abrir, cerrar });

const formatFecha = (fecha) => {
  if (!fecha) return 'Indefinido';
  return formatDateLima(fecha);
};

const onEditar = () => {
  cerrar();
  emit('editar');
};
</script>

<style scoped>
.modal.show {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}
</style>