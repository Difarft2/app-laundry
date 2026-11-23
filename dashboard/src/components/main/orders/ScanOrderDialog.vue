<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Field, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import {
  CameraIcon,
  CheckCircleIcon,
  LoaderCircleIcon,
  SearchIcon,
} from '@/icons/layout'
import {
  LAUNDRY_STATUS_FLOW,
  nextLaundryStatus,
  orderStatusColor,
  orderStatusLabel,
} from '@/constants/orders'
import { normalizeApiError } from '@/services/http'
import { ordersApi } from '@/services/orders'
import type { Order, OrderStatus } from '@/types/order'
import { formatCurrency, formatDateTime } from '@/utils/format'

const props = defineProps<{ open: boolean }>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'status-updated', message: string): void
}>()

const SCANNER_ID = 'order-qr-reader'

const order = ref<Order | null>(null)
const manualCode = ref('')
const lookupLoading = ref(false)
const lookupError = ref<string | null>(null)
const scannerError = ref<string | null>(null)
const isScanning = ref(false)

const status = ref<OrderStatus>('menunggu')
const keterangan = ref('')
const updating = ref(false)
const updateError = ref<string | null>(null)
const updateSuccess = ref<string | null>(null)

let scanner: Html5Qrcode | null = null

const statusOptions = LAUNDRY_STATUS_FLOW.map((value) => ({
  value,
  label: orderStatusLabel(value),
}))

const stopScanner = async () => {
  if (!scanner || !isScanning.value) return

  try {
    await scanner.stop()
  } catch {
    // Scanner sudah berhenti saat kamera ditutup browser.
  }

  isScanning.value = false
}

const extractCode = (text: string): string => {
  const value = text.trim()
  const match = value.match(/ORD-[0-9A-Za-z-]+/)
  return (match?.[0] ?? value).toUpperCase()
}

const lookup = async (kode: string) => {
  const clean = extractCode(kode)
  if (!clean) return

  lookupLoading.value = true
  lookupError.value = null
  updateError.value = null
  updateSuccess.value = null

  try {
    order.value = await ordersApi.lookupByCode(clean)
    manualCode.value = clean
    status.value = nextLaundryStatus(order.value.status_pesanan) ?? order.value.status_pesanan
    keterangan.value = ''
    await stopScanner()
  } catch (error) {
    order.value = null
    lookupError.value = normalizeApiError(error).message
  } finally {
    lookupLoading.value = false
  }
}

const onScanSuccess = ( decodedText: string) => {
  if (lookupLoading.value) return
  void lookup(decodedText)
}

const startScanner = async () => {
  scannerError.value = null

  if (scanner) {
    try {
      await scanner.clear()
    } catch {
      // Element scanner mungkin sudah dihapus saat dialog ditutup.
    }
    scanner = null
  }

  await nextTick()

  try {
    scanner = new Html5Qrcode(SCANNER_ID, {
      formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
      verbose: false,
    })

    await scanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 240, height: 240 } },
      onScanSuccess,
      () => {
        // Frame belum terbaca; tidak perlu ditampilkan sebagai error.
      },
    )

    isScanning.value = true
  } catch {
    scannerError.value =
      'Kamera tidak dapat dibuka. Pastikan izin kamera diberikan, lalu gunakan input kode pesanan.'
    isScanning.value = false
  }
}

const submitStatus = async () => {
  if (!order.value || updating.value) return

  if (status.value === order.value.status_pesanan) {
    updateError.value = 'Pilih status yang berbeda dari status saat ini.'
    return
  }

  updating.value = true
  updateError.value = null
  updateSuccess.value = null

  try {
    await ordersApi.updateStatus(order.value.id_pesanan, {
      status_pesanan: status.value,
      keterangan: keterangan.value.trim() || null,
    })

    order.value = await ordersApi.show(order.value.id_pesanan)
    status.value = nextLaundryStatus(order.value.status_pesanan) ?? order.value.status_pesanan
    keterangan.value = ''
    updateSuccess.value = `Status ${order.value.kode_pesanan} menjadi ${orderStatusLabel(order.value.status_pesanan)}.`
    emit('status-updated', updateSuccess.value)
  } catch (error) {
    updateError.value = normalizeApiError(error).message
  } finally {
    updating.value = false
  }
}

const close = () => emit('update:open', false)

watch(
  () => props.open,
  async (open) => {
    if (open) {
      await startScanner()
      return
    }

    await stopScanner()
    order.value = null
    manualCode.value = ''
    lookupError.value = null
    scannerError.value = null
    updateError.value = null
    updateSuccess.value = null
  },
)

onBeforeUnmount(() => {
  void stopScanner()
})
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-[calc(100%-2rem)] max-h-[90vh] overflow-y-auto sm:max-w-2xl">
      <DialogHeader class="gap-2 pe-10 text-start">
        <DialogTitle class="text-xl font-semibold text-gray-800 dark:text-white/90">
          Scan QR pesanan
        </DialogTitle>
        <DialogDescription class="text-sm text-gray-500 dark:text-gray-400">
          Arahkan kamera ke QR pada kartu pesanan, atau masukkan kode pesanan secara manual.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <div
          v-show="!order"
          id="order-qr-reader"
          class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800"
        />

        <div
          v-if="scannerError"
          role="alert"
          class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-400"
        >
          {{ scannerError }}
        </div>

        <div v-if="isScanning && !order" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
          <CameraIcon class="size-4 animate-pulse" />
          Kamera aktif, menunggu QR pesanan...
        </div>

        <div v-if="!order" class="flex flex-col gap-2 sm:flex-row">
          <Input
            v-model="manualCode"
            placeholder="Kode pesanan, contoh: ORD-20261123-XXXXXXXX"
            @keyup.enter="lookup(manualCode)"
          />
          <Button class="shrink-0" :disabled="lookupLoading || !manualCode.trim()" @click="lookup(manualCode)">
            <LoaderCircleIcon v-if="lookupLoading" class="size-4 animate-spin" />
            <SearchIcon v-else class="size-4" />
            Cari
          </Button>
        </div>

        <div
          v-if="lookupError"
          role="alert"
          class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
        >
          {{ lookupError }}
        </div>

        <template v-if="order">
          <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">No. pesanan</p>
                <p class="font-semibold text-gray-800 dark:text-white/90">{{ order.kode_pesanan }}</p>
              </div>
              <Badge :color="orderStatusColor(order.status_pesanan)" size="sm">
                {{ orderStatusLabel(order.status_pesanan) }}
              </Badge>
            </div>

            <dl class="mt-3 grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">Nama pelanggan</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">{{ order.pelanggan.nama }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">No. HP</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">{{ order.pelanggan.no_hp }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">Tanggal masuk</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">
                  {{ formatDateTime(order.tanggal_masuk) }}
                </dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">Estimasi selesai</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">
                  {{ formatDateTime(order.estimasi_selesai) }}
                </dd>
              </div>
            </dl>

            <div v-if="order.detail_pesanan?.length" class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-800">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Layanan
              </p>
              <div
                v-for="detail in order.detail_pesanan"
                :key="detail.id_detail_pesanan"
                class="flex items-center justify-between gap-3 py-0.5 text-sm"
              >
                <span class="text-gray-700 dark:text-gray-200">
                  {{ detail.nama_layanan }} &times; {{ detail.jumlah }} {{ detail.satuan }}
                </span>
                <span class="font-medium text-gray-800 dark:text-white/90">
                  {{ formatCurrency(detail.subtotal) }}
                </span>
              </div>
              <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 text-sm dark:border-gray-800">
                <span class="font-semibold text-gray-800 dark:text-white/90">Total</span>
                <span class="font-semibold text-brand-600 dark:text-brand-400">
                  {{ formatCurrency(order.total_harga) }}
                </span>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Ubah status</p>

            <div
              v-if="updateSuccess"
              role="status"
              class="mb-3 flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400"
            >
              <CheckCircleIcon class="size-4" />
              {{ updateSuccess }}
            </div>
            <div
              v-if="updateError"
              role="alert"
              class="mb-3 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
            >
              {{ updateError }}
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <Field class="gap-1.5">
                <FieldLabel for="scan-status">Status baru <span class="text-red-500">*</span></FieldLabel>
                <Select v-model="status" :disabled="updating || order.status_pesanan === 'selesai'">
                  <SelectTrigger id="scan-status" class="w-full" aria-label="Status baru">
                    <SelectValue placeholder="Pilih status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in statusOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </Field>

              <Field class="gap-1.5">
                <FieldLabel for="scan-note">Keterangan</FieldLabel>
                <Textarea
                  id="scan-note"
                  v-model="keterangan"
                  rows="2"
                  placeholder="Opsional, dicatat pada riwayat"
                  :disabled="updating || order.status_pesanan === 'selesai'"
                />
              </Field>
            </div>

            <Button
              class="mt-3"
              :disabled="updating || order.status_pesanan === 'selesai'"
              @click="submitStatus"
            >
              <LoaderCircleIcon v-if="updating" class="size-4 animate-spin" />
              <CheckCircleIcon v-else class="size-4" />
              {{ updating ? 'Menyimpan...' : 'Simpan status' }}
            </Button>
          </div>

          <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
            <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Riwayat status</p>
            <div v-if="order.riwayat_status?.length" class="space-y-3">
              <div
                v-for="(riwayat, index) in [...order.riwayat_status].reverse()"
                :key="`${riwayat.tanggal_perubahan}-${index}`"
                class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2 text-sm last:border-0 last:pb-0 dark:border-gray-800"
              >
                <div class="text-gray-700 dark:text-gray-200">
                  <span class="text-gray-500 dark:text-gray-400">
                    {{ riwayat.status_sebelumnya ? orderStatusLabel(riwayat.status_sebelumnya) : 'Pesanan dibuat' }}
                  </span>
                  <span class="mx-2 text-gray-400">&rarr;</span>
                  <span class="font-medium">{{ orderStatusLabel(riwayat.status_selanjutnya) }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDateTime(riwayat.tanggal_perubahan) }}
                  <span v-if="riwayat.user"> &middot; {{ riwayat.user }}</span>
                </div>
                <p v-if="riwayat.keterangan" class="w-full text-xs text-gray-500 dark:text-gray-400">
                  {{ riwayat.keterangan }}
                </p>
              </div>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat perubahan status.</p>
          </div>

          <div class="flex justify-end">
            <Button variant="outline" @click="close">Tutup</Button>
          </div>
        </template>
      </div>
    </DialogContent>
  </Dialog>
</template>
