// services/ApiService.js
import axios from 'axios';
import JwtService from './JwtService';

export default function createApiService(baseURL) {
  const instance = axios.create({
    baseURL : baseURL,
    timeout : 30000,  // Increased timeout to 30 seconds
    headers : {
      'Content-Type' : 'application/json',
      'Accept'       : 'application/json'
    },
  });

  instance.interceptors.request.use(
    (config) => {
      const token = JwtService.getToken();
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    },
    (error) => {
      console.error('Error en la configuración del request:', error);
      return Promise.reject(error);
    }
  );

  instance.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response) {
        switch (error.response.status) {
        case 401:
          // Token inválido o expirado
          if (JwtService.loggedIn()) {
            JwtService.destroyToken();
            window.location.href = '/';
          }
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
      return instance.get(url, params);
    },
    post(url, data = {}) {
      return instance.post(url, data);
    },
    put(url, data = {}) {
      return instance.put(url, data);
    },
    delete(url, params = {}) {
      return instance.delete(url, { params });
    },
  }
};