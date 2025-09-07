<template>
  <div class="table-container">
    <div class="table-responsive custom-scrollbar">
      <table class="table align-middle table-row-dashed fs-6 gy-4">
        <thead class="sticky-thead fs-7">
          <tr>
            <th
              v-for="header in columns"
              :key="header.columnLabel"
              :class="[
                'text-start bg-thead',
                header.sortEnabled ? 'sortable' : '',
                sortState.label === header.columnLabel ? `table-sort-${sortState.order}` : '',
                'min-w-100px'
              ]"
              :style="getColumnStyle(header)"
              @click="header.sortEnabled && emitSort(header.columnLabel)"
            >
              {{ header.columnName }}
              <i
                v-if="header.sortEnabled"
                class="bi ms-2"
                :class="getSortIcon(header.columnLabel)"
              ></i>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(item, idx) in items"
            :key="item[idField] ?? idx"
            ref="rowRefs"
            :class="getRowClasses(idx)"
            :tabindex="0"
            :aria-selected="isRowSelected(idx)"
            @click="onRowClick($event, item, idx)"
            @keydown="onRowKeydown($event, item, idx)"
            @focus="scrollRowIntoView(idx)"
          >
            <slot name="row" :item="item" :idx="idx" :selected="isRowSelected(idx)">
              <td
                v-for="header in columns"
                :key="header.columnLabel"
                :style="getColumnStyle(header)"
                class="cell-ellipsis"
              >
                <span class="cell-ellipsis-content">
                  {{ item?.[header.columnLabel] ?? '-' }}
                </span>
              </td>
            </slot>
          </tr>
        </tbody>
      </table>

      <!-- Estado de carga -->
      <div v-if="loading" class="text-center py-5">
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
          
          <!-- Texto principal con degradado -->
          <div class="text-primary fw-bold fs-4 mb-0" style="background: linear-gradient(45deg, #0d6efd, #6f42c1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            Cargando datos
          </div>
          
          <!-- Barra de progreso indeterminada -->
          <div class="progress mt-3" style="width: 200px; height: 10px;">
            <div
              class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
              role="progressbar"
              style="width: 100%"
            ></div>
          </div>

          <!-- Texto descriptivo -->
          <div class="text-muted fs-6">
            Procesando información, por favor espere...
          </div>
        </div>
      </div>

      <!-- Sin datos (solo cuando no está cargando) -->
      <div v-else-if="items.length === 0" class="text-center py-5">
        <div class="d-flex flex-column align-items-center justify-content-center py-4">
          <div class="mb-3">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
          </div>
          <div class="text-muted fw-semibold fs-5 mb-2">
            No se encontraron registros
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div class="d-flex align-items-center">
        <span class="text-muted fs-7 fw-semibold me-2">Filas por página:</span>
        <select
          class="form-select form-select-sm form-select-solid w-auto"
          :value="pagination.per_page"
          @change="clearAndEmit(() => $emit('items-per-page-change', +$event.target.value))"
        >
          <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>

      <div class="fs-6 fw-semibold text-gray-700">
        Mostrando {{ pageStart + 1 }} a
        {{ Math.min(pageStart + pagination.per_page, pagination.total) }}
        de {{ pagination.total }} registros
      </div>

      <ul class="pagination mb-0">
        <li class="page-item" :class="{ disabled: isFirstPage }">
          <button class="page-link" @click="clearAndEmit(() => $emit('page-change', 1))">
            <i class="bi bi-chevron-double-left"></i>
          </button>
        </li>
        <li class="page-item" :class="{ disabled: isFirstPage }">
          <button class="page-link" @click="clearAndEmit(() => $emit('page-change', pagination.current_page - 1))">
            <i class="bi bi-chevron-left"></i>
          </button>
        </li>
        <li class="page-item disabled">
          <span class="page-link">{{ pagination.current_page }} / {{ totalPages }}</span>
        </li>
        <li class="page-item" :class="{ disabled: isLastPage }">
          <button class="page-link" @click="clearAndEmit(() => $emit('page-change', pagination.current_page + 1))">
            <i class="bi bi-chevron-right"></i>
          </button>
        </li>
        <li class="page-item" :class="{ disabled: isLastPage }">
          <button class="page-link" @click="clearAndEmit(() => $emit('page-change', totalPages))">
            <i class="bi bi-chevron-double-right"></i>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'

// === PROPIEDADES DEL COMPONENTE ===
const props = defineProps({
  items          : { type: Array, default: () => [] },       // Lista de registros a mostrar
  selectedItems  : { type: Array, default: () => [] },       // Registros seleccionados desde el padre
  columns        : { type: Array, default: () => [] },       // Configuración de columnas (etiqueta, orden, etc.)
  idField        : { type: String, default: 'id' },          // Campo único por fila
  pagination     : { type: Object, default: () => ({ current_page: 1, per_page: 10, total: 0 }) },
  perPageOptions : { type: Array, default: () => [10, 20, 50, 100] },
  sortLabel      : { type: String, default: '' },            // Columna actualmente ordenada
  sortOrder      : { type: String, default: 'asc' },         // Orden actual: asc | desc
  multiSelect    : { type: Boolean, default: true },         // Permitir selección múltiple
  loading        : { type: Boolean, default: false },        // Estado de carga
})

// === EVENTOS QUE EMITE EL COMPONENTE ===
const emit = defineEmits(['page-change', 'items-per-page-change', 'sort', 'row-select'])

// === ESTADO INTERNO ===
const selectedRowIndex = ref(null)     // Índice seleccionado (modo simple)
const selectedRow = ref(null)          // Objeto fila seleccionada (modo simple)
const selectedRows = ref([])           // Índices seleccionados (modo múltiple)
const lastSelectedIndex = ref(null)    // Último índice usado para selección por shift
const currentFocusIndex = ref(0)       // Para control de navegación con teclado
const rowRefs = ref([])                // Refs a filas HTML para focus

// === COMPUTADAS ===
const pageStart = computed(() => (props.pagination.current_page - 1) * props.pagination.per_page)
const totalPages = computed(() => Math.max(1, Math.ceil(props.pagination.total / props.pagination.per_page)))
const isFirstPage = computed(() => props.pagination.current_page === 1)
const isLastPage = computed(() => props.pagination.current_page >= totalPages.value)
const sortState = computed(() => ({ label: props.sortLabel, order: props.sortOrder }))
const selectedItems = computed(() => selectedRows.value.map(i => props.items[i]).filter(Boolean))

// === ORDENAMIENTO ===
const emitSort = (label) => {
  const newOrder = sortState.value.label === label && sortState.value.order === 'asc' ? 'desc' : 'asc'
  emit('sort', { label, order: newOrder })
}

const getSortIcon = (label) => {
  if (sortState.value.label !== label) return 'bi-sort-alpha-down'
  return sortState.value.order === 'asc' ? 'bi-sort-alpha-up' : 'bi-sort-alpha-down'
}

// === ESTILOS DE COLUMNAS ===
const getColumnStyle = (header) => {
  if (header?.width) {
    return {
      userSelect : 'none',
      width      : `${header.width}`,
      minWidth   : `${header.width}`,
    }
  }
  return {}
}

// === CLASES PARA FILAS SELECCIONADAS ===
const getRowClasses = (idx) => {
  if (!props.multiSelect && selectedRowIndex.value === idx) {
    return { 'table-success': true }
  }

  if (props.multiSelect) {
    const item = props.items[idx]
    const match = props.selectedItems.some(i => i.id === item.id)
    return { 'table-success': match }
  }

  return {}
}

// === SELECCIÓN POR TECLADO ===
const focusRow = (index) => {
  const item = props.items[index]
  if (item) {
    selectedRowIndex.value = index
    selectedRow.value = item
    emit('row-select', { index, item, selectedItems: selectedItems.value })
    nextTick(() => rowRefs.value[index]?.focus())
  }
}

// === VERIFICAR SI UNA FILA ESTÁ SELECCIONADA ===
const isRowSelected = (idx) => {
  return props.multiSelect ? selectedRows.value.includes(idx) : selectedRowIndex.value === idx
}

// === SELECCIÓN POR CLICK (incluye shift-click) ===
const onRowClick = (event, item, idx) => {
  // Shift-click para selección por rango
  if (props.multiSelect && event.shiftKey && lastSelectedIndex.value !== null) {
    const min = Math.min(lastSelectedIndex.value, idx)
    const max = Math.max(lastSelectedIndex.value, idx)
    selectedRows.value = []
    for (let i = min; i <= max; i++) selectedRows.value.push(i)
    lastSelectedIndex.value = idx
    selectedRowIndex.value = idx
    selectedRow.value = item
    emit('row-select', { index: idx, item, selectedItems: selectedItems.value })
    return
  }

  // Selección/deselección individual
  if (props.multiSelect) {
    const wasSelected = selectedRows.value.includes(idx)

    if (wasSelected) {
      selectedRows.value = selectedRows.value.filter(i => i !== idx)
    } else {
      selectedRows.value.push(idx)
    }

    lastSelectedIndex.value = idx
    selectedRowIndex.value = wasSelected ? null : idx
    selectedRow.value = wasSelected ? null : item

    emit('row-select', {
      index         : wasSelected ? null : idx,
      item          : wasSelected ? null : item,
      selectedItems : selectedItems.value
    })
  } else {
    const wasSelected = selectedRowIndex.value === idx

    if (wasSelected) {
      selectedRowIndex.value = null
      selectedRow.value = null
      selectedRows.value = []
    } else {
      selectedRowIndex.value = idx
      selectedRow.value = item
      selectedRows.value = []
    }

    emit('row-select', {
      index         : wasSelected ? null : idx,
      item          : wasSelected ? null : item,
      selectedItems : wasSelected ? [] : [item]
    })
  }
}

// === CONTROL DE TECLADO EN FILA ===
const onRowKeydown = (e, item, idx) => {
  if (e.key === 'Enter') {
    e.preventDefault()
    onRowClick(e, item, idx)
    return
  }

  if (['Backspace', 'Delete', 'Escape'].includes(e.key)) {
    clearRows()
    return
  }

  if (e.ctrlKey && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
    e.preventDefault()
    const delta = e.key === 'ArrowDown' ? 1 : -1
    const newIndex = idx + delta
    if (newIndex >= 0 && newIndex < props.items.length) {
      if (!selectedRows.value.includes(newIndex)) {
        selectedRows.value.push(newIndex)
      } else {
        selectedRows.value = selectedRows.value.filter(i => i !== newIndex)
      }
      lastSelectedIndex.value = newIndex
      nextTick(() => rowRefs.value[newIndex]?.focus())
    }
  }
}

// === CENTRAR UNA FILA EN LA VISTA SI TIENE FOCUS ===
const scrollRowIntoView = (idx) => {
  nextTick(() => {
    const rows = document.querySelectorAll('tbody tr')
    const row = rows[idx]
    if (row) row.scrollIntoView({ block: 'nearest' })
  })
}

// === ATALHOS DE TECLADO A NIVEL GLOBAL (esc, pageup, pagedown, flechas) ===
const onGlobalKeydown = (e) => {
  if (e.key === 'Escape') {
    clearRows()
    e.preventDefault()
  }

  if (e.key === 'PageUp' && props.pagination.current_page > 1) {
    emit('page-change', props.pagination.current_page - 1)
    e.preventDefault()
  }

  if (e.key === 'PageDown' && props.pagination.current_page < totalPages.value) {
    emit('page-change', props.pagination.current_page + 1)
    e.preventDefault()
  }

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (currentFocusIndex.value < props.items.length - 1) {
      currentFocusIndex.value++
      focusRow(currentFocusIndex.value)
    }
  }

  if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (currentFocusIndex.value > 0) {
      currentFocusIndex.value--
      focusRow(currentFocusIndex.value)
    }
  }

/*   if (e.key === 'Enter') {
    e.preventDefault()
    const idx = currentFocusIndex.value
    const item = props.items[idx]
    if (item) onRowClick(e, item, idx)
  } */
}

// === LIMPIAR TODAS LAS SELECCIONES ===
const clearRows = () => {
  selectedRows.value = []
  selectedRow.value = null
  selectedRowIndex.value = null
  lastSelectedIndex.value = null
  currentFocusIndex.value = 0
  emit('row-select', { index: null, item: null, selectedItems: [] })
}

// === LIMPIA SELECCIÓN Y EJECUTA UNA ACCIÓN (como cambiar página o tamaño) ===
const clearAndEmit = (fn) => {
  clearRows()
  fn()
}

// === LISTENERS GLOBALES DE TECLADO ===
onMounted(() => window.addEventListener('keydown', onGlobalKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onGlobalKeydown))
</script>


