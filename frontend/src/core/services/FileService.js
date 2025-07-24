// Servicio para gestionar archivos usando Axios y JWT
import axios from 'axios' // Cliente HTTP para peticiones
import JwtService from './JwtService' // Servicio para obtener el token JWT

// Crear instancia de Axios con configuración base
const client = axios.create({
  baseURL : import.meta.env.VITE_API_FILE || 'http://localhost:8085/api/files', // URL base del microservicio de archivos
  timeout : 30000, // Tiempo máximo de espera
  // No se define Content-Type aquí para permitir que el navegador lo gestione automáticamente en uploads
})

// Interceptor para agregar el token JWT y limpiar Content-Type en cada petición
client.interceptors.request.use(config => {
  const token = JwtService.getToken()
  if (token) config.headers.Authorization = `Bearer ${token}` // Agrega el token JWT
  delete config.headers['Content-Type'] // Elimina Content-Type para uploads con FormData
  return config
})

// Métodos principales del servicio de archivos
export default {
  // Sube un archivo al microservicio
  upload(url, formData) {
    return client.post(url, formData)
  },
  // Elimina un archivo por ID
  delete(url, params) {
    return client.delete(url, { params })
  },
}
