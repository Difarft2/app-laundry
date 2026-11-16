<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { CalendarIcon } from '@/icons/layout'
import { cn } from '@/utils/cn'
import { roleMeta } from './status'
import { formatDateLong } from '@/utils/format'
import type { DashboardRole } from './types'

interface DashboardHeaderProps {
  role: DashboardRole
  name: string
  /** Switcher role hanya untuk pratinjau owner/admin. */
  canPreviewRoles?: boolean
}

const props = withDefaults(defineProps<DashboardHeaderProps>(), {
  canPreviewRoles: false,
})

const emit = defineEmits<{ (e: 'update:role', role: DashboardRole): void }>()

const roles: DashboardRole[] = ['owner', 'admin', 'karyawan', 'kurir']

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

    <div v-if="canPreviewRoles" class="shrink-0">
      <p class="mb-2 text-xs font-medium text-gray-400 dark:text-gray-500">
        Pratinjau tampilan role
      </p>
      <div
        class="inline-flex flex-wrap gap-1 rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-white/3"
        role="group"
        aria-label="Pilih role untuk melihat tampilan dashboard"
      >
        <button
          v-for="item in roles"
          :key="item"
          type="button"
          :aria-pressed="role === item"
          :class="
            cn(
              'rounded-lg px-3 py-1.5 text-xs font-medium transition-colors sm:text-sm',
              role === item
                ? 'bg-brand-500 text-white shadow-theme-xs'
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white',
            )
          "
          @click="emit('update:role', item)"
        >
          {{ roleMeta[item].label }}
        </button>
      </div>
    </div>
  </section>
</template>
