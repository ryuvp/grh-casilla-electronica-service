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

const configStore = useConfigStore();
const themeStore = useThemeStore();
const bodyStore = useBodyStore();
const authStore = useAuthStore();
const router = useRouter();
const comunicadosOverlayRef = ref(null);

const allowedOrigin = import.meta.env.VITE_AUTH_ORIGIN;

// Evita repetir comunicados al navegar dentro de Casilla en la misma carga de la app.
// Al recargar el navegador vuelve a false y permite recurrencias como cada_inicio_sesion.
const comunicadosCargadosEnNavegacion = ref(false);

const init = () => {
  initializeComponents();
  bodyStore.removeBodyClassName("page-loading");
};

async function irABandejaSiCorresponde() {
  if (router.currentRoute.value.path === '/loading') {
    await router.replace('/bandeja');
  }
}

async function cargarComunicadosSiCorresponde() {
  if (!authStore.isLoggedIn || comunicadosCargadosEnNavegacion.value) return;

  comunicadosCargadosEnNavegacion.value = true;
  await nextTick();
  await comunicadosOverlayRef.value?.cargar();
}

async function finalizarInicioAutenticado() {
  authStore.setAuthReady(true);
  init();
  await irABandejaSiCorresponde();
  await cargarComunicadosSiCorresponde();
}

const handleMessage = async (event) => {
  if (event.origin !== allowedOrigin) return;
  if (!event.data || event.data.type !== 'OPEN_SERVICE') return;

  const { token } = event.data;

  if (!token) {
    console.error("El mensaje OPEN_SERVICE no contiene un token valido.");
    return;
  }

  try {
    JwtService.saveToken(token);
    await authStore.validateToken();
    await finalizarInicioAutenticado();
  } catch (error) {
    console.error("Error al iniciar servicio (handshake):", error);
    return;
  }

  window.removeEventListener("message", handleMessage);
};

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

onBeforeMount(() => {
  configStore.overrideLayoutConfig();
  themeStore.setThemeMode(themeConfigValue.value);
});

onMounted(() => {
  nextTick(async () => {
    const tokenExiste = JwtService.haveToken();
    const tieneOrigen = !!window.opener;

    if (!tokenExiste && !tieneOrigen) {
      window.location.href = allowedOrigin + "/login";
      return;
    }

    if (tokenExiste) {
      try {
        await authStore.validateToken();
        await finalizarInicioAutenticado();
      } catch (error) {
        console.error("Error al validar sesion guardada:", error);
        authStore.setAuthReady(true);
      }
    }

    window.addEventListener("message", handleMessage);
    window.addEventListener("storage", handleCerrarHijas);

    if (window.opener && !window.opener.closed) {
      window.opener.postMessage(
        { type: "loaded", payload: { } },
        allowedOrigin
      );
    }
  });
});

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
