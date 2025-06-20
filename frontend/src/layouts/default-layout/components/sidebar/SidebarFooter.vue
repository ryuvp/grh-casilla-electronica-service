<template>
  <!--begin::Footer-->
  <div
    id="kt_app_sidebar_footer"
    class="app-sidebar-footer flex-column-auto pt-2 pb-4 px-3"
  >
  </div>
  <!--end::Footer-->
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent } from "vue";

export default defineComponent({
  name       : "SidebarFooter",
  components : {},
  setup() {
    return {
      getAssetPath,
    };
  },
});
</script>
