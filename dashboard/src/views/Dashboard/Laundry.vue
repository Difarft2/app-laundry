<script setup lang="ts">
import { computed, ref } from 'vue'
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

const role = ref<DashboardRole>(auth.role ?? 'karyawan')

const isManager = computed(() => role.value === 'owner' || role.value === 'admin')

const metrics = computed(() => {
  if (role.value === 'karyawan') return karyawanMetrics
  if (role.value === 'kurir') return kurirMetrics
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
  return 'Ringkasan Aktivitas Hari Ini'
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-4 md:space-y-6">
      <DashboardHeader
        v-model:role="role"
        :name="auth.user?.name ?? 'Pengguna'"
        :can-preview-roles="auth.canPreviewRoles"
      />

      <MetricsGrid :metrics="metrics" />

      <template v-if="isManager">
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div class="col-span-12 xl:col-span-8">
            <RevenueChart />
          </div>
          <div class="col-span-12 xl:col-span-4">
            <PaymentBreakdown />
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div class="col-span-12 2xl:col-span-8">
            <RecentOrders />
          </div>
          <div class="col-span-12 space-y-4 md:space-y-6 2xl:col-span-4">
            <DailyActivity :title="activityTitle" :stats="activityStats" :activities="activities" />
            <PickupRequests />
          </div>
        </div>
      </template>

      <template v-else-if="role === 'karyawan'">
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div class="col-span-12 xl:col-span-8">
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

        <RecentOrders />
      </template>

      <template v-else>
        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <div class="col-span-12 xl:col-span-8">
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

        <CourierHistory />
      </template>
    </div>
  </AdminLayout>
</template>
