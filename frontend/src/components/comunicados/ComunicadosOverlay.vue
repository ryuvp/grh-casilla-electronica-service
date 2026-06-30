<template>
  <Teleport to="body">
    <Transition name="comunicado-fade">
      <div v-if="visible && actual"
        class="comunicado-overlay position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-4"
        role="dialog" aria-modal="true">
        <div class="comunicado-backdrop position-absolute top-0 start-0 w-100 h-100 z-0 pe-none"></div>

        <main v-if="contenidoVisible"
          class="comunicado-stage position-relative z-1 d-flex align-items-center justify-content-center w-100 h-100">
          <Transition name="comunicado-slide" mode="out-in">
            <div :key="actual.id" :class="[
              'd-flex flex-column align-items-center gap-4',
              { 'comunicado-media--compact': actual.url_acceso },
            ]">
              <img v-if="actual.archivo_tipo === 'image' && actual.archivo_url" :src="actual.archivo_url"
                :alt="actual.titulo" class="comunicado-preview d-block w-auto object-fit-contain rounded-4 shadow-lg" />


              <div v-else
                class="comunicado-empty d-flex flex-column align-items-center justify-content-center gap-3 text-white text-center">
                <i class="bi bi-megaphone fs-3x"></i>
                <span>El comunicado está disponible, pero no tiene vista previa.</span>
              </div>

              <a v-if="actual.url_acceso" :href="actual.url_acceso" target="_blank" rel="noopener noreferrer"
                class="comunicado-link comunicado-glass d-flex align-items-center justify-content-center gap-2 min-h-42px px-4 py-3 rounded-3 shadow text-dark fw-bold text-decoration-none">
                <i class="bi bi-box-arrow-up-right flex-shrink-0"></i>
                <span class="min-w-0 overflow-hidden text-center text-truncate">{{ actual.url_acceso }}</span>
              </a>
            </div>
          </Transition>
        </main>

        <footer v-if="contenidoVisible"
          class="position-absolute start-50 bottom-0 z-2 d-flex align-items-center justify-content-center gap-3 mb-6 translate-middle-x comunicado-actions">
          <button v-if="indiceActual > 0" type="button"
            class="comunicado-action comunicado-action--ghost btn min-w-112px h-45px rounded-4 fw-semibold"
            @click="anterior">
            Anterior
          </button>

          <button type="button"
            class="comunicado-action comunicado-action--primary btn min-w-112px h-45px rounded-4 fw-semibold"
            @click="siguiente">
            {{ indiceActual + 1 < total ? 'Siguiente' : 'Entendido' }} </button>
        </footer>

        <div v-if="contenidoVisible"
          class="position-absolute top-0 end-0 z-3 d-flex align-items-center gap-3 mt-6 me-6">
          <span v-if="total > 1" class="comunicado-glass shadow-sm rounded-pill px-3 py-2 text-dark fw-bold">
            {{ indiceActual + 1 }} / {{ total }}
          </span>

          <button type="button"
            class="comunicado-glass btn btn-icon btn-active-light rounded-circle shadow-sm text-black cursor-pointer"
            aria-label="Cerrar" @click="cerrarTodo">
            <i class="bi bi-x-lg text-black"></i>
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import useComunicadosStore from '@/stores/comunicados/comunicadosStore.js'

const store = useComunicadosStore()
const visible = ref(false)
const contenidoVisible = ref(false)
const indiceActual = ref(0)
const vistos = ref(new Set())

const total = computed(() => store.items.length)
const actual = computed(() => store.items[indiceActual.value] || null)

async function cargar() {
  try {
    contenidoVisible.value = false
    await store.cargarVisibles()
    if (store.items.length) {
      indiceActual.value = 0
      visible.value = true
      await nextTick()
      await esperarPintadoBackdrop()
      contenidoVisible.value = true
      await registrarActual()
    }
  } catch (error) {
    console.error('No se pudieron cargar los comunicados visibles:', error)
  }
}

function esperarPintadoBackdrop() {
  if (typeof requestAnimationFrame !== 'function') return Promise.resolve()

  return new Promise((resolve) => {
    requestAnimationFrame(() => {
      requestAnimationFrame(resolve)
    })
  })
}

async function registrarActual() {
  const comunicado = actual.value
  if (!comunicado || vistos.value.has(comunicado.id)) return

  vistos.value.add(comunicado.id)

  try {
    await store.registrarVista(comunicado.id)
  } catch (error) {
    vistos.value.delete(comunicado.id)
    console.error('No se pudo registrar la visualización del comunicado:', error)
  }
}

async function siguiente() {
  if (indiceActual.value + 1 < total.value) {
    indiceActual.value += 1
    await registrarActual()
    return
  }

  cerrarTodo()
}

async function anterior() {
  if (indiceActual.value === 0) return
  indiceActual.value -= 1
  await registrarActual()
}

function cerrarTodo() {
  contenidoVisible.value = false
  visible.value = false
}

defineExpose({ cargar })
</script>

<style scoped>
.comunicado-overlay {
  z-index: 11000;
  background: rgba(255, 255, 255, .56);
}

.comunicado-backdrop {
  backdrop-filter: blur(16px) saturate(115%);
  -webkit-backdrop-filter: blur(16px) saturate(115%);
  transform: translateZ(0);
  will-change: backdrop-filter;
}

.comunicado-glass {
  background: rgba(255, 255, 255, .9);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.comunicado-stage {
  padding: 4.8rem 1rem 5.8rem;
}

.comunicado-preview {
  max-width: min(94vw, 1100px);
  max-height: min(80vh, 860px);
}


.comunicado-media--compact .comunicado-preview {
  max-height: min(72vh, 780px);
}


.comunicado-empty {
  min-height: 280px;
}

.comunicado-link {
  width: min(94vw, 410px);
  color: #0f172a;
}

.comunicado-link:hover {
  color: #0f766e !important;
}

.comunicado-action {
  transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease;
}

.comunicado-action:hover {
  transform: translateY(-1px);
}

.comunicado-action--ghost {
  color: #334155 !important;
  background: rgba(255, 255, 255, .9) !important;
  box-shadow: 0 12px 28px rgba(15, 23, 42, .16) !important;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.comunicado-action--primary,
.comunicado-action--primary:hover,
.comunicado-action--primary:focus {
  color: #fff !important;
  background: linear-gradient(135deg, #0f766e, #059669) !important;
  box-shadow: 0 16px 34px rgba(5, 150, 105, .36) !important;
}

.comunicado-slide-enter-active,
.comunicado-slide-leave-active {
  transition: opacity .18s ease, transform .18s ease;
}

.comunicado-slide-enter-from,
.comunicado-slide-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@media (max-width: 767.98px) {
  .comunicado-stage {
    padding: 4.5rem .25rem 6rem;
  }

  .comunicado-actions {
    width: calc(100% - 2rem);
    flex-wrap: wrap;
  }
}
</style>
