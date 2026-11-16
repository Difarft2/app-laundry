<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { AlertIcon, ClockIcon } from '@/icons/layout'
import { cn } from '@/utils/cn'
import { queueItems, queueNotes, queueSummary } from './data'
import { activityDotClasses, laundryStatusMeta } from './status'
import type { LaundryStatus } from './types'

const steps = ['Dicuci', 'Dikeringkan', 'Finishing', 'Selesai'] as const

const stepIndex = (status: LaundryStatus): number => {
  const map: Record<LaundryStatus, number> = {
    menunggu: -1,
    dicuci: 0,
    dikeringkan: 1,
    finishing: 2,
    selesai: 3,
  }
  return map[status]
}
</script>

<template>
  <div
    class="h-full rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Antrean Proses Laundry
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Pekerjaan yang sedang berjalan dan perlu diselesaikan.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <span class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
          <ClockIcon class="size-4" />
          Diperbarui 09.45
        </span>
        <Button variant="outline" size="sm" disabled title="Halaman proses laundry belum tersedia">
          Lihat antrean
        </Button>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div
        v-for="item in queueSummary"
        :key="item.label"
        class="rounded-xl border border-gray-100 px-3 py-2.5 dark:border-gray-800"
      >
        <div class="flex items-center gap-2">
          <span :class="cn('size-2 rounded-full', activityDotClasses[item.tone])" />
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.label }}</p>
        </div>
        <p class="mt-1.5 text-xl font-bold text-gray-800 dark:text-white/90">{{ item.count }}</p>
      </div>
    </div>

    <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
      <div v-for="item in queueItems" :key="item.id" class="py-4 first:pt-1">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                {{ item.invoice }}
              </p>
              <Badge size="sm" :color="laundryStatusMeta[item.status].color">
                {{ laundryStatusMeta[item.status].label }}
              </Badge>
              <Badge v-if="item.isExpress" size="sm" color="warning">Express 4 Jam</Badge>
            </div>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
              {{ item.customer }}
              <span class="text-gray-400 dark:text-gray-500">
                · {{ item.service }} · {{ item.quantity }}
              </span>
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
              Masuk {{ item.entryTime }} · Estimasi {{ item.estimate }}
            </p>
          </div>

          <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">
            {{ stepIndex(item.status) + 1 }}/4 tahap
          </span>
        </div>

        <div class="mt-3 grid grid-cols-4 gap-1.5">
          <div v-for="(step, index) in steps" :key="step">
            <div
              :class="
                cn(
                  'h-1.5 rounded-full',
                  index <= stepIndex(item.status) ? 'bg-brand-500' : 'bg-gray-100 dark:bg-gray-800',
                )
              "
            />
            <p
              :class="
                cn(
                  'mt-1 text-[10px]',
                  index <= stepIndex(item.status)
                    ? 'text-gray-600 dark:text-gray-300'
                    : 'text-gray-400 dark:text-gray-500',
                )
              "
            >
              {{ step }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div
      class="mt-3 rounded-xl border border-warning-200 bg-warning-25 p-4 dark:border-warning-500/20 dark:bg-warning-500/10"
    >
      <p
        class="flex items-center gap-2 text-xs font-semibold text-warning-700 dark:text-warning-400"
      >
        <AlertIcon class="size-4" />
        Perlu ditindaklanjuti
      </p>
      <ul class="mt-2 space-y-1.5">
        <li
          v-for="note in queueNotes"
          :key="note"
          class="flex gap-2 text-xs leading-5 text-warning-700/90 dark:text-warning-200/80"
        >
          <span class="mt-2 size-1 shrink-0 rounded-full bg-warning-500" />
          {{ note }}
        </li>
      </ul>
    </div>
  </div>
</template>
