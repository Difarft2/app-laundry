<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { cn } from '@/utils/cn'

const props = withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })

const settings = useSettingsStore()
const imageFailed = ref(false)

watch(
  () => settings.current.logo_url,
  () => {
    imageFailed.value = false
  },
)

const boxClass = computed(
  () =>
    ({
      sm: 'size-8 rounded-lg',
      md: 'size-10 rounded-xl shadow-theme-xs',
      lg: 'size-12 rounded-2xl shadow-theme-lg',
    })[props.size],
)

const iconClass = computed(() => ({ sm: 'size-5', md: 'size-6', lg: 'size-7' })[props.size])
</script>

<template>
  <span
    :class="cn('flex shrink-0 items-center justify-center overflow-hidden bg-brand-500 text-white', boxClass)"
  >
    <img
      v-if="settings.current.logo_url && !imageFailed"
      :src="settings.current.logo_url"
      :alt="`Logo ${settings.namaApp}`"
      class="size-full object-contain p-1"
      @error="imageFailed = true"
    />
    <svg v-else viewBox="0 0 24 24" fill="none" :class="iconClass" aria-hidden="true">
      <rect
        x="3.75"
        y="2.75"
        width="16.5"
        height="18.5"
        rx="4"
        stroke="currentColor"
        stroke-width="1.6"
      />
      <circle cx="12" cy="13.5" r="4.25" stroke="currentColor" stroke-width="1.6" />
      <path
        d="M9 13.5c.8.9 1.8 1.35 3 1.35s2.2-.45 3-1.35"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
      />
      <circle cx="7.5" cy="6.5" r="1" fill="currentColor" />
      <rect x="14" y="5.5" width="3.5" height="2" rx="0.8" fill="currentColor" />
    </svg>
  </span>
</template>
