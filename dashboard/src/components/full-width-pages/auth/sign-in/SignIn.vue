<script setup lang="ts">
import SignInFormFields from './Form.vue'
import SignInHeader from './Header.vue'
</script>

<template>
  <div class="flex w-full flex-1 flex-col lg:w-1/2">
    <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 py-10 sm:px-0">
      <div class="mb-8 flex items-center gap-3 lg:hidden">
        <span
          class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white shadow-theme-xs"
        >
          <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
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
        <span class="flex flex-col leading-tight">
          <span class="text-base font-bold text-gray-800 dark:text-white">Pemuda Laundry</span>
          <span class="text-[11px] text-gray-500 dark:text-gray-400">Manajemen Operasional</span>
        </span>
      </div>

      <div>
        <SignInHeader />
        <SignInFormFields />
      </div>

      <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-500">
        &copy; {{ new Date().getFullYear() }} Pemuda Laundry. Seluruh hak cipta dilindungi.
      </p>
    </div>
  </div>
</template>
