import type { PaginationLinks, PaginationMeta } from './auth'

export type ServiceType = 'kiloan' | 'satuan'

export type ServiceFinishing = 'lipat' | 'setrika' | 'tanpa_finishing'

export interface ServiceTime {
  id: number
  nama_waktu: string
  estimasi_min_jam: number | null
  estimasi_max_jam: number | null
}

export interface ServiceTimeDetail extends ServiceTime {
  deskripsi: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface ServiceTimesQuery {
  search?: string
  is_active?: boolean
  per_page?: number
  page?: number
}

export interface PaginatedServiceTimes {
  data: ServiceTimeDetail[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface CreateServiceTimePayload {
  nama_waktu: string
  estimasi_min_jam?: number | null
  estimasi_max_jam?: number | null
  deskripsi?: string | null
  is_active?: boolean
}

export type UpdateServiceTimePayload = Partial<CreateServiceTimePayload>

export interface ServiceTimeResponse {
  success?: boolean
  message?: string
  data: ServiceTimeDetail
}

export interface Service {
  id: number
  nama_layanan: string
  jenis_layanan: ServiceType
  jenis_finishing: ServiceFinishing
  /** Backend mengirim string desimal, mis. "7000.00". */
  harga: string
  deskripsi: string | null
  is_active: boolean
  waktu_layanan: ServiceTime | null
  created_at: string
  updated_at: string
}

export interface ServicesQuery {
  search?: string
  jenis_layanan?: ServiceType
  /** true = aktif, false = tidak aktif, undefined = semua. */
  is_active?: boolean
  per_page?: number
  page?: number
}

export interface PaginatedServices {
  data: Service[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface CreateServicePayload {
  nama_layanan: string
  id_waktu_layanan?: number | null
  jenis_layanan: ServiceType
  jenis_finishing: ServiceFinishing
  harga: number
  deskripsi?: string | null
  is_active?: boolean
}

/** Semua field opsional, tetapi field yang dikirim harus memenuhi validasi. */
export type UpdateServicePayload = Partial<CreateServicePayload>

export interface ServiceResponse {
  success?: boolean
  message?: string
  data: Service
}
