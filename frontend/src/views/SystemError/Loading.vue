<template>
  <div class="overlay-loader">
    <div class="spinner-border text-primary spinner-lg" role="status" aria-hidden="true"></div>
    <p class="mt-3 mb-0 animate-fade">Cargando servicio…</p>
    <small class="text-muted animate-fade">Esperando autenticación desde la ventana padre</small>
  </div>
</template>

<script setup>
import { onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import useAuthStore from "@/stores/auth/authStore";
import JwtService from "@/core/services/JwtService";

const router = useRouter();
const authStore = useAuthStore();

// Si ya hay token (refresh), salta directo
onMounted(() => {
  if (JwtService.haveToken()) {
    router.replace("/bandeja");
  }
});

// Si el store cambia a estado autenticado, navegar
watch(
  () => authStore.isAuthenticated,
  (ok) => { if (ok) router.replace("/bandeja"); }
);
</script>

<style scoped>
.overlay-loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(255, 255, 255, 0.95); /* Fondo blanco semitransparente */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 9999; /* Siempre encima */
}

.spinner-lg {
  width: 5rem;
  height: 5rem;
}

/* Animación fade-in/fade-out */
@keyframes fade {
  0%, 100% { opacity: 0.2; }
  50% { opacity: 1; }
}

.animate-fade {
  animation: fade 2s infinite;
}
</style>
