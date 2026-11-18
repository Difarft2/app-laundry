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
import { PAYMENT_METHOD_STATUS_OPTIONS } from '@/constants/payment-methods'
import {
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

const paymentMethods = ref<PaymentMethod[]>([])
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

const load = async (): Promise<void> => {
  loading.value = true
  loadError.value = null

  try {
    const result = await paymentMethodsApi.list({
      search: search.value.trim() || undefined,
      is_active: statusFilter.value === 'all' ? undefined : statusFilter.value === 'active',
      page: page.value,
    })

    paymentMethods.value = result.data
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
const selectedPaymentMethod = ref<PaymentMethod | null>(null)

const openCreate = () => {
  formMode.value = 'create'
  selectedPaymentMethod.value = null
  formOpen.value = true
}

const openEdit = (pm: PaymentMethod) => {
  formMode.value = 'edit'
  selectedPaymentMethod.value = pm
  formOpen.value = true
}

const onSaved = (pm: PaymentMethod, mode: PaymentMethodFormMode) => {
  successMessage.value = mode === 'create' ? 'Metode pembayaran berhasil ditambahkan.' : 'Data berhasil diperbarui.'
  void load()
}

const deleteOpen = ref(false)
const pmToDelete = ref<PaymentMethod | null>(null)

const confirmDelete = (pm: PaymentMethod) => {
  pmToDelete.value = pm
  deleteOpen.value = true
}

const onDeleted = (msg: string) => {
  successMessage.value = msg
  void load()
}

const toggleStatus = async (pm: PaymentMethod) => {
  try {
    const originalStatus = pm.is_active
    pm.is_active = !originalStatus
    await paymentMethodsApi.updateStatus(pm.id, !originalStatus)
    successMessage.value = `Metode pembayaran ${pm.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`
  } catch (error) {
    pm.is_active = !pm.is_active
    successMessage.value = normalizeApiError(error).message
  }
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-screen-2xl p-4 md:p-6">
      <PageBreadcrumb page-title="Metode Pembayaran" />

      <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="p-4 md:p-6">
          <div
            v-if="successMessage"
            role="alert"
            class="mb-6 flex items-center justify-between gap-4 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
          >
            <span>{{ successMessage }}</span>
            <button class="hover:opacity-70" @click="successMessage = null">
              <XIcon class="size-4" />
            </button>
          </div>

          <div class="mb-6 flex flex-col items-center justify-between gap-4 sm:flex-row">
            <div class="flex w-full flex-1 flex-col gap-4 sm:flex-row sm:items-center">
              <InputGroup class="max-w-xs">
                <InputGroupAddon>
                  <SearchIcon class="size-4 text-gray-400" />
                </InputGroupAddon>
                <InputGroupInput v-model="search" placeholder="Cari nama atau kode..." />
              </InputGroup>

              <Select v-model="statusFilter">
                <SelectTrigger class="w-full sm:w-44">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="opt in PAYMENT_METHOD_STATUS_OPTIONS"
                    :key="opt.value"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button v-if="canCreate" class="w-full gap-2 sm:w-auto" @click="openCreate">
              <PlusIcon class="size-4" />
              Tambah Metode
            </Button>
          </div>

          <div class="relative overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-800">
            <Table>
              <TableHeader>
                <TableRow class="bg-gray-50 dark:bg-gray-800/50">
                  <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500">Metode</th>
                  <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                  <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                  <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-if="loading && paymentMethods.length === 0">
                  <TableCell colspan="4" class="py-12 text-center">
                    <LoaderCircleIcon class="mx-auto size-8 animate-spin text-primary-600" />
                    <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
                  </TableCell>
                </TableRow>

                <TableRow v-else-if="loadError">
                  <TableCell colspan="4" class="py-12 text-center">
                    <p class="text-sm text-error-600">{{ loadError }}</p>
                    <Button variant="outline" size="sm" class="mt-3" @click="load">Coba Lagi</Button>
                  </TableCell>
                </TableRow>

                <TableRow v-else-if="paymentMethods.length === 0">
                  <TableCell colspan="4" class="py-12 text-center text-gray-500">
                    Tidak ada metode pembayaran ditemukan.
                  </TableCell>
                </TableRow>

                <TableRow
                  v-for="pm in paymentMethods"
                  :key="pm.id"
                  class="group border-b border-gray-100 last:border-0 dark:border-gray-800"
                >
                  <TableCell class="px-4 py-3">
                    <div class="flex flex-col">
                      <span class="font-medium text-gray-900 dark:text-white/90">{{ pm.nama_metode }}</span>
                      <span class="text-xs text-gray-500">{{ pm.deskripsi || 'Tidak ada deskripsi' }}</span>
                    </div>
                  </TableCell>
                  <TableCell class="px-4 py-3">
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                      {{ pm.kode }}
                    </code>
                  </TableCell>
                  <TableCell class="px-4 py-3">
                    <Badge :variant="pm.is_active ? 'success' : 'gray'">
                      {{ pm.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="px-4 py-3 text-end">
                    <DropdownMenu v-if="canUpdate || canDelete">
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" class="size-8">
                          <HorizontalDotsIcon class="size-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" class="w-40">
                        <DropdownMenuItem v-if="canUpdate" class="gap-2" @click="openEdit(pm)">
                          <PencilIcon class="size-3.5" />
                          Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="canUpdate" class="gap-2" @click="toggleStatus(pm)">
                          <component :is="pm.is_active ? XIcon : PlusIcon" class="size-3.5" />
                          {{ pm.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator v-if="canUpdate && canDelete" />
                        <DropdownMenuItem v-if="canDelete" class="gap-2 text-error-600 focus:text-error-600" @click="confirmDelete(pm)">
                          <TrashBinIcon class="size-3.5" />
                          Hapus
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <div v-if="meta && totalPages > 1" class="mt-6 flex flex-col items-center justify-between gap-4 sm:flex-row">
            <p class="text-sm text-gray-500">
              Menampilkan {{ meta.from }} sampai {{ meta.to }} dari {{ meta.total }} entri
            </p>
            <Pagination :total="meta.total" :sibling-count="1" :default-page="page">
              <PaginationContent>
                <PaginationItem>
                  <PaginationPrevious :disabled="page === 1" @click="goToPage(page - 1)" />
                </PaginationItem>
                <PaginationItem v-for="(p, i) in paginationPages" :key="i">
                  <template v-if="typeof p === 'number'">
                    <PaginationLink :is-active="p === page" @click="goToPage(p)">{{ p }}</PaginationLink>
                  </template>
                  <template v-else>
                    <PaginationEllipsis />
                  </template>
                </PaginationItem>
                <PaginationItem>
                  <PaginationNext :disabled="page === totalPages" @click="goToPage(page + 1)" />
                </PaginationItem>
              </PaginationContent>
            </Pagination>
          </div>
        </div>
      </div>
    </div>

    <PaymentMethodFormDialog
      v-model:open="formOpen"
      :mode="formMode"
      :payment-method="selectedPaymentMethod"
      @saved="onSaved"
    />

    <DeletePaymentMethodDialog
      v-model:open="deleteOpen"
      :payment-method="pmToDelete"
      @deleted="onDeleted"
    />
  </AdminLayout>
</template>
