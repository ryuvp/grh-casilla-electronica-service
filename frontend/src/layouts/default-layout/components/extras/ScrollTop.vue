<template>
  <!-- begin::Scrolltop -->
  <div
    v-if="scrolltopDispaly"
    id="kt_scrolltop"
    ref="kt_scrolltop"
    class="scrolltop"
    data-kt-scrolltop="true"
  >
    <KTIcon icon-name="arrow-up" icon-class="fs-2x" />
  </div>
  <!-- end::Scrolltop -->
</template>

<script setup>
import { getAssetPath } from "@/core/helpers/assets";
import { onMounted } from "vue";
import { ScrollTopComponent } from "@/assets/ts/components/_ScrollTopComponent";
import { scrolltopDispaly } from "@/layouts/default-layout/config/helper";

onMounted(() => {
  ScrollTopComponent.reinitialization();
});
</script>
