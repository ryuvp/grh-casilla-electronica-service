<template>
  <ul class="list-group list-group-flush">
    <li
      v-for="msg in mensajes"
      :key="msg.id"
      class="list-group-item d-flex justify-content-between align-items-start py-3"
      :class="{ active: selected?.id === msg.id }"
      style="cursor: pointer;"
      @click="$emit('seleccionar', msg)"
    >
      <div class="me-auto">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
          <i :class="msg.leido ? 'bi-envelope-open-fill text-muted' : 'bi-envelope-fill text-primary'"></i>
          Yo, noreply
        </div>
        <div :class="{'fw-semibold': !msg.leido}">
          {{ msg.asunto }}
        </div>
      </div>
      <div class="text-muted small">
        {{ formatFecha(msg.fecha_envio) }}
      </div>
    </li>
  </ul>
</template>

<script setup>
import { defineProps } from 'vue'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

defineProps(['mensajes', 'selected'])

function formatFecha(fechaStr) {
  if (!fechaStr) return ''
  const fecha = new Date(fechaStr)
  return format(fecha, 'dd/MM/yyyy HH:mm', { locale: es })
}
</script>

<style scoped>
.list-group-item.active {
  background-color: #7fb4e6;
  border-left: 4px solid #0d6efd;
}
</style>
