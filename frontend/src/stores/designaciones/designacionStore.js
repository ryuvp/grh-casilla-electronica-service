import { defineStore } from 'pinia'
import createApiService from '@/core/services/ApiService'

const ApiCasillaService = createApiService(import.meta.env.VITE_API_URL || 'http://localhost:8087/api')
const ApiAuthService = createApiService(import.meta.env.VITE_AUTH_API)

export const useDesignacionStore = defineStore('designacionStore', {
  state : () => ({
    resumenByDesignacionId        : {},
    actorByCasillaId              : {},
    loadingByCasillaId            : {},
    casillaByDesignacionId        : {},
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

        // Lote único en vez de una petición GET /casillas por candidato: mismo
        // patrón de filtro IN que resolveActorsByCasillaIds, más abajo en este
        // mismo store.
        const designacionIds = [...new Set(
          candidatos.map((item) => item?.designacion_id).filter(Boolean)
        )]
        const idsAResolver = designacionIds.filter(
          (id) => !Object.prototype.hasOwnProperty.call(this.casillaByDesignacionId, id)
        )

        if (idsAResolver.length) {
          try {
            const casillasResponse = await ApiCasillaService.get('/casillas', {
              designacion_id : ['in', ...idsAResolver],
              activo         : true,
              per_page       : Math.min(Math.max(idsAResolver.length, 10), 100),
            })

            const casillas = Array.isArray(casillasResponse?.data?.data) ? casillasResponse.data.data : []
            const casillaPorDesignacion = new Map()
            casillas.forEach((casilla) => {
              if (casilla?.designacion_id) {
                casillaPorDesignacion.set(Number(casilla.designacion_id), casilla)
              }
            })

            idsAResolver.forEach((id) => {
              this.casillaByDesignacionId[id] = casillaPorDesignacion.get(Number(id)) || null
            })
          } catch (error) {
            console.error('Error obteniendo casillas activas en lote:', error)
            idsAResolver.forEach((id) => {
              if (!Object.prototype.hasOwnProperty.call(this.casillaByDesignacionId, id)) {
                this.casillaByDesignacionId[id] = null
              }
            })
          }
        }

        const resolved = candidatos.map((item) => {
          const casilla = this.casillaByDesignacionId[item.designacion_id]
          if (!casilla?.id) return null

          return {
            casillaId       : casilla.id,
            casillaNumero   : casilla.numero,
            designacionId   : item.designacion_id,
            usuarioNombre   : item.usuario_nombre,
            numeroDocumento : item.numero_documento,
            cargoNombre     : item.cargo_nombre,
            // La casilla es de la persona: se identifica por su DNI, no por
            // un codigo tecnico interno/externo.
            label           : `${item.usuario_nombre}${item.cargo_nombre ? ` - ${item.cargo_nombre}` : ''}${item.numero_documento ? ` - Casilla DNI ${item.numero_documento}` : ''}`,
          }
        })

        return resolved.filter(Boolean)
      } catch (error) {
        console.error('Error buscando destinatarios por designacion:', error)
        return []
      }
    },

    // Resuelve el nombre de una persona por DNI contra Auth Service (filtro nativo
    // numero_documento del modelo Usuario, datos ya existentes en la BD). Se usa
    // para mostrar visualmente a quien se le va a notificar como destinatario
    // externo, antes de confirmarlo.
    async buscarUsuarioPorDni(dni) {
      if (!/^\d{8}$/.test(String(dni || ''))) return null

      try {
        const response = await ApiAuthService.get('/usuarios', {
          numero_documento : dni,
          per_page         : 1,
        })

        return response?.data?.data?.[0] || null
      } catch (error) {
        console.error('Error buscando usuario por DNI:', error)
        return null
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

    // Resuelve el resumen usuario/cargo de un LOTE de designaciones en una sola
    // petición (en vez de N llamadas a /designaciones/{id}/usuario-cargo).
    async fetchResumenByDesignacionIds(designacionIds = []) {
      const idsUnicos = [...new Set(
        (designacionIds || [])
          .map((id) => Number(id))
          .filter((id) => id > 0 && !this.resumenByDesignacionId[id])
      )]

      if (!idsUnicos.length) return this.resumenByDesignacionId

      try {
        const response = await ApiAuthService.get('/designaciones', {
          ids      : idsUnicos.join(','),
          per_page : Math.min(Math.max(idsUnicos.length, 10), 100),
        })

        const items = Array.isArray(response?.data?.data) ? response.data.data : []
        items.forEach((item) => {
          const cargoTipo = item?.cargo_tipo || ''
          const isPersonaNatural = String(cargoTipo).toLowerCase() === 'persona natural'
          const usuarioNombre = [item?.usuario_nombre, item?.usuario_apellido].filter(Boolean).join(' ')

          this.resumenByDesignacionId[item.id] = {
            designacion_id     : item.id,
            usuario_id         : item.usuario_id,
            usuario_nombre     : usuarioNombre,
            cargo_nombre       : item.cargo_nombre,
            cargo_tipo         : cargoTipo,
            is_persona_natural : isPersonaNatural,
            display_name       : isPersonaNatural
              ? usuarioNombre
              : [usuarioNombre, item.cargo_nombre].filter(Boolean).join(' - '),
          }
        })
      } catch (error) {
        console.error('Error obteniendo resumen en lote de designaciones:', error)
      }

      return this.resumenByDesignacionId
    },

    // Resuelve un LOTE de actores por casilla_id en máximo 2 peticiones
    // (1 a Casilla por los ids de casilla, 1 a Auth por las designaciones
    // resultantes), en vez de 2 peticiones POR CADA casilla distinta.
    async resolveActorsByCasillaIds(casillaIds = []) {
      const idsUnicos = [...new Set(
        (casillaIds || [])
          .map((id) => Number(id))
          .filter((id) => id > 0 && !this.actorByCasillaId[id])
      )]

      if (!idsUnicos.length) return this.actorByCasillaId

      try {
        const response = await ApiCasillaService.get('/casillas', {
          id       : ['in', ...idsUnicos],
          per_page : Math.min(Math.max(idsUnicos.length, 10), 100),
        })

        const casillas = Array.isArray(response?.data?.data) ? response.data.data : []
        const designacionIdPorCasilla = new Map()
        const casillaPorId = new Map()

        casillas.forEach((casilla) => {
          casillaPorId.set(casilla.id, casilla)
          if (casilla?.designacion_id) {
            this.casillaByDesignacionId[casilla.designacion_id] = casilla
            designacionIdPorCasilla.set(casilla.id, casilla.designacion_id)
          }
        })

        await this.fetchResumenByDesignacionIds([...designacionIdPorCasilla.values()])

        designacionIdPorCasilla.forEach((designacionId, casillaId) => {
          const resumen = this.resumenByDesignacionId[designacionId]
          if (!resumen) return

          this.actorByCasillaId[casillaId] = {
            casilla_id         : casillaId,
            casilla_numero     : casillaPorId.get(casillaId)?.numero || null,
            designacion_id     : designacionId,
            usuario_nombre     : resumen.usuario_nombre,
            cargo_nombre       : resumen.cargo_nombre,
            cargo_tipo         : resumen.cargo_tipo,
            is_persona_natural : !!resumen.is_persona_natural,
            display_name       : resumen.display_name,
          }
        })
      } catch (error) {
        console.error('Error resolviendo actores en lote por casilla:', error)
      }

      return this.actorByCasillaId
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
            casilla_numero     : casilla?.numero || null,
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
