import { defineStore } from 'pinia'
import createApiService from "@/core/services/ApiService"

const ApiFileService = createApiService(import.meta.env.VITE_API_FILE)

export const useFileStore = defineStore('fileStore', {
  state : () => ({
    archivos : [] // [{ id, name, size, extension, url, ... }]
  }),

  actions : {
    async subirArchivo(file) {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('carpeta', 'public')
      formData.append('temporal', true)

      try {
        const response = await ApiFileService.post('/upload', formData)
        this.archivos.push(response)
        return response
      } catch (error) {
        console.error('Error al subir archivo:', error)
        throw error
      }
    },

    async eliminarArchivo(id) {
      try {
        await ApiFileService.delete(`/${id}`)
        this.archivos = this.archivos.filter(a => a.id !== id)
      } catch (error) {
        console.error(`Error al eliminar archivo ${id}:`, error)
      }
    },

    async marcarPermanentes() {
      const idsTemporales = this.archivos.filter(a => a.temporal).map(a => a.id)
      if (!idsTemporales.length) return

      try {
        await ApiFileService.post('/marcar-permanente', { ids: idsTemporales })
        this.archivos = this.archivos.map(a => ({
          ...a,
          temporal : false
        }))
      } catch (error) {
        console.error('Error al marcar archivos como permanentes:', error)
      }
    },

    async mostrarArchivo(id) {
      try {
        const data = await ApiFileService.get(`/${id}`)
        return data.data
      } catch (error) {
        console.error(`Error al obtener archivo ${id}:`, error)
        throw error
      }
    },

    async mostrarArchivosBatch(ids) {
      if (!ids || ids.length === 0) return []
      try {
        // Enviar array de IDs como ids[]=1&ids[]=2...
        const queryParams = ids.map(id => `ids[]=${encodeURIComponent(id)}`).join('&')
        const data = await ApiFileService.get(`/info?${queryParams}`)
        return data.data?.archivos || []
      } catch (error) {
        console.error('Error al obtener archivos por lote:', error)
        throw error
      }
    },

    async descargarArchivo(id) {
      try {
        const response = await ApiFileService.download(`/download/${id}`)
        // Esto abre el archivo en el navegador o lo fuerza a descargar
        const blob = new Blob([response.data])
        const link = document.createElement('a')
        const url = window.URL.createObjectURL(blob)
        link.href = url
        link.setAttribute('download', 'archivo') // opcional: usa nombre dinámico si lo tienes
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error(`Error al descargar archivo ${id}:`, error)
        throw error
      }
    },

    async validarFirma(id) {
      try {
        const data = await ApiFileService.get(`/validar/${id}`)
        return data.data?.data || data.data
      } catch (error) {
        console.error(`Error al validar firma archivo ${id}:`, error)
        throw error
      }
    },

    limpiar() {
      this.archivos = []
    }
  }
})
