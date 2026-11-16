<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { roleColor, roleLabel } from '@/constants/roles'
import { ChevronDownIcon, LogOutIcon, UserCircleIcon } from '@/icons/layout'
import { useAuthStore } from '@/stores/auth'
import { cn } from '@/utils/cn'
import { getInitials } from '@/utils/get-initials'

const auth = useAuthStore()
const router = useRouter()

const isOpen = ref(false)
const loggingOut = ref(false)

const displayName = computed(() => auth.user?.name ?? 'Pengguna')
const email = computed(() => auth.user?.email ?? '—')
const primaryRole = computed(() => auth.role ?? auth.user?.roles[0] ?? '')
const primaryRoleLabel = computed(() =>
  primaryRole.value ? roleLabel(primaryRole.value) : 'Pengguna',
)

const handleLogout = async () => {
  if (loggingOut.value) return

  loggingOut.value = true

  try {
    await auth.logout()
  } finally {
    loggingOut.value = false
    isOpen.value = false
    await router.replace({ name: 'Signin' })
  }
}
</script>

<template>
  <DropdownMenu :open="isOpen" @update:open="isOpen = $event">
    <DropdownMenuTrigger class="dropdown-toggle flex items-center text-gray-700 dark:text-gray-400">
      <span
        class="me-3 flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
        aria-hidden="true"
      >
        {{ getInitials(displayName) }}
      </span>

      <span class="me-1 hidden max-w-40 truncate text-sm font-medium sm:block">
        {{ displayName }}
      </span>

      <ChevronDownIcon
        :class="
          cn(
            'size-5 text-gray-500 duration-200 dark:text-gray-400',
            isOpen ? 'rotate-180' : 'rotate-0',
          )
        "
      />
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" :side-offset="12" class="w-65 p-3">
      <div class="flex items-center gap-3 px-3 py-2">
        <span
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
          aria-hidden="true"
        >
          {{ getInitials(displayName) }}
        </span>
        <div class="min-w-0">
          <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ displayName }}
          </p>
          <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
            {{ email }}
          </p>
        </div>
        <Badge
          v-if="primaryRole"
          :color="roleColor(primaryRole)"
          size="sm"
          class="ms-auto shrink-0"
        >
          {{ primaryRoleLabel }}
        </Badge>
      </div>

      <DropdownMenuSeparator />

      <DropdownMenuItem to="/account">
        <UserCircleIcon class="size-6" />
        Akun saya
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <DropdownMenuItem variant="destructive" :disabled="loggingOut" @select="handleLogout">
        <LogOutIcon />
        {{ loggingOut ? 'Keluar...' : 'Keluar' }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
