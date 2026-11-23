/**
 * Formatting helpers for Indonesian locale.
 * Keep these pure so they can be reused by other modules later.
 */

export const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('id-ID').format(value)
}

export const formatCurrency = (value: number): string => {
  return `Rp${new Intl.NumberFormat('id-ID').format(Math.round(value))}`
}

export const formatCompactCurrency = (value: number): string => {
  const abs = Math.abs(value)

  if (abs >= 1_000_000_000) {
    return `Rp${(value / 1_000_000_000).toFixed(1).replace('.', ',')} M`
  }
  if (abs >= 1_000_000) {
    return `Rp${(value / 1_000_000).toFixed(1).replace('.', ',')} jt`
  }
  if (abs >= 1_000) {
    return `Rp${Math.round(value / 1_000)} rb`
  }
  return `Rp${formatNumber(value)}`
}

export const formatDateLong = (date: Date): string => {
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

export const formatDateShort = (date: Date): string => {
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

export const formatDateTime = (value: string | null | undefined): string => {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}
