<template>
  <div
    class="modal fade"
    id="kt_modal_formulario"
    ref="newTargetModalRef"
    tabindex="-1" 
    aria-hidden="true"
  >
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <!--begin::Modal content-->
      <div class="modal-content rounded">
        <!--begin::Modal header separado-->
        <div class="modal-header pb-0 border-0 justify-content-between align-items-center custom-modal-header">
          <h3 class="mb-0 fw-bold text-muted">Nuevo registro</h3>
          <!--begin::Close-->
          <button
            type="button"
            class="btn btn-icon btn-lg btn-light btn-active-color-danger rounded-circle shadow-sm"
            data-bs-dismiss="modal"
            aria-label="Cerrar"
            style="font-size: 1.5rem;"
          >
            <KTIcon icon-name="cross" icon-class="fs-1" />
          </button>
          <!--end::Close-->
        </div>
        <!--end::Modal header-->

        <!--begin::Modal body-->
        <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
          <!--begin:Form-->
          <el-form
            id="kt_modal_formulario_form"
            @submit.prevent="submit()"
            :model="modalejemplo"
            :rules="rules"
            ref="formRef"
            class="form"
          >
            <!--begin::Input group-->
            <div class="d-flex flex-column mb-8 fv-row">
              <!--begin::Label-->
              <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                <span class="required">Título</span>
                <i
                  class="fas fa-exclamation-circle ms-2 fs-7"
                  data-bs-toggle="tooltip"
                  title="Especifica un nombre para referencia futura"
                ></i>
              </label>
              <!--end::Label-->

              <el-form-item prop="nombre">
                <el-input
                  v-model="modalejemplo.nombre"
                  placeholder="Ingresa el título"
                  name="nombre"
                ></el-input>
              </el-form-item>
            </div>
            <!--end::Input group-->

            <!--begin::Input group-->
            <div class="row g-9 mb-8">
              <!--begin::Col-->
              <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Asignación</label>

                <el-form-item prop="asignacion">
                  <el-select
                    v-model="modalejemplo.asignacion"
                    placeholder="Selecciona un usuario"
                    name="asignacion"
                    as="select"
                  >
                    <el-option value="">Selecciona el usuario...</el-option>
                    <el-option label="Karina Clark" value="1"
                      >Karina Clark</el-option
                    >
                    <el-option label="Robert Doe" value="2"
                      >Robert Doe</el-option
                    >
                    <el-option label="Niel Owen" value="3">Niel Owen</el-option>
                    <el-option label="Olivia Wild" value="4"
                      >Olivia Wild</el-option
                    >
                    <el-option label="Sean Bean" value="5">Sean Bean</el-option>
                  </el-select>
                </el-form-item>
              </div>
              <!--end::Col-->

              <!--begin::Col-->
              <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Fecha</label>

                <!--begin::Input-->
                <div class="position-relative align-items-center">
                  <!--begin::Datepicker-->
                  <el-form-item prop="fecha">
                    <el-date-picker
                      v-model="modalejemplo.fecha"
                      type="date"
                      placeholder="Selecciona una fecha"
                      :teleported="false"
                      popper-class="override-styles"
                      name="fecha"
                    />
                  </el-form-item>
                  <!--end::Datepicker-->
                </div>
                <!--end::Input-->
              </div>
              <!--end::Col-->
            </div>
            <!--end::Input group-->

            <!--begin::Input group-->
            <div class="d-flex flex-column mb-8">
              <label class="fs-6 fw-semibold mb-2">Detalles</label>

              <el-form-item prop="detalles">
                <el-input
                  v-model="modalejemplo.detalles"
                  type="textarea"
                  :rows="3"
                  name="detalles"
                  placeholder="Escribe los detalles"
                />
              </el-form-item>
            </div>
            <!--end::Input group-->

            <!--begin::Input group-->
            <div class="d-flex flex-column mb-8 fv-row">
              <!--begin::Label-->
              <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                <span class="required">Etiquetas</span>
                <i
                  class="fas fa-exclamation-circle ms-2 fs-7"
                  data-bs-toggle="tooltip"
                  title="Especifica la prioridad"
                ></i>
              </label>
              <!--end::Label-->

              <el-form-item prop="etiquetas">
                <el-select
                  v-model="modalejemplo.etiquetas"
                  multiple
                  filterable
                  allow-create
                  default-first-option
                  placeholder="Elige etiquetas para tu objetivo"
                >
                  <el-option label="Importante" value="important"> </el-option>
                  <el-option label="Urgente" value="urgent"> </el-option>
                  <el-option label="Alta" value="high"> </el-option>
                  <el-option label="Baja" value="low"> </el-option>
                  <el-option label="Media" value="medium"> </el-option>
                </el-select>
              </el-form-item>
            </div>
            <!--end::Input group-->

            <!--begin::Input group-->
            <div class="mb-15 fv-row">
              <!--begin::Wrapper-->
              <div class="d-flex flex-stack">
                <!--begin::Label-->
                <div class="fw-semibold me-5">
                  <label class="fs-6">Notificaciones</label>

                  <div class="fs-7 text-gray-500">
                   ¿Estás de acuerdo en recibir notificaciones por correo electrónico y/o mensaje de texto?
                  </div>
                </div>
                <!--end::Label-->

                <!--begin::Checkboxes-->
                <div class="d-flex align-items-center">
                  <!--begin::Checkbox-->
                  <label
                    class="form-check form-check-custom form-check-solid me-10"
                  >
                    <input
                      class="form-check-input h-20px w-20px"
                      type="checkbox"
                      name="communication[]"
                      value="email"
                      checked
                    />

                    <span class="form-check-label fw-semibold"> Correo electrónico </span>
                  </label>
                  <!--end::Checkbox-->

                  <!--begin::Checkbox-->
                  <label class="form-check form-check-custom form-check-solid">
                    <input
                      class="form-check-input h-20px w-20px"
                      type="checkbox"
                      name="communication[]"
                      value="phone"
                    />

                    <span class="form-check-label fw-semibold"> Celular </span>
                  </label>
                  <!--end::Checkbox-->
                </div>
                <!--end::Checkboxes-->
              </div>
              <!--end::Wrapper-->
            </div>
            <!--end::Input group-->

            <!--begin::Actions-->
            <div class="text-center">
              <button
                type="reset"
                id="kt_modal_new_target_cancel"
                class="btn btn-light me-3"
              >
                Cancelar
              </button>

              <!--begin::Button-->
              <button
                :data-kt-indicator="loading ? 'on' : null"
                class="btn btn-lg btn-primary"
                type="submit"
              >
                <span v-if="!loading" class="indicator-label">
                  Enviar
                  <KTIcon icon-name="arrow-right" icon-class="fs-3 ms-2 me-0" />
                </span>
                <span v-if="loading" class="indicator-progress">
                  Por favor espere...
                  <span
                    class="spinner-border spinner-border-sm align-middle ms-2"
                  ></span>
                </span>
              </button>
              <!--end::Button-->
            </div>
            <!--end::Actions-->
          </el-form>
          <!--end:Form-->
        </div>
        <!--end::Modal body-->
      </div>
      <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
  </div>
  <!--end::Modal - New Target-->
</template>

<style lang="scss">
.el-select {
  width: 100%;
}

.el-date-editor.el-input,
.el-date-editor.el-input__inner {
  width: 100%;
}

/* Cabecera separada visualmente */
.custom-modal-header {
  border-bottom: 1px solid #e4e6ef !important;
  background: #bebec03d;
  padding-top: 1.25rem !important;
  padding-bottom: 1.25rem !important;
  margin-bottom: 1.5rem;
}

/* Botón de cerrar destacado */
.custom-modal-header .btn.btn-icon {
  color: #f1416c !important;
  background: #fff5f8 !important;
  border: 1px solid #f1416c1a !important;
  transition: background 0.2s, color 0.2s;
}
.custom-modal-header .btn.btn-icon:hover,
.custom-modal-header .btn.btn-icon:focus {
  background: #f1416c !important;
  color: #fff !important;
  border-color: #f1416c !important;
}

.override-styles {
  z-index: 99999 !important;
  pointer-events: initial;
}
</style>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, ref } from "vue";
import { hideModal } from "@/core/helpers/modal";
import Swal from "sweetalert2/dist/sweetalert2.js";

interface NewAddressData {
  nombre: string;
  asignacion: string;
  fecha: string;
  detalles: string;
  etiquetas: Array<string>;
}

export default defineComponent({
  name: "new-target-modal",
  components: {},
  setup() {
    const formRef = ref<null | HTMLFormElement>(null);
    const newTargetModalRef = ref<null | HTMLElement>(null);
    const loading = ref<boolean>(false);

    const modalejemplo = ref<NewAddressData>({
      nombre: "",
      asignacion: "",
      fecha: "",
      detalles: "",
      etiquetas: ["important", "urgent"],
    });

    const rules = ref({
      nombre: [
        {
          required: true,
          message: "Por favor ingresa el nombre de la actividad",
          trigger: "blur",
        },
      ],
      asignacion: [
        {
          required: true,
          message: "Por favor selecciona el usuario asignado",
          trigger: "change",
        },
      ],
      fecha: [
        {
          required: true,
          message: "Por favor selecciona la fecha",
          trigger: "change",
        },
      ],
      etiquetas: [
        {
          required: true,
          message: "Por favor selecciona al menos una etiqueta",
          trigger: "change",
        },
      ],
    });

    const submit = () => {
      if (!formRef.value) {
        return;
      }

      formRef.value.validate((valid: boolean) => {
        if (valid) {
          loading.value = true;

          setTimeout(() => {
            loading.value = false;

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
              hideModal(newTargetModalRef.value);
            });
          }, 2000);
        } else {
          Swal.fire({
            text: "Lo sentimos, parece que hay algunos errores. Por favor, inténtalo de nuevo.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok, entendido",
            heightAuto: false,
            customClass: {
              confirmButton: "btn btn-primary",
            },
          });
          return false;
        }
      });
    };

    return {
      modalejemplo,
      submit,
      loading,
      formRef,
      rules,
      newTargetModalRef,
      getAssetPath,
    };
  },
});
</script>
