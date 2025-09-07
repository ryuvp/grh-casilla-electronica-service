import { createRouter, createWebHistory } from 'vue-router';
import { useConfigStore } from "@/stores/config";
import JwtService from "@/core/services/JwtService";
import useAuthStore from "@/stores/auth/authStore";

// Rutas
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
      // pública
      {
        path      : '/loading',
        name      : 'Loading',
        component : () => import("@/views/SystemError/Loading.vue"),
        meta      : { pageTitle: "Cargando..." }
      },
    ],
  },

  // Layout sistema
  {
    path      : '/system',
    component : () => import("@/layouts/SystemLayout.vue"),
    children  : [
      { path: '404', name: '404', component: () => import("@/views/SystemError/Error404.vue"), meta: { pageTitle: "Error 404" } },
      { path: '500', name: '500', component: () => import("@/views/SystemError/Error500.vue"), meta: { pageTitle: "Error 500" } },
      { path: '403', name: '403', component: () => import("@/views/SystemError/Error403.vue"), meta: { pageTitle: "Error 403" } },
    ],
  },

  { path: "/:pathMatch(.*)*", redirect: "/system/404" },
];

const router = createRouter({
  history : createWebHistory(),
  routes,
});

// Guard
router.beforeEach(async (to, from, next) => {
  const userStore = useAuthStore();
  const configStore = useConfigStore();

  const hasToken = JwtService.haveToken();

  // Título y layout
  document.title = `${to.meta.pageTitle || ''} - ${import.meta.env.VITE_APP_NAME}`;
  configStore.resetLayoutConfig();

  // Rutas públicas
  const publicRoutes = ['Home', '403', '404', '500', 'system', 'Loading'];
  const noRequierePermiso = publicRoutes.includes(to.name) || to.path.startsWith('/system');

  // Si requiere auth
  if (to.matched.some(r => r.meta.requiresAuth)) {
    if (!hasToken) {
      // No fuerces logout aquí; solo bloquea
      return next({ path: '/system/403' });
    }
    // Asegurar que el store esté listo antes de validar permisos
    if (!userStore.isAuthReady) {
      try { await userStore.validateToken(); }
      finally { userStore.setAuthReady(true); }
    }
  }

  // Permisos por ruta (solo cuando corresponde)
  if (!noRequierePermiso && to.matched.some(r => r.meta.requiresAuth)) {
    const rutasPermitidas = userStore.permisosRuta.map(p => p.ruta);
    const tieneAcceso = rutasPermitidas.some(ruta => to.path.startsWith(ruta));
    if (!tieneAcceso) {
      return next({ name: '403', replace: true });
    }
  }

  next();
});

export default router;
