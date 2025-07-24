<template>
  <ul class="list-group list-group-flush">
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
  </ul>
</template>

<script setup>
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
