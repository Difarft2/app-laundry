<script setup lang="ts">
import { computed } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import CourierHistory from '@/components/main/dashboard/laundry/CourierHistory.vue'
import EmployeeActivity from '@/components/main/dashboard/laundry/EmployeeActivity.vue'
import ExpenseBreakdown from '@/components/main/dashboard/laundry/ExpenseBreakdown.vue'
import MetricsGrid from '@/components/main/dashboard/laundry/MetricsGrid.vue'
import PaymentBreakdown from '@/components/main/dashboard/laundry/PaymentBreakdown.vue'
import RecentOrders from '@/components/main/dashboard/laundry/RecentOrders.vue'
import RevenueChart from '@/components/main/dashboard/laundry/RevenueChart.vue'
import { adminMetrics, ownerMetrics } from '@/components/main/dashboard/laundry/data'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const metrics = computed(() => (auth.role === 'owner' ? ownerMetrics : adminMetrics))

const isReadOnly = computed(() => auth.role === 'admin')
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
          <ExpenseBreakdown />
        </div>
        <div class="col-span-12 2xl:col-span-7">
          <EmployeeActivity />
        </div>
      </div>

      <RecentOrders />

      <CourierHistory
        title="Riwayat Kurir"
        subtitle="Seluruh tugas antar-jemput yang telah diselesaikan kurir."
      />
    </div>
  </AdminLayout>
</template>
