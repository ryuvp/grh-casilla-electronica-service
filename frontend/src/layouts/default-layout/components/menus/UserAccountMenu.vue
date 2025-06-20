<template>
  <!--
    Menú desplegable de usuario que muestra avatar, nombre, correo y nombre de usuario.
    Incluye enlaces para ver el perfil y cerrar sesión.
  -->
  <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold py-4 fs-6 w-275px"
    data-kt-menu="true">
    <div class="menu-item px-3">
      <div class="menu-content d-flex align-items-center px-3">
        <!--begin::Avatar-->
        <div class="symbol symbol-50px me-5">
          <!-- Imagen de avatar del usuario o inicial con color aleatorio -->
          <template v-if="user?.foto">
            <img alt="Logo" :src="user.foto" />
          </template>
          <template v-else>
            <div
              class="symbol-label fs-2 text-white rounded"
              :style="{
                width: '50px',
                height: '50px',
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
        <!--end::Avatar-->

        <!--begin::Username-->
        <div class="d-flex flex-column">
          <div class="fw-bold d-flex align-items-center fs-5">
            <!-- Nombre completo del usuario -->
            {{ userFullName }}
          </div>
          <div class="d-flex flex-column">
            <!-- Correo electrónico del usuario -->
            <span class="fw-semibold text-muted text-hover-primary fs-7">
              {{ user?.numero_documento }}
            </span>
            <!-- Nombre de usuario (si existe) -->
            <span class="text-muted fs-8" v-if="user?.nombre_usuario">
              {{ user?.nombre_usuario }}
            </span>
          </div>
        </div>
        <!--end::Username-->
      </div>
    </div>
    <div class="menu-item px-5">
      <!-- Enlace al perfil del usuario -->
      <a :href="perfilUrl" class="menu-link px-5">Mi Perfil</a>
    </div>
    <!--begin::Menu item-->
    <div
      class="menu-item px-5"
      data-kt-menu-trigger="hover"
      data-kt-menu-placement="left-start"
      data-kt-menu-flip="center, top"
    >
      <router-link to="/pages/profile/overview" class="menu-link px-5">
        <span class="menu-title position-relative">
          Idioma
          <span
            class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0"
          >
            {{ currentLangugeLocale.name }}
            <img
              class="w-15px h-15px rounded-1 ms-2"
              :src="currentLangugeLocale.flag"
              alt="metronic"
            />
          </span>
        </span>
      </router-link>

      <!--begin::Menu sub-->
      <div class="menu-sub menu-sub-dropdown w-175px py-4">

         <!--begin::Menu item-->
         <div class="menu-item px-3">
          <a
            @click="setLang('es')"
            href="#"
            class="menu-link d-flex px-5"
            :class="{ active: currentLanguage === 'es' }"
          >
            <span class="symbol symbol-20px me-4">
              <img
                class="rounded-1"
                :src="getAssetPath('media/flags/spain.svg')"
                alt="metronic"
              />
            </span>
            Español
          </a>
        </div>
        <!--end::Menu item-->

        <!--begin::Menu item-->
        <div class="menu-item px-3">
          <a
            @click="setLang('en')"
            href="#"
            class="menu-link d-flex px-5"
            :class="{ active: currentLanguage === 'en' }"
          >
            <span class="symbol symbol-20px me-4">
              <img
                class="rounded-1"
                :src="getAssetPath('media/flags/united-states.svg')"
                alt="metronic"
              />
            </span>
            Inglés
          </a>
        </div>
        <!--end::Menu item-->

      </div>
      <!--end::Menu sub-->
    </div>
    <!--end::Menu item-->
    <div class="menu-item px-5">
      <!-- Botón para cerrar sesión -->
      <a @click="signOut()" class="menu-link px-5"> Cerrar Sesión </a>
    </div>
  </div>
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { computed, defineComponent, ref, onMounted } from "vue";
import useAuthStore from "@/stores/auth/authStore";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";

export default defineComponent({
  name: "kt-user-menu",
  setup() {
    // Acceso al store de autenticación
    const store = useAuthStore();
    // Acceso al router de Vue
    const router = useRouter();

    // Estado local para almacenar el usuario (puede venir de localStorage)
    const localUser = ref(null);

    // Computed que retorna el usuario local si existe, si no, el del store
    const user = computed(() => localUser.value || store.currentUser);

    const perfilUrl = ref(import.meta.env.VITE_AUTH_ORIGIN + "/perfil");
    
    // Computed para mostrar el nombre completo del usuario
    const userFullName = computed(() => {
      const currentUser = user.value;
      if (!currentUser) return '';
      return `${currentUser.nombre} ${currentUser.apellido}`;
    });
    const i18n = useI18n();

    i18n.locale.value = localStorage.getItem("lang")
      ? (localStorage.getItem("lang") as string)
      : "en";

    const countries = {
      es: {
        flag: getAssetPath("media/flags/spain.svg"),
        name: "Español",
      },
      en: {
        flag: getAssetPath("media/flags/united-states.svg"),
        name: "Inglés",
      },
    };

    // Función para cerrar sesión
    const signOut = async () => {
      localUser.value = null;
      await store.logout();
      store.$patch({ userData: null });
      window.close();
    };

    // Al montar el componente, intenta recuperar el usuario desde localStorage

    /**
     * Genera un color HSL consistente a partir de un string (nombre/email).
     */
    function getColorFromString(str: string): string {
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      const h = Math.abs(hash) % 360;
      return `hsl(${h}, 70%, 50%)`;
    }

    const setLang = (lang: string) => {
      localStorage.setItem("lang", lang);
      i18n.locale.value = lang;
    };

    const currentLanguage = computed(() => {
      return i18n.locale.value;
    });

    const currentLangugeLocale = computed(() => {
      return countries[i18n.locale.value as keyof typeof countries];
    });

    // Exponer métodos y variables al template
    return {
      signOut,
      setLang,
      user,
      userFullName,
      getAssetPath,
      getColorFromString,
      currentLanguage,
      currentLangugeLocale,
      countries,
      perfilUrl,
    };
  },
});
</script>
