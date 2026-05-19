<template>
  <Teleport to="body">
    <Transition name="comunicado-fade">
      <div v-if="visible && actual" class="comunicado-overlay" role="dialog" aria-modal="true">
        <div class="comunicado-backdrop"></div>
        <div class="comunicado-floating-top">
          <span v-if="total > 1" class="comunicado-counter">{{ indiceActual + 1 }} / {{ total }}</span>
          <button type="button" class="comunicado-close" aria-label="Cerrar" @click="cerrarTodo"><i class="bi bi-x-lg"></i></button>
        </div>
        <main class="comunicado-stage">
          <Transition name="comunicado-slide" mode="out-in">
            <div :key="actual.id" class="comunicado-media-shell">
              <img v-if="actual.archivo_tipo === 'image' && actual.archivo_url" :src="actual.archivo_url" :alt="actual.titulo" />
              <iframe v-else-if="actual.archivo_tipo === 'pdf' && actual.archivo_url" :src="actual.archivo_url" :title="actual.archivo_nombre || actual.titulo" />
              <div v-else class="comunicado-empty"><i class="bi bi-megaphone"></i><span>El comunicado está disponible, pero no tiene vista previa.</span></div>
            </div>
          </Transition>
        </main>
        <footer class="comunicado-floating-bottom">
          <button v-if="indiceActual > 0" type="button" class="comunicado-action comunicado-action--ghost" @click="anterior">Anterior</button>
          <button type="button" class="comunicado-action comunicado-action--ghost" @click="cerrarTodo">Cerrar</button>
          <button type="button" class="comunicado-action comunicado-action--primary" @click="siguiente">{{ indiceActual + 1 < total ? 'Siguiente' : 'Entendido' }}</button>
        </footer>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import useComunicadosStore from '@/stores/comunicados/comunicadosStore.js'
const store = useComunicadosStore()
const visible = ref(false)
const indiceActual = ref(0)
const vistos = ref(new Set())
const total = computed(() => store.items.length)
const actual = computed(() => store.items[indiceActual.value] || null)
async function cargar() { try { await store.cargarVisibles(); if (store.items.length) { indiceActual.value = 0; visible.value = true; await registrarActual(); } } catch (error) { console.error('No se pudieron cargar los comunicados visibles:', error) } }
async function registrarActual() { const comunicado = actual.value; if (!comunicado || vistos.value.has(comunicado.id)) return; vistos.value.add(comunicado.id); try { await store.registrarVista(comunicado.id) } catch (error) { vistos.value.delete(comunicado.id); console.error('No se pudo registrar la visualización del comunicado:', error) } }
async function siguiente() { if (indiceActual.value + 1 < total.value) { indiceActual.value += 1; await registrarActual(); return } cerrarTodo() }
async function anterior() { if (indiceActual.value === 0) return; indiceActual.value -= 1; await registrarActual() }
function cerrarTodo() { visible.value = false }
defineExpose({ cargar })
</script>

<style scoped>
.comunicado-overlay { position: fixed; inset: 0; z-index: 11000; display: grid; place-items: center; padding: 1.5rem; }
.comunicado-backdrop { position: absolute; inset: 0; background: rgba(255,255,255,.56); backdrop-filter: blur(16px) saturate(115%); -webkit-backdrop-filter: blur(16px) saturate(115%); }
.comunicado-floating-top { position: absolute; top: 1.5rem; right: 1.5rem; z-index: 2; display: flex; align-items: center; gap: .75rem; }
.comunicado-counter,.comunicado-close { background: rgba(255,255,255,.86); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); box-shadow: 0 10px 28px rgba(15,23,42,.14); }
.comunicado-counter { padding: .5rem .8rem; border-radius: 999px; color: #0f172a; font-weight: 700; }
.comunicado-close { width: 44px; height: 44px; display: grid; place-items: center; border: 0; border-radius: 999px; color: #0f172a; }
.comunicado-stage { position: relative; z-index: 1; display: grid; place-items: center; width: 100%; height: 100%; padding: 4.8rem 1rem 5.8rem; }
.comunicado-media-shell { display: grid; place-items: center; }
.comunicado-stage img { display: block; width: auto; max-width: min(94vw, 1100px); max-height: min(80vh, 860px); border-radius: 18px; object-fit: contain; box-shadow: 0 24px 64px rgba(15,23,42,.22), 0 8px 22px rgba(15,23,42,.12); }
.comunicado-stage iframe { width: min(94vw, 1100px); height: min(80vh, 860px); border: 0; border-radius: 18px; background: #fff; box-shadow: 0 24px 64px rgba(15,23,42,.22), 0 8px 22px rgba(15,23,42,.12); }
.comunicado-empty { min-height: 280px; display: grid; place-items: center; gap: .8rem; color: #fff; text-align: center; }
.comunicado-empty i { font-size: 2.6rem; }
.comunicado-floating-bottom { position: absolute; left: 50%; bottom: 1.5rem; z-index: 2; display: flex; align-items: center; gap: .7rem; transform: translateX(-50%); }
.comunicado-action { min-width: 112px; height: 46px; padding: 0 1.25rem; border: 0; border-radius: 14px; font-weight: 600; transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease; }
.comunicado-action:hover { transform: translateY(-1px); }
.comunicado-action--ghost { color: #334155; background: rgba(255,255,255,.88); box-shadow: 0 10px 24px rgba(15,23,42,.12); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); }
.comunicado-action--primary { color: #fff; background: linear-gradient(135deg,#0f766e,#059669); box-shadow: 0 14px 30px rgba(5,150,105,.3); }
</style>
