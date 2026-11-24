<script setup lang="ts">
import { RouterLink } from 'vue-router'
import GridShape from '@/components/common/CommonGridShape.vue'
import ThemeToggler from '@/components/full-width-pages/auth/ThemeToggler.vue'
import { useSettingsStore } from '@/stores/settings'
import AppLogo from './AppLogo.vue'

const settings = useSettingsStore()
</script>

<template>
  <div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
    <div
      class="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900"
    >
      <slot />
      <div
        class="relative hidden h-full w-full items-center justify-center bg-brand-950 lg:grid lg:w-1/2 dark:bg-white/5"
      >
        <div class="z-1 flex h-full items-center justify-center px-10">
          <GridShape />

          <div class="flex max-w-md flex-col items-center">
            <RouterLink to="/" class="mb-5 flex items-center gap-3">
              <AppLogo size="lg" />
              <span class="text-left">
                <span class="block text-lg font-bold text-white">{{ settings.namaApp }}</span>
                <span class="block text-xs text-gray-400 dark:text-white/60">
                  Manajemen Operasional
                </span>
              </span>
            </RouterLink>

            <p class="text-center text-sm leading-6 text-gray-400 dark:text-white/60">
              Kelola pesanan, antrean proses laundry, pembayaran, dan tugas antar-jemput dalam satu
              dashboard.
            </p>

            <div class="mt-8 w-full space-y-3">
              <div
                v-for="item in [
                  'Pantau pesanan dan pendapatan harian',
                  'Atur antrean proses laundry',
                  'Kelola pengguna dan hak akses',
                ]"
                :key="item"
                class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3"
              >
                <span
                  class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-500/20 text-brand-300"
                >
                  <svg viewBox="0 0 20 20" fill="none" class="size-3.5" aria-hidden="true">
                    <path
                      d="m4 10.5 4 4 8-9"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </span>
                <span class="text-sm text-gray-300 dark:text-white/70">{{ item }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="fixed end-6 bottom-6 z-50 hidden sm:block">
        <ThemeToggler />
      </div>
    </div>
  </div>
</template>
