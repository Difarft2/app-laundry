<script setup lang="ts">
import { cn } from '@/utils/cn'
import { activityDotClasses } from './status'
import type { ActivityItem, ActivityStat } from './types'

interface DailyActivityProps {
  title?: string
  subtitle?: string
  stats: ActivityStat[]
  activities: ActivityItem[]
  /** Stretch the card to fill a taller grid row (e.g. beside a long queue). */
  fill?: boolean
}

withDefaults(defineProps<DailyActivityProps>(), {
  title: 'Ringkasan Aktivitas Hari Ini',
  subtitle: 'Perubahan terbaru pada operasional laundry.',
  fill: false,
})
</script>

<template>
  <div
    :class="
      cn(
        'flex flex-col rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/3',
        fill && 'h-full',
      )
    "
  >
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ title }}</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/3"
      >
        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
        <p class="mt-0.5 text-base font-semibold text-gray-800 dark:text-white/90">
          {{ stat.value }}
        </p>
      </div>
    </div>

    <ol class="custom-scrollbar mt-5 max-h-105 flex-1 space-y-4 overflow-y-auto pe-1">
      <li v-for="activity in activities" :key="activity.id" class="relative ps-6">
        <span
          :class="
            cn(
              'absolute inset-s-0 top-1.5 size-2.5 rounded-full ring-4 ring-white dark:ring-gray-900',
              activityDotClasses[activity.tone],
            )
          "
        />
        <span
          v-if="activity !== activities[activities.length - 1]"
          class="absolute inset-s-[4.5px] top-5 h-[calc(100%-8px)] w-px bg-gray-100 dark:bg-gray-800"
          aria-hidden="true"
        />
        <p class="text-xs text-gray-400 dark:text-gray-500">{{ activity.time }}</p>
        <p class="mt-0.5 text-sm font-medium text-gray-800 dark:text-white/90">
          {{ activity.title }}
        </p>
        <p class="mt-0.5 text-xs leading-5 text-gray-500 dark:text-gray-400">
          {{ activity.description }}
        </p>
      </li>
    </ol>
  </div>
</template>
