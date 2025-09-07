<template>
  <div>
    <Filtro />
    <BotonesAccion
      ref="botonesAccionRef"
      :buttons="botonesAccion"
      :permissions="authStore.permisosAccion"
      @ver="abrir"
      @editar="editar"
      @agregar="agregar"
      @eliminar="() => eliminar(store.seleccionados)"
    />
    <Lista />
    <Formulario
      ref="formularioRef"
      :item="item"
    />
    <Ver
      ref="verRef"
      :item="item"
      @editar="editar"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, useTemplateRef, watch } from 'vue'
import Swal from 'sweetalert2'

import BotonesAccion from '@/components/tabla/BotonesAccion.vue'
import Lista from './Lista.vue'
import Filtro from './Filtro.vue'
import Formulario from './Formulario.vue'
import Ver from './Ver.vue'

import useAuthStore from '@/stores/auth/authStore'
import useCasillaStore from '@/stores/casillas/casillasPaginadoStore.js'

const store = useCasillaStore();
const authStore = useAuthStore();

const formularioRef = useTemplateRef('formularioRef')

const item = ref({ ...store.default })

watch(() => store.seleccionados, (newVal) => {
  if (newVal && newVal.length === 1) {
    item.value = { ...store.default, ...newVal[0] }
  } else {
    item.value = { ...store.default }
  }
}, { immediate: true, deep: true })

onMounted( async () => {
  await store.get();
});

const botonesAccion = computed(() => {
  if (store.tieneSeleccionados) {
    return [
      {
        label      : 'Ver',
        icon       : 'bi bi-eye',
        class      : 'btn-light-primary',
        event      : 'ver',
        permission : 'casilla.ver'
      },
      {
        label      : 'Editar',
        icon       : 'bi bi-pencil',
        class      : 'btn-light-warning',
        event      : 'editar',
        permission : 'casilla.editar'
      },
      {
        label      : 'Eliminar',
        icon       : 'bi bi-trash',
        class      : 'btn-light-danger',
        event      : 'eliminar',
        permission : 'casilla.eliminar' 
      }
    ]
  } else {
    return [
      {
        label      : 'Nueva Casilla',
        icon       : 'bi bi-plus',
        class      : 'btn-light-primary',
        event      : 'agregar',
        permission : 'casilla.crear'
      }
    ]
  }
})

function agregar() {
  item.value = { ...store.default }
  formularioRef.value.abrir()
}

function editar() {
  formularioRef.value.abrir()
}

function eliminar(rowItem) {
  if (!rowItem || !rowItem.id) return

  Swal.fire({
    title             : '¿Estás seguro?',
    text              : 'Esta acción no se puede revertir',
    icon              : 'warning',
    showCancelButton  : true,
    confirmButtonText : 'Sí, eliminar',
    cancelButtonText  : 'Cancelar'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await store.delete({ id: rowItem.id })
        Swal.fire('Eliminado', 'El usuario ha sido eliminado correctamente', 'success')
      } catch (error) {
        console.error('Error al eliminar usuario:', error)
        Swal.fire('Error', 'No se pudo eliminar el usuario', 'error')
      }
    }
  })
}
</script>