import type { ApiFieldErrors } from '@/services/http'

const fieldLabels: Record<string, string> = {
  nama_pelanggan: 'Nama pelanggan',
  no_hp: 'Nomor HP',
  alamat: 'Alamat',
  tanggal_masuk: 'Tanggal masuk',
  diskon: 'Diskon',
  catatan: 'Catatan',
  detail_pesanan: 'Detail layanan',
  'detail_pesanan.0.id_layanan': 'Layanan',
  'detail_pesanan.0.jumlah': 'Jumlah',
  'detail_pesanan.0.catatan': 'Catatan layanan',
  status_pesanan: 'Status',
}

/**
 * Laravel mengirim pesan validasi dalam bahasa Inggris. Terjemahkan pola umum
 * agar UI tetap berbahasa Indonesia; pesan lain ditampilkan apa adanya.
 */
const translateMessage = (field: string, message: string): string => {
  const lower = message.toLowerCase()
  const label = fieldLabels[field] ?? 'Data'

  if (lower.includes('is required') || lower.includes('field is required')) {
    return `${label} wajib diisi.`
  }
  if (lower.includes('must not be greater than')) {
    return `${label} terlalu panjang.`
  }
  if (lower.includes('must be a number') || lower.includes('must be numeric')) {
    return `${label} harus berupa angka.`
  }
  if (lower.includes('must be an integer')) {
    return `${label} harus bilangan bulat.`
  }
  if (lower.includes('must be at least') || lower.includes('must not be less than')) {
    return `${label} tidak boleh kurang dari nilai minimum.`
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
