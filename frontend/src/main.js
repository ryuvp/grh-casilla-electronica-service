// src/main.js
import "@/core/plugins/prismjs";

import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';


//imports for app initialization
import { initApexCharts } from "@/core/plugins/apexcharts";
import { initInlineSvg } from "@/core/plugins/inline-svg";
import { initVeeValidate } from "@/core/plugins/vee-validate";
import { initKtIcon } from "@/core/plugins/keenthemes";

// Si usas Bootstrap 5, importa así:
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Si necesitas usar Tooltip explícitamente:
import { Tooltip } from 'bootstrap';

// Directiva global para tooltips de Bootstrap
const tooltipDirective = {
  mounted(el, binding) {
    // Solo inicializa el tooltip si hay valor
    if (binding.value) {
      el._tooltipInstance = new Tooltip(el, {
        title     : binding.value,
        placement : binding.arg || 'top',
        trigger   : 'hover focus',
      });
    }
  },
  updated(el, binding) {
    // Actualiza el tooltip si cambia el valor
    if (el._tooltipInstance) {
      el._tooltipInstance.dispose();
      el._tooltipInstance = null;
    }
    if (binding.value) {
      el._tooltipInstance = new Tooltip(el, {
        title     : binding.value,
        placement : binding.arg || 'top',
        trigger   : 'hover focus',
      });
    }
  },
  unmounted(el) {
    if (el._tooltipInstance) {
      el._tooltipInstance.dispose();
      el._tooltipInstance = null;
    }
  }
};

const app = createApp(App);
import ElementPlus from 'element-plus';
import i18n from '@/core/plugins/i18n';

const pinia = createPinia();
app.use(pinia);
app.use(router);
app.use(ElementPlus);
app.use(i18n);

initApexCharts(app);
initInlineSvg(app);
initKtIcon(app);
initVeeValidate();

app.directive('tooltip', tooltipDirective);

app.mount('#app');
