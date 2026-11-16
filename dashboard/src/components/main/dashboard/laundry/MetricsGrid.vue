<script setup lang="ts">
import MetricCard from './MetricCard.vue'
import type { MetricItem } from './types'

interface MetricsGridProps {
  metrics: MetricItem[]
}

defineProps<MetricsGridProps>()
</script>

<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
    <MetricCard v-for="metric in metrics" :key="metric.id" :metric="metric" />
  </div>
</template>
