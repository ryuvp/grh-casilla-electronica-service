import { defineStore } from 'pinia'
import createApiService from '@/core/services/ApiService'
import { useFileStore } from '@/stores/files/filesStore'

const Apiservice = createApiService(import.meta.env.VITE_API_URL || 'http://localhost:8087/api')

export const useMensajesStore = defineStore('mensajes', {
  state : () => ({
    mensajes            : [],
    mensajeSeleccionado : null,
    default             : {
      id                 : null,
      prioridad          : 1,
      asunto             : '',
      contenido          : '',
      usuario_destino_id : '',
      fecha_envio        : '',
      leido              : false,
    },
    loading : false,
    error   : null,
  }),

  getters : {
    mensajesCount     : (state) => state.mensajes.length,
    tieneSeleccionado : (state) => !!state.mensajeSeleccionado,
  },

  actions : {
    async fetchMensajes(type = 'entrada') {
      this.loading = true
      this.error = null

      try {
        const response = await Apiservice.get(`/mensajes/${type}`)
        this.mensajes = response.data.data.map(m => ({
          ...m,
          archivos         : [],
          archivosCargados : false,
        }))
        await this.cargarArchivosDeMensajes()
      } catch (error) {
        console.error('Error al obtener los mensajes:', error)
        this.error = error
      } finally {
        this.loading = false
      }
    },

    async cargarArchivosDeMensajes() {
      const fileStore = useFileStore()

      await Promise.allSettled(
        this.mensajes.map(async (mensaje) => {
          if (mensaje.archivosCargados || !mensaje.archivo_ids?.length) return

          const archivos = await Promise.allSettled(
            mensaje.archivo_ids.map(id => fileStore.mostrarArchivo(id))
          )

          mensaje.archivos = archivos
            .filter(r => r.status === 'fulfilled')
            .map(r => r.value)

          mensaje.archivosCargados = true
        })
      )
    },

    async createMensaje(data) {
      try {
        const response = await Apiservice.post('/mensajes', data)
        const nuevo = {
          ...response.data.data,
          archivos         : [],
          archivosCargados : false,
        }
        this.mensajes.push(nuevo)
        return response.data
      } catch (error) {
        console.error('Error al crear el mensaje:', error)
        throw error
      }
    },

    async marcarLeido(id) {
      try {
        const response = await Apiservice.post(`/mensajes/${id}/leido`)
        const index = this.mensajes.findIndex(m => m.id === id)
        if (index !== -1) {
          this.mensajes[index] = {
            ...response.data.data,
            archivos         : this.mensajes[index].archivos,
            archivosCargados : this.mensajes[index].archivosCargados,
          }
        }
      } catch (error) {
        console.error('Error al marcar como leído:', error)
      }
    },

    async updateMensaje(id, data) {
      try {
        const response = await Apiservice.put(`/mensajes/${id}`, data)
        const index = this.mensajes.findIndex(m => m.id === id)
        if (index !== -1) {
          this.mensajes[index] = {
            ...response.data.data,
            archivos         : this.mensajes[index].archivos,
            archivosCargados : this.mensajes[index].archivosCargados,
          }
        }
        return response.data
      } catch (error) {
        console.error('Error al actualizar el mensaje:', error)
        throw error
      }
    },

    async deleteMensaje(id) {
      try {
        await Apiservice.delete(`/mensajes/${id}`)
        this.mensajes = this.mensajes.filter(m => m.id !== id)
      } catch (error) {
        console.error('Error al eliminar el mensaje:', error)
        throw error
      }
    },

    selectMensaje(mensaje) {
      this.mensajeSeleccionado = mensaje
    },

    deselectMensaje() {
      this.mensajeSeleccionado = null
    },
  },
})