export default {
  alias      : 'casillas',
  route      : '/casillas',
  selectable : true,
  hash       : false,
  store      : false,
  paginate   : true,
  pagination : {
    current_page : 1,
    per_page     : 50
  },
  relations : [],
  methods   : {      
  },
  default : {
    id             : null,
    numero         : null,
    tipo           : 'interno',
    usuario_id     : null,
    designacion_id : null,
    dni            : null,
    persona_id     : null,
    nombre_externo : null,
    activo         : true,
    fecha_inicio   : null,
    fecha_fin      : null,
  }
}