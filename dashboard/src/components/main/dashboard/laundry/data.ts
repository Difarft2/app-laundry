import {
  BanknoteUndoIcon,
  BarChartIcon,
  BoxTappedIcon,
  CheckCircleIcon,
  ClockIcon,
  DollarIcon,
  HourglassIcon,
  InboxIcon,
  ListIcon,
  MapIcon,
  SparkIcon,
  TruckDeliveryIcon,
} from '@/icons/layout'
import type {
  ActivityItem,
  ActivityStat,
  CourierHistoryItem,
  CourierTask,
  MetricItem,
  OrderItem,
  PaymentMethodItem,
  PickupRequestItem,
  QueueItem,
  QueueSummaryItem,
  RevenuePeriod,
} from './types'

/* -------------------------------------------------------------------------- */
/* Metric cards                                                               */
/* -------------------------------------------------------------------------- */

export const ownerMetrics: MetricItem[] = [
  {
    id: 'total-orders',
    label: 'Total Pesanan',
    value: '128',
    hint: 'Sejak awal bulan ini',
    trend: '+12,5%',
    trendDirection: 'up',
    tone: 'brand',
    icon: ListIcon,
  },
  {
    id: 'new-orders',
    label: 'Pesanan Baru',
    value: '18',
    hint: 'Masuk hari ini',
    trend: '+6',
    trendDirection: 'up',
    tone: 'info',
    icon: InboxIcon,
  },
  {
    id: 'in-process',
    label: 'Dalam Proses',
    value: '26',
    hint: '8 dicuci · 10 dikeringkan · 8 finishing',
    tone: 'warning',
    icon: HourglassIcon,
  },
  {
    id: 'completed',
    label: 'Pesanan Selesai',
    value: '74',
    hint: '58% dari total pesanan',
    trend: '+9,2%',
    trendDirection: 'up',
    tone: 'success',
    icon: CheckCircleIcon,
  },
  {
    id: 'unpaid',
    label: 'Pesanan Belum Lunas',
    value: '12',
    hint: 'Sisa tagihan Rp845.000',
    trend: '-3',
    trendDirection: 'down',
    tone: 'error',
    icon: BanknoteUndoIcon,
  },
  {
    id: 'pending-pickups',
    label: 'Antar-Jemput Menunggu',
    value: '5',
    hint: '3 jemput · 2 antar',
    tone: 'gray',
    icon: TruckDeliveryIcon,
  },
]

export const adminMetrics: MetricItem[] = [
  {
    id: 'new-orders',
    label: 'Pesanan Baru',
    value: '18',
    hint: 'Masuk hari ini',
    trend: '+6',
    trendDirection: 'up',
    tone: 'info',
    icon: InboxIcon,
  },
  {
    id: 'in-process',
    label: 'Dalam Proses',
    value: '26',
    hint: '8 dicuci · 10 dikeringkan · 8 finishing',
    tone: 'warning',
    icon: HourglassIcon,
  },
  {
    id: 'ready',
    label: 'Siap Diambil',
    value: '12',
    hint: '5 pelanggan sudah dihubungi',
    trend: '+4',
    trendDirection: 'up',
    tone: 'success',
    icon: CheckCircleIcon,
  },
  {
    id: 'unpaid',
    label: 'Belum Lunas',
    value: '12',
    hint: 'Sisa tagihan Rp845.000',
    trend: '-3',
    trendDirection: 'down',
    tone: 'error',
    icon: BanknoteUndoIcon,
  },
  {
    id: 'pickup-today',
    label: 'Antar-Jemput Hari Ini',
    value: '7',
    hint: '3 jemput · 2 antar · 2 selesai',
    tone: 'gray',
    icon: TruckDeliveryIcon,
  },
  {
    id: 'active-services',
    label: 'Layanan Aktif',
    value: '12',
    hint: '3 pilihan waktu layanan',
    tone: 'brand',
    icon: ListIcon,
  },
]

export const karyawanMetrics: MetricItem[] = [
  {
    id: 'queue',
    label: 'Antrean Mencuci',
    value: '6',
    hint: 'Menunggu giliran dicuci',
    tone: 'gray',
    icon: HourglassIcon,
  },
  {
    id: 'washing',
    label: 'Sedang Dicuci',
    value: '8',
    hint: '3 di antaranya ekspres',
    tone: 'info',
    icon: BoxTappedIcon,
  },
  {
    id: 'finishing',
    label: 'Finishing',
    value: '8',
    hint: '5 setrika · 3 lipat',
    tone: 'warning',
    icon: SparkIcon,
  },
  {
    id: 'done-today',
    label: 'Selesai Hari Ini',
    value: '12',
    hint: '5 pesanan siap diambil',
    trend: '+4',
    trendDirection: 'up',
    tone: 'success',
    icon: CheckCircleIcon,
  },
]

export const kurirMetrics: MetricItem[] = [
  {
    id: 'active-tasks',
    label: 'Tugas Aktif',
    value: '3',
    hint: '2 jemput · 1 antar',
    tone: 'brand',
    icon: TruckDeliveryIcon,
  },
  {
    id: 'on-the-way',
    label: 'Menuju Lokasi',
    value: '1',
    hint: 'Estimasi tiba 12 menit',
    tone: 'warning',
    icon: MapIcon,
  },
  {
    id: 'done-today',
    label: 'Selesai Hari Ini',
    value: '5',
    hint: '3 jemput · 2 antar',
    trend: '+2',
    trendDirection: 'up',
    tone: 'success',
    icon: CheckCircleIcon,
  },
  {
    id: 'unassigned',
    label: 'Menunggu Penugasan',
    value: '2',
    hint: 'Belum memiliki kurir',
    tone: 'gray',
    icon: ClockIcon,
  },
]

/* -------------------------------------------------------------------------- */
/* Revenue                                                                    */
/* -------------------------------------------------------------------------- */

export const revenuePeriods: RevenuePeriod[] = [
  {
    id: '7d',
    label: '7 Hari',
    categories: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    revenue: [1_250_000, 1_480_000, 1_320_000, 1_750_000, 2_100_000, 2_650_000, 1_850_000],
    orders: [22, 26, 24, 31, 36, 44, 32],
  },
  {
    id: '30d',
    label: '30 Hari',
    categories: ['1', '4', '7', '10', '13', '16', '19', '22', '25', '28'],
    revenue: [
      2_450_000, 2_980_000, 3_150_000, 2_740_000, 3_620_000, 3_980_000, 3_410_000, 4_120_000,
      4_560_000, 4_290_000,
    ],
    orders: [42, 51, 54, 47, 62, 68, 58, 71, 78, 74],
  },
  {
    id: 'month',
    label: 'Bulan Ini',
    categories: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 5'],
    revenue: [8_420_000, 10_180_000, 11_640_000, 12_950_000, 4_290_000],
    orders: [144, 172, 198, 220, 74],
  },
]

export const paymentMethods: PaymentMethodItem[] = [
  {
    id: 'cash',
    label: 'Tunai',
    amount: 6_240_000,
    transactions: 84,
    percentage: 42,
    color: '#465FFF',
  },
  {
    id: 'transfer',
    label: 'Transfer Bank',
    amount: 4_160_000,
    transactions: 56,
    percentage: 28,
    color: '#7592FF',
  },
  {
    id: 'qris',
    label: 'QRIS',
    amount: 3_570_000,
    transactions: 48,
    percentage: 24,
    color: '#12B76A',
  },
  {
    id: 'ewallet',
    label: 'E-Wallet',
    amount: 890_000,
    transactions: 12,
    percentage: 6,
    color: '#F79009',
  },
]

/* -------------------------------------------------------------------------- */
/* Orders                                                                     */
/* -------------------------------------------------------------------------- */

export const recentOrders: OrderItem[] = [
  {
    id: '1',
    invoice: 'PL-20261116-0128',
    customer: 'Ibu Sari Wulandari',
    phone: '0812-3456-7890',
    service: 'Cuci Setrika',
    quantity: '4,5 kg',
    entryDate: '16 Nov · 08.15',
    estimateDate: '18 Nov · 15.00',
    total: 45_000,
    laundryStatus: 'dicuci',
    paymentStatus: 'belum_lunas',
  },
  {
    id: '2',
    invoice: 'PL-20261116-0127',
    customer: 'Bapak Andi Pratama',
    phone: '0813-2211-3344',
    service: 'Express 4 Jam',
    quantity: '3 kg',
    entryDate: '16 Nov · 07.50',
    estimateDate: '16 Nov · 12.00',
    total: 75_000,
    laundryStatus: 'finishing',
    paymentStatus: 'lunas',
  },
  {
    id: '3',
    invoice: 'PL-20261116-0126',
    customer: 'Ibu Rina Kartika',
    phone: '0857-8899-1122',
    service: 'Cuci Kering',
    quantity: '5 kg',
    entryDate: '16 Nov · 07.20',
    estimateDate: '17 Nov · 10.00',
    total: 40_000,
    laundryStatus: 'dikeringkan',
    paymentStatus: 'lunas',
  },
  {
    id: '4',
    invoice: 'PL-20261115-0125',
    customer: 'Bapak Hendra Wijaya',
    phone: '0821-4455-6677',
    service: 'Satuan · Bedcover',
    quantity: '1 item',
    entryDate: '15 Nov · 16.40',
    estimateDate: '18 Nov · 16.00',
    total: 35_000,
    laundryStatus: 'menunggu',
    paymentStatus: 'belum_dibayar',
  },
  {
    id: '5',
    invoice: 'PL-20261115-0124',
    customer: 'Ibu Dewi Lestari',
    phone: '0819-7788-9900',
    service: 'Setrika',
    quantity: '6 kg',
    entryDate: '15 Nov · 14.10',
    estimateDate: '17 Nov · 14.00',
    total: 42_000,
    laundryStatus: 'dicuci',
    paymentStatus: 'lunas',
  },
  {
    id: '6',
    invoice: 'PL-20261115-0123',
    customer: 'Bapak Agus Salim',
    phone: '0838-1234-5678',
    service: 'Cuci Basah',
    quantity: '8 kg',
    entryDate: '15 Nov · 11.30',
    estimateDate: '16 Nov · 11.00',
    total: 48_000,
    laundryStatus: 'selesai',
    paymentStatus: 'lunas',
  },
  {
    id: '7',
    invoice: 'PL-20261115-0122',
    customer: 'Ibu Maya Anggraini',
    phone: '0852-3344-5566',
    service: 'Cuci Setrika',
    quantity: '2,5 kg',
    entryDate: '15 Nov · 09.45',
    estimateDate: '17 Nov · 09.00',
    total: 25_000,
    laundryStatus: 'selesai',
    paymentStatus: 'belum_lunas',
  },
  {
    id: '8',
    invoice: 'PL-20261114-0121',
    customer: 'Bapak Rudi Hartono',
    phone: '0877-6655-4433',
    service: 'Satuan · Sepatu',
    quantity: '2 item',
    entryDate: '14 Nov · 17.20',
    estimateDate: '19 Nov · 17.00',
    total: 60_000,
    laundryStatus: 'selesai',
    paymentStatus: 'lunas',
  },
]

/* -------------------------------------------------------------------------- */
/* Laundry queue                                                              */
/* -------------------------------------------------------------------------- */

export const queueSummary: QueueSummaryItem[] = [
  { label: 'Menunggu', count: 6, tone: 'gray' },
  { label: 'Dicuci', count: 8, tone: 'info' },
  { label: 'Dikeringkan', count: 10, tone: 'brand' },
  { label: 'Finishing', count: 8, tone: 'warning' },
]

export const queueItems: QueueItem[] = [
  {
    id: '1',
    invoice: 'PL-20261116-0128',
    customer: 'Ibu Sari Wulandari',
    service: 'Cuci Setrika',
    quantity: '4,5 kg',
    entryTime: '08.15',
    estimate: 'Besok · 15.00',
    status: 'dicuci',
  },
  {
    id: '2',
    invoice: 'PL-20261116-0127',
    customer: 'Bapak Andi Pratama',
    service: 'Express 4 Jam',
    quantity: '3 kg',
    entryTime: '07.50',
    estimate: 'Hari ini · 12.00',
    status: 'finishing',
    isExpress: true,
  },
  {
    id: '3',
    invoice: 'PL-20261116-0126',
    customer: 'Ibu Rina Kartika',
    service: 'Cuci Kering',
    quantity: '5 kg',
    entryTime: '07.20',
    estimate: 'Besok · 10.00',
    status: 'dikeringkan',
  },
  {
    id: '4',
    invoice: 'PL-20261115-0124',
    customer: 'Ibu Dewi Lestari',
    service: 'Setrika',
    quantity: '6 kg',
    entryTime: '14.10',
    estimate: 'Besok · 14.00',
    status: 'dicuci',
  },
  {
    id: '5',
    invoice: 'PL-20261115-0125',
    customer: 'Bapak Hendra Wijaya',
    service: 'Satuan · Bedcover',
    quantity: '1 item',
    entryTime: '16.40',
    estimate: '18 Nov · 16.00',
    status: 'menunggu',
  },
]

export const queueNotes: string[] = [
  'Pesanan express PL-20261116-0127 harus selesai sebelum pukul 12.00.',
  '4 pesanan telah menunggu antrean lebih dari 2 jam.',
  '5 pesanan selesai dan siap diambil pelanggan.',
]

/* -------------------------------------------------------------------------- */
/* Activity                                                                   */
/* -------------------------------------------------------------------------- */

export const ownerActivityStats: ActivityStat[] = [
  { label: 'Nota baru', value: '18' },
  { label: 'Selesai', value: '12' },
  { label: 'Pembayaran masuk', value: 'Rp1,85 jt' },
  { label: 'Tugas antar-jemput', value: '7' },
]

export const ownerActivities: ActivityItem[] = [
  {
    id: '1',
    time: '09.42',
    title: 'Pembayaran diterima',
    description: 'Ibu Dewi Lestari melunasi nota PL-20261115-0124 sebesar Rp42.000.',
    tone: 'success',
  },
  {
    id: '2',
    time: '09.15',
    title: 'Status laundry diperbarui',
    description: 'PL-20261116-0128 masuk tahap Dicuci oleh Dewi Anggraini.',
    tone: 'info',
  },
  {
    id: '3',
    time: '08.55',
    title: 'Pesanan baru dibuat',
    description: 'PL-20261116-0128 · Ibu Sari Wulandari · Cuci Setrika 4,5 kg.',
    tone: 'brand',
  },
  {
    id: '4',
    time: '08.30',
    title: 'Kurir menuju lokasi',
    description: 'Rizky Maulana menuju lokasi penjemputan Ibu Sari Wulandari.',
    tone: 'warning',
  },
  {
    id: '5',
    time: '08.05',
    title: 'Pesanan selesai',
    description: 'PL-20261114-0121 selesai dan siap diambil pelanggan.',
    tone: 'success',
  },
  {
    id: '6',
    time: '07.45',
    title: 'Pesanan express masuk',
    description: 'PL-20261116-0127 · Bapak Andi Pratama · Express 4 Jam.',
    tone: 'brand',
  },
]

export const karyawanActivityStats: ActivityStat[] = [
  { label: 'Masuk antrean', value: '6' },
  { label: 'Selesai dicuci', value: '8' },
  { label: 'Finishing', value: '8' },
  { label: 'Siap diambil', value: '5' },
]

export const karyawanActivities: ActivityItem[] = [
  {
    id: '1',
    time: '09.20',
    title: 'Finishing dimulai',
    description: 'PL-20261116-0127 dipindahkan ke tahap Finishing (setrika).',
    tone: 'warning',
  },
  {
    id: '2',
    time: '09.05',
    title: 'Pengeringan selesai',
    description: 'PL-20261116-0126 siap masuk tahap Finishing.',
    tone: 'info',
  },
  {
    id: '3',
    time: '08.40',
    title: 'Pencucian selesai',
    description: 'PL-20261115-0124 selesai dicuci dan menunggu pengeringan.',
    tone: 'brand',
  },
  {
    id: '4',
    time: '08.15',
    title: 'Pesanan selesai',
    description: 'PL-20261115-0123 selesai, 8 kg cuci basah siap diambil.',
    tone: 'success',
  },
  {
    id: '5',
    time: '07.55',
    title: 'Antrean bertambah',
    description: 'PL-20261116-0128 masuk antrean menunggu giliran dicuci.',
    tone: 'gray',
  },
]

export const courierActivityStats: ActivityStat[] = [
  { label: 'Tugas selesai', value: '5' },
  { label: 'Penjemputan', value: '3' },
  { label: 'Pengantaran', value: '2' },
  { label: 'Jarak tempuh', value: '18,4 km' },
]

export const courierActivities: ActivityItem[] = [
  {
    id: '1',
    time: '09.15',
    title: 'Menuju lokasi penjemputan',
    description: 'AJ-20261116-0042 · Ibu Sari Wulandari · Jl. Melati No. 12.',
    tone: 'warning',
  },
  {
    id: '2',
    time: '08.40',
    title: 'Tugas pengantaran selesai',
    description: 'AJ-20261116-0041 · Bapak Agus Salim · diterima pelanggan.',
    tone: 'success',
  },
  {
    id: '3',
    time: '08.05',
    title: 'Barang berhasil dijemput',
    description: 'AJ-20261116-0040 · Ibu Maya Anggraini · 2 kantong laundry.',
    tone: 'info',
  },
  {
    id: '4',
    time: '07.35',
    title: 'Memulai perjalanan',
    description: 'Menuju Jl. Anggrek No. 21 untuk penjemputan pertama.',
    tone: 'brand',
  },
  {
    id: '5',
    time: '07.20',
    title: 'Tugas pagi diterima',
    description: '2 penjemputan dan 1 pengantaran ditugaskan untuk hari ini.',
    tone: 'gray',
  },
]

/* -------------------------------------------------------------------------- */
/* Courier                                                                    */
/* -------------------------------------------------------------------------- */

export const courierTasks: CourierTask[] = [
  {
    id: '1',
    taskNumber: 'AJ-20261116-0042',
    type: 'jemput',
    customer: 'Ibu Sari Wulandari',
    phone: '0812-3456-7890',
    address: 'Jl. Melati No. 12, RT 03/RW 05, Kel. Sukamaju',
    schedule: 'Hari ini · 09.00–10.00',
    status: 'menuju_lokasi',
    note: '3 kantong laundry, 1 bedcover. Rumah pagar hijau.',
  },
  {
    id: '2',
    taskNumber: 'AJ-20261116-0043',
    type: 'antar',
    customer: 'Bapak Andi Pratama',
    phone: '0813-2211-3344',
    address: 'Jl. Kenanga No. 8, Perum Griya Asri',
    schedule: 'Hari ini · 12.00–13.00',
    status: 'ditugaskan',
    note: 'Pesanan Express 4 Jam. Mohon konfirmasi saat tiba.',
  },
  {
    id: '3',
    taskNumber: 'AJ-20261116-0044',
    type: 'jemput',
    customer: 'Ibu Rina Kartika',
    phone: '0857-8899-1122',
    address: 'Jl. Anggrek No. 21, Kel. Mekarjaya',
    schedule: 'Hari ini · 14.00–15.00',
    status: 'ditugaskan',
    note: '5 kg cuci kering. Hubungi sebelum tiba.',
  },
]

export const courierHistory: CourierHistoryItem[] = [
  {
    id: '1',
    taskNumber: 'AJ-20261116-0041',
    type: 'antar',
    customer: 'Bapak Agus Salim',
    address: 'Jl. Flamboyan No. 7',
    completedAt: 'Hari ini · 08.40',
  },
  {
    id: '2',
    taskNumber: 'AJ-20261116-0040',
    type: 'jemput',
    customer: 'Ibu Maya Anggraini',
    address: 'Jl. Dahlia No. 19',
    completedAt: 'Hari ini · 08.05',
  },
  {
    id: '3',
    taskNumber: 'AJ-20261115-0039',
    type: 'antar',
    customer: 'Ibu Dewi Lestari',
    address: 'Jl. Cempaka No. 5',
    completedAt: 'Kemarin · 17.20',
  },
  {
    id: '4',
    taskNumber: 'AJ-20261115-0038',
    type: 'jemput',
    customer: 'Bapak Rudi Hartono',
    address: 'Jl. Mawar No. 33',
    completedAt: 'Kemarin · 16.45',
  },
]

export const pickupRequests: PickupRequestItem[] = [
  {
    id: '1',
    taskNumber: 'AJ-20261116-0045',
    type: 'jemput',
    customer: 'Bapak Hendra Wijaya',
    address: 'Jl. Cempaka No. 5, Kel. Sukamaju',
    schedule: 'Hari ini · 10.00',
    status: 'menunggu_kurir',
  },
  {
    id: '2',
    taskNumber: 'AJ-20261116-0046',
    type: 'antar',
    customer: 'Ibu Maya Anggraini',
    address: 'Jl. Dahlia No. 19, Kel. Mekarjaya',
    schedule: 'Hari ini · 13.00',
    status: 'menunggu_kurir',
  },
  {
    id: '3',
    taskNumber: 'AJ-20261116-0047',
    type: 'jemput',
    customer: 'Bapak Agus Salim',
    address: 'Jl. Flamboyan No. 7, Kel. Sukamaju',
    schedule: 'Hari ini · 15.30',
    status: 'menunggu_kurir',
  },
]

/* -------------------------------------------------------------------------- */
/* Helpers                                                                    */
/* -------------------------------------------------------------------------- */

export const revenueTotal = (period: RevenuePeriod): number =>
  period.revenue.reduce((total, value) => total + value, 0)

export const orderTotal = (period: RevenuePeriod): number =>
  period.orders.reduce((total, value) => total + value, 0)

export const paymentTotal = paymentMethods.reduce((total, method) => total + method.amount, 0)

export const dashboardIcons = {
  dollar: DollarIcon,
  chart: BarChartIcon,
}
