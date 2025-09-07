import { defineStore } from "pinia";
import { Casilla } from '@/core/models/index.js';

const {state,getters,actions} = Casilla.getStore();
//const baseGet = actions.get; // guarda referencia

const useCasillaStore = defineStore(Casilla.alias, {
  state : () => ({ 
    ...state,
    seleccionados : [],
  }),
  getters : {
    ...getters,
    tieneSeleccionados() { return this.seleccionados.length > 0; },
  },
  actions : { 
    ...actions,

    // ✅ Mantén la firma: (pathParams = {}, query = {})
    /* async get(pathParams = {}, query = {}) {
      const defaultWith = ['dependencia', 'role'];

      const withVal = Array.isArray(query.with)
        ? [...new Set([...query.with, ...defaultWith])]
        : defaultWith;

      const finalQuery = { ...query, with: withVal };

      // llama al get original con el contexto del store
      return baseGet.call(this, pathParams, finalQuery);
    }, */

    setSeleccionados(items = []) { this.seleccionados = [...items]; },
    limpiarSeleccion() { this.seleccionados = []; },
    toggleSeleccionado(item) {
      const i = this.seleccionados.findIndex(x => x.id === item.id);
      if (i >= 0) this.seleccionados.splice(i, 1);
      else this.seleccionados.push(item);
    }
  },
})

export default useCasillaStore;