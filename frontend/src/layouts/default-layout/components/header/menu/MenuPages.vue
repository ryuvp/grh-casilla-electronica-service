<template>
  <template v-for="(item, i) in authStore.getHeaderMenuConfig" :key="i">
    <RecursiveMenuItem :item="item" :is-root="true" />
  </template>
</template>

<script setup>
import RecursiveMenuItem from "./RecursiveMenuItem.vue";
import useAuthStore from "@/stores/auth/authStore";

const authStore = useAuthStore();
</script>
