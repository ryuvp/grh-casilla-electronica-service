<template>
  <div id="kt_app_content" class="app-content flex-column-fluid">
    <!--begin::Content container-->
    <div
      id="kt_app_content_container"
      class="app-container"
      :class="{
        'container-fluid': contentWidthFluid,
      }"
    >
      <RouterView></RouterView>
    </div>
    <!--end::Content container-->
  </div>
</template>

<script setup>
import { contentWidthFluid } from "@/layouts/default-layout/config/helper";
import { RouterView } from "vue-router";
</script>
