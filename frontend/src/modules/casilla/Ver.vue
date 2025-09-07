<template>
  <div ref="modalRef" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue';
import { Modal } from 'bootstrap';

let modal = null;
const modalRef = ref(null);

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
</script>