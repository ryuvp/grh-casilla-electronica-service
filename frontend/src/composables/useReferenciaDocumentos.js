import { ref, computed } from 'vue'

// Stub para casilla: no hay referencias de documentos SGD.
// El componente ReferenciasDocumento mostrará "Sin referencias ni archivos".

const TIPOS_REFERENCIAS = {
  normativas : { label: 'Normativas', icono: 'bi bi-file-earmark-ruled', botonActivo: 'btn-secondary', botonInactivo: 'btn-outline-secondary' },
  leyes      : { label: 'Leyes',      icono: 'bi bi-file-earmark-check', botonActivo: 'btn-warning',   botonInactivo: 'btn-outline-warning'   },
  documentos : { label: 'Documentos', icono: 'bi bi-file-earmark',       botonActivo: 'btn-info',      botonInactivo: 'btn-outline-info'      },
}

function getTituloTipo(tipo)     { return TIPOS_REFERENCIAS[tipo]?.label         || tipo }
function getIconoTipo(tipo)      { return TIPOS_REFERENCIAS[tipo]?.icono          || 'bi bi-file-earmark' }
function getBotonActivoClass(t)  { return TIPOS_REFERENCIAS[t]?.botonActivo       || 'btn-primary' }
function getBotonInactivoClass(t){ return TIPOS_REFERENCIAS[t]?.botonInactivo     || 'btn-outline-primary' }
function formatearIdDocumento(id){ return `Registro del documento ${String(id||0).padStart(7,'0')}` }

let sharedState = null

export function useReferenciaDocumentos() {
  if (!sharedState) {
    sharedState = {
      loading                    : ref(false),
      error                      : ref(null),
      referencias                : ref([]),
      documentosConArchivos      : ref({}),
      documentoPrincipal         : ref(null),
      archivosDocumentoPrincipal : ref([]),
    }
  }

  const { loading, error, referencias, documentosConArchivos, documentoPrincipal, archivosDocumentoPrincipal } = sharedState

  const referenciasPorTipo = computed(() => ({
    normativas : [],
    leyes      : [],
    documentos : [],
  }))

  const tieneReferencias               = computed(() => false)
  const archivoPrincipalDocPrincipal   = computed(() => null)
  const anexosDocPrincipal             = computed(() => [])
  const obtenerArchivosDocumento       = computed(() => () => [])
  const obtenerArchivosPorTipo         = computed(() => () => ({ principal: null, anexos: [] }))

  function limpiarEstadoCompleto() {
    loading.value = false; error.value = null
    referencias.value = []; documentosConArchivos.value = {}
    archivosDocumentoPrincipal.value = []; documentoPrincipal.value = null
  }

  async function cargarReferenciasDocumento() { return true }
  async function obtenerDocumentoIdDeArchivo() { return null }
  async function establecerDocumentoPrincipal() {}
  async function cargarArchivosDocumentoPrincipal() {}
  async function cargarArchivosDocumentosReferenciados() {}
  function establecerArchivosDirectos() {}
  function normalizarArchivos() { return [] }

  return {
    loading, error, referencias, documentosConArchivos,
    documentoPrincipal, archivosDocumentoPrincipal,
    referenciasPorTipo, obtenerArchivosDocumento, obtenerArchivosPorTipo,
    archivoPrincipalDocPrincipal, anexosDocPrincipal,
    tieneReferencias, formatearIdDocumento,
    cargarReferenciasDocumento, cargarArchivosDocumentosReferenciados,
    cargarArchivosDocumentoPrincipal, obtenerDocumentoIdDeArchivo,
    establecerDocumentoPrincipal, limpiarEstadoCompleto, establecerArchivosDirectos,
    getTituloTipo, getIconoTipo, getBotonActivoClass, getBotonInactivoClass,
    normalizarArchivos, TIPOS_REFERENCIAS,
  }
}
