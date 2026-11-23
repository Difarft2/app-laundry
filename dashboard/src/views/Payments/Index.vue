<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import PaymentHistoryList from '@/components/main/payments/PaymentHistoryList.vue'
import { Input } from '@/components/ui/input'
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
import { InboxIcon, LoaderCircleIcon, SearchIcon, XIcon } from '@/icons/layout'
import { PAYMENT_STATUS_OPTIONS, PAYMENT_TYPE_OPTIONS } from '@/constants/payments'
import { fetchActivePaymentMethods, type PaymentMethodOption } from '@/services/dashboard'
import { normalizeApiError } from '@/services/http'
import { paymentsApi } from '@/services/payments'
import type { PaginationMeta } from '@/types/auth'
import type { Payment, PaymentStatus, PaymentType } from '@/types/payment'
import { formatCurrency } from '@/utils/format'

const payments = ref<Payment[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const methods = ref<PaymentMethodOption[]>([])

const search = ref('')
const statusFilter = ref<PaymentStatus | 'all'>('all')
const typeFilter = ref<PaymentType | 'all'>('all')
const methodFilter = ref('all')
const tanggalMulai = ref('')
const tanggalSelesai = ref('')
const page = ref(1)

const totalPages = computed(() => Math.max(1, meta.value?.last_page ?? 1))
const hasFilter = computed(
  () =>
    Boolean(search.value) ||
    statusFilter.value !== 'all' ||
    typeFilter.value !== 'all' ||
    methodFilter.value !== 'all' ||
    Boolean(tanggalMulai.value) ||
    Boolean(tanggalSelesai.value),
)

const totalDiterima = computed(() =>
  payments.value
    .filter((payment) => payment.status_pembayaran === 'berhasil')
    .reduce((total, payment) => total + Number(payment.jumlah_bayar), 0),
)
const totalMenunggu = computed(
  () => payments.value.filter((payment) => payment.status_pembayaran === 'menunggu').length,
)

const loadMethods = async () => {
  try {
    methods.value = await fetchActivePaymentMethods()
  } catch {
    // Filter metode tetap bisa dipakai walau daftar gagal dimuat.
    methods.value = []
  }
}

const load = async (): Promise<void> => {
  loading.value = true
  loadError.value = null

  try {
    const result = await paymentsApi.list({
      search: search.value.trim() || undefined,
      status_pembayaran: statusFilter.value === 'all' ? undefined : statusFilter.value,
      jenis_pembayaran: typeFilter.value === 'all' ? undefined : typeFilter.value,
      id_metode_pembayaran:
        methodFilter.value === 'all' ? undefined : Number(methodFilter.value),
      tanggal_mulai: tanggalMulai.value || undefined,
      tanggal_selesai: tanggalSelesai.value || undefined,
      page: page.value,
    })

    payments.value = result.data
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

watch(search, () => {
  page.value = 1
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => void load(), 350)
})

watch([statusFilter, typeFilter, methodFilter, tanggalMulai, tanggalSelesai], () => {
  page.value = 1
  void load()
})

const goToPage = (next: number) => {
  if (next < 1 || next > totalPages.value || next === page.value) return
  page.value = next
  void load()
}

const resetFilter = () => {
  search.value = ''
  statusFilter.value = 'all'
  typeFilter.value = 'all'
  methodFilter.value = 'all'
  tanggalMulai.value = ''
  tanggalSelesai.value = ''
  page.value = 1
  void load()
}

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

onMounted(() => {
  void loadMethods()
  void load()

  if (new URLSearchParams(window.location.search).has('saved')) {
    successMessage.value = 'Pembayaran berhasil dicatat.'
  }
})

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
})
</script>

<template>
  <AdminLayout>
    <div class="space-y-4">
      <PageBreadcrumb page-title="Riwayat Pembayaran" />

      <div
        v-if="successMessage"
        role="status"
        class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
      >
        {{ successMessage }}
        <button type="button" class="ms-2 font-medium underline" @click="successMessage = null">
          Tutup
        </button>
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 dark:border-gray-800 dark:bg-white/3">
          <p class="text-xs text-gray-500 dark:text-gray-400">Diterima (halaman ini)</p>
          <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
            {{ formatCurrency(totalDiterima) }}
          </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 dark:border-gray-800 dark:bg-white/3">
          <p class="text-xs text-gray-500 dark:text-gray-400">Transaksi berhasil</p>
          <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
            {{ payments.filter((item) => item.status_pembayaran === 'berhasil').length }}
          </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 dark:border-gray-800 dark:bg-white/3">
          <p class="text-xs text-gray-500 dark:text-gray-400">Menunggu konfirmasi</p>
          <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">{{ totalMenunggu }}</p>
        </div>
      </div>

      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
      >
        <!-- Toolbar -->
        <div class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between lg:px-6">
          <div class="w-full lg:max-w-xs">
            <InputGroup class="border-gray-300 dark:border-gray-700">
              <InputGroupAddon align="inline-start" class="ps-3.5">
                <SearchIcon class="size-5 text-gray-500 dark:text-gray-400" />
              </InputGroupAddon>
              <InputGroupInput
                v-model="search"
                placeholder="Cari kode pembayaran / nota / pelanggan"
                aria-label="Cari pembayaran"
              />
            </InputGroup>
          </div>

          <div class="flex flex-wrap gap-2">
            <Select v-model="statusFilter">
              <SelectTrigger class="w-full min-w-40 sm:w-auto" aria-label="Filter status">
                <SelectValue placeholder="Semua status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua status</SelectItem>
                <SelectItem
                  v-for="option in PAYMENT_STATUS_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="typeFilter">
              <SelectTrigger class="w-full min-w-36 sm:w-auto" aria-label="Filter jenis">
                <SelectValue placeholder="Semua jenis" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua jenis</SelectItem>
                <SelectItem
                  v-for="option in PAYMENT_TYPE_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="methodFilter">
              <SelectTrigger class="w-full min-w-40 sm:w-auto" aria-label="Filter metode">
                <SelectValue placeholder="Semua metode" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua metode</SelectItem>
                <SelectItem v-for="method in methods" :key="method.id" :value="String(method.id)">
                  {{ method.nama_metode }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Input v-model="tanggalMulai" type="date" aria-label="Tanggal mulai" class="w-full sm:w-40" />
            <Input v-model="tanggalSelesai" type="date" aria-label="Tanggal selesai" class="w-full sm:w-40" />

            <button
              v-if="hasFilter"
              type="button"
              class="inline-flex items-center gap-1 rounded-lg px-2 text-xs font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"
              @click="resetFilter"
            >
              <XIcon class="size-4" />
              Reset
            </button>
          </div>
        </div>

        <div
          v-if="loadError"
          role="alert"
          class="mx-5 mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400 lg:mx-6"
        >
          {{ loadError }}
          <button type="button" class="ms-2 font-medium underline" @click="load">Coba lagi</button>
        </div>

        <div v-if="loading" class="flex items-center justify-center gap-2 px-6 py-16 text-sm text-gray-500 dark:text-gray-400">
          <LoaderCircleIcon class="size-5 animate-spin" />
          Memuat riwayat pembayaran...
        </div>

        <div v-else class="border-t border-gray-100 dark:border-gray-800">
          <PaymentHistoryList v-if="payments.length > 0" :payments="payments" show-order />
          <div v-else class="px-6 py-16 text-center">
            <InboxIcon class="mx-auto size-10 text-gray-300 dark:text-gray-600" />
            <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ hasFilter ? 'Tidak ada pembayaran yang cocok.' : 'Belum ada pembayaran.' }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Pembayaran dari pesanan yang dibayar nanti akan muncul di sini.
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div
          class="flex flex-col gap-4 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800"
        >
          <p class="text-xs text-gray-500 dark:text-gray-400">
            <template v-if="meta && meta.total > 0">
              Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }} pembayaran
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

      <p class="text-xs text-gray-500 dark:text-gray-400">
        Catat pembayaran baru melalui menu Pesanan → pilih nota → <RouterLink to="/orders" class="font-medium text-brand-500 hover:underline">Buka daftar pesanan</RouterLink>.
      </p>
    </div>
  </AdminLayout>
</template>
