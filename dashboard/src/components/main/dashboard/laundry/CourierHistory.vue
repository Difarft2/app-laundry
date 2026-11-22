<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { courierHistory } from './data'
import { courierTypeMeta } from './status'

interface CourierHistoryProps {
  title?: string
  subtitle?: string
}

withDefaults(defineProps<CourierHistoryProps>(), {
  title: 'Riwayat Tugas',
  subtitle: 'Tugas antar-jemput yang telah Anda selesaikan.',
})
</script>

<template>
  <div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pt-5 pb-4 sm:px-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ title }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
      </div>
      <Badge color="success" size="sm">{{ courierHistory.length }} selesai</Badge>
    </div>

    <div class="custom-scrollbar mt-4 max-w-full overflow-x-auto">
      <table class="w-full min-w-[720px]">
        <thead class="border-y border-gray-100 dark:border-gray-800">
          <tr>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              No. Tugas
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Jenis
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Pelanggan
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Alamat
            </th>
            <th class="py-3 pe-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Diselesaikan
            </th>
            <th class="py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400">
              Status
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <tr
            v-for="task in courierHistory"
            :key="task.id"
            class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/3"
          >
            <td class="py-3.5 pe-4 text-sm font-medium text-gray-800 dark:text-white/90">
              {{ task.taskNumber }}
            </td>
            <td class="py-3.5 pe-4">
              <Badge size="sm" :color="courierTypeMeta[task.type].color">
                {{ courierTypeMeta[task.type].label }}
              </Badge>
            </td>
            <td class="py-3.5 pe-4 text-sm text-gray-700 dark:text-gray-300">
              {{ task.customer }}
            </td>
            <td class="py-3.5 pe-4 text-sm text-gray-500 dark:text-gray-400">
              {{ task.address }}
            </td>
            <td class="py-3.5 pe-4 text-sm text-gray-500 dark:text-gray-400">
              {{ task.completedAt }}
            </td>
            <td class="py-3.5">
              <Badge size="sm" color="success">Selesai</Badge>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
