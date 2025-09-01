<template>
  <!-- <ul class="list-group list-group-flush">
    <li
      v-for="msg in props.mensajes"
      :key="msg.id"
      class="list-group-item d-flex justify-content-between align-items-start py-3"
      :class="{ active: props.selected?.id === msg.id }"
      style="cursor: pointer;"
      @click="$emit('seleccionar', msg)"
    >
      <div class="me-auto">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
          <i class="bi-send-fill text-success"></i>
          Para: <span class="text-muted">Usuario {{ msg.usuario_destino_id }}</span>
        </div>
        <div>{{ msg.asunto }}</div>
      </div>
      <div class="text-muted small">
        {{ formatDate(msg.fecha_envio) }}
      </div>
    </li>
  </ul> -->

  <Tabla
    ref="tablaRef"
    :items="props.mensajes"
    :columns="encabezadoTabla"
    :pagination="props.pagination"
    :multi-select="true"
    :selected-items="props.seleccionados"
    @items-per-page-change="handleSizeChange"
    @row-select="handleSeleccion"
    @sort="handleSort"
    @page-change="handlePageChange"
  >
    <template #row="{ item }">
      <div class="me-auto">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
          <i class="bi-send-fill text-success"></i>
          Para: <span class="text-muted">Usuario {{ item.usuario_destino_id }}</span>
        </div>
        <div>{{ item.asunto }}</div>
      </div>
      <div class="text-muted small">
        {{ formatDate(item.fecha_envio) }}
      </div>
    </template>
  </Tabla>
</template>

<script setup>

import { ref } from 'vue'
import Tabla from '@/components/tablas/TablaBackend.vue'

import { format } from 'date-fns'
import { es } from 'date-fns/locale'

const props = defineProps({
  mensajes : {
    type     : Array,
    required : true
  },
  selected : {
    type    : Object,
    default : null
  }
})

const encabezadoTabla = ref([
  { columnName: "MENSAJES ENVIADOS", columnLabel: "enviados", sortEnabled: true, width: "100%" },
]);

defineEmits(['seleccionar'])

function formatDate(fechaStr) {
  if (!fechaStr) return ''
  const fecha = new Date(fechaStr)
  return format(fecha, 'dd/MM/yyyy HH:mm', { locale: es })
}


</script>

<style scoped>
.list-group-item.active {
  background-color: #f0f7ff;
  border-left: 4px solid #0d6efd;
}
</style>
