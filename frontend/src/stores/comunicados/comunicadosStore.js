import { defineStore } from 'pinia'
import createApiService from '@/core/services/ApiService'

const ComunicadosService = createApiService(
  import.meta.env.VITE_COMUNICADOS_API || 'http://localhost:8087/api'
)

const fileInfoBase = import.meta.env.VITE_API_FILE
  || (import.meta.env.VITE_FILE_API ? `${import.meta.env.VITE_FILE_API.replace(/\/$/, '')}/api/files` : 'http://localhost:8085/api/files')

const FileInfoService = createApiService(fileInfoBase)

const SERVICIO_CODIGO = 'CASILLA'

function buildVisualizarUrl(fileId) {
  if (!fileId) return ''

  const fileBase = fileInfoBase.replace(/\/$/, '')
  const root = fileBase.replace(/\/api\/files$/, '')
  return `${root}/api/files/visualizar/${fileId}`
}

function normalizarTipoArchivo(mimeType = '') {
  if (mimeType.startsWith('image/')) return 'image'
  return ''
}

const useComunicadosStore = defineStore('comunicados-visibles', {
  state   : () => ({ items: [], loading: false, loaded: false }),
  actions : {
    async cargarVisibles() {
      this.loading = true

      try {
        const response = await ComunicadosService.get('/comunicados/visibles', { servicio: SERVICIO_CODIGO })
        const comunicados = response.data?.data || []
        const ids = [...new Set(comunicados.map(item => item.archivo_id).filter(Boolean))]
        let metadata = {}

        if (ids.length) {
          const filesResponse = await FileInfoService.get('/info', { ids })
          metadata = Object.fromEntries(
            (filesResponse.data?.archivos || []).map(archivo => [archivo.id, archivo])
          )
        }

        this.items = comunicados.map((item) => {
          const archivo = metadata[item.archivo_id] || {}

          return {
            ...item,
            archivo_nombre : archivo.nombre_original || archivo.ruta_relativa || '',
            archivo_tipo   : normalizarTipoArchivo(archivo.mime_type || ''),
            archivo_url    : buildVisualizarUrl(item.archivo_id),
          }
        }).filter(item => item.archivo_tipo === 'image')

        this.loaded = true
        return this.items
      } finally {
        this.loading = false
      }
    },

    async registrarVista(comunicadoId) {
      if (!comunicadoId) return null

      return ComunicadosService.post(`/comunicados/${comunicadoId}/registrar-vista`, {
        servicio : SERVICIO_CODIGO,
      })
    },
  },
})

export default useComunicadosStore
