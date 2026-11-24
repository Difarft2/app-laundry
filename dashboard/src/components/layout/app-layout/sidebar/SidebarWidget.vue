<script setup lang="ts">
import { useSettingsStore } from '@/stores/settings'
import { whatsappLink } from '@/utils/whatsapp'

const settings = useSettingsStore()
const buildVersion = __APP_BUILD_VERSION__
</script>

<template>
  <div class="mb-4">
    <div
      class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 dark:border-gray-800 dark:bg-white/3"
    >
      <div class="flex items-center justify-between gap-2">
        <h3 class="text-xs font-semibold text-gray-800 dark:text-white/90">Status App</h3>
        <span
          class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-600 dark:bg-green-500/15 dark:text-green-500"
        >
          <span class="size-1.5 rounded-full bg-green-500" />
          Aktif
        </span>
      </div>

      <p class="mt-2 text-xs font-medium text-gray-700 dark:text-gray-300">
        {{ settings.namaApp }}
      </p>

      <dl class="mt-3 grid grid-cols-2 gap-2 border-t border-gray-200 pt-3 dark:border-gray-800">
        <div>
          <dt class="text-[10px] text-gray-400 dark:text-gray-500">Versi</dt>
          <dd class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-white/90">
            v{{ settings.versiApp }}
          </dd>
        </div>
        <div>
          <dt class="text-[10px] text-gray-400 dark:text-gray-500">Build</dt>
          <dd class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-white/90">
            {{ buildVersion }}
          </dd>
        </div>
      </dl>

      <a
        v-if="whatsappLink(settings.current.nomor_laundry)"
        :href="whatsappLink(settings.current.nomor_laundry) ?? '#'"
        target="_blank"
        rel="noopener"
        class="mt-3 block truncate text-[11px] text-gray-500 hover:text-brand-500 dark:text-gray-400"
      >
        {{ settings.current.nomor_laundry }}
      </a>
    </div>
  </div>
</template>
