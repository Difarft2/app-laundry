<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'

const dashboard = useDashboardStore()
const auth = useAuthStore()
const isCourier = computed(() => auth.role === 'kurir')

onMounted(() => {
  void dashboard.load()
})

const queue = computed(() => dashboard.data?.data.metrics.queueWashing ?? 0)
const couriers = computed(() =>
  isCourier.value
    ? (dashboard.data?.data.metrics.waitingCourier ?? 0)
    : (dashboard.data?.data.metrics.activeCouriers ?? 0),
)
</script>

<template>
  <div class="mb-4">
    <div
      class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 dark:border-gray-800 dark:bg-white/3"
    >
      <div class="flex items-center justify-between gap-2">
        <h3 class="text-xs font-semibold text-gray-800 dark:text-white/90">Status Outlet</h3>
        <span
          class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-600 dark:bg-green-500/15 dark:text-green-500"
        >
          <span class="size-1.5 rounded-full bg-green-500" />
          Buka
        </span>
      </div>

      <p class="mt-2 text-xs font-medium text-gray-700 dark:text-gray-300">
        Pemuda Laundry · Outlet Pusat
      </p>
      <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">Senin–Sabtu, 08.00–21.00</p>

      <div class="mt-3 grid grid-cols-2 gap-2 border-t border-gray-200 pt-3 dark:border-gray-800">
        <div>
          <p class="text-[10px] text-gray-400 dark:text-gray-500">Antrean cuci</p>
          <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-white/90">{{ queue }}</p>
        </div>
        <div>
          <p class="text-[10px] text-gray-400 dark:text-gray-500">
            {{ isCourier ? 'Tugas menunggu' : 'Kurir aktif' }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-white/90">{{ couriers }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
