<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import CourierHistory from '@/components/main/dashboard/laundry/CourierHistory.vue'
import EmployeeActivity from '@/components/main/dashboard/laundry/EmployeeActivity.vue'
import ExpenseBreakdown from '@/components/main/dashboard/laundry/ExpenseBreakdown.vue'
import ExpenseDialog from '@/components/main/dashboard/laundry/ExpenseDialog.vue'
import { Button } from '@/components/ui/button'
import MetricsGrid from '@/components/main/dashboard/laundry/MetricsGrid.vue'
import PaymentBreakdown from '@/components/main/dashboard/laundry/PaymentBreakdown.vue'
import RecentOrders from '@/components/main/dashboard/laundry/RecentOrders.vue'
import RevenueChart from '@/components/main/dashboard/laundry/RevenueChart.vue'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'
import {
  mapActivityStats,
  mapCourierHistory,
  mapEmployeeActivities,
  mapExpenses,
  mapMetrics,
  mapOrders,
} from '@/components/main/dashboard/laundry/mapping'

const auth = useAuthStore()
const dashboard = useDashboardStore()
const expenseDialogOpen = ref(false)
const expenseMessage = ref<string | null>(null)

const canManageExpenses = computed(() => auth.hasPermission('finance.manage'))

const onExpenseCreated = async (message: string) => {
  expenseMessage.value = message
  await dashboard.refresh()
}

onMounted(() => {
  void dashboard.load()
})

const payload = computed(() => dashboard.data?.data ?? null)
const metrics = computed(() => mapMetrics(payload.value?.metrics ?? emptyMetrics(), auth.role ?? 'owner'))
const orders = computed(() => mapOrders(payload.value?.recent.orders ?? []))
const activityStats = computed(() =>
  mapActivityStats(payload.value?.metrics ?? emptyMetrics(), auth.role ?? 'owner'),
)
const employeeActivities = computed(() =>
  mapEmployeeActivities(payload.value?.recent.activities ?? []),
)
const courierHistory = computed(() => mapCourierHistory(payload.value?.delivery.history ?? []))
const expenses = computed(() => mapExpenses(payload.value?.charts.expenses ?? []))
const expenseTotal = computed(() => payload.value?.metrics.expenses ?? 0)

const isReadOnly = computed(() => auth.role === 'admin')

function emptyMetrics(): NonNullable<typeof payload.value>['metrics'] {
  return {
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
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-4 md:space-y-6">
      <PageBreadcrumb page-title="Laporan & Histori" />

      <div
        class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800 dark:bg-white/3"
      >
        <div>
          <h1 class="text-lg font-semibold text-gray-800 dark:text-white/90">
            Laporan pendapatan, pengeluaran, dan histori
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Ringkasan transaksi, aktivitas karyawan, dan riwayat tugas kurir.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <span
            class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-medium"
            :class="
              isReadOnly
                ? 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300'
                : 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'
            "
          >
            {{ isReadOnly ? 'Akses baca' : 'Pemantauan owner' }}
          </span>
          <Button v-if="canManageExpenses" size="sm" @click="expenseDialogOpen = true">
            Catat pengeluaran
          </Button>
        </div>
      </div>

      <MetricsGrid :metrics="metrics" />

      <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 xl:col-span-8">
          <RevenueChart />
        </div>
        <div class="col-span-12 xl:col-span-4">
          <PaymentBreakdown />
        </div>
      </div>

      <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 2xl:col-span-5">
          <ExpenseBreakdown
            :items="expenses"
            :total="expenseTotal"
            :loading="dashboard.loading"
            :error="dashboard.error"
          />
        </div>
        <div class="col-span-12 2xl:col-span-7">
          <EmployeeActivity
            :activities="employeeActivities"
            :stats="activityStats"
          />
        </div>
      </div>

      <RecentOrders
        :orders="orders"
        :loading="dashboard.loading"
        :error="dashboard.error"
      />

      <CourierHistory
        :tasks="courierHistory"
        title="Riwayat Kurir"
        subtitle="Seluruh tugas antar-jemput yang telah diselesaikan kurir."
      />

      <p
        v-if="expenseMessage"
        role="status"
        class="rounded-xl border border-success-200 bg-success-50 px-5 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
      >
        {{ expenseMessage }}
      </p>

      <ExpenseDialog
        v-model:open="expenseDialogOpen"
        @created="onExpenseCreated"
      />
    </div>
  </AdminLayout>
</template>
