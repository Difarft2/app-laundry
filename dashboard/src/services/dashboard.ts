import { http } from './http'

export interface PaymentMethodOption {
  id: number
  nama_metode: string
  kode: string
}

/** Daftar metode pembayaran aktif untuk dropdown form pesanan. */
export const fetchActivePaymentMethods = async (): Promise<PaymentMethodOption[]> => {
  const { data } = await http.get('/payment-methods', {
    params: { is_active: true, per_page: 100 },
  })
  const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []

  return list.map((m: any) => ({
    id: Number(m.id ?? m.id_metode_pembayaran),
    nama_metode: m.nama_metode,
    kode: m.kode,
  }))
}

export type DashboardPeriod = '7d' | '30d' | 'month'

export interface DashboardMetricsApi {
  totalOrders: number
  completedOrders: number
  revenue: number
  expenses: number
  activeEmployees: number
  activeCouriers: number
  pendingPickups: number
  newOrdersToday: number
  queueWashing: number
  washingInProgress: number
  finishing: number
  doneToday: number
  unpaid: number
  waitingCourier: number
  onDelivery: number
  completedDeliveriesToday: number
  myTasks?: number
  myCompletedToday?: number
}

export interface RevenueChartApi {
  id?: DashboardPeriod
  label?: string
  categories: string[]
  orders: number[]
  revenue: number[]
}

/** Nama lama yang dipakai komponen grafik. */
export type RevenuePeriod = RevenueChartApi

export interface PaymentMethodApi {
  id: number
  label: string
  kode: string
  amount: number
  transactions: number
}

export interface DashboardActivityApi {
  id: string
  type: 'order' | 'process' | 'payment' | 'delivery'
  title: string
  description: string
  user?: string | null
  role?: string | null
  time: string
}

export interface DashboardOrderApi {
  id_pesanan: number
  kode_pesanan: string
  pelanggan: { nama: string; no_hp: string; alamat: string }
  tanggal_masuk: string
  estimasi_selesai: string | null
  tanggal_selesai: string | null
  status_pesanan: string
  total_harga: number
  total_terbayar: number
  sisa_pembayaran: number
  pembayaran?: Array<{ status_pembayaran: string; jumlah_bayar: number }>
  detail_pesanan?: Array<{
    nama_layanan: string
    jumlah: number
    satuan: string
  }>
}

export interface DashboardQueueApi {
  id_pesanan: number
  kode_pesanan: string
  pelanggan: { nama: string; no_hp: string }
  tanggal_masuk: string
  estimasi_selesai: string | null
  status_pesanan: string
  layanan: string
  jumlah_layanan: number
  total_jumlah: number
  satuan: string
}

export interface DashboardTaskApi {
  id_antar_jemput: number
  sumber: 'internal' | 'landing'
  jenis_antar_jemput: 'penjemputan' | 'pengantaran'
  status_antar_jemput: string
  alamat_tujuan: string
  nama_penerima: string
  no_hp_penerima: string
  jadwal: string | null
  tanggal_selesai: string | null
  catatan: string | null
  created_at: string
  updated_at: string
  kurir?: { id: number; name: string; no_hp: string } | null
  pesanan?: {
    id_pesanan: number
    kode_pesanan: string
    pelanggan?: { nama: string; no_hp: string; alamat: string }
  } | null
}

export interface DashboardApi {
  success: boolean
  data: {
    role: 'owner' | 'admin' | 'karyawan' | 'kurir' | 'user'
    metrics: DashboardMetricsApi
    charts: {
      revenue: RevenueChartApi
      payment_methods: PaymentMethodApi[]
      expenses: Array<{
        id: string
        label: string
        amount: number
        percentage: number
      }>
    }
    recent: {
      orders: DashboardOrderApi[]
      delivery_tasks: DashboardTaskApi[]
      activities: DashboardActivityApi[]
    }
    laundry: {
      queue: DashboardQueueApi[]
    }
    delivery: {
      active_tasks: DashboardTaskApi[]
      history: DashboardTaskApi[]
      pickup_requests: DashboardTaskApi[]
      landing_requests: DashboardTaskApi[]
    }
  }
}

/** Satu sumber data untuk seluruh widget dashboard, sudah di-scope oleh role di backend. */
export const fetchDashboard = async (period: DashboardPeriod = '7d'): Promise<DashboardApi> => {
  const { data } = await http.get<DashboardApi>('/dashboard', { params: { period } })
  return data
}
