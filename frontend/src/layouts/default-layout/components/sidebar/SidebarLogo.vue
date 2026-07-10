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
        alt="Logo dark"
        :src="logoDark"
        class="h-45px app-sidebar-logo-default center"
      />
      <img
        v-if="themeMode === 'light' && layout === 'light-sidebar'"
        alt="Logo light"
        :src="logoLight"
        class="h-45px app-sidebar-logo-default center"
      />
      <img
        alt="Logo minimized"
        :src="logoMin"
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
import { onMounted, ref } from "vue";
import { ToggleComponent } from "@/assets/ts/components";

// Importación directa de assets (garantiza carga correcta en dev y build)
import logoDark from "@/assets/img/logoDark.png";
import logoLight from "@/assets/img/logoLight.png";
import logoMin from "@/assets/img/logomin.png";

import {
  layout,
  sidebarToggleDisplay,
  themeMode,
} from "@/layouts/default-layout/config/helper";

const props = defineProps({
  sidebarRef : { type: Object, default: null },
});

const toggleRef = ref(null);

onMounted(() => {
  setTimeout(() => {
    const toggleObj = ToggleComponent.getInstance(toggleRef.value);
    if (!toggleObj) return;

    toggleObj.on("kt.toggle.change", function () {
      props.sidebarRef?.classList.add("animating");
      setTimeout(() => {
        props.sidebarRef?.classList.remove("animating");
      }, 300);
    });
  }, 1);
});
</script>
