<template>
  <div class="botones-contenedor">
    <div class="botones-label"></div>
    <div class="botones-wrapper" :class="layoutClass">
      <template v-if="botonesPermitidos && botonesPermitidos.length">
        <button
          v-for="(btn, idx) in botonesPermitidos"
          :key="btn.key || idx"
          :type="btn.type || 'button'"
          :class="['btn accion-btn', btn.class || 'btn-light-primary']"
          v-bind="btn.attrs"
          tabindex="0"
          @click="() => handleClick(btn)"
          @keydown.esc="handleEsc"
          @keydown.left="e => focusBtn(idx - 1, e)"
          @keydown.right="e => focusBtn(idx + 1, e)"
          @keydown.backspace="handleBackspace"
        >
          <i v-if="btn.icon" :class="btn.icon + ' me-1'"></i>
          <span class="btn-text">{{ btn.label }}</span>
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { nextTick, computed, ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
  showActions : {
    type    : Boolean,
    default : false
  },
  modalAttrs : {
    type    : Object,
    default : () => ({})
  },
  modalAttrsVer : {
    type    : Object,
    default : () => ({})
  },
  modalAttrsEditar : {
    type    : Object,
    default : () => ({})
  },
  modalAttrsEliminar : {
    type    : Object,
    default : () => ({})
  },
  modalAttrsAsignarCargo : {
    type    : Object,
    default : () => ({})
  },
  buttons : {
    type    : Array,
    default : () => []
  },
  permissions : {
    type    : Array,
    default : () => []
  }
});

const emit = defineEmits([
  'ver', 'editar', 'agregar', 'eliminar', 'recibir', 'observar', 'aceptar',
  'focus-row', 'custom', 'clear-multi-select'
]);

// Variables reactivas simplificadas
const windowWidth = ref(window.innerWidth);

// Computed para permisos
const botonesPermitidos = computed(() => {
  const permisos = (props.permissions || []).map(p => p.name || p);
  return (props.buttons || []).filter(btn => {
    return !btn.permission || permisos.includes(btn.permission);
  });
});

// Layout automático simplificado
const layoutClass = computed(() => {
  const numBotones = botonesPermitidos.value.length;
  
  if (numBotones === 0) return 'layout-vacio';
  if (numBotones <= 3) return 'layout-lineal';
  if (numBotones >= 6 || windowWidth.value < 1200) return 'layout-envuelto';
  
  return 'layout-compacto';
});

// Event handlers simplificados
function handleClick(btn) {
  if (btn.event) emit(btn.event);
  else emit('custom', btn);
}

function handleBackspace() {
  emit('clear-multi-select');
}

function focusFirstButton() {
  nextTick(() => {
    const btns = document.querySelectorAll('.botones-wrapper button');
    if (btns.length > 0) btns[0]?.focus();
  });
}

function focusBtn(idx, e) {
  e.preventDefault();
  const btns = Array.from(
    e.currentTarget.closest('.botones-wrapper')?.querySelectorAll('button') || []
  );
  if (idx < 0) idx = btns.length - 1;
  if (idx >= btns.length) idx = 0;
  nextTick(() => {
    btns[idx]?.focus();
  });
}

function handleEsc(e) {
  e.preventDefault();
  emit('focus-row');
}

// Listener de resize simplificado
function updateWindowWidth() {
  windowWidth.value = window.innerWidth;
}

onMounted(() => {
  window.addEventListener('resize', updateWindowWidth);
});

onUnmounted(() => {
  window.removeEventListener('resize', updateWindowWidth);
});

defineExpose({
  focusFirstButton
});
</script>

<style scoped>
.botones-contenedor {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.botones-label {
  height: 15px;
  margin-bottom: 0.15rem;
  font-size: 0.85rem;
  line-height: 18px;
  color: transparent;
  user-select: none;
}

.botones-wrapper {
  min-height: 42px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Layouts simplificados */
.layout-vacio {
  display: none;
}

.layout-lineal {
  flex-wrap: nowrap;
  justify-content: flex-start;
}

.layout-compacto {
  flex-wrap: nowrap;
  justify-content: flex-start;
  gap: 0.4rem;
}

.layout-envuelto {
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.6rem;
}

.accion-btn {
  height: 42px !important;
  min-width: 85px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.5;
  box-sizing: border-box;
  white-space: nowrap;
  flex-shrink: 0;
  border-radius: 0.375rem;
}

.accion-btn:focus {
  box-shadow: 0 0 0 0.25rem rgba(var(--kt-primary-rgb), 0.25);
  outline: none;
  position: relative;
  z-index: 5;
}

/* Responsive optimizado */
@media (max-width: 1199px) and (min-width: 768px) {
  .layout-lineal .accion-btn:nth-child(n+4) .btn-text,
  .layout-compacto .accion-btn:nth-child(n+4) .btn-text {
    display: none;
  }
  
  .layout-lineal .accion-btn:nth-child(n+4) i,
  .layout-compacto .accion-btn:nth-child(n+4) i {
    margin-right: 0 !important;
  }
}

@media (max-width: 767px) {
  .botones-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(50px, 1fr));
    gap: 0.3rem;
    justify-content: center;
  }
  
  .accion-btn {
    min-width: 50px;
    width: 100%;
    padding: 0.4rem 0.2rem;
  }
  
  .accion-btn .btn-text {
    display: none;
  }
  
  .accion-btn i {
    margin-right: 0 !important;
  }
}

@media (max-width: 575px) {
  .botones-wrapper {
    grid-template-columns: repeat(4, 1fr);
  }
  
  .accion-btn {
    min-width: 45px;
    height: 38px !important;
    padding: 0.3rem 0.1rem;
  }
}
</style>
 