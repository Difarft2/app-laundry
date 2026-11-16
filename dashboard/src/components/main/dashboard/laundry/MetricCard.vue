<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { ArrowDownIcon, ArrowUpIcon } from '@/icons/layout'
import { cn } from '@/utils/cn'
import { metricToneClasses } from './status'
import type { MetricItem } from './types'

interface MetricCardProps {
  metric: MetricItem
}

const props = defineProps<MetricCardProps>()

const trendColor = computed(() => {
  if (props.metric.trendDirection === 'down') return 'error'
  if (props.metric.trendDirection === 'flat') return 'gray'
  return 'success'
})
</script>

<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white p-5 transition-shadow hover:shadow-theme-sm dark:border-gray-800 dark:bg-white/3"
  >
    <div class="flex items-start justify-between gap-3">
      <span
        :class="
          cn(
            'flex size-11 items-center justify-center rounded-xl [&>svg]:size-6',
            metricToneClasses[metric.tone],
          )
        "
      >
        <component :is="metric.icon" />
      </span>

      <Badge v-if="metric.trend" :color="trendColor" size="sm">
        <ArrowDownIcon v-if="metric.trendDirection === 'down'" />
        <ArrowUpIcon v-else-if="metric.trendDirection !== 'flat'" />
        {{ metric.trend }}
      </Badge>
    </div>

    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ metric.label }}</p>
    <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">{{ metric.value }}</p>
    <p class="mt-1 line-clamp-2 text-xs text-gray-400 dark:text-gray-500">{{ metric.hint }}</p>
  </div>
</template>
