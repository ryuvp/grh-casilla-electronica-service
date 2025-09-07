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
      <td class="cell-ellipsis">{{ item.numero }}</td>
      <td class="cell-ellipsis">{{ item.tipo_nombre }}</td>
      <td class="cell-ellipsis">{{ item.titular_id }}</td>
      <td class="cell-ellipsis">{{ item.fecha_inicio }}</td>
      <td class="cell-ellipsis">{{ item.fecha_fin }}</td>
      <td class="cell-ellipsis">{{ item.activo }}</td>
    </template>
  </TablaBackend>
</template>

<script setup>
import { ref } from 'vue'
import TablaBackend from '@/components/tabla/TablaBackend.vue'
import useCasillaStore from '@/stores/casillas/casillasPaginadoStore.js'

const store = useCasillaStore();

const columns = ref([
  { columnName: 'Número de Casilla', columnLabel: 'Número', sortEnabled: false, width: '15%' },
  { columnName: 'Tipo de Titular', columnLabel: 'Tipo', sortEnabled: false, width: '20%' },
  { columnName: 'Titular', columnLabel: 'Titular', sortEnabled: false, width: '15%' },
  { columnName: 'Fecha de Inicio', columnLabel: 'Fecha de Inicio', sortEnabled: false, width: '15%' },
  { columnName: 'Fecha de Fin', columnLabel: 'Fecha de Fin', sortEnabled: false, width: '15%' },
  { columnName: 'Activo', columnLabel: 'Activo', sortEnabled: false, width: '10%' },
]);

const tablaRef = ref();

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
</script>