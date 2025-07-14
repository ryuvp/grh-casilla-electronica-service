// stores//auth/authStore.js
import { defineStore } from "pinia";
import JwtService from '@/core/services/JwtService';
import createApiService from "@/core/services/ApiService";
const ApiService = createApiService(import.meta.env.VITE_AUTH_API);
// const AUTH_API = import.meta.env.VITE_AUTH_ORIGIN;

const useAuthStore = defineStore('auth', {
  state : () => ({
    isAuthenticated : JwtService.loggedIn() || false,
    userData        : JSON.parse(JwtService.getUserLogged()) || null,
    serviceName     : import.meta.env.VITE_SERVICE_NAME || 'sgd',
    isAuthReady     : false,
  }),
  getters : {
    isLoggedIn       : (state) => state.isAuthenticated,
    currentUser      : (state) => state.userData,
    roles            : (state) => state.userData?.roles || [],
    permisosServicio : (state) => {
      if(!state.userData) return [];
      console.log('permisos servicio', state.userData.roles);
      return state.userData.roles?.flatMap((rol) => 
        rol.permissions.filter((permiso) => permiso.tipo_permiso === 1 && permiso.nombre_servicio === state.serviceName)) || [];
    },
    permisosMenu : (state) => {
      if(!state.userData) return [];
      return state.userData.roles?.flatMap((rol) => 
        rol.permissions.filter((permiso) => permiso.tipo_permiso === 4 && permiso.nombre_servicio === state.serviceName)) || [];
    },
    permisosRuta : (state) => {
      if(!state.userData) return [];
      return state.userData.roles?.flatMap((rol) => 
        rol.permissions.filter((permiso) => permiso.tipo_permiso === 3 && permiso.nombre_servicio === state.serviceName)) || [];
    },
    permisosAccion : (state) => {
      if(!state.userData) return [];
      return state.userData.roles?.flatMap((rol) => 
        rol.permissions.filter((permiso) => permiso.tipo_permiso === 2 && permiso.nombre_servicio === state.serviceName)) || [];
    },
    getMainMenuConfig : (state) => {
      const permisos = state.userData?.roles
        ?.flatMap((rol) =>
          rol.permissions.filter((permiso) => permiso.nombre_servicio === state.serviceName)
        ) || [];
    
      // Filtrar por tipo de permiso menú
      const permisosMenu = permisos.filter(p => p.tipo_permiso === 4);
    
      const independientes = permisosMenu.filter(p => !p.permiso_padre_id && p.ruta);
      const padresRaiz = permisosMenu.filter(p => !p.permiso_padre_id && !p.ruta);
      const hijos = permisosMenu.filter(p => p.permiso_padre_id);
    
      function buildRecursiveMenu(hijo) {
        const subHijos = hijos.filter(p => p.permiso_padre_id === hijo.id);
        return {
          heading        : hijo.descripcion,
          route          : hijo.ruta || "#",
          keenthemesIcon : "element-7",
          bootstrapIcon  : hijo.icon || "bi-folder",
          ...(subHijos.length ? { sub: subHijos.map(buildRecursiveMenu) } : {}),
        };
      }
    
      const menu = [];
    
      if (independientes.length) {
        menu.push({
          pages : independientes.map(p => ({
            heading        : p.descripcion,
            route          : p.ruta,
            keenthemesIcon : "element-7",
            bootstrapIcon  : p.icon || "bi-folder",
          })),
        });
      }
    
      padresRaiz.forEach(padre => {
        const hijosDirectos = permisosMenu.filter(p => p.permiso_padre_id === padre.id);
        if (!hijosDirectos.length) return;
    
        menu.push({
          heading        : padre.descripcion,
          route          : padre.ruta || undefined,
          keenthemesIcon : "element-7",
          bootstrapIcon  : padre.icon || "bi-folder",
          pages          : hijosDirectos.map(buildRecursiveMenu),
        });
      });
    
      return menu;
    },
    getHeaderMenuConfig : (state) => {
      const permisos = state.userData?.roles
        ?.flatMap((rol) =>
          rol.permissions.filter((permiso) => permiso.nombre_servicio === state.serviceName)
        ) || [];
    
      const permisosMenu = permisos.filter(p => p.tipo_permiso === 4);
    
      const independientes = permisosMenu.filter(p => !p.permiso_padre_id && p.ruta);
      const padresRaiz = permisosMenu.filter(p => !p.permiso_padre_id && !p.ruta);
      const hijos = permisosMenu.filter(p => p.permiso_padre_id);
    
      function buildRecursiveMenu(hijo) {
        const subHijos = hijos.filter(p => p.permiso_padre_id === hijo.id);
        return {
          heading        : hijo.descripcion,
          route          : hijo.ruta || "#",
          keenthemesIcon : "element-7",
          bootstrapIcon  : hijo.icon || "bi-folder",
          ...(subHijos.length ? { sub: subHijos.map(buildRecursiveMenu) } : {}),
        };
      }
    
      const menu = [];
    
      if (independientes.length) {
        menu.push({
          pages : independientes.map(p => ({
            heading        : p.descripcion,
            route          : p.ruta,
            keenthemesIcon : "element-7",
            bootstrapIcon  : p.icon || "bi-folder",
          })),
        });
      }
    
      padresRaiz.forEach(padre => {
        const hijosDirectos = permisosMenu.filter(p => p.permiso_padre_id === padre.id);
        if (!hijosDirectos.length) return;
    
        menu.push({
          heading        : padre.descripcion,
          route          : padre.ruta || "#",
          keenthemesIcon : "element-7",
          bootstrapIcon  : padre.icon || "bi-folder",
          sub            : hijosDirectos.map(buildRecursiveMenu), // <--- aquí usamos `sub`
        });
      });
    
      return menu;
    }   
  },
  actions : {
    async logout() {
      // try {
      //   const token = JwtService.getToken();
      //   if (token) {
      //     await ApiService.post('/logout', {}, {
      //       headers : { Authorization: `Bearer ${token}` }
      //     });
      //   }
      // } catch (error) {
      //   console.error('Error during logout:', error);
      // } finally {
      //   // Limpiar todo
      //   JwtService.destroyToken();
      //   JwtService.destroyUserLogged();       
      //   // Limpiar el estado
      //   this.$patch({
      //     isAuthenticated : false,
      //     userData        : null
      //   });
        
      //   localStorage.clear();
      //   sessionStorage.clear();
        
      //   // Asegurar que se borren todas las cookies de sesión
      //   document.cookie.split(";").forEach(function(c) {
      //     document.cookie = c
      //       .replace(/^ +/, "")
      //       .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
      //   });
      //   //funcion para enviar msj al padre para cerrar su sesion
      //   if (window.opener && !window.opener.closed) {
      //     window.opener.postMessage({ type: 'LOGOUT' }, import.meta.env.VITE_AUTH_ORIGIN);
      //   }
      //   // Redirigir al login de la pagina de origen
      //   //window.location.href = import.meta.env.VITE_AUTH_ORIGIN + '/login';


      // }
    },
    setAuthReady(status) {
      this.isAuthReady = status;
      console.log('isAuthReady actualizado a:', this.isAuthReady)
    },
    async validateToken() {
      try {
        const response = await ApiService.get('/usuario');
        this.userData = response.data;
        JwtService.saveUserLogged(this.userData);
      } catch (error) {
        console.error('Error fetching user data:', error);
        //this.logout(); // Si falla, cerramos sesión
      }
    },
  },
}
)
export default useAuthStore;