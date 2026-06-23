import { ref } from 'vue'

const _cache = new Map()
const TTL_MS = 15 * 60 * 1000

const _isValid = (e) => !!e && (Date.now() - e.t) < TTL_MS
const _get = (id) => { const e = _cache.get(Number(id)); return _isValid(e) ? e.d : null }
const _set = (id, data) => _cache.set(Number(id), { d: data, t: Date.now() })
const _cleanExpired = () => { for (const [k, v] of _cache.entries()) if (!_isValid(v)) _cache.delete(k) }
const _timer = setInterval(_cleanExpired, 5 * 60 * 1000)
if (import.meta?.hot) import.meta.hot.on('vite:beforeFullReload', () => clearInterval(_timer))

export function useValidacionFirma() {
  const loading   = ref(false)
  const error     = ref(null)
  const resultado = ref(null)

  async function validarFirmaConCache(fileId, forzarRecarga = false) {
    if (!fileId) {
      const e = { error: 'ID de archivo requerido', errorMessage: 'No se proporcionó fileId' }
      error.value = e.error
      resultado.value = e
      return e
    }
    const fid = parseInt(fileId, 10)

    if (!forzarRecarga) {
      const cached = _get(fid)
      if (cached) { resultado.value = cached; error.value = null; return cached }
    }

    loading.value = true
    error.value   = null
    try {
      const { useFileStore } = await import('@/stores/files/filesStore')
      const filesStore = useFileStore()
      const resp = await filesStore.validarFirma(fid)
      _set(fid, resp)
      resultado.value = resp
      return resp
    } catch (e) {
      const resp = { error: 'No se pudo validar la firma', errorMessage: e?.message }
      _set(fid, resp)
      error.value = resp.error
      resultado.value = resp
      return resp
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    resultado,
    validarFirmaConCache,
    forzarRevalidacion : (fileId) => validarFirmaConCache(fileId, true),
    invalidar          : (fileId) => _cache.delete(parseInt(fileId, 10)),
    limpiarCache       : () => _cache.clear(),
  }
}
