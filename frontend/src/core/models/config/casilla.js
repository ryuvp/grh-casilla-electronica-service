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
    id           : null,
    numero       : null,
    titular_tipo : null,
    titular_id   : null,
    activo       : true,
    fecha_inicio : null,
    fecha_fin    : null,
  }
}