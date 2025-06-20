<template>
  <!--begin::Footer-->
  <div v-if="footerDisplay" id="kt_app_footer" class="app-footer">
    <!--begin::Footer container-->
    <div
      class="app-container d-flex flex-column justify-content-center align-items-center py-3 container-fluid"
      :class="{
        'container-fluid': footerWidthFluid,
      }"
      style="min-height: 60px;"
    >
      <!--begin::Copyright-->
      <div class="text-gray-900 text-center w-100">
        <span class="text-muted fw-semibold me-1">2025©</span>
        <a
          href=""
          target="_blank"
          class="text-gray-800 text-hover-primary"
          >Gorehco</a
        >
      </div>
      <!--end::Copyright-->
    </div>
    <!--end::Footer container-->
  </div>
  <!--end::Footer-->
</template>

<script lang="ts">
import { defineComponent } from "vue";
import {
  footerDisplay,
  footerWidthFluid,
} from "@/layouts/default-layout/config/helper";

export default defineComponent({
  name: "theme-footer",
  components: {},
  setup() {
    return {
      footerWidthFluid,
      footerDisplay,
    };
  },
});
</script>
