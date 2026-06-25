<template>
  <div class="ce-gmail-wrap">

    <!-- Toolbar -->
    <div class="ce-gmail-toolbar">
      <span>{{ paginationFrom }}–{{ paginationTo }} de {{ pagination.total }}</span>
      <div class="d-flex align-items-center gap-2">
        <span>Por página:</span>
        <select class="ce-per-page" @change="$emit('items-per-page-change', +$event.target.value)">
          <option v-for="n in [10,25,50]" :key="n" :value="n" :selected="pagination.per_page==n">{{ n }}</option>
        </select>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="!mensajes.length" class="ce-gmail-empty">
      <div class="ce-gmail-empty-icon"><i class="bi bi-inbox"></i></div>
      <p>Bandeja vacía</p>
      <p style="font-size:13px;color:#94a3b8;">No hay mensajes en esta sección</p>
    </div>

    <!-- Rows -->
    <div v-else class="ce-gmail-rows">
      <div
        v-for="item in mensajes"
        :key="item.id"
        class="ce-gmail-row"
        :class="{
          'ce-gmail-row--unread'   : !item.leido,
          'ce-gmail-row--selected' : isSelected(item),
        }"
        @click="selectItem(item)"
      >
        <!-- Star -->
        <div
          class="ce-col-star"
          :class="{ 'ce-col-star--active': item.destacado }"
          :title="item.destacado ? 'Destacado' : 'Sin destacar'"
          @click.stop
        >
          <i :class="item.destacado ? 'bi bi-star-fill' : 'bi bi-star'"></i>
        </div>

        <!-- Remitente -->
        <div class="ce-col-sender" :title="getDeTexto(item)">{{ getDeTexto(item) }}</div>

        <!-- Asunto + preview (ocupa el espacio restante) -->
        <div class="ce-col-subject-wrap">
          <span class="ce-col-subject">{{ item.asunto }}</span>
          <span v-if="previewTexto(item)" class="ce-col-preview"> &mdash; {{ previewTexto(item) }}</span>
        </div>

        <!-- Chips -->
        <div class="ce-col-chips">
          <span v-if="item.prioridad === 1" class="ce-row-chip ce-row-chip--red">Alta</span>
          <span v-else-if="item.prioridad === 2" class="ce-row-chip ce-row-chip--yellow">Media</span>
          <span v-if="item.adjuntos?.length" class="ce-row-chip ce-row-chip--clip">
            <i class="bi bi-paperclip"></i>
          </span>
        </div>

        <!-- Date -->
        <div class="ce-col-date">{{ formatDateShort(item.created_at) }}</div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="ce-gmail-footer">
      <span>{{ paginationFrom }}–{{ paginationTo }} de {{ pagination.total }}</span>
      <nav>
        <ul class="pagination pagination-sm mb-0 gap-1">
          <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
            <a class="page-link ce-page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <li
            v-for="(p, i) in visiblePages" :key="i"
            class="page-item"
            :class="{ active: p === pagination.current_page, disabled: p === '…' }"
          >
            <a class="page-link ce-page-link" href="#" @click.prevent="p !== '…' && goToPage(p)">{{ p }}</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page >= totalPages }">
            <a class="page-link ce-page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>

  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import useDesignacionStore from '@/stores/designaciones/designacionStore'

const props = defineProps({
  mensajes   : { type: Array,  required: true },
  pagination : { type: Object, required: true },
  selected   : { type: Object, default: null },
})
const emit = defineEmits(['seleccionar', 'page-change', 'items-per-page-change', 'sort'])

const designacionStore   = useDesignacionStore()
const deTextoByMensajeId = ref({})
let cargaActual          = 0

const totalPages = computed(() => Math.ceil((props.pagination.total || 0) / (props.pagination.per_page || 10)))

const paginationFrom = computed(() => {
  const { current_page: c, per_page: p } = props.pagination
  return Math.max(1, (c - 1) * p + 1)
})
const paginationTo = computed(() => {
  const { current_page: c, per_page: p, total: t } = props.pagination
  return Math.min(c * p, t)
})
const visiblePages = computed(() => {
  const cur = props.pagination.current_page, tot = totalPages.value
  if (tot <= 7) return Array.from({ length: tot }, (_, i) => i + 1)
  const pages = [1]
  if (cur > 3)       pages.push('…')
  for (let i = Math.max(2, cur - 1); i <= Math.min(tot - 1, cur + 1); i++) pages.push(i)
  if (cur < tot - 2) pages.push('…')
  pages.push(tot)
  return pages
})

const isSelected  = (item) => props.selected?.id === item.id
const selectItem  = (item) => emit('seleccionar', item)
const goToPage    = (page) => {
  if (page < 1 || page > totalPages.value) return
  emit('page-change', page)
}
const getDeTexto  = (item) => deTextoByMensajeId.value[item.id] || `Casilla ${item.casilla_origen_id}`
const previewTexto = (item) => {
  if (!item.contenido) return ''
  return item.contenido.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()
}
const formatDateShort = (d) => {
  if (!d) return ''
  const date = new Date(d)
  const now  = new Date()
  if (date.toDateString() === now.toDateString())
    return date.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })
  return date.toLocaleDateString('es-PE', { day: '2-digit', month: 'short' })
}

async function cargarRemitentes(mensajes = []) {
  const validos = mensajes.filter(m => m?.id && m?.casilla_origen_id)
  if (!validos.length) { deTextoByMensajeId.value = {}; return }
  const id = ++cargaActual
  const actores = await Promise.all(validos.map(m => designacionStore.resolveActorByCasillaId(m.casilla_origen_id)))
  if (id !== cargaActual) return
  const map = {}
  validos.forEach((m, i) => { map[m.id] = actores[i]?.usuario_nombre || `Casilla ${m.casilla_origen_id}` })
  deTextoByMensajeId.value = map
}

watch(() => props.mensajes.map(m => `${m.id}:${m.casilla_origen_id}`).join('|'),
  () => cargarRemitentes(props.mensajes), { immediate: true })
</script>
