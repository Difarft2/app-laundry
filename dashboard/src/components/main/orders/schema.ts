export interface OrderFormData {
  namaPelanggan: string
  noHp: string
  alamat: string
  /** Nilai dari input datetime-local, mis. "2026-11-18T19:00". */
  tanggalMasuk: string
  diskon: string
  catatan: string
  idLayanan: string
  jumlah: string
  catatanDetail: string
}

/** Format `datetime-local` yang dipakai backend: "YYYY-MM-DD HH:mm:ss". */
export const toLocalDateTime = (value: string): string => {
  const normalized = value.trim().replace('T', ' ')
  if (!normalized) return normalized
  return normalized.length === 16 ? `${normalized}:00` : normalized
}

export const nowLocalInput = (): string => {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
}

export const emptyOrderForm = (): OrderFormData => ({
  namaPelanggan: '',
  noHp: '',
  alamat: '',
  tanggalMasuk: nowLocalInput(),
  diskon: '0',
  catatan: '',
  idLayanan: '',
  jumlah: '',
  catatanDetail: '',
})

/**
 * Validasi lokal mengikuti StoreOrderRequest. Aturan bisnis berat (mis. Cuci
 * Basah minimal 5 kg) tetap divalidasi backend dan ditampilkan dari response 422.
 */
export const validateOrderForm = (
  data: OrderFormData,
  isSatuan: boolean,
): Partial<Record<keyof OrderFormData, string>> => {
  const errors: Partial<Record<keyof OrderFormData, string>> = {}

  const nama = data.namaPelanggan.trim()
  if (!nama) errors.namaPelanggan = 'Nama pelanggan wajib diisi.'
  else if (nama.length > 150) errors.namaPelanggan = 'Nama pelanggan maksimal 150 karakter.'

  const noHp = data.noHp.trim()
  if (!noHp) errors.noHp = 'Nomor HP wajib diisi.'
  else if (noHp.length > 20) errors.noHp = 'Nomor HP maksimal 20 karakter.'

  if (!data.tanggalMasuk.trim()) errors.tanggalMasuk = 'Tanggal masuk wajib diisi.'

  if (!data.idLayanan.trim()) errors.idLayanan = 'Layanan wajib dipilih.'

  const jumlah = Number(data.jumlah.replace(',', '.'))
  if (!data.jumlah.trim()) {
    errors.jumlah = isSatuan ? 'Jumlah item wajib diisi.' : 'Berat wajib diisi.'
  } else if (Number.isNaN(jumlah) || jumlah <= 0) {
    errors.jumlah = 'Jumlah harus lebih dari 0.'
  } else if (isSatuan && !Number.isInteger(jumlah)) {
    errors.jumlah = 'Jumlah item harus bilangan bulat.'
  }

  const diskon = Number(data.diskon.replace(',', '.'))
  if (data.diskon.trim() && (Number.isNaN(diskon) || diskon < 0)) {
    errors.diskon = 'Diskon tidak boleh kurang dari 0.'
  }

  return errors
}
