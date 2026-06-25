<template>
  <div ref="modalRef" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <div class="modal-content rounded border-0 shadow">
        <HeaderColor
          :accion="isEdit ? 'editar' : 'agregar'"
          :bg-color="isEdit ? '#fff3cd' : '#d1e7dd'"
          :text-color="isEdit ? '#664d03' : '#08632a'"
          data-bs-dismiss="modal"
          aria-label="Cerrar"
        >
          <i class="bi bi-mailbox fs-3 me-2" :style="{ color: isEdit ? '#664d03' : '#08632a' }"></i>
          <span class="fw-bold">{{ isEdit ? 'Actualizar Casilla Electrónica' : 'Nueva Casilla Electrónica' }}</span>
        </HeaderColor>
        <div class="modal-body px-5 py-4">
          <el-form 
            ref="formRef" 
            :model="formData" 
            :rules="rules" 
            class="form" 
            label-position="top"
            @submit.prevent="submit" 
          >
            <!-- Número de Casilla -->
            <el-form-item label="Número de Casilla" prop="numero">
              <el-input
                v-model="formData.numero"
                type="text"
                placeholder="Ej. 1004"
                clearable
                size="large"
              />
            </el-form-item>

            <!-- Designación / Usuario -->
            <el-form-item label="Designación / Usuario" prop="designacion_id">
              <el-select
                v-model="formData.designacion_id"
                placeholder="Busque por nombre o DNI la designación del usuario"
                filterable
                remote
                :remote-method="buscarDesignaciones"
                :loading="loadingDesignaciones"
                class="w-100"
                size="large"
                clearable
              >
                <el-option
                  v-for="option in designacionOptions"
                  :key="option.designacion_id"
                  :label="option.display_name || option.usuario_nombre"
                  :value="option.designacion_id"
                />
              </el-select>
            </el-form-item>

            <!-- Rango de Fechas -->
            <div class="row">
              <div class="col-md-6">
                <el-form-item label="Fecha de Inicio" prop="fecha_inicio">
                  <el-date-picker
                    v-model="formData.fecha_inicio"
                    type="date"
                    placeholder="Seleccione fecha"
                    class="w-100"
                    value-format="YYYY-MM-DD"
                    size="large"
                  />
                </el-form-item>
              </div>
              <div class="col-md-6">
                <el-form-item label="Fecha de Fin" prop="fecha_fin">
                  <el-date-picker
                    v-model="formData.fecha_fin"
                    type="date"
                    placeholder="Seleccione fecha (Opcional)"
                    class="w-100"
                    value-format="YYYY-MM-DD"
                    size="large"
                  />
                </el-form-item>
              </div>
            </div>

            <!-- Estado -->
            <el-form-item label="Estado de Casilla" prop="activo">
              <div class="d-flex align-items-center">
                <el-switch
                  v-model="formData.activo"
                  active-text="Activo"
                  inactive-text="Inactivo"
                  :active-value="1"
                  :inactive-value="0"
                />
              </div>
            </el-form-item>

            <!-- Botones de Acción -->
            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
              <button type="button" class="btn btn-secondary btn-sm px-6" data-bs-dismiss="modal">
                Cancelar
              </button>
              <button type="submit" class="btn btn-primary btn-sm px-6" :disabled="submitting">
                <span v-if="!submitting">
                  <i class="bi bi-check-circle me-1"></i> {{ isEdit ? 'Actualizar' : 'Guardar' }}
                </span>
                <span v-else class="spinner-border spinner-border-sm"></span>
              </button>
            </div>
          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';
import HeaderColor from '@/components/tabla/HeaderColor.vue';

import useCasillaStore from '@/stores/casillas/casillasPaginadoStore.js';
import useDesignacionStore from '@/stores/designaciones/designacionStore';

const store = useCasillaStore();
const designacionStore = useDesignacionStore();

const props = defineProps({
  item : {
    type     : Object,
    required : false,
    default  : null
  }
});

let modal = null;
const modalRef = ref(null);
const formRef = ref(null);
const submitting = ref(false);

const isEdit = computed(() => !!props.item?.id);
const formData = ref({ ...store.default });

const designacionOptions = ref([]);
const loadingDesignaciones = ref(false);
let searchTimeout = null;

const buscarDesignaciones = (query) => {
  clearTimeout(searchTimeout);
  if (!query || query.length < 2) {
    designacionOptions.value = [];
    return;
  }
  searchTimeout = setTimeout(async () => {
    loadingDesignaciones.value = true;
    try {
      designacionOptions.value = await designacionStore.searchDesignaciones(query);
    } catch (error) {
      console.error('Error buscando designaciones:', error);
    } finally {
      loadingDesignaciones.value = false;
    }
  }, 500);
};

// Sincroniza y precarga la designacion actual
watch(() => props.item, async (newVal) => {
  if (newVal && newVal.id) {
    formData.value = { 
      ...newVal,
      activo: newVal.activo ? 1 : 0
    };
    if (formData.value.designacion_id) {
      const resumen = await designacionStore.fetchResumenByDesignacionId(formData.value.designacion_id);
      if (resumen) {
        designacionOptions.value = [resumen];
      }
    }
  } else {
    formData.value = { 
      ...store.default,
      activo: 1
    };
    designacionOptions.value = [];
  }
}, { immediate: true, deep: true });

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

const rules = {
  numero : [
    { required: true, message: 'El número de casilla es obligatorio', trigger: 'blur' }
  ],
  designacion_id : [
    { required: true, message: 'La designación es obligatoria', trigger: 'change' }
  ],
  fecha_inicio : [
    { required: true, message: 'La fecha de inicio es obligatoria', trigger: 'change' }
  ]
};

const submit = async () => {
  if (!formRef.value) return;
  await formRef.value.validate(async (valid) => {
    if (!valid) return;
    submitting.value = true;
    try {
      if (isEdit.value) {
        await store.update(formData.value);
        Swal.fire('Actualizado', 'La casilla ha sido actualizada con éxito.', 'success');
      } else {
        await store.create(formData.value);
        Swal.fire('Creado', 'La casilla ha sido creada con éxito.', 'success');
      }
      cerrar();
      await store.get(); 
    } catch (e) {
      console.error(e);
      Swal.fire('Error', 'Hubo un error al guardar la casilla: ' + (e.response?.data?.message || e.message || e), 'error');
    } finally {
      submitting.value = false;
    }
  });
};
</script>

<style scoped>
.modal.show {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}
</style>