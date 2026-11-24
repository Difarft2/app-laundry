<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CallIcon, MapIcon, TimeIcon } from '@/icons/layout'
import { courierStatusMeta, courierTypeMeta } from './status'
import type { CourierTask, CourierTaskStatus } from './types'

interface CourierTasksProps {
  tasks: CourierTask[]
  loading?: boolean
  error?: string | null
}

const props = withDefaults(defineProps<CourierTasksProps>(), {
  loading: false,
  error: null,
})

const actionLabel: Record<CourierTaskStatus, string> = {
  menunggu_verifikasi: 'Lihat tugas',
  menunggu: 'Lihat & terima',
  ditugaskan: 'Lihat tugas',
  menuju_lokasi: 'Lihat tugas',
  menuju_laundry: 'Lihat tugas',
  pakaian_sampai_laundry: 'Lihat tugas',
  barang_diambil: 'Lihat tugas',
  barang_diantar: 'Lihat tugas',
  selesai: 'Lihat tugas',
  gagal: 'Lihat tugas',
}
</script>

<template>
  <div
    class="h-full rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Tugas Aktif</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Tugas Anda dan penjemputan/pengantaran yang siap diambil.
        </p>
      </div>
      <Badge color="primary" size="sm">{{ props.tasks.length }} tugas</Badge>
    </div>

    <div class="mt-4 space-y-3">
      <article
        v-for="task in props.tasks"
        :key="task.id"
        class="rounded-xl border border-gray-200 p-4 dark:border-gray-800"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <Badge size="sm" :color="courierTypeMeta[task.type].color">
              {{ courierTypeMeta[task.type].label }}
            </Badge>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ task.taskNumber }}</span>
          </div>
          <Badge size="sm" :color="courierStatusMeta[task.status].color">
            {{ courierStatusMeta[task.status].label }}
          </Badge>
        </div>

        <h4 class="mt-3 text-sm font-semibold text-gray-800 dark:text-white/90">
          {{ task.customer }}
        </h4>

        <div class="mt-2 space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
          <p class="flex items-start gap-2">
            <MapIcon class="mt-0.5 size-4 shrink-0" />
            {{ task.address }}
          </p>
          <p class="flex items-center gap-2">
            <TimeIcon class="size-4 shrink-0" />
            {{ task.schedule }}
          </p>
          <p v-if="task.note" class="flex items-start gap-2 text-gray-400 dark:text-gray-500">
            <span
              class="mt-1.5 size-1 shrink-0 rounded-full bg-gray-300 dark:bg-gray-600"
              aria-hidden="true"
            />
            {{ task.note }}
          </p>
        </div>

        <div
          class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-800"
        >
          <a
            :href="`tel:${task.phone.replace(/[^0-9+]/g, '')}`"
            class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400"
          >
            <CallIcon class="size-4" />
            {{ task.phone }}
          </a>

          <a
            v-if="task.waLink"
            :href="task.waLink"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-1.5 text-xs font-medium text-green-600 hover:text-green-700 dark:text-green-400"
          >
            <CallIcon class="size-4" />
            WhatsApp
          </a>

          <Button as-child size="xs">
            <RouterLink :to="`/pickup-delivery/${task.id}`">
              {{ actionLabel[task.status] }}
            </RouterLink>
          </Button>
        </div>
      </article>

      <p
        v-if="props.tasks.length === 0"
        class="py-10 text-center text-sm text-gray-500 dark:text-gray-400"
      >
        {{ props.error ?? (props.loading ? 'Memuat tugas…' : 'Tidak ada tugas aktif.') }}
      </p>
    </div>
  </div>
</template>
