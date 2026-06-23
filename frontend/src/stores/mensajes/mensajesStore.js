import { defineStore } from 'pinia'
import createApiService from '@/core/services/ApiService'
import { useFileStore } from '@/stores/files/filesStore'

// Cliente HTTP del servicio de casilla/mensajeria.
const Apiservice = createApiService(import.meta.env.VITE_API_URL || 'http://localhost:8087/api')

// Store de mensajes para bandeja de entrada/salida y acciones CRUD.
export const useMensajesStore = defineStore('mensajes', {
  // Estado centralizado de la bandeja y del mensaje seleccionado.
  state : () => ({
    mensajes            : [],
    mensajeSeleccionado : null,
    currentTray         : 'entrada',
    pagination          : {
      current_page : 1,
      per_page     : 10,
      total        : 0,
    },
    counts : {
      entrada    : 0,
      enviados   : 0,
      destacados : 0,
      archivados : 0,
    },
    default : {
      id                 : null,
      prioridad          : 1,
      asunto             : '',
      contenido          : '',
      casilla_destino_id : '',
      created_at         : null,
      read_at            : null,
      leido              : false,
      destacado          : false,
      archivado          : false,
    },
    loading : false,
    error   : null,
  }),

  getters : {
    // Devuelve cantidad de mensajes cargados en la lista actual.
    mensajesCount : (state) => state.mensajes.length,

    // Indica si existe un mensaje seleccionado en la UI.
    tieneSeleccionado : (state) => !!state.mensajeSeleccionado,
  },

  actions : {
    // Obtiene mensajes del tipo solicitado y prepara estructura para adjuntos.
    async fetchMensajes(type = 'entrada', params = {}) {
      // Activa estado de carga y limpia error previo.
      this.loading = true
      this.error = null
      this.currentTray = type

      try {
        const response = await Apiservice.get(`/mensajes/${type}`, params)
        const meta = response?.data?.meta || {}

        // Normaliza cada mensaje para soportar carga diferida de archivos.
        this.mensajes = response.data.data.map(m => ({
          ...m,
          archivos         : [],
          adjuntos         : [],
          archivosCargados : false,
        }))
        this.pagination = {
          current_page : Number(meta.current_page || params.page || 1),
          per_page     : Number(meta.per_page || params.per_page || 10),
          total        : Number(meta.total || this.mensajes.length),
        }
        if (Object.prototype.hasOwnProperty.call(this.counts, type)) {
          this.counts[type] = this.pagination.total
        }

        // Resuelve adjuntos de forma asyncrona luego de cargar la lista base.
        await this.cargarArchivosDeMensajes()
      } catch (error) {
        console.error('Error al obtener los mensajes:', error)
        this.error = error
      } finally {
        this.loading = false
      }
    },

    async fetchCounts() {
      const trays = ['entrada', 'enviados', 'destacados', 'archivados']
      const responses = await Promise.allSettled(
        trays.map((tray) => Apiservice.get(`/mensajes/${tray}`, { per_page: 1, page: 1 }))
      )

      responses.forEach((result, index) => {
        const tray = trays[index]
        if (result.status === 'fulfilled') {
          this.counts[tray] = Number(result.value?.data?.meta?.total || 0)
        }
      })
    },

    // Carga metadatos/urls de archivos para mensajes que tienen archivo_ids.
    async cargarArchivosDeMensajes() {
      const fileStore = useFileStore()

      // Resuelve archivos por mensaje sin detener todo el flujo ante fallas parciales.
      await Promise.allSettled(
        this.mensajes.map(async (mensaje) => {
          if (mensaje.archivosCargados || !mensaje.archivo_ids?.length) return

          // Intenta resolver cada archivo y conserva solo resultados exitosos.
          const archivos = await Promise.allSettled(
            mensaje.archivo_ids.map(id => fileStore.mostrarArchivo(id))
          )

          mensaje.archivos = archivos
            .filter(r => r.status === 'fulfilled')
            .map(r => r.value)

          mensaje.adjuntos = mensaje.archivos.map(a => ({
            id     : a.id,
            nombre : a.nombre_original || a.nombre_archivo || a.nombre || 'Archivo adjunto',
            tamaño : a.tamanio ? (Math.round(a.tamanio / 1024) + ' KB') : null,
            tipo   : a.mime_type || a.tipo,
          }))

          mensaje.archivosCargados = true
        })
      )
    },

    // Crea un nuevo mensaje y lo sincroniza en la lista local.
    async createMensaje(data) {
      try {
        const response = await Apiservice.post('/mensajes', data)

        // Inserta el mensaje creado con estructura preparada para adjuntos.
        const nuevo = {
          ...response.data.data,
          archivos         : [],
          adjuntos         : [],
          archivosCargados : false,
        }
        this.mensajes.push(nuevo)
        this.counts.enviados += 1
        return response.data
      } catch (error) {
        console.error('Error al crear el mensaje:', error)
        throw error
      }
    },

    // Marca un mensaje como leido y conserva adjuntos ya resueltos en memoria.
    async marcarLeido(id) {
      try {
        const response = await Apiservice.post(`/mensajes/${id}/leido`)
        this.syncMensaje(response?.data?.data)
      } catch (error) {
        console.error('Error al marcar como leído:', error)
      }
    },

    async toggleDestacado(id) {
      try {
        const response = await Apiservice.post(`/mensajes/${id}/destacar`)
        this.syncMensaje(response?.data?.data)
        return response?.data?.data || null
      } catch (error) {
        console.error('Error al destacar el mensaje:', error)
        throw error
      }
    },

    async toggleArchivado(id) {
      try {
        const response = await Apiservice.post(`/mensajes/${id}/archivar`)
        this.syncMensaje(response?.data?.data)
        return response?.data?.data || null
      } catch (error) {
        console.error('Error al archivar el mensaje:', error)
        throw error
      }
    },

    // Actualiza un mensaje existente y mantiene estado local de adjuntos.
    async updateMensaje(id, data) {
      try {
        const response = await Apiservice.put(`/mensajes/${id}`, data)
        const index = this.mensajes.findIndex(m => m.id === id)
        if (index !== -1) {
          // Conserva cache de archivos para evitar recargas innecesarias.
          this.mensajes[index] = {
            ...response.data.data,
            archivos         : this.mensajes[index].archivos,
            adjuntos         : this.mensajes[index].adjuntos,
            archivosCargados : this.mensajes[index].archivosCargados,
          }
        }
        return response.data
      } catch (error) {
        console.error('Error al actualizar el mensaje:', error)
        throw error
      }
    },

    // Elimina mensaje en backend y limpia la lista local.
    async deleteMensaje(id) {
      try {
        await Apiservice.delete(`/mensajes/${id}`)
        this.mensajes = this.mensajes.filter(m => m.id !== id)
      } catch (error) {
        console.error('Error al eliminar el mensaje:', error)
        throw error
      }
    },

    // Selecciona un mensaje para mostrar detalle en panel derecho.
    selectMensaje(mensaje) {
      this.mensajeSeleccionado = mensaje
    },

    syncMensaje(mensajeActualizado) {
      if (!mensajeActualizado?.id) return

      const index = this.mensajes.findIndex(m => m.id === mensajeActualizado.id)
      if (index === -1) return

      this.mensajes[index] = {
        ...mensajeActualizado,
        archivos         : this.mensajes[index].archivos,
        adjuntos         : this.mensajes[index].adjuntos,
        archivosCargados : this.mensajes[index].archivosCargados,
      }
    },

    // Limpia seleccion activa del mensaje.
    deselectMensaje() {
      this.mensajeSeleccionado = null
    },
  },
})