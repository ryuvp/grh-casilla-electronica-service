<template>
  <RouterView />
</template>

<script setup>
import { nextTick, onBeforeMount, onMounted, onUnmounted } from "vue";
import { RouterView } from "vue-router";

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
const allowedOrigin = import.meta.env.VITE_AUTH_ORIGIN;

// Función para inicializar la app
const init = async () => {
  console.log("Initializing app...");

  await authStore.validateToken();
  console.log("Token validado correctamente", authStore.isAuthenticated);

  if (authStore.isAuthenticated) {
    console.log("User is authenticated, initializing components...");
    initializeComponents();
    bodyStore.removeBodyClassName("page-loading");
  } else {
    console.log("User is not authenticated.");
  }
};

// Manejar mensajes desde ventana padre (autenticación)
const handleMessage = async (event) => {
  console.log("Received message from parent window:", event.origin, event.data);
  
  if (event.origin !== allowedOrigin) {
    console.log("Message origin is not allowed, ignoring...");
    return;
  }
  
  if (!event.data || event.data.type !== 'OPEN_SERVICE') {
    console.log("Message data is invalid, ignoring...");
    return;
  }

  const { token } = event.data;
  if (!token) {
    console.log("No token found in message, closing window...");
    window.close();
    return;
  }

  try {
    console.log("Saving received token...");
    JwtService.saveToken(token);
    init();
  } catch (error) {
    console.error("Error saving the token:", error);
    window.close();
    return;
  }

  window.removeEventListener("message", handleMessage);
};

// Cierre remoto de ventanas hijas
const handleCerrarHijas = (event) => {
  console.log("Received event:", event);
  if (event.key === 'cerrar-hijas') {
    console.log("Closing child windows...");
    authStore.logout();
    window.close();
    setTimeout(() => {
      if (!window.closed) {
        console.log("Redirecting to login page...");
        window.location.href = import.meta.env.VITE_AUTH_ORIGIN + "/login";
      }
    }, 200);
    window.close();
  }
};

onBeforeMount(() => {
  console.log("Before mount: Overriding layout config and setting theme mode...");
  configStore.overrideLayoutConfig();
  themeStore.setThemeMode(themeConfigValue.value);
});

onMounted(() => {
  console.log("Component mounted, checking token and window state...");
  
  nextTick(() => {
    const tokenExiste = JwtService.haveToken();
    const tieneOrigen = !!window.opener;

    console.log("Token exists:", tokenExiste);
    console.log("Has opener window:", tieneOrigen);

    if (!tokenExiste && !tieneOrigen) {
      console.log("No token found, redirecting to login...");
      window.location.href = import.meta.env.VITE_AUTH_ORIGIN + "/login";
    }

    if (tokenExiste) {
      console.log("Token found, initializing...");
      init();
    }

    window.addEventListener("message", handleMessage);
    window.addEventListener("storage", handleCerrarHijas);
  });
});

onUnmounted(() => {
  console.log("Component unmounted, cleaning up event listeners...");
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
