<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CallIcon, CheckIcon, MapIcon, TimeIcon, XIcon } from '@/icons/layout'
import type { LandingRequestItem } from './types'

interface LandingDeliveryRequestsProps {
  requests: LandingRequestItem[]
  loading?: boolean
  error?: string | null
  busyId?: number | null
}

const props = withDefaults(defineProps<LandingDeliveryRequestsProps>(), {
  loading: false,
  error: null,
  busyId: null,
})

const emit = defineEmits<{
  verify: [id: number]
  reject: [id: number]
}>()

const isBusy = (id: string): boolean => props.busyId === Number(id)
</script>

<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Permintaan Kurir dari Website
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Periksa data pengirim, lalu verifikasi atau tolak sebelum menugaskan kurir.
        </p>
      </div>
      <Badge color="warning" size="sm">{{ props.requests.length }} baru</Badge>
    </div>

    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
      <li v-for="request in props.requests" :key="request.id" class="py-4 first:pt-1">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <Badge size="sm" color="primary">Penjemputan</Badge>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ request.taskNumber }}</span>
          </div>
          <span class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
            <TimeIcon class="size-3.5" />
            {{ request.createdAt }}
          </span>
        </div>

        <p class="mt-2 text-sm font-semibold text-gray-800 dark:text-white/90">
          {{ request.customer }}
        </p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ request.phone }}</p>
        <p class="mt-2 flex items-start gap-1.5 text-xs text-gray-600 dark:text-gray-300">
          <MapIcon class="mt-0.5 size-3.5 shrink-0" />
          {{ request.address }}
        </p>

        <div class="mt-3 flex flex-wrap gap-2">
          <Button
            size="xs"
            :disabled="isBusy(request.id)"
            @click="emit('verify', Number(request.id))"
          >
            <CheckIcon class="size-3.5" />
            {{ isBusy(request.id) ? 'Memproses...' : 'Verifikasi' }}
          </Button>
          <Button v-if="request.waLink" as-child size="xs" variant="outline">
            <a :href="request.waLink" target="_blank" rel="noopener">
              <CallIcon class="size-3.5" />
              WhatsApp
            </a>
          </Button>
          <Button
            size="xs"
            variant="outline"
            :disabled="isBusy(request.id)"
            @click="emit('reject', Number(request.id))"
          >
            <XIcon class="size-3.5" />
            Tolak
          </Button>
        </div>
      </li>

      <li
        v-if="props.requests.length === 0"
        class="py-8 text-center text-sm text-gray-500 dark:text-gray-400"
      >
        Tidak ada permintaan baru dari website.
      </li>
    </ul>

    <p v-if="props.loading" class="mt-3 text-xs text-gray-400">Memuat permintaan terbaru...</p>
    <p v-if="props.error" class="mt-3 text-xs text-red-500">{{ props.error }}</p>
  </div>
</template>
