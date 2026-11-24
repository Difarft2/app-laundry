import type { PaginationLinks, PaginationMeta } from './auth'
import type { Payment } from './payment'

export type OrderStatus =
  | 'menunggu'
  | 'dicuci'
  | 'dikeringkan'
  | 'dilipat'
  | 'disetrika'
  | 'diproses'
  | 'siap_diambil'
  | 'selesai'
  | 'dibatalkan'

export interface OrderCustomer {
  nama: string
  no_hp: string
  alamat: string | null
}

export interface OrderProcess {
  id_proses_laundry: number
  status_proses: string
  mulai_proses: string | null
  selesai_proses: string | null
}

export interface OrderDetail {
  id_detail_pesanan: number
  id_layanan: number
  nama_layanan: string
  harga_satuan: number
  jumlah: number
  satuan: 'kg' | 'pcs'
  subtotal: number
  catatan: string | null
  proses_laundry: OrderProcess | null
}

export interface OrderStatusHistory {
  status_sebelumnya: string
  status_selanjutnya: string
  keterangan: string | null
  tanggal_perubahan: string
  user: string | null
}

export interface OrderDeliveryTask {
  id_antar_jemput: number
  jenis_antar_jemput: string
  status_antar_jemput: string
  jadwal: string | null
  alamat_tujuan: string
  kurir: string | null
}

export interface Order {
  id_pesanan: number
  kode_pesanan: string
  pelanggan: OrderCustomer
  tanggal_masuk: string
  estimasi_selesai: string | null
  tanggal_selesai: string | null
  status_pesanan: OrderStatus
  subtotal: number
  diskon: number
  total_harga: number
  total_terbayar: number
  sisa_pembayaran: number
  catatan: string | null
  pembayaran?: Payment[]
  detail_pesanan?: OrderDetail[]
  riwayat_status?: OrderStatusHistory[]
  antar_jemput?: OrderDeliveryTask[]
  created_at: string
  updated_at: string
}

export interface OrdersQuery {
  search?: string
  status?: OrderStatus
  tanggal_mulai?: string
  tanggal_selesai?: string
  per_page?: number
  page?: number
}

export interface PaginatedOrders {
  data: Order[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface CreateOrderDetailPayload {
  id_layanan: number
  jumlah: number
  catatan?: string | null
}

export interface CreateOrderPayload {
  nama_pelanggan: string
  no_hp: string
  alamat?: string | null
  tanggal_masuk: string
  diskon?: number
  catatan?: string | null
  detail_pesanan: CreateOrderDetailPayload[]
  cara_bayar?: string
  id_metode_pembayaran?: number | null
  jumlah_bayar?: number | null
  status_pembayaran?: string
}

export interface UpdateOrderStatusPayload {
  status_pesanan: OrderStatus
  keterangan?: string | null
}

export interface CreateOrderDeliveryPayload {
  alamat_tujuan?: string | null
  nama_penerima?: string | null
  no_hp_penerima?: string | null
  jadwal?: string | null
  catatan?: string | null
}

export interface OrderResponse {
  data: Order
}
