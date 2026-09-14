<template>
  <!-- Modal para redactar mensaje -->
  <div
    ref="modalRef"
    class="modal fade"
    tabindex="-1"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered" style="min-height: 80vh; min-width: 70vw;">
      <div class="modal-content rounded-3 border-0 shadow">
        <el-form
          ref="formRef"
          :model="formData"
          :rules="rules"
          class="form"
          label-position="top"
          @submit.prevent="submit"
        >
          <HeaderColor
            :accion="isEdit ? 'editar' : 'agregar'"
            :bg-color="isEdit ? '#fff3cd' : '#d1e7dd'"
            :text-color="isEdit ? '#664d03' : '#08632a'"
            data-bs-dismiss="modal"
            aria-label="Cerrar"
          >
            <i class="bi bi-envelope-plus me-2 fs-2" :style="{ color: isEdit ? '#664d03' : '#08632a' }"></i>
            <span class="text-uppercase">Nuevo Mensaje</span>
          </HeaderColor>

          <!-- Barra de botones -->
          <div class="bg-light bg-opacity-25 py-1 px-5">
            <div class="d-flex justify-content-end gap-3 w-100">
              <!-- Botón de adjuntar archivo -->
              <button type="button" class="btn btn-light btn-sm px-8" @click="triggerFileInput">
                <i class="bi bi-paperclip me-2"></i>Adjuntar archivo
              </button>
              <input
                id="fileInput"
                ref="fileInput"
                type="file"
                class="d-none"
                multiple
                @change="handleFileChange"
              />

              <!-- Botón de prioridad (tres puntitos) -->
              <div class="dropdown">
                <button
                  id="dropdownPriority"
                  class="btn btn-light btn-sm px-8"
                  type="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                  @click="toggleDropdown"
                >
                  <i class="bi bi-three-dots-vertical"></i> Prioridad
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownPriority">
                  <li><a class="dropdown-item" href="#" @click="setPriority('alta')">Alta</a></li>
                  <li><a class="dropdown-item" href="#" @click="setPriority('media')">Media</a></li>
                  <li><a class="dropdown-item" href="#" @click="setPriority('baja')">Baja</a></li>
                </ul>
              </div>

              <!-- Botón de guardar en borrador -->
              <button type="button" class="btn btn-light btn-sm px-8" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-2"></i>Guardar en borrador
              </button>

              <!-- Botón de enviar -->
              <button type="submit" class="btn btn-primary btn-sm px-8" :disabled="loading">
                <span v-if="!loading" class="indicator-label">
                  <i class="bi bi-send me-2"></i>Enviar
                </span>
                <span v-else class="indicator-progress">
                  <span class="spinner-border spinner-border-sm me-2"></span>Guardando...
                </span>
              </button>
            </div>
          </div>

          <!-- Cuerpo del modal -->
          <div class="modal-body px-0 py-1">
            <div class="container">
              <!-- Destinatarios (multiple, interno y/o externo por DNI) -->
              <el-form-item label="Destinatarios" prop="destinatariosSeleccionados">
                <el-select
                  v-model="destinatariosSeleccionados"
                  placeholder="Busque por nombre/DNI (interno) o ingrese un DNI de 8 dígitos (ciudadano/administrado)"
                  filterable
                  remote
                  multiple
                  value-key="key"
                  :remote-method="buscarDestinatarios"
                  :loading="loadingUsuarios"
                  class="w-100 text-uppercase"
                  size="large"
                  clearable
                >
                  <el-option
                    v-for="option in destinatariosOptions"
                    :key="option.key"
                    :label="option.label"
                    :value="option"
                  />
                </el-select>
                <div v-if="dniValidationError" class="text-danger small mt-1">
                  {{ dniValidationError }}
                </div>
              </el-form-item>

              <!-- Asunto -->
              <el-form-item label="Asunto" prop="asunto">
                <el-input
                  v-model="formData.asunto"
                  type="text"
                  placeholder="Ingrese el asunto del mensaje"
                  class="w-100 text-uppercase"
                  size="large"
                  clearable
                />
              </el-form-item>
            </div>

            <div v-if="archivosSubidos.length > 0" class="mb-3 px-5">
              <div class="d-flex flex-wrap gap-2">
                <div
                  v-for="(file, index) in archivosSubidos"
                  :key="file.id"
                  class="card p-2 mb-2 bg-light bg-opacity-50"
                  style="width: 200px; max-height: 200px; overflow: hidden;"
                >
                  <div class="d-flex align-items-center">
                    <div class="badge text-white text-uppercase fw-bold py-3" :class="getFileIconClass(file)">
                      {{ file.extension }}
                    </div>
                    <div class="ms-2">
                      <p class="text-truncate text-muted m-0" style="font-size: 12px; max-width: 140px;" :title="file.nombre_original">
                        {{ file.nombre_original }}
                      </p>
                      <p class="d-flex justify-content-between m-0 w-100">
                        <span class="text-muted" style="font-size: 10px;">{{ formatFileSize(file.tamanio) }}</span>
                        <a class="text-danger ms-2 px-2" style="cursor: pointer; font-size: 11px;" @click="removeUploadedFile(index)">
                          <i class="bi bi-x-circle"></i>
                        </a>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Progreso -->
            <div v-if="archivosCargando > 0" class="text-muted px-5">
              Subiendo {{ archivosCargando }} archivo(s)...
            </div>
            <!-- Contenido -->
            <div class="mb-3 px-5">
              <Editor v-model="formData.contenido" />
            </div>
          </div>
        </el-form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Modal } from 'bootstrap'
import Swal from 'sweetalert2'

import HeaderColor from '@/components/tabla/HeaderColor.vue'
import Editor from '@/components/ckeditor/Editor.vue'

import { useMensajesStore } from '@/stores/mensajes/mensajesStore'
import { useFileStore } from '@/stores/files/filesStore'
import useDesignacionStore from '@/stores/designaciones/designacionStore'
import useCasillaStore from '@/stores/casillas/casillasPaginadoStore'
import useAuthStore from '@/stores/auth/authStore'

const store = useMensajesStore()
const fileStore = useFileStore()
const designacionStore = useDesignacionStore()
const casillaStore = useCasillaStore()
const authStore = useAuthStore()

// Identidad del trabajador autenticado: no puede notificarse a si mismo, ni
// siquiera "como externo" con su propio DNI (equivale a derivarse un
// documento a uno mismo, tampoco permitido en el resto del flujo del SGD).
const miDesignacionId = () => authStore.userData?.designacion_logeada?.id || authStore.userData?.designacion_logeada_id
const miDni = () => authStore.userData?.numero_documento

// Peru: DNI valido = 8 digitos numericos.
const DNI_REGEX = /^\d{8}$/

const props = defineProps({ item: { type: Object, default: null } })

const modalRef = ref(null)
const modal = ref(null)

// Destinatarios elegidos en el multi-select: cada opcion es interna
// ({ key, label, tipo:'interno', casillaId }) o externa, ya existente
// ({ key, label, tipo:'externo', casillaId, dni }) o por crear
// ({ key, label, tipo:'externo', dni, nuevo:true }).
const destinatariosSeleccionados = ref([])
const dniValidationError = ref('')

const abrir = () => {
  destinatariosSeleccionados.value = []
  dniValidationError.value = ''
  modal.value?.show()
  sugerirDestinatarioDelExpediente()
}

const cerrar = async () => {
  /* for (const file of archivosSubidos.value) {
    if (file.temporal) await fileStore.eliminarArchivo(file.id)
  } */
  archivosSubidos.value = []
  modal.value?.hide()
}
onMounted(() => {
  if (modalRef.value) modal.value = new Modal(modalRef.value)
})
defineExpose({ abrir, cerrar })

const formRef = ref(null)
const formData = ref({ ...store.default })
const loading = ref(false)
const isEdit = computed(() => !!props.item?.id)

const archivosSubidos = ref([])
const archivosCargando = ref(0)

const validarDestinatarios = (rule, value, callback) => {
  if (!destinatariosSeleccionados.value.length) {
    callback(new Error('Debe seleccionar al menos un destinatario'))
    return
  }
  callback()
}

const rules = computed(() => ({
  destinatariosSeleccionados : [{ validator: validarDestinatarios, trigger: 'change' }],
  asunto                     : [{ required: true, message: 'El asunto es obligatorio', trigger: 'blur' }],
}))

const submit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    const data = { ...formData.value }
    delete data.casilla_destino_id

    // Destinatarios ya resueltos a una casilla existente (interna o externa).
    data.casilla_destino_ids = destinatariosSeleccionados.value
      .filter(d => d.casillaId)
      .map(d => d.casillaId)

    // Destinatarios externos nuevos (solo se conoce el DNI, aun sin casilla):
    // el backend resuelve o crea la casilla correspondiente de forma atomica.
    data.administrados_externos = destinatariosSeleccionados.value
      .filter(d => !d.casillaId && d.dni)
      .map(d => ({ dni: d.dni, persona_id: d.personaId || null, nombre: d.label || null }))

    data.archivo_ids = archivosSubidos.value.map(f => f.id)

    await store.createMensaje(data)
    await fileStore.marcarPermanentes(data.archivo_ids)
    await store.fetchCounts()

    Swal.fire({ icon: 'success', title: 'Éxito', text: 'Mensaje enviado correctamente.' })
    cerrar()
    formData.value = { ...store.default }
    destinatariosSeleccionados.value = []
    archivosSubidos.value = []
  } catch (error) {
    console.error('Error al enviar:', error)
    Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un problema al enviar.' })
  }
}


const fileInput = ref(null)
const triggerFileInput = () => fileInput.value?.click()

const handleFileChange = async (event) => {
  const files = Array.from(event.target.files || [])
  for (const file of files) {
    if (!(file instanceof File)) {
      console.warn('Archivo inválido:', file)
      continue
    }

    archivosCargando.value++

    try {
      const result = await fileStore.subirArchivo(file)

      archivosSubidos.value.push(result.data)
    } catch (err) {
      console.error('Error subiendo archivo:', file.name, err)
    } finally {
      archivosCargando.value--
    }
  }
}



const removeUploadedFile = async (index) => {
  const file = archivosSubidos.value[index]
  await fileStore.eliminarArchivo(file.id)
  archivosSubidos.value.splice(index, 1)
}

const formatFileSize = (bytes) => {
  const kb = bytes / 1024
  return kb < 1024 ? `${kb.toFixed(2)} KB` : `${(kb / 1024).toFixed(2)} MB`
}
const getFileIconClass = (file) => {
  const classes = {
    ods     : 'bg-success', doc     : 'bg-primary', docx    : 'bg-primary',
    xls     : 'bg-success', xlsx    : 'bg-success', ppt     : 'bg-warning',
    pptx    : 'bg-warning', mp4     : 'bg-primary', pdf     : 'bg-warning',
    jpg     : 'bg-info', png     : 'bg-info', unknown : 'bg-danger',
  }
  return classes[file.extension] || 'bg-dark'
}

const destinatariosOptions = ref([])
const loadingUsuarios = ref(false)
let timeout = null

// Busca la casilla externa activa ya registrada para un DNI (ciudadano/administrado).
// Usa getSome (metodo canonico PMSG para resolver un lote/registro puntual sin
// tocar el listado principal del store), evitando peticiones ad-hoc.
const buscarCasillaExternaPorDni = async (dni) => {
  try {
    const response = await casillaStore.getSome({ dni, tipo: 'externo' })
    const casilla = response?.data?.data?.[0] || null
    return casilla
  } catch (error) {
    console.error('Error buscando casilla externa por DNI:', error)
    return null
  }
}

// Busqueda mixta de destinatarios: internos (por nombre/DNI de personal via
// Auth Service) y externos (por DNI de 8 digitos de un ciudadano/administrado).
const buscarDestinatarios = (query) => {
  clearTimeout(timeout)
  dniValidationError.value = ''

  const search = String(query || '').trim()
  if (!search || search.length < 2) {
    destinatariosOptions.value = []
    loadingUsuarios.value = false
    return
  }

  timeout = setTimeout(async () => {
    loadingUsuarios.value = true
    try {
      const esNumerico = /^\d+$/.test(search)

      // No puede notificarse a si mismo, ni siquiera "como externo" con su propio DNI.
      if (esNumerico && search.length === 8 && search === miDni()) {
        dniValidationError.value = 'No puede notificarse a sí mismo.'
        destinatariosOptions.value = []
        return
      }

      if (esNumerico && search.length === 8) {
        // Candidato a DNI externo: prioriza resolver casilla externa existente.
        // El nombre se resuelve contra Auth Service (no solo nombre_externo de la
        // casilla, que puede no estar registrado todavia) para poder confirmar
        // visualmente que el DNI corresponde a la persona correcta antes de enviar.
        const [casillaExterna, usuarioAuth, internosSinFiltrar] = await Promise.all([
          buscarCasillaExternaPorDni(search),
          designacionStore.buscarUsuarioPorDni(search),
          designacionStore.searchDestinatarios(search),
        ])
        const internos = internosSinFiltrar.filter(item => item.designacionId !== miDesignacionId())

        const nombreResuelto = casillaExterna?.nombre_externo
          || (usuarioAuth ? `${usuarioAuth.nombre || ''} ${usuarioAuth.apellido || ''}`.trim() : '')

        const externoOption = casillaExterna
          ? {
              key      : `externo-${casillaExterna.id}`,
              casillaId: casillaExterna.id,
              dni      : search,
              tipo     : 'externo',
              label    : `${nombreResuelto || 'Administrado (sin nombre registrado)'} - Casilla DNI ${search}`,
            }
          : {
              key   : `externo-nuevo-${search}`,
              dni   : search,
              tipo  : 'externo',
              nuevo : true,
              label : nombreResuelto
                ? `${nombreResuelto} - DNI ${search} (casilla nueva)`
                : `DNI ${search} sin registro previo (verifique antes de enviar)`,
            }

        destinatariosOptions.value = [externoOption, ...internos]
      } else if (esNumerico && search.length !== 8) {
        dniValidationError.value = 'El DNI debe tener exactamente 8 dígitos'
        const internos = await designacionStore.searchDestinatarios(search)
        destinatariosOptions.value = internos.filter(item => item.designacionId !== miDesignacionId())
      } else {
        const internos = await designacionStore.searchDestinatarios(search)
        destinatariosOptions.value = internos.filter(item => item.designacionId !== miDesignacionId())
      }
    } catch (error) {
      console.error('Error buscando destinatarios:', error)
      destinatariosOptions.value = []
    } finally {
      loadingUsuarios.value = false
    }
  }, 500)
}

// Auto-sugerencia: si el tramite/expediente que origina el envio trae el DNI
// de un posible destinatario (props.item.administrado_dni), se busca su
// casilla externa y se deja como SUGERENCIA visible en el desplegable, sin
// pre-seleccionarla.
//
// Importante: el documento no necesariamente se notifica a quien inicio el
// tramite (puede ir dirigido a otra persona, otra dependencia, o a varios
// destinatarios distintos del solicitante). Por eso esta funcion nunca agrega
// un destinatario por si sola: solo acerca la opcion para que el usuario la
// revise y la seleccione manualmente si en efecto corresponde notificar al
// administrado que inicio el tramite.
const sugerirDestinatarioDelExpediente = async () => {
  const dniSugerido = props.item?.administrado_dni
  if (!dniSugerido || !DNI_REGEX.test(dniSugerido)) return
  if (dniSugerido === miDni()) return // no puede notificarse a si mismo

  const [casillaExterna, usuarioAuth] = await Promise.all([
    buscarCasillaExternaPorDni(dniSugerido),
    designacionStore.buscarUsuarioPorDni(dniSugerido),
  ])

  const nombreResuelto = casillaExterna?.nombre_externo
    || (usuarioAuth ? `${usuarioAuth.nombre || ''} ${usuarioAuth.apellido || ''}`.trim() : '')

  if (casillaExterna) {
    destinatariosOptions.value = [{
      key      : `externo-${casillaExterna.id}`,
      casillaId: casillaExterna.id,
      dni      : dniSugerido,
      tipo     : 'externo',
      label    : `Sugerido (administrado que inició el trámite) - ${nombreResuelto || 'Administrado sin nombre registrado'} - Casilla DNI ${dniSugerido}`,
    }]
  } else {
    destinatariosOptions.value = [{
      key   : `externo-nuevo-${dniSugerido}`,
      dni   : dniSugerido,
      tipo  : 'externo',
      nuevo : true,
      label : nombreResuelto
        ? `Sugerido (administrado que inició el trámite) - ${nombreResuelto} - DNI ${dniSugerido} (aún sin casilla, se creará al enviar)`
        : `Administrado que inició el trámite - DNI ${dniSugerido} sin nombre registrado (verifique antes de enviar)`,
    }]
  }
}

// --------------------------- Prioridad ---------------------------
const priority = ref('normal')
const setPriority = (value) => {
  priority.value = value
  const btn = document.getElementById('dropdownPriority')
  btn?.classList.remove('btn-light', 'btn-danger', 'btn-warning', 'btn-success')
  if (value === 'alta') btn?.classList.add('btn-danger')
  else if (value === 'media') btn?.classList.add('btn-warning')
  else btn?.classList.add('btn-success')
}
</script>

<style scoped>
.modal.show {
  display: block;
  background-color: rgba(0, 0, 0, 0.5);
}

.dropdown-menu.show {
  display: block;
}

.card {
  width: 150px;
  max-height: 150px;
  overflow: hidden;
}
</style>
