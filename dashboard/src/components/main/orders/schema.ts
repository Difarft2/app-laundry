export interface OrderFormData {
  namaPelanggan: string
  noHp: string
  /** Nilai dari input datetime-local, mis. "2026-11-18T19:00". */
  tanggalMasuk: string
  diskon: string
  catatan: string
  idLayanan: string
  jumlah: string
  catatanDetail: string
  /** "now" = bayar sekarang (buat data pembayaran), "later" = bayar nanti. */
  caraBayar: 'now' | 'later'
  /** ID metode pembayaran saat caraBayar = "now". */
  idMetodePembayaran: string
  jumlahBayar: string
  statusPembayaran: 'pending' | 'berhasil'
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
  tanggalMasuk: nowLocalInput(),
  diskon: '0',
  catatan: '',
  idLayanan: '',
  jumlah: '',
  catatanDetail: '',
  caraBayar: 'later',
  idMetodePembayaran: '',
  jumlahBayar: '',
  statusPembayaran: 'pending',
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

  const jumlahRaw = String(data.jumlah ?? '').trim()
  const jumlah = Number(jumlahRaw.replace(',', '.'))
  if (!jumlahRaw) {
    errors.jumlah = isSatuan ? 'Jumlah item wajib diisi.' : 'Berat wajib diisi.'
  } else if (Number.isNaN(jumlah) || jumlah <= 0) {
    errors.jumlah = 'Jumlah harus lebih dari 0.'
  } else if (isSatuan && !Number.isInteger(jumlah)) {
    errors.jumlah = 'Jumlah item harus bilangan bulat.'
  }

  const diskonRaw = String(data.diskon ?? '').trim()
  const diskon = Number(diskonRaw.replace(',', '.'))
  if (diskonRaw && (Number.isNaN(diskon) || diskon < 0)) {
    errors.diskon = 'Diskon tidak boleh kurang dari 0.'
  }

  if (data.caraBayar === 'now') {
    if (!data.idMetodePembayaran) {
      errors.idMetodePembayaran = 'Metode pembayaran wajib dipilih.'
    }

    const bayarRaw = String(data.jumlahBayar ?? '').trim()
    const bayar = Number(bayarRaw.replace(',', '.'))
    if (!bayarRaw || Number.isNaN(bayar) || bayar <= 0) {
      errors.jumlahBayar = 'Jumlah bayar wajib lebih dari 0.'
    }
  }

  return errors
}
