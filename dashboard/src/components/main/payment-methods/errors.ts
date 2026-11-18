import type { ApiFieldErrors } from '@/services/http'

const fieldLabels: Record<string, string> = {
  nama_metode: 'Nama metode',
  kode: 'Kode metode',
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
    return field === 'nama_metode'
      ? 'Nama metode maksimal 100 karakter.'
      : field === 'kode'
        ? 'Kode metode maksimal 50 karakter.'
        : `${label} terlalu panjang.`
  }
  if (lower.includes('has already been taken')) {
    return 'Kode metode sudah digunakan.'
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
