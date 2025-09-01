import { defineStore } from "pinia";
import JwtService from '@/core/services/JwtService';
import createApiService from "@/core/services/ApiService";

const ApiService = createApiService(import.meta.env.VITE_AUTH_API);
const allowedOrigin = import.meta.env.VITE_AUTH_ORIGIN;

const useAuthStore = defineStore('auth', {
  state : () => ({
    isAuthenticated : JwtService.loggedIn() || false,
    userData        : JSON.parse(JwtService.getUserLogged()) || null,
    serviceName     : import.meta.env.VITE_SERVICE_NAME,
    isAuthReady     : false,
  }),

  getters : {
    isLoggedIn  : state => state.isAuthenticated,
    currentUser : state => state.userData,
    roles       : state => state.userData?.roles || [],

    permisosServicio : state =>
      state.roles.flatMap(rol =>
        rol.permissions.filter(p =>
          p.tipo_permiso === 1 && p.nombre_servicio === state.serviceName
        )
      ) || [],

    permisosMenu : state =>
      state.roles.flatMap(rol =>
        rol.permissions.filter(p =>
          p.tipo_permiso === 4 && p.nombre_servicio === state.serviceName
        )
      ) || [],

    permisosRuta : state =>
      state.roles.flatMap(rol =>
        rol.permissions.filter(p =>
          p.tipo_permiso === 3 && p.nombre_servicio === state.serviceName
        )
      ) || [],

    permisosAccion : state =>
      state.roles.flatMap(rol =>
        rol.permissions.filter(p =>
          p.tipo_permiso === 2 && p.nombre_servicio === state.serviceName
        )
      ) || [],

    getMainMenuConfig(state) {
      const permisosMenu = state.roles.flatMap(rol =>
        rol.permissions.filter(p =>
          p.tipo_permiso === 4 && p.nombre_servicio === state.serviceName
        )
      ) || [];
      console.log("Generating main menu config for service:", permisosMenu);
      
      const independientes = permisosMenu.filter(p => !p.permiso_padre_id && p.ruta);
      const padres = permisosMenu.filter(p => !p.permiso_padre_id && !p.ruta);
      const hijos = permisosMenu.filter(p => p.permiso_padre_id);

      const buildRecursive = (padre) => {
        const children = hijos.filter(p => p.permiso_padre_id === padre.id);
        return {
          heading        : padre.descripcion,
          route          : padre.ruta || "#",
          keenthemesIcon : "element-7",
          bootstrapIcon  : padre.icon || "bi-folder",
          ...(children.length ? { sub: children.map(buildRecursive) } : {}),
        };
      };

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

      padres.forEach(padre => {
        const hijosDirectos = hijos.filter(p => p.permiso_padre_id === padre.id);
        if (!hijosDirectos.length) return;

        menu.push({
          heading        : padre.descripcion,
          route          : padre.ruta || undefined,
          keenthemesIcon : "element-7",
          bootstrapIcon  : padre.icon || "bi-folder",
          pages          : hijosDirectos.map(buildRecursive),
        });
      });
      console.log("Generated menu:", menu)
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
    async validateToken() {
      try {
        // 1. Obtener datos de usuario
        const { data } = await ApiService.get('/usuario');
        JwtService.saveUserLogged(data);
        // 2. Obtener dependencia principal usando getOficinas
        let userUnidadOrganica = null;
        try {
          // Importar dinámicamente el store de usuarios para evitar ciclos
          const { default: useUsuariosStore } = await import('@/stores/usuarios/usuariosStore');
          const usuariosStore = useUsuariosStore();
          await usuariosStore.getOficinas(data.id);
          // Tomamos la dependencia principal (ajusta según la estructura real)
          if (usuariosStore.oficina && usuariosStore.oficina.dependencia && usuariosStore.oficina.dependencia.id) {
            userUnidadOrganica = usuariosStore.oficina.dependencia.id;
          } else if (Array.isArray(usuariosStore.oficina) && usuariosStore.oficina.length > 0 && usuariosStore.oficina[0].dependencia && usuariosStore.oficina[0].dependencia.id) {
            userUnidadOrganica = usuariosStore.oficina[0].dependencia.id;
          }
        } catch (e) {
          console.warn('No se pudo obtener la dependencia principal del usuario:', e);
        }
        this.$patch({
          isAuthenticated    : true,
          userData           : data,
          userUnidadOrganica : userUnidadOrganica,
        });
      } catch (error) {
        console.error("Token inválido o expirado:", error);
        this.logout();
      }
    },

    async logout() {
      try {
        await ApiService.post('/logout');
      } catch (err) {
        console.warn("Logout remoto fallido:", err);
      } finally {
        // Sincronización y limpieza
        localStorage.setItem("cerrar-hijas", Date.now().toString());
        JwtService.destroyToken();
        JwtService.destroyUserLogged();
        //sessionStorage.clear();
        //localStorage.clear();

        this.$patch({
          isAuthenticated : false,
          userData        : null,
        });

        // Limpiar cookies
        //document.cookie.split(";").forEach(c => {
        //  document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        //});

        // Notificar al padre si existe
        if (window.opener && !window.opener.closed) {
          window.opener.postMessage({ type: "LOGOUT" }, allowedOrigin);
        }

        /* window.close();
        setTimeout(() => {
          if (!window.closed) window.location.href = "/login";
        }, 200); */
      }
    },

    setAuthReady(status) {
      this.isAuthReady = status;
    },
    // AGREGAR: Método para debugging de permisos
    async debugPermisos() {
      
      // Verificar si hay más permisos en el backend
      try {
        const token = JwtService.getToken();
        if (token) {
          
          // Llamada directa al endpoint de permisos
          const response = await ApiService.get('/user-permissions');
          
          // Actualizar permisos si hay más de los que ya tenemos
          if (response.data.length > (this.permisosAccion?.length || 0)) {
            this.permisosAccion = response.data;
          }

        }
      } catch (error) {
        console.error('❌ Error verificando permisos en backend:', error);
      }
    },
  },
});

export default useAuthStore;
