import { defineStore } from "pinia";
import { Usuario } from '@/core/models/index.js';

const { state, getters, actions } = Usuario.getStore();
const useUsuariosStore = defineStore('usuarios', {
  state   : () => ({ ...state }),
  getters : { ...getters },
  actions : { ...actions },
  headers:{
    'Content-Type' : 'multipart/form-data',
  }
});
export default useUsuariosStore;