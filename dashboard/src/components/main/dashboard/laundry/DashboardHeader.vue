<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { CalendarIcon } from '@/icons/layout'
import { roleMeta } from './status'
import { formatDateLong } from '@/utils/format'
import type { DashboardRole } from './types'

interface DashboardHeaderProps {
  role: DashboardRole
  name: string
}

const props = defineProps<DashboardHeaderProps>()

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 11) return 'Selamat pagi'
  if (hour < 15) return 'Selamat siang'
  if (hour < 19) return 'Selamat sore'
  return 'Selamat malam'
})

const firstName = computed(() => props.name.trim().split(/\s+/)[0] || 'Pengguna')

const today = computed(() => formatDateLong(new Date()))
</script>

<template>
  <section class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
    <div class="min-w-0">
      <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-xl font-semibold text-gray-800 sm:text-2xl dark:text-white/90">
          {{ greeting }}, {{ firstName }}
        </h1>
        <Badge color="primary" size="sm">{{ roleMeta[role].label }}</Badge>
        <Badge color="warning" size="sm">Mode demo</Badge>
      </div>

      <p class="mt-1.5 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
        {{ roleMeta[role].description }}
      </p>

      <p
        class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-400 dark:text-gray-500"
      >
        <CalendarIcon class="size-4" />
        <span class="capitalize">{{ today }}</span>
        <span aria-hidden="true">·</span>
        <span>Data demo, belum terhubung ke API.</span>
      </p>
    </div>

    <div class="shrink-0">
      <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Dashboard {{ roleMeta[role].label }}</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Tampilan mengikuti role akun yang sedang login.
      </p>
    </div>
  </section>
</template>
