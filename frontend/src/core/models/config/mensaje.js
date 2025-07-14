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
    id                : null,
    asunto            : null,
    contenido         : null,
    prioridad         : null,
    leido             : false,
    usuario_origen_id : null,
    fecha_envio       : null,
    fecha_leido       : null,
  }
}
