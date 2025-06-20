<template>
  <div class="filtro-container">
    <div class="filtro-row">
      <!-- Búsqueda de texto con selector de campo -->
      <template v-if="showSearch">
        <div class="filtro-group">
          <label class="filtro-label required">Buscar en:</label>
          <div class="filtro-input-wrapper">
            <el-select 
              v-model="searchFieldModel" 
              class="campo-select custom-select"
              placeholder="Seleccionar campos"
              @change="handleSearchFieldChange"
              multiple
              :collapse-tags="searchFieldModel.length > 2"
              :collapse-tags-tooltip="true"
              :popper-append-to-body="true"
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
        </div>
        
        <div class="filtro-group filtro-search-group">
          <label class="filtro-label">Término:</label>
          <div class="filtro-input-wrapper">
            <div class="position-relative">
              <KTIcon icon-name="magnifier" icon-class="fs-1 position-absolute ms-6 top-50 translate-middle-y" />
              <input
                type="text"
                class="form-control form-control-solid ps-14"
                :placeholder="getSearchPlaceholder()"
                :value="search"
                @input="e => emit('update:search', (e.target as HTMLInputElement).value)"
              />
            </div>
          </div>
        </div>
      </template>
      
      <!-- Filtros de fecha -->
      <template v-if="showDateFilter">
        <div class="filtro-group">
          <label class="filtro-label">Desde:</label>
          <div class="filtro-input-wrapper">
            <el-date-picker
              v-model="startDateModel"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Selecciona fecha de inicio"
              class="filtro-date-input custom-date-picker"
              :clearable="false"
              popper-class="custom-date-popper"
            />
            <span 
              v-if="startDateModel"
              class="filtro-clear-btn"
              @click="clearStartDate"
            >
              <i class="bi bi-x"></i>
            </span>
          </div>
        </div>
        <div class="filtro-group">
          <label class="filtro-label">Hasta:</label>
          <div class="filtro-input-wrapper">
            <el-date-picker
              v-model="endDateModel"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Selecciona fecha final"
              class="filtro-date-input custom-date-picker"
              :clearable="false"
              popper-class="custom-date-popper"
            />
            <span 
              v-if="endDateModel"
              class="filtro-clear-btn"
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
 * Inicializa el campo de búsqueda con valor predeterminado o el primer campo
 */
function initializeSearchField() {
  // Si no hay campos, usar 'all'
  if (!props.searchableFields || props.searchableFields.length === 0) {
    searchFieldModel.value = ['all'];
    emit('update:searchField', searchFieldModel.value);
    return;
  }

  // Si ya hay un valor definido en props, usarlo excepto si es 'all'
  if (props.searchField) {
    if (Array.isArray(props.searchField) && props.searchField.length > 0) {
      // Si es array y tiene elementos, verificar si incluye 'all'
      if (props.searchField.includes('all') && props.searchField.length === 1) {
        // Reemplazar 'all' con el primer campo disponible
        searchFieldModel.value = [props.searchableFields[0].value];
      } else {
        // Mantener los campos seleccionados
        searchFieldModel.value = [...props.searchField];
      }
    } else if (typeof props.searchField === 'string') {
      // Si es un string, convertir a array con un solo valor (excepto 'all')
      if (props.searchField === 'all') {
        searchFieldModel.value = [props.searchableFields[0].value];
      } else {
        searchFieldModel.value = [props.searchField];
      }
    } else {
      // Si es un array vacío o tiene otro formato, usar primer campo
      searchFieldModel.value = [props.searchableFields[0].value];
    }
  } else {
    // Si no hay valor definido, usar el primer campo
    searchFieldModel.value = [props.searchableFields[0].value];
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
  // Si se selecciona "Todos los campos", deseleccionar los demás
  if (searchFieldModel.value.includes('all') && searchFieldModel.value.length > 1) {
    searchFieldModel.value = ['all'];
  }
  // Si se seleccionan otros campos y ya estaba "Todos", quitar "Todos"
  if (searchFieldModel.value.length > 1 && searchFieldModel.value.includes('all')) {
    searchFieldModel.value = searchFieldModel.value.filter(field => field !== 'all');
  }
  // Si no hay nada seleccionado, seleccionar el primer campo disponible
  if (searchFieldModel.value.length === 0) {
    if (props.searchableFields && props.searchableFields.length > 0) {
      searchFieldModel.value = [props.searchableFields[0].value];
    } else {
      // Solo usar 'all' si no hay campos disponibles
      searchFieldModel.value = ['all'];
    }
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

<style scoped>
/*Css del componente filtro */
.filtro-container {
  width: 100%;
  padding: 0;
}

.filtro-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  width: 100%;
  align-items: flex-end;
}

.filtro-group {
  display: flex;
  flex-direction: column;
  min-width: 180px;
  flex: 1 1 auto;
  height: 60px;
}

.filtro-input-wrapper {
  height: 42px;
  display: flex;
  align-items: center;
  position: relative;
}

.filtro-input-wrapper > div,
.filtro-input-wrapper > button {
  width: 100%;
  height: 100%;
}

.filtro-label {
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  color: #5e6278;
  height: 18px;
  line-height: 18px;
}

.required::after {
  content: "*";
  color: #f1416c;
  margin-left: 2px;
}

.filtro-date-input {
  width: 100%;
  height: 100%;
}

.filtro-clear-btn {
  position: absolute;
  right: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background-color: rgba(241, 65, 108, 0.1);
  color: #f1416c;
  cursor: pointer;
  z-index: 2;
  font-size: 14px;
  transition: all 0.2s ease;
}

.filtro-clear-btn:hover {
  background-color: #f1416c;
  color: white;
  transform: scale(1.1);
}

/* Oculta el botón X nativo de Element UI */
:deep(.el-input__clear) {
  display: none !important;
}

/* Adaptar altura y estilos del select para que coincida con el input */
:deep(.el-select) {
  width: 100%;
  height: 42px !important;
}

:deep(.el-select .el-input) {
  height: 42px !important;
}

:deep(.el-select .el-input__wrapper) {
  height: 42px !important;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  padding: 0 30px 0 12px !important; /* Ajustar padding igual que el datepicker */
  border-radius: 0.475rem;
}

:deep(.el-select .el-input__inner) {
  height: 42px !important;
  line-height: 42px !important;
  padding: 0 !important;
}

/* Bordes consistentes para los selects con tema claro/oscuro, igual que los datepickers */
/* Tema claro */
html:not([data-theme="dark"]) :deep(.el-select .el-input__wrapper) {
  border: 1px solid var(--kt-input-solid-border-color, #e4e6ef) !important;
  background-color: var(--kt-input-solid-bg, #f5f8fa) !important;
  box-shadow: none !important;
}

html:not([data-theme="dark"]) :deep(.el-select .el-input__wrapper:hover) {
  border-color: var(--kt-primary-light, #a1a5b7) !important;
}

html:not([data-theme="dark"]) :deep(.el-select .el-input__wrapper.is-focus),
html:not([data-theme="dark"]) :deep(.el-select .el-input__wrapper:focus-within) {
  border-color: var(--kt-primary, #009ef7) !important;
  box-shadow: 0 0 0 1px var(--kt-primary, #009ef7) !important;
}

/* Tema oscuro */
html[data-theme="dark"] :deep(.el-select .el-input__wrapper) {
  border: 1px solid var(--kt-input-solid-border-color, #2b2b40) !important;
  background-color: var(--kt-input-solid-bg, #1b1b29) !important;
  color: var(--kt-gray-700, #cdcdde);
  box-shadow: none !important;
}

html[data-theme="dark"] :deep(.el-select .el-input__wrapper:hover) {
  border-color: var(--kt-gray-600, #3f4254) !important;
}

html[data-theme="dark"] :deep(.el-select .el-input__wrapper.is-focus),
html[data-theme="dark"] :deep(.el-select .el-input__wrapper:focus-within) {
  border-color: var(--kt-primary, #009ef7) !important;
  box-shadow: 0 0 0 1px var(--kt-primary, #009ef7) !important;
}

/* Estilo consistente para el texto en los selects en tema oscuro */
html[data-theme="dark"] :deep(.el-select .el-input__inner) {
  color: var(--kt-gray-700, #cdcdde) !important;
}

/* Ajustar el popup del select para que sea coherente con el tema */
:deep(.el-select__popper) {
  border-radius: 0.475rem !important;
}

/* Tema claro para el popup del select */
html:not([data-theme="dark"]) :deep(.el-select__popper) {
  --el-select-dropdown-bg-color: #fff;
  --el-select-dropdown-border-color: var(--kt-input-solid-border-color, #e4e6ef);
  --el-select-dropdown-item-hover-color: var(--kt-primary, #009ef7);
}

/* Tema oscuro para el popup del select */
html[data-theme="dark"] :deep(.el-select__popper) {
  --el-select-dropdown-bg-color: var(--kt-input-solid-bg, #1b1b29);
  --el-select-dropdown-border-color: var(--kt-input-solid-border-color, #2b2b40);
  --el-select-dropdown-item-hover-color: var(--kt-primary, #009ef7);
  --el-select-dropdown-item-hover-bg: rgba(0, 158, 247, 0.1);
  color: var(--kt-gray-700, #cdcdde);
}

html[data-theme="dark"] :deep(.el-select-dropdown__item) {
  color: var(--kt-gray-700, #cdcdde);
}

html[data-theme="dark"] :deep(.el-select-dropdown__item.hover) {
  color: #fff;
}

/* Ajustar estilo del placeholder en todos los controles */
:deep(.el-input__inner::placeholder),
.form-control::placeholder {
  opacity: 0.7;
}

html[data-theme="dark"] :deep(.el-input__inner::placeholder),
html[data-theme="dark"] .form-control::placeholder {
  color: var(--kt-gray-500, #7e8299);
}

/* Adaptar altura y estilos del select para que coincida con el input */
:deep(.el-select) {
  width: 100%;
  height: 42px !important;
}

:deep(.el-select .el-input) {
  height: 42px !important;
}

:deep(.el-select .el-input__wrapper) {
  height: 42px !important;
  padding: 0 12px !important;
  box-sizing: border-box;
  display: flex;
  align-items: center;
}

:deep(.el-select .el-input__inner) {
  height: 42px !important;
  line-height: 42px !important;
  padding: 0 !important;
}

/* Bordes consistentes para los inputs de fecha con tema claro/oscuro */
/* Tema claro */
html:not([data-theme="dark"]) :deep(.el-date-editor.el-input__wrapper) {
  border: 1px solid var(--kt-input-solid-border-color, #e4e6ef) !important;
  background-color: var(--kt-input-solid-bg, #f5f8fa) !important;
  padding: 0 30px 0 12px !important;
  box-shadow: none !important;
  border-radius: 0.475rem;
}

html:not([data-theme="dark"]) :deep(.el-date-editor.el-input__wrapper:hover) {
  border-color: var(--kt-primary-light, #a1a5b7) !important;
}

html:not([data-theme="dark"]) :deep(.el-date-editor.el-input__wrapper.is-focused),
html:not([data-theme="dark"]) :deep(.el-date-editor.el-input__wrapper:focus-within) {
  border-color: var(--kt-primary, #009ef7) !important;
  box-shadow: 0 0 0 1px var(--kt-primary, #009ef7) !important;
}

/* Tema oscuro */
html[data-theme="dark"] :deep(.el-date-editor.el-input__wrapper) {
  border: 1px solid var(--kt-input-solid-border-color, #2b2b40) !important;
  background-color: var(--kt-input-solid-bg, #1b1b29) !important;
  color: var(--kt-gray-700, #cdcdde);
  padding: 0 30px 0 12px !important;
  box-shadow: none !important;
  border-radius: 0.475rem;
}

html[data-theme="dark"] :deep(.el-date-editor.el-input__wrapper:hover) {
  border-color: var(--kt-gray-600, #3f4254) !important;
}

html[data-theme="dark"] :deep(.el-date-editor.el-input__wrapper.is-focused),
html[data-theme="dark"] :deep(.el-date-editor.el-input__wrapper:focus-within) {
  border-color: var(--kt-primary, #009ef7) !important;
  box-shadow: 0 0 0 1px var(--kt-primary, #009ef7) !important;
}

/* Asegurarse que el texto en el datepicker tiene el color correcto en tema oscuro */
html[data-theme="dark"] :deep(.el-date-editor .el-input__inner) {
  color: var(--kt-gray-700, #cdcdde) !important;
}

/* Adaptar estilos a tema en selects */
html[data-theme="dark"] :deep(.el-select .el-input__wrapper) {
  background-color: var(--kt-input-solid-bg, #1b1b29) !important;
  border: 1px solid var(--kt-input-solid-border-color, #2b2b40) !important;
  color: var(--kt-gray-700, #cdcdde);
}

html:not([data-theme="dark"]) :deep(.el-select .el-input__wrapper) {
  background-color: var(--kt-input-solid-bg, #f5f8fa) !important;
  border: 1px solid var(--kt-input-solid-border-color, #e4e6ef) !important;
}

/* Ajustar posición y tamaño de los iconos */
:deep(.el-input__icon) {
  width: 20px !important;
  height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

:deep(.el-date-editor) {
  width: 100%;
  height: 100%;
}

.form-control {
  height: 42px !important;
}

/* Estilos para el selector de campo */
.campo-select {
  width: 100%;
  height: 100%;
}

/* Ajustar tamaño de los grupos para mejor distribución */
.filtro-group {
  min-width: 150px;
  flex: 1;
}

.filtro-search-group {
  min-width: 250px;
  flex: 2;
}

/* Media queries para responsive */
@media (max-width: 991px) {
  .filtro-group {
    min-width: 150px;
  }
}

@media (max-width: 767px) {
  .filtro-row {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .filtro-group {
    width: 100%;
    min-width: 100%;
  }
}

/* Estilo para el select múltiple para que tenga la altura correcta */
:deep(.el-select .el-select__tags) {
  display: flex;
  flex-wrap: wrap;
  overflow: visible;
  height: auto;
  max-width: 100%;
  padding-right: 30px;
  line-height: 1.2;
}

/* Mostrar hasta 2 tags completos en pantallas pequeñas */
:deep(.el-select .el-select__tags .el-tag) {
  max-width: calc(50% - 10px);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
  margin-bottom: 2px;
}

/* Mostrar hasta 4 tags completos en pantallas medianas */
@media (min-width: 600px) {
  :deep(.el-select .el-select__tags .el-tag) {
    max-width: calc(25% - 10px);
  }
}

/* Mostrar hasta 6 tags completos en pantallas grandes */
@media (min-width: 900px) {
  :deep(.el-select .el-select__tags .el-tag) {
    max-width: calc(16.66% - 10px);
  }
}

/* Si hay más tags de los que caben, el select colapsa el resto automáticamente (collapse-tags) */
:deep(.el-select .el-select__tags-text) {
  display: inline-block;
  max-width: 100%;
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
  word-break: break-word;
}

/* Hacer que las tags sean completamente visibles */
:deep(.el-tag) {
  max-width: 100%;
  overflow: visible;
  white-space: normal;
  height: auto;
  margin-top: 2px;
  margin-bottom: 2px;
}

/* Agregar espacio para el icono del dropdown */
:deep(.el-select .el-input__wrapper) {
  padding-right: 30px !important;
}

/* Ajustar la posición del icono del dropdown */
:deep(.el-select .el-input__suffix) {
  right: 5px;
}

/* Manejar el contenido cuando hay tags colapsadas */
:deep(.el-select .el-select__tags .el-select__tags-text) {
  display: inline-block;
  max-width: unset;
  white-space: normal;
  word-break: break-word;
}

/* Solo aplicar collapse-tags cuando hay más de 2 selecciones para mejor visibilidad */
@media (min-width: 768px) {
  :deep(.el-select--small.el-select--multiple.el-select) {
    --el-select-multiple-input-line-height: normal;
  }
}

/* Estilo para los tags seleccionados */
:deep(.el-select .el-select__tags .el-tag) {
  background-color: var(--kt-primary-light, rgba(0, 158, 247, 0.1));
  color: var(--kt-primary, #009ef7);
  margin-right: 4px;
  border-color: var(--kt-primary-light, rgba(0, 158, 247, 0.2));
}
</style>