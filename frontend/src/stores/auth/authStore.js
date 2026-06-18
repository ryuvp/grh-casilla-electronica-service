import { defineStore } from "pinia";
import JwtService from '@/core/services/JwtService';
import createApiService from "@/core/services/ApiService";

// Cliente para endpoints de autenticacion centralizados.
const ApiService = createApiService(import.meta.env.VITE_AUTH_API);
// Cliente para endpoints del servicio de casillas.
const ApiCasillaService = createApiService(import.meta.env.VITE_API_URL);
// Origen permitido para comunicacion segura con ventana padre.
const allowedOrigin = import.meta.env.VITE_AUTH_ORIGIN;

// Helper para eliminar permisos duplicados
const uniquePermissions = (permissions = []) => {
  const seen = new Set();

  return (permissions || []).filter((permission) => {
    const key = `${permission?.guard_name || ""}:${permission?.name || permission?.id || ""}`;

    if (seen.has(key)) return false;

    seen.add(key);
    return true;
  });
};

// Resuelve permisos canónicos desde designacion_logeada o userData
const getCanonicalPermissions = (state) => {
  const directPermissions =
    state.userData?.designacion_logeada?.permissions || state.userData?.permissions;

  if (Array.isArray(directPermissions) && directPermissions.length) {
    return uniquePermissions(directPermissions);
  }

  return uniquePermissions(
    (state.userData?.designacion_logeada?.roles || state.userData?.roles || []).flatMap(
      (role) => role.permissions || []
    )
  );
};

// Store de autenticacion/autorizacion y permisos del servicio actual.
const useAuthStore = defineStore('auth', {
  // Estado base de sesion y cache de validacion de casilla.
  state : () => ({
    isAuthenticated : JwtService.loggedIn() || false,
    userData        : JSON.parse(JwtService.getUserLogged()) || null,
    serviceName     : import.meta.env.VITE_SERVICE_NAME,
    isAuthReady     : false,
    hasCasilla      : null,
    casillaChecked  : false,
  }),

  getters : {
    // Indica si existe una sesion autenticada en memoria.
    isLoggedIn : state => state.isAuthenticated,

    // Retorna el perfil completo del usuario autenticado.
    currentUser : state => state.userData,

    // Retorna los roles asociados al usuario actual (desde designación logeada con fallback a directo).
    roles : state => state.userData?.designacion_logeada?.roles || state.userData?.roles || [],

    // Obtiene listado de permisos únicos
    permissions : state => getCanonicalPermissions(state),

    // Filtra permisos de tipo servicio para el modulo activo.
    permisosServicio : state =>
      getCanonicalPermissions(state).filter(p =>
        p.tipo_permiso === 1 && p.nombre_servicio === state.serviceName
      ) || [],

    // Filtra permisos de tipo menu para construir navegacion.
    permisosMenu : state =>
      getCanonicalPermissions(state).filter(p =>
        p.tipo_permiso === 4 && p.nombre_servicio === state.serviceName
      ) || [],

    // Filtra permisos de tipo ruta usados por el router guard.
    permisosRuta : state =>
      getCanonicalPermissions(state).filter(p =>
        p.tipo_permiso === 3 && p.nombre_servicio === state.serviceName
      ) || [],

    // Filtra permisos de tipo accion para botones y operaciones UI.
    permisosAccion : state =>
      getCanonicalPermissions(state).filter(p =>
        p.tipo_permiso === 2 && p.nombre_servicio === state.serviceName
      ) || [],

    // Determina si el perfil puede crear/enviar notificaciones.
    canWriteNotifications : state => {
      const roleNames = (state.userData?.designacion_logeada?.roles || state.userData?.roles || [])
        .map((rol) => String(rol?.name || rol?.nombre || rol?.descripcion || '').toLowerCase())

      if (roleNames.some((name) => name.includes('admin') || name.includes('notificador'))) {
        return true
      }

      const actionPermisos = getCanonicalPermissions(state).filter(
        (permiso) => permiso?.tipo_permiso === 2 && permiso?.nombre_servicio === state.serviceName
      );

      return actionPermisos.some((permiso) => {
        const text = String(permiso?.descripcion || permiso?.nombre || permiso?.ruta || '').toLowerCase()
        return (
          (text.includes('mensaje') || text.includes('notificacion')) &&
          (text.includes('crear') || text.includes('enviar') || text.includes('notificar'))
        )
      })
    },

    // Genera estructura del menu principal en base a permisos tipo menu.
    getMainMenuConfig(state) {
      const permisosMenu = getCanonicalPermissions(state).filter(p =>
        p.tipo_permiso === 4 && p.nombre_servicio === state.serviceName
      ) || [];
      
      // Permisos sin padre y con ruta se renderizan como accesos directos.
      const independientes = permisosMenu.filter(p => !p.permiso_padre_id && p.ruta);
      // Permisos sin padre y sin ruta son contenedores de submenu.
      const padres = permisosMenu.filter(p => !p.permiso_padre_id && !p.ruta);
      // Permisos con padre representan nodos hijos.
      const hijos = permisosMenu.filter(p => p.permiso_padre_id);

      // Construye nodos recursivos para menus anidados.
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

      // Acumula estructura final consumida por el layout.
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

      return menu;
    },

    // Genera estructura del menu de cabecera con arbol de permisos.
    getHeaderMenuConfig : (state) => {
      const permisosMenu = getCanonicalPermissions(state).filter(p =>
        p.tipo_permiso === 4 && p.nombre_servicio === state.serviceName
      ) || [];
    
      // Clasifica nodos para construir jerarquia padre-hijo.
      const independientes = permisosMenu.filter(p => !p.permiso_padre_id && p.ruta);
      const padresRaiz = permisosMenu.filter(p => !p.permiso_padre_id && !p.ruta);
      const hijos = permisosMenu.filter(p => p.permiso_padre_id);
    
      // Construye recursivamente submenus anidados.
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
    
      // Devuelve la configuracion final para el header menu.
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
          sub            : hijosDirectos.map(buildRecursiveMenu),
        });
      });
    
      return menu;
    }
  },

  actions : {
    // Cierra la pestana cuando falta casilla; si no es posible, navega a una pantalla en blanco.
    closePageByMissingCasilla() {
      try {
        window.open('', '_self');
        window.close();
      } catch (error) {
        console.warn('No se pudo cerrar la ventana automáticamente:', error);
      }

      setTimeout(() => {
        if (!window.closed) {
          window.location.replace('about:blank');
        }
      }, 150);
    },

    // Verifica existencia de casilla del usuario autenticado antes de permitir uso del modulo.
    async ensureUserHasCasilla() {
      // Evita consultas repetidas en la misma sesion.
      if (this.casillaChecked) {
        return true;
      }

      const designacionId = this.userData?.designacion_logeada?.id
        || this.userData?.designacion_logeada_id
        || this.userData?.designacion_id
        || this.userData?.designaciones_activas?.[0]?.id
        || this.userData?.designaciones?.[0]?.id;

      if (!designacionId) {
        console.warn("Sin designacion activa valida.");
        this.hasCasilla = false;
        this.casillaChecked = true;
        return true;
      }

      try {
        // Consulta minima: solo verifica existencia con una pagina de 1 elemento.
        const response = await ApiCasillaService.get('/casillas', {
          designacion_id : designacionId,
          per_page       : 1,
          page           : 1,
        });

        const casillas = response?.data?.data || [];
        const existeCasilla = Array.isArray(casillas) && casillas.length > 0;

        this.hasCasilla = existeCasilla;
        this.casillaChecked = true;

        if (!existeCasilla) {
          console.warn("El usuario no tiene una casilla activa en el sistema.");
        }

        return true;
      } catch (error) {
        console.error('Error validando casilla del usuario:', error);
        this.hasCasilla = false;
        this.casillaChecked = true;
        return true;
      }
    },

    // Valida token remoto y sincroniza datos de usuario en el store.
    async validateToken() {
      try {
        // 1) Obtiene el usuario autenticado desde el servicio de auth.
        const { data } = await ApiService.get('/usuario');
        JwtService.saveUserLogged(data);

        // 2) Actualiza estado local para habilitar rutas privadas.
        this.$patch({
          isAuthenticated : true,
          userData        : data
        });
      } catch (error) {
        console.error("Token inválido o expirado:", error);
        this.logout();
      }
    },

    // Ejecuta cierre de sesion remoto y limpia estado/token local.
    async logout() {
      try {
        await ApiService.post('/logout');
      } catch (err) {
        console.warn("Logout remoto fallido:", err);
      } finally {
        // Sincronizacion y limpieza de credenciales compartidas.
        localStorage.setItem("cerrar-hijas", Date.now().toString());
        JwtService.destroyToken();
        JwtService.destroyUserLogged();

        // Resetea estado de autenticacion y cache de casilla.
        this.$patch({
          isAuthenticated : false,
          userData        : null,
          hasCasilla      : null,
          casillaChecked  : false,
        });

        // Notifica a ventana padre para sincronizar logout entre contextos embebidos.
        if (window.opener && !window.opener.closed) {
          window.opener.postMessage({ type: "LOGOUT" }, allowedOrigin);
        }
      }
    },

    // Marca si el proceso inicial de autenticacion ya fue ejecutado en el ciclo actual.
    setAuthReady(status) {
      this.isAuthReady = status;
    },

    // Diagnostica diferencias entre permisos locales y permisos reportados por backend.
    async debugPermisos() {
      // Verifica permisos reales del backend para detectar desincronizacion.
      try {
        const token = JwtService.getToken();
        if (token) {
          // Consulta endpoint de permisos para comparacion tecnica.
          const response = await ApiService.get('/user-permissions');

          // Refresca permisos de accion si backend reporta mayor cobertura.
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
