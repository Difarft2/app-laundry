<script setup lang="ts">
import { computed, ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ArrowRightIcon, InboxIcon, SearchIcon } from '@/icons/layout'
import { formatCurrency } from '@/utils/format'
import { recentOrders } from './data'
import { laundryStatusMeta, paymentStatusMeta } from './status'
import type { LaundryStatus } from './types'

const search = ref('')
const statusFilter = ref<'all' | LaundryStatus>('all')

const filteredOrders = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  return recentOrders.filter((order) => {
    const matchKeyword =
      !keyword ||
      order.invoice.toLowerCase().includes(keyword) ||
      order.customer.toLowerCase().includes(keyword)
    const matchStatus = statusFilter.value === 'all' || order.laundryStatus === statusFilter.value

    return matchKeyword && matchStatus
  })
})
</script>

<template>
  <div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pt-5 pb-4 sm:px-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pesanan Terbaru</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Status pengerjaan dan pembayaran ditampilkan terpisah.
        </p>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative">
          <SearchIcon
            class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="search"
            type="search"
            placeholder="Cari nota atau pelanggan..."
            aria-label="Cari nomor nota atau nama pelanggan"
            class="h-9 w-full rounded-lg border border-gray-300 bg-transparent py-2 ps-9 pe-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/20 sm:w-56 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-gray-500"
          />
        </div>

        <label class="sr-only" for="order-status-filter">Filter status pengerjaan</label>
        <select
          id="order-status-filter"
          v-model="statusFilter"
          class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
        >
          <option value="all">Semua status</option>
          <option v-for="(meta, status) in laundryStatusMeta" :key="status" :value="status">
            {{ meta.label }}
          </option>
        </select>

        <Button variant="outline" size="sm" disabled title="Halaman pesanan belum tersedia">
          Lihat semua
        </Button>
      </div>
    </div>

    <div class="custom-scrollbar mt-4 max-w-full overflow-x-auto">
      <table class="min-w-[1040px] w-full">
        <thead class="border-y border-gray-100 dark:border-gray-800">
          <tr>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              No. Nota
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Pelanggan
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Layanan
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Masuk
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Estimasi
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Total
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Status Cuci
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Pembayaran
            </th>
            <th class="py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <tr
            v-for="order in filteredOrders"
            :key="order.id"
            class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/3"
          >
            <td class="py-3.5 pe-4">
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                {{ order.invoice }}
              </p>
              <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">1 layanan</p>
            </td>
            <td class="py-3.5 pe-4">
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ order.customer }}</p>
              <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ order.phone }}</p>
            </td>
            <td class="py-3.5 pe-4 text-sm text-gray-500 dark:text-gray-400">
              {{ order.service }}
              <span class="block text-xs text-gray-400 dark:text-gray-500">{{
                order.quantity
              }}</span>
            </td>
            <td class="py-3.5 pe-4 text-sm text-gray-500 dark:text-gray-400">
              {{ order.entryDate }}
            </td>
            <td class="py-3.5 pe-4 text-sm text-gray-500 dark:text-gray-400">
              {{ order.estimateDate }}
            </td>
            <td class="py-3.5 pe-4 text-sm font-medium text-gray-800 dark:text-white/90">
              {{ formatCurrency(order.total) }}
            </td>
            <td class="py-3.5 pe-4">
              <Badge size="sm" :color="laundryStatusMeta[order.laundryStatus].color">
                {{ laundryStatusMeta[order.laundryStatus].label }}
              </Badge>
            </td>
            <td class="py-3.5 pe-4">
              <Badge size="sm" :color="paymentStatusMeta[order.paymentStatus].color">
                {{ paymentStatusMeta[order.paymentStatus].label }}
              </Badge>
            </td>
            <td class="py-3.5 text-end">
              <button
                type="button"
                disabled
                title="Detail pesanan belum tersedia"
                class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-white/5 dark:hover:text-white"
                aria-label="Lihat detail pesanan"
              >
                <ArrowRightIcon class="size-4" />
              </button>
            </td>
          </tr>

          <tr v-if="filteredOrders.length === 0">
            <td colspan="9" class="py-12">
              <div class="flex flex-col items-center justify-center text-center">
                <span
                  class="flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500"
                >
                  <InboxIcon class="size-6" />
                </span>
                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Pesanan tidak ditemukan
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Coba ubah kata kunci pencarian atau filter status.
                </p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-800"
    >
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Menampilkan {{ filteredOrders.length }} dari {{ recentOrders.length }} pesanan terbaru
      </p>
      <p class="text-xs text-gray-400 dark:text-gray-500">Data demo</p>
    </div>
  </div>
</template>
