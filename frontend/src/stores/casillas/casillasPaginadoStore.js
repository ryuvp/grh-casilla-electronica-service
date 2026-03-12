import { defineStore } from "pinia";
import { Casilla } from '@/core/models/index.js';

// Obtiene estado/getters/actions base del modelo PMSG de Casilla.
const {state,getters,actions} = Casilla.getStore();

// Store de casillas con soporte de seleccion multiple para acciones UI.
const useCasillaStore = defineStore(Casilla.alias, {
  // Extiende estado base con coleccion de seleccionados.
  state : () => ({ 
    ...state,
    seleccionados : [],
  }),

  getters : {
    ...getters,

    // Indica si existe al menos una casilla seleccionada.
    tieneSeleccionados() { return this.seleccionados.length > 0; },
  },

  actions : { 
    ...actions,

    // Reemplaza seleccion actual por la lista enviada desde la tabla.
    setSeleccionados(items = []) { this.seleccionados = [...items]; },

    // Limpia todos los elementos seleccionados.
    limpiarSeleccion() { this.seleccionados = []; },

    // Alterna seleccion de una casilla (agrega o quita segun estado previo).
    toggleSeleccionado(item) {
      const i = this.seleccionados.findIndex(x => x.id === item.id);
      if (i >= 0) this.seleccionados.splice(i, 1);
      else this.seleccionados.push(item);
    }
  },
})

export default useCasillaStore;