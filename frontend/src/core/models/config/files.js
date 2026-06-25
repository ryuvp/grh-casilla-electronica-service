const API_FILE = (import.meta.env.VITE_API_FILE || 'http://localhost:8085/api/files').replace(/\/$/, '')
const FILE_ROOT = API_FILE.replace(/\/api\/files$/, '')

const Files = {
  buildVisualizarUrl(fileId) {
    if (!fileId) return ''
    return `${FILE_ROOT}/api/files/visualizar/${fileId}`
  },
}

export default Files
