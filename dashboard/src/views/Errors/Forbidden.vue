<script setup lang="ts">
import AdminLayout from '@/components/layout/AdminLayout.vue'
</script>

<template>
  <AdminLayout>
    <div
      class="flex min-h-[60vh] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 text-center dark:border-gray-800 dark:bg-white/3"
    >
      <span
        class="flex size-14 items-center justify-center rounded-2xl bg-red-50 text-red-500 dark:bg-red-500/15 dark:text-red-400"
      >
        <svg viewBox="0 0 24 24" fill="none" class="size-7" aria-hidden="true">
          <path
            d="M12 8.5v4.25M12 16.5h.01M10.29 3.86 2.82 17a2 2 0 0 0 1.71 3h14.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </span>

      <h1 class="mt-5 text-xl font-semibold text-gray-800 dark:text-white/90">Akses ditolak</h1>
      <p class="mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
        Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi Owner atau Admin bila akses
        memang dibutuhkan.
      </p>

      <RouterLink
        to="/"
        class="mt-5 inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600"
      >
        Kembali ke dashboard
      </RouterLink>
    </div>
  </AdminLayout>
</template>
