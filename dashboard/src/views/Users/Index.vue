<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import DeleteUserDialog from '@/components/main/users/DeleteUserDialog.vue'
import ResetPasswordDialog from '@/components/main/users/ResetPasswordDialog.vue'
import RoleBadges from '@/components/main/users/RoleBadges.vue'
import UserFormDialog from '@/components/main/users/UserFormDialog.vue'
import type { UserFormMode } from '@/components/main/users/schema'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHeader, TableRow } from '@/components/ui/table'
import { ROLE_OPTIONS } from '@/constants/roles'
import {
  HorizontalDotsIcon,
  KeyIcon,
  LoaderCircleIcon,
  PencilIcon,
  PlusIcon,
  SearchIcon,
  TrashBinIcon,
  XIcon,
} from '@/icons/layout'
import { normalizeApiError } from '@/services/http'
import { usersApi } from '@/services/users'
import { useAuthStore } from '@/stores/auth'
import type { AuthUser, PaginationMeta } from '@/types/auth'
import { formatDateShort } from '@/utils/format'
import { getInitials } from '@/utils/get-initials'

const auth = useAuthStore()

const users = ref<AuthUser[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const search = ref('')
const roleFilter = ref('all')
const page = ref(1)

const canCreate = computed(() => auth.hasPermission('users.create'))
const canUpdate = computed(() => auth.hasPermission('users.update'))
const canDelete = computed(() => auth.hasPermission('users.delete'))
const canAssignOwner = computed(() => auth.hasRole('owner'))

const totalPages = computed(() => Math.max(1, meta.value?.last_page ?? 1))

const isSelf = (user: AuthUser) => auth.user?.id === user.id

const removeSuccessMessage = () => {
  successMessage.value = null
}

const load = async (): Promise<void> => {
  loading.value = true
  loadError.value = null

  try {
    const result = await usersApi.list({
      search: search.value.trim() || undefined,
      role: roleFilter.value === 'all' ? undefined : roleFilter.value,
      page: page.value,
    })

    users.value = result.data
    meta.value = result.meta

    // Setelah penghapusan, halaman terakhir bisa ikut berkurang.
    if (result.data.length === 0 && page.value > 1 && result.meta.last_page < page.value) {
      page.value = Math.max(1, result.meta.last_page)
      await load()
    }
  } catch (error) {
    loadError.value = normalizeApiError(error).message
  } finally {
    loading.value = false
  }
}

let searchTimer: ReturnType<typeof setTimeout> | undefined

const scheduleLoad = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    void load()
  }, 350)
}

watch(search, () => {
  page.value = 1
  scheduleLoad()
})

watch(roleFilter, () => {
  page.value = 1
  void load()
})

const goToPage = (next: number) => {
  if (next < 1 || next > totalPages.value || next === page.value) return
  page.value = next
  void load()
}

onMounted(() => {
  void load()
})

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
})

const paginationPages = computed<(number | 'ellipsis-start' | 'ellipsis-end')[]>(() => {
  const total = totalPages.value
  const current = page.value

  if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1)
  if (current <= 4) return [1, 2, 3, 4, 5, 'ellipsis-end', total]
  if (current >= total - 3) {
    return [1, 'ellipsis-start', total - 4, total - 3, total - 2, total - 1, total]
  }
  return [1, 'ellipsis-start', current - 1, current, current + 1, 'ellipsis-end', total]
})

const formOpen = ref(false)
const formMode = ref<UserFormMode>('create')
const editingUser = ref<AuthUser | null>(null)

const resetOpen = ref(false)
const deleteOpen = ref(false)
const targetUser = ref<AuthUser | null>(null)

const openCreate = () => {
  formMode.value = 'create'
  editingUser.value = null
  formOpen.value = true
}

const openEdit = (user: AuthUser) => {
  formMode.value = 'edit'
  editingUser.value = user
  formOpen.value = true
}

const openReset = (user: AuthUser) => {
  targetUser.value = user
  resetOpen.value = true
}

const openDelete = (user: AuthUser) => {
  targetUser.value = user
  deleteOpen.value = true
}

const handleSaved = async (saved: AuthUser, mode: UserFormMode) => {
  successMessage.value =
    mode === 'create'
      ? `Pengguna ${saved.name} berhasil ditambahkan.`
      : `Data ${saved.name} berhasil diperbarui.`

  if (isSelf(saved)) {
    await auth.refreshUser()
  }

  await load()
}

const handlePasswordReset = async (message: string) => {
  successMessage.value = message
  await load()
}

const handleDeleted = async (message: string) => {
  successMessage.value = message
  await load()
}

const formatCreatedAt = (value: string) => {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '—' : formatDateShort(date)
}
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb page-title="Pengguna" />

      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
      >
        <!-- Header -->
        <div
          class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
              Manajemen pengguna
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Kelola akun dan role yang menentukan akses ke dashboard.
            </p>
          </div>

          <Button v-if="canCreate" class="shrink-0" @click="openCreate">
            <PlusIcon class="size-5" />
            Tambah pengguna
          </Button>
        </div>

        <!-- Toolbar -->
        <div
          class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6"
        >
          <div class="w-full sm:max-w-xs">
            <InputGroup class="border-gray-300 dark:border-gray-700">
              <InputGroupAddon align="inline-start" class="ps-3.5">
                <SearchIcon class="size-5 text-gray-500 dark:text-gray-400" />
              </InputGroupAddon>
              <InputGroupInput
                v-model="search"
                type="search"
                placeholder="Cari nama, email, atau nomor HP..."
                class="text-sm"
              />
            </InputGroup>
          </div>

          <div class="flex items-center gap-3">
            <Select v-model="roleFilter">
              <SelectTrigger class="w-full sm:w-44" aria-label="Filter role">
                <SelectValue placeholder="Semua role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua role</SelectItem>
                <SelectItem v-for="role in ROLE_OPTIONS" :key="role.value" :value="role.value">
                  {{ role.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Badge v-if="meta" color="gray" size="sm" class="shrink-0">
              {{ meta.total }} pengguna
            </Badge>
          </div>
        </div>

        <div
          v-if="successMessage"
          role="status"
          class="mx-5 mb-4 flex items-start justify-between gap-3 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 lg:mx-6 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
        >
          <span>{{ successMessage }}</span>
          <button
            type="button"
            class="shrink-0 rounded p-0.5 hover:bg-success-100 dark:hover:bg-success-500/20"
            aria-label="Tutup notifikasi"
            @click="removeSuccessMessage"
          >
            <XIcon class="size-4" />
          </button>
        </div>

        <div
          v-if="loadError"
          role="alert"
          class="mx-5 mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 lg:mx-6 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
        >
          {{ loadError }}
          <button type="button" class="ms-2 font-medium underline" @click="load()">
            Coba lagi
          </button>
        </div>

        <!-- Table -->
        <div class="max-w-full overflow-x-auto">
          <div class="min-w-200">
            <Table>
              <TableHeader
                class="border-y border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900"
              >
                <TableRow>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Pengguna
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Role
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Dibuat
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-end text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Aksi
                  </TableCell>
                </TableRow>
              </TableHeader>

              <TableBody class="divide-y divide-gray-100 dark:divide-gray-800">
                <TableRow v-if="loading && users.length === 0">
                  <TableCell colspan="4" class="px-6 py-12 text-center">
                    <span
                      class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"
                    >
                      <LoaderCircleIcon class="size-5 animate-spin" />
                      Memuat data pengguna...
                    </span>
                  </TableCell>
                </TableRow>

                <TableRow v-else-if="users.length === 0">
                  <TableCell colspan="4" class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{
                        search || roleFilter !== 'all'
                          ? 'Tidak ada pengguna yang cocok.'
                          : 'Belum ada pengguna lain.'
                      }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{
                        search || roleFilter !== 'all'
                          ? 'Ubah kata kunci atau filter role untuk melihat data lain.'
                          : 'Tambahkan akun karyawan, kurir, atau admin melalui tombol di atas.'
                      }}
                    </p>
                  </TableCell>
                </TableRow>

                <template v-else>
                  <TableRow
                    v-for="user in users"
                    :key="user.id"
                    class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/2"
                  >
                    <TableCell class="px-5 py-4 lg:px-6">
                      <div class="flex items-center gap-3">
                        <span
                          class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
                          aria-hidden="true"
                        >
                          {{ getInitials(user.name) }}
                        </span>
                        <div class="min-w-0">
                          <div class="flex flex-wrap items-center gap-2">
                            <p
                              class="truncate text-sm font-medium text-gray-800 dark:text-white/90"
                            >
                              {{ user.name }}
                            </p>
                            <Badge v-if="isSelf(user)" color="info" size="sm">Anda</Badge>
                          </div>
                          <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ user.email }}
                          </p>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell class="px-5 py-4 lg:px-6">
                      <RoleBadges :roles="user.roles" />
                    </TableCell>

                    <TableCell
                      class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 lg:px-6 dark:text-gray-400"
                    >
                      {{ formatCreatedAt(user.created_at) }}
                    </TableCell>

                    <TableCell class="px-5 py-4 text-end lg:px-6">
                      <DropdownMenu v-if="canUpdate || canDelete">
                        <DropdownMenuTrigger as-child>
                          <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-gray-500 dark:text-gray-400"
                            :aria-label="`Aksi untuk ${user.name}`"
                          >
                            <HorizontalDotsIcon class="size-5" />
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" class="w-48">
                          <DropdownMenuItem v-if="canUpdate" @select="openEdit(user)">
                            <PencilIcon class="size-5" />
                            Edit pengguna
                          </DropdownMenuItem>

                          <DropdownMenuItem
                            v-if="canUpdate && !isSelf(user)"
                            @select="openReset(user)"
                          >
                            <KeyIcon class="size-5" />
                            Reset password
                          </DropdownMenuItem>

                          <DropdownMenuSeparator v-if="canDelete" />

                          <DropdownMenuItem
                            v-if="canDelete"
                            variant="destructive"
                            :disabled="isSelf(user)"
                            :title="
                              isSelf(user) ? 'Anda tidak dapat menghapus akun sendiri.' : undefined
                            "
                            @select="openDelete(user)"
                          >
                            <TrashBinIcon class="size-5" />
                            Hapus pengguna
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>

                      <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </div>

        <!-- Footer -->
        <div
          class="flex flex-col gap-4 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800"
        >
          <p class="text-xs text-gray-500 dark:text-gray-400">
            <template v-if="meta && meta.total > 0">
              Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }} pengguna
            </template>
            <template v-else>Tidak ada data untuk ditampilkan</template>
          </p>

          <Pagination v-if="totalPages > 1" class="mx-0 w-auto justify-end">
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious
                  text="Sebelumnya"
                  size="sm"
                  :disabled="page <= 1 || loading"
                  @click="goToPage(page - 1)"
                />
              </PaginationItem>

              <PaginationItem v-for="item in paginationPages" :key="String(item)">
                <PaginationEllipsis v-if="typeof item === 'string'" class="size-9" />
                <PaginationLink
                  v-else
                  href="#"
                  size="sm"
                  :is-active="item === page"
                  :disabled="loading"
                  @click.prevent="goToPage(item)"
                >
                  {{ item }}
                </PaginationLink>
              </PaginationItem>

              <PaginationItem>
                <PaginationNext
                  text="Berikutnya"
                  size="sm"
                  :disabled="page >= totalPages || loading"
                  @click="goToPage(page + 1)"
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </div>
      </div>
    </div>

    <UserFormDialog
      v-model:open="formOpen"
      :mode="formMode"
      :user="editingUser"
      :can-assign-owner="canAssignOwner"
      @saved="handleSaved"
    />

    <ResetPasswordDialog v-model:open="resetOpen" :user="targetUser" @saved="handlePasswordReset" />

    <DeleteUserDialog v-model:open="deleteOpen" :user="targetUser" @deleted="handleDeleted" />
  </AdminLayout>
</template>
