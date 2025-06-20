<template>
  <!--
    Tabla.vue
    Componente de tabla reutilizable.
    - Recibe los datos y configuración de columnas por props.
    - Permite personalizar las filas usando el slot "row".
    - La lógica de los botones de acción se define en el padre (por ejemplo, Tabla1.vue).
    - Incluye paginación y ordenamiento.
  -->
  <div>
    <!-- Contenedor principal de la tabla -->
    <div class="table-container">
      <div class="table-responsive custom-scrollbar">
        <table class="table align-middle table-row-dashed fs-6 gy-4">
          <thead class="sticky-thead">
            <tr>
              <!-- SOLO LOS ENCABEZADOS CONFIGURADOS -->
              <th
                v-for="header in headerConfig"
                :key="header.columnLabel"
                @click="header.sortEnabled && sortBy(header.columnLabel)"
                :class="[
                  'text-start bg-thead',
                  header.sortEnabled ? 'sortable' : '',
                  sortState.label === header.columnLabel ? 'table-sort-asc' : '',
                  'min-w-100px'
                ]"
                :style="header.width ? { width: header.width, minWidth: header.width, maxWidth: header.width } : {}"
                style="user-select: none;"
              >
                {{ header.columnName }}
                <i
                  v-if="header.sortEnabled"
                  class="bi ms-2"
                  :class="getSortIcon(header.columnLabel)"
                />
              </th>
            </tr>
          </thead>
          <tbody>
            <!-- FILAS -->
            <tr
              v-for="(item, idx) in paginatedData"
              :key="item[idField]"
              tabindex="0"
              :class="getRowClasses(item[idField], idx)"
              @click="onRowClick($event, idx)"
              @keydown="onRowKeydown($event, idx)"
              @focus="scrollRowIntoView(idx)"
              @mouseenter="keyboardIndex = idx + pageStart"
              ref="rowRefs"
              draggable="false"
            >
              <slot name="row" :item="item" :idx="idx" :selected="isRowSelected(idx)">
                <td
                  v-for="header in headerConfig"
                  :key="header.columnLabel"
                  :style="header.width ? { width: header.width, minWidth: header.width, maxWidth: header.width } : {}"
                  class="cell-ellipsis"
                >
                  <span class="cell-ellipsis-content">{{ item[header.columnLabel] }}</span>
                </td>
              </slot>
            </tr>
          </tbody>
        </table>
        <!-- Mensaje cuando no hay registros -->
        <div v-if="paginatedData.length === 0" class="text-center py-10">
          <span class="text-gray-500">No se encontraron registros</span>
        </div>
      </div>
      <!-- Paginación y controles de la tabla -->
      <div class="d-flex justify-content-between align-items-center flex-wrap pt-5">
        <div class="d-flex align-items-center">
          <span class="text-muted fs-7 fw-semibold me-2">Filas por página:</span>
          <select
            v-model.number="rowsPerPage"
            class="form-select form-select-sm form-select-solid w-auto"
            @change="changeRowsPerPage"
          >
            <option v-for="n in rowsPerPageOptions" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
        <div class="fs-6 fw-semibold text-gray-700">
          Mostrando {{ pageStart + 1 }} a {{ Math.min(pageStart + rowsPerPage, data.length) }}
          de {{ data.length }} registros
        </div>
        <ul class="pagination mb-0">
          <!-- Botones de paginación -->
          <li class="page-item" :class="{ disabled: page === 1 }">
            <button class="page-link" @click="goToPage(1)" :disabled="page === 1">
              <i class="bi bi-chevron-double-left"></i>
            </button>
          </li>
          <li class="page-item" :class="{ disabled: page === 1 }">
            <button class="page-link" @click="prevPage" :disabled="page === 1">
              <i class="bi bi-chevron-left"></i>
            </button>
          </li>
          <li class="page-item disabled">
            <span class="page-link">{{ page }} / {{ totalPages }}</span>
          </li>
          <li class="page-item" :class="{ disabled: page === totalPages }">
            <button class="page-link" @click="nextPage" :disabled="page === totalPages">
              <i class="bi bi-chevron-right"></i>
            </button>
          </li>
          <li class="page-item" :class="{ disabled: page === totalPages }">
            <button class="page-link" @click="goToPage(totalPages)" :disabled="page === totalPages">
              <i class="bi bi-chevron-double-right"></i>
            </button>
          </li>
        </ul>
      </div>
    </div>
    
    <!-- Panel de acciones para múltiples elementos seleccionados - SOLO MOSTRAR CON 2+ ELEMENTOS -->
    <div v-if="multiSelect && selectedRows.length > 1" class="multi-select-actions">
      <div class="multi-select-counter">
        <span class="badge badge-light-secondary">
          <i class="bi bi-check2-square me-1"></i> {{ selectedRows.length }} elementos seleccionados
        </span>
      </div>
      <div class="multi-select-buttons">
        <button class="btn btn-sm btn-light-secondary me-2" @click="clearMultiSelection">
          <i class="bi bi-x-circle me-1"></i>Quitar selecciones
        </button>
        <slot name="multi-actions" :selected-items="getSelectedItems()">
          <button class="btn btn-sm btn-danger" @click="$emit('multi-delete', getSelectedItems())">
            <i class="bi bi-trash me-1"></i>Eliminar seleccionados
          </button>
        </slot>
      </div>
    </div>
    
    <div class="d-flex justify-content-end mt-2">
      <slot 
        name="actions" 
        :showActions="selectedRow !== null"
        :selectedData="selectedRowData"
      ></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'

/**
 * Props y emits para la tabla reutilizable
 * @typedef {Object} HeaderConfig - Configuración de columnas
 * @property {string} columnName - Nombre visible de la columna
 * @property {string} columnLabel - Identificador para datos y ordenamiento
 * @property {boolean} [sortEnabled] - Indica si la columna es ordenable
 */
const props = defineProps<{
  /** Datos a mostrar en la tabla */
  data: any[]
  /** Configuración de columnas */
  headerConfig: Array<{
    columnName: string
    columnLabel: string
    sortEnabled?: boolean
    width?: string | number
  }>
  /** Campo que identifica cada fila (default: 'id') */
  idField?: string
  /** Opciones de filas por página */
  rowsPerPageOptions?: number[]
  /** Habilita selección múltiple */
  multiSelect?: boolean
}>()

const emit = defineEmits<{
  /** Evento cuando se cambia el ordenamiento */
  (e: 'sort', sort: { label: string; order: string }): void
  /** Evento cuando se cambia el número de filas por página */
  (e: 'items-per-page-change', size: number): void
  /** Evento cuando se selecciona una fila */
  (e: 'row-select', data: { index: number | null, item: any | null }): void
  /** Evento para enfocar los botones de acción */
  (e: 'action-focus'): void
  /** Evento cuando hay selección múltiple */
  (e: 'multi-select', items: any[]): void
  /** Evento para eliminar múltiples elementos */
  (e: 'multi-delete', items: any[]): void
}>()

// Estado de la tabla y paginación
const idField = props.idField ?? 'id'
const rowsPerPageOptions = props.rowsPerPageOptions ?? [10, 20, 50, 100]
const rowsPerPage = ref(rowsPerPageOptions[0])
const page = ref(1)
const sortState = ref({ label: '', order: '' })
const keyboardIndex = ref(-1)
const selectedRow = ref<number | null>(null)

/** Datos con ordenamiento aplicado internamente */
const sortedData = computed(() => {
  if (!sortState.value.label || !sortState.value.order) {
    return [...props.data]
  }

  return [...props.data].sort((a, b) => {
    const aValue = a[sortState.value.label]
    const bValue = b[sortState.value.label]

    // Manejar valores null/undefined
    if (aValue == null && bValue == null) return 0
    if (aValue == null) return sortState.value.order === 'asc' ? 1 : -1
    if (bValue == null) return sortState.value.order === 'asc' ? -1 : 1

    // Ordenamiento numérico para IDs y números
    if (sortState.value.label === 'id' || (typeof aValue === 'number' && typeof bValue === 'number')) {
      const numA = Number(aValue)
      const numB = Number(bValue)
      return sortState.value.order === 'asc' ? numA - numB : numB - numA
    }

    // Ordenamiento especial para nombres (name/nombre)
    if (sortState.value.label === 'name' || sortState.value.label === 'nombre') {
      const nameA = aValue || a.nombre || a.name || ''
      const nameB = bValue || b.nombre || b.name || ''
      const comparison = nameA.toString().localeCompare(nameB.toString(), 'es', { 
        numeric: true, 
        sensitivity: 'base' 
      })
      return sortState.value.order === 'asc' ? comparison : -comparison
    }

    // Ordenamiento para fechas
    if (sortState.value.label === 'createdAt' || sortState.value.label.includes('fecha')) {
      const dateA = aValue || a.created_at || a.createdDate || a.fecha_creacion || ''
      const dateB = bValue || b.created_at || b.createdDate || b.fecha_creacion || ''
      if (dateA < dateB) return sortState.value.order === 'asc' ? -1 : 1
      if (dateA > dateB) return sortState.value.order === 'asc' ? 1 : -1
      return 0
    }

    // Ordenamiento de texto
    if (typeof aValue === 'string' && typeof bValue === 'string') {
      const comparison = aValue.localeCompare(bValue, 'es', { 
        numeric: true, 
        sensitivity: 'base' 
      })
      return sortState.value.order === 'asc' ? comparison : -comparison
    }

    // Fallback para otros tipos
    if (aValue < bValue) return sortState.value.order === 'asc' ? -1 : 1
    if (aValue > bValue) return sortState.value.order === 'asc' ? 1 : -1
    return 0
  })
})

/** Datos de la tabla ahora usan sortedData */
const data = computed(() => sortedData.value)

/** Número total de páginas */
const totalPages = computed(() => Math.max(1, Math.ceil(data.value.length / rowsPerPage.value)))
/** Índice de inicio de la página actual */
const pageStart = computed(() => (page.value - 1) * rowsPerPage.value)
/** Datos paginados para la vista actual */
const paginatedData = computed(() =>
  data.value.slice(pageStart.value, pageStart.value + rowsPerPage.value)
)

/** Datos de la fila seleccionada */
const selectedRowData = computed(() => 
  selectedRow.value !== null ? paginatedData.value[selectedRow.value] : null
)

// Estado para selección múltiple y hover
const selectedRows = ref<number[]>([])
const lastSelectedIndex = ref<number | null>(null)
const hoveredIndex = ref<number | null>(null)

// Variables para selección por arrastrar
const isDragging = ref(false);
const dragStartIdx = ref<number | null>(null);

/** Indica si todos los elementos están seleccionados */
const isAllSelected = computed(() => {
  return paginatedData.value.length > 0 && selectedRows.value.length === paginatedData.value.length;
})

/**
 * Verifica si una fila está seleccionada para aplicar clases multi-select solo cuando hay 2+
 * @param {number} idx - Índice de la fila
 * @returns {Object} Clases CSS a aplicar
 */
function isRowSelected(idx: number) {
  // La fila tiene selección simple si selectedRow === idx
  const isSimpleSelected = selectedRow.value === idx;
  
  // La fila tiene selección múltiple si está en selectedRows Y hay 2+ filas seleccionadas
  const isInMultiSelection = props.multiSelect && selectedRows.value.includes(idx);
  const hasMultipleSelections = selectedRows.value.length >= 2;
  
  // Solo activamos el estilo "multi-row-selected" cuando hay 2+ selecciones
  return {
    'row-active': idx + pageStart.value === keyboardIndex.value,
    'table-active': isSimpleSelected || (isInMultiSelection && !hasMultipleSelections),
    'multi-row-selected': isInMultiSelection && hasMultipleSelections,
  }
}

// Cambia la cantidad de filas por página
function changeRowsPerPage() {
  page.value = 1
  emit('items-per-page-change', rowsPerPage.value)
}

// Cambia de página
function goToPage(p: number) {
  if (p >= 1 && p <= totalPages.value) page.value = p
}

// Página anterior
function prevPage() {
  if (page.value > 1) page.value--
}

// Página siguiente
function nextPage() {
  if (page.value < totalPages.value) page.value++
}

// Ordena por columna - FUNCIÓN CORREGIDA PARA MANEJAR INTERNAMENTE
function sortBy(label: string) {
  if (sortState.value.label === label) {
    // Cambiar dirección si es la misma columna
    sortState.value.order = sortState.value.order === 'asc' ? 'desc' : 'asc'
  } else {
    // Nueva columna, ordenar ascendente
    sortState.value = { label, order: 'asc' }
  }
  
}

// Icono de orden
function getSortIcon(label: string) {
  if (sortState.value.label !== label) return 'bi-sort-alpha-down'
  return sortState.value.order === 'asc' ? 'bi-sort-alpha-up' : 'bi-sort-alpha-down'
}

// Actualiza el índice de la fila sobre la que está el cursor
function handleMouseEnter(idx: number) {
  keyboardIndex.value = idx + pageStart.value
  hoveredIndex.value = idx
}

// Clases para resaltar la fila según su estado - SOLO CAMBIO DE COLOR
function getRowClasses(id: any, idx: number) {
  return isRowSelected(idx);
}

/**
 * Manejador de clic en fila con selección múltiple
 * @param {MouseEvent} e - Evento del mouse
 * @param {number} idx - Índice de la fila clicada
 */
function onRowClick(e: MouseEvent, idx: number) {
  // En modo multiselección
  if (props.multiSelect) {
    // Shift+click = seleccionar rango
    if (e.shiftKey && lastSelectedIndex.value !== null) {
      e.preventDefault()
      const start = Math.min(lastSelectedIndex.value, idx)
      const end = Math.max(lastSelectedIndex.value, idx)
      
      // Seleccionar todas las filas en el rango
      for (let i = start; i <= end; i++) {
        if (!selectedRows.value.includes(i)) {
          selectedRows.value.push(i)
        }
      }
      emit('multi-select', getSelectedItems())
      return
    }
    
    // Ctrl/Cmd+click = toggle individual
    if (e.ctrlKey || e.metaKey) {
      e.preventDefault()
      toggleRowSelection(idx)
      return
    }
    
    // Iniciar proceso de arrastre para selección múltiple
    dragStartIdx.value = idx;
    
    // Click simple:
    // Si la fila ya estaba seleccionada en modo multi, la quitamos
    if (selectedRows.value.includes(idx)) {
      selectedRows.value = selectedRows.value.filter(i => i !== idx)
    } else {
      // Si no, seleccionamos solo esta fila (sin cambiar estilo multi todavía)
      selectedRows.value = [idx]
    }
    
    lastSelectedIndex.value = idx
    emit('multi-select', getSelectedItems())
  }
  
  // Manejo regular de selección simple para botones de acción
  if (selectedRow.value === idx) {
    selectedRow.value = null
    emit('row-select', { index: null, item: null })
  } else {
    selectedRow.value = idx
    emit('row-select', { index: idx, item: paginatedData.value[idx] })
    // No enfocamos botones en clic, solo en Enter
  }
}

// Toggle selección de una fila
function toggleRowSelection(idx: number) {
  const index = selectedRows.value.indexOf(idx)
  
  if (index === -1) {
    selectedRows.value.push(idx)
  } else {
    selectedRows.value.splice(index, 1)
  }
  
  lastSelectedIndex.value = idx
  emit('multi-select', getSelectedItems())
}

// Toggle selección de todas las filas
function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedRows.value = [];
  } else {
    selectedRows.value = Array.from({ length: paginatedData.value.length }, (_, i) => i);
  }
  emit('multi-select', getSelectedItems());
}

// Obtiene los elementos seleccionados
function getSelectedItems() {
  return selectedRows.value.map(idx => paginatedData.value[idx]);
}

// Manejo de teclado en filas
function onRowKeydown(e: KeyboardEvent, idx: number) {
  // Permite seleccionar con Enter
  if (e.key === 'Enter') {
    e.preventDefault()
    if (selectedRow.value === idx) {
      selectedRow.value = null
      emit('row-select', { index: null, item: null })
    } else {
      selectedRow.value = idx
      emit('row-select', { index: idx, item: paginatedData.value[idx] })
      emit('action-focus')
      
      // Enfocar el primer botón de acción
      nextTick(() => {
        const actionsBtn = document.querySelector('.botones-wrapper')?.querySelector('button');
        if (actionsBtn) {
          (actionsBtn as HTMLElement).focus();
        }
      })
    }
    return
  }
  
  // Tecla Backspace para limpiar selección múltiple
  if (props.multiSelect && (e.key === 'Backspace')) {
    e.preventDefault()
    selectedRows.value = [];
    emit('multi-select', []);
    return
  }
  
  // Selección múltiple con Shift+flechas
  if (props.multiSelect && e.shiftKey) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault()
      const delta = e.key === 'ArrowDown' ? 1 : -1
      let newIndex = idx + delta
      
      if (newIndex >= 0 && newIndex < paginatedData.value.length) {
        // Si ya tenemos un índice seleccionado, añadir al rango
        if (lastSelectedIndex.value !== null) {
          toggleRowSelection(newIndex)
        } else {
          selectedRows.value = [idx, newIndex]
          lastSelectedIndex.value = idx
        }
        
        // Enfocar la nueva fila
        nextTick(() => {
          const rows = document.querySelectorAll('tbody tr')
          const row = rows[newIndex] as HTMLElement
          row?.focus()
        })
        
        emit('multi-select', getSelectedItems())
        return
      }
    }
  }
  
  // Tecla Space o Delete para toggle de selección múltiple
  if (props.multiSelect && (e.key === ' ' || e.key === 'Delete')) {
    e.preventDefault()
    toggleRowSelection(idx)
    return
  }
  
  // Navegación con flechas y paginación
  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      moveKeyboardFocus(1)
      break
    case 'ArrowUp':
      e.preventDefault()
      moveKeyboardFocus(-1)
      break
    case 'PageDown':
      e.preventDefault()
      nextPage()
      focusFirstRow()
      break
    case 'PageUp':
      e.preventDefault()
      prevPage()
      focusFirstRow()
      break
    case 'Tab':
      break
  }
}

// Hace scroll y enfoca la fila seleccionada
function scrollRowIntoView(idx: number) {
  keyboardIndex.value = idx + pageStart.value
  nextTick(() => {
    const rows = document.querySelectorAll('tbody tr')
    const row = rows[idx] as HTMLElement
    if (row) {
      const thead = document.querySelector('.sticky-thead') as HTMLElement
      const headerHeight = thead ? thead.offsetHeight : 0
      const container = row.closest('.table-responsive') as HTMLElement
      if (container) {
        const rowRect = row.getBoundingClientRect()
        const containerRect = container.getBoundingClientRect()
        if (rowRect.top < containerRect.top + headerHeight) {
          container.scrollTop += rowRect.top - containerRect.top - headerHeight
        }
        if (rowRect.bottom > containerRect.bottom) {
          container.scrollTop += rowRect.bottom - containerRect.bottom
        }
      }
      row.focus()
    }
  })
}

// Navegación con flechas entre filas
function moveKeyboardFocus(delta: number) {
  let newIndex = keyboardIndex.value + delta
  const totalRows = data.value.length
  if (newIndex < 0) newIndex = 0
  if (newIndex >= totalRows) newIndex = totalRows - 1
  const newPage = Math.floor(newIndex / rowsPerPage.value) + 1
  if (page.value !== newPage) {
    page.value = newPage
    nextTick(() => {
      const rowIdxInPage = newIndex % rowsPerPage.value
      const rows = document.querySelectorAll('tbody tr')
      const row = rows[rowIdxInPage] as HTMLElement
      row?.focus()
      keyboardIndex.value = newIndex
    })
  } else {
    const rowIdxInPage = newIndex % rowsPerPage.value
    const rows = document.querySelectorAll('tbody tr')
    const row = rows[rowIdxInPage] as HTMLElement
    row?.focus()
    keyboardIndex.value = newIndex
  }
}

// Enfoca la primera fila de la página
function focusFirstRow() {
  nextTick(() => {
    const rows = document.querySelectorAll('tbody tr')
    const row = rows[0] as HTMLElement
    row?.focus()
    keyboardIndex.value = pageStart.value
  })
}

// Enfoca la fila seleccionada
function focusSelectedRow() {
  if (selectedRow.value !== null) {
    const rows = document.querySelectorAll('tbody tr')
    const row = rows[selectedRow.value] as HTMLElement
    if (row) {
      nextTick(() => {
        row.focus()
      })
    }
  }
}

// Manejo global de la tecla Delete para limpiar selección múltiple
function handleGlobalKeys(e: KeyboardEvent) {
  if (e.key === 'Escape' && selectedRow.value !== null) {
    const lastFocusedIdx = keyboardIndex.value; // Guarda el índice de la fila enfocada antes de quitar la selección
    selectedRow.value = null
    emit('row-select', { index: null, item: null })
    nextTick(() => {
      const rows = document.querySelectorAll('tbody tr')
      // Enfoca la fila que estaba previamente enfocada, si existe, si no la primera
      const row = rows[lastFocusedIdx - pageStart.value] as HTMLElement || rows[0] as HTMLElement
      row?.focus()
      keyboardIndex.value = lastFocusedIdx
    })
  }
  
  // Si se presiona Delete fuera de una fila y hay elementos seleccionados, limpiar selección
  if (e.key === 'Delete' && props.multiSelect && selectedRows.value.length > 0) {
    const activeElement = document.activeElement;
    // Solo si no estamos en un input, textarea o selección de texto
    if (activeElement && 
        activeElement.tagName !== 'INPUT' && 
        activeElement.tagName !== 'TEXTAREA' &&
        activeElement.tagName !== 'SELECT') {
      selectedRows.value = [];
      emit('multi-select', []);
    }
  }
}

/**
 * Limpia la selección múltiple
 */
function clearMultiSelection() {
  selectedRows.value = [];
  emit('multi-select', []);
}

// Agregar eventos de arrastrar para selección múltiple
onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeys)
  
  // Eventos para selección por arrastre
  const tbody = document.querySelector('tbody');
  if (tbody) {
    // Al iniciar arrastre
    tbody.addEventListener('mousedown', (e) => {
      if (props.multiSelect) {
        isDragging.value = true;
      }
    });
    
    // Al mover durante arrastre
    document.addEventListener('mousemove', (e) => {
      if (isDragging.value && dragStartIdx.value !== null) {
        const target = e.target;
        if (target) {
          const tr = (target as HTMLElement).closest('tr');
          if (tr) {
            const rowIndex = Array.from(tr.parentElement?.children || []).indexOf(tr);
            if (rowIndex !== -1) {
              // Seleccionar desde el inicio del arrastre hasta la posición actual
              const start = Math.min(dragStartIdx.value, rowIndex);
              const end = Math.max(dragStartIdx.value, rowIndex);
              
              selectedRows.value = [];
              for (let i = start; i <= end; i++) {
                selectedRows.value.push(i);
              }
              emit('multi-select', getSelectedItems());
            }
          }
        }
      }
    });
    
    // Al finalizar arrastre
    document.addEventListener('mouseup', () => {
      isDragging.value = false;
    });
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeys)
  
  document.removeEventListener('mousemove', () => {});
  document.removeEventListener('mouseup', () => {});
})

// Restablecer selección al cambiar de página
watch(page, () => {
  selectedRows.value = [];
  emit('multi-select', []);
})

// Exponer funciones para el padre (por ejemplo, para devolver el foco a la fila seleccionada)
defineExpose({
  focusSelectedRow,
  selectedRowData,
  clearSelection: () => {
    selectedRow.value = null
    emit('row-select', { index: null, item: null })
  },
  selectedRows,
  getSelectedItems,
  clearMultiSelection,
  // Añadir método para seleccionar todos los elementos programáticamente
  selectAll: () => {
    selectedRows.value = Array.from({ length: paginatedData.value.length }, (_, i) => i);
    emit('multi-select', getSelectedItems());
  },
  // Añadir método para invertir la selección
  toggleSelection: () => {
    const allIndices = Array.from({ length: paginatedData.value.length }, (_, i) => i);
    selectedRows.value = allIndices.filter(i => !selectedRows.value.includes(i));
    emit('multi-select', getSelectedItems());
  }
})
</script>

<style lang="scss" scoped>
@import './TablaEstilos.scss';

/**
 * Estilos base de la tabla
 * - Header sticky al hacer scroll
 * - Bordes y espaciados consistentes
 * - Estilos de paginación
 */

/* Ellipsis para celdas con overflow */
.cell-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 1px; /* será sobrescrito por el width inline si existe */
  position: relative;
}

.cell-ellipsis-content {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

</style>
