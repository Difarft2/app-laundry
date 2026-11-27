<script setup lang="ts">
import { computed, onMounted } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from '@/composables/useTheme'
import { useDashboardStore } from '@/stores/dashboard'
import { cn } from '@/utils/cn'
import type { RevenuePeriod } from '@/services/dashboard'
import { formatCompactCurrency, formatCurrency, formatNumber } from '@/utils/format'
import { revenuePeriods } from './data'
import type { RevenuePeriodId } from './types'

const { isDarkMode } = useTheme()
const dashboard = useDashboardStore()

const loading = computed(() => dashboard.loading)
const loadError = computed(() => dashboard.error)
const selectedPeriod = computed<RevenuePeriodId>({
  get: () => dashboard.period,
  set: (value) => {
    void dashboard.load(value, true)
  },
})

const emptyPeriod: RevenuePeriod = {
  categories: [],
  orders: [],
  revenue: [],
}

const period = computed<RevenuePeriod>(
  () => dashboard.data?.data.charts.revenue ?? emptyPeriod,
)

const loadPeriod = () => dashboard.refresh()

onMounted(() => {
  void dashboard.load()
})

const totalRevenue = computed(() =>
  period.value.revenue.reduce((total, value) => total + value, 0),
)
const totalOrders = computed(() =>
  period.value.orders.reduce((total, value) => total + value, 0),
)
const averagePerOrder = computed(() =>
  totalOrders.value > 0 ? Math.round(totalRevenue.value / totalOrders.value) : 0,
)

const series = computed(() => [
  { name: 'Pendapatan', data: period.value.revenue },
  { name: 'Transaksi', data: period.value.orders },
])

const options = computed<ApexOptions>(() => {
  const labelColor = isDarkMode.value ? '#94A3B8' : '#667085'

  return {
    colors: ['#465FFF', '#12B76A'],
    chart: {
      fontFamily: 'Outfit, sans-serif',
      type: 'area',
      height: 320,
      toolbar: { show: false },
      zoom: { enabled: false },
    },
    stroke: {
      curve: 'smooth',
      width: [2.5, 2],
      dashArray: [0, 4],
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 0.6,
        opacityFrom: isDarkMode.value ? 0.45 : 0.35,
        opacityTo: 0,
        stops: [0, 90, 100],
      },
    },
    dataLabels: { enabled: false },
    markers: { size: 0, hover: { size: 5 } },
    legend: { show: false },
    grid: {
      borderColor: isDarkMode.value ? '#1E2640' : '#F2F4F7',
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } },
      padding: { top: 0, right: 0, bottom: 0, left: 0 },
    },
    xaxis: {
      categories: period.value.categories,
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: labelColor,
          fontSize: '12px',
          fontFamily: 'Outfit, sans-serif',
        },
      },
    },
    yaxis: [
      {
        labels: {
          formatter: (value: number) => formatCompactCurrency(value),
          style: {
            colors: labelColor,
            fontSize: '12px',
            fontFamily: 'Outfit, sans-serif',
          },
        },
      },
      {
        opposite: true,
        labels: {
          formatter: (value: number) => formatNumber(Math.round(value)),
          style: {
            colors: labelColor,
            fontSize: '12px',
            fontFamily: 'Outfit, sans-serif',
          },
        },
      },
    ],
    tooltip: {
      shared: true,
      intersect: false,
      custom: ({
        series: chartSeries,
        dataPointIndex,
        w,
      }: {
        series: number[][]
        dataPointIndex: number
        w: { globals?: { categoryLabels?: string[] } }
      }) => {
        const category = w.globals?.categoryLabels?.[dataPointIndex] ?? ''
        const revenue = chartSeries[0]?.[dataPointIndex] ?? 0
        const orders = chartSeries[1]?.[dataPointIndex] ?? 0
        const valueColor = isDarkMode.value ? '#FFFFFF' : '#1D2939'

        return `
          <div style="min-width: 180px; padding: 4px 2px;">
            <p style="margin-bottom: 8px; font-size: 11px; color: #667085;">${category}</p>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 4px;">
              <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 6px; height: 6px; border-radius: 9999px; background: #465FFF;"></span>
                <span style="font-size: 12px; color: #667085;">Pendapatan</span>
              </span>
              <span style="font-size: 12px; font-weight: 600; color: ${valueColor};">${formatCurrency(revenue)}</span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
              <span style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 6px; height: 6px; border-radius: 9999px; background: #12B76A;"></span>
                <span style="font-size: 12px; color: #667085;">Transaksi</span>
              </span>
              <span style="font-size: 12px; font-weight: 600; color: ${valueColor};">${formatNumber(orders)}</span>
            </div>
          </div>
        `
      },
    },
  }
})
</script>

<template>
  <div
    class="h-full rounded-2xl border border-gray-200 bg-white px-5 pt-5 pb-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Pendapatan &amp; Transaksi
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Ringkasan pemasukan dari pesanan yang tercatat.
        </p>
      </div>

      <div
        class="inline-flex w-fit flex-wrap gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-800 dark:bg-white/3"
        role="group"
        aria-label="Pilih rentang waktu pendapatan"
      >
        <button
          v-for="item in revenuePeriods"
          :key="item.id"
          type="button"
          :aria-pressed="selectedPeriod === item.id"
          :disabled="loading"
          :class="
            cn(
              'rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors disabled:opacity-60',
              selectedPeriod === item.id
                ? 'bg-white text-gray-800 shadow-theme-xs dark:bg-gray-800 dark:text-white'
                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white',
            )
          "
          @click="selectedPeriod = item.id"
        >
          {{ item.label }}
        </button>
      </div>
    </div>

    <p v-if="loadError" class="mt-3 text-xs text-error-500">
      {{ loadError }}
      <button type="button" class="font-medium underline" @click="loadPeriod()">
        Muat ulang
      </button>
    </p>

    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/3">
        <p class="text-xs text-gray-500 dark:text-gray-400">Total pendapatan</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
          {{ formatCurrency(totalRevenue) }}
        </p>
      </div>
      <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/3">
        <p class="text-xs text-gray-500 dark:text-gray-400">Jumlah transaksi</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
          {{ formatNumber(totalOrders) }}
          <span class="text-xs font-medium text-gray-400">nota</span>
        </p>
      </div>
      <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/3">
        <p class="text-xs text-gray-500 dark:text-gray-400">Rata-rata per nota</p>
        <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
          {{ formatCurrency(averagePerOrder) }}
        </p>
      </div>
    </div>

    <div class="custom-scrollbar mt-4 max-w-full overflow-x-auto">
      <div class="min-w-640">
        <VueApexCharts type="area" :height="320" :options="options" :series="series" />
      </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
      <span class="flex items-center gap-1.5">
        <span class="size-2 rounded-full bg-brand-500" />
        Pendapatan
      </span>
      <span class="flex items-center gap-1.5">
        <span class="size-2 rounded-full bg-green-500" />
        Jumlah transaksi
      </span>
    </div>
  </div>
</template>
