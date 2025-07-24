import { defineStore } from 'pinia'
import createApiService from "@/core/services/ApiService"

const ApiFileService = createApiService(import.meta.env.VITE_API_FILE || "http://localhost:8085/api/files")

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
        await ApiFileService.put('/marcar-permanente', { ids: idsTemporales })
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

    async descargarArchivo(id) {
      try {
        const response = await ApiFileService.download(`/${id}/download`)
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

    limpiar() {
      this.archivos = []
    }
  }
})
