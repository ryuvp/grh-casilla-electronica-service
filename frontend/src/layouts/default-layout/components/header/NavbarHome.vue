<template>
  <!--
    Navbar principal de la aplicación.
    Incluye búsqueda, actividades, notificaciones, chat, cambio de tema y menú de usuario.
  -->
  <div class="app-navbar flex-shrink-0">
    <!--begin::Search-->
    <div class="app-navbar-item align-items-stretch ms-1 ms-md-4">
      <KTSearch />
    </div>
    <!--end::Search-->
    <!--begin::Activities-->
    <div class="app-navbar-item ms-1 ms-md-4">
      <!--begin::Drawer toggle-->
      <div
        class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
        id="kt_activities_toggle"
      >
        <KTIcon icon-name="messages" icon-class="fs-2" />
      </div>
      <!--end::Drawer toggle-->
    </div>
    <!--end::Activities-->
    <!--begin::Notifications-->
    <div class="app-navbar-item ms-1 ms-md-4">
      <!--begin::Menu- wrapper-->
      <div
        class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
        data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
        data-kt-menu-attach="parent"
        data-kt-menu-placement="bottom-end"
        id="kt_menu_item_wow"
      >
        <KTIcon icon-name="notification-status" icon-class="fs-2" />
      </div>
      <KTNotificationMenu />
      <!--end::Menu wrapper-->
    </div>
    <!--end::Notifications-->
    <!--begin::Chat-->
    <div class="app-navbar-item ms-1 ms-md-4">
      <!--begin::Menu wrapper-->
      <div
        class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px position-relative"
        id="kt_drawer_chat_toggle"
      >
        <KTIcon icon-name="message-text-2" icon-class="fs-2" />
        <span
          class="bullet bullet-dot bg-success h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink"
        ></span>
      </div>
      <!--end::Menu wrapper-->
    </div>
    <!--end::Chat-->
    <!--begin::Theme mode-->
    <div class="app-navbar-item ms-1 ms-md-3">
      <!--begin::Menu toggle-->
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
      <!--begin::Menu toggle-->
      <KTThemeModeSwitcher />
    </div>
    <!--end::Theme mode-->
    <!--begin::User menu-->
    <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
      <!--
        Muestra el avatar del usuario en el navbar.
        Si el usuario tiene foto, la muestra.
        Si no, muestra la inicial del nombre con un color de fondo generado a partir del nombre/email.
      -->
      <div
        class="cursor-pointer symbol symbol-35px"
        data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
        data-kt-menu-attach="parent"
        data-kt-menu-placement="bottom-end"
      >
        <template v-if="user?.foto">
          <img
            :src="user.foto"
            class="rounded-3"
            alt="user"
            style="width: 35px; height: 35px;"
          />
        </template>
        <template v-else>
          <div
            class="symbol-label fs-4 text-white rounded"
            :style="{
              width: '35px',
              height: '35px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              backgroundColor: getColorFromString(user?.nombre || user?.email || '?')
            }"
          >
            {{ user?.nombre ? user.nombre.charAt(0).toUpperCase() : '?' }}
          </div>
        </template>
      </div>
      <!-- Menú desplegable de usuario -->
      <KTUserMenu />
      <!--end::Menu wrapper-->
    </div>
    <!--end::User menu-->
  </div>
  <!--end::Navbar-->
</template>

<script lang="ts">
// Store para el modo de tema (oscuro/claro/sistema)
import { getAssetPath } from "@/core/helpers/assets";
import { computed, defineComponent } from "vue";
import KTSearch from "@/layouts/default-layout/components/search/Search.vue";
import KTNotificationMenu from "@/layouts/default-layout/components/menus/NotificationsMenu.vue";
import KTUserMenu from "@/layouts/default-layout/components/menus/UserAccountMenu.vue";
import KTThemeModeSwitcher from "@/layouts/default-layout/components/theme-mode/ThemeModeSwitcher.vue";
import { ThemeModeComponent } from "@/assets/ts/layout";
import { useThemeStore } from "@/stores/theme";
import useAuthStore from "@/stores/auth/authStore";

export default defineComponent({
  name: "header-navbar",
  components: {
    KTSearch,
    KTNotificationMenu,
    KTUserMenu,
    KTThemeModeSwitcher,
  },
  setup() {
    // Store para el modo de tema (oscuro/claro/sistema)
    const store = useThemeStore();
    // Store de autenticación para obtener el usuario
    const authStore = useAuthStore();

    // Computed para el modo de tema actual
    const themeMode = computed(() => {
      if (store.mode === "system") {
        return ThemeModeComponent.getSystemMode();
      }
      return store.mode;
    });

    // Computed para obtener el usuario actual, ya sea del store o de localStorage
    const user = computed(() => {
      // Si el usuario está en el store y tiene nombre, lo retorna
      if (authStore.user && authStore.user.nombre) {
        return authStore.user;
      }
      // Si no, intenta obtenerlo de localStorage
      const savedUser = localStorage.getItem('user');
      if (savedUser) {
        try {
          return JSON.parse(savedUser);
        } catch {
          return null;
        }
      }
      return null;
    });

    /**
     * Genera un color HSL consistente a partir de un string (nombre/email).
     * Esto permite distinguir visualmente a los usuarios por su inicial.
     * @param str - Cadena base para el color
     * @returns Color HSL como string
     */
    function getColorFromString(str: string): string {
      if (!str) return "#888";
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      const h = Math.abs(hash) % 360;
      return `hsl(${h}, 70%, 50%)`;
    }

    // Exponer variables y funciones al template
    return {
      themeMode,
      getAssetPath,
      user,
      getColorFromString,
    };
  },
});
</script>
