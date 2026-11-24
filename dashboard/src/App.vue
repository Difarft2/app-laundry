<template>
  <ConfigProvider :dir="dir">
    <ThemeProvider>
      <SidebarProvider>
        <RouterView />
      </SidebarProvider>
    </ThemeProvider>
  </ConfigProvider>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ConfigProvider } from 'reka-ui'
import ThemeProvider from './components/layout/ThemeProvider.vue'
import SidebarProvider from './components/layout/SidebarProvider.vue'
import { useRTL } from './composables/useRTL'
import { useSettingsStore } from './stores/settings'

// reka-ui popper wrappers (dropdowns, popovers, tooltips, selects) read their direction from here;
// without it they stay `ltr` and floating-ui aligns them as if the page was left-to-right.
const { isRtl } = useRTL()
const dir = computed(() => (isRtl.value ? 'rtl' : 'ltr'))

const settings = useSettingsStore()
const route = useRoute()

onMounted(() => {
  void settings.load()
})

watch(
  () => settings.namaApp,
  (name) => {
    const pageTitle = String(route.meta.title ?? '')
    document.title = pageTitle ? `${pageTitle} | ${name}` : name
  },
)
</script>
