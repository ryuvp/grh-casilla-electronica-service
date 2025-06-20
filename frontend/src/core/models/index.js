// core/models/index.js
import Model from '@/core/services/pmsg/Model'
import createApiService from '@/core/services/ApiService'
const Apiservice = createApiService(import.meta.env.VITE_API_URL || 'http://localhost:8083/api')


