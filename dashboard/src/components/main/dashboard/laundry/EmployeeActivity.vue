<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import type { RoleBadgeColor } from '@/constants/roles'
import { cn } from '@/utils/cn'
import { getInitials } from '@/utils/get-initials'
import { activityDotClasses } from './status'
import type { ActivityStat, EmployeeActivityItem } from './types'

interface EmployeeActivityProps {
  activities: EmployeeActivityItem[]
  stats: ActivityStat[]
  title?: string
  subtitle?: string
}

const props = withDefaults(defineProps<EmployeeActivityProps>(), {
  title: 'Riwayat Aktivitas Karyawan',
  subtitle: 'Jejak aktivitas akun owner, admin, karyawan, dan kurir.',
})

const roleColor = (role: string): RoleBadgeColor => {
  if (role === 'owner') return 'primary'
  if (role === 'admin') return 'info'
  if (role === 'kurir') return 'success'
  return 'warning'
}

const roleLabel = (role: string): string =>
  role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Sistem'
</script>

<template>
  <div
    class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        {{ props.title }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ props.subtitle }}</p>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
      <div
        v-for="stat in props.stats"
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
      <li v-for="activity in props.activities" :key="activity.id" class="relative ps-12">
        <span
          class="absolute inset-s-0 top-0 flex size-9 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
          aria-hidden="true"
        >
          {{ getInitials(activity.employee) }}
        </span>
        <span
          :class="
            cn(
              'absolute inset-s-9 top-0 size-2.5 rounded-full ring-4 ring-white dark:ring-gray-900',
              activityDotClasses[activity.tone],
            )"
          aria-hidden="true"
        />

        <div class="flex flex-wrap items-center gap-2">
          <p class="text-sm font-medium text-gray-800 dark:text-white/90">
            {{ activity.employee }}
          </p>
          <Badge :color="roleColor(activity.employeeRole)" size="sm">
            {{ roleLabel(activity.employeeRole) }}
          </Badge>
          <span class="text-xs text-gray-400 dark:text-gray-500">{{ activity.time }}</span>
        </div>
        <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ activity.title }}</p>
        <p class="mt-0.5 text-xs leading-5 text-gray-500 dark:text-gray-400">
          {{ activity.description }}
        </p>
      </li>
      <li v-if="props.activities.length === 0" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
        Belum ada aktivitas yang tercatat.
      </li>
    </ol>
  </div>
</template>
