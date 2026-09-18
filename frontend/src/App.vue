<template>
  <RouterView />
  <ComunicadosOverlay ref="comunicadosOverlayRef" />
</template>

<script setup>
import { nextTick, onBeforeMount, onMounted, onUnmounted, ref, watch } from "vue";
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
const HANDSHAKE_TIMEOUT_MS = 3000;
let handshakeCompleted = false;
let bootstrapResuelto = false;
const esRecarga = () => performance.getEntriesByType?.('navigation')?.[0]?.type === 'reload';

// Valida el token guardado contra el backend (sin padre) o sale si no hay sesion.
const validarSesionGuardada = async () => {
  bootstrapResuelto = true;
  if (!JwtService.haveToken()) {
    salirPorSesionInvalida();
    return;
  }
  const result = await authStore.validateToken(true);
  if (result === true || result?.success) {
    await finalizarInicioAutenticado();
  } else {
    salirPorSesionInvalida();
  }
};

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

// Sesion cerrada en el auth: cerrar la ventana hija o, si no es posible, ir al login.
const salirPorSesionInvalida = () => {
  authStore.setAuthReady(true);
  if (window.opener && !window.opener.closed) {
    window.close();
  } else {
    window.location.replace(allowedOrigin + "/login");
  }
};

const handleMessage = async (event) => {
  if (event.origin !== allowedOrigin) return;

  // El auth cerro sesion: limpiar la sesion local (sin llamar al backend, el token ya esta revocado).
  if (event.data?.type === 'LOGOUT') {
    await authStore.logout(true);
    salirPorSesionInvalida();
    return;
  }

  if (event.data?.type === 'USER_UPDATED' && event.data.user) {
    JwtService.saveUserLogged(event.data.user);
    if (event.data.token) {
      JwtService.saveToken(event.data.token);
    }
    window.location.reload();
    return;
  }

  if (!event.data || event.data.type !== 'OPEN_SERVICE') return;

  // Evitar que el handshake se ejecute más de una vez;
  // el listener permanece activo para seguir recibiendo USER_UPDATED.
  if (handshakeCompleted) return;
  handshakeCompleted = true;

  const { token, user } = event.data;

  if (!token) {
    console.error("El mensaje OPEN_SERVICE no contiene un token valido.");
    return;
  }

  if (user) {
    JwtService.saveUserLogged(user);
    authStore.userData = user;
  }

  try {
    JwtService.saveToken(token);
    const result = await authStore.validateToken();
    const isValid = result === true || result?.success;

    if (!isValid) {
      authStore.setAuthReady(true);
      if (window.opener && !window.opener.closed) {
        window.close();
      } else {
        window.location.replace(allowedOrigin + "/login");
      }
      return;
    }

    await finalizarInicioAutenticado();
  } catch (error) {
    console.error("Error al iniciar servicio (handshake):", error);
    return;
  }

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

// Revalidacion en segundo plano con 401: la sesion fue cerrada/revocada. Si se espera el
// handshake del padre (primera apertura) se ignora, porque traera un token nuevo.
watch(() => authStore.sesionInvalidada, async (invalida) => {
  if (!invalida) return;
  authStore.sesionInvalidada = false;

  const esperandoPadre = !!window.opener && !window.opener.closed && !handshakeCompleted && !bootstrapResuelto;
  if (esperandoPadre) return;

  await authStore.logout(true);
  salirPorSesionInvalida();
});

onBeforeMount(() => {
  configStore.overrideLayoutConfig();
  themeStore.setThemeMode(themeConfigValue.value);
});

onMounted(() => {
  nextTick(async () => {
    const tokenExiste = JwtService.haveToken();
    const tieneOrigen = !!window.opener && !window.opener.closed;

    if (!tokenExiste && !tieneOrigen) {
      window.location.href = allowedOrigin + "/login";
      return;
    }

    if (tieneOrigen) {
      // Refresh con sesion guardada: validarla de inmediato en vez de esperar al padre
      // (si el auth ya cerro sesion, nadie responderia al handshake).
      if (tokenExiste && esRecarga()) {
        validarSesionGuardada();
      } else {
        // Primera apertura: se espera el handshake; si el padre no responde, se valida/sale.
        setTimeout(() => {
          if (handshakeCompleted || bootstrapResuelto) return;
          validarSesionGuardada();
        }, HANDSHAKE_TIMEOUT_MS);
      }
    } else if (tokenExiste) {
      try {
        const result = await authStore.validateToken();
        const isValid = result === true || result?.success;

        if (!isValid) {
          authStore.setAuthReady(true);
          if (window.opener && !window.opener.closed) {
            window.close();
          } else {
            window.location.replace(allowedOrigin + "/login");
          }
          return;
        }

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
