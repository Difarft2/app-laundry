<script setup lang="ts">
import { computed } from 'vue'
import type { DashboardOwnerApi } from '@/services/dashboard'
import { formatCurrency, formatNumber } from '@/utils/format'

interface OwnerInsightsProps {
  owner: DashboardOwnerApi | null
  loading?: boolean
  error?: string | null
}

const props = withDefaults(defineProps<OwnerInsightsProps>(), {
  loading: false,
  error: null,
})

const netIncome = computed(() => props.owner?.net_income ?? null)
const retention = computed(() => props.owner?.retention ?? null)
const unpaid = computed(() => props.owner?.unpaid ?? null)

const topServices = computed(() => props.owner?.top_services ?? [])
const topServiceMax = computed(() =>
  topServices.value.reduce((max, item) => Math.max(max, Number(item.revenue)), 0),
)

const growthPositive = computed(() => (netIncome.value?.growth ?? 0) >= 0)

const card = 'rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/3'
const label = 'text-xs font-medium text-gray-500 dark:text-gray-400'
</script>

<template>
  <div class="grid grid-cols-12 gap-4 md:gap-6">
    <!-- Laba bersih bulan ini + pertumbuhan MoM (owner only). -->
    <div v-if="netIncome" class="col-span-12 sm:col-span-6 xl:col-span-3">
      <div class="h-full" :class="card">
        <p :class="label">Laba Bersih Bulan Ini</p>
        <p
          class="mt-2 text-2xl font-bold"
          :class="Number(netIncome.net_income) >= 0
            ? 'text-success-600 dark:text-success-400'
            : 'text-error-600 dark:text-error-400'"
        >
          {{ formatCurrency(netIncome.net_income) }}
        </p>
        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
          <span
            class="inline-flex items-center rounded-full px-2 py-0.5 font-medium"
            :class="growthPositive
              ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400'
              : 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400'"
          >
            {{ growthPositive ? '▲' : '▼' }}
            {{ Math.abs(netIncome.growth) }}%
          </span>
          <span class="text-gray-500 dark:text-gray-400">vs bulan lalu</span>
        </div>
        <dl class="mt-4 space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
          <div class="flex items-center justify-between">
            <dt>Pendapatan</dt>
            <dd class="font-medium text-gray-700 dark:text-gray-300">
              {{ formatCurrency(netIncome.revenue) }}
            </dd>
          </div>
          <div class="flex items-center justify-between">
            <dt>Pengeluaran</dt>
            <dd class="font-medium text-gray-700 dark:text-gray-300">
              {{ formatCurrency(netIncome.expenses) }}
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Layanan terlaris berdasarkan pendapatan (owner & admin). -->
    <div v-if="topServices.length > 0" class="col-span-12 lg:col-span-6 xl:col-span-4">
      <div class="h-full" :class="card">
        <p :class="label">Layanan Terlaris</p>
        <ul class="mt-4 space-y-3.5">
          <li v-for="service in topServices" :key="service.label">
            <div class="flex items-center justify-between gap-3">
              <span class="truncate text-sm text-gray-700 dark:text-gray-300">
                {{ service.label }}
              </span>
              <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                {{ formatNumber(service.total) }} order
              </span>
            </div>
            <div class="mt-1.5 flex items-center justify-between gap-3">
              <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div
                  class="h-full rounded-full bg-brand-500"
                  :style="{ width: `${topServiceMax > 0 ? (Number(service.revenue) / topServiceMax) * 100 : 0}%` }"
                />
              </div>
              <span class="shrink-0 text-xs font-medium text-gray-700 dark:text-gray-300">
                {{ formatCurrency(service.revenue) }}
              </span>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Retensi pelanggan baru vs lama (owner only). -->
    <div v-if="retention" class="col-span-12 sm:col-span-6 xl:col-span-2">
      <div class="h-full" :class="card">
        <p :class="label">Retensi Pelanggan</p>
        <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">
          {{ retention.retention_rate }}%
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ formatNumber(retention.returning_customers) }} dari
          {{ formatNumber(retention.total_customers) }} pelanggan kembali
        </p>
        <dl class="mt-4 space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
          <div class="flex items-center justify-between">
            <dt>Pelanggan baru</dt>
            <dd class="font-medium text-gray-700 dark:text-gray-300">
              {{ formatNumber(retention.new_customers) }}
            </dd>
          </div>
          <div class="flex items-center justify-between">
            <dt>Pelanggan setia</dt>
            <dd class="font-medium text-gray-700 dark:text-gray-300">
              {{ formatNumber(retention.returning_customers) }}
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Monitoring piutang (owner & admin). -->
    <div v-if="unpaid" class="col-span-12 lg:col-span-6 xl:col-span-3">
      <div class="h-full" :class="card">
        <p :class="label">Piutang Belum Lunas</p>
        <p
          class="mt-2 text-2xl font-bold"
          :class="unpaid.count > 0
            ? 'text-warning-600 dark:text-warning-400'
            : 'text-gray-800 dark:text-white/90'"
        >
          {{ formatCurrency(unpaid.total_unpaid) }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ formatNumber(unpaid.count) }} pesanan belum lunas
        </p>

        <ul v-if="unpaid.list.length > 0" class="mt-4 space-y-3">
          <li
            v-for="item in unpaid.list"
            :key="item.kode_pesanan"
            class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3 first:border-t-0 first:pt-0 dark:border-gray-800"
          >
            <span class="min-w-0">
              <span class="block truncate text-sm text-gray-700 dark:text-gray-300">
                {{ item.nama_pelanggan || '-' }}
              </span>
              <span class="block truncate text-xs text-gray-400 dark:text-gray-500">
                {{ item.kode_pesanan }}
              </span>
            </span>
            <span class="shrink-0 text-xs font-semibold text-warning-600 dark:text-warning-400">
              {{ formatCurrency(item.sisa_tagihan) }}
            </span>
          </li>
        </ul>
        <p
          v-else
          class="mt-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400"
        >
          {{ props.error ?? (props.loading ? 'Memuat data piutang...' : 'Semua pesanan sudah lunas.') }}
        </p>
      </div>
    </div>
  </div>
</template>
