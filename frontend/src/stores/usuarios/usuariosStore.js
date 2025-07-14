import { defineStore } from "pinia";
import { Usuario } from '@/core/models/index.js';

const { state, getters, actions } = Usuario.getStore();
const useUsuariosStore = defineStore(Usuario.alias, {
  state   : () => ({ ...state }),
  getters : { ...getters },
  actions : { ...actions },
});
export default useUsuariosStore;