import type { PaginationLinks, PaginationMeta } from './auth'

export type DeliveryType = 'penjemputan' | 'pengantaran'

export type DeliveryStatus =
  | 'menunggu'
  | 'ditugaskan'
  | 'menuju_lokasi'
  | 'barang_diambil'
  | 'barang_diantar'
  | 'selesai'
  | 'gagal'

export interface DeliveryCourier {
  id: number
  name: string
  no_hp: string | null
}

export interface DeliveryCustomer {
  nama: string
  no_hp: string
  alamat: string | null
}

export interface DeliveryOrder {
  id_pesanan: number
  kode_pesanan: string
  status_pesanan: string
  total_harga: number
  pelanggan: DeliveryCustomer
}

export interface DeliveryHistory {
  status_sebelumnya: string | null
  status_selanjutnya: string
  keterangan: string | null
  tanggal_perubahan: string
  user: string | null
}

export interface DeliveryTask {
  id_antar_jemput: number
  jenis_antar_jemput: DeliveryType
  status_antar_jemput: DeliveryStatus
  alamat_tujuan: string
  nama_penerima: string | null
  no_hp_penerima: string | null
  jadwal: string | null
  tanggal_selesai: string | null
  catatan: string | null
  kurir: DeliveryCourier | null
  pesanan?: DeliveryOrder
  riwayat?: DeliveryHistory[]
  created_at: string
  updated_at: string
}

export interface DeliveryTaskPayload {
  id_pesanan: number
  jenis_antar_jemput: DeliveryType
  id_kurir?: number | null
  alamat_tujuan: string
  nama_penerima?: string | null
  no_hp_penerima?: string | null
  jadwal?: string | null
  catatan?: string | null
}

export interface UpdateDeliveryStatusPayload {
  status_antar_jemput: DeliveryStatus
  keterangan?: string | null
}
