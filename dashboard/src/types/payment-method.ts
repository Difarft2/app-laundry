import type { PaginationLinks, PaginationMeta } from './auth'

export interface PaymentMethod {
  id: number
  nama_metode: string
  kode: string
  deskripsi: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface PaymentMethodsQuery {
  search?: string
  /** true = aktif, false = tidak aktif, undefined = semua. */
  is_active?: boolean
  per_page?: number
  page?: number
}

export interface PaginatedPaymentMethods {
  data: PaymentMethod[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface CreatePaymentMethodPayload {
  nama_metode: string
  kode: string
  deskripsi?: string | null
  is_active?: boolean
}

/** Semua field opsional, tetapi field yang dikirim harus memenuhi validasi. */
export type UpdatePaymentMethodPayload = Partial<CreatePaymentMethodPayload>

export interface PaymentMethodResponse {
  success?: boolean
  message?: string
  data: PaymentMethod
}
