import type { ApiFieldErrors } from '@/services/http'

const fieldLabels: Record<string, string> = {
  nama_waktu: 'Nama waktu layanan',
  estimasi_min_jam: 'Estimasi minimal',
  estimasi_max_jam: 'Estimasi maksimal',
  deskripsi: 'Deskripsi',
  is_active: 'Status',
}

const translateMessage = (field: string, message: string): string => {
  const lower = message.toLowerCase()
  const label = fieldLabels[field] ?? 'Data'

  if (lower.includes('is required') || lower.includes('field is required')) {
    return `${label} wajib diisi.`
  }
  if (lower.includes('must not be greater than')) {
    return field === 'nama_waktu'
      ? 'Nama waktu layanan maksimal 100 karakter.'
      : `${label} terlalu besar.`
  }
  if (lower.includes('must be a number') || lower.includes('must be an integer')) {
    return `${label} harus berupa angka.`
  }
  if (lower.includes('must be at least') || lower.includes('must not be less than')) {
    return `${label} tidak boleh kurang dari 0.`
  }
  if (lower.includes('must be a string')) return `${label} tidak valid.`

  return message
}

export const translateFieldErrors = (errors?: ApiFieldErrors): Record<string, string> => {
  const result: Record<string, string> = {}

  if (!errors) return result

  for (const [field, messages] of Object.entries(errors)) {
    const first = messages?.[0]
    if (!first) continue
    result[field] = translateMessage(field, first)
  }

  return result
}
