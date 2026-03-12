import { format, isValid, parseISO } from 'date-fns'
import { es } from 'date-fns/locale'

const LIMA_TIME_ZONE = 'America/Lima'

function isDateOnlyString(value) {
  return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value.trim())
}

function normalizeDateInput(value) {
  if (value === null || value === undefined || value === '') return null

  if (value instanceof Date) {
    return isValid(value) ? value : null
  }

  if (typeof value === 'number') {
    const date = new Date(value)
    return isValid(date) ? date : null
  }

  if (typeof value === 'string') {
    const normalized = value.includes('T') ? value : value.replace(' ', 'T')
    const withZone = /([zZ]|[+-]\d{2}:?\d{2})$/.test(normalized)
      ? normalized
      : `${normalized}Z`

    const parsed = parseISO(withZone)
    if (isValid(parsed)) {
      return parsed
    }

    const fallback = new Date(value)
    return isValid(fallback) ? fallback : null
  }

  return null
}

function toLimaDate(value) {
  const date = normalizeDateInput(value)
  if (!date) return null

  // Project the timestamp into America/Lima and parse it back for date-fns formatting.
  const zonedText = new Intl.DateTimeFormat('sv-SE', {
    timeZone : LIMA_TIME_ZONE,
    year     : 'numeric',
    month    : '2-digit',
    day      : '2-digit',
    hour     : '2-digit',
    minute   : '2-digit',
    second   : '2-digit',
    hour12   : false,
  }).format(date)

  const zonedDate = parseISO(zonedText.replace(' ', 'T'))
  return isValid(zonedDate) ? zonedDate : null
}

export function toTimestamp(value) {
  const date = normalizeDateInput(value)
  return date ? date.getTime() : 0
}

export function formatDateTimeLima(value, pattern = 'dd/MM/yyyy HH:mm') {
  const limaDate = toLimaDate(value)
  if (!limaDate) return ''
  return format(limaDate, pattern, { locale: es })
}

export function formatDateLima(value, pattern = 'dd/MM/yyyy') {
  if (isDateOnlyString(value)) {
    const parsed = parseISO(value)
    return isValid(parsed) ? format(parsed, pattern, { locale: es }) : ''
  }

  const limaDate = toLimaDate(value)
  if (!limaDate) return ''
  return format(limaDate, pattern, { locale: es })
}
