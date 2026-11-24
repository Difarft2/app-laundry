<script setup lang="ts">
import { useSettingsStore } from '@/stores/settings'

const settings = useSettingsStore()
const year = new Date().getFullYear()
</script>

<template>
  <footer
    class="mt-auto border-t border-gray-200 px-4 py-4 text-center text-xs text-gray-500 sm:px-6 dark:border-gray-800 dark:text-gray-400"
  >
    &copy; {{ year }} {{ settings.namaApp }} &middot; Versi {{ settings.versiApp }}
  </footer>
</template>
