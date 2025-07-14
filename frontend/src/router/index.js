import { createRouter, createWebHistory } from 'vue-router';
import { useConfigStore } from "@/stores/config";
import JwtService from "@/core/services/JwtService";
import useAuthStore from "@/stores/auth/authStore";

// Definición de las rutas de la aplicación
const routes = [
  // Ruta principal con layout protegido
  {
    path      : '/',
    name      : 'Home',
    component : () => import("@/layouts/gestion/DefaultLayoutGestion.vue"),
    redirect  : '/bandeja',
    children  : [
      {
        path      : 'bandeja',
        name      : 'Bandeja',
        component : () => import("@/views/Bandeja/Bandeja.vue"),
        // .catch(() =>
        //   import("@/views/SystemError/Error500.vue")
        // ),
        meta      : {
          requiresAuth : true,
          pageTitle    : "Bandeja de Entrada",
          breadcrumbs  : ["Bandeja"],
        },
      },
      {
        path      : 'enviados',
        name      : 'Enviados',
        component : () => import("@/views/Enviados/Enviados.vue"),
        meta      : {
          requiresAuth : true,
          pageTitle    : "Bandeja de Enviados",
          breadcrumbs  : ["Enviados"],
        },
      },
      {
        path      : 'dashboard',
        name      : 'Dashboard',
        component : () => import("@/views/Dashboard.vue"),
        meta      : {
          requiresAuth : true,
          pageTitle    : "Dashboard",
          breadcrumbs  : ["Dashboards"],
        },
      },
      
    ],
  },
  //ruta de prueba para el editor de texto


  // Layout para páginas de sistema/error
  {
    path      : '/system',
    component : () => import("@/layouts/SystemLayout.vue"),
    children  : [
      {
        path      : '404',
        name      : '404',
        component : () => import("@/views/SystemError/Error404.vue"),
        meta      : {
          pageTitle : "Error 404",
        },
      },
      {
        path      : '500',
        name      : '500',
        component : () => import("@/views/SystemError/Error500.vue"),
        meta      : {
          pageTitle : "Error 500",
        },
      },
      {
        path      : '403',
        name      : '403',
        component : () => import("@/views/SystemError/Error403.vue"),
        meta      : {
          pageTitle : "Error 403",
        },
      },
    ],
  },

  // Ruta catch-all al final
  {
    path     : "/:pathMatch(.*)*",
    redirect : "/system/404",
  },
];

// Creación del router de Vue con historial HTML5
const router = createRouter({
  history : createWebHistory(),
  routes,
});

// Guard global para verificar autenticación antes de cada navegación
router.beforeEach((to, from, next) => {
  const userStore = useAuthStore();

  const isAuthenticated = JwtService.loggedIn();
  const configStore = useConfigStore();

  // Establecer título de página
  document.title = `${to.meta.pageTitle || ''} - ${import.meta.env.VITE_APP_NAME}`;

  // Resetear configuración del layout
  configStore.resetLayoutConfig();

  // Rutas que no requieren validación de permisos
  const publicRoutes = ['Home', '403', '404', '500', 'system', 'Correlativos', 'TiposDocumentos', 'TiposTramite', 'Plantillas'];

  const noRequierePermiso = publicRoutes.includes(to.name) || to.path.startsWith('/system');

  // Verificar autenticación para rutas protegidas
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      userStore.logout();
      return next({ path: '/system/403' });
    }
  }

  // Verificar permisos de usuario
  const permisosRuta = userStore.permisosRuta;
  const rutasPermitidas = permisosRuta.map(p => p.ruta);

  // Verificar acceso exacto o por inicio de ruta
  const tieneAcceso = rutasPermitidas.some(ruta => to.path.startsWith(ruta));
  
  if (!tieneAcceso && !noRequierePermiso) {
    next({ name: '403', replace: true });
    return;
  }

  next();
});

export default router;