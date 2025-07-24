// composables/useQueryBuilder.js
import { ref } from 'vue'

export default function useQueryBuilder(props) {
  const search = ref(props.search || '')

  // Inicializa con todos los campos si no se pasó searchField
  const searchField = ref(
    Array.isArray(props.searchField) && props.searchField.length > 0
      ? [...props.searchField]
      : (props.searchableFields || []).map(field => field.value)
  )

  const dateStart = ref(props.dateStart || '')
  const dateEnd = ref(props.dateEnd || '')
  const dateField = ref(props.dateField || '')

  function buildQuery() {
    return {
      search      : search.value,
      searchField : searchField.value,
      dateStart   : dateStart.value,
      dateEnd     : dateEnd.value,
      dateField   : dateField.value
    }
  }

  return {
    search,
    searchField,
    dateStart,
    dateEnd,
    dateField,
    buildQuery,
  }
}
