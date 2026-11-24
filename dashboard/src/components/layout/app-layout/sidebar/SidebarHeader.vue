<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { SidebarHeader } from '@/components/ui/sidebar'
import { useSettingsStore } from '@/stores/settings'
import AppLogo from '../../AppLogo.vue'

interface AppSidebarHeaderProps {
  showContent: boolean
}

defineProps<AppSidebarHeaderProps>()

const settings = useSettingsStore()
</script>

<template>
  <SidebarHeader class="px-5 py-6">
    <RouterLink to="/" class="flex items-center gap-3">
      <AppLogo />

      <span v-if="showContent" class="flex min-w-0 flex-col leading-tight">
        <span class="truncate text-base font-bold text-gray-800 dark:text-white">
          {{ settings.namaApp }}
        </span>
        <span class="truncate text-[11px] text-gray-500 dark:text-gray-400">
          Manajemen Operasional
        </span>
      </span>
    </RouterLink>
  </SidebarHeader>
</template>
