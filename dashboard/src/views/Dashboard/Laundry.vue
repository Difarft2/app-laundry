<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { Button } from '@/components/ui/button'
import ScanOrderDialog from '@/components/main/orders/ScanOrderDialog.vue'
import CourierHistory from '@/components/main/dashboard/laundry/CourierHistory.vue'
import CourierTasks from '@/components/main/dashboard/laundry/CourierTasks.vue'
import DailyActivity from '@/components/main/dashboard/laundry/DailyActivity.vue'
import DashboardHeader from '@/components/main/dashboard/laundry/DashboardHeader.vue'
import EmployeeActivity from '@/components/main/dashboard/laundry/EmployeeActivity.vue'
import ExpenseBreakdown from '@/components/main/dashboard/laundry/ExpenseBreakdown.vue'
import LandingDeliveryRequests from '@/components/main/dashboard/laundry/LandingDeliveryRequests.vue'
import LaundryQueue from '@/components/main/dashboard/laundry/LaundryQueue.vue'
import MetricsGrid from '@/components/main/dashboard/laundry/MetricsGrid.vue'
import OwnerInsights from '@/components/main/dashboard/laundry/OwnerInsights.vue'
import PaymentBreakdown from '@/components/main/dashboard/laundry/PaymentBreakdown.vue'
import PickupRequests from '@/components/main/dashboard/laundry/PickupRequests.vue'
import RecentOrders from '@/components/main/dashboard/laundry/RecentOrders.vue'
import RevenueChart from '@/components/main/dashboard/laundry/RevenueChart.vue'
import type { DashboardMetricsApi } from '@/services/dashboard'
import type { DashboardRole } from '@/components/main/dashboard/laundry/types'
import {
  mapActivities,
  mapActivityStats,
  mapCourierHistory,
  mapEmployeeActivities,
  mapExpenses,
  mapLandingRequests,
  mapMetrics,
  mapOrders,
  mapPickupRequests,
  mapQueue,
  mapTasks,
} from '@/components/main/dashboard/laundry/mapping'
import { deliveryApi } from '@/services/delivery'
import { normalizeApiError } from '@/services/http'
import { CameraIcon } from '@/icons/layout'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'

const auth = useAuthStore()
const dashboard = useDashboardStore()
const scanOpen = ref(false)
const scanMessage = ref<string | null>(null)
const reviewMessage = ref<string | null>(null)
const reviewBusy = ref<number | null>(null)

onMounted(() => {
  void dashboard.load()
})

/**
 * Dashboard selalu mengikuti role akun yang sedang login. Backend mengirim role
 * yang sama agar scope datanya konsisten dengan tampilan.
 */
const role = computed<DashboardRole>(() => {
  const apiRole = dashboard.data?.data.role
  if (apiRole === 'owner' || apiRole === 'admin' || apiRole === 'karyawan' || apiRole === 'kurir') {
    return apiRole
  }
  return auth.role ?? 'karyawan'
})

const isOwner = computed(() => role.value === 'owner')
const isAdmin = computed(() => role.value === 'admin')
const isKaryawan = computed(() => role.value === 'karyawan')

const canViewFinance = computed(() => auth.hasPermission('reports.view'))
const canViewPayments = computed(() => auth.hasPermission('payments.view'))
const canViewOrders = computed(() => auth.hasPermission('orders.view'))
const canViewLaundry = computed(() => auth.hasPermission('laundry.view'))
const canViewDelivery = computed(() => auth.hasPermission('delivery.view'))
const canViewUsers = computed(() => auth.hasPermission('users.view'))
const canCreateOrder = computed(() => auth.hasPermission('orders.create'))
const canUpdateStatus = computed(() => auth.hasPermission('orders.update-status'))
const canVerifyDelivery = computed(() => auth.hasPermission('delivery.verify'))

const emptyMetrics: DashboardMetricsApi = {
  totalOrders: 0,
  completedOrders: 0,
  revenue: 0,
  expenses: 0,
  activeEmployees: 0,
  activeCouriers: 0,
  pendingPickups: 0,
  newOrdersToday: 0,
  queueWashing: 0,
  washingInProgress: 0,
  finishing: 0,
  doneToday: 0,
  unpaid: 0,
  waitingCourier: 0,
  onDelivery: 0,
  completedDeliveriesToday: 0,
}

const payload = computed(() => dashboard.data?.data ?? null)
const metricsApi = computed(() => payload.value?.metrics ?? emptyMetrics)

const metrics = computed(() => mapMetrics(metricsApi.value, role.value))
const activityStats = computed(() => mapActivityStats(metricsApi.value, role.value))
const activities = computed(() => mapActivities(payload.value?.recent.activities ?? []))
const employeeActivities = computed(() =>
  mapEmployeeActivities(payload.value?.recent.activities ?? []),
)
const orders = computed(() => mapOrders(payload.value?.recent.orders ?? []))
const queue = computed(() =>
  mapQueue(payload.value?.laundry.queue ?? [], metricsApi.value),
)
const activeTasks = computed(() => mapTasks(payload.value?.delivery.active_tasks ?? []))
const courierHistory = computed(() => mapCourierHistory(payload.value?.delivery.history ?? []))
const pickupRequests = computed(() =>
  mapPickupRequests(payload.value?.delivery.pickup_requests ?? []),
)
const landingRequests = computed(() =>
  mapLandingRequests(payload.value?.delivery.landing_requests ?? []),
)
const expenses = computed(() => mapExpenses(payload.value?.charts.expenses ?? []))
const expenseTotal = computed(() => metricsApi.value.expenses)
const ownerInsights = computed(() => payload.value?.owner ?? null)

const activityTitle = computed(() => {
  if (isKaryawan.value) return 'Aktivitas Proses Laundry'
  if (role.value === 'kurir') return 'Aktivitas Tugas Hari Ini'
  if (isAdmin.value) return 'Riwayat Aktivitas Operasional'
  return 'Ringkasan Aktivitas Hari Ini'
})

const activitySubtitle = computed(() => {
  if (isKaryawan.value) return 'Perubahan tahapan yang baru terjadi.'
  if (role.value === 'kurir') return 'Perjalanan dan penyelesaian tugas terbaru.'
  if (isAdmin.value) return 'Riwayat perubahan data dan transaksi terbaru.'
  return 'Perubahan terbaru pada operasional laundry.'
})

const reviewLandingRequest = async (id: number, action: 'verify' | 'reject'): Promise<void> => {
  reviewBusy.value = id
  reviewMessage.value = null

  try {
    const task = action === 'verify'
      ? await deliveryApi.verify(id)
      : await deliveryApi.reject(id)

    reviewMessage.value = action === 'verify'
      ? `Permintaan ${task.id_antar_jemput} diverifikasi. Silakan tugaskan kurir.`
      : `Permintaan ${task.id_antar_jemput} ditolak.`

    await dashboard.refresh()
  } catch (error) {
    reviewMessage.value = normalizeApiError(error).message
  } finally {
    reviewBusy.value = null
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-4 md:space-y-6">
      <DashboardHeader :role="role" :name="auth.user?.name ?? 'Pengguna'" />

      <p
        v-if="dashboard.error"
        role="alert"
        class="rounded-xl border border-error-200 bg-error-50 px-5 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
      >
        {{ dashboard.error }}
        <button type="button" class="ms-2 font-medium underline" @click="dashboard.refresh()">
          Muat ulang
        </button>
      </p>

      <!-- Hanya karyawan yang dapat membuat pesanan. -->
      <div
        v-if="isKaryawan && canCreateOrder"
        class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-brand-500/20 dark:bg-brand-500/10"
      >
        <div>
          <p class="text-sm font-semibold text-brand-700 dark:text-brand-300">
            Buat pesanan laundry baru
          </p>
          <p class="mt-0.5 text-xs text-brand-600/80 dark:text-brand-300/70">
            Pencatatan pesanan pelanggan adalah tugas operasional karyawan.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button v-if="canUpdateStatus" variant="outline" class="shrink-0" @click="scanOpen = true">
            <CameraIcon class="size-4" />
            Scan QR pesanan
          </Button>
          <Button as-child class="shrink-0">
            <RouterLink to="/orders?create=1">Buat pesanan</RouterLink>
          </Button>
        </div>
      </div>

      <p
        v-if="scanMessage"
        role="status"
        class="rounded-xl border border-success-200 bg-success-50 px-5 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
      >
        {{ scanMessage }}
      </p>

      <p
        v-if="reviewMessage"
        role="status"
        class="rounded-xl border border-brand-200 bg-brand-50 px-5 py-3 text-sm text-brand-700 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300"
      >
        {{ reviewMessage }}
      </p>

      <!-- Admin hanya memantau, tidak memiliki aksi operasional. -->
      <div
        v-else-if="isAdmin"
        class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-white/3 dark:text-gray-300"
      >
        <span class="font-semibold text-gray-800 dark:text-white/90">Mode pemantauan.</span>
        Admin hanya dapat melihat riwayat pesanan dan data historis tanpa membuat atau mengubah
        transaksi.
      </div>

      <MetricsGrid :metrics="metrics" />

      <!-- Analitik eksekutif: owner penuh, admin mendapat layanan terlaris & piutang. -->
      <OwnerInsights
        v-if="(isOwner || isAdmin) && ownerInsights"
        :owner="ownerInsights"
        :loading="dashboard.loading"
        :error="dashboard.error"
      />

      <!-- Owner: fokus pendapatan, pengeluaran, dan histori seluruh aktivitas. -->
      <template v-if="isOwner">
        <div v-if="canViewFinance || canViewPayments" class="grid grid-cols-12 gap-4 md:gap-6">
          <div
            v-if="canViewFinance"
            :class="canViewPayments ? 'col-span-12 xl:col-span-8' : 'col-span-12'"
          >
            <RevenueChart />
          </div>
          <div v-if="canViewPayments" class="col-span-12 xl:col-span-4">
            <PaymentBreakdown />
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewOrders" class="col-span-12 2xl:col-span-8">
            <RecentOrders
              :orders="orders"
              :loading="dashboard.loading"
              :error="dashboard.error"
            />
          </div>
          <div class="col-span-12 space-y-4 md:space-y-6 2xl:col-span-4">
            <DailyActivity
              :title="activityTitle"
              :subtitle="activitySubtitle"
              :stats="activityStats"
              :activities="activities"
            />
            <PickupRequests v-if="canViewDelivery" :requests="pickupRequests" />
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewUsers" class="col-span-12 xl:col-span-7">
            <EmployeeActivity
              :activities="employeeActivities"
              :stats="activityStats"
            />
          </div>
          <div v-if="canViewFinance" class="col-span-12 xl:col-span-5">
            <ExpenseBreakdown
              :items="expenses"
              :total="expenseTotal"
              :loading="dashboard.loading"
              :error="dashboard.error"
            />
          </div>
        </div>

        <CourierHistory
          v-if="canViewDelivery"
          :tasks="courierHistory"
          title="Riwayat Kurir"
          subtitle="Seluruh tugas antar-jemput yang telah diselesaikan kurir."
        />
      </template>

      <!-- Admin: read-only, fokus riwayat dan data historis. -->
      <template v-else-if="isAdmin">
        <div v-if="canViewFinance || canViewPayments" class="grid grid-cols-12 gap-4 md:gap-6">
          <div
            v-if="canViewFinance"
            :class="canViewPayments ? 'col-span-12 xl:col-span-8' : 'col-span-12'"
          >
            <RevenueChart />
          </div>
          <div v-if="canViewPayments" class="col-span-12 xl:col-span-4">
            <PaymentBreakdown />
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewOrders" class="col-span-12 2xl:col-span-8">
            <RecentOrders
              :orders="orders"
              :loading="dashboard.loading"
              :error="dashboard.error"
            />
          </div>
          <div class="col-span-12 space-y-4 md:space-y-6 2xl:col-span-4">
            <DailyActivity
              :title="activityTitle"
              :subtitle="activitySubtitle"
              :stats="activityStats"
              :activities="activities"
            />
            <PickupRequests v-if="canViewDelivery" :requests="pickupRequests" />
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewUsers" class="col-span-12 xl:col-span-7">
            <EmployeeActivity
              :activities="employeeActivities"
              :stats="activityStats"
            />
          </div>
          <div v-if="canViewFinance" class="col-span-12 xl:col-span-5">
            <ExpenseBreakdown
              :items="expenses"
              :total="expenseTotal"
              :loading="dashboard.loading"
              :error="dashboard.error"
            />
          </div>
        </div>

        <CourierHistory
          v-if="canViewDelivery"
          :tasks="courierHistory"
          title="Riwayat Kurir"
          subtitle="Seluruh tugas antar-jemput yang telah diselesaikan kurir."
        />
      </template>

      <!-- Karyawan: fokus operasional pesanan dan proses laundry. -->
      <template v-else-if="isKaryawan">
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewLaundry" class="col-span-12 xl:col-span-8">
            <LaundryQueue
              :summary="queue.summary"
              :items="queue.items"
              :notes="queue.notes"
              :loading="dashboard.loading"
              :error="dashboard.error"
            />
          </div>
          <div class="col-span-12 xl:col-span-4">
            <DailyActivity
              fill
              :title="activityTitle"
              :subtitle="activitySubtitle"
              :stats="activityStats"
              :activities="activities"
            />
          </div>
        </div>

        <LandingDeliveryRequests
          v-if="canVerifyDelivery"
          :requests="landingRequests"
          :loading="dashboard.loading"
          :error="dashboard.error"
          :busy-id="reviewBusy"
          @verify="reviewLandingRequest($event, 'verify')"
          @reject="reviewLandingRequest($event, 'reject')"
        />

        <RecentOrders
          v-if="canViewOrders"
          :orders="orders"
          :loading="dashboard.loading"
          :error="dashboard.error"
        />

        <div
          v-if="canViewPayments || canViewDelivery"
          class="grid grid-cols-12 gap-4 md:gap-6"
        >
          <div v-if="canViewPayments" class="col-span-12 xl:col-span-6">
            <PaymentBreakdown />
          </div>
          <div v-if="canViewDelivery" class="col-span-12 xl:col-span-6">
            <PickupRequests :requests="pickupRequests" />
          </div>
        </div>
      </template>

      <!-- Kurir: hanya tugas antar-jemput, tanpa modul pesanan laundry. -->
      <template v-else>
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div v-if="canViewDelivery" class="col-span-12 xl:col-span-8">
            <CourierTasks
              :tasks="activeTasks"
              :loading="dashboard.loading"
              :error="dashboard.error"
            />
          </div>
          <div class="col-span-12 xl:col-span-4">
            <DailyActivity
              fill
              :title="activityTitle"
              :subtitle="activitySubtitle"
              :stats="activityStats"
              :activities="activities"
            />
          </div>
        </div>

        <CourierHistory v-if="canViewDelivery" :tasks="courierHistory" />
      </template>

      <p
        v-if="isAdmin && !canViewOrders && !canViewLaundry && !canViewDelivery"
        class="rounded-xl border border-gray-200 bg-white px-5 py-6 text-sm text-gray-500 dark:border-gray-800 dark:bg-white/3 dark:text-gray-400"
      >
        Akun ini belum memiliki akses ke modul operasional. Hubungi owner untuk pengaturan
        permission.
      </p>

      <ScanOrderDialog
        v-model:open="scanOpen"
        @status-updated="scanMessage = $event"
      />
    </div>
  </AdminLayout>
</template>
