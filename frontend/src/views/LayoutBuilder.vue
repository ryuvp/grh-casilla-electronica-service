<template>
  <!--begin::Layout Builder Notice-->
  <div class="card mb-10">
    <div class="card-body d-flex align-items-center py-8">
      <!--begin::Icon-->
      <div
        class="d-flex h-50px w-50px h-lg-80px w-lg-80px flex-shrink-0 flex-center position-relative align-self-start align-self-lg-center mt-3 mt-lg-0"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          class="text-primary h-75px w-75px h-lg-100px w-lg-100px position-absolute opacity-5"
        >
          <path
            fill="currentColor"
            d="M10.2,21.23,4.91,18.17a3.58,3.58,0,0,1-1.8-3.11V8.94a3.58,3.58,0,0,1,1.8-3.11L10.2,2.77a3.62,3.62,0,0,1,3.6,0l5.29,3.06a3.58,3.58,0,0,1,1.8,3.11v6.12a3.58,3.58,0,0,1-1.8,3.11L13.8,21.23A3.62,3.62,0,0,1,10.2,21.23Z"
          ></path>
        </svg>
        <KTIcon
          icon-name="wrench"
          icon-class="fs-2x fs-lg-3x text-primary position-absolute"
        />
      </div>
      <!--end::Icon-->

      <!--begin::Description-->
      <div class="ms-6">
        <p class="list-unstyled text-gray-600 fw-semibold fs-6 p-0 m-0">
          El generador de layouts te ayudará a configurar tu diseño preferido para tu proyecto, generando una configuración de layout y previsualizándola en tiempo real. Para previsualizar la configuración por defecto, simplemente vacía tu localStorage o presiona el botón 'Restablecer'.
        </p>
      </div>
      <!--end::Description-->
    </div>
  </div>
  <!--end::Layout Builder Notice-->

  <div class="card mb-10">
    <!--begin::Header-->
    <div class="card-header card-header-stretch">
      <ul
        class="nav nav-stretch nav-line-tabs fw-semibold border-0"
        role="tablist"
        id="kt_layout_builder_tabs"
        ref="kt_layout_builder_tabs"
      >
        <li class="nav-item">
          <a
            class="nav-link cursor-pointer"
            :class="{ active: tabIndex === 'main' }"
            data-bs-toggle="tab"
            @click="setActiveTab($event)"
            data-tab-index="main"
            role="tab"
          >
            Principal
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link cursor-pointer"
            :class="{ active: tabIndex === 'layout' }"
            data-bs-toggle="tab"
            @click="setActiveTab($event)"
            data-tab-index="layout"
            role="tab"
          >
            Layout
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link cursor-pointer"
            :class="{ active: tabIndex === 'sidebar' }"
            data-bs-toggle="tab"
            @click="setActiveTab($event)"
            data-tab-index="sidebar"
            role="tab"
          >
            Barra lateral
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link cursor-pointer"
            :class="{ active: tabIndex === 'header' }"
            data-bs-toggle="tab"
            @click="setActiveTab($event)"
            data-tab-index="header"
            role="tab"
          >
            Encabezado
          </a>
        </li>
      </ul>
    </div>
    <!--end::Header-->

    <!--begin::Form-->
    <form class="form" id="kt_layout_builder_form" @submit="submit($event)">
      <!--begin::Body-->
      <div class="card-body">
        <div class="tab-content pt-3" id="kt_tabs">
          <div
            class="tab-pane"
            :class="{ active: tabIndex === 'main' }"
            id="kt_builder_main"
          >
            <!--begin::Row-->
            <div class="row">
              <!--begin::Col-->
              <div class="col-lg-6 pe-lg-15">
                <!--begin::Form group-->
                <div class="form-group">
                  <!--begin::Heading-->
                  <div class="mb-6">
                    <h4 class="fw-bold text-gray-900">Modo de Tema</h4>
                    <div class="fw-semibold text-muted fs-7 d-block lh-1">
                      Disfruta de los modos Oscuro y Claro.
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
                      <label
                        class="form-check-image form-check-success"
                        :class="[themeMode === 'light' && 'active']"
                      >
                        <!--begin::Image-->
                        <div class="form-check-wrapper">
                          <img
                            :src="
                              getAssetPath('media/misc/layout/demo1-light.png')
                            "
                            class="mw-100"
                            alt=""
                          />
                        </div>
                        <!--end::Image-->
                        <!--begin::Check-->
                        <div
                          class="form-check form-check-custom form-check-solid form-check-sm form-check-success"
                        >
                          <input
                            class="form-check-input"
                            type="radio"
                            :checked="themeMode === 'light'"
                            value="light"
                            name="theme_mode"
                            @change="onThemeModeChange"
                          />
                          <!--begin::Label-->
                          <div class="form-check-label text-gray-700">
                            Claro
                          </div>
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
                      <label
                        class="form-check-image form-check-success"
                        :class="[themeMode === 'dark' && 'active']"
                      >
                        <!--begin::Image-->
                        <div class="form-check-wrapper">
                          <img
                            :src="
                              getAssetPath('media/misc/layout/demo1-dark.png')
                            "
                            class="mw-100"
                            alt=""
                          />
                        </div>
                        <!--end::Image-->
                        <!--begin::Check-->
                        <div
                          class="form-check form-check-custom form-check-solid form-check-sm form-check-success"
                        >
                          <input
                            class="form-check-input"
                            type="radio"
                            :checked="themeMode === 'dark'"
                            value="dark"
                            name="theme_mode"
                            @change="onThemeModeChange"
                          />
                          <!--begin::Label-->
                          <div class="form-check-label text-gray-700">Oscuro</div>
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
              </div>
              <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Separator-->
            <div class="separator separator-dashed my-6"></div>
            <!--end::Separator-->
            <!--begin::Form group-->
            <div class="form-group">
              <!--begin::Heading-->
              <div class="d-flex flex-column mb-4">
                <h4 class="fw-bold text-gray-900">Modo de Ancho</h4>
                <div class="fs-7 fw-semibold text-muted">
                  Opciones de ancho de página
                </div>
              </div>
              <!--end::Heading-->
              <!--begin::Options-->
              <div class="d-flex">
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-success form-check-solid form-check-sm me-7"
                >
                  <input
                    v-model="config.general.pageWidth"
                    class="form-check-input"
                    type="radio"
                    value="default"
                    id="kt_builder_page_width_default"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold text-nowrap"
                    for="kt_builder_page_width_default"
                    >Por defecto</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-success form-check-solid form-check-sm me-7"
                >
                  <input
                    v-model="config.general.pageWidth"
                    class="form-check-input"
                    type="radio"
                    value="fluid"
                    id="kt_builder_page_width_fluid"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold text-nowrap"
                    for="kt_builder_page_width_fluid"
                    >Fluido</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-success form-check-solid form-check-sm me-7"
                >
                  <input
                    v-model="config.general.pageWidth"
                    class="form-check-input"
                    type="radio"
                    value="fixed"
                    id="kt_builder_page_width_fixed"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold text-nowrap"
                    for="kt_builder_page_width_fixed"
                    >Fijo</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
              </div>
              <!--end::Options-->
            </div>
            <!--end::Form group-->
            <!--begin::Separator-->
            <div class="separator separator-dashed my-6"></div>
            <!--end::Separator-->
            <!--begin::Form group-->
            <div class="form-group d-flex flex-stack">
              <!--begin::Heading-->
              <div class="d-flex flex-column">
                <h4 class="fw-bold text-gray-900">Estilo de Keenicons</h4>
                <div>
                  <span class="fs-7 fw-semibold text-muted"
                    >Selecciona el estilo global de íconos UI.</span
                  >{{ " " }}
                  <a
                    class="fw-semibold"
                    href="https://preview.keenthemes.com/html/metronic/docs/icons/keenicons"
                    target="_blank"
                    >Saber más</a
                  >
                </div>
              </div>
              <!--end::Heading-->
              <!--begin::Options-->
              <div
                class="d-flex flex-stack gap-3 mw-lg-600px"
                data-kt-buttons="true"
                data-kt-buttons-target=".form-check-image,.form-check-input"
                data-kt-initialized="1"
              >
                <!--begin::Option-->
                <label
                  :class="config.general.iconsType === 'duotone' && 'active'"
                  class="form-check-image form-check-success w-100 parent-active parent-hover"
                >
                  <!--begin::Image-->
                  <div
                    class="form-check-wrapper d-flex flex-center border-gray-200 border-2 mb-0 py-3 px-4"
                  >
                    <KTIcon
                      icon-type="duotone"
                      icon-name="picture"
                      icon-class="fs-1 text-gray-500 parent-active-gray-700 parent-hover-gray-700"
                    />
                    <span
                      class="fs-7 fw-semibold ms-2 text-gray-500 parent-active-gray-700 parent-hover-gray-700"
                      >Duotone</span
                    >
                  </div>
                  <!--end::Image-->
                  <!--begin::Check-->
                  <div
                    style="
                      visibility: hidden;
                      height: 0;
                      width: 0;
                      overflow: hidden;
                    "
                  >
                    <input
                      v-model="config.general.iconsType"
                      class="form-check-input"
                      type="radio"
                      value="duotone"
                      :checked="config.general.iconsType === 'duotone'"
                    />
                  </div>
                  <!--end::Check-->
                </label>
                <!--end::Option-->
                <!--begin::Option-->
                <label
                  :class="config.general.iconsType === 'outline' && 'active'"
                  class="form-check-image form-check-success w-100 parent-active parent-hover"
                >
                  <!--begin::Image-->
                  <div
                    class="form-check-wrapper d-flex flex-center border-gray-200 border-2 mb-0 py-3 px-4"
                  >
                    <i
                      class="ki-outline ki-picture fs-1 text-gray-500 parent-active-gray-700 parent-hover-gray-700"
                    ></i>
                    <span
                      class="fs-7 fw-semibold ms-2 text-gray-500 parent-active-gray-700 parent-hover-gray-700"
                      >Outline</span
                    >
                  </div>
                  <!--end::Image-->
                  <!--begin::Check-->
                  <div
                    style="
                      visibility: hidden;
                      height: 0;
                      width: 0;
                      overflow: hidden;
                    "
                  >
                    <input
                      v-model="config.general.iconsType"
                      class="form-check-input"
                      type="radio"
                      value="outline"
                      name="outline"
                      :checked="config.general.iconsType === 'outline'"
                    />
                  </div>
                  <!--end::Check-->
                </label>
                <!--end::Option-->
                <!--begin::Option-->
                <label
                  :class="config.general.iconsType === 'solid' && 'active'"
                  class="form-check-image form-check-success w-100 parent-active parent-hover"
                >
                  <!--begin::Image-->
                  <div
                    class="form-check-wrapper d-flex flex-center border-gray-200 border-2 mb-0 py-3 px-4"
                  >
                    <i
                      class="ki-solid ki-picture fs-1 text-gray-500 parent-active-gray-700 parent-hover-gray-700"
                    ></i>
                    <span
                      class="fs-7 fw-semibold ms-2 text-gray-500 parent-active-gray-700 parent-hover-gray-700"
                      >Solid</span
                    >
                  </div>
                  <!--end::Image-->
                  <!--begin::Check-->
                  <div
                    style="
                      visibility: hidden;
                      height: 0;
                      width: 0;
                      overflow: hidden;
                    "
                  >
                    <input
                      v-model="config.general.iconsType"
                      class="form-check-input"
                      type="radio"
                      value="solid"
                      name="solid"
                      :checked="config.general.iconsType === 'solid'"
                    />
                  </div>
                  <!--end::Check-->
                </label>
                <!--end::Option-->
              </div>
              <!--end::Options-->
            </div>
            <!--end::Form group-->
            <!--begin::Separator-->
            <div class="separator separator-dashed my-6"></div>
            <!--end::Separator-->
            <!--begin::Form group-->
            <div class="form-group d-flex flex-stack">
              <!--begin::Heading-->
              <div class="d-flex flex-column">
                <h4 class="fw-bold text-gray-900">Ícono del Menú</h4>
                <div class="fs-7 fw-semibold text-muted">
                  Opciones de ícono del menú lateral
                </div>
              </div>
              <!--end::Heading-->
              <!--begin::Option-->
              <div class="d-flex justify-content-end">
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-success form-check-solid me-7"
                  data-bs-toggle="tooltip"
                  data-kt-initialized="1"
                >
                  <input
                    v-model="config.sidebar.default.menu.iconType"
                    class="form-check-input w-20px h-20px"
                    type="radio"
                    value="bootstrap"
                    id="kt_builder_icon_svg"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold text-nowrap"
                    for="kt_builder_icon_svg"
                    >Bootstrap</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-success form-check-solid me-7"
                  data-bs-toggle="tooltip"
                  data-kt-initialized="1"
                >
                  <input
                    v-model="config.sidebar.default.menu.iconType"
                    class="form-check-input w-20px h-20px"
                    type="radio"
                    value="keenthemes"
                    id="kt_builder_icon_font"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold text-nowrap"
                    for="kt_builder_icon_font"
                    >Keenthemes</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
              </div>
              <!--end::Option-->
            </div>
            <!--end::Form group-->
          </div>

          <div
            class="tab-pane"
            :class="{ active: tabIndex === 'layout' }"
            id="kt_builder_layout"
          >
            <!--begin::Heading-->
            <div class="mb-6">
              <h4 class="fw-bold text-gray-900">Layouts</h4>
              <span class="fw-semibold text-muted fs-7 lh-1"
                >4 opciones principales de layout.</span
              >
            </div>
            <!--end::Heading-->
            <!--begin::Options-->
            <div
              class="row gy-5"
              data-kt-buttons="true"
              data-kt-buttons-target=".form-check-image:not(.disabled),.form-check-input:not([disabled])"
              data-kt-initialized="1"
            >
              <!--begin::Col-->
              <div class="col-lg-3">
                <!--begin::Option-->
                <label
                  :class="[layoutType === 'dark-sidebar' && 'active']"
                  class="form-check-image form-check-success"
                >
                  <!--begin::Image-->
                  <div class="form-check-wrapper">
                    <img
                      :src="getAssetPath('media/misc/layout/dark-sidebar.png')"
                      class="mw-100"
                      alt=""
                    />
                  </div>
                  <!--end::Image-->
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-success form-check-sm form-check-solid"
                  >
                    <input
                      v-model="layoutType"
                      class="form-check-input"
                      type="radio"
                      value="dark-sidebar"
                      name="layoutType"
                    />
                    <!--begin::Label-->
                    <div class="form-check-label text-gray-800">
                      Barra lateral oscura
                    </div>
                    <!--end::Label-->
                  </div>
                  <!--end::Check-->
                </label>
                <!--end::Option-->
              </div>
              <!--end::Col-->
              <!--begin::Col-->
              <div class="col-lg-3">
                <!--begin::Option-->
                <label
                  :class="[layoutType === 'light-sidebar' && 'active']"
                  class="form-check-image form-check-success"
                >
                  <!--begin::Image-->
                  <div class="form-check-wrapper">
                    <img
                      :src="getAssetPath('media/misc/layout/light-sidebar.png')"
                      class="mw-100"
                      alt=""
                    />
                  </div>
                  <!--end::Image-->
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-success form-check-sm form-check-solid"
                  >
                    <input
                      v-model="layoutType"
                      class="form-check-input"
                      type="radio"
                      value="light-sidebar"
                      name="layoutType"
                    />
                    <!--begin::Label-->
                    <div class="form-check-label text-gray-800">
                      Barra lateral clara
                    </div>
                    <!--end::Label-->
                  </div>
                  <!--end::Check-->
                </label>
                <!--end::Option-->
              </div>
              <!--end::Col-->
              <!--begin::Col-->
              <div class="col-lg-3">
                <!--begin::Option-->
                <label
                  :class="[layoutType === 'dark-header' && 'active']"
                  class="form-check-image form-check-success"
                >
                  <!--begin::Image-->
                  <div class="form-check-wrapper">
                    <img
                      :src="getAssetPath('media/misc/layout/dark-header.png')"
                      class="mw-100"
                      alt=""
                    />
                  </div>
                  <!--end::Image-->
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-success form-check-sm form-check-solid"
                  >
                    <input
                      v-model="layoutType"
                      class="form-check-input"
                      type="radio"
                      value="dark-header"
                      name="layoutType"
                    />
                    <!--begin::Label-->
                    <div class="form-check-label text-gray-800">
                      Encabezado oscuro
                    </div>
                    <!--end::Label-->
                  </div>
                  <!--end::Check-->
                </label>
                <!--end::Option-->
              </div>
              <!--end::Col-->
              <!--begin::Col-->
              <div class="col-lg-3">
                <!--begin::Option-->
                <label
                  :class="[layoutType === 'light-header' && 'active']"
                  class="form-check-image form-check-success"
                >
                  <!--begin::Image-->
                  <div class="form-check-wrapper">
                    <img
                      :src="getAssetPath('media/misc/layout/light-header.png')"
                      class="mw-100"
                      alt=""
                    />
                  </div>
                  <!--end::Image-->
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-success form-check-sm form-check-solid"
                  >
                    <input
                      v-model="layoutType"
                      class="form-check-input"
                      type="radio"
                      value="light-header"
                      name="layoutType"
                    />
                    <!--begin::Label-->
                    <div class="form-check-label text-gray-800">
                      Encabezado claro
                    </div>
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

          <div
            class="tab-pane"
            :class="{ active: tabIndex === 'sidebar' }"
            id="kt_builder_sidebar"
          >
            <!--begin::Form group-->
            <div class="form-group d-flex flex-stack">
              <!--begin::Heading-->
              <div class="d-flex flex-column">
                <h4 class="fw-bold text-gray-900">Minimizar</h4>
                <div class="fs-7 fw-semibold text-muted">
                  Modo de minimizado de la barra lateral
                </div>
              </div>
              <!--end::Heading-->
              <!--begin::Option-->
              <div class="d-flex justify-content-end">
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-solid form-check-success form-switch"
                >
                  <input
                    v-model="config.sidebar.default.minimize.desktop.enabled"
                    class="form-check-input w-45px h-30px"
                    type="checkbox"
                    value="true"
                    id="kt_builder_sidebar_minimize_desktop_enabled"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold"
                    for="kt_builder_sidebar_minimize_desktop_enabled"
                    data-bs-toggle="tooltip"
                    data-kt-initialized="1"
                    >Alternar Minimizado</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-solid form-check-success form-switch ms-10"
                >
                  <input
                    v-model="config.sidebar.default.minimize.desktop.hoverable"
                    class="form-check-input w-45px h-30px"
                    type="checkbox"
                    value="true"
                    id="kt_builder_sidebar_minimize_desktop_hoverable"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold"
                    for="kt_builder_sidebar_minimize_desktop_hoverable"
                    data-bs-toggle="tooltip"
                    data-kt-initialized="1"
                    >Hoverable</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
                <!--begin::Check-->
                <div
                  class="form-check form-check-custom form-check-solid form-check-success form-switch ms-10"
                >
                  <input
                    v-model="config.sidebar.default.minimize.desktop.default"
                    class="form-check-input w-45px h-30px"
                    type="checkbox"
                    value="true"
                    id="kt_builder_sidebar_minimize_desktop_default"
                  />
                  <!--begin::Label-->
                  <label
                    class="form-check-label text-gray-700 fw-bold"
                    for="kt_builder_sidebar_minimize_desktop_default"
                    data-bs-toggle="tooltip"
                    data-kt-initialized="1"
                    >Minimizado por defecto</label
                  >
                  <!--end::Label-->
                </div>
                <!--end::Check-->
              </div>
              <!--end::Option-->
            </div>
            <!--end::Form group-->

            <!--begin::Separator-->
            <div class="separator separator-dashed my-6"></div>
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
                  @click="setSidebarColor(color.value)"
                  :title="color.label"
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
                  :class="{'border-primary border-3': isSidebarColorSelected('')}"
                  style="width:36px;height:36px;border-radius:8px;box-shadow:0 0 0 1px #ccc;"
                  @click="setSidebarColor('')"
                  title="Por defecto"
                >
                  <span class="bi bi-slash-circle text-muted" style="font-size:1.5rem;line-height:36px;display:block;text-align:center;"></span>
                </button>
              </div>
            </div>
            <!--end::Form group-->
          </div>

          <div
            class="tab-pane"
            :class="{ active: tabIndex === 'header' }"
            id="kt_builder_header"
          >
            <div
              class="tab-pane active show"
              id="kt_builder_header"
              role="tabpanel"
            >
              <!--begin::Form group-->
              <div class="form-group d-flex flex-stack">
                <!--begin::Heading-->
                <div class="d-flex flex-column">
                  <h4 class="fw-bold text-gray-900">Fijo</h4>
                  <div class="fs-7 fw-semibold text-muted">
                    Modo de barra de herramientas fija
                  </div>
                </div>
                <!--end::Heading-->
                <!--begin::Option-->
                <div class="d-flex justify-content-end">
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-solid form-check-success form-switch me-10"
                  >
                    <input
                      v-model="config.header.default.fixed.desktop"
                      class="form-check-input w-45px h-30px"
                      type="checkbox"
                      value="true"
                      id="kt_builder_header_fixed_desktop"
                    />
                    <!--begin::Label-->
                    <label
                      class="form-check-label text-gray-700 fw-bold"
                      for="kt_builder_header_fixed_desktop"
                      >Modo Escritorio</label
                    >
                    <!--end::Label-->
                  </div>
                  <!--end::Check-->
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-solid form-check-success form-switch"
                  >
                    <input
                      v-model="config.header.default.fixed.mobile"
                      class="form-check-input w-45px h-30px"
                      type="checkbox"
                      value="true"
                      id="kt_builder_header_fixed_mobile"
                    />
                    <!--begin::Label-->
                    <label
                      class="form-check-label text-gray-700 fw-bold"
                      for="kt_builder_header_fixed_mobile"
                      >Modo Móvil</label
                    >
                    <!--end::Label-->
                  </div>
                  <!--end::Check-->
                </div>
                <!--end::Option-->
              </div>
              <!--end::Form group-->
              <!--begin::Separator-->
              <div class="separator separator-dashed my-6"></div>
              <!--end::Separator-->
              <!--begin::Form group-->
              <div class="form-group d-flex flex-stack">
                <!--begin::Heading-->
                <div class="d-flex flex-column">
                  <h4 class="fw-bold text-gray-900">Título de Página</h4>
                  <div class="fs-7 fw-semibold text-muted">
                    Opciones de layout del título de página
                  </div>
                </div>
                <!--end::Heading-->
                <!--begin::Option-->
                <div class="d-flex justify-content-end">
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-solid form-check-success form-check-sm me-10"
                  >
                    <input
                      v-model="config.pageTitle.direction"
                      class="form-check-input"
                      type="radio"
                      value="column"
                      id="kt_builder_page_title_direction_column"
                    />
                    <!--begin::Label-->
                    <label
                      class="form-check-label text-gray-700 fw-bold"
                      for="kt_builder_page_title_direction_column"
                      data-bs-toggle="tooltip"
                      data-kt-initialized="1"
                      >Columna</label
                    >
                    <!--end::Label-->
                  </div>
                  <!--end::Check-->
                  <!--begin::Check-->
                  <div
                    class="form-check form-check-custom form-check-solid form-check-success"
                  >
                    <input
                      v-model="config.pageTitle.direction"
                      class="form-check-input"
                      type="radio"
                      value="row"
                      id="kt_builder_page_title_direction_row"
                    />
                    <!--begin::Label-->
                    <label
                      class="form-check-label text-gray-700 fw-bold"
                      for="kt_builder_page_title_direction_row"
                      data-bs-toggle="tooltip"
                      data-kt-initialized="1"
                      >Fila</label
                    >
                    <!--end::Label-->
                  </div>
                  <!--end::Check-->
                </div>
                <!--end::Option-->
              </div>
              <!--end::Form group-->
            </div>
          </div>
        </div>
      </div>
      <!--end::Body-->

      <!--begin::Footer-->
      <div class="card-footer py-6">
        <div class="row">
          <div class="col-lg-9">
            <button
              type="submit"
              id="kt_layout_builder_preview"
              class="btn btn-primary me-2"
            >
              <span class="indicator-label"> Previsualizar </span>
              <span class="indicator-progress">
                Por favor espere...
                <span
                  class="spinner-border spinner-border-sm align-middle ms-2"
                ></span>
              </span>
            </button>

            <button
              @click="reset($event)"
              type="button"
              id="kt_layout_builder_reset"
              class="btn btn-active-light btn-color-muted fw-semibold"
            >
              Restablecer
            </button>
          </div>
        </div>
      </div>
      <!--end::Footer-->
    </form>
    <!--end::Form-->
  </div>
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, onMounted, ref, computed } from "vue";
import { config, layout, themeMode } from "@/layouts/default-layout/config/helper";
import CodeHighlighter from "@/components/highlighters/CodeHighlighter.vue";
import { themeName } from "@/core/helpers/system";
import { useThemeStore } from "@/stores/theme";
import { LS_CONFIG_NAME_KEY, useConfigStore } from "@/stores/config";

const LS_BUILDER_TAB_NAME =
  "layoutBuilderTabIndex_" + import.meta.env.VITE_APP_DEMO;

export default defineComponent({
  name: "layout-builder",
  components: {
    CodeHighlighter,
  },
  setup() {
    const storeTheme = useThemeStore();
    const storeBody = useConfigStore();
    const tabIndex = ref<string>("main");
    const layoutType = ref(layout.value);

    onMounted(() => {
      // set the tab from previous
      tabIndex.value = localStorage.getItem(LS_BUILDER_TAB_NAME) || "main";
    });

    /**
     * Reset config
     * @param e
     */
    const reset = (e: Event) => {
      e.preventDefault();
      // remove existing saved config
      localStorage.removeItem(LS_CONFIG_NAME_KEY);
      window.location.reload();
    };

    /**
     * Set active tab when the tab get clicked
     * @param e
     */
    const setActiveTab = (e: Event) => {
      const target = e.target as HTMLInputElement;

      tabIndex.value = target.getAttribute("data-tab-index") as string;
      // keep active tab
      localStorage.setItem(LS_BUILDER_TAB_NAME, tabIndex.value);
    };

    const onThemeModeChange = (e: Event) => {
      const target = e.target as HTMLInputElement;

      storeBody.setLayoutConfigProperty("general.mode", target.value);

      storeTheme.setThemeMode(target.value as "light" | "dark" | "system");

      // save new config to localStorage
      localStorage.setItem(LS_CONFIG_NAME_KEY, JSON.stringify(config.value));
    };

    /**
     * Submit form
     * @param event
     */
    const submit = (event: Event) => {
      event.preventDefault();

      storeBody.setLayoutConfigProperty("general.layout", layoutType);

      // save new config to localStorage
      localStorage.setItem(LS_CONFIG_NAME_KEY, JSON.stringify(config.value));
      window.location.reload();
    };

    // Colores disponibles para el sidebar (11 colores)
    const sidebarColors = [
      { value: "yellow", label: "Amarillo", hex: "#FFE600" },
      { value: "dark-yellow", label: "Amarillo Oscuro", hex: "#F9B233" },
      { value: "orange", label: "Naranja", hex: "#F39200" },
      { value: "dark-green", label: "Verde Oscuro", hex: "#417A3C" },
      { value: "green", label: "Verde", hex: "#00B388" },
      { value: "light-green", label: "Verde Claro", hex: "#6FC06E" },
      { value: "pink", label: "Rosa", hex: "#E6007E" },
      { value: "dark-pink", label: "Rosa Oscuro", hex: "#A63A5A" },
      { value: "blue", label: "Azul", hex: "#0071B9" },
      { value: "dark-blue", label: "Azul Oscuro", hex: "#252364" },
      { value: "lime", label: "Lima", hex: "#8DC63F" },
    ];

    // Detectar el tema actual (light/dark)
    const currentTheme = computed(() => {
      // themeMode puede ser 'light', 'dark' o 'system'
      if (themeMode.value === "system") {
        return window.matchMedia("(prefers-color-scheme: dark)").matches
          ? "dark"
          : "light";
      }
      return themeMode.value;
    });

    const currentThemeLabel = computed(() =>
      currentTheme.value === "dark" ? "oscuro" : "claro"
    );

    // Saber qué color está seleccionado para el tema actual
    function isSidebarColorSelected(color: string) {
      const sidebarDefault = (config.value.sidebar?.default ?? {}) as any;
      if (currentTheme.value === "dark") {
        return (sidebarDefault.bgColorDark || "") === color;
      }
      return (sidebarDefault.bgColorLight || "") === color;
    }

    // Cambiar el color del sidebar para el tema actual
    function setSidebarColor(color: string) {
      const sidebarDefault = (config.value.sidebar?.default ?? {}) as any;
      if (color) {
        // Guardar el color en ambos temas para que se mantenga al cambiar de tema
        sidebarDefault.bgColorLight = color;
        sidebarDefault.bgColorDark = color;
      } else {
        // Si es por defecto, limpiar ambos
        sidebarDefault.bgColorLight = "";
        sidebarDefault.bgColorDark = "";
      }
      // Guardar en localStorage para persistencia inmediata
      localStorage.setItem(LS_CONFIG_NAME_KEY, JSON.stringify(config.value));
    }

    return {
      tabIndex,
      config,
      reset,
      setActiveTab,
      submit,
      themeMode,
      themeName,
      layout,
      layoutType,
      onThemeModeChange,
      getAssetPath,
      sidebarColors,
      currentTheme,
      currentThemeLabel,
      isSidebarColorSelected,
      setSidebarColor,
    };
  },
});
</script>