<script setup lang="ts">
import { computed } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import CourierHistory from '@/components/main/dashboard/laundry/CourierHistory.vue'
import CourierTasks from '@/components/main/dashboard/laundry/CourierTasks.vue'
import DailyActivity from '@/components/main/dashboard/laundry/DailyActivity.vue'
import DashboardHeader from '@/components/main/dashboard/laundry/DashboardHeader.vue'
import LaundryQueue from '@/components/main/dashboard/laundry/LaundryQueue.vue'
import MetricsGrid from '@/components/main/dashboard/laundry/MetricsGrid.vue'
import PaymentBreakdown from '@/components/main/dashboard/laundry/PaymentBreakdown.vue'
import PickupRequests from '@/components/main/dashboard/laundry/PickupRequests.vue'
import RecentOrders from '@/components/main/dashboard/laundry/RecentOrders.vue'
import RevenueChart from '@/components/main/dashboard/laundry/RevenueChart.vue'
import {
  adminMetrics,
  courierActivities,
  courierActivityStats,
  karyawanActivities,
  karyawanActivityStats,
  karyawanMetrics,
  kurirMetrics,
  ownerActivities,
  ownerActivityStats,
  ownerMetrics,
} from '@/components/main/dashboard/laundry/data'
import type { DashboardRole } from '@/components/main/dashboard/laundry/types'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

/**
 * Dashboard selalu mengikuti role akun yang sedang login. Tidak ada
 * pratinjau role agar setiap pengguna hanya melihat tampilan miliknya.
 */
const role = computed<DashboardRole>(() => auth.role ?? 'karyawan')

const isOwner = computed(() => role.value === 'owner')
const isAdmin = computed(() => role.value === 'admin')
const isManager = computed(() => isOwner.value || isAdmin.value)

const canViewFinance = computed(() => auth.hasPermission('reports.view'))
const canViewPayments = computed(() => auth.hasPermission('payments.view'))
const canViewOrders = computed(() => auth.hasPermission('orders.view'))
const canViewLaundry = computed(() => auth.hasPermission('laundry.view'))
const canViewDelivery = computed(() => auth.hasPermission('delivery.view'))

const metrics = computed(() => {
  if (role.value === 'karyawan') return karyawanMetrics
  if (role.value === 'kurir') return kurirMetrics
  if (role.value === 'admin') return adminMetrics
  return ownerMetrics
})

const activityStats = computed(() => {
  if (role.value === 'karyawan') return karyawanActivityStats
  if (role.value === 'kurir') return courierActivityStats
  return ownerActivityStats
})

const activities = computed(() => {
  if (role.value === 'karyawan') return karyawanActivities
  if (role.value === 'kurir') return courierActivities
  return ownerActivities
})

const activityTitle = computed(() => {
  if (role.value === 'karyawan') return 'Aktivitas Proses Laundry'
  if (role.value === 'kurir') return 'Aktivitas Tugas Hari Ini'
  if (role.value === 'admin') return 'Ringkasan Aktivitas Operasional'
  return 'Ringkasan Aktivitas Hari Ini'
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-4 md:space-y-6">
      <DashboardHeader :role="role" :name="auth.user?.name ?? 'Pengguna'" />

      <MetricsGrid :metrics="metrics" />

      <!-- Owner: fokus pendapatan dan ringkasan bisnis. -->
      <template v-if="isOwner">
        <div v-if="canViewFinance || canViewPayments" class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewFinance" class="col-span-12 xl:col-span-8">
            <RevenueChart />
          </div>
          <div class="col-span-12 xl:col-span-4">
            <PaymentBreakdown v-if="canViewPayments" />
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewOrders" class="col-span-12 2xl:col-span-8">
            <RecentOrders />
          </div>
          <div class="col-span-12 space-y-4 md:space-y-6 2xl:col-span-4">
            <DailyActivity :title="activityTitle" :stats="activityStats" :activities="activities" />
            <PickupRequests v-if="canViewDelivery" />
          </div>
        </div>
      </template>

      <!-- Admin: fokus operasional harian, pesanan, pembayaran, antar-jemput. -->
      <template v-else-if="isAdmin">
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewLaundry" class="col-span-12 xl:col-span-8">
            <LaundryQueue />
          </div>
          <div class="col-span-12 xl:col-span-4">
            <DailyActivity
              fill
              :title="activityTitle"
              subtitle="Perubahan tahapan dan pembayaran terbaru."
              :stats="activityStats"
              :activities="activities"
            />
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewOrders" class="col-span-12 2xl:col-span-8">
            <RecentOrders />
          </div>
          <div class="col-span-12 space-y-4 md:space-y-6 2xl:col-span-4">
            <PaymentBreakdown v-if="canViewPayments" />
            <PickupRequests v-if="canViewDelivery" />
          </div>
        </div>
      </template>

      <!-- Karyawan: fokus antrean dan proses laundry. -->
      <template v-else-if="role === 'karyawan'">
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewLaundry" class="col-span-12 xl:col-span-8">
            <LaundryQueue />
          </div>
          <div class="col-span-12 xl:col-span-4">
            <DailyActivity
              fill
              :title="activityTitle"
              subtitle="Perubahan tahapan yang baru terjadi."
              :stats="activityStats"
              :activities="activities"
            />
          </div>
        </div>

        <RecentOrders v-if="canViewOrders" />
      </template>

      <!-- Kurir: fokus tugas antar-jemput. -->
      <template v-else>
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewDelivery" class="col-span-12 xl:col-span-8">
            <CourierTasks />
          </div>
          <div class="col-span-12 xl:col-span-4">
            <DailyActivity
              fill
              :title="activityTitle"
              subtitle="Perjalanan dan penyelesaian tugas terbaru."
              :stats="activityStats"
              :activities="activities"
            />
          </div>
        </div>

        <CourierHistory v-if="canViewDelivery" />
      </template>

      <p
        v-if="isManager && !canViewOrders && !canViewLaundry && !canViewDelivery"
        class="rounded-xl border border-gray-200 bg-white px-5 py-6 text-sm text-gray-500 dark:border-gray-800 dark:bg-white/3 dark:text-gray-400"
      >
        Akun ini belum memiliki akses ke modul operasional. Hubungi owner untuk pengaturan
        permission.
      </p>
    </div>
  </AdminLayout>
</template>
