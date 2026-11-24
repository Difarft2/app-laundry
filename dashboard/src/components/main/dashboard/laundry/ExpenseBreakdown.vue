<script setup lang="ts">
import { formatCompactCurrency, formatCurrency } from '@/utils/format'
import type { ExpenseItem } from './types'

interface ExpenseBreakdownProps {
  items: ExpenseItem[]
  total: number
  loading?: boolean
  error?: string | null
}

const props = withDefaults(defineProps<ExpenseBreakdownProps>(), {
  loading: false,
  error: null,
})

const averageExpense = () =>
  props.items.length > 0 ? Math.round(props.total / props.items.length) : 0
</script>

<template>
  <div
    class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white px-5 pt-5 pb-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pengeluaran</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Ringkasan biaya operasional bulan ini.
      </p>
    </div>

    <div class="mt-4 rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/3">
      <p class="text-xs text-gray-500 dark:text-gray-400">Total pengeluaran</p>
      <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white/90">
        {{ formatCurrency(props.total) }}
      </p>
      <p v-if="props.items.length > 0" class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
        Rata-rata {{ formatCompactCurrency(averageExpense()) }} per kategori
      </p>
    </div>

    <ul class="mt-4 flex-1 space-y-3.5">
      <li v-for="item in props.items" :key="item.id">
        <div class="flex items-center justify-between gap-3">
          <span class="flex min-w-0 items-center gap-2">
            <span
              class="size-2.5 shrink-0 rounded-full"
              :style="{ backgroundColor: item.color }"
            />
            <span class="truncate text-sm text-gray-700 dark:text-gray-300">
              {{ item.label }}
            </span>
          </span>
          <span class="shrink-0 text-sm font-semibold text-gray-800 dark:text-white/90">
            {{ item.percentage }}%
          </span>
        </div>

        <div class="mt-1.5 flex items-center justify-between gap-3">
          <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
            <div
              class="h-full rounded-full"
              :style="{ width: `${item.percentage}%`, backgroundColor: item.color }"
            />
          </div>
          <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
            {{ formatCurrency(item.amount) }}
          </span>
        </div>
      </li>
      <li
        v-if="props.items.length === 0"
        class="py-8 text-center text-sm text-gray-500 dark:text-gray-400"
      >
        {{ props.error ?? (props.loading ? 'Memuat pengeluaran…' : 'Belum ada pengeluaran tercatat bulan ini.') }}
      </li>
    </ul>
  </div>
</template>
