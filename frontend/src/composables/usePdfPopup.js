import { ref } from 'vue'
import { Files } from '@/core/models'
import JwtService from '@/core/services/JwtService'

let singleton

function getArchivoId(archivo) {
  if (!archivo) return null
  return archivo.id || archivo.archivo_id || null
}

function esPdf(archivo) {
  if (!archivo) return false
  return archivo.mime_type === 'application/pdf' || archivo.extension === 'pdf'
}

async function descargarArchivo(archivo) {
  try {
    const id = getArchivoId(archivo)
    if (!id) throw new Error('Sin ID de archivo')
    const nombre = archivo.nombre_original || archivo.nombre || `Archivo_${id}`
    const API_FILE = (import.meta.env.VITE_API_FILE || 'http://localhost:8085/api/files').replace(/\/$/, '')
    const token = JwtService.getToken()
    const res = await window.fetch(`${API_FILE}/download/${id}`, {
      headers: token ? { 'Authorization': `Bearer ${token}` } : {},
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const blob = await res.blob()
    const objectUrl = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = objectUrl
    a.download = nombre
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(objectUrl), 1000)
    return { éxito: true, error: null }
  } catch (e) {
    return { éxito: false, error: e.message || 'Error desconocido' }
  }
}

export function usePdfPopup() {
  if (singleton) return singleton

  const datosVisor = ref({
    visible       : false,
    fileId        : null,
    pdfUrl        : '',
    nombreArchivo : '',
  })

  function abrirPdfEnPopup(archivo) {
    if (!archivo) return

    const fileId = getArchivoId(archivo)
    if (!fileId) {
      console.warn('[usePdfPopup] Archivo sin ID:', archivo)
      return
    }

    const pdfUrl = Files.buildVisualizarUrl(fileId)
    const nombreArchivo = archivo.nombre_original || archivo.nombre || `Archivo_${fileId}.pdf`

    datosVisor.value = {
      visible       : true,
      fileId,
      pdfUrl,
      nombreArchivo,
    }
  }

  function cerrarVisor() {
    datosVisor.value = {
      visible       : false,
      fileId        : null,
      pdfUrl        : '',
      nombreArchivo : '',
    }
  }

  singleton = { datosVisor, abrirPdfEnPopup, cerrarVisor, esPdf, getArchivoId, descargarArchivo }
  return singleton
}
