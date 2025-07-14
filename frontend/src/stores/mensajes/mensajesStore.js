import { defineStore } from 'pinia'
import createApiService from '@/core/services/ApiService'

// API configurada
const Apiservice = createApiService(import.meta.env.VITE_API_URL || 'http://localhost:8087/api')

export const useMensajesStore = defineStore('mensajes', {
  state : () => ({
    mensajes            : [],
    mensajeSeleccionado : null,
    default             : {
      id           : null,
      asunto       : '',
      contenido    : '',
      destinatario : '',
      remitente    : '',
      fecha        : '',
      leido        : false,
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
        this.mensajes = response.data.data
      } catch (error) {
        console.error('Error al obtener los mensajes:', error)
        this.error = error
      } finally {
        this.loading = false
      }
    },

    async createMensaje(data) {
      try {
        const response = await Apiservice.post('/mensajes', data)
        this.mensajes.push(response.data.data)
        return response.data
      } catch (error) {
        console.error('Error al crear el mensaje:', error)
        throw error
      }
    },

    async marcarLeido(id) {
      try {
        const response = await Apiservice.post(`/mensajes/${id}/leido`);
        const index = this.mensajes.findIndex(m => m.id === id);
        if (index !== -1) {
          this.mensajes[index] = response.data.data; // actualiza con info nueva
        }
      } catch (error) {
        console.error('Error al marcar como leído:', error);
      }
    },

    async updateMensaje(id, data) {
      try {
        const response = await Apiservice.put(`/mensajes/${id}`, data)
        const index = this.mensajes.findIndex(m => m.id === id)
        if (index !== -1) {
          this.mensajes[index] = response.data.data
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
