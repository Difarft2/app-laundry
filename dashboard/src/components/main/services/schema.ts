import type { ServiceFinishing, ServiceType } from '@/types/service'

export type ServiceFormMode = 'create' | 'edit'

export interface ServiceFormData {
  namaLayanan: string
  /** Disimpan sebagai string agar mudah terikat ke input Select/teks. */
  idWaktuLayanan: string
  jenisLayanan: ServiceType
  jenisFinishing: ServiceFinishing
  /** Disimpan sebagai string; dikonversi ke number saat dikirim. */
  harga: string
  deskripsi: string
  isActive: boolean
}

export const emptyServiceForm = (): ServiceFormData => ({
  namaLayanan: '',
  idWaktuLayanan: '',
  jenisLayanan: 'kiloan',
  jenisFinishing: 'setrika',
  harga: '',
  deskripsi: '',
  isActive: true,
})

/**
 * Validasi mengikuti StoreServiceRequest / UpdateServiceRequest di backend.
 */
export const validateServiceForm = (
  data: ServiceFormData,
): Partial<Record<keyof ServiceFormData, string>> => {
  const errors: Partial<Record<keyof ServiceFormData, string>> = {}

  const nama = data.namaLayanan.trim()
  if (!nama) errors.namaLayanan = 'Nama layanan wajib diisi.'
  else if (nama.length > 100) errors.namaLayanan = 'Nama layanan maksimal 100 karakter.'

  const harga = data.harga.trim()
  if (!harga) {
    errors.harga = 'Harga layanan wajib diisi.'
  } else {
    const parsed = Number(harga.replace(',', '.'))
    if (Number.isNaN(parsed)) errors.harga = 'Harga harus berupa angka.'
    else if (parsed < 0) errors.harga = 'Harga tidak boleh kurang dari 0.'
  }

  if (data.idWaktuLayanan.trim() && data.idWaktuLayanan !== '__none__') {
    const id = Number(data.idWaktuLayanan.trim())
    if (!Number.isInteger(id) || id <= 0) {
      errors.idWaktuLayanan = 'Waktu layanan tidak valid.'
    }
  }

  return errors
}
