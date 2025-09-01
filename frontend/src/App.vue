<template>
  <RouterView />
</template>

<script setup>
import { nextTick, onBeforeMount, onMounted, onUnmounted } from "vue";
import { RouterView, useRouter } from "vue-router";

import { useConfigStore } from "@/stores/config";
import { useThemeStore } from "@/stores/theme";
import { useBodyStore } from "@/stores/body";
import { themeConfigValue } from "@/layouts/default-layout/config/helper";
import { initializeComponents } from "@/core/plugins/keenthemes";

import useAuthStore from "@/stores/auth/authStore";
import JwtService from "@/core/services/JwtService";

// Stores y router
const configStore = useConfigStore();
const themeStore = useThemeStore();
const bodyStore = useBodyStore();
const authStore = useAuthStore();
//const usuariosStore = useUsuariosStore();
const router = useRouter();
const allowedOrigin = import.meta.env.VITE_AUTH_ORIGIN;

// Función para inicializar la app
const init = () => {
  authStore.validateToken();
  initializeComponents();
  bodyStore.removeBodyClassName("page-loading");
};

// Manejar mensajes desde ventana padre (autenticación)
const handleMessage = async (event) => {
  //console.log("Mensaje recibido:", event.origin, event.data);
  if (event.origin !== allowedOrigin) return;
  if (!event.data || event.data.type !== 'OPEN_SERVICE') return;
  
  const { token } = event.data;
  if (!token) {
    window.close();
    return;
  }

  try {
    JwtService.saveToken(token);
    init();
  } catch (error) {
    console.error("Error al guardar el token:", error);
    window.close();
    return;
  }

  window.removeEventListener("message", handleMessage);
};

// Cierre remoto de ventanas hijas
const handleCerrarHijas = (event) => {
  if (event.key === 'cerrar-hijas') {
    authStore.logout();
    window.close();
    setTimeout(() => {
      if (!window.closed) {
        router.push('/login');
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
  nextTick(() => {
    const tokenExiste = JwtService.haveToken();
    const tieneOrigen = !!window.opener;

    if (!tokenExiste && !tieneOrigen) {
      window.location.href = allowedOrigin + "/login";
    }

    if (tokenExiste) {
      init();
    }
    window.addEventListener("message", handleMessage);
    window.addEventListener("storage", handleCerrarHijas);
    if (window.opener && !window.opener.closed) {
      window.opener.postMessage(
        { type: "loaded", payload: { } },
        allowedOrigin // aqui va la ruta o dominio del padre
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