<template>
  <RouterView />
</template>

<script lang="ts">
import { defineComponent, nextTick, onBeforeMount, onMounted } from "vue";
import { RouterView } from "vue-router";
import { useConfigStore } from "@/stores/config";
import { useThemeStore } from "@/stores/theme";
import { useBodyStore } from "@/stores/body";
import { themeConfigValue } from "@/layouts/default-layout/config/helper";
import { initializeComponents } from "@/core/plugins/keenthemes";
import useAuthStore from "@/stores/auth/authStore";
import JwtService from "@/core/services/JwtService";


export default defineComponent({
  name       : "App",
  components : {
    RouterView,
  },
  setup() {
    const configStore = useConfigStore();
    const themeStore = useThemeStore();
    const bodyStore = useBodyStore();
    const authStore = useAuthStore();
    const allowedOrigin = import.meta.env.VITE_AUTH_ORIGIN;


    onBeforeMount(() => {
      /**
       * Overrides the layout config using saved data from localStorage
       * remove this to use static config (@/layouts/default-layout/config/DefaultLayoutConfig.ts)
       */
      configStore.overrideLayoutConfig();

      /**
       *  Sets a mode from configuration
       */
      themeStore.setThemeMode(themeConfigValue.value);
    });

    onMounted(() => {
      nextTick(() => {
        const tokenExiste = JwtService.haveToken();
        const tieneOrigen = !!window.opener;

        if (!tokenExiste && !tieneOrigen) {
          window.location.href = import.meta.env.VITE_AUTH_ORIGIN + "/login";
        }
        if (tokenExiste) {
          init();
        }

        // ✅ Registrar handler antes de esperar mensajes
        window.addEventListener("message", handleMessage);

      });
    });
    const init = () => {      
      authStore.validateToken();      
      initializeComponents();
      bodyStore.removeBodyClassName("page-loading");
    };
    // ✅ Declarar handler por separado para poder removerlo luego
    const handleMessage = async (event) => {
      if (event.origin !== allowedOrigin) {
        return;
      }

      if (!event.data || event.data.type !== 'OPEN_SERVICE') {
        return;
      }

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
  },
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

// Main demo style scss
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
