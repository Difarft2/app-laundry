import type { PaginationLinks, PaginationMeta } from './auth'

export type PaymentStatus = 'berhasil' | 'menunggu' | 'dibatalkan'

export type PaymentType = 'dp' | 'angsuran' | 'pelunasan'

export interface Payment {
  id_pembayaran: number
  kode_pembayaran: string
  id_pesanan: number
  kode_pesanan: string | null
  nama_pelanggan: string | null
  id_metode_pembayaran: number
  nama_metode: string | null
  kode_metode: string | null
  id_user: number | null
  nama_user: string | null
  jumlah_bayar: number
  idempotency_key: string | null
  amount_received: number
  amount_applied: number
  change_amount: number
  jenis_pembayaran: PaymentType
  status_pembayaran: PaymentStatus
  tanggal_pembayaran: string
  catatan: string | null
  created_at: string
  updated_at: string
}

export interface PaymentsQuery {
  search?: string
  status_pembayaran?: PaymentStatus
  jenis_pembayaran?: PaymentType
  id_metode_pembayaran?: number
  tanggal_mulai?: string
  tanggal_selesai?: string
  per_page?: number
  page?: number
}

export interface PaginatedPayments {
  data: Payment[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface CreatePaymentPayload {
  id_pesanan: number
  id_metode_pembayaran: number
  jumlah_bayar: number
  idempotency_key: string
  amount_received: number
  jenis_pembayaran: PaymentType
  status_pembayaran?: 'berhasil' | 'menunggu'
  tanggal_pembayaran?: string | null
  catatan?: string | null
}

export interface PaymentResponse {
  success?: boolean
  message?: string
  data: Payment
}
