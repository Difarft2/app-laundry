export type ServiceTimeFormMode = 'create' | 'edit'

export interface ServiceTimeFormData {
  namaWaktu: string
  /** Disimpan sebagai string agar mudah terikat ke input teks. */
  estimasiMinJam: string
  estimasiMaxJam: string
  deskripsi: string
  isActive: boolean
}

export const emptyServiceTimeForm = (): ServiceTimeFormData => ({
  namaWaktu: '',
  estimasiMinJam: '',
  estimasiMaxJam: '',
  deskripsi: '',
  isActive: true,
})

/**
 * Validasi mengikuti StoreServiceTimeRequest / UpdateServiceTimeRequest.
 * Estimasi bersifat opsional, tetapi bila diisi harus angka >= 0 dan
 * maksimal tidak boleh lebih kecil dari minimal.
 */
export const validateServiceTimeForm = (
  data: ServiceTimeFormData,
): Partial<Record<keyof ServiceTimeFormData, string>> => {
  const errors: Partial<Record<keyof ServiceTimeFormData, string>> = {}

  const nama = String(data.namaWaktu ?? '').trim()
  if (!nama) errors.namaWaktu = 'Nama waktu layanan wajib diisi.'
  else if (nama.length > 100) errors.namaWaktu = 'Nama waktu layanan maksimal 100 karakter.'

  const parseHours = (value: string): number | null => {
    const raw = String(value ?? '')
    if (!raw.trim()) return null
    return Number(raw.replace(',', '.'))
  }

  const min = parseHours(data.estimasiMinJam)
  const max = parseHours(data.estimasiMaxJam)

  if (min !== null) {
    if (!Number.isInteger(min) || min < 0) {
      errors.estimasiMinJam = 'Estimasi minimal harus berupa angka bulat tidak negatif.'
    }
  }

  if (max !== null) {
    if (!Number.isInteger(max) || max < 0) {
      errors.estimasiMaxJam = 'Estimasi maksimal harus berupa angka bulat tidak negatif.'
    }
  }

  if (min !== null && max !== null && Number.isInteger(min) && Number.isInteger(max) && max < min) {
    errors.estimasiMaxJam = 'Estimasi maksimal tidak boleh lebih kecil dari estimasi minimal.'
  }

  return errors
}
