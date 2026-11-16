<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { MapIcon, TimeIcon } from '@/icons/layout'
import { pickupRequests } from './data'
import { courierStatusMeta, courierTypeMeta } from './status'
</script>

<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Antar-Jemput Menunggu
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Permintaan yang belum memiliki kurir.
        </p>
      </div>
      <Badge color="gray" size="sm">{{ pickupRequests.length }} tugas</Badge>
    </div>

    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
      <li v-for="request in pickupRequests" :key="request.id" class="py-3.5 first:pt-1">
        <div class="flex items-start justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <Badge size="sm" :color="courierTypeMeta[request.type].color">
              {{ courierTypeMeta[request.type].label }}
            </Badge>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ request.taskNumber }}</span>
          </div>
          <Badge size="sm" :color="courierStatusMeta[request.status].color">
            {{ courierStatusMeta[request.status].label }}
          </Badge>
        </div>

        <p class="mt-2 text-sm font-medium text-gray-800 dark:text-white/90">
          {{ request.customer }}
        </p>
        <p class="mt-1 flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400">
          <MapIcon class="mt-0.5 size-3.5 shrink-0" />
          {{ request.address }}
        </p>
        <div class="mt-2 flex items-center justify-between gap-2">
          <span class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
            <TimeIcon class="size-3.5" />
            {{ request.schedule }}
          </span>
          <Button variant="outline" size="xs" disabled title="Penugasan kurir belum tersedia">
            Tugaskan
          </Button>
        </div>
      </li>
    </ul>
  </div>
</template>
