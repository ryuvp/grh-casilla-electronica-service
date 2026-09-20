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
          'ce-gmail-row--unread'   : !isEnviados && !item.leido,
          'ce-gmail-row--selected' : isSelected(item),
        }"
        @click="selectItem(item)"
      >
        <!-- Enviados: icono fijo de envio (destacar/archivar es solo del destinatario) -->
        <div v-if="isEnviados" class="ce-col-star ce-col-star--sent" title="Enviado">
          <i class="bi bi-send"></i>
        </div>

        <!-- Star -->
        <div
          v-else
          class="ce-col-star"
          :class="{ 'ce-col-star--active': item.destacado }"
          :title="item.destacado ? 'Destacado' : 'Sin destacar'"
          @click.stop
        >
          <i :class="item.destacado ? 'bi bi-star-fill' : 'bi bi-star'"></i>
        </div>

        <!-- Remitente (entrada) o destinatarios (enviados) -->
        <div class="ce-col-sender" :title="getDeTitle(item)">{{ getDeTexto(item) }}</div>

        <!-- Asunto + preview (ocupa el espacio restante) -->
        <div class="ce-col-subject-wrap">
          <span class="ce-col-subject">{{ item.asunto }}</span>
          <span v-if="previewTexto(item)" class="ce-col-preview"> &mdash; {{ previewTexto(item) }}</span>
        </div>

        <!-- Chips -->
        <div class="ce-col-chips">
          <span v-if="isEnviados" class="ce-row-chip" :class="estadoLectura(item).clase">
            {{ estadoLectura(item).texto }}
          </span>
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
  trayType   : { type: String, default: 'entrada' },
})
const emit = defineEmits(['seleccionar', 'page-change', 'items-per-page-change', 'sort'])

const designacionStore   = useDesignacionStore()
const isEnviados         = computed(() => props.trayType === 'enviados')
// Nombres de la contraparte por mensaje: remitente (entrada) o destinatarios (enviados).
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
// Casillas de la contraparte: en enviados, todos los destinatarios del envio.
const contraparteIds = (item) => {
  if (!isEnviados.value) return [item.casilla_origen_id].filter(Boolean)
  const ids = item.casilla_destino_ids?.length ? item.casilla_destino_ids : [item.casilla_destino_id]
  return ids.filter(Boolean)
}
const nombresDe = (item) => deTextoByMensajeId.value[item.id] || contraparteIds(item).map(id => `Casilla ${id}`)
const getDeTexto = (item) => {
  const nombres = nombresDe(item)
  if (!isEnviados.value) return nombres[0] || ''
  const extra = nombres.length > 1 ? ` +${nombres.length - 1}` : ''
  return `Para: ${nombres[0] || ''}${extra}`
}
const getDeTitle = (item) => (isEnviados.value ? 'Para: ' : '') + nombresDe(item).join(', ')
// Estado de lectura del envio: la lectura es individual por destinatario.
const estadoLectura = (item) => {
  const dests  = item.destinatarios?.length ? item.destinatarios : [{ leido: item.leido }]
  const leidos = dests.filter(d => d.leido).length
  if (!leidos)                  return { texto: 'Sin leer', clase: 'ce-row-chip--gray' }
  if (leidos === dests.length)  return { texto: 'Leído', clase: 'ce-row-chip--green' }
  return { texto: `Leído ${leidos}/${dests.length}`, clase: 'ce-row-chip--blue' }
}
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

async function cargarContrapartes(mensajes = []) {
  const validos = mensajes.filter(m => m?.id && contraparteIds(m).length)
  if (!validos.length) { deTextoByMensajeId.value = {}; return }
  const id = ++cargaActual
  // Un solo lote (máx. 2 peticiones totales) en vez de 2 peticiones POR mensaje.
  const casillaIds = [...new Set(validos.flatMap(contraparteIds))]
  await designacionStore.resolveActorsByCasillaIds(casillaIds)
  if (id !== cargaActual) return
  const map = {}
  validos.forEach((m) => {
    map[m.id] = contraparteIds(m).map(cid => designacionStore.actorByCasillaId[cid]?.usuario_nombre || `Casilla ${cid}`)
  })
  deTextoByMensajeId.value = map
}

watch(() => props.trayType + '|' + props.mensajes.map(m => `${m.id}:${m.casilla_origen_id}:${contraparteIds(m).join(',')}`).join('|'),
  () => cargarContrapartes(props.mensajes), { immediate: true })
</script>
