<template>
  <div
    class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end widget-option-app"
    :class="className"
    :style="{ backgroundColor: bgColor, backgroundImage: `url('${bgImage}')` }"
    tabindex="0"
  >
    <div class="widget-app-content">
      <span class="widget-icon-app mb-3">
        <i :class="icon"></i>
      </span>
      <span class="widget-app-title">{{ title }}</span>
      <span class="widget-app-subtitle">{{
        description || "Sistema de Gestión Documental"
      }}</span>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
  name: "default-dashboard-widget-1",
  components: {},
  props: {
    className: { type: String, required: false },
    bgColor: { type: String, required: false, default: "#080655" },
    bgImage: { type: String, required: false },
    description: { type: String, required: true },
    title: { type: String, required: false, default: "SGD" },
    icon: { type: String, required: false, default: "bi bi-grid-fill" },
  },
});
</script>

<style scoped>
.widget-option-app {
  border-radius: 1.3rem;
  box-shadow: 0 6px 32px 0 rgba(40, 40, 90, 0.13), 0 1.5px 8px 0 rgba(0,0,0,0.08);
  cursor: pointer;
  transition: box-shadow 0.22s cubic-bezier(.4,2,.6,1), transform 0.18s cubic-bezier(.4,2,.6,1), background 0.22s;
  outline: none;
  min-height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: 
    linear-gradient(120deg, rgba(255,255,255,0.92) 60%, rgba(240,245,255,0.97) 100%),
    linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
  padding: 2rem 1.2rem;
  position: relative;
  overflow: hidden;
}

.widget-option-app::before {
  content: "";
  position: absolute;
  top: -40px;
  right: -40px;
  width: 120px;
  height: 120px;
  background: linear-gradient(135deg, rgba(108,99,255,0.12) 0%, rgba(255,255,255,0.0) 80%);
  border-radius: 50%;
  z-index: 0;
  pointer-events: none;
}

.widget-option-app::after {
  content: "";
  position: absolute;
  left: -30px;
  bottom: -30px;
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, rgba(99,102,241,0.10) 0%, rgba(255,255,255,0.0) 80%);
  border-radius: 50%;
  z-index: 0;
  pointer-events: none;
}

.widget-option-app:hover,
.widget-option-app:focus {
  box-shadow: 0 14px 40px 0 rgba(40, 40, 90, 0.18), 0 2px 12px 0 rgba(0,0,0,0.13);
  transform: translateY(-4px) scale(1.035);
  background: 
    linear-gradient(120deg, rgba(255,255,255,0.98) 60%, rgba(220,230,255,1) 100%),
    linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
}

.widget-app-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  position: relative;
  z-index: 1;
}

.widget-icon-app {
  font-size: 2.7rem;
  color: #6c63ff;
  background: linear-gradient(135deg, #f1f1fa 60%, #e9eafc 100%);
  border-radius: 0.9rem;
  padding: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px 0 rgba(108,99,255,0.08);
  transition: background 0.2s, box-shadow 0.2s, color 0.2s;
}

.widget-option-app:hover .widget-icon-app {
  background: linear-gradient(135deg, #6c63ff 60%, #23235b 100%);
  color: #fff;
  box-shadow: 0 6px 18px 0 rgba(108,99,255,0.13);
}

.widget-app-title {
  color: #ffffff;
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: 1px;
  margin-bottom: 0.4rem;
  text-shadow: 0 1px 4px rgba(108,99,255,0.06);
  text-transform: uppercase;
  transition: color 0.2s;
}

.widget-app-subtitle {
  color: #fffbfb;
  opacity: 0.92;
  font-weight: 500;
  font-size: 1.01rem;
  text-align: center;
  letter-spacing: 0.3px;
  line-height: 1.3;
  max-width: 90%;
}
</style>
