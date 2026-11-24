<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/utils'
import { useSettingsStore } from '@/stores/settings'

const props = defineProps<{ title?: string; description?: string; class?: string }>()

const settings = useSettingsStore()
const title = computed(() => props.title ?? `Masuk ke ${settings.namaApp}`)
</script>

<template>
  <div :class="cn('mb-5 sm:mb-8', $props.class)">
    <h1 class="mb-2 text-title-sm font-semibold text-gray-800 dark:text-white/90">
      {{ title }}
    </h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">
      {{ description ?? 'Gunakan email dan password akun Anda untuk mengakses dashboard operasional.' }}
    </p>
  </div>
</template>
