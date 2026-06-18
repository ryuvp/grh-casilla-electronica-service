<template>
  <RouterView />
  <ComunicadosOverlay ref="comunicadosOverlayRef" />
</template>

<script setup>
import { nextTick, onBeforeMount, onMounted, onUnmounted, ref } from "vue";
import { RouterView, useRouter } from "vue-router";

import { useConfigStore } from "@/stores/config";
import { useThemeStore } from "@/stores/theme";
import { useBodyStore } from "@/stores/body";
import { themeConfigValue } from "@/layouts/default-layout/config/helper";
import { initializeComponents } from "@/core/plugins/keenthemes";

import useAuthStore from "@/stores/auth/authStore";
import JwtService from "@/core/services/JwtService";
import ComunicadosOverlay from "@/components/comunicados/ComunicadosOverlay.vue";

// Stores base para configuracion visual, tema, body y autenticacion.
const configStore = useConfigStore();
const themeStore = useThemeStore();
const bodyStore = useBodyStore();
const authStore = useAuthStore();
const comunicadosOverlayRef = ref(null);

// Router usado para redireccion inicial tras validar sesion.
const router = useRouter();

// Origen permitido para intercambio de mensajes con ventana padre.
const allowedOrigin = import.meta.env.VITE_AUTH_ORIGIN;

// Inicializa componentes visuales y remueve estado de carga inicial.
const init = () => {
  initializeComponents();
  bodyStore.removeBodyClassName("page-loading");
};

// Procesa handshake de autenticacion cuando el servicio abre como ventana hija.
const handleMessage = async (event) => {
  // Solo acepta mensajes del origen autorizado.
  if (event.origin !== allowedOrigin) return;

  // Solo procesa evento de apertura de servicio.
  if (!event.data || event.data.type !== 'OPEN_SERVICE') return;
  
  const { token } = event.data;

  // Sin token no se permite operar.
  if (!token) {
    console.error("El mensaje OPEN_SERVICE no contiene un token válido.");
    // window.close(); // Comentado para depuración
    return;
  }

  try {
    // Guarda token y luego hidrata store con usuario/roles desde auth service.
    JwtService.saveToken(token);
    await authStore.validateToken();
    authStore.setAuthReady(true);

    // Inicializa app y redirige desde loading a bandeja.
    init();
    await nextTick();
    await comunicadosOverlayRef.value?.cargar();

    if (router.currentRoute.value.path === '/loading') {
      router.replace('/bandeja');
    }
  } catch (error) {
    console.error("Error al iniciar servicio (handshake):", error);
    // window.close(); // Comentado para depuración: evita que la ventana se cierre ante errores
    return;
  }

  // El listener se desregistra tras completar handshake exitoso.
  window.removeEventListener("message", handleMessage);
};

// Cierra ventana hija cuando el contexto padre solicita cierre de sesion global.
const handleCerrarHijas = (event) => {
  if (event.key === 'cerrar-hijas') {
    authStore.logout();
    window.close();
    setTimeout(() => {
      if (!window.closed) {
        window.location.href = allowedOrigin + "/login";
      }
    }, 200);
    window.close();
  }
};

// Aplica configuracion de layout y tema antes del primer render.
onBeforeMount(() => {
  configStore.overrideLayoutConfig();
  themeStore.setThemeMode(themeConfigValue.value);
});

// Inicializa flujo de sesion al montar la app.
onMounted(() => {
  nextTick( async () => {
    // Detecta si existe token local o apertura desde ventana padre.
    const tokenExiste = JwtService.haveToken();
    const tieneOrigen = !!window.opener;

    // Si no hay token ni origen valido, redirige a login del auth service.
    if (!tokenExiste && !tieneOrigen) {
      window.location.href = allowedOrigin + "/login";
      return;
    }

    // Si recargaron con token: hidrata store antes de salir de /loading
    if (tokenExiste) {
      try {
        await authStore.validateToken();
      } finally {
        authStore.setAuthReady(true);
      }
      init();
      await nextTick();
      await comunicadosOverlayRef.value?.cargar();
      if (router.currentRoute.value.path === '/loading') {
        router.push('/bandeja');
      }
    }

    // Registra listeners de comunicacion entre ventana hija y padre.
    window.addEventListener("message", handleMessage);
    window.addEventListener("storage", handleCerrarHijas);

    // Notifica al padre que la ventana hija termino de cargar.
    if (window.opener && !window.opener.closed) {
      window.opener.postMessage(
        { type: "loaded", payload: { } },
        allowedOrigin
      );
    }
  });
});

// Limpia listeners globales al desmontar la aplicacion.
onUnmounted(() => {
  window.removeEventListener("message", handleMessage);
  window.removeEventListener("storage", handleCerrarHijas);
});
</script>

<style lang="scss">
@import "bootstrap-icons/font/bootstrap-icons.css";
@import "apexcharts/dist/apexcharts.css";
@import "quill/dist/quill.snow.css";
@import "animate.css";
@import "sweetalert2/dist/sweetalert2.css";
@import "nouislider/dist/nouislider.css";
@import "@fortawesome/fontawesome-free/css/all.min.css";
@import "socicon/css/socicon.css";
@import "line-awesome/dist/line-awesome/css/line-awesome.css";
@import "dropzone/dist/dropzone.css";
@import "@vueform/multiselect/themes/default.css";
@import "prism-themes/themes/prism-shades-of-purple.css";
@import "element-plus/dist/index.css";

@import "assets/keenicons/duotone/style.css";
@import "assets/keenicons/outline/style.css";
@import "assets/keenicons/solid/style.css";
@import "assets/sass/element-ui.dark";
@import "assets/sass/plugins";
@import "assets/sass/style";
@import "assets/css/estilos.css";

#app {
  display: contents;
}
</style>
