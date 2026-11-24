<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AssignCourierDialog from '@/components/main/courier/AssignCourierDialog.vue'
import UpdateDeliveryStatusDialog from '@/components/main/courier/UpdateDeliveryStatusDialog.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ArrowLeftIcon, CheckIcon, LoaderCircleIcon, PencilIcon } from '@/icons/layout'
import { deliveryStatusMeta } from '@/constants/delivery'
import { deliveryApi } from '@/services/delivery'
import { normalizeApiError } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import type { DeliveryTask } from '@/types/delivery'
import { formatCurrency, formatDateTime } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const task = ref<DeliveryTask | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const assignOpen = ref(false)
const statusOpen = ref(false)

const taskId = computed(() => Number(route.params.id))

const canAssign = computed(() => auth.hasPermission('delivery.assign'))
const canUpdateStatus = computed(() => auth.hasPermission('delivery.update'))
const isCourier = computed(() => auth.hasRole('kurir'))
const canAccept = computed(() => isCourier.value && canUpdateStatus.value)
const isAssignedToMe = computed(
  () => isCourier.value && task.value?.kurir?.id === auth.user?.id,
)

const canAssignNow = computed(
  () => canAssign.value && ['menunggu', 'ditugaskan'].includes(task.value?.status_antar_jemput ?? ''),
)

const canAcceptNow = computed(
  () =>
    canAccept.value
    && task.value?.status_antar_jemput === 'menunggu'
    && task.value.kurir === null,
)

const load = async (): Promise<void> => {
  if (!Number.isInteger(taskId.value) || taskId.value <= 0) {
    loadError.value = 'Nomor tugas tidak valid.'
    return
  }

  loading.value = true
  loadError.value = null

  try {
    task.value = await deliveryApi.show(taskId.value)
  } catch (error) {
    loadError.value = normalizeApiError(error).message
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})

const handleUpdated = async (message: string) => {
  successMessage.value = message
  await load()
}

const acceptTask = async () => {
  if (!task.value) return

  loadError.value = null

  try {
    const updated = await deliveryApi.accept(task.value.id_antar_jemput)
    await handleUpdated(
      `Tugas ${updated.pesanan?.kode_pesanan ?? ''} diterima. Status sekarang proses antar.`,
    )
  } catch (error) {
    loadError.value = normalizeApiError(error).message
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <PageBreadcrumb page-title="Detail Tugas Antar-Jemput" />
        <Button variant="outline" size="sm" @click="router.push({ name: 'PickupDelivery' })">
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
        Memuat detail tugas...
      </div>

      <div
        v-else-if="loadError"
        role="alert"
        class="rounded-2xl border border-error-200 bg-error-50 px-5 py-6 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
      >
        {{ loadError }}
        <button type="button" class="ms-2 font-medium underline" @click="load()">Coba lagi</button>
      </div>

      <template v-else-if="task">
        <div
          class="rounded-2xl border border-gray-200 bg-white px-5 py-5 lg:px-6 dark:border-gray-800 dark:bg-white/3"
        >
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Nomor nota</p>
              <h3 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ task.pesanan?.kode_pesanan ?? `Tugas #${task.id_antar_jemput}` }}
              </h3>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <Badge :color="task.jenis_antar_jemput === 'penjemputan' ? 'info' : 'warning'" size="sm">
                  {{ task.jenis_antar_jemput === 'penjemputan' ? 'Penjemputan' : 'Pengantaran' }}
                </Badge>
                <Badge :color="deliveryStatusMeta[task.status_antar_jemput].color" size="sm">
                  {{ deliveryStatusMeta[task.status_antar_jemput].label }}
                </Badge>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <Button v-if="canAcceptNow" size="sm" @click="acceptTask">
                <CheckIcon class="size-4" />
                Terima tugas
              </Button>
              <Button v-if="canAssignNow" variant="outline" size="sm" @click="assignOpen = true">
                <PencilIcon class="size-4" />
                {{ task.kurir ? 'Ganti kurir' : 'Tugaskan kurir' }}
              </Button>
              <Button v-if="canUpdateStatus && isAssignedToMe" variant="outline" size="sm" @click="statusOpen = true">
                <PencilIcon class="size-4" />
                Perbarui status
              </Button>
            </div>
          </div>

          <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Pelanggan</dt>
              <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
                {{ task.pesanan?.pelanggan.nama ?? '—' }}
              </dd>
              <dd class="text-xs text-gray-500 dark:text-gray-400">
                {{ task.pesanan?.pelanggan.no_hp ?? '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Penerima</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ task.nama_penerima || '—' }}
              </dd>
              <dd class="text-xs text-gray-500 dark:text-gray-400">{{ task.no_hp_penerima || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Kurir</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ task.kurir?.name ?? 'Belum ditugaskan' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Jadwal</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ formatDateTime(task.jadwal) }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Selesai</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ formatDateTime(task.tanggal_selesai) }}
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500 dark:text-gray-400">Total tagihan</dt>
              <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                {{ task.pesanan ? formatCurrency(task.pesanan.total_harga) : '—' }}
              </dd>
            </div>
          </dl>

          <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
              <p class="text-xs text-gray-500 dark:text-gray-400">Alamat tujuan</p>
              <p class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ task.alamat_tujuan }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
              <p class="text-xs text-gray-500 dark:text-gray-400">Catatan</p>
              <p class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ task.catatan || '—' }}</p>
            </div>
          </div>
        </div>

        <div
          class="rounded-2xl border border-gray-200 bg-white px-5 py-5 lg:px-6 dark:border-gray-800 dark:bg-white/3"
        >
          <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Riwayat tugas</h4>

          <ol
            v-if="task.riwayat && task.riwayat.length > 0"
            class="mt-4 space-y-4 border-s border-gray-200 ps-4 dark:border-gray-800"
          >
            <li v-for="(riwayat, index) in task.riwayat" :key="index" class="relative">
              <span
                class="absolute -start-[21px] top-1.5 size-2.5 rounded-full bg-brand-500"
                aria-hidden="true"
              ></span>
              <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                {{ deliveryStatusMeta[riwayat.status_selanjutnya as keyof typeof deliveryStatusMeta]?.label ?? riwayat.status_selanjutnya }}
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

    <AssignCourierDialog v-model:open="assignOpen" :task="task" @assigned="handleUpdated" />

    <UpdateDeliveryStatusDialog v-model:open="statusOpen" :task="task" @updated="handleUpdated" />
  </AdminLayout>
</template>
