export type PaymentMethodFormMode = 'create' | 'edit'

export interface PaymentMethodFormData {
  namaMetode: string
  kode: string
  deskripsi: string
  isActive: boolean
}

export const emptyPaymentMethodForm = (): PaymentMethodFormData => ({
  namaMetode: '',
  kode: '',
  deskripsi: '',
  isActive: true,
})

/**
 * Membuat kode metode dari nama, mis. "Transfer Bank" -> "transfer_bank".
 * Hanya saran; pengguna tetap bisa mengubah kodenya.
 */
export const suggestKode = (nama: string): string =>
  nama
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .slice(0, 50)

/**
 * Validasi mengikuti StorePaymentMethodRequest / UpdatePaymentMethodRequest.
 * Keunikan kode tetap divalidasi backend.
 */
export const validatePaymentMethodForm = (
  data: PaymentMethodFormData,
): Partial<Record<keyof PaymentMethodFormData, string>> => {
  const errors: Partial<Record<keyof PaymentMethodFormData, string>> = {}

  const nama = String(data.namaMetode ?? '').trim()
  if (!nama) errors.namaMetode = 'Nama metode wajib diisi.'
  else if (nama.length > 100) errors.namaMetode = 'Nama metode maksimal 100 karakter.'

  const kode = String(data.kode ?? '').trim()
  if (!kode) errors.kode = 'Kode metode wajib diisi.'
  else if (kode.length > 50) errors.kode = 'Kode metode maksimal 50 karakter.'

  return errors
}
