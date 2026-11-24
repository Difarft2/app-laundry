import {
  BanknoteUndoIcon,
  BoxTappedIcon,
  CheckCircleIcon,
  ClockIcon,
  DollarIcon,
  HourglassIcon,
  InboxIcon,
  ListIcon,
  MapIcon,
  TruckDeliveryIcon,
  UsersIcon,
} from '@/icons/layout'
import type {
  DashboardActivityApi,
  DashboardMetricsApi,
  DashboardOrderApi,
  DashboardQueueApi,
  DashboardTaskApi,
  PaymentMethodApi,
} from '@/services/dashboard'
import { formatCompactCurrency, formatCurrency, formatDateTime, formatNumber } from '@/utils/format'
import type {
  ActivityItem,
  ActivityStat,
  CourierHistoryItem,
  CourierTask,
  EmployeeActivityItem,
  ExpenseItem,
  LaundryStatus,
  MetricItem,
  OrderItem,
  PaymentMethodItem,
  PickupRequestItem,
  QueueItem,
  QueueSummaryItem,
} from './types'

const timeOnly = (value: string): string => {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(date)
}

const orderStatusToLaundryStatus = (status: string): LaundryStatus => {
  switch (status) {
    case 'dicuci':
      return 'dicuci'
    case 'dikeringkan':
      return 'dikeringkan'
    case 'dilipat':
    case 'disetrika':
    case 'diproses':
    case 'siap_diambil':
      return 'finishing'
    case 'selesai':
      return 'selesai'
    default:
      return 'menunggu'
  }
}

const paymentStatusOf = (order: DashboardOrderApi): 'belum_dibayar' | 'belum_lunas' | 'lunas' => {
  if (Number(order.sisa_pembayaran) <= 0) return 'lunas'
  if (Number(order.total_terbayar) > 0) return 'belum_lunas'
  return 'belum_dibayar'
}

const taskTypeOf = (task: DashboardTaskApi): 'jemput' | 'antar' =>
  task.jenis_antar_jemput === 'penjemputan' ? 'jemput' : 'antar'

const taskCustomer = (task: DashboardTaskApi): string =>
  task.pesanan?.pelanggan?.nama || task.nama_penerima || '-'

const taskPhone = (task: DashboardTaskApi): string =>
  task.no_hp_penerima || task.pesanan?.pelanggan?.no_hp || '-'

const activityTone = (type: DashboardActivityApi['type']): ActivityItem['tone'] => {
  switch (type) {
    case 'process':
      return 'info'
    case 'payment':
      return 'success'
    case 'delivery':
      return 'warning'
    default:
      return 'brand'
  }
}

export const mapMetrics = (
  metrics: DashboardMetricsApi,
  role: string,
): MetricItem[] => {
  if (role === 'kurir') {
    return [
      {
        id: 'my-tasks',
        label: 'Tugas Aktif Saya',
        value: formatNumber(metrics.myTasks ?? 0),
        hint: 'Tugas yang belum diselesaikan',
        tone: 'brand',
        icon: TruckDeliveryIcon,
      },
      {
        id: 'on-delivery',
        label: 'Sedang Diantar',
        value: formatNumber(metrics.onDelivery ?? 0),
        hint: 'Pengantaran yang masih berjalan',
        tone: 'info',
        icon: MapIcon,
      },
      {
        id: 'waiting-courier',
        label: 'Menunggu Kurir',
        value: formatNumber(metrics.waitingCourier ?? 0),
        hint: 'Tugas yang belum diambil kurir',
        tone: 'warning',
        icon: ClockIcon,
      },
      {
        id: 'completed-today',
        label: 'Selesai Hari Ini',
        value: formatNumber(metrics.myCompletedToday ?? 0),
        hint: 'Tugas yang Anda selesaikan hari ini',
        tone: 'success',
        icon: CheckCircleIcon,
      },
    ]
  }

  if (role === 'karyawan') {
    return [
      {
        id: 'new-orders',
        label: 'Pesanan Hari Ini',
        value: formatNumber(metrics.newOrdersToday),
        hint: 'Pesanan yang masuk hari ini',
        tone: 'brand',
        icon: ListIcon,
      },
      {
        id: 'queue-washing',
        label: 'Menunggu Cuci',
        value: formatNumber(metrics.queueWashing),
        hint: 'Belum mulai diproses',
        tone: 'warning',
        icon: HourglassIcon,
      },
      {
        id: 'washing',
        label: 'Sedang Dicuci',
        value: formatNumber(metrics.washingInProgress),
        hint: 'Proses pencucian berjalan',
        tone: 'info',
        icon: BoxTappedIcon,
      },
      {
        id: 'finishing',
        label: 'Finishing',
        value: formatNumber(metrics.finishing),
        hint: 'Pengeringan dan finishing',
        tone: 'gray',
        icon: ClockIcon,
      },
      {
        id: 'done-today',
        label: 'Selesai Hari Ini',
        value: formatNumber(metrics.doneToday),
        hint: 'Pesanan yang selesai hari ini',
        tone: 'success',
        icon: CheckCircleIcon,
      },
      {
        id: 'unpaid',
        label: 'Belum Dibayar',
        value: formatCompactCurrency(metrics.unpaid),
        hint: 'Sisa tagihan seluruh pesanan',
        tone: 'error',
        icon: BanknoteUndoIcon,
      },
    ]
  }

  return [
    {
      id: 'total-orders',
      label: role === 'admin' ? 'Total Pesanan' : 'Total Pesanan',
      value: formatNumber(metrics.totalOrders),
      hint: 'Seluruh pesanan yang tercatat',
      tone: 'brand',
      icon: ListIcon,
    },
    {
      id: 'completed',
      label: 'Pesanan Selesai',
      value: formatNumber(metrics.completedOrders),
      hint: `${formatNumber(metrics.newOrdersToday)} pesanan masuk hari ini`,
      tone: 'success',
      icon: CheckCircleIcon,
    },
    {
      id: 'revenue',
      label: 'Total Pendapatan',
      value: formatCompactCurrency(metrics.revenue),
      hint: 'Pembayaran berhasil tercatat',
      tone: 'info',
      icon: DollarIcon,
    },
    {
      id: 'expenses',
      label: 'Pengeluaran Bulan Ini',
      value: formatCompactCurrency(metrics.expenses),
      hint: 'Pengeluaran operasional tercatat',
      tone: 'warning',
      icon: BanknoteUndoIcon,
    },
    {
      id: 'unpaid',
      label: 'Belum Dibayar',
      value: formatCompactCurrency(metrics.unpaid),
      hint: 'Sisa tagihan pesanan aktif',
      tone: 'error',
      icon: BanknoteUndoIcon,
    },
    {
      id: 'active-users',
      label: 'Akun Aktif',
      value: formatNumber(metrics.activeEmployees + metrics.activeCouriers),
      hint: `${metrics.activeEmployees} karyawan · ${metrics.activeCouriers} kurir`,
      tone: 'warning',
      icon: UsersIcon,
    },
    {
      id: 'delivery',
      label: 'Antar-Jemput',
      value: formatNumber(metrics.pendingPickups + metrics.onDelivery),
      hint: `${metrics.pendingPickups} jemput · ${metrics.onDelivery} antar`,
      tone: 'gray',
      icon: TruckDeliveryIcon,
    },
  ]
}

export const mapActivityStats = (
  metrics: DashboardMetricsApi,
  role: string,
): ActivityStat[] => {
  if (role === 'kurir') {
    return [
      { label: 'Tugas aktif', value: formatNumber(metrics.myTasks ?? 0) },
      { label: 'Diantar', value: formatNumber(metrics.onDelivery ?? 0) },
      { label: 'Menunggu', value: formatNumber(metrics.waitingCourier ?? 0) },
      { label: 'Selesai hari ini', value: formatNumber(metrics.myCompletedToday ?? 0) },
    ]
  }

  if (role === 'karyawan') {
    return [
      { label: 'Masuk hari ini', value: formatNumber(metrics.newOrdersToday) },
      { label: 'Selesai hari ini', value: formatNumber(metrics.doneToday) },
      { label: 'Finishing', value: formatNumber(metrics.finishing) },
      { label: 'Belum dibayar', value: formatCompactCurrency(metrics.unpaid) },
    ]
  }

  return [
    { label: 'Pesanan baru', value: formatNumber(metrics.newOrdersToday) },
    { label: 'Selesai hari ini', value: formatNumber(metrics.doneToday) },
    { label: 'Pendapatan', value: formatCompactCurrency(metrics.revenue) },
    { label: 'Menunggu kurir', value: formatNumber(metrics.waitingCourier) },
  ]
}

export const mapActivities = (activities: DashboardActivityApi[]): ActivityItem[] =>
  activities.map((activity) => ({
    id: activity.id,
    time: timeOnly(activity.time),
    title: activity.title,
    description: activity.user
      ? `${activity.description} · ${activity.user}`
      : activity.description,
    tone: activityTone(activity.type),
  }))

export const mapEmployeeActivities = (
  activities: DashboardActivityApi[],
): EmployeeActivityItem[] =>
  activities.map((activity) => ({
    id: activity.id,
    time: timeOnly(activity.time),
    title: activity.title,
    description: activity.description,
    tone: activityTone(activity.type),
    employee: activity.user ?? 'Sistem',
    employeeRole: activity.role ?? (activity.type === 'delivery' ? 'Kurir' : 'Karyawan'),
  }))

export const mapOrders = (orders: DashboardOrderApi[]): OrderItem[] =>
  orders.map((order) => {
    const detail = order.detail_pesanan?.[0]
    const extras = Math.max(0, (order.detail_pesanan?.length ?? 0) - 1)

    return {
      id: String(order.id_pesanan),
      invoice: order.kode_pesanan,
      customer: order.pelanggan?.nama ?? '-',
      phone: order.pelanggan?.no_hp ?? '-',
      service: detail?.nama_layanan ?? '-',
      quantity: detail
        ? `${detail.jumlah} ${detail.satuan}${extras > 0 ? ` +${extras}` : ''}`
        : '-',
      entryDate: formatDateTime(order.tanggal_masuk),
      estimateDate: formatDateTime(order.estimasi_selesai),
      total: Number(order.total_harga),
      laundryStatus: orderStatusToLaundryStatus(order.status_pesanan),
      paymentStatus: paymentStatusOf(order),
    }
  })

export const mapQueue = (
  queue: DashboardQueueApi[],
  metrics: DashboardMetricsApi,
): {
  summary: QueueSummaryItem[]
  items: QueueItem[]
  notes: string[]
} => {
  const summary: QueueSummaryItem[] = [
    { label: 'Menunggu', count: metrics.queueWashing, tone: 'gray' },
    { label: 'Dicuci', count: metrics.washingInProgress, tone: 'info' },
    { label: 'Finishing', count: metrics.finishing, tone: 'warning' },
    { label: 'Selesai hari ini', count: metrics.doneToday, tone: 'success' },
  ]

  const items: QueueItem[] = queue.map((item) => ({
    id: String(item.id_pesanan),
    invoice: item.kode_pesanan,
    customer: item.pelanggan?.nama ?? '-',
    service: item.layanan,
    quantity: `${item.total_jumlah} ${item.satuan}`.trim(),
    entryTime: formatDateTime(item.tanggal_masuk),
    estimate: formatDateTime(item.estimasi_selesai),
    status: orderStatusToLaundryStatus(item.status_pesanan),
  }))

  const overdue = queue.filter((item) => {
    if (!item.estimasi_selesai) return false
    return new Date(item.estimasi_selesai).getTime() < Date.now()
  }).length
  const waiting = metrics.queueWashing
  const finishing = metrics.finishing

  const notes: string[] = []
  if (overdue > 0) notes.push(`${overdue} pesanan melewati estimasi selesai.`)
  if (waiting > 0) notes.push(`${waiting} pesanan menunggu giliran cuci.`)
  if (finishing > 0) notes.push(`${finishing} pesanan berada di tahap finishing.`)
  if (notes.length === 0) notes.push('Tidak ada antrean yang perlu ditindaklanjuti.')

  return { summary, items, notes }
}

export const mapTasks = (tasks: DashboardTaskApi[]): CourierTask[] =>
  tasks.map((task) => ({
    id: String(task.id_antar_jemput),
    taskNumber: `AJ-${task.id_antar_jemput}`,
    type: taskTypeOf(task),
    customer: taskCustomer(task),
    phone: taskPhone(task),
    address: task.alamat_tujuan || '-',
    schedule: formatDateTime(task.jadwal),
    status: task.status_antar_jemput as CourierTask['status'],
    note: task.catatan ?? undefined,
  }))

export const mapCourierHistory = (tasks: DashboardTaskApi[]): CourierHistoryItem[] =>
  tasks.map((task) => ({
    id: String(task.id_antar_jemput),
    taskNumber: `AJ-${task.id_antar_jemput}`,
    type: taskTypeOf(task),
    customer: taskCustomer(task),
    address: task.alamat_tujuan || '-',
    completedAt: formatDateTime(task.tanggal_selesai),
  }))

export const mapPickupRequests = (tasks: DashboardTaskApi[]): PickupRequestItem[] =>
  tasks.map((task) => ({
    id: String(task.id_antar_jemput),
    taskNumber: `AJ-${task.id_antar_jemput}`,
    type: taskTypeOf(task),
    customer: taskCustomer(task),
    address: task.alamat_tujuan || '-',
    schedule: formatDateTime(task.jadwal),
    status: task.status_antar_jemput as PickupRequestItem['status'],
  }))

const methodColors: Record<string, string> = {
  tunai: '#FFD700',
  transfer_bank: '#1E90FF',
  qris: '#FF4500',
  e_wallet: '#8B5CF6',
}

export const mapPaymentMethods = (methods: PaymentMethodApi[]): PaymentMethodItem[] => {
  const total = methods.reduce((sum, method) => sum + Number(method.amount), 0)

  return methods.map((method) => ({
    id: String(method.id),
    label: method.label,
    amount: Number(method.amount),
    transactions: Number(method.transactions),
    percentage: total > 0 ? Math.round((Number(method.amount) / total) * 100) : 0,
    color: methodColors[method.kode] ?? '#6C757D',
  }))
}

const expenseColors = ['#F04438', '#F79009', '#7A5AF8', '#12B76A', '#2E90FA', '#667085']

export const mapExpenses = (
  items: Array<{ id: string; label: string; amount: number; percentage: number }>,
): ExpenseItem[] =>
  items.map((item, index) => ({
    id: item.id,
    label: item.label,
    amount: Number(item.amount),
    percentage: Number(item.percentage),
    color: expenseColors[index % expenseColors.length],
  }))

export const formatMetricCurrency = (value: number): string => formatCurrency(value)
