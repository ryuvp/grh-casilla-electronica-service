<template>
  <div class="w-100">
    <div class="d-flex flex-wrap gap-3 align-items-end w-100">
      <!-- Búsqueda de texto con selector de campo -->
      <template v-if="showSearch">
        <div class="d-flex flex-column" style="min-width: 180px; flex: 1 1 auto;">
          <label class="form-label fs-7 fw-semibold text-gray-600 mb-2">
            Buscar en: <span class="text-danger">*</span>
          </label>
          <el-select 
            v-model="searchFieldModel" 
            placeholder="Seleccionar campos"
            @change="handleSearchFieldChange"
            multiple
            :collapse-tags="searchFieldModel.length > 2"
            :collapse-tags-tooltip="true"
            :popper-append-to-body="true"
            class="w-100"
            size="default"
          >
            <el-option 
              v-for="field in searchableFields" 
              :key="field.value" 
              :label="field.label" 
              :value="field.value"
            />
            <el-option key="all" label="Todos los campos" value="all" />
          </el-select>
        </div>
        
        <div class="d-flex flex-column" style="min-width: 250px; flex: 2 1 auto;">
          <label class="form-label fs-7 fw-semibold text-gray-600 mb-2">Término:</label>
          <div class="position-relative">
            <KTIcon icon-name="magnifier" icon-class="fs-1 position-absolute ms-3 top-50 translate-middle-y" />
            <input
              type="text"
              class="form-control form-control-solid ps-14"
              :placeholder="getSearchPlaceholder()"
              :value="search"
              @input="e => emit('update:search', (e.target as HTMLInputElement).value)"
            />
          </div>
        </div>
      </template>
      
      <!-- Filtros de fecha -->
      <template v-if="showDateFilter">
        <div class="d-flex flex-column" style="min-width: 180px; flex: 1 1 auto;">
          <label class="form-label fs-7 fw-semibold text-gray-600 mb-2">Desde:</label>
          <div class="position-relative">
            <el-date-picker
              v-model="startDateModel"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Selecciona fecha de inicio"
              class="w-100"
              size="default"
              :clearable="false"
              popper-class="custom-date-popper"
            />
            <span 
              v-if="startDateModel"
              class="position-absolute top-50 translate-middle-y bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
              style="right: 8px; width: 22px; height: 22px; cursor: pointer; font-size: 12px; z-index: 10;"
              @click="clearStartDate"
            >
              <i class="bi bi-x"></i>
            </span>
          </div>
        </div>
        <div class="d-flex flex-column" style="min-width: 180px; flex: 1 1 auto;">
          <label class="form-label fs-7 fw-semibold text-gray-600 mb-2">Hasta:</label>
          <div class="position-relative">
            <el-date-picker
              v-model="endDateModel"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Selecciona fecha final"
              class="w-100"
              size="default"
              :clearable="false"
              popper-class="custom-date-popper"
            />
            <span 
              v-if="endDateModel"
              class="position-absolute top-50 translate-middle-y bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
              style="right: 8px; width: 22px; height: 22px; cursor: pointer; font-size: 12px; z-index: 10;"
              @click="clearEndDate"
            >
              <i class="bi bi-x"></i>
            </span>
          </div>
        </div>
      </template>
      
      <!-- Slot para filtros adicionales -->
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'

/**
 * Props del componente Filtro
 * @typedef {Object} FiltroProps
 */
const props = defineProps<{
  /** Término de búsqueda actual */
  search?: string,
  /** Campo(s) de búsqueda seleccionado(s) - ahora puede ser string o array */
  searchField?: string | string[],
  /** Lista de campos buscables */
  searchableFields?: Array<{label: string, value: string}>,
  /** Fecha inicial para filtrado */
  dateStart?: string,
  /** Fecha final para filtrado */
  dateEnd?: string,
  /** Mostrar filtro de fechas */
  showDateFilter?: boolean,
  /** Mostrar campo de búsqueda */
  showSearch?: boolean,
  /** Lista de elementos a filtrar */
  items?: any[],
}>()

/**
 * Eventos emitidos por el componente
 */
const emit = defineEmits<{
  /** Actualiza el término de búsqueda */
  (e: 'update:search', value: string): void
  /** Actualiza el/los campo(s) de búsqueda */
  (e: 'update:searchField', value: string | string[]): void
  /** Actualiza la fecha inicial */
  (e: 'update:dateStart', value: string): void
  /** Actualiza la fecha final */
  (e: 'update:dateEnd', value: string): void
  /** Solicita búsqueda inmediata */
  (e: 'search-items'): void
  /** Emite items filtrados */
  (e: 'filtered-items', items: any[]): void
}>()

/** Modelo local para fecha inicial */
const startDateModel = ref(props.dateStart || '')
/** Modelo local para fecha final */
const endDateModel = ref(props.dateEnd || '')

/** 
 * Modelo local para campos de búsqueda
 * Convierte el valor recibido (string o array) a array
 */
const searchFieldModel = ref<string[]>([]);

/**
 * Inicialización inteligente del componente
 * 1. Determina automáticamente qué campos usar basado en props.searchField
 * 2. Selecciona por defecto el primer campo si no hay selección previa
 * 3. Emite un evento con la selección inicial para actualizar el estado del padre
 */
onMounted(() => {
  // Asegurar que los campos se inicialicen correctamente
  initializeSearchField();
  
  // Activar búsqueda en tiempo real
  watchSearchInput();
});

/**
 * Inicializa el campo de búsqueda con valor predeterminado "todos los campos"
 */
function initializeSearchField() {
  // Si no hay campos, usar 'all'
  if (!props.searchableFields || props.searchableFields.length === 0) {
    searchFieldModel.value = ['all'];
    emit('update:searchField', searchFieldModel.value);
    return;
  }

  // Siempre inicializar con "todos los campos" por defecto
  if (props.searchField) {
    if (Array.isArray(props.searchField) && props.searchField.length > 0) {
      // Mantener la selección actual del padre
      searchFieldModel.value = [...props.searchField];
    } else if (typeof props.searchField === 'string') {
      // Si es un string, convertir a array
      searchFieldModel.value = [props.searchField];
    } else {
      // Si es un array vacío o tiene otro formato, usar "todos los campos"
      searchFieldModel.value = ['all'];
    }
  } else {
    // Por defecto, iniciar con "todos los campos"
    searchFieldModel.value = ['all'];
  }
  
  // Notificar el cambio al padre
  nextTick(() => {
    emit('update:searchField', searchFieldModel.value);
  });
}

/**
 * Configura el watcher para búsqueda en tiempo real
 */
function watchSearchInput() {
  // Realizar búsqueda en tiempo real cuando cambie el texto
  watch(() => props.search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
      emit('search-items');
      if (props.items) {
        filtrarItems(); // Realizar filtrado si hay items propios
      }
    }
  }, { immediate: true });
  
  // Realizar búsqueda en tiempo real cuando cambie el campo seleccionado
  watch(() => searchFieldModel.value, () => {
    emit('search-items');
    if (props.items) {
      filtrarItems(); // Realizar filtrado si hay items propios
    }
  }, { deep: true });
}

// Mantener sincronizado el modelo local con las props
watch(() => props.searchField, (newVal) => {
  if (newVal) {
    if (Array.isArray(newVal)) {
      searchFieldModel.value = [...newVal];
    } else {
      searchFieldModel.value = [newVal];
    }
  }
}, { deep: true });

/**
 * Obtiene el placeholder del campo de búsqueda según los campos seleccionados
 */
function getSearchPlaceholder() {
  if (!searchFieldModel.value || searchFieldModel.value.length === 0) {
    return "Buscar...";
  }
  
  if (searchFieldModel.value.includes('all')) {
    return "Buscar en todos los campos...";
  }
  
  if (searchFieldModel.value.length === 1) {
    const field = props.searchableFields?.find(f => f.value === searchFieldModel.value[0]);
    return field ? `Buscar por ${field.label.toLowerCase()}...` : "Buscar...";
  }
  
  return `Buscar en ${searchFieldModel.value.length} campos seleccionados...`;
}

/**
 * Maneja el cambio de campo(s) de búsqueda
 */
function handleSearchFieldChange() {
  // Si se selecciona un campo específico y "Todos los campos" estaba seleccionado
  if (searchFieldModel.value.includes('all') && searchFieldModel.value.length > 1) {
    // Quitar "Todos los campos" y mantener solo los campos específicos
    searchFieldModel.value = searchFieldModel.value.filter(field => field !== 'all');
  }
  
  // Si se selecciona "Todos los campos" después de tener campos específicos
  else if (searchFieldModel.value.includes('all') && searchFieldModel.value.length === 1) {
    // Mantener solo "Todos los campos"
    searchFieldModel.value = ['all'];
  }
  
  // Si no hay nada seleccionado, volver a "todos los campos"
  if (searchFieldModel.value.length === 0) {
    searchFieldModel.value = ['all'];
  }
  
  emit('update:searchField', searchFieldModel.value);
  emit('search-items');
  
  if (props.items) {
    filtrarItems(); // Realizar filtrado si hay items propios
  }
}

// Observar cambios en fechas para búsqueda inmediata
watch([startDateModel, endDateModel], () => {
  if (props.items) {
    filtrarItems();
  }
}, { deep: true });

// Observar cambios en los items para refiltar
watch(() => props.items, () => {
  if (props.items) {
    filtrarItems();
  }
}, { deep: true });

/**
 * Limpia la fecha inicial y desencadena búsqueda
 */
function clearStartDate() {
  startDateModel.value = ''
  emit('update:dateStart', '')
  emit('search-items')
}

/**
 * Limpia la fecha final y desencadena búsqueda
 */
function clearEndDate() {
  endDateModel.value = ''
  emit('update:dateEnd', '')
  emit('search-items')
}

/**
 * Normaliza texto para búsqueda (quita acentos, espacios extra, etc.)
 */
function normalize(txt: string | null | undefined) {
  if (!txt) return '';
  return txt.toString().toLowerCase().normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * Obtiene el valor de un campo (incluso anidado con notación de punto)
 */
function getNestedValue(obj: any, path: string) {
  const parts = path.split('.');
  let value = obj;
  
  for (const part of parts) {
    if (value == null || typeof value !== 'object') {
      return null;
    }
    value = value[part];
  }
  
  return value;
}

/**
 * Filtra items por texto y rango de fechas, considerando los campos seleccionados
 * Replica la lógica exacta de búsqueda de Usuarios
 */
function filtrarItems() {
  if (!props.items) return;
  
  const term = normalize(props.search || '');
  const itemsFiltrados = props.items.filter(item => {
    // Filtro texto basado en campos seleccionados
    let matchText = true;
    
    if (term) {
      if (Array.isArray(searchFieldModel.value)) {
        if (searchFieldModel.value.includes('all')) {
          // Búsqueda en todos los campos
          matchText = Object.values(item)
            .filter(v => v != null)
            .some(v => normalize(String(v)).includes(term));
        } else if (searchFieldModel.value.length > 0) {
          // Búsqueda en múltiples campos específicos
          matchText = searchFieldModel.value.some(campo => {
            const valor = getNestedValue(item, campo);
            return valor != null ? normalize(String(valor)).includes(term) : false;
          });
        } else {
          // Si no hay campos seleccionados, buscar en todos (comportamiento default)
          matchText = Object.values(item)
            .filter(v => v != null)
            .some(v => normalize(String(v)).includes(term));
        }
      } else {
        // Compatibilidad para searchField como string
        const fieldValue = typeof searchFieldModel.value === 'string' 
          ? searchFieldModel.value 
          : (searchFieldModel.value.length > 0 ? searchFieldModel.value[0] : 'all');
          
        if (fieldValue === 'all') {
          // Búsqueda en todos los campos
          matchText = Object.values(item)
            .filter(v => v != null)
            .some(v => normalize(String(v)).includes(term));
        } else {
          // Búsqueda en campo específico
          const valor = getNestedValue(item, fieldValue);
          matchText = valor != null ? normalize(String(valor)).includes(term) : false;
        }
      }
    }
    
    // Filtro fecha
    let matchDate = true;
    // Buscar una propiedad de fecha común (fecha_nacimiento, createdDate, fecha, etc.)
    const fechaField = ['fecha_nacimiento', 'createdDate', 'fecha', 'created_at', 'date'].find(f => item[f]);
    const fecha = fechaField ? item[fechaField] : null;
    
    if (fecha && (startDateModel.value || endDateModel.value)) {
      const f = typeof fecha === 'string' ? fecha.slice(0, 10) : ''; // yyyy-mm-dd
      if (startDateModel.value && f < startDateModel.value) matchDate = false;
      if (endDateModel.value && f > endDateModel.value) matchDate = false;
    }
    
    return matchText && matchDate;
  });
  
  // Emitir los resultados filtrados
  emit('filtered-items', itemsFiltrados);
}
</script>