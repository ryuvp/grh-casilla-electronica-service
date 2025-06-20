<template>
  <!--begin::Modal - Create Api Key-->
  <div
    class="modal fade"
    id="kt_modal_modal3"
    ref="createAPIKeyModalRef"
    tabindex="-1"
    aria-hidden="true"
  >
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <!--begin::Modal content-->
      <div class="modal-content">
        <!--begin::Modal header-->
        <div class="modal-header" id="kt_modal_modal3_header">
          <!--begin::Modal title-->
          <h2>Crear API Key</h2>
          <!--end::Modal title-->

          <!--begin::Close-->
          <div
            class="btn btn-sm btn-icon btn-active-color-primary"
            data-bs-dismiss="modal"
          >
            <KTIcon icon-name="cross" icon-class="fs-1" />
          </div>
          <!--end::Close-->
        </div>
        <!--end::Modal header-->

        <!--begin::Form-->
        <VForm
          id="kt_modal_modal3_form"
          class="form"
          @submit="submit"
          :validation-schema="validationSchema"
        >
          <!--begin::Modal body-->
          <div class="modal-body py-10 px-lg-17">
            <!--begin::Scroll-->
            <div
              class="scroll-y me-n7 pe-7"
              id="kt_modal_modal3"
              data-kt-scroll="true"
              data-kt-scroll-activate="{default: false, lg: true}"
              data-kt-scroll-max-height="auto"
              data-kt-scroll-dependencies="#kt_modal_modal3_header"
              data-kt-scroll-wrappers="#kt_modal_modal3"
              data-kt-scroll-offset="300px"
            >

              <!--begin::Input group-->
              <div class="mb-5 fv-row">
                <!--begin::Label-->
                <label class="required fs-5 fw-semibold mb-2">Nombre de la API</label>
                <!--end::Label-->

                <!--begin::Input-->
                <Field
                  type="text"
                  class="form-control form-control-solid"
                  placeholder="Nombre de tu API"
                  name="apiName"
                  v-model="apiData.apiName"
                />
                <div class="fv-plugins-message-container">
                  <div class="fv-help-block">
                    <ErrorMessage name="apiName" />
                  </div>
                </div>
                <!--end::Input-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="d-flex flex-column mb-5 fv-row">
                <!--begin::Label-->
                <label class="required fs-5 fw-semibold mb-2"
                  >Descripción corta</label
                >
                <!--end::Label-->

                <!--begin::Input-->
                <Field
                  type="text"
                  class="form-control form-control-solid"
                  rows="3"
                  name="shortDescription"
                  placeholder="Describe tu API"
                  v-model="apiData.shortDescription"
                />
                <div class="fv-plugins-message-container">
                  <div class="fv-help-block">
                    <ErrorMessage name="shortDescription" />
                  </div>
                </div>
                <!--end::Input-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="d-flex flex-column mb-10 fv-row">
                <!--begin::Label-->
                <label class="required fs-5 fw-semibold mb-2">Categoría</label>
                <!--end::Label-->

                <!--begin::Select-->
                <Field
                  name="category"
                  data-control="select2"
                  data-hide-search="true"
                  data-placeholder="Selecciona una categoría..."
                  class="form-select form-select-solid"
                  as="select"
                  v-model="apiData.category"
                >
                  <option value="">Selecciona una categoría...</option>
                  <option value="1">CRM</option>
                  <option value="2">Project Alice</option>
                  <option value="3">Keenthemes</option>
                  <option value="4">General</option>
                </Field>
                <div class="fv-plugins-message-container">
                  <div class="fv-help-block">
                    <ErrorMessage name="category" />
                  </div>
                </div>
                <!--end::Select-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="mb-10">
                <!--begin::Heading-->
                <div class="mb-3">
                  <!--begin::Label-->
                  <label class="d-flex align-items-center fs-5 fw-semibold">
                    <span class="required">Especifica el método de tu API</span>

                    <i
                      class="fas fa-exclamation-circle ms-2 fs-7"
                      data-bs-toggle="tooltip"
                      title="Tus números de facturación se calcularán según el método de tu API"
                    ></i>
                  </label>
                  <!--end::Label-->

                  <!--begin::Description-->
                  <div class="fs-7 fw-semibold text-gray-500">
                    Si necesitas más información, revisa la planificación de presupuesto
                  </div>
                  <!--end::Description-->
                </div>
                <!--end::Heading-->

                <!--begin::Row-->
                <div class="fv-row">
                  <!--begin::Select-->
                  <Field
                    name="apiMethod"
                    data-control="select2"
                    data-hide-search="true"
                    data-placeholder="Selecciona un método..."
                    class="form-select form-select-solid"
                    as="select"
                    v-model="apiData.apiMethod"
                  >
                    <option value="">Selecciona un método de API...</option>
                    <option value="1">Open API</option>
                    <option value="2">SQL Call</option>
                    <option value="3">UI/UX</option>
                    <option value="4">Docs</option>
                  </Field>
                  <div class="fv-plugins-message-container">
                    <div class="fv-help-block">
                      <ErrorMessage name="apiMethod" />
                    </div>
                  </div>
                  <!--end::Select-->
                </div>
                <!--end::Row-->
              </div>
              <!--end::Input group-->
            </div>
            <!--end::Scroll-->
          </div>
          <!--end::Modal body-->

          <!--begin::Modal footer-->
          <div class="modal-footer flex-center">
            <!--begin::Button-->
            <button
              type="reset"
              id="kt_modal_modal3_cancel"
              class="btn btn-light me-3"
            >
              Descartar
            </button>
            <!--end::Button-->

            <!--begin::Button-->
            <button
              ref="submitButtonRef"
              type="submit"
              id="kt_modal_modal3_submit"
              class="btn btn-primary"
            >
              <span class="indicator-label"> Enviar </span>
              <span class="indicator-progress">
                Por favor espere...
                <span
                  class="spinner-border spinner-border-sm align-middle ms-2"
                ></span>
              </span>
            </button>
            <!--end::Button-->
          </div>
          <!--end::Modal footer-->
        </VForm>
        <!--end::Form-->
      </div>
      <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
  </div>
  <!--end::Modal - Create Api Key-->
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, ref } from "vue";
import { hideModal } from "@/core/helpers/modal";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import * as Yup from "yup";
import Swal from "sweetalert2/dist/sweetalert2.js";

interface APIData {
  apiName: string;
  shortDescription: string;
  category: string;
  apiMethod: string;
}

export default defineComponent({
  name: "create-api-key-modal",
  components: {
    ErrorMessage,
    Field,
    VForm,
  },
  setup() {
    const submitButtonRef = ref<null | HTMLButtonElement>(null);
    const modalRef = ref<null | HTMLElement>(null);
    const createAPIKeyModalRef = ref<null | HTMLElement>(null);

    const apiData = ref<APIData>({
      apiName: "",
      shortDescription: "",
      category: "",
      apiMethod: "",
    });

    const validationSchema = Yup.object().shape({
      apiName: Yup.string().required("El nombre de la API es obligatorio").label("Nombre de la API"),
      shortDescription: Yup.string().required("La descripción es obligatoria").label("Descripción"),
      category: Yup.string().required("La categoría es obligatoria").label("Categoría"),
      apiMethod: Yup.string().required("El método de la API es obligatorio").label("Método de la API"),
    });

    const submit = () => {
      if (!submitButtonRef.value) {
        return;
      }

      //Deshabilitar botón
      submitButtonRef.value.disabled = true;
      // Activar indicador
      submitButtonRef.value.setAttribute("data-kt-indicator", "on");

      setTimeout(() => {
        if (submitButtonRef.value) {
          submitButtonRef.value.disabled = false;

          submitButtonRef.value?.removeAttribute("data-kt-indicator");
        }

        Swal.fire({
          text: "¡El formulario se ha enviado correctamente!",
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok, entendido",
          heightAuto: false,
          customClass: {
            confirmButton: "btn btn-primary",
          },
        }).then(() => {
          hideModal(createAPIKeyModalRef.value);
        });
      }, 2000);
    };

    return {
      apiData,
      validationSchema,
      submit,
      submitButtonRef,
      modalRef,
      createAPIKeyModalRef,
      getAssetPath,
    };
  },
});
</script>
