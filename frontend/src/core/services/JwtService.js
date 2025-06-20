// services/JwtService.js
const ID_TOKEN_KEY = "token";
const USER_LOGGED = "userLogged";

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
  }

};