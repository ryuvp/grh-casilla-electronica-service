<template>
  <!--begin::Modal - New Address-->
  <div
    class="modal fade"
    ref="newAddressModalRef"
    id="kt_modal_modal2"
    tabindex="-1"
    aria-hidden="true"
  >
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <!--begin::Modal content-->
      <div class="modal-content">
        <!--begin::Form-->
        <VForm
          class="form"
          id="kt_modal_modal2_form"
          @submit="enviar"
          :validation-schema="esquemaValidacion"
          v-slot="{ errors }"
        >
          <!--begin::Modal header-->
          <div class="modal-header" id="kt_modal_modal2_header">
            <!--begin::Modal title-->
            <h2>Agregar nueva dirección</h2>
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

          <!--begin::Modal body-->
          <div class="modal-body py-10 px-lg-17">
            <!--begin::Scroll-->
            <div
              class="scroll-y me-n7 pe-7"
              id="kt_modal_modal2_scroll"
              data-kt-scroll="true"
              data-kt-scroll-activate="{default: false, lg: true}"
              data-kt-scroll-max-height="auto"
              data-kt-scroll-dependencies="#kt_modal_modal2_header"
              data-kt-scroll-wrappers="#kt_modal_modal2_scroll"
              data-kt-scroll-offset="300px"
            >
              <!--begin::Notice-->
              <div
                class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6"
              >
                <KTIcon
                  icon-name="information-5"
                  icon-class="fs-2tx text-warning me-4"
                />
                <!--begin::Wrapper-->
                <div class="d-flex flex-stack flex-grow-1">
                  <!--begin::Content-->
                  <div class="fw-semibold">
                    <h4 class="text-gray-800 fw-bold">Advertencia</h4>
                    <div class="fs-6 text-gray-600">
                      Actualizar la dirección puede afectar tu
                      <a href="#">ubicación fiscal</a>
                    </div>
                  </div>
                  <!--end::Content-->
                </div>
                <!--end::Wrapper-->
              </div>
              <!--end::Notice-->

              <!--begin::Input group-->
              <div class="row mb-5">
                <!--begin::Col-->
                <div class="col-md-6 fv-row">
                  <!--begin::Label-->
                  <label class="required fs-5 fw-semibold mb-2"
                    >Nombre</label
                  >
                  <!--end::Label-->

                  <!--begin::Input-->
                  <Field
                    type="text"
                    class="form-control"
                    :class="errors.firstName ? 'is-invalid' : ''"
                    placeholder=""
                    name="firstName"
                    v-model="datosNuevaDireccion.firstName"
                  />
                  <ErrorMessage class="invalid-feedback" name="firstName" />
                  <!--end::Input-->
                </div>
                <!--end::Col-->

                <!--begin::Col-->
                <div class="col-md-6 fv-row">
                  <!--end::Label-->
                  <label class="required fs-5 fw-semibold mb-2"
                    >Apellido</label
                  >
                  <!--end::Label-->

                  <!--end::Input-->
                  <Field
                    type="text"
                    class="form-control"
                    :class="errors.lastName ? 'is-invalid' : ''"
                    placeholder=""
                    name="lastName"
                    v-model="datosNuevaDireccion.lastName"
                  />
                  <ErrorMessage class="invalid-feedback" name="lastName" />
                  <!--end::Input-->
                </div>
                <!--end::Col-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="d-flex flex-column mb-5 fv-row">
                <!--begin::Label-->
                <label class="d-flex align-items-center fs-5 fw-semibold mb-2">
                  <span class="required">País</span>
                  <i
                    class="fas fa-exclamation-circle ms-2 fs-7"
                    data-bs-toggle="tooltip"
                    title="Tus estados de pago pueden variar según el país seleccionado"
                  ></i>
                </label>
                <!--end::Label-->

                <!--begin::Select-->
                <Field
                  name="country"
                  class="form-select"
                  :class="errors.country ? 'is-invalid' : ''"
                  as="select"
                  v-model="datosNuevaDireccion.country"
                >
                  <option value="">Selecciona un país...</option>
                  <option
                    v-for="(item, i) in countries"
                    :key="`countries-select-option-${i}`"
                    :value="item.code"
                  >
                    {{ item.name }}
                  </option>
                </Field>

                <ErrorMessage class="invalid-feedback" name="country" />
                <!--end::Select-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="d-flex flex-column mb-5 fv-row">
                <!--begin::Label-->
                <label class="required fs-5 fw-semibold mb-2"
                  >Dirección línea 1</label
                >
                <!--end::Label-->

                <!--begin::Input-->
                <Field
                  class="form-control"
                  :class="errors.address1 ? 'is-invalid' : ''"
                  placeholder=""
                  name="address1"
                  v-model="datosNuevaDireccion.address1"
                />
                <ErrorMessage class="invalid-feedback" name="address1" />
                <!--end::Input-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="d-flex flex-column mb-5 fv-row">
                <!--begin::Label-->
                <label class="required fs-5 fw-semibold mb-2"
                  >Dirección línea 2</label
                >
                <!--end::Label-->

                <!--begin::Input-->
                <Field
                  class="form-control"
                  :class="errors.address2 ? 'is-invalid' : ''"
                  placeholder=""
                  name="address2"
                  v-model="datosNuevaDireccion.address2"
                />
                <ErrorMessage class="invalid-feedback" name="address2" />
                <!--end::Input-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="d-flex flex-column mb-5 fv-row">
                <!--begin::Label-->
                <label class="fs-5 fw-semibold mb-2">Ciudad</label>
                <!--end::Label-->

                <!--begin::Input-->
                <Field
                  class="form-control"
                  :class="errors.town ? 'is-invalid' : ''"
                  placeholder=""
                  name="town"
                  v-model="datosNuevaDireccion.town"
                />
                <ErrorMessage class="invalid-feedback" name="town" />
                <!--end::Input-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="row g-9 mb-5">
                <!--begin::Col-->
                <div class="col-md-6 fv-row">
                  <!--begin::Label-->
                  <label class="fs-5 fw-semibold mb-2">Estado / Provincia</label>
                  <!--end::Label-->

                  <!--begin::Input-->
                  <Field
                    class="form-control"
                    :class="errors.state ? 'is-invalid' : ''"
                    placeholder=""
                    name="state"
                    v-model="datosNuevaDireccion.state"
                  />
                  <ErrorMessage class="invalid-feedback" name="state" />
                  <!--end::Input-->
                </div>
                <!--end::Col-->

                <!--begin::Col-->
                <div class="col-md-6 fv-row">
                  <!--begin::Label-->
                  <label class="fs-5 fw-semibold mb-2">Código postal</label>
                  <!--end::Label-->

                  <!--begin::Input-->
                  <Field
                    class="form-control"
                    :class="errors.postCode ? 'is-invalid' : ''"
                    placeholder=""
                    name="postCode"
                    v-model="datosNuevaDireccion.postCode"
                  />
                  <ErrorMessage class="invalid-feedback" name="postCode" />
                  <!--end::Input-->
                </div>
                <!--end::Col-->
              </div>
              <!--end::Input group-->

              <!--begin::Input group-->
              <div class="fv-row mb-5">
                <!--begin::Wrapper-->
                <div class="d-flex flex-stack">
                  <!--begin::Label-->
                  <div class="me-5">
                    <!--begin::Label-->
                    <label class="fs-5 fw-semibold"
                      >¿Estás de acuerdo con los términos y condiciones?</label
                    >
                    <!--end::Label-->

                    <!--begin::Input-->
                    <div class="fs-7 fw-semibold text-gray-500">
                      Si necesitas más información, revisa la planificación de presupuesto
                    </div>
                    <!--end::Input-->
                  </div>
                  <!--end::Label-->

                  <!--begin::Switch-->
                  <label
                    class="form-check form-switch form-check-custom form-check-solid"
                  >
                    <!--begin::Input-->
                    <Field
                      class="form-check-input"
                      :class="errors.postCode ? 'is-invalid' : ''"
                      name="billing"
                      type="checkbox"
                      value="1"
                      checked
                    />
                    <ErrorMessage class="invalid-feedback" name="billing" />
                    <!--end::Input-->

                    <!--begin::Label-->
                    <span class="form-check-label fw-semibold text-gray-500">
                      Sí
                    </span>
                    <!--end::Label-->
                  </label>
                  <!--end::Switch-->
                </div>
                <!--begin::Wrapper-->
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
              id="kt_modal_modal2_cancel"
              class="btn btn-light me-3"
            >
              Descartar
            </button>
            <!--end::Button-->

            <!--begin::Button-->
            <button
              ref="submitButtonRef"
              type="submit"
              id="kt_modal_modal2_submit"
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
    </div>
  </div>
  <!--end::Modal - New Address-->
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, ref } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import { hideModal } from "@/core/helpers/modal";
import Swal from "sweetalert2/dist/sweetalert2.js";
import * as Yup from "yup";
import { countries } from "@/core/data/countries";

interface NewAddressData {
  firstName: string;
  lastName: string;
  country: string;
  address1: string;
  address2: string;
  town: string;
  state: string;
  postCode: string;
}

export default defineComponent({
  name: "modal2",
  components: {
    ErrorMessage,
    Field,
    VForm,
  },
  setup() {
    const submitButtonRef = ref<null | HTMLButtonElement>(null);
    const newAddressModalRef = ref<null | HTMLElement>(null);

    const datosNuevaDireccion = ref<NewAddressData>({
      firstName: "",
      lastName: "",
      country: "",
      address1: "",
      address2: "",
      town: "",
      state: "",
      postCode: "",
    });

    const esquemaValidacion = Yup.object().shape({
      firstName: Yup.string().required("El nombre es obligatorio").label("Nombre"),
      lastName: Yup.string().required("El apellido es obligatorio").label("Apellido"),
      country: Yup.string().required("El país es obligatorio").label("País"),
      address1: Yup.string().required("La dirección línea 1 es obligatoria").label("Dirección línea 1"),
      address2: Yup.string().required("La dirección línea 2 es obligatoria").label("Dirección línea 2"),
      town: Yup.string().required("La ciudad es obligatoria").label("Ciudad"),
      state: Yup.string().required("El estado/provincia es obligatorio").label("Estado/Provincia"),
      postCode: Yup.string().required("El código postal es obligatorio").label("Código postal"),
    });

    const enviar = () => {
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
          hideModal(newAddressModalRef.value);
        });
      }, 2000);
    };

    return {
      datosNuevaDireccion,
      esquemaValidacion,
      enviar,
      submitButtonRef,
      newAddressModalRef,
      getAssetPath,
      countries,
    };
  },
});
</script>
