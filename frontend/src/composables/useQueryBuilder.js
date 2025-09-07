// composables/useQueryBuilder.js
import { ref } from 'vue'

/**
 * useQueryBuilder
 * props:
 *  - search, searchField, dateStart, dateEnd, dateField
 *  - searchableFields: [{label, value}]  // usado para default de searchField
 *  - initialExtras: objeto con params extra iniciales (opcional)
 *  - snakeCase: boolean -> convierte claves a snake_case en buildQuery (opcional, false por defecto)
 *  - keyMap: { uiKey: 'backend_key' } -> mapea claves finales en buildQuery (opcional)
 */
export default function useQueryBuilder(props = {}) {
  // Base
  const search = ref(props.search || '')
  const searchField = ref(
    Array.isArray(props.searchField) && props.searchField.length > 0
      ? [...props.searchField]
      : (props.searchableFields || []).map(f => f.value)
  )
  const dateStart = ref(props.dateStart || '')
  const dateEnd = ref(props.dateEnd || '')
  const dateField = ref(props.dateField || '')

  // Extras arbitrarios
  const extras = ref({ ...(props.initialExtras || {}) })

  // Helpers para extras
  function setParam(key, value) {
    if (value === undefined) return
    extras.value[key] = value
  }

  function setParams(obj = {}) {
    for (const [k, v] of Object.entries(obj)) setParam(k, v)
  }

  function removeParam(key) {
    if (key in extras.value) delete extras.value[key]
  }

  function resetParams() {
    extras.value = {}
  }

  // Utils
  const toSnake = s => s.replace(/[A-Z]/g, m => '_' + m.toLowerCase())
  const isEmpty = v =>
    v === null ||
    v === undefined ||
    (typeof v === 'string' && v.trim() === '') ||
    (Array.isArray(v) && v.length === 0)

  function normalizeKeys(obj, { snakeCase = false, keyMap = {} } = {}) {
    const out = {}
    for (const [k, v] of Object.entries(obj)) {
      if (isEmpty(v)) continue
      const mapped = keyMap[k] || k
      const finalKey = snakeCase ? toSnake(mapped) : mapped
      out[finalKey] = v
    }
    return out
  }

  /**
   * buildQuery
   * options:
   *  - snakeCase: boolean -> convierte claves a snake_case
   *  - keyMap: { uiKey: 'backend_key' } -> mapea claves
   *  - includeEmpty: boolean -> si true, no filtra vacíos (por defecto false)
   */
  function buildQuery(options = {}) {
    const { snakeCase = !!props.snakeCase, keyMap = props.keyMap || {}, includeEmpty = false } = options

    const base = {
      search      : search.value,
      searchField : searchField.value,
      dateStart   : dateStart.value,
      dateEnd     : dateEnd.value,
      dateField   : dateField.value,
      ...extras.value
    }

    if (includeEmpty) {
      // Solo normaliza claves, no filtra vacíos
      const out = {}
      for (const [k, v] of Object.entries(base)) {
        const mapped = keyMap[k] || k
        const finalKey = snakeCase ? toSnake(mapped) : mapped
        out[finalKey] = v
      }
      return out
    }

    // Normaliza + elimina vacíos
    return normalizeKeys(base, { snakeCase, keyMap })
  }

  return {
    // estado base
    search, searchField, dateStart, dateEnd, dateField,
    // extras y helpers
    extras, setParam, setParams, removeParam, resetParams,
    // construir query final
    buildQuery,
  }
}
