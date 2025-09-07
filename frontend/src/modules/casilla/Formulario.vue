<template>
  <div ref="modalRef" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
      <div class="modal-content rounded">
        <HeaderColor
          :accion="isEdit ? 'editar' : 'agregar'"
          :bg-color="isEdit ? '#fff3cd' : '#d1e7dd'"
          :text-color="isEdit ? '#664d03' : '#08632a'"
          data-bs-dismiss="modal"
          aria-label="Cerrar"
        >
          <i class="bi bi-folder-symlink-fill bi fs-3" :style="{ color: isEdit ? '#664d03' : '#08632a' }"></i>
          {{ isEdit ? 'Actualizar Casilla' : 'Nueva Casilla' }}
        </HeaderColor>
        <div class="modal-body px-0 pt-0 pb-2">
          <el-form 
            ref="formRef" 
            :model="formData" 
            :rules="rules" 
            class="form" 
            @submit.prevent="submit" 
          >
            <div>
              form
            </div>
          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref, computed } from 'vue';
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap';
import HeaderColor from '@/components/tabla/HeaderColor.vue';

import useCasillaStore from '@/stores/casillas/casillasPaginadoStore.js'
const store = useCasillaStore();

const props = defineProps({
  item : {
    type     : Object,
    required : false,
    default  : null
  }
});

let modal = null;
const modalRef = ref(null);

const isEdit = computed(() => !!props.item?.id);

onMounted( async () => {
  if (modalRef.value) {
    modal = new Modal(modalRef.value);
  }

});

const abrir = () => {
  console.log(formData.value)
  if (modal) modal.show();
}
const cerrar = () => {
  if (modal) modal.hide();
}
defineExpose({ abrir, cerrar });

const formData = ref(store.default);

const rules = {
  // Define validation rules here
};

const submit = async () => {
  try {
    if (isEdit.value) {
      await store.update(props.item.id, formData.value);
      Swal.fire('Actualizado', 'La casilla ha sido actualizada con éxito.', 'success');
    } else {
      await store.create(formData.value);
      Swal.fire('Creado', 'La casilla ha sido creada con éxito.', 'success');
    }
    cerrar();
    //await store.get(); // Refrescar la lista después de crear/actualizar
  } catch (e) {
    Swal.fire('Error', 'Hubo un error al guardar la casilla: ' + e.message, 'error');
  }
};
</script>