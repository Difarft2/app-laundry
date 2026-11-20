<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import DeleteOrderDialog from '@/components/main/orders/DeleteOrderDialog.vue'
import OrderFormDialog from '@/components/main/orders/OrderFormDialog.vue'
import UpdateOrderStatusDialog from '@/components/main/orders/UpdateOrderStatusDialog.vue'
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
import { ORDER_STATUS_OPTIONS, orderStatusColor, orderStatusLabel } from '@/constants/orders'
import {
  ArrowRightIcon,
  HorizontalDotsIcon,
  InboxIcon,
  LoaderCircleIcon,
  PencilIcon,
  PlusIcon,
  SearchIcon,
  TrashBinIcon,
  XIcon,
} from '@/icons/layout'
import { normalizeApiError } from '@/services/http'
import { ordersApi } from '@/services/orders'
import { useAuthStore } from '@/stores/auth'
import type { PaginationMeta } from '@/types/auth'
import type { Order, OrderStatus } from '@/types/order'
import { formatCurrency } from '@/utils/format'

const auth = useAuthStore()

const orders = ref<Order[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const search = ref('')
const statusFilter = ref<OrderStatus | 'all'>('all')
const page = ref(1)

const canCreate = computed(() => auth.hasPermission('orders.create'))
const canUpdate = computed(() => auth.hasPermission('orders.update'))
const canUpdateStatus = computed(() => auth.hasPermission('orders.update-status'))
const canDelete = computed(() => auth.hasPermission('orders.delete'))

const totalPages = computed(() => Math.max(1, meta.value?.last_page ?? 1))

const hasFilter = computed(() => Boolean(search.value) || statusFilter.value !== 'all')

const load = async (): Promise<void> => {
  loading.value = true
  loadError.value = null

  try {
    const result = await ordersApi.list({
      search: search.value.trim() || undefined,
      status: statusFilter.value === 'all' ? undefined : statusFilter.value,
      page: page.value,
    })

    orders.value = result.data
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

const statusOpen = ref(false)
const statusTarget = ref<Order | null>(null)

const deleteOpen = ref(false)
const deleteTarget = ref<Order | null>(null)

const openStatus = (order: Order) => {
  statusTarget.value = order
  statusOpen.value = true
}

const openDelete = (order: Order) => {
  deleteTarget.value = order
  deleteOpen.value = true
}

const handleSaved = async (message: string) => {
  successMessage.value = message
  page.value = 1
  await load()
}

const handleUpdated = async (message: string) => {
  successMessage.value = message
  await load()
}

const handleDeleted = async (message: string) => {
  successMessage.value = message
  await load()
}

const formatDateTime = (iso: string | null): string => {
  if (!iso) return '—'
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return iso

  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const remainingLabel = (order: Order): string =>
  order.sisa_pembayaran > 0
    ? `Sisa ${formatCurrency(order.sisa_pembayaran)}`
    : 'Lunas'
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb page-title="Pesanan" />

      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
      >
        <!-- Header -->
        <div
          class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Manajemen pesanan</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Kelola nota pesanan, pantau status, dan sisa pembayaran.
            </p>
          </div>

          <Button v-if="canCreate" class="shrink-0" @click="formOpen = true">
            <PlusIcon class="size-5" />
            Buat pesanan
          </Button>
        </div>

        <!-- Toolbar -->
        <div
          class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-6"
        >
          <div class="w-full lg:max-w-xs">
            <InputGroup class="border-gray-300 dark:border-gray-700">
              <InputGroupAddon align="inline-start" class="ps-3.5">
                <SearchIcon class="size-5 text-gray-500 dark:text-gray-400" />
              </InputGroupAddon>
              <InputGroupInput
                v-model="search"
                type="search"
                placeholder="Cari nota, nama, atau nomor HP..."
                class="text-sm"
              />
            </InputGroup>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <Select v-model="statusFilter">
              <SelectTrigger class="w-full sm:w-52" aria-label="Filter status pesanan">
                <SelectValue placeholder="Semua status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua status</SelectItem>
                <SelectItem
                  v-for="option in ORDER_STATUS_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Badge v-if="meta" color="gray" size="sm" class="shrink-0">
              {{ meta.total }} pesanan
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
            @click="successMessage = null"
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
          <button type="button" class="ms-2 font-medium underline" @click="load()">Coba lagi</button>
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
                    No. Nota
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Pelanggan
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Layanan
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Masuk
                  </TableCell>
                  <TableCell
                    isHeader
                    class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400"
                  >
                    Estimasi
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
                    Total
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
                <TableRow v-if="loading && orders.length === 0">
                  <TableCell colspan="8" class="px-6 py-12 text-center">
                    <span
                      class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"
                    >
                      <LoaderCircleIcon class="size-5 animate-spin" />
                      Memuat data pesanan...
                    </span>
                  </TableCell>
                </TableRow>

                <TableRow v-else-if="orders.length === 0">
                  <TableCell colspan="8" class="px-6 py-12 text-center">
                    <span
                      class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500"
                    >
                      <InboxIcon class="size-6" />
                    </span>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ hasFilter ? 'Tidak ada pesanan yang cocok.' : 'Belum ada pesanan.' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{
                        hasFilter
                          ? 'Ubah kata kunci atau filter untuk melihat data lain.'
                          : 'Buat pesanan baru melalui tombol di atas.'
                      }}
                    </p>
                  </TableCell>
                </TableRow>

                <template v-else>
                  <TableRow
                    v-for="order in orders"
                    :key="order.id_pesanan"
                    class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/2"
                  >
                    <TableCell class="px-5 py-4 lg:px-6">
                      <RouterLink
                        :to="`/orders/${order.id_pesanan}`"
                        class="text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400"
                      >
                        {{ order.kode_pesanan }}
                      </RouterLink>
                    </TableCell>

                    <TableCell class="px-5 py-4 lg:px-6">
                      <p class="text-sm text-gray-800 dark:text-white/90">
                        {{ order.pelanggan.nama }}
                      </p>
                      <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ order.pelanggan.no_hp }}
                      </p>
                    </TableCell>

                    <TableCell class="px-5 py-4 text-sm text-gray-500 lg:px-6 dark:text-gray-400">
                      {{ order.detail_pesanan?.length ?? 0 }} layanan
                    </TableCell>

                    <TableCell
                      class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 lg:px-6 dark:text-gray-400"
                    >
                      {{ formatDateTime(order.tanggal_masuk) }}
                    </TableCell>

                    <TableCell
                      class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 lg:px-6 dark:text-gray-400"
                    >
                      {{ formatDateTime(order.estimasi_selesai) }}
                    </TableCell>

                    <TableCell class="px-5 py-4 lg:px-6">
                      <Badge :color="orderStatusColor(order.status_pesanan)" size="sm">
                        {{ orderStatusLabel(order.status_pesanan) }}
                      </Badge>
                    </TableCell>

                    <TableCell class="px-5 py-4 text-end lg:px-6">
                      <p class="text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">
                        {{ formatCurrency(order.total_harga) }}
                      </p>
                      <p class="mt-0.5 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">
                        {{ remainingLabel(order) }}
                      </p>
                    </TableCell>

                    <TableCell class="px-5 py-4 text-end lg:px-6">
                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-gray-500 dark:text-gray-400"
                            :aria-label="`Aksi untuk ${order.kode_pesanan}`"
                          >
                            <HorizontalDotsIcon class="size-5" />
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" class="w-52">
                          <DropdownMenuItem :to="`/orders/${order.id_pesanan}`">
                            <ArrowRightIcon class="size-5" />
                            Lihat detail
                          </DropdownMenuItem>

                          <DropdownMenuItem v-if="canUpdateStatus" @select="openStatus(order)">
                            <PencilIcon class="size-5" />
                            Ubah status
                          </DropdownMenuItem>

                          <template v-if="canDelete">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem variant="destructive" @select="openDelete(order)">
                              <TrashBinIcon class="size-5" />
                              Hapus pesanan
                            </DropdownMenuItem>
                          </template>
                        </DropdownMenuContent>
                      </DropdownMenu>
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
              Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }} pesanan
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

    <OrderFormDialog v-model:open="formOpen" @saved="handleSaved" />

    <UpdateOrderStatusDialog
      v-model:open="statusOpen"
      :order="statusTarget"
      @updated="handleUpdated"
    />

    <DeleteOrderDialog v-model:open="deleteOpen" :order="deleteTarget" @deleted="handleDeleted" />
  </AdminLayout>
</template>