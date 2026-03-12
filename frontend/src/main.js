// src/main.js
import "@/core/plugins/prismjs";

import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';

// Plugins globales de UI y validacion.
import { initApexCharts } from "@/core/plugins/apexcharts";
import { initInlineSvg } from "@/core/plugins/inline-svg";
import { initVeeValidate } from "@/core/plugins/vee-validate";
import { initKtIcon } from "@/core/plugins/keenthemes";

// Carga bundle JS de Bootstrap (modal, dropdown, tooltip, etc.).
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Constructor de tooltip usado por directiva global.
import { Tooltip } from 'bootstrap';

// Directiva global para tooltips de Bootstrap.
const tooltipDirective = {
  // Inicializa tooltip al montar el elemento cuando existe contenido.
  mounted(el, binding) {
    if (binding.value) {
      el._tooltipInstance = new Tooltip(el, {
        title     : binding.value,
        placement : binding.arg || 'top',
        trigger   : 'hover focus',
      });
    }
  },

  // Re-crea tooltip cuando cambian sus props para mantener estado consistente.
  updated(el, binding) {
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

  // Libera recursos del tooltip al desmontar el elemento.
  unmounted(el) {
    if (el._tooltipInstance) {
      el._tooltipInstance.dispose();
      el._tooltipInstance = null;
    }
  }
};

// Instancia principal de Vue.
const app = createApp(App);
import ElementPlus from 'element-plus';
import i18n from '@/core/plugins/i18n';

// Registra stores y plugins base.
const pinia = createPinia();
app.use(pinia);
app.use(router);
app.use(ElementPlus);
app.use(i18n);

// Inicializa plugins visuales/transversales.
initApexCharts(app);
initInlineSvg(app);
initKtIcon(app);
initVeeValidate();

// Registra directiva tooltip para uso global en templates.
app.directive('tooltip', tooltipDirective);

// Monta aplicacion en el contenedor raiz.
app.mount('#app');
