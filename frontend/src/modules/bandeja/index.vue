<template>
  <div class="card ce-bandeja">

    <!-- Filter bar -->
    <div class="ce-filter-bar">
      <Filtro :key="props.trayType" @buscar="handleBuscar" />
    </div>

    <!-- Full-width Gmail list -->
    <Lista
      :mensajes="mensajesFiltrados"
      :pagination="store.pagination"
      :selected="selected"
      @seleccionar="seleccionarMensaje"
      @page-change="handlePageChange"
      @items-per-page-change="handleSizeChange"
      @sort="handleSort"
    />

    <!-- Backdrop + Drawer via Teleport -->
    <Teleport to="body">

      <Transition name="backdrop">
        <div
          v-if="selected"
          class="ce-backdrop"
          @click="cerrarDrawer"
        />
      </Transition>

      <Transition name="drawer">
        <div v-if="selected" class="ce-drawer">
          <Contenido
            :mensaje="selected"
            :tray-type="props.trayType"
            @cerrar="cerrarDrawer"
            @mensaje-cambiado="refrescarBandeja"
          />
        </div>
      </Transition>

    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Filtro    from './Filtro.vue'
import Lista     from './Lista.vue'
import Contenido from './Contenido.vue'
import { useMensajesStore } from '@/stores/mensajes/mensajesStore'

const props = defineProps({
  trayType: { type: String, default: 'entrada' }
})

const store        = useMensajesStore()
const selected     = ref(null)
const queryFilters = ref({})
const sortParams   = ref({ sort: 'created_at', order: 'desc' })

onMounted(async () => { await cargarBandeja() })

watch(() => props.trayType, async () => {
  selected.value     = null
  queryFilters.value = {}
  sortParams.value   = { sort: 'created_at', order: 'desc' }
  await cargarBandeja({ page: 1 })
})

const mensajesFiltrados = computed(() => store.mensajes)

function seleccionarMensaje(mensaje) {
  selected.value = mensaje
  if (!mensaje.leido) store.marcarLeido(mensaje.id)
}

function cerrarDrawer() {
  selected.value = null
}

async function cargarBandeja(extraParams = {}) {
  const params = {
    page     : extraParams.page     || store.pagination.current_page || 1,
    per_page : extraParams.per_page || store.pagination.per_page     || 10,
    ...queryFilters.value,
    ...sortParams.value,
  }
  await store.fetchMensajes(props.trayType, params)
  await store.fetchCounts()
}

async function refrescarBandeja() {
  await cargarBandeja({ page: 1, per_page: store.pagination.per_page })
  selected.value = null
}

async function handlePageChange(page)       { await cargarBandeja({ page }) }
async function handleSizeChange(perPage)    { await cargarBandeja({ page: 1, per_page: perPage }) }
async function handleBuscar(filtros)        { queryFilters.value = filtros; await cargarBandeja({ page: 1 }) }
async function handleSort({ label, order }) {
  sortParams.value = { sort: label, order }
  await cargarBandeja({ page: 1 })
}
</script>
