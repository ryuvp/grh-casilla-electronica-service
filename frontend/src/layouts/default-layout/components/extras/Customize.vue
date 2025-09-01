<template>
  <!--begin::Customize Icon in Navbar-->
  <div
    id="kt_app_layout_builder_toggle"
    class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
    data-bs-toggle="tooltip"
    data-bs-placement="left"
    data-bs-dismiss="click"
    data-bs-trigger="hover"
    title="Personalizar"
    style="display: flex; align-items: center; justify-content: center;"
  >
    <KTIcon icon-name="setting-4" icon-class="fs-2" />
  </div>

  <div
    id="kt_app_layout_builder"
    class="bg-body drawer drawer-end"
    data-kt-drawer="true"
    data-kt-drawer-name="app-settings"
    data-kt-drawer-activate="true"
    data-kt-drawer-overlay="true"
    data-kt-drawer-width="{default:'300px', 'lg': '380px'}"
    data-kt-drawer-direction="end"
    data-kt-drawer-toggle="#kt_app_layout_builder_toggle"
    data-kt-drawer-close="#kt_app_layout_builder_close"
  >
    <!--begin::Card-->
    <div class="card border-0 shadow-none rounded-0 w-100">
      <!--begin::Card header-->
      <div
        id="kt_app_layout_builder_header"
        class="card-header bgi-position-y-bottom bgi-position-x-end bgi-size-cover bgi-no-repeat rounded-0 border-0 py-4"
        :style="{
          backgroundImage: `url(
            ${getAssetPath('media/misc/layout/customizer-header-bg.jpg')}
          )`,
        }"
      >
        <!--begin::Card title-->
        <h3 class="card-title fs-3 fw-bold text-white flex-column m-0">
          Configuraciones
          <small class="text-white opacity-50 fs-7 fw-semibold pt-1"></small>
        </h3>
        <!--end::Card title-->
        <!--begin::Card toolbar-->
        <div class="card-toolbar">
          <button
            id="kt_app_layout_builder_close"
            type="button"
            class="btn btn-sm btn-icon btn-color-white p-0 w-20px h-20px rounded-1"
          >
            <KTIcon icon-name="cross-square" icon-class="fs-2" />
          </button>
        </div>
        <!--end::Card toolbar-->
      </div>
      <!--end::Card header-->
      <!--begin::Card body-->
      <div id="kt_app_layout_builder_body" class="card-body position-relative">
        <!--begin::Content-->
        <div 
          id="kt_app_settings_content" 
          class="position-relative scroll-y me-n5 pe-5"           
          data-kt-scroll="true"
          data-kt-scroll-height="auto" 
          data-kt-scroll-wrappers="#kt_app_layout_builder_body"
          data-kt-scroll-dependencies="#kt_app_layout_builder_header, #kt_app_layout_builder_footer"
          data-kt-scroll-offset="5px"
          style="height: 710px"
        >
          <!--begin::Form-->
          <form id="kt_app_layout_builder_form" class="form">
            <input id="kt_app_layout_builder_action" type="hidden" name="layout-builder[action]" />
            <!--begin::Card body-->
            <div class="card-body p-0">
              <!--begin::Form group-->
              <div class="form-group">
                <!--begin::Heading-->
                <div class="mb-6">
                  <h4 class="fw-bold text-gray-900">Temas</h4>
                  <div class="fw-semibold text-muted fs-7 d-block lh-1">
                    Claro &amp; Oscuro.
                  </div>
                </div>
                <!--end::Heading-->
                <!--begin::Options-->
                <div
                  class="row"
                  data-kt-buttons="true"
                  data-kt-buttons-target=".form-check-image,.form-check-input"
                  data-kt-initialized="1"
                >
                  <!--begin::Col-->
                  <div class="col-6">
                    <!--begin::Option-->
                    <label :class="[themeMode === 'light' && 'active']" class="form-check-image form-check-success">
                      <!--begin::Image-->
                      <div class="form-check-wrapper">
                        <img :src="getAssetPath('media/misc/layout/demo1-light.png')" class="mw-100" alt="" />
                      </div>
                      <!--end::Image-->
                      <!--begin::Check-->
                      <div class="form-check form-check-custom form-check-solid form-check-sm form-check-success">
                        <input
                          :checked="themeMode === 'light'"
                          class="form-check-input"
                          type="radio"
                          value="light"
                          name="theme_mode"
                          @change="onThemeModeChange"
                        />
                        <!--begin::Label-->
                        <div class="form-check-label text-gray-700">Light</div>
                        <!--end::Label-->
                      </div>
                      <!--end::Check-->
                    </label>
                    <!--end::Option-->
                  </div>
                  <!--end::Col-->
                  <!--begin::Col-->
                  <div class="col-6">
                    <!--begin::Option-->
                    <label :class="[themeMode === 'dark' && 'active']" class="form-check-image form-check-success">
                      <!--begin::Image-->
                      <div class="form-check-wrapper">
                        <img :src="getAssetPath('media/misc/layout/demo1-dark.png')" class="mw-100" alt="" />
                      </div>
                      <!--end::Image-->
                      <!--begin::Check-->
                      <div class="form-check form-check-custom form-check-solid form-check-sm form-check-success">
                        <input
                          :checked="themeMode === 'dark'"
                          class="form-check-input"
                          type="radio"
                          value="dark"
                          name="theme_mode"
                          @change="onThemeModeChange"
                        />
                        <!--begin::Label-->
                        <div class="form-check-label text-gray-700">Dark</div>
                        <!--end::Label-->
                      </div>
                      <!--end::Check-->
                    </label>
                    <!--end::Option-->
                  </div>
                  <!--end::Col-->
                </div>
                <!--end::Options-->
              </div>
              <!--end::Form group-->

              <!--begin::Separator-->
              <div class="separator separator-dashed my-5"></div>
              <!--end::Separator-->

              <!--begin::Form group-->
              <div class="form-group">
                <div class="d-flex flex-column mb-4">
                  <h4 class="fw-bold text-gray-900">Color de la barra lateral</h4>
                  <div class="fs-7 fw-semibold text-muted">
                    Selecciona el color del sidebar para el tema <b>{{ currentThemeLabel }}</b>.
                  </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <button
                    v-for="color in sidebarColors"
                    :key="color.value"
                    type="button"
                    class="btn p-0 border-0 sidebar-color-btn"
                    :class="[
                      isSidebarColorSelected(color.value) ? `active-${color.value}` : '',
                    ]"
                    :style="{
                      width: '36px',
                      height: '36px',
                      borderRadius: '8px',
                      background: color.hex,
                      boxShadow: '0 0 0 1px #ccc',
                    }"
                    :title="color.label"
                    @click="setSidebarColor(color.value)"
                  >
                    <span
                      v-if="isSidebarColorSelected(color.value)"
                      class="bi bi-check-lg"
                      :style="{
                        fontSize: '1.5rem',
                        lineHeight: '36px',
                        display: 'block',
                        textAlign: 'center',
                        color: '#fff'
                      }"
                    ></span>
                  </button>
                  <button
                    type="button"
                    class="btn btn-light border sidebar-color-btn"
                    :class="{ 'border-primary border-3': isSidebarColorSelected('') }"
                    style="width:36px;height:36px;border-radius:8px;box-shadow:0 0 0 1px #ccc;"
                    title="Verde"
                    @click="setSidebarColor('')"
                  >
                    <span
                      class="bi bi-slash-circle text-success"
                      style="font-size:1.5rem;line-height:36px;display:block;text-align:center;"
                    ></span>
                  </button>
                </div>
              </div>
              <!--end::Form group-->

              <!--begin::Separator-->
              <div class="separator separator-dashed my-5"></div>
              <!--end::Separator-->

              <!--begin::Form group-->
              <div class="form-group">
                <div class="mb-6">
                  <h4 class="fw-bold text-gray-900">Formatos</h4>
                  <span class="fw-semibold text-muted fs-7 lh-1">Seleccione el formato.{{ " " }}</span>
                </div>
                <div
                  class="row gy-3"
                  data-kt-buttons="true"
                  data-kt-buttons-target=".form-check-image:not(.disabled),.form-check-input:not([disabled])"
                  data-kt-initialized="1"
                >
                  <div class="col-6">
                    <label
                      class="form-check-image form-check-success"
                      :class="[layoutType === 'dark-sidebar' && 'active']"
                    >
                      <div class="form-check-wrapper">
                        <img :src="getAssetPath('media/misc/layout/dark-sidebar.png')" class="mw-100" alt="" />
                      </div>
                      <div class="form-check form-check-custom form-check-success form-check-sm form-check-solid">
                        <input
                          v-model="layoutType"
                          class="form-check-input"
                          type="radio"
                          value="dark-sidebar"
                          name="layoutType"
                        />
                        <div class="form-check-label text-gray-700">
                          Menú (Izquierda)
                        </div>
                      </div>
                    </label>
                  </div>

                  <div class="col-6">
                    <label
                      class="form-check-image form-check-success"
                      :class="[layoutType === 'dark-header' && 'active']"
                    >
                      <div class="form-check-wrapper">
                        <img :src="getAssetPath('media/misc/layout/dark-header.png')" class="mw-100" alt="" />
                      </div>
                      <div class="form-check form-check-custom form-check-success form-check-sm form-check-solid">
                        <input
                          v-model="layoutType"
                          class="form-check-input"
                          type="radio"
                          value="dark-header"
                          name="layoutType"
                        />
                        <div class="form-check-label text-gray-700">
                          Menu superior
                        </div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
              <!--end::Form group-->
            </div>
            <!--end::Card body-->
          </form>
          <!--end::Form-->
        </div>
        <!--end::Content-->
      </div>
      <!--end::Card body-->
      <!--begin::Card footer-->
      <div id="kt_app_layout_builder_footer" class="card-footer border-0 d-flex gap-3 pb-9 pt-0">
        <button
          id="kt_app_layout_builder_preview"
          type="button"
          class="btn btn-primary flex-grow-1 fw-semibold"
          @click="submit"
        >
          <span class="indicator-label">Visualizar Cambios</span>

        </button>
        <button
          id="kt_app_layout_builder_reset"
          type="button"
          class="btn btn-light flex-grow-1 fw-semibold"
          @click="reset"
        >
          <span class="indicator-label">Reset</span>
          <span class="indicator-progress">Please wait...
            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
          </span>
        </button>
      </div>
      <!--end::Card footer-->
    </div>
    <!--end::Card-->
  </div>
  <!-- Componente de carga para cambios de formato -->
  <Loading :show="isLoading" />
</template>

<script setup lang="js">
import { ref, computed } from "vue";
import { getAssetPath } from "@/core/helpers/assets";
import { config, layout, themeMode } from "@/layouts/default-layout/config/helper";
import { LS_CONFIG_NAME_KEY, useConfigStore } from "@/stores/config";
import { useThemeStore } from "@/stores/theme";
import Loading from "@/components/page-layouts/Loading.vue";

const storeConfig = useConfigStore();
const storeTheme = useThemeStore();

const isLoading = ref(false);
const layoutType = ref(layout.value);

const sidebarColors = [
  { value: "yellow",      label: "Amarillo",        hex: "#FFE600" },
  { value: "dark-yellow", label: "Amarillo Oscuro", hex: "#F9B233" },
  { value: "orange",      label: "Naranja",         hex: "#F39200" },
  { value: "dark-green",  label: "Verde Oscuro",    hex: "#417A3C" },
  { value: "green",       label: "Verde",           hex: "#00B388" },
  { value: "light-green", label: "Verde Claro",     hex: "#6FC06E" },
  { value: "pink",        label: "Rosa",            hex: "#E6007E" },
  { value: "dark-pink",   label: "Rosa Oscuro",     hex: "#A63A5A" },
  { value: "blue",        label: "Azul",            hex: "#0071B9" },
  { value: "dark-blue",   label: "Azul Oscuro",     hex: "#252364" },
  { value: "lime",        label: "Lima",            hex: "#8DC63F" },
  { value: "dark",        label: "Oscuro",          hex: "#181C32" },
];

const saveConfig = () => {
  localStorage.setItem(LS_CONFIG_NAME_KEY, JSON.stringify(config.value));
};

const currentTheme = computed(() => {
  if (themeMode.value === "system") {
    return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
  }
  return themeMode.value;
});

const currentThemeLabel = computed(() => (currentTheme.value === "dark" ? "oscuro" : "claro"));

const getSidebarDefaults = () => (config.value.sidebar?.default ?? {});

function isSidebarColorSelected(color) {
  const defaults = getSidebarDefaults();
  if (currentTheme.value === "dark") return (defaults.bgColorDark || "") === color;
  return (defaults.bgColorLight || "") === color;
}

function setSidebarColor(color) {
  const defaults = getSidebarDefaults();
  const newColor = color || "green";
  defaults.bgColorLight = newColor;
  defaults.bgColorDark  = newColor;
  saveConfig();
}

function reset(e) {
  e.preventDefault();
  localStorage.removeItem(LS_CONFIG_NAME_KEY);
  window.location.reload();
}

function onThemeModeChange(e) {
  const value = e.target.value;
  storeConfig.setLayoutConfigProperty("general.mode", value);
  storeTheme.setThemeMode(value);
  saveConfig();
}

function submit(e) {
  e.preventDefault();
  isLoading.value = true;
  storeConfig.setLayoutConfigProperty("general.layout", layoutType.value);
  saveConfig();
  setTimeout(() => window.location.reload(), 200);
}
</script>
