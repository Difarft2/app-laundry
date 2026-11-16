<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { SidebarHeader } from '@/components/ui/sidebar'

interface AppSidebarHeaderProps {
  showContent: boolean
}

defineProps<AppSidebarHeaderProps>()
</script>

<template>
  <SidebarHeader class="px-5 py-6">
    <RouterLink to="/" class="flex items-center gap-3">
      <span
        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white shadow-theme-xs"
      >
        <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
          <rect
            x="3.75"
            y="2.75"
            width="16.5"
            height="18.5"
            rx="4"
            stroke="currentColor"
            stroke-width="1.6"
          />
          <circle cx="12" cy="13.5" r="4.25" stroke="currentColor" stroke-width="1.6" />
          <path
            d="M9 13.5c.8.9 1.8 1.35 3 1.35s2.2-.45 3-1.35"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
          />
          <circle cx="7.5" cy="6.5" r="1" fill="currentColor" />
          <rect x="14" y="5.5" width="3.5" height="2" rx="0.8" fill="currentColor" />
        </svg>
      </span>

      <span v-if="showContent" class="flex min-w-0 flex-col leading-tight">
        <span class="truncate text-base font-bold text-gray-800 dark:text-white">
          Pemuda Laundry
        </span>
        <span class="truncate text-[11px] text-gray-500 dark:text-gray-400">
          Manajemen Operasional
        </span>
      </span>
    </RouterLink>
  </SidebarHeader>
</template>
