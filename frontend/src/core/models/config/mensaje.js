//import { storeToRefs } from "pinia";

export default {
  alias      : 'mensajes',
  route      : '/mensajes',
  selectable : true,
  relations  : [
  
  ],

  methods : {
    
  },

  default : {
    id                     : null,
    asunto                 : null,
    contenido              : null,
    prioridad              : null,
    leido                  : false,
    casilla_origen_id      : null,
    casilla_destino_id     : null,
    casilla_destino_ids    : [],
    administrados_externos : [],
    created_at             : null,
    read_at                : null,
  }
}
