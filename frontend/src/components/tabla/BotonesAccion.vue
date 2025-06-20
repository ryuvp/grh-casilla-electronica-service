<template>
  <div class="botones-contenedor">
    <!-- Etiqueta invisible para alinear con filtros -->
    <div class="botones-label">Acciones</div>
    <div class="botones-wrapper">
      <template v-if="botonesPermitidos && botonesPermitidos.length ">
        <button
          v-for="(btn, idx) in botonesPermitidos"
          :key="btn.key || idx"
          :type="btn.type || 'button'"
          :class="['btn accion-btn', btn.class || 'btn-light-primary']"
          v-bind="btn.attrs"
          @click="() => handleClick(btn)"
          @keydown.esc="handleEsc"
          @keydown.left="e => focusBtn(idx - 1, e)"
          @keydown.right="e => focusBtn(idx + 1, e)"
          @keydown.backspace="handleBackspace"
          tabindex="0"
        >
          <i v-if="btn.icon" :class="btn.icon + ' me-1'"></i>
          <span>{{ btn.label }}</span>
        </button>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, computed} from "vue";
import useAuthStore from "@/stores/auth/authStore";

// Definimos el store de autenticación
const authStore = useAuthStore();

/**
 * Configuración de botones de acción
 * @typedef {Object} Button - Configuración de un botón
 * @property {string|number} [key] - Identificador único opcional
 * @property {string} label - Texto a mostrar
 * @property {string} [icon] - Clase del ícono (ej: 'bi bi-eye')
 * @property {string} [class] - Clase CSS adicional
 * @property {Record<string,any>} [attrs] - Atributos adicionales (ej: para modales)
 * @property {string} [event] - Nombre del evento a emitir al hacer clic
 */
const props = defineProps<{
  /** Mostrar los botones de acción */
  showActions?: boolean,
  /** Atributos para modal genérico */
  modalAttrs?: Record<string, any>,
  /** Atributos para modal de ver */
  modalAttrsVer?: Record<string, any>,
  /** Atributos para modal de editar */
  modalAttrsEditar?: Record<string, any>,
  /** Atributos para modal de eliminar */
  modalAttrsEliminar?: Record<string, any>,
  /** Atributos para modal de asignar cargo */
  modalAttrsAsignarCargo?: Record<string, any>,
  /** Configuración de botones */
  buttons?: Array<{
    key?: string|number,
    label: string,
    icon?: string,
    class?: string,
    attrs?: Record<string, any>,
    event?: string,
    permission?: string | null, // Permiso para mostrar el botón
  }>
}>();

/**
 * Eventos que puede emitir el componente
 */
const emit = defineEmits([
  'ver',             // Ver detalle
  'editar',          // Editar elemento
  'agregar',         // Agregar elemento
  'eliminar',
  'asignarcargo',       // Asignar cargo
  'focus-row',       // Enfocar fila en tabla
  'custom',          // Evento personalizado
  'clear-multi-select' // Limpiar selección múltiple
]);

/**
 * Maneja clic en un botón de acción
 * @param {Object} btn - Configuración del botón
 */
function handleClick(btn) {
  if (btn.event) emit(btn.event);
  else emit('custom', btn);
}

/**
 * Computa los botones visibles según permisos
 */
 const botonesPermitidos = computed(() => {
  const permisos = authStore.permisosAccion.map(p => p.name || p);
  return (props.buttons || []).filter(btn => {
    return !btn.permission || permisos.includes(btn.permission);
  });
});

/**
 * Tecla Backspace para limpiar selección múltiple
 */
function handleBackspace() {
  emit('clear-multi-select');
}

/**
 * Enfoca el primer botón solo cuando lo pide explícitamente
 */
function focusFirstButton() {
  nextTick(() => {
    const btns = document.querySelectorAll('.botones-wrapper button');
    if (btns.length > 0) (btns[0] as HTMLElement)?.focus();
  });
}

/**
 * Navegación con flechas entre botones de acción
 * @param {number} idx - Índice del botón a enfocar
 * @param {KeyboardEvent} e - Evento de teclado
 */
function focusBtn(idx: number, e: KeyboardEvent) {
  e.preventDefault();
  const btns = Array.from(
    (e.currentTarget as HTMLElement).closest('.botones-wrapper')?.querySelectorAll('button') || []
  );
  if (idx < 0) idx = btns.length - 1;
  if (idx >= btns.length) idx = 0;
  nextTick(() => {
    (btns[idx] as HTMLElement)?.focus();
  });
}

/**
 * Al presionar ESC, emite evento para devolver el foco a la fila
 * @param {KeyboardEvent} e - Evento de teclado
 */
function handleEsc(e: KeyboardEvent) {
  e.preventDefault();
  emit('focus-row');
}

// Exponer métodos para el componente padre
defineExpose({
  focusFirstButton
});
</script>

<style scoped>
.botones-contenedor {
  display: flex;
  flex-direction: column;
}

.botones-label {
  height: 18px; /* Altura exacta igual que el label del filtro */
  margin-bottom: 0.25rem; /* Igual que el filtro */
  font-size: 0.85rem;
  line-height: 18px;
  color: transparent; /* Transparente pero ocupa espacio */
  user-select: none; /* No seleccionable */
}

.botones-wrapper {
  height: 42px; /* Exactamente la misma altura que los inputs */
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.accion-btn {
  height: 42px !important; /* Altura exacta */
  min-width: 110px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  padding: 0.5rem 1.5rem;
  font-size: 0.95rem;
  line-height: 1.5;
  box-sizing: border-box;
}

.accion-btn:focus {
  box-shadow: 0 0 0 0.25rem rgba(var(--kt-primary-rgb), 0.25);
  outline: none;
  position: relative;
  z-index: 5;
}
</style>
