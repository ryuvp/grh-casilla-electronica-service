<template>
  <!--begin::sidebar-->
  <div
    v-if="displaySidebar"
    id="kt_app_sidebar"
    ref="sidebarRef"
    class="app-sidebar flex-column"
    :class="sidebarColorClass"
    data-kt-drawer="true"
    data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true"
    data-kt-drawer-width="225px"
    data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"
  >
    <KTSidebarLogo :sidebar-ref="sidebarRef" />
    <KTSidebarMenu />
    <KTSidebarFooter />
  </div>
  <!--end::sidebar-->
</template>

<script setup>
import { ref, computed } from "vue";
import { displaySidebar, config, themeMode } from "@/layouts/default-layout/config/helper";
import KTSidebarLogo from "@/layouts/default-layout/components/sidebar/SidebarLogo.vue";
import KTSidebarMenu from "@/layouts/default-layout/components/sidebar/SidebarMenuGestion.vue";
import KTSidebarFooter from "@/layouts/default-layout/components/sidebar/SidebarFooter.vue";

const sidebarRef = ref(null);

const sidebarColorClass = computed(() => {
  const sidebarConfig = config.value.sidebar?.default || {};
  let color = sidebarConfig.bgColorLight || sidebarConfig.bgColorDark || "";

  if (!color) {
    const mode = themeMode.value;
    color = mode === "dark" ? sidebarConfig.bgColorDark : sidebarConfig.bgColorLight;
  }

  return color ? `sidebar-bg-${color}` : "";
});
</script>
