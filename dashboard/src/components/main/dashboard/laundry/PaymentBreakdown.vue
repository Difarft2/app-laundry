<script setup lang="ts">
import { computed, onMounted } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from '@/composables/useTheme'
import { useDashboardStore } from '@/stores/dashboard'
import { formatCompactCurrency, formatCurrency, formatNumber } from '@/utils/format'
import { mapPaymentMethods } from './mapping'

const { isDarkMode } = useTheme()
const dashboard = useDashboardStore()

const methods = computed(() =>
  mapPaymentMethods(dashboard.data?.data.charts.payment_methods ?? []),
)
const loading = computed(() => dashboard.loading)
const loadError = computed(() => dashboard.error)

const series = computed(() => methods.value.map((method) => method.amount))
const totalTransactions = computed(() =>
  methods.value.reduce((total, method) => total + method.transactions, 0),
)
const paymentTotal = computed(() =>
  methods.value.reduce((total, method) => total + method.amount, 0),
)

const loadMethods = () => dashboard.refresh()

onMounted(() => {
  void dashboard.load()
})

const options = computed<ApexOptions>(() => ({
  colors: methods.value.map((method) => method.color),
  chart: {
    fontFamily: 'Outfit, sans-serif',
    type: 'donut',
    height: 250,
  },
  labels: methods.value.map((method) => method.label),
  stroke: {
    width: 3,
    colors: [isDarkMode.value ? '#101828' : '#FFFFFF'],
  },
  dataLabels: { enabled: false },
  legend: { show: false },
  plotOptions: {
    pie: {
      expandOnClick: false,
      donut: {
        size: '72%',
      },
    },
  },
  tooltip: {
    y: {
      formatter: (value: number) => formatCurrency(value),
    },
  },
}))
</script>

<template>
  <div
    class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white px-5 pt-5 pb-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Metode Pembayaran</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Komposisi seluruh pembayaran yang berhasil.
      </p>
    </div>

    <div class="relative mx-auto mt-2 w-full max-w-65">
      <VueApexCharts type="donut" :height="250" :options="options" :series="series" />
      <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-[11px] text-gray-500 dark:text-gray-400">Total diterima</span>
        <span class="mt-0.5 text-lg font-bold text-gray-800 dark:text-white/90">
          {{ formatCompactCurrency(paymentTotal) }}
        </span>
        <span class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">
          {{ formatNumber(totalTransactions) }} transaksi
        </span>
      </div>
    </div>

    <ul class="mt-4 space-y-3.5">
      <li v-for="method in methods" :key="method.id">
        <div class="flex items-center justify-between gap-3">
          <span class="flex min-w-0 items-center gap-2">
            <span
              class="size-2.5 shrink-0 rounded-full"
              :style="{ backgroundColor: method.color }"
            />
            <span class="truncate text-sm text-gray-700 dark:text-gray-300">
              {{ method.label }}
            </span>
          </span>
          <span class="shrink-0 text-sm font-semibold text-gray-800 dark:text-white/90">
            {{ formatCompactCurrency(method.amount) }}
          </span>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ method.transactions }} transaksi
        </p>
      </li>
    </ul>

    <p v-if="loadError" class="mt-3 text-xs text-error-500">
      {{ loadError }}
      <button type="button" class="font-medium underline" @click="loadMethods">Muat ulang</button>
    </p>
    <p
      v-else-if="!loading && methods.length === 0"
      class="mt-3 text-xs text-gray-400 dark:text-gray-500"
    >
      Belum ada pembayaran yang berhasil tercatat.
    </p>
  </div>
</template>
