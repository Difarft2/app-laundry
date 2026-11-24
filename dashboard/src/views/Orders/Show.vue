<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import PaymentHistoryList from '@/components/main/payments/PaymentHistoryList.vue'
import RecordPaymentDialog from '@/components/main/payments/RecordPaymentDialog.vue'
import CreateDeliveryDialog from '@/components/main/orders/CreateDeliveryDialog.vue'
import DeleteOrderDialog from '@/components/main/orders/DeleteOrderDialog.vue'
import UpdateOrderStatusDialog from '@/components/main/orders/UpdateOrderStatusDialog.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  ArrowLeftIcon,
  CallIcon,
  DollarIcon,
  LoaderCircleIcon,
  PencilIcon,
  PrinterIcon,
  TrashBinIcon,
  TruckDeliveryIcon,
} from '@/icons/layout'
import { orderStatusColor, orderStatusLabel } from '@/constants/orders'
import { normalizeApiError } from '@/services/http'
import { ordersApi } from '@/services/orders'
import { useAuthStore } from '@/stores/auth'
import { hasActiveDelivery } from '@/utils/order-delivery'
import { printOrderReceipt } from '@/utils/order-receipt'
import type { Order } from '@/types/order'
import { formatCurrency } from '@/utils/format'
import { whatsappLink } from '@/utils/whatsapp'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const order = ref<Order | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const statusOpen = ref(false)
const deleteOpen = ref(false)
const paymentOpen = ref(false)
const deliveryOpen = ref(false)

const orderId = computed(() => Number(route.params.id))

const canUpdateStatus = computed(() => auth.hasPermission('orders.update-status'))
const canDelete = computed(() => auth.hasPermission('orders.delete'))
const canRecordPayment = computed(() => auth.hasPermission('payments.create'))
const canAntar = computed(() => {
  const current = order.value

  return Boolean(
    current
    && canUpdateStatus.value
    && current.status_pesanan === 'siap_diambil'
    && !hasActiveDelivery(current),
  )
})

const processStatusLabels: Record<string, string> = {
  menunggu: 'Menunggu pengerjaan',
  pencucian: 'Sedang dicuci',
  pengeringan: 'Sedang dikeringkan',
  finishing: 'Finishing',
  selesai: 'Proses selesai',
}

const load = async (): Promise<void> => {
  if (!Number.isInteger(orderId.value) || orderId.value <= 0) {
    loadError.value = 'Nomor pesanan tidak valid.'
    return
  }

  loading.value = true
  loadError.value = null

  try {
    order.value = await ordersApi.show(orderId.value)
  } catch (error) {
    loadError.value = normalizeApiError(error).message
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})

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

const quantityLabel = (jumlah: number, satuan: string): string =>
  `${jumlah} ${satuan === 'kg' ? 'kg' : 'pcs'}`

const handleUpdated = async (message: string) => {
  successMessage.value = message
  await load()
}

const handleDeleted = async (message: string) => {
  await router.push({ name: 'Orders', query: { deleted: message } })
}

const handlePaymentSaved = async (message: string) => {
  successMessage.value = message
  await load()
}

const customerWaLink = computed(() =>
  whatsappLink(
    order.value?.pelanggan?.no_hp,
    `Halo, saya dari Pemuda Laundry. Saya ingin mengonfirmasi pesanan ${order.value?.kode_pesanan ?? ''} Anda.`
  ),
)

const printOrder = async () => {
  if (!order.value) return

  loadError.value = null

  try {
    await printOrderReceipt(order.value)
  } catch (error) {
    loadError.value = normalizeApiError(error).message
  }
}

const handleDeliveryCreated = async (message: string) => {
  successMessage.value = message
  await load()
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <PageBreadcrumb page-title="Detail Pesanan" />
        <Button variant="outline" size="sm" @click="router.push({ name: 'Orders' })">
          <ArrowLeftIcon class="size-4" />
          Kembali
        </Button>
      </div>

      <div
        v-if="successMessage"
        role="status"
        class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="loading"
        class="flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white py-16 text-sm text-gray-500 dark:border-gray-800 dark:bg-white/3 dark:text-gray-400"
      >
        <LoaderCircleIcon class="size-5 animate-spin" />
        Memuat detail pesanan...
      </div>

      <div
        v-else-if="loadError"
        role="alert"
        class="rounded-2xl border border-error-200 bg-error-50 px-5 py-6 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
      >
        {{ loadError }}
        <button type="button" class="ms-2 font-medium underline" @click="load()">Coba lagi</button>
      </div>

      <template v-else-if="order">
        <!-- Ringkasan nota -->
        <div
          class="rounded-2xl border border-gray-200 bg-white px-5 py-5 lg:px-6 dark:border-gray-800 dark:bg-white/3"
        >
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Nomor nota</p>
              <h3 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ order.kode_pesanan }}
              </h3>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <Badge :color="orderStatusColor(order.status_pesanan)" size="sm">
                  {{ orderStatusLabel(order.status_pesanan) }}
                </Badge>
                <Badge :color="order.sisa_pembayaran > 0 ? 'error' : 'success'" size="sm">
                  {{ order.sisa_pembayaran > 0 ? 'Belum lunas' : 'Lunas' }}
                </Badge>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <Button v-if="canAntar" size="sm" @click="deliveryOpen = true">
                <TruckDeliveryIcon class="size-4" />
                Antar
              </Button>
              <Button variant="outline" size="sm" @click="printOrder">
                <PrinterIcon class="size-4" />
                Cetak struk
              </Button>
              <Button v-if="customerWaLink" as-child variant="outline" size="sm">
                <a :href="customerWaLink" target="_blank" rel="noopener">
                  <CallIcon class="size-4" />
                  Hubungi pelanggan
                </a>
              </Button>
              <Button
                v-if="canRecordPayment && order.sisa_pembayaran > 0 && order.status_pesanan !== 'dibatalkan'"
                size="sm"
                @click="paymentOpen = true"
              >
                <DollarIcon class="size-4" />
                Catat pembayaran
              </Button>
              <Button v-if="canUpdateStatus" variant="outline" size="sm" @click="statusOpen = true">
                <PencilIcon class="size-4" />
                Ubah status
              </Button>
              <Button v-if="canDelete" variant="outline" size="sm" @click="deleteOpen = true">
                <TrashBinIcon class="size-4" />
                Hapus
              </Button>
            </div>
          </div>

          <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Pelanggan</dt>
              <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
                {{ order.pelanggan.nama }}
              </dd>
              <dd class="text-xs text-gray-500 dark:text-gray-400">{{ order.pelanggan.no_hp }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Alamat</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ order.pelanggan.alamat || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Tanggal masuk</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ formatDateTime(order.tanggal_masuk) }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Estimasi selesai</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ formatDateTime(order.estimasi_selesai) }}
              </dd>
            </div>
          </dl>

          <p
            v-if="order.catatan"
            class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300"
          >
            <span class="font-medium">Catatan:</span> {{ order.catatan }}
          </p>
        </div>

        <!-- Rincian biaya + pembayaran -->
        <div class="grid gap-4 lg:grid-cols-2">
          <div
            class="rounded-2xl border border-gray-200 bg-white px-5 py-5 lg:px-6 dark:border-gray-800 dark:bg-white/3"
          >
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Rincian biaya</h4>
            <dl class="mt-4 space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ formatCurrency(order.subtotal) }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Diskon</dt>
                <dd class="text-gray-800 dark:text-white/90">- {{ formatCurrency(order.diskon) }}</dd>
              </div>
              <div
                class="flex items-center justify-between border-t border-gray-100 pt-2 dark:border-gray-800"
              >
                <dt class="font-medium text-gray-800 dark:text-white/90">Total</dt>
                <dd class="font-semibold text-gray-800 dark:text-white/90">
                  {{ formatCurrency(order.total_harga) }}
                </dd>
              </div>
            </dl>
          </div>

          <div
            class="rounded-2xl border border-gray-200 bg-white px-5 py-5 lg:px-6 dark:border-gray-800 dark:bg-white/3"
          >
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Pembayaran</h4>
            <dl class="mt-4 space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Sudah dibayar</dt>
                <dd class="text-gray-800 dark:text-white/90">
                  {{ formatCurrency(order.total_terbayar) }}
                </dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Sisa tagihan</dt>
                <dd
                  class="font-medium"
                  :class="order.sisa_pembayaran > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-500'"
                >
                  {{ formatCurrency(order.sisa_pembayaran) }}
                </dd>
              </div>
            </dl>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
              Status pembayaran terpisah dari status pesanan dan proses laundry.
            </p>
          </div>
        </div>

        <!-- Riwayat pembayaran -->
        <div
          class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
        >
          <div
            class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-4 lg:px-6 dark:border-gray-800"
          >
            <div>
              <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Riwayat pembayaran</h4>
              <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Catatan pembayaran untuk nota ini, termasuk DP dan pelunasan.
              </p>
            </div>
            <RouterLink
              to="/payments"
              class="text-xs font-medium text-brand-500 hover:underline dark:text-brand-400"
            >
              Lihat semua pembayaran
            </RouterLink>
          </div>

          <PaymentHistoryList :payments="order.pembayaran ?? []" />
        </div>

        <!-- Detail layanan -->
        <div
          class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
        >
          <div class="border-b border-gray-100 px-5 py-4 lg:px-6 dark:border-gray-800">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Detail layanan</h4>
          </div>

          <div class="max-w-full overflow-x-auto">
            <div class="min-w-150">
              <table class="w-full">
                <thead class="border-b border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                  <tr>
                    <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                      Layanan
                    </th>
                    <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                      Jumlah
                    </th>
                    <th class="px-5 py-3 text-end text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                      Harga satuan
                    </th>
                    <th class="px-5 py-3 text-end text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                      Subtotal
                    </th>
                    <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                      Proses laundry
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <tr v-if="!order.detail_pesanan || order.detail_pesanan.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                      Tidak ada detail layanan pada pesanan ini.
                    </td>
                  </tr>
                  <tr v-for="detail in order.detail_pesanan" :key="detail.id_detail_pesanan">
                    <td class="px-5 py-4 lg:px-6">
                      <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                        {{ detail.nama_layanan }}
                      </p>
                      <p v-if="detail.catatan" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ detail.catatan }}
                      </p>
                    </td>
                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 lg:px-6 dark:text-gray-400">
                      {{ quantityLabel(detail.jumlah, detail.satuan) }}
                    </td>
                    <td class="px-5 py-4 text-end text-sm whitespace-nowrap text-gray-500 lg:px-6 dark:text-gray-400">
                      {{ formatCurrency(detail.harga_satuan) }}
                    </td>
                    <td class="px-5 py-4 text-end text-sm font-medium whitespace-nowrap text-gray-800 lg:px-6 dark:text-white/90">
                      {{ formatCurrency(detail.subtotal) }}
                    </td>
                    <td class="px-5 py-4 lg:px-6">
                      <Badge v-if="detail.proses_laundry" color="info" size="sm">
                        {{
                          processStatusLabels[detail.proses_laundry.status_proses] ??
                          detail.proses_laundry.status_proses
                        }}
                      </Badge>
                      <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Riwayat status -->
        <div
          class="rounded-2xl border border-gray-200 bg-white px-5 py-5 lg:px-6 dark:border-gray-800 dark:bg-white/3"
        >
          <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Riwayat status pesanan</h4>

          <ol
            v-if="order.riwayat_status && order.riwayat_status.length > 0"
            class="mt-4 space-y-4 border-s border-gray-200 ps-4 dark:border-gray-800"
          >
            <li v-for="(riwayat, index) in order.riwayat_status" :key="index" class="relative">
              <span
                class="absolute -start-[21px] top-1.5 size-2.5 rounded-full bg-brand-500"
                aria-hidden="true"
              ></span>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                {{ orderStatusLabel(riwayat.status_selanjutnya) }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatDateTime(riwayat.tanggal_perubahan) }}
                <template v-if="riwayat.user"> · {{ riwayat.user }}</template>
              </p>
              <p v-if="riwayat.keterangan" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ riwayat.keterangan }}
              </p>
            </li>
          </ol>

          <p v-else class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            Belum ada riwayat perubahan status.
          </p>
        </div>
      </template>
    </div>

    <UpdateOrderStatusDialog
      v-model:open="statusOpen"
      :order="order"
      @updated="handleUpdated"
    />

    <DeleteOrderDialog v-model:open="deleteOpen" :order="order" @deleted="handleDeleted" />

    <CreateDeliveryDialog
      v-model:open="deliveryOpen"
      :order="order"
      @created="handleDeliveryCreated"
    />

    <RecordPaymentDialog
      v-model:open="paymentOpen"
      :order="order"
      @saved="handlePaymentSaved"
    />
  </AdminLayout>
</template>