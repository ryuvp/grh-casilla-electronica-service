<template>
  <template v-for="(item, i) in authStore.getHeaderMenuConfig" :key="i">
    <RecursiveMenuItem :item="item" :is-root="true" />
  </template>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import RecursiveMenuItem from "./RecursiveMenuItem.vue";
import useAuthStore from "@/stores/auth/authStore";

export default defineComponent({
  name       : "KTMenu",
  components : { RecursiveMenuItem },
  setup() {
    const { t, te } = useI18n();
    const route = useRoute();
    const authStore = useAuthStore();


    return {
      authStore,
    };
  },
});
</script>
