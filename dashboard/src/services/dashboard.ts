import { http } from './http'

export interface RevenuePeriod {
  id: string
  label: string
  categories: string[]
  orders: number[]
  revenue: number[]
}

export interface OrderSummary {
  total: number
  completed: number
  revenue: number
  pendingPayment: number
}

export interface PaymentMethodSummary {
  id: number
  label: string
  kode: string
  amount: number
  transactions: number
  color: string
}

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

export interface DashboardMetrics {
  totalOrders: number
  completedOrders: number
  revenue: number
  expenses: number
  activeEmployees: number
  pendingPickups: number
  newOrdersToday: number
  queueWashing: number
  washingInProgress: number
  finishing: number
  doneToday: number
  unpaid: number
}

/** Ambil daftar pesanan dengan summary. */
export const fetchOrders = async (): Promise<{
  orders: any[]
  summary: OrderSummary
}> => {
  const { data } = await http.get('/orders', { params: { per_page: 50 } })
  const orders = data.data ?? []

  const completed = orders.filter((o: any) => o.status_pesanan === 'selesai')
  const revenue = completed.reduce((s: number, o: any) => s + (o.total_terbayar ?? 0), 0)
  const pendingPayment = orders.reduce(
    (s: number, o: any) => {
      const berhasil = (o.pembayaran ?? []).filter(
        (p: any) => p.status_pembayaran === 'berhasil'
      )
      return s + (berhasil.reduce((ss: number, p: any) => ss + (p.jumlah_bayar ?? 0), 0))
    },
    0,
  )

  return {
    orders,
    summary: {
      total: orders.length,
      completed: completed.length,
      revenue: Number(revenue.toFixed(2)),
      pendingPayment: Number(pendingPayment.toFixed(2)),
    },
  }
}

/** Ambil metode pembayaran summary (30 hari terakhir). */
export const fetchPaymentMethodsSummary = async (): Promise<PaymentMethodSummary[]> => {
  const { data } = await http.get('/payment-methods', {
    params: { per_page: 100, with_summary: true },
  })
  const methods = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []

  const colors: Record<string, string> = {
    tunai: '#FFD700',
    transfer_bank: '#1E90FF',
    qris: '#FF4500',
  }

  return methods.map((m: any) => ({
    id: Number(m.id ?? m.id_metode_pembayaran),
    label: m.nama_metode,
    kode: m.kode,
    amount: Number(m.amount ?? 0),
    transactions: Number(m.transactions ?? 0),
    color: colors[m.kode] || '#6C757D',
  }))
}

const localDateKey = (date: Date): string => {
  const pad = (value: number) => String(value).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

/** Ambil pendapatan & transaksi per hari sesuai rentang waktu yang dipilih. */
export const fetchRevenuePeriod = async (
  periodId: '7d' | '30d' | 'month' = '7d',
): Promise<RevenuePeriod> => {
  const { data } = await http.get('/orders', { params: { per_page: 100 } })
  const orders: any[] = data?.data ?? []

  const today = new Date()
  const startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  let days: number
  let label: string

  if (periodId === 'month') {
    startDate.setDate(1)
    days = today.getDate()
    label = 'Bulan Ini'
  } else if (periodId === '30d') {
    startDate.setDate(startDate.getDate() - 29)
    days = 30
    label = '30 Hari'
  } else {
    startDate.setDate(startDate.getDate() - 6)
    days = 7
    label = '7 Hari'
  }

  const buckets = new Map<string, { orders: number; revenue: number }>()

  for (let offset = 0; offset < days; offset += 1) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + offset)
    buckets.set(localDateKey(date), { orders: 0, revenue: 0 })
  }

  orders.forEach((order) => {
    if (!order.tanggal_masuk) return
    const key = localDateKey(new Date(order.tanggal_masuk))
    const bucket = buckets.get(key)
    if (!bucket) return

    bucket.orders += 1
    bucket.revenue += Number(order.total_terbayar ?? 0)
  })

  const formatter = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' })
  const categories: string[] = []
  const ordersData: number[] = []
  const revenueData: number[] = []

  buckets.forEach((bucket, key) => {
    const [year, month, day] = key.split('-').map(Number)
    categories.push(formatter.format(new Date(year, month - 1, day)))
    ordersData.push(bucket.orders)
    revenueData.push(Number(bucket.revenue.toFixed(2)))
  })

  return { id: periodId, label, categories, orders: ordersData, revenue: revenueData }
}

/** Ambil metrik dashboard per role. */
export const fetchDashboardMetrics = async (role: 'admin' | 'owner' | 'karyawan'): Promise<DashboardMetrics> => {
  const ordersResult = await fetchOrders()

  switch (role) {
    case 'admin': {
      const all = ordersResult.orders
      const completed = all.filter((o: any) => o.status_pesanan === 'selesai')
      const totalRevenue = completed.reduce((s: number, o: any) => s + (o.total_terbayar ?? 0), 0)

      return {
        totalOrders: all.length,
        completedOrders: completed.length,
        revenue: Number(totalRevenue.toFixed(2)),
        expenses: 0,
        activeEmployees: 0,
        pendingPickups: 0,
        newOrdersToday: 0,
        queueWashing: 0,
        washingInProgress: 0,
        finishing: 0,
        doneToday: completed.filter(
          (o: any) => o.estimasi_selesai && new Date(o.estimasi_selesai) <= new Date(),
        ).length,
        unpaid: 0,
      }
    }

    case 'owner': {
      const today = new Date()
      const startM = new Date(today.getFullYear(), today.getMonth(), 1).getTime()
      const endM = new Date(today.getFullYear(), today.getMonth() + 1, 0).getTime()

      const all = ordersResult.orders
      const monthOrders = all.filter(
        (o: any) => new Date(o.tanggal_masuk).getTime() >= startM && new Date(o.tanggal_masuk).getTime() <= endM,
      )
      const monthCompleted = monthOrders.filter((o: any) => o.status_pesanan === 'selesai')

      const totalRevenue = monthOrders.reduce(
        (s: number, o: any) => {
          const berhasil = (o.pembayaran ?? []).filter(
            (p: any) => p.status_pembayaran === 'berhasil',
          )
          return s + (berhasil.reduce((ss: number, p: any) => ss + (p.jumlah_bayar ?? 0), 0) ?? 0)
        },
        0,
      )

      return {
        totalOrders: monthOrders.length,
        completedOrders: monthCompleted.length,
        revenue: Number(totalRevenue.toFixed(2)),
        expenses: 0,
        activeEmployees: 0,
        pendingPickups: 0,
        newOrdersToday: monthOrders.filter(
          (o: any) => new Date(o.tanggal_masuk).getTime() <= new Date().getTime(),
        ).length,
        queueWashing: 0,
        washingInProgress: 0,
        finishing: 0,
        doneToday: monthCompleted.filter(
          (o: any) => o.estimasi_selesai && new Date(o.estimasi_selesai) <= new Date(),
        ).length,
        unpaid: 0,
      }
    }

    case 'karyawan': {
      const today = new Date()
      const startD = new Date(today.getFullYear(), today.getMonth(), today.getDate()).getTime()

      const all = ordersResult.orders
      const todayOrders = all.filter(
        (o: any) => new Date(o.tanggal_masuk).getTime() >= startD,
      )
      const todayCompleted = todayOrders.filter((o: any) => o.status_pesanan === 'selesai')

      const queueWashing = todayOrders.filter(
        (o: any) => o.detailPesanan && o.detailPesanan.length > 0,
      ).length

      const washingInProgress = todayOrders.filter(
        (o: any) =>
          o.status_pesanan &&
          o.status_pesanan !== 'selesai' &&
          o.status_pesanan !== 'pending',
      ).length

      const finishingToday = todayOrders.filter(
        (o: any) => o.estimasi_selesai && new Date(o.estimasi_selesai) <= new Date(),
      ).length

      const unpaidToday = todayOrders.reduce(
        (s: number, o: any) => s + (o.sisa_pembayaran ?? 0),
        0,
      )

      return {
        totalOrders: todayOrders.length,
        completedOrders: todayCompleted.length,
        revenue: 0,
        expenses: 0,
        activeEmployees: 0,
        pendingPickups: 0,
        newOrdersToday: todayOrders.length,
        queueWashing,
        washingInProgress,
        finishing: finishingToday,
        doneToday: todayCompleted.length,
        unpaid: Number(unpaidToday.toFixed(2)),
      }
    }
  }
}