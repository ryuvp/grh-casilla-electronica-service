import axios from 'axios';
import JwtService from './JwtService';

export default function createApiService(baseURL) {
  const instance = axios.create({
    baseURL,
    timeout : 30000,
    headers : {
      'Accept' : 'application/json'
      // No establecer Content-Type globalmente
    }
  });

  // Interceptor de solicitud
  instance.interceptors.request.use(
    (config) => {
      const token = JwtService.getToken();
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    },
    (error) => {
      console.error('Error en la configuración de la solicitud:', error);
      return Promise.reject(error);
    }
  );

  // Interceptor de respuesta
  instance.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response) {
        switch (error.response.status) {
        case 401:
          console.warn('Token inválido o expirado');
          break;
        case 403:
          console.error('Acción no autorizada');
          break;
        case 404:
          console.error('Recurso no encontrado');
          break;
        case 500:
          console.error('Error del servidor');
          break;
        default:
          console.error(`Error ${error.response.status}: ${error.message}`);
        }
      } else {
        console.error('Error en la configuración del response:', error);
      }
      return Promise.reject(error);
    }
  );

  return {
    get(url, params = {}) {
      return instance.get(url, { params });
    },
    post(url, data = {}, customConfig = {}) {
      const config = { ...customConfig };
      if (data instanceof FormData) {
        config.headers = {
          ...config.headers,
          'Content-Type' : undefined // dejar que axios lo genere automáticamente
        };
      }
      return instance.post(url, data, config);
    },
    put(url, data = {}, config = {}) {
      return instance.put(url, data, config);
    },
    delete(url, params = {}) {
      return instance.delete(url, { params });
    },
    setHeaders(newHeaders = {}) {
      Object.assign(instance.defaults.headers.common, newHeaders);
    },
    removeHeader(headerName) {
      delete instance.defaults.headers.common[headerName];
    }
  };
}
