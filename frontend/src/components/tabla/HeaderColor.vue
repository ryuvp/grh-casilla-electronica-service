<template>
  <div
    class="modal-header d-flex align-items-center justify-content-between"
    :style="headerStyle"
  >
    <div class="flex-grow-1">
      <h3 class="mb-0 fw-bold" :style="{ color: headerStyle.color }">
        <slot />
      </h3>
    </div>
    <button
      type="button"
      class="btn btn-icon btn-sm btn-light btn-active-color-danger rounded-circle shadow-sm ms-2"
      @click="emitClose"
      aria-label="Cerrar"
    >
      <slot name="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.5em" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
          <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
        </svg>
      </slot>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// Props
const props = defineProps<{
  accion: 'ver' | 'editar' | 'agregar',
  bgColor?: string,
  textColor?: string
}>()
// Emit close event
const emit = defineEmits<{ (e: 'close'): void }>()
function emitClose() {
  emit('close')
}

// Default color map
const defaultColorsMap: Record<string, { bg: string; text: string }> = {
  ver:    { bg: '#bbd2f3', text: '#084298' },
  editar: { bg: '#fff3cd', text: '#664d03' },
  agregar:{ bg: '#d1e7dd', text: '#08632a' },
}

// Computed inline style
const headerStyle = computed(() => {
  const defaults = defaultColorsMap[props.accion]
  return {
    backgroundColor: props.bgColor || defaults.bg,
    color:           props.textColor || defaults.text,
    padding:         '1rem 1.5rem',
    height:          '50px'
  }
})
</script>