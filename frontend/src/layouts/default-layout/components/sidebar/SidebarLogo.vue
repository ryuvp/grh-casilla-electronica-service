<template>
  <!--begin::Logo-->
  <div
    id="kt_app_sidebar_logo"
    class="app-sidebar-logo px-6"
    style="align-items: center; justify-content: center;"
  >
    <!--begin::Logo image-->
    <router-link to="/">
      <img
        v-if="layout === 'dark-sidebar' || (themeMode === 'dark' && layout === 'light-sidebar')"
        alt="Logo"
        :src="getAssetPath('src/assets/img/logoDark.png')"
        class="h-45px app-sidebar-logo-default center"
      />
      <img
        v-if="themeMode === 'light' && layout === 'light-sidebar'"
        alt="Logo"
        :src="getAssetPath('src/assets/img/logoLight.png')"
        class="h-45px app-sidebar-logo-default center"
      />
      <img
        alt="Logo"
        :src="getAssetPath('src/assets/img/logomin.png')"
        class="h-35px app-sidebar-logo-minimize"
      />
    </router-link>
    <!--end::Logo image-->

    <!--begin::Sidebar toggle-->
    <div
      v-if="sidebarToggleDisplay"
      id="kt_app_sidebar_toggle"
      ref="toggleRef"
      class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
      data-kt-toggle="true"
      data-kt-toggle-state="active"
      data-kt-toggle-target="body"
      data-kt-toggle-name="app-sidebar-minimize"
    >
      <KTIcon icon-name="black-left-line" icon-class="fs-3 rotate-180 ms-1" />
    </div>
    <!--end::Sidebar toggle-->
  </div>
  <!--end::Logo-->
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import { ToggleComponent } from "@/assets/ts/components";
import { getAssetPath } from "@/core/helpers/assets";
import { layout, sidebarToggleDisplay, themeMode } from "@/layouts/default-layout/config/helper";

// Props (runtime)
const props = defineProps({
  sidebarRef : { type: Object, default: null }, // HTMLElement | null
});

const toggleRef = ref(null);

let toggleObj = null;
let onChangeHandler = null;

onMounted(async () => {
  await nextTick(); // Asegura que el DOM esté listo

  if (!toggleRef.value) return;

  // Metronic: obtiene la instancia si ya existe
  toggleObj = ToggleComponent.getInstance(toggleRef.value);

  if (!toggleObj) return;

  // Evita crear funciones anónimas sueltas: guardamos la referencia
  onChangeHandler = () => {
    // Añade clase para evitar hover mientras anima
    props.sidebarRef?.classList.add("animating");
    // Retira la clase luego de la animación (300ms por tu comentario)
    setTimeout(() => props.sidebarRef?.classList.remove("animating"), 300);
  };

  toggleObj.on("kt.toggle.change", onChangeHandler);
});

onBeforeUnmount(() => {
  // Limpieza del listener si la API lo soporta
  try {
    if (toggleObj && onChangeHandler && typeof toggleObj.off === "function") {
      toggleObj.off("kt.toggle.change", onChangeHandler);
    }
  } catch (error) {
    // no-op: algunas versiones no exponen .off
    console.warn("Error removing toggle change listener:", error);
  }
  toggleObj = null;
  onChangeHandler = null;
});
</script>
