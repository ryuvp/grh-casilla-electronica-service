// core/models/index.js
import Model from '@/core/services/pmsg/Model'
import createApiService from '@/core/services/ApiService'
const Apiservice = createApiService(import.meta.env.VITE_API_URL || 'http://localhost:8087/api')
const ApiAuthService = createApiService(import.meta.env.VITE_AUTH_API || 'http://localhost:8082/api')

import UsuarioConfig from '@/core/models/config/usuarios'
import MensajeConfig from '@/core/models/config/mensaje'
import CasillaConfig from '@/core/models/config/casilla'

export const Usuario = new Model(UsuarioConfig, ApiAuthService)
export const Mensaje = new Model(MensajeConfig, Apiservice)
export const Casilla = new Model(CasillaConfig, Apiservice)
