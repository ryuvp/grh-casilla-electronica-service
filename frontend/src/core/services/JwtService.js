// services/JwtService.js
const ID_TOKEN_KEY = "token";
const USER_LOGGED = "userLogged";

// Decodifica el payload del JWT (sin verificar firma; solo para leer exp/iat en el cliente).
function decodePayload(token) {
  try {
    const part = String(token || "").split(".")[1];
    if (!part) return null;
    const b64 = part.replace(/-/g, "+").replace(/_/g, "/");
    return JSON.parse(atob(b64.padEnd(Math.ceil(b64.length / 4) * 4, "=")));
  } catch (e) {
    return null;
  }
}

// Ventana en la que un token recien emitido (login/handshake) se considera "fresco".
const FRESH_TOKEN_WINDOW_MS = 15000;

export default {
  getToken() {
    return localStorage.getItem(ID_TOKEN_KEY);
  },
  
  saveToken(token) {
    localStorage.setItem(ID_TOKEN_KEY, token);
  },
  
  destroyToken() {
    localStorage.removeItem(ID_TOKEN_KEY);
  },

  getUserLogged() {
    return localStorage.getItem(USER_LOGGED);
  },

  saveUserLogged(user) {
    localStorage.setItem(USER_LOGGED, JSON.stringify(user));
  },

  destroyUserLogged() {
    localStorage.removeItem(USER_LOGGED);
  },
  
  loggedIn() {
    const token = this.getToken();
    const user = this.getUserLogged();
    return !!token && !!user;
  },

  haveToken() {
    const token = this.getToken();
    return !!token;
  },

  // true solo si el token trae `exp` y ya paso; si no se puede leer, decide el servidor.
  isTokenExpired() {
    const exp = decodePayload(this.getToken())?.exp;
    return typeof exp === "number" && exp * 1000 <= Date.now();
  },

  // Elimina un token vencido (y el usuario asociado) antes de arrancar la app,
  // para no enviarlo al backend ni cerrar la sesion con un 401 innecesario.
  purgeIfExpired() {
    if (!this.haveToken() || !this.isTokenExpired()) return false;
    this.destroyToken();
    this.destroyUserLogged();
    return true;
  },

  // true si el token vigente fue emitido hace pocos segundos (`iat`).
  isRecentlyIssued() {
    const iat = decodePayload(this.getToken())?.iat;
    return typeof iat === "number" && Date.now() - iat * 1000 < FRESH_TOKEN_WINDOW_MS;
  },

  // Un 401 se reintenta una sola vez si el token es recien emitido y la peticion
  // fallida usaba justamente ese token (no uno anterior).
  shouldRetryUnauthorized(config) {
    if (!config || config._freshTokenRetry || !this.isRecentlyIssued()) return false;
    return String(config.headers?.Authorization || "") === `Bearer ${this.getToken()}`;
  }
};
