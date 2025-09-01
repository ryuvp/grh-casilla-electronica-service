<template>
  <div v-if="show" class="loading-overlay">
    <div class="loading-content">
      <img :src="imageSrc" alt="Logo" class="custom-img" />
      <div class="loading-dots">
        <span class="dot"></span>
        <span class="dot delay1"></span>
        <span class="dot delay2"></span>
      </div>
    </div>
  </div>
</template>

<script setup lang="js">
const { show } = defineProps({
  show : { type: Boolean, default: false }
});

// ruta estática; si prefieres assets de Vite usa:
// const imageSrc = new URL('/media/auth/loading.png', import.meta.url).pathname;
const imageSrc = "/media/auth/loading.png";
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  z-index: 9999;
  top: 0; left: 0; right: 0; bottom: 0;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.loading-content {
  background: #fff;
  border-radius: 18px;
  padding: 2.5rem 2.7rem 2rem 2.7rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 0 32px #0001;
  min-width: 260px;
}
.custom-img {
  width: 200px;
  height: 200px;
  object-fit: contain;
  margin-bottom: 1.2rem;
  filter: drop-shadow(0 2px 18px #6993ff40);
}
.loading-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  width: 100%;
  min-height: 24px;
}
.dot {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #6993FF;
  opacity: 0.7;
  animation: bounce 1.2s infinite;
}
.dot.delay1 { animation-delay: 0.25s; }
.dot.delay2 { animation-delay: 0.5s; }
@keyframes bounce {
  0%, 80%, 100% { transform: scale(0.7); opacity: 0.7; }
  40% { transform: scale(1.3); opacity: 1; }
}
</style>
