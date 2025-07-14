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
            <i class="bi bi-envelope-plus me-2 fs-2" :style="{'color': isEdit ? '#664d03' : '#08632a'}"></i>
            <span class="text-uppercase">Nuevo Mensaje</span>          
          </HeaderColor>

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
                  <i class="bi bi-send me-2"></i>
                  {{ isEdit ? 'Enviar' : 'Enviar' }}
                </span>
                <span v-else class="indicator-progress">
                  <span class="spinner-border spinner-border-sm me-2"></span>Guardando...
                </span>
              </button>
            </div>
          </div>

          <div class="modal-body px-0 py-1">
            <div class="container">
              <!-- Destinatario -->
              <div class="mb-3">
                <el-form-item label="Ubigeo" prop="ubigeo_id" class="mb-3">
                  <el-select
                    v-model="formData.destinatario"
                    placeholder="Busque y seleccione el ubigeo"
                    filterable
                    remote
                    :remote-method="buscarUsuarios"
                    :loading="loadingUsuarios"
                    class="w-100"
                    size="large"
                    clearable
                    @visible-change="handleUsuariosSelectOpen"
                  >
                    <el-option
                      v-for="usuario in usuariosDisponibles"
                      :key="usuario.id"
                      :label="`${usuario.nombre} ${usuario.apellido} (${usuario.numero_documento})`"
                      :value="usuario.id"
                    >
                      <div class="d-flex justify-content-between align-items-center">
                        <span>{{ usuario.nombre }} - {{ usuario.apellido }}</span>
                        <small class="text-muted">{{ usuario.numero_documento }}</small>
                      </div>
                    </el-option>
                  </el-select>
                </el-form-item>
              </div>

              <div class="form-floating mb-1">
                <input
                  id="asunto"
                  v-model="formData.asunto"
                  type="text"
                  class="form-control"
                  placeholder=" "
                  required
                />
                <label for="asunto">Asunto</label>
              </div>
            </div>

            <!-- Mostrar archivos adjuntos -->
            <div v-if="fileNames.length > 0" class="mb-3 px-5">
              <div class="d-flex flex-wrap gap-2">
                <div
                  v-for="(file, index) in fileNames"
                  :key="index"
                  class="card p-2 mb-2 bg-light bg-opacity-50"
                  style="width: 200px; max-height: 200px; overflow: hidden;"
                >
                  <div class="d-flex align-items-center">
                    <div class="badge text-white text-uppercase fw-bold py-3" :class="getFileIconClass(file)">
                      <span>{{ file.extension }}</span>
                    </div>
                    <div class="ms-2">
                      <p class="text-truncate text-muted m-0" style="font-size: 12px; max-width: 140px; min-width: 140px;">
                        {{ file.name }}
                      </p>
                      <p class="d-flex justify-content-between m-0 w-100">
                        <span style="font-size: 10px; color: #6c757d;">{{ file.size }}</span>
                        <a class="text-danger ms-2 px-2" style="cursor: pointer; font-size: 11px;" @click="removeFile(index)">
                          <i class="text-danger bi bi-x-circle"></i>
                        </a>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
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

// Componentes
import HeaderColor from '@/components/tabla/HeaderColor.vue'
import Editor from '@/components/ckeditor/Editor.vue'

// Stores
import { useMensajesStore } from '@/stores/mensajes/mensajesStore'
import useUsuariosStore from '@/stores/usuarios/usuariosStore'

const store = useMensajesStore()
const usuariosStore = useUsuariosStore()

const props = defineProps({ item: { type: Object, default: null } })

// --------------------------- Modal ---------------------------
const modalRef = ref(null)
const modal = ref(null)
onMounted(() => {
  if (modalRef.value) modal.value = new Modal(modalRef.value)
})
const abrir = () => modal.value?.show()
const cerrar = () => modal.value?.hide()
defineExpose({ abrir, cerrar })

// --------------------------- Formulario ---------------------------
const formRef = ref(null)
const formData = ref({ ...store.default })
const loading = ref(false)
const isEdit = computed(() => !!props.item?.id)

const rules = computed(() => ({
  destinatario : [{ required: true, message: 'El destinatario es obligatorio', trigger: 'blur' }],
  asunto       : [{ required: true, message: 'El asunto es obligatorio', trigger: 'blur' }],
}))

const submit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    const data = { ...formData.value }
    console.log('Datos del formulario:', data)
    // enviar...
  } catch (error) {
    console.error('Error al enviar:', error)
    Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un problema al enviar.' })
  }
}

// --------------------------- Usuarios ---------------------------
const usuarios = ref([])
const loadingUsuarios = ref(false)
let timeout = null

const buscarUsuarios = (query) => {
  clearTimeout(timeout)

  if (!query || query.length < 2) {
    usuarios.value = []
    loadingUsuarios.value = false
    return
  }

  timeout = setTimeout(async () => {
    loadingUsuarios.value = true
    try {
      await usuariosStore.get({
        nombre           : query,
        apellido         : query,
        numero_documento : query,
        modeljs          : true
      })
      usuarios.value = usuariosStore.list.map(user => ({
        id    : user.id,
        label : `${user.nombre} ${user.apellido} (${user.numero_documento})`
      }))
    } catch (error) {
      console.error('Error buscando usuarios:', error)
    } finally {
      loadingUsuarios.value = false
    }
  }, 300)
}


const handleUsuariosSelectOpen = async (visible) => {
  if (visible && !usuariosStore.list.length) {
    loadingUsuarios.value = true
    try {
      await usuariosStore.get()
    } catch (error) {
      console.error('Error cargando usuarios:', error)
    } finally {
      loadingUsuarios.value = false
    }
  }
}

// --------------------------- Archivos ---------------------------
const fileInput = ref(null)
const fileNames = ref([])

const triggerFileInput = () => fileInput.value?.click()

const handleFileChange = (event) => {
  const files = event.target.files
  if (files?.length) {
    for (const file of files) {
      const ext = getFileExtension(file.name)
      if (ext) {
        fileNames.value.push({
          name      : file.name,
          size      : formatFileSize(file.size),
          extension : ext,
          file,
        })
      }
    }
  }
}

const removeFile = (index) => fileNames.value.splice(index, 1)
const getFileExtension = (name) => name.split('.').pop()?.toLowerCase() || 'unknown'
const formatFileSize = (bytes) => {
  const kb = bytes / 1024
  return kb < 1024 ? `${kb.toFixed(2)} KB` : `${(kb / 1024).toFixed(2)} MB`
}

const getFileIconClass = (file) => {
  const classes = {
    ods     : 'bg-success',
    doc     : 'bg-primary',
    docx    : 'bg-primary',
    xls     : 'bg-success',
    xlsx    : 'bg-success',
    ppt     : 'bg-warning',
    pptx    : 'bg-warning',
    mp4     : 'bg-primary',
    pdf     : 'bg-warning',
    jpg     : 'bg-info',
    png     : 'bg-info',
    unknown : 'bg-danger',
  }
  return classes[file.extension] || 'bg-dark'
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
