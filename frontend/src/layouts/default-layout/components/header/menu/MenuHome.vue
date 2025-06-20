<template>
  <!--begin::Menu wrapper-->
  <div
    v-if="headerMenuDisplay"
    class="app-header-menu app-header-mobile-drawer align-items-stretch"
    data-kt-drawer="true"
    data-kt-drawer-name="app-header-menu"
    data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true"
    data-kt-drawer-width="225px"
    data-kt-drawer-direction="end"
    data-kt-drawer-toggle="#kt_app_header_menu_toggle"
    data-kt-swapper="true"
    data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
    data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}"
  >
    <!--begin::Menu-->
    <div
      id="kt_app_header_menu"
      class="menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0"
      data-kt-menu="true"
    >
      <div
        data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
        data-kt-menu-placement="bottom-start"
        class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2"
      >
        <!--begin:Menu link-->
        <span class="menu-link">
          <span class="menu-title">
            Bienvenido al sistema
            <span v-if="userFullName">
              &nbsp;-&nbsp;{{ userFullName }}
            </span>
          </span>
          <span class="menu-arrow d-lg-none"></span>
        </span>
        <!--end:Menu link-->
      </div>
    </div>
    <!--end::Menu-->
  </div>
  <div v-else class="align-items-stretch"></div>
  <!--end::Menu wrapper-->
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, computed, onMounted, ref } from "vue";
import { version } from "@/core/helpers/system";
import { headerMenuDisplay } from "@/layouts/default-layout/config/helper";
import { useAuthStore } from "@/stores/authStore";

export default defineComponent({
  name : "HeaderMenu",
  setup() {
    // Referencia reactiva para el usuario
    const user = ref<any>(null);
    const store = useAuthStore();

    // Sincroniza el usuario desde localStorage o el store (igual que en Perfil.vue)
    onMounted(() => {
      const savedUser = localStorage.getItem("user");
      if (savedUser) {
        try {
          user.value = JSON.parse(savedUser);
        } catch {
          user.value = store.user;
        }
      } else {
        user.value = store.user;
      }
    });

    // Computed para mostrar el nombre completo del usuario si existe
    const userFullName = computed(() => {
      if (user.value && user.value.nombre && user.value.apellido) {
        return `${user.value.nombre} ${user.value.apellido}`;
      }
      if (user.value && user.value.nombre) {
        return user.value.nombre;
      }
      if (user.value && user.value.email) {
        return user.value.email;
      }
      return "";
    });

    return {
      version,
      headerMenuDisplay,
      getAssetPath,
      userFullName,
    };
  },
});
</script>
