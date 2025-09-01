<template>
  <!--begin::Navbar-->
  <div class="app-navbar flex-shrink-0">
    <!--begin::Customize-->
    <div class="app-navbar-item ms-1 ms-md-4">
      <KTCustomize />
    </div>
    <!--end::Customize-->

    <!--begin::Theme mode-->
    <div class="app-navbar-item ms-1 ms-md-3">
      <a
        href="#"
        class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-30px h-30px w-md-40px h-md-40px"
        data-kt-menu-trigger="{default:'click', lg: 'hover'}"
        data-kt-menu-attach="parent"
        data-kt-menu-placement="bottom-end"
      >
        <KTIcon
          v-if="themeMode === 'light'"
          icon-name="night-day"
          icon-class="fs-2"
        />
        <KTIcon v-else icon-name="moon" icon-class="fs-2" />
      </a>
      <KTThemeModeSwitcher />
    </div>
    <!--end::Theme mode-->

    <!--begin::User menu-->
    <div id="kt_header_user_menu_toggle" class="app-navbar-item ms-1 ms-md-4">
      <div
        class="cursor-pointer symbol symbol-35px"
        data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
        data-kt-menu-attach="parent"
        data-kt-menu-placement="bottom-end"
      >
        <template v-if="user?.genero_nombre === 'Masculino'">
          <img :src="avatarHombre" class="rounded-3" alt="Usuario masculino" />
        </template>
        <template v-else-if="user?.genero_nombre === 'Femenino'">
          <img :src="avatarMujer" class="rounded-3" alt="Usuario femenino" />
        </template>
        <template v-else>
          <div
            class="symbol-label fs-6 text-white rounded-3 d-flex align-items-center justify-content-center"
            :style="{
              width: '35px',
              height: '35px',
              backgroundColor: getColorFromString(user?.nombre || user?.email || '?')
            }"
          >
            {{ user?.nombre?.charAt(0).toUpperCase() || '?' }}
          </div>
        </template>
      </div>
      <KTUserMenu />
    </div>
    <!--end::User menu-->

    <!--begin::Header menu toggle-->
    <div class="app-navbar-item d-lg-none ms-2 me-n2">
      <div
        id="kt_app_header_menu_toggle"
        class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px"
      >
        <KTIcon icon-name="element-4" icon-class="fs-2" />
      </div>
    </div>
    <!--end::Header menu toggle-->
  </div>
  <!--end::Navbar-->
</template>

<script setup>
// Imports
import { computed } from "vue";
import { useThemeStore } from "@/stores/theme";
import useAuthStore from "@/stores/auth/authStore";
import { ThemeModeComponent } from "@/assets/ts/layout";
import KTCustomize from "@/layouts/default-layout/components/extras/Customize.vue";
import KTUserMenu from "@/layouts/default-layout/components/menus/UserAccountMenu.vue";
import KTThemeModeSwitcher from "@/layouts/default-layout/components/theme-mode/ThemeModeSwitcher.vue";
import avatarHombre from "@/assets/img/avatar-hombre.png";
import avatarMujer from "@/assets/img/avatar-mujer.png";

// Stores
const themeStore = useThemeStore();
const authStore = useAuthStore();

// Modo de tema actual
const themeMode = computed(() =>
  themeStore.mode === "system"
    ? ThemeModeComponent.getSystemMode()
    : themeStore.mode
);

// Usuario actual
const user = computed(() => authStore.currentUser);

// Generar color desde texto
function getColorFromString(str) {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash);
  }
  const h = Math.abs(hash) % 360;
  return `hsl(${h}, 70%, 50%)`;
}
</script>