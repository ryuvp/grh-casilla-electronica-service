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
              <!-- Destinatario -->
              <el-form-item label="Destinatario" prop="usuario_destino_id">
                <el-select
                  v-model="formData.usuario_destino_id"
                  placeholder="Busque y seleccione el destinatario"
                  filterable
                  remote
                  :remote-method="buscarUsuarios"
                  :loading="loadingUsuarios"
                  class="w-100 text-uppercase"
                  size="large"
                  clearable
                >
                  <el-option
                    v-for="usuario in usuariosStore.list"
                    :key="usuario.id"
                    :label="`${usuario.nombre} ${usuario.apellido} (${usuario.numero_documento})`"
                    :value="usuario.id"
                  />
                </el-select>
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
import useUsuariosStore from '@/stores/usuarios/usuariosStore'

const store = useMensajesStore()
const usuariosStore = useUsuariosStore()
const fileStore = useFileStore()

const props = defineProps({ item: { type: Object, default: null } })

const modalRef = ref(null)
const modal = ref(null)

const abrir = () => modal.value?.show()

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

const rules = computed(() => ({
  usuario_destino_id : [{ required: true, message: 'El destinatario es obligatorio', trigger: 'blur' }],
  asunto             : [{ required: true, message: 'El asunto es obligatorio', trigger: 'blur' }],
}))

const submit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    const data = { ...formData.value }

    data.archivo_ids = archivosSubidos.value.map(f => f.id)
    
    await store.createMensaje(data)
    await fileStore.marcarPermanentes(data.archivo_ids)

    Swal.fire({ icon: 'success', title: 'Éxito', text: 'Mensaje enviado correctamente.' })
    cerrar()
    formData.value = { ...store.default }
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

      console.log('Archivo subido:', archivosSubidos)
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

const usuarios = ref([])
const loadingUsuarios = ref(false)
let timeout = null
const buscarUsuarios = (query) => {
  clearTimeout(timeout)
  if (!query || query.length < 4) {
    usuarios.value = []
    loadingUsuarios.value = false
    return
  }
  timeout = setTimeout(async () => {
    loadingUsuarios.value = true
    try {
      await usuariosStore.get({ search: query })
    } catch (error) {
      console.error('Error buscando usuarios:', error)
    } finally {
      loadingUsuarios.value = false
    }
  }, 1000)
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
