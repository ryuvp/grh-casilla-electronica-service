//import { storeToRefs } from "pinia";

export default {
  alias      : 'usuarios',
  route      : '/usuarios',
  selectable : true,
  relations  : [
  
  ],

  methods : {
    
  },

  default : {
    id               : null,
    nombre           : null,
    apellido         : null,
    tipo_documento   : null,
    numero_documento : null,
    telefono         : null,
    direccion        : null,
    email            : null,
    fecha_nacimiento : null,
    genero           : null,
    ubigeo_id        : null,
    tipo_usuario     : null,
    password         : null,
    imagen           : null,
    roles            : [],
    ubigeo           : null,
  }
}
