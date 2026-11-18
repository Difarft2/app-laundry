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

export const validatePaymentMethodForm = (
  data: PaymentMethodFormData,
): Partial<Record<keyof PaymentMethodFormData, string>> => {
  const errors: Partial<Record<keyof PaymentMethodFormData, string>> = {}

  const nama = data.namaMetode.trim()
  if (!nama) errors.namaMetode = 'Nama metode wajib diisi.'
  else if (nama.length > 100) errors.namaMetode = 'Nama metode maksimal 100 karakter.'

  const kode = data.kode.trim()
  if (!kode) errors.kode = 'Kode metode wajib diisi.'
  else if (kode.length > 50) errors.kode = 'Kode metode maksimal 50 karakter.'

  return errors
}
