import type { ApiFieldErrors } from '@/services/http'

const fieldLabels: Record<string, string> = {
  nama_layanan: 'Nama layanan',
  id_waktu_layanan: 'Waktu layanan',
  jenis_layanan: 'Jenis layanan',
  jenis_finishing: 'Jenis finishing',
  harga: 'Harga',
  deskripsi: 'Deskripsi',
  is_active: 'Status',
}

/**
 * Laravel mengirim pesan validasi dalam bahasa Inggris. Terjemahkan pola
 * yang umum agar UI tetap berbahasa Indonesia; pesan lain ditampilkan
 * apa adanya supaya informasi dari server tidak hilang.
 */
const translateMessage = (field: string, message: string): string => {
  const lower = message.toLowerCase()
  const label = fieldLabels[field] ?? 'Data'

  if (lower.includes('is required') || lower.includes('field is required')) {
    return `${label} wajib diisi.`
  }
  if (lower.includes('must not be greater than')) {
    return field === 'nama_layanan'
      ? 'Nama layanan maksimal 100 karakter.'
      : `${label} terlalu panjang.`
  }
  if (lower.includes('must be a number') || lower.includes('must be numeric')) {
    return 'Harga harus berupa angka.'
  }
  if (lower.includes('must be at least') || lower.includes('must not be less than')) {
    return 'Harga tidak boleh kurang dari 0.'
  }
  if (lower.includes('selected') && lower.includes('invalid')) {
    return `${label} tidak valid.`
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
