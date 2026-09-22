// Autoservicio de la casilla propia del usuario autenticado: nunca se le
// crea una casilla a nadie desde otro flujo (ver CasillaIdentityService en el
// backend). Este es el único camino de creación, a su propio pedido explícito.
export default {
  alias      : 'miCasilla',
  route      : '/mi-casilla',
  selectable : false,
  hash       : false,
  store      : false,
  paginate   : false,
  methods    : {
    estado() {
      return this.get('estado')
    },
    crear() {
      return this.create({})
    }
  },
  default : {}
}
