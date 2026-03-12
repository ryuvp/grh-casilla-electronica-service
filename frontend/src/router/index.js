import { createRouter, createWebHistory } from 'vue-router';
import { useConfigStore } from "@/stores/config";
import JwtService from "@/core/services/JwtService";
import useAuthStore from "@/stores/auth/authStore";

// Definicion central de rutas de la aplicacion.
// Estructura: layout de gestion, rutas privadas y layout de errores del sistema.
const routes = [
  {
    path      : '/',
    name      : 'Home',
    component : () => import("@/layouts/gestion/DefaultLayoutGestion.vue"),
    redirect  : '/loading',
    children  : [
      {
        path      : 'bandeja',
        name      : 'Bandeja',
        component : () => import("@/views/Bandeja/Bandeja.vue"),
        meta      : { requiresAuth: true, pageTitle: "Bandeja de Entrada", breadcrumbs: ["Bandeja"] },
      },
      {
        path      : 'destacados',
        name      : 'Destacados',
        component : () => import("@/views/Destacados/Destacados.vue"),
        meta      : { requiresAuth: true, pageTitle: "Mensajes Destacados", breadcrumbs: ["Destacados"] },
      },
      {
        path      : 'archivados',
        name      : 'Archivados',
        component : () => import("@/views/Archivados/Archivados.vue"),
        meta      : { requiresAuth: true, pageTitle: "Mensajes Archivados", breadcrumbs: ["Archivados"] },
      },
      {
        path      : 'enviados',
        name      : 'Enviados',
        component : () => import("@/views/Enviados/Enviados.vue"),
        meta      : { requiresAuth: true, pageTitle: "Bandeja de Enviados", breadcrumbs: ["Enviados"] },
      },
      {
        path      : 'casillas',
        name      : 'Casilla',
        component : () => import("@/views/Casilla/Casilla.vue"),
        meta      : { requiresAuth: true, pageTitle: "Casilla", breadcrumbs: ["Casilla"] },
      },
      // Ruta publica de carga inicial mientras se valida contexto de sesion.
      {
        path      : '/loading',
        name      : 'Loading',
        component : () => import("@/views/SystemError/Loading.vue"),
        meta      : { pageTitle: "Cargando..." }
      },
    ],
  },

  // Layout del sistema para paginas de error controlado.
  {
    path      : '/system',
    component : () => import("@/layouts/SystemLayout.vue"),
    children  : [
      { path: '404', name: '404', component: () => import("@/views/SystemError/Error404.vue"), meta: { pageTitle: "Error 404" } },
      { path: '500', name: '500', component: () => import("@/views/SystemError/Error500.vue"), meta: { pageTitle: "Error 500" } },
      { path: '403', name: '403', component: () => import("@/views/SystemError/Error403.vue"), meta: { pageTitle: "Error 403" } },
    ],
  },

  // Fallback global para cualquier ruta inexistente.
  { path: "/:pathMatch(.*)*", redirect: "/system/404" },
];

// Instancia del router usando historial HTML5.
const router = createRouter({
  history : createWebHistory(),
  routes,
});

// Guard global: controla autenticacion, validacion de casilla y permisos por ruta.
router.beforeEach(async (to, from, next) => {
  // Obtiene stores necesarios para autenticacion/autorizacion y configuracion visual.
  const authStore = useAuthStore();
  const configStore = useConfigStore();

  // Verifica presencia de token para decidir acceso a rutas privadas.
  const hasToken = JwtService.haveToken();

  // Actualiza metadatos visuales por navegacion.
  document.title = `${to.meta.pageTitle || ''} - ${import.meta.env.VITE_APP_NAME}`;
  configStore.resetLayoutConfig();

  // Determina si la ruta actual es publica o pertenece al modulo de errores.
  const publicRoutes = ['Home', '403', '404', '500', 'system', 'Loading'];
  const noRequierePermiso = publicRoutes.includes(to.name) || to.path.startsWith('/system');

  // Solo se ejecuta en rutas privadas.
  if (to.matched.some(r => r.meta.requiresAuth)) {
    if (!hasToken) {
      // Sin token no se intenta logout remoto: solo se bloquea acceso.
      return next({ path: '/system/403' });
    }

    // Inicializa contexto de autenticacion solo una vez por sesion activa.
    if (!authStore.isAuthReady) {
      try { await authStore.validateToken(); }
      finally { authStore.setAuthReady(true); }
    }

    // Paso 1: validar que el usuario autenticado tenga casilla.
    // Si no la tiene, el store intenta cerrar la pagina y se corta la navegacion.
    const hasCasilla = await authStore.ensureUserHasCasilla();
    if (!hasCasilla) {
      return next(false);
    }
  }

  // Permisos por ruta (solo cuando corresponde)
  if (!noRequierePermiso && to.matched.some(r => r.meta.requiresAuth)) {
    // El acceso se concede si la ruta actual coincide con alguna ruta permitida.
    const rutasPermitidas = authStore.permisosRuta.map(p => p.ruta);
    const tieneAcceso = rutasPermitidas.some(ruta => to.path.startsWith(ruta));
    if (!tieneAcceso) {
      return next({ name: '403', replace: true });
    }
  }

  // Continua navegacion cuando pasa todas las validaciones.
  next();
});

export default router;
