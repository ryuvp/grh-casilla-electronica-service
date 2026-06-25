<template>
  <TablaBackend
    ref="tablaRef"
    :items="store.list"
    :columns="columns"
    :pagination="store.pagination"
    :multi-select="false"
    :selected-items="store.seleccionados"
    @items-per-page-change="handleSizeChange"
    @row-select="handleSeleccion"
    @sort="handleSort"
    @page-change="handlePageChange"
  >
    <template #row="{ item }">
      <!-- Número de Casilla -->
      <td class="align-middle py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-mailbox fs-4 text-primary"></i>
          <span class="fw-bold text-dark fs-6">{{ item.numero }}</span>
        </div>
      </td>

      <!-- Usuario y Cargo -->
      <td class="align-middle py-3 px-4">
        <div v-if="getDesignacion(item)" class="d-flex flex-column">
          <span class="fw-bold text-dark text-uppercase fs-7">{{ getDesignacion(item).usuario_nombre }}</span>
          <span class="text-muted small text-uppercase" style="font-size: 11px;">
            {{ getDesignacion(item).cargo_nombre }} - {{ getDesignacion(item).dependencia_nombre }}
          </span>
        </div>
        <div v-else class="text-muted small">
          <span class="spinner-border spinner-border-sm me-1 text-primary"></span> Resolviendo...
        </div>
      </td>

      <!-- Vigencia -->
      <td class="align-middle py-3 px-4 text-muted small">
        <div class="d-flex flex-column">
          <span><strong>Desde:</strong> {{ formatFecha(item.fecha_inicio) }}</span>
          <span><strong>Hasta:</strong> {{ formatFecha(item.fecha_fin) }}</span>
        </div>
      </td>

      <!-- Estado -->
      <td class="align-middle py-3 px-4">
        <span v-if="item.activo" class="badge bg-success bg-opacity-25 text-success border border-success px-3 py-1">
          ACTIVO
        </span>
        <span v-else class="badge bg-danger bg-opacity-25 text-danger border border-danger px-3 py-1">
          INACTIVO
        </span>
      </td>
    </template>
  </TablaBackend>
</template>

<script setup>
import { ref, watch } from 'vue'
import TablaBackend from '@/components/tabla/TablaBackend.vue'
import useCasillaStore from '@/stores/casillas/casillasPaginadoStore.js'
import useDesignacionStore from '@/stores/designaciones/designacionStore'
import { formatDateLima } from '@/core/utils/dateTime'

const store = useCasillaStore();
const designacionStore = useDesignacionStore();

const columns = ref([
  { columnName: 'Número de Casilla', columnLabel: 'numero', sortEnabled: false, width: '20%' },
  { columnName: 'Usuario / Cargo', columnLabel: 'designacion_id', sortEnabled: false, width: '45%' },
  { columnName: 'Vigencia de Casilla', columnLabel: 'fecha_inicio', sortEnabled: false, width: '20%' },
  { columnName: 'Estado', columnLabel: 'activo', sortEnabled: false, width: '15%' },
]);

const tablaRef = ref();
const designacionesMap = ref({});

function handleSeleccion({ selectedItems }) {
  store.setSeleccionados(selectedItems)
}

function handlePageChange(page) {
  store.get({},{page})
}

function handleSizeChange(size) {
  store.setPageSize(size)
  store.get()
}

function handleSort({ label, order }) {
  store.limpiarSeleccion()
  store.get({ sort: label, order })
}

function formatFecha(fecha) {
  if (!fecha) return 'Indefinido';
  return formatDateLima(fecha)
}

function getDesignacion(item) {
  return designacionesMap.value[item.designacion_id] || null;
}

async function cargarDesignaciones(items = []) {
  const idsValidos = [...new Set(items.map(i => i?.designacion_id).filter(Boolean))];
  if (!idsValidos.length) return;

  const actores = await Promise.all(
    idsValidos.map(id => designacionStore.fetchResumenByDesignacionId(id))
  );

  const nuevoMap = {};
  idsValidos.forEach((id, index) => {
    if (actores[index]) {
      nuevoMap[id] = actores[index];
    }
  });

  designacionesMap.value = {
    ...designacionesMap.value,
    ...nuevoMap
  };
}

watch(() => store.list, (newVal) => {
  if (newVal) {
    cargarDesignaciones(newVal);
  }
}, { immediate: true, deep: true });
</script>

<style scoped>
:deep(.table-container) {
  height: 100%;
  display: flex;
  flex-direction: column;
}

:deep(.custom-scrollbar) {
  flex: 1 1 auto;
  min-height: 0;
}

:deep(.table-responsive) {
  flex: 1 1 auto;
  min-height: 0;
}

:deep(table) {
  margin-bottom: 0;
}

:deep(tbody tr) {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

:deep(tbody tr:hover) {
  background-color: #f8f9fa !important;
}

:deep(tbody tr.table-success) {
  background-color: #e8f0fe !important;
}

:deep(tbody tr.table-success .text-dark) {
  color: #1a73e8 !important;
}
</style>