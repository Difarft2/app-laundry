<script setup lang="ts">
import SignInFormFields from './Form.vue'
import SignInHeader from './Header.vue'
import AppLogo from '@/components/layout/AppLogo.vue'
import { useSettingsStore } from '@/stores/settings'

const settings = useSettingsStore()
</script>

<template>
  <div class="flex w-full flex-1 flex-col lg:w-1/2">
    <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 py-10 sm:px-0">
      <div class="mb-8 flex items-center gap-3 lg:hidden">
        <AppLogo />
        <span class="flex flex-col leading-tight">
          <span class="text-base font-bold text-gray-800 dark:text-white">
            {{ settings.namaApp }}
          </span>
          <span class="text-[11px] text-gray-500 dark:text-gray-400">Manajemen Operasional</span>
        </span>
      </div>

      <div>
        <SignInHeader />
        <SignInFormFields />
      </div>

      <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-500">
        &copy; {{ new Date().getFullYear() }} {{ settings.namaApp }}. Seluruh hak cipta dilindungi.
      </p>
    </div>
  </div>
</template>
