<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import DeletePaymentMethodDialog from '@/components/main/payment-methods/DeletePaymentMethodDialog.vue'
import PaymentMethodFormDialog from '@/components/main/payment-methods/PaymentMethodFormDialog.vue'
import type { PaymentMethodFormMode } from '@/components/main/payment-methods/schema'
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
import {
  DollarIcon,
  HorizontalDotsIcon,
  LoaderCircleIcon,
  PencilIcon,
  PlusIcon,
  SearchIcon,
  TrashBinIcon,
  XIcon,
} from '@/icons/layout'
import { normalizeApiError } from '@/services/http'
import { paymentMethodsApi } from '@/services/payment-methods'
import { useAuthStore } from '@/stores/auth'
import type { PaginationMeta } from '@/types/auth'
import type { PaymentMethod } from '@/types/payment-method'

const auth = useAuthStore()

const items = ref<PaymentMethod[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const search = ref('')
const statusFilter = ref('all')
const page = ref(1)

const canCreate = computed(() => auth.hasPermission('payment-methods.create'))
const canUpdate = computed(() => auth.hasPermission('payment-methods.update'))
const canDelete = computed(() => auth.hasPermission('payment-methods.delete'))

const totalPages = computed(() => Math.max(1, meta.value?.last_page ?? 1))

const removeSuccessMessage = () => {
  successMessage.value = null
}

const load = async (): Promise<void> => {
  loading.value = true
  loadError.value = null

  try {
    const result = await paymentMethodsApi.list({
      search: search.value.trim() || undefined,
      is_active: statusFilter.value === 'all' ? undefined : statusFilter.value === 'active',
      page: page.value,
    })

    items.value = result.data
    meta.value = result.meta

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

watch(statusFilter, () => {
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
const formMode = ref<PaymentMethodFormMode>('create')
const editingItem = ref<PaymentMethod | null>(null)

const deleteOpen = ref(false)
const targetItem = ref<PaymentMethod | null>(null)

const openCreate = () => {
  formMode.value = 'create'
  editingItem.value = null
  formOpen.value = true
}

const openEdit = (item: PaymentMethod) => {
  formMode.value = 'edit'
  editingItem.value = item
  formOpen.value = true
}

const openDelete = (item: PaymentMethod) => {
  targetItem.value = item
  deleteOpen.value = true
}

const handleSaved = async (saved: PaymentMethod, mode: PaymentMethodFormMode) => {
  successMessage.value =
    mode === 'create'
      ? `Metode ${saved.nama_metode} berhasil ditambahkan.`
      : `Metode ${saved.nama_metode} berhasil diperbarui.`

  await load()
}

const handleDeleted = async (message: string) => {
  successMessage.value = message
  await load()
}

const hasFilter = computed(() => Boolean(search.value) || statusFilter.value !== 'all')
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb page-title="Metode Pembayaran" />

      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
      >
        <!-- Header -->
        <div
          class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
              {{ canCreate ? 'Manajemen metode pembayaran' : 'Daftar metode pembayaran' }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{
                canCreate
                  ? 'Kelola metode pembayaran yang dapat dipilih saat transaksi.'
                  : 'Lihat metode pembayaran yang tersedia.'
              }}
            </p>
          </div>

          <Button v-if="canCreate" class="shrink-0" @click="openCreate">
            <PlusIcon class="size-5" />
            Tambah metode
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
                placeholder="Cari nama atau kode metode..."
                class="text-sm"
              />
            </InputGroup>
          </div>

          <div class="flex items-center gap-3">
            <Select v-model="statusFilter">
              <SelectTrigger class="w-full sm:w-40" aria-label="Filter status">
                <SelectValue placeholder="Semua status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua status</SelectItem>
                <SelectItem value="active">Aktif</SelectItem>
                <SelectItem value="inactive">Tidak aktif</SelectItem>
              </SelectContent>
            </Select>

            <Badge v-if="meta" color="gray" size="sm" class="shrink-0">
              {{ meta.total }} metode
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
          <div class="min-w-160">
            <Table>
              <TableHeader
                class="border-y border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900"
              >
                <TableRow>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Metode
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Kode
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Status
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
                <TableRow v-if="loading && items.length === 0">
                  <TableCell colspan="4" class="px-6 py-12 text-center">
                    <span
                      class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"
                    >
                      <LoaderCircleIcon class="size-5 animate-spin" />
                      Memuat data metode pembayaran...
                    </span>
                  </TableCell>
                </TableRow>

                <TableRow v-else-if="items.length === 0">
                  <TableCell colspan="4" class="px-6 py-12 text-center">
                    <span
                      class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500"
                    >
                      <DollarIcon class="size-6" />
                    </span>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{
                        hasFilter
                          ? 'Tidak ada metode pembayaran yang cocok.'
                          : 'Belum ada metode pembayaran.'
                      }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{
                        hasFilter
                          ? 'Ubah kata kunci atau filter untuk melihat data lain.'
                          : canCreate
                            ? 'Tambahkan metode pembayaran melalui tombol di atas.'
                            : 'Belum ada metode pembayaran untuk ditampilkan.'
                      }}
                    </p>
                  </TableCell>
                </TableRow>

                <template v-else>
                  <TableRow
                    v-for="item in items"
                    :key="item.id"
                    class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/2"
                  >
                    <TableCell class="px-5 py-4 lg:px-6">
                      <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                        {{ item.nama_metode }}
                      </p>
                      <p
                        v-if="item.deskripsi"
                        class="mt-0.5 line-clamp-1 text-xs text-gray-500 dark:text-gray-400"
                      >
                        {{ item.deskripsi }}
                      </p>
                    </TableCell>

                    <TableCell class="px-5 py-4 lg:px-6">
                      <code
                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300"
                      >
                        {{ item.kode }}
                      </code>
                    </TableCell>

                    <TableCell class="px-5 py-4 lg:px-6">
                      <Badge :color="item.is_active ? 'success' : 'gray'" size="sm">
                        {{ item.is_active ? 'Aktif' : 'Tidak aktif' }}
                      </Badge>
                    </TableCell>

                    <TableCell class="px-5 py-4 text-end lg:px-6">
                      <DropdownMenu v-if="canCreate || canUpdate || canDelete">
                        <DropdownMenuTrigger as-child>
                          <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-gray-500 dark:text-gray-400"
                            :aria-label="`Aksi untuk ${item.nama_metode}`"
                          >
                            <HorizontalDotsIcon class="size-5" />
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" class="w-48">
                          <DropdownMenuItem v-if="canUpdate" @select="openEdit(item)">
                            <PencilIcon class="size-5" />
                            Edit metode
                          </DropdownMenuItem>

                          <DropdownMenuSeparator v-if="canDelete" />

                          <DropdownMenuItem
                            v-if="canDelete"
                            variant="destructive"
                            @select="openDelete(item)"
                          >
                            <TrashBinIcon class="size-5" />
                            Hapus metode
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
              Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }} metode pembayaran
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

    <PaymentMethodFormDialog
      v-model:open="formOpen"
      :mode="formMode"
      :payment-method="editingItem"
      @saved="handleSaved"
    />

    <DeletePaymentMethodDialog
      v-model:open="deleteOpen"
      :payment-method="targetItem"
      @deleted="handleDeleted"
    />
  </AdminLayout>
</template>
