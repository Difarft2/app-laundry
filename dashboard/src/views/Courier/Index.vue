<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AssignCourierDialog from '@/components/main/courier/AssignCourierDialog.vue'
import DeliveryTaskFormDialog from '@/components/main/courier/DeliveryTaskFormDialog.vue'
import UpdateDeliveryStatusDialog from '@/components/main/courier/UpdateDeliveryStatusDialog.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
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
import {
  ArrowRightIcon,
  CallIcon,
  CheckIcon,
  HorizontalDotsIcon,
  InboxIcon,
  LoaderCircleIcon,
  PencilIcon,
  PlusIcon,
  SearchIcon,
  XIcon,
} from '@/icons/layout'
import { DELIVERY_STATUS_OPTIONS, deliveryStatusMeta } from '@/constants/delivery'
import { deliveryApi } from '@/services/delivery'
import { normalizeApiError } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import type { PaginationMeta } from '@/types/auth'
import type { DeliveryTask } from '@/types/delivery'
import { formatDateTime } from '@/utils/format'
import { whatsappLink } from '@/utils/whatsapp'

const auth = useAuthStore()

// Tugas dari landing tidak memiliki `pesanan`, jadi data pelanggan
// diambil dari kolom penerima pada tugas itu sendiri.
const customerName = (task: DeliveryTask): string =>
  task.pesanan?.pelanggan.nama || task.nama_penerima || '—'

const customerPhone = (task: DeliveryTask): string =>
  task.pesanan?.pelanggan.no_hp || task.no_hp_penerima || '—'

const customerWaLink = (task: DeliveryTask): string | null =>
  whatsappLink(task.pesanan?.pelanggan.no_hp || task.no_hp_penerima)

const tasks = ref<DeliveryTask[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const search = ref('')
const statusFilter = ref('all')
const jenisFilter = ref('all')
const assignmentFilter = ref('all')
const page = ref(1)

const canAssign = computed(() => auth.hasPermission('delivery.assign'))
const canUpdateStatus = computed(() => auth.hasPermission('delivery.update'))
const isCourier = computed(() => auth.hasRole('kurir'))
const canAccept = computed(() => isCourier.value && canUpdateStatus.value)

const isAssignedToMe = (task: DeliveryTask): boolean =>
  isCourier.value && task.kurir?.id === auth.user?.id

const totalPages = computed(() => Math.max(1, meta.value?.last_page ?? 1))

const load = async (): Promise<void> => {
  loading.value = true
  loadError.value = null

  try {
    const result = await deliveryApi.list({
      search: search.value.trim() || undefined,
      status: statusFilter.value === 'all' ? undefined : statusFilter.value,
      jenis: jenisFilter.value === 'all' ? undefined : jenisFilter.value,
      unassigned: assignmentFilter.value === 'unassigned' ? true : undefined,
      assigned: assignmentFilter.value === 'assigned' ? true : undefined,
      page: page.value,
    })

    tasks.value = result.data
    meta.value = result.meta
  } catch (error) {
    loadError.value = normalizeApiError(error).message
  } finally {
    loading.value = false
  }
}

let searchTimer: ReturnType<typeof setTimeout> | undefined

const scheduleLoad = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => void load(), 350)
}

watch(search, () => {
  page.value = 1
  scheduleLoad()
})

watch([statusFilter, jenisFilter, assignmentFilter], () => {
  page.value = 1
  void load()
})

const goToPage = (next: number) => {
  if (next < 1 || next > totalPages.value || next === page.value) return
  page.value = next
  void load()
}

const formOpen = ref(false)
const assignOpen = ref(false)
const assignTarget = ref<DeliveryTask | null>(null)
const statusOpen = ref(false)
const statusTarget = ref<DeliveryTask | null>(null)

const openAssign = (task: DeliveryTask) => {
  assignTarget.value = task
  assignOpen.value = true
}

const openStatus = (task: DeliveryTask) => {
  statusTarget.value = task
  statusOpen.value = true
}

const acceptTask = async (task: DeliveryTask) => {
  loadError.value = null

  try {
    const updated = await deliveryApi.accept(task.id_antar_jemput)
    await refreshWith(
      `Tugas ${updated.pesanan?.kode_pesanan ?? ''} diterima. Status sekarang proses antar.`
    )
  } catch (error) {
    loadError.value = normalizeApiError(error).message
  }
}

const refreshWith = async (message: string, resetFilters = false) => {
  successMessage.value = message
  if (resetFilters) {
    search.value = ''
    statusFilter.value = 'all'
    jenisFilter.value = 'all'
    assignmentFilter.value = 'all'
    page.value = 1
  }
  await load()
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
  void load()
})

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
})
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb page-title="Antar-Jemput" />

      <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3"
      >
        <!-- Header -->
        <div
          class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Tugas antar-jemput</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{
                isCourier
                  ? 'Daftar tugas penjemputan dan pengantaran untuk Anda.'
                  : 'Kelola permintaan penjemputan dan pengantaran laundry.'
              }}
            </p>
          </div>

          <Button v-if="canAssign" class="shrink-0" @click="formOpen = true">
            <PlusIcon class="size-5" />
            Buat tugas
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
                placeholder="Cari nota, pelanggan, atau alamat..."
                class="text-sm"
              />
            </InputGroup>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <Select v-model="statusFilter">
              <SelectTrigger class="w-full sm:w-48" aria-label="Filter status tugas">
                <SelectValue placeholder="Semua status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua status</SelectItem>
                <SelectItem
                  v-for="option in DELIVERY_STATUS_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="jenisFilter">
              <SelectTrigger class="w-full sm:w-40" aria-label="Filter jenis tugas">
                <SelectValue placeholder="Semua jenis" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua jenis</SelectItem>
                <SelectItem value="penjemputan">Penjemputan</SelectItem>
                <SelectItem value="pengantaran">Pengantaran</SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="assignmentFilter">
              <SelectTrigger class="w-full sm:w-44" aria-label="Filter penugasan kurir">
                <SelectValue placeholder="Semua penugasan" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua penugasan</SelectItem>
                <SelectItem value="unassigned">Belum ditugaskan</SelectItem>
                <SelectItem value="assigned">Sudah ditugaskan</SelectItem>
              </SelectContent>
            </Select>

            <Badge v-if="meta" color="gray" size="sm" class="shrink-0">
              {{ meta.total }} tugas
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
          <div class="min-w-225">
            <table class="w-full">
              <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                <tr>
                  <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Nota
                  </th>
                  <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Jenis
                  </th>
                  <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Pelanggan
                  </th>
                  <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Alamat
                  </th>
                  <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Jadwal
                  </th>
                  <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Kurir
                  </th>
                  <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Status
                  </th>
                  <th class="px-5 py-3 text-end text-xs font-medium text-gray-500 lg:px-6 dark:text-gray-400">
                    Aksi
                  </th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="loading && tasks.length === 0">
                  <td colspan="8" class="px-6 py-12 text-center">
                    <span class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                      <LoaderCircleIcon class="size-5 animate-spin" />
                      Memuat data tugas...
                    </span>
                  </td>
                </tr>

                <tr v-else-if="tasks.length === 0">
                  <td colspan="8" class="px-6 py-12 text-center">
                    <span
                      class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500"
                    >
                      <InboxIcon class="size-6" />
                    </span>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Belum ada tugas antar-jemput.
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{
                        canAssign
                          ? 'Buat tugas penjemputan atau pengantaran baru.'
                          : 'Belum ada tugas yang ditugaskan kepada Anda.'
                      }}
                    </p>
                  </td>
                </tr>

                <template v-else>
                  <tr
                    v-for="task in tasks"
                    :key="task.id_antar_jemput"
                    class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/2"
                  >
                    <td class="px-5 py-4 lg:px-6">
                      <RouterLink
                        :to="`/pickup-delivery/${task.id_antar_jemput}`"
                        class="text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400"
                      >
                        {{ task.pesanan?.kode_pesanan ?? `Tugas #${task.id_antar_jemput}` }}
                      </RouterLink>
                    </td>

                    <td class="px-5 py-4 lg:px-6">
                      <Badge
                        :color="task.jenis_antar_jemput === 'penjemputan' ? 'info' : 'warning'"
                        size="sm"
                      >
                        {{ task.jenis_antar_jemput === 'penjemputan' ? 'Penjemputan' : 'Pengantaran' }}
                      </Badge>
                    </td>

                    <td class="px-5 py-4 lg:px-6">
                      <p class="text-sm text-gray-800 dark:text-white/90">
                        {{ customerName(task) }}
                      </p>
                      <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ customerPhone(task) }}
                      </p>
                      <a
                        v-if="customerWaLink(task)"
                        :href="customerWaLink(task) ?? '#'"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-green-600 hover:text-green-700 dark:text-green-400"
                      >
                        <CallIcon class="size-3.5" />
                        WhatsApp
                      </a>
                    </td>

                    <td class="max-w-64 px-5 py-4 lg:px-6">
                      <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ task.alamat_tujuan }}
                      </p>
                    </td>

                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 lg:px-6 dark:text-gray-400">
                      {{ formatDateTime(task.jadwal) }}
                    </td>

                    <td class="px-5 py-4 text-sm whitespace-nowrap lg:px-6">
                      <span v-if="task.kurir" class="text-gray-800 dark:text-white/90">
                        {{ task.kurir.name }}
                      </span>
                      <span v-else class="text-xs text-gray-400 dark:text-gray-500">Belum ditugaskan</span>
                    </td>

                    <td class="px-5 py-4 lg:px-6">
                      <Badge :color="deliveryStatusMeta[task.status_antar_jemput].color" size="sm">
                        {{ deliveryStatusMeta[task.status_antar_jemput].label }}
                      </Badge>
                    </td>

                    <td class="px-5 py-4 text-end lg:px-6">
                      <div class="flex flex-col items-end gap-2">
                        <Button
                          v-if="canAssign && ['menunggu', 'ditugaskan'].includes(task.status_antar_jemput)"
                          variant="default"
                          size="sm"
                          class="w-40 justify-center"
                          @click="openAssign(task)"
                        >
                          {{ task.kurir ? 'Ganti kurir' : 'Tugaskan kurir' }}
                        </Button>

                        <Button
                          v-if="canAccept && task.status_antar_jemput === 'menunggu' && (!task.kurir || isAssignedToMe(task))"
                          size="sm"
                          class="w-40 justify-center"
                          @click="acceptTask(task)"
                        >
                          <CheckIcon class="size-4" />
                          Terima tugas
                        </Button>

                        <Button
                          v-if="canUpdateStatus && isAssignedToMe(task) && !['selesai', 'gagal'].includes(task.status_antar_jemput)"
                          size="sm"
                          class="w-40 justify-center"
                          @click="openStatus(task)"
                        >
                          Perbarui status
                        </Button>

                        <Button variant="outline" size="sm" class="w-40 justify-center" as-child>
                          <RouterLink :to="`/pickup-delivery/${task.id_antar_jemput}`">
                            Lihat detail
                          </RouterLink>
                        </Button>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Footer -->
        <div
          class="flex flex-col gap-4 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6 dark:border-gray-800"
        >
          <p class="text-xs text-gray-500 dark:text-gray-400">
            <template v-if="meta && meta.total > 0">
              Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }} tugas
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

    <DeliveryTaskFormDialog v-model:open="formOpen" @saved="refreshWith($event, true)" />

    <AssignCourierDialog
      v-model:open="assignOpen"
      :task="assignTarget"
      @assigned="refreshWith"
    />

    <UpdateDeliveryStatusDialog
      v-model:open="statusOpen"
      :task="statusTarget"
      @updated="refreshWith"
    />
  </AdminLayout>
</template>
