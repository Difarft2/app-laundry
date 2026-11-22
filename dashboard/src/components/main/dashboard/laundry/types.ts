import type { Component } from 'vue'

export type DashboardRole = 'owner' | 'admin' | 'karyawan' | 'kurir'

export type MetricTone = 'brand' | 'info' | 'success' | 'warning' | 'error' | 'gray'

export type BadgeTone = 'primary' | 'success' | 'error' | 'warning' | 'info' | 'gray'

export type LaundryStatus = 'menunggu' | 'dicuci' | 'dikeringkan' | 'finishing' | 'selesai'

export type PaymentStatus = 'belum_dibayar' | 'belum_lunas' | 'lunas'

export type CourierTaskStatus =
  | 'menunggu_kurir'
  | 'ditugaskan'
  | 'menuju_lokasi'
  | 'barang_diambil'
  | 'barang_diantar'
  | 'selesai'

export type CourierTaskType = 'jemput' | 'antar'

export type RevenuePeriodId = '7d' | '30d' | 'month'

export type ActivityTone = 'brand' | 'info' | 'success' | 'warning' | 'error' | 'gray'

export interface MetricItem {
  id: string
  label: string
  value: string
  hint: string
  trend?: string
  trendDirection?: 'up' | 'down' | 'flat'
  tone: MetricTone
  icon: Component
}

export interface OrderItem {
  id: string
  invoice: string
  customer: string
  phone: string
  service: string
  quantity: string
  entryDate: string
  estimateDate: string
  total: number
  laundryStatus: LaundryStatus
  paymentStatus: PaymentStatus
}

export interface RevenuePeriod {
  id: RevenuePeriodId
  label: string
  categories: string[]
  revenue: number[]
  orders: number[]
}

export interface PaymentMethodItem {
  id: string
  label: string
  amount: number
  transactions: number
  percentage: number
  color: string
}

export interface ExpenseItem {
  id: string
  label: string
  amount: number
  percentage: number
  color: string
}

export interface QueueSummaryItem {
  label: string
  count: number
  tone: MetricTone
}

export interface QueueItem {
  id: string
  invoice: string
  customer: string
  service: string
  quantity: string
  entryTime: string
  estimate: string
  status: LaundryStatus
  isExpress?: boolean
}

export interface ActivityStat {
  label: string
  value: string
}

export interface ActivityItem {
  id: string
  time: string
  title: string
  description: string
  tone: ActivityTone
}

export interface EmployeeActivityItem extends ActivityItem {
  employee: string
  employeeRole: string
}

export interface CourierTask {
  id: string
  taskNumber: string
  type: CourierTaskType
  customer: string
  phone: string
  address: string
  schedule: string
  status: CourierTaskStatus
  note?: string
}

export interface CourierHistoryItem {
  id: string
  taskNumber: string
  type: CourierTaskType
  customer: string
  address: string
  completedAt: string
}

export interface PickupRequestItem {
  id: string
  taskNumber: string
  type: CourierTaskType
  customer: string
  address: string
  schedule: string
  status: CourierTaskStatus
}
