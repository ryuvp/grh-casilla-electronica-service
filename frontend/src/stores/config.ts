import { ref } from "vue";
import { defineStore } from "pinia";
import objectPath from "object-path";
import layoutConfig from "@/layouts/default-layout/config/DefaultLayoutConfig";

export const LS_CONFIG_NAME_KEY = "config_" + import.meta.env.VITE_APP_DEMO;

export const useConfigStore = defineStore("config", () => {
  // Evitamos mutar el objeto importado
  const config = ref({ ...layoutConfig });
  const initial = ref({ ...layoutConfig });

  function getLayoutConfig(path, defaultValue) {
    return objectPath.get(config.value, path, defaultValue);
  }

  function setLayoutConfigProperty(property, value) {
    objectPath.set(config.value, property, value);
    localStorage.setItem(LS_CONFIG_NAME_KEY, JSON.stringify(config.value));
  }

  function resetLayoutConfig() {
    config.value = { ...initial.value };
  }

  function overrideLayoutConfig() {
    const saved = JSON.parse(window.localStorage.getItem(LS_CONFIG_NAME_KEY) || "{}");
    // merge simple (nivel superior); si necesitas deep-merge, usar una lib
    config.value = { ...initial.value, ...saved };
    initial.value = { ...config.value };
  }

  return {
    config,
    getLayoutConfig,
    setLayoutConfigProperty,
    resetLayoutConfig,
    overrideLayoutConfig,
  };
});
