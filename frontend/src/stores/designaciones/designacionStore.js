import { defineStore } from 'pinia'
import createApiService from '@/core/services/ApiService'

const ApiCasillaService = createApiService(import.meta.env.VITE_API_URL || 'http://localhost:8087/api')
const ApiAuthService = createApiService(import.meta.env.VITE_AUTH_API)

export const useDesignacionStore = defineStore('designacionStore', {
  state : () => ({
    resumenByDesignacionId : {},
    actorByCasillaId       : {},
    loadingByCasillaId     : {},
    casillaByDesignacionId : {},
    loadingCasillaByDesignacionId : {},
  }),

  actions : {
    // Obtiene y cachea resumen de usuario/cargo por designacion.
    async fetchResumenByDesignacionId(designacionId) {
      if (!designacionId) return null
      if (this.resumenByDesignacionId[designacionId]) {
        return this.resumenByDesignacionId[designacionId]
      }

      try {
        const response = await ApiAuthService.get(`/designaciones/${designacionId}/usuario-cargo`)
        const data = response?.data || null

        if (data?.designacion_id) {
          this.resumenByDesignacionId[data.designacion_id] = data
        }

        return data
      } catch (error) {
        console.error('Error obteniendo resumen de designacion:', error)
        return null
      }
    },

    async fetchActiveCasillaByDesignacionId(designacionId) {
      if (!designacionId) return null
      if (Object.prototype.hasOwnProperty.call(this.casillaByDesignacionId, designacionId)) {
        return this.casillaByDesignacionId[designacionId]
      }
      if (this.loadingCasillaByDesignacionId[designacionId]) {
        return this.loadingCasillaByDesignacionId[designacionId]
      }

      const promise = (async () => {
        try {
          const response = await ApiCasillaService.get('/casillas', {
            designacion_id : designacionId,
            activo         : true,
            per_page       : 1,
            page           : 1,
          })

          const casilla = response?.data?.data?.[0] || null
          this.casillaByDesignacionId[designacionId] = casilla
          return casilla
        } catch (error) {
          console.error('Error obteniendo casilla activa por designacion:', error)
          this.casillaByDesignacionId[designacionId] = null
          return null
        } finally {
          delete this.loadingCasillaByDesignacionId[designacionId]
        }
      })()

      this.loadingCasillaByDesignacionId[designacionId] = promise
      return promise
    },

    async searchDestinatarios(query) {
      const search = String(query || '').trim()
      if (search.length < 2) return []

      try {
        const response = await ApiAuthService.get('/designaciones/buscar-destinatarios', {
          search,
          per_page : 20,
        })

        const candidatos = Array.isArray(response?.data?.data) ? response.data.data : []

        candidatos.forEach((item) => {
          if (item?.designacion_id) {
            this.resumenByDesignacionId[item.designacion_id] = item
          }
        })

        const resolved = await Promise.all(
          candidatos.map(async (item) => {
            const casilla = await this.fetchActiveCasillaByDesignacionId(item.designacion_id)
            if (!casilla?.id) return null

            return {
              casillaId      : casilla.id,
              casillaNumero  : casilla.numero,
              designacionId  : item.designacion_id,
              usuarioNombre  : item.usuario_nombre,
              numeroDocumento: item.numero_documento,
              cargoNombre    : item.cargo_nombre,
              label          : `${item.usuario_nombre}${item.cargo_nombre ? ` - ${item.cargo_nombre}` : ''} - Casilla ${casilla.numero}`,
            }
          })
        )

        return resolved.filter(Boolean)
      } catch (error) {
        console.error('Error buscando destinatarios por designacion:', error)
        return []
      }
    },

    async searchDesignaciones(query) {
      const search = String(query || '').trim()
      if (search.length < 2) return []

      try {
        const response = await ApiAuthService.get('/designaciones/buscar-destinatarios', {
          search,
          per_page : 20,
        })

        const candidatos = Array.isArray(response?.data?.data) ? response.data.data : []
        candidatos.forEach((item) => {
          if (item?.designacion_id) {
            this.resumenByDesignacionId[item.designacion_id] = item
          }
        })
        return candidatos
      } catch (error) {
        console.error('Error buscando designaciones:', error)
        return []
      }
    },

    // Resuelve actor por casilla para mostrar etiquetas De/Para en mensajeria.
    async resolveActorByCasillaId(casillaId) {
      if (!casillaId) return null
      if (this.actorByCasillaId[casillaId]) {
        return this.actorByCasillaId[casillaId]
      }
      if (this.loadingByCasillaId[casillaId]) {
        return this.loadingByCasillaId[casillaId]
      }

      const promise = (async () => {
        try {
          const casillaResponse = await ApiCasillaService.get(`/casillas/${casillaId}`)
          const casilla = casillaResponse?.data?.data || casillaResponse?.data || null
          const designacionId = casilla?.designacion_id || null

          if (designacionId) {
            this.casillaByDesignacionId[designacionId] = casilla
          }

          if (!designacionId) {
            return null
          }

          const resumen = await this.fetchResumenByDesignacionId(designacionId)
          if (!resumen) {
            return null
          }

          const actor = {
            casilla_id         : casillaId,
            designacion_id     : designacionId,
            usuario_nombre     : resumen.usuario_nombre,
            cargo_nombre       : resumen.cargo_nombre,
            cargo_tipo         : resumen.cargo_tipo,
            is_persona_natural : !!resumen.is_persona_natural,
            display_name       : resumen.display_name,
          }

          this.actorByCasillaId[casillaId] = actor
          return actor
        } catch (error) {
          console.error('Error resolviendo actor por casilla:', error)
          return null
        } finally {
          delete this.loadingByCasillaId[casillaId]
        }
      })()

      this.loadingByCasillaId[casillaId] = promise
      return promise
    },
  },
})

export default useDesignacionStore
