<template> 
  <div class="main-container">
    <!-- Sección del Logo -->
    <div class="section logo-section">
      <img
        :src="getAssetPath('media/auth/logo_gorehco.png')"
        class="logo-image"
        alt="Logo Gorehco"
      />
    </div>

    <!-- Sección del Error 404 y texto -->
    <div class="section error-section">
      <h1 class="fw-bolder fs-2hx text-white mb-6">¡Error del sistema!</h1>
    
      <img
        :src="getAssetPath('media/auth/500er.webp')"
        class="error-image"
        alt="Error"
      />
      
      <div class="fw-semibold fs-5 text-white mb-2">
        <!-- Reemplazado el <center> por un div con clase text-center -->
        <div class="text-center">
          <h6>¡Algo salió mal!</h6>
          <p>Por favor intenta de nuevo más tarde.</p>
        </div>
      </div>
      
      <router-link to="/" class="btn btn-lg btn-primary mt-3">
        Volver al Inicio
      </router-link>
      
    </div>

    <!-- Sección de la Imagen Decorativa -->
    <div class="section image-section">
      <img
        :src="getAssetPath('media/auth/-min.png')"
        class="min-image"
        alt="Imagen Decorativa"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, onMounted } from "vue";
import LayoutService from "@/core/services/LayoutServiceHome";
import { useBodyStore } from "@/stores/body";
import { themeMode } from "@/layouts/default-layout/config/helper";


export default defineComponent({
  name       : "Error500",
  components : {},
  setup() {
    const storeBody = useBodyStore();

    // Ruta de la imagen de fondo
    const bgImage =
      themeMode.value !== "dark"
        ? getAssetPath("media/auth/fondo.jpg")
        : getAssetPath("media/auth/fondo.jpg");

    onMounted(() => {
      LayoutService.emptyElementClassesAndAttributes(document.body);

      storeBody.addBodyClassname("bg-body");
      storeBody.addBodyAttribute({
        qualifiedName : "style",
        value         : ` 
          background-image: url("${bgImage}");
          background-size: cover;
          background-position: center;
          background-repeat: no-repeat;
          min-height: 100vh;
        `,
      });
    });

    return {
      bgImage,
      getAssetPath,
    };
  },
});
</script>

<style scoped>
@import "@/assets/css/error-pages.css";
</style>
