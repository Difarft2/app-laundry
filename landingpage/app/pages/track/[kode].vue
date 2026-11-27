<script setup>
import { ref, onMounted, computed } from "vue";

const route = useRoute();
const kode = route.params.kode;

const isLoading = ref(true);
const error = ref("");

const order = ref({
  nomor_nota: "",
  nama_pelanggan: "",
  status: "",
  estimasi_selesai: null,
  layanan: [],
  subtotal: 0,
  riwayat_status: [],
});

const normalizedKode = kode ? String(kode).trim().toUpperCase() : "";

const fetchOrder = async () => {
  if (!normalizedKode) {
    error.value = "Nomor nota tidak valid";
    isLoading.value = false;
    return;
  }

  try {
    const response = await $fetch(
      `/api/track/${encodeURIComponent(normalizedKode)}`,
    );

    if (response.success && response.data) {
      order.value = response.data;
    } else {
      error.value = response.message || "Pesanan tidak ditemukan";
    }
  } catch (e) {
    error.value =
      e?.data?.message || "Pesanan tidak ditemukan atau terjadi kesalahan.";
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchOrder);

const statusMap = {
  menunggu: {
    label: "Menunggu",
    description: "Pesanan sedang menunggu untuk diproses.",
    color: "amber",
    step: 1,
  },
  diproses: {
    label: "Sedang Diproses",
    description: "Pesanan sedang dikerjakan oleh tim laundry.",
    color: "blue",
    step: 2,
  },
  selesai: {
    label: "Selesai",
    description: "Pesanan sudah selesai dikerjakan.",
    color: "green",
    step: 3,
  },
  dibatalkan: {
    label: "Dibatalkan",
    description: "Pesanan ini telah dibatalkan.",
    color: "red",
    step: 0,
  },
};

const currentStatus = computed(() => {
  return (
    statusMap[order.value.status] || {
      label: order.value.status || "Tidak diketahui",
      description: "Status pesanan belum tersedia.",
      color: "gray",
      step: 0,
    }
  );
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value) || 0);
};

const formatDate = (value, options = {}) => {
  if (!value) return "-";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) return "-";

  return date.toLocaleString("id-ID", {
    dateStyle: "long",
    timeStyle: "short",
    ...options,
  });
};

const progressSteps = [
  { key: "menunggu", label: "Menunggu" },
  { key: "diproses", label: "Diproses" },
  { key: "selesai", label: "Selesai" },
];

const isStepCompleted = (step) => {
  if (order.value.status === "dibatalkan") return false;
  return currentStatus.value.step >= step;
};
</script>

<template>
  <main class="tracking-page">
    <div class="tracking-container">
      <!-- Header -->
      <header class="page-header">
        <a href="/" class="brand">
          <span class="brand-icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path d="M3 7h18l-1.5 13h-15L3 7Z" stroke-linejoin="round" />
              <path d="M8 7a4 4 0 0 1 8 0" stroke-linecap="round" />
              <path d="M8 12h8M8 15h5" stroke-linecap="round" />
            </svg>
          </span>
          <span class="brand-name">Pemuda<span>Laundry</span></span>
        </a>

        <div class="header-right">
          <span class="online-indicator"></span>
          <span>Pelacakan Pesanan</span>
        </div>
      </header>

      <!-- Loading -->
      <section v-if="isLoading" class="state-card">
        <div class="loader"></div>
        <h2>Memuat pesanan</h2>
        <p>Sedang mengambil informasi pesananmu. Mohon tunggu sebentar.</p>
      </section>

      <!-- Error -->
      <section v-else-if="error" class="state-card">
        <div class="state-icon error-icon">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v5m0 3h.01" stroke-linecap="round" />
          </svg>
        </div>

        <span class="eyebrow">PESANAN TIDAK DITEMUKAN</span>
        <h2>Oops, ada masalah!</h2>
        <p>{{ error }}</p>
        <a href="/" class="primary-button">Kembali ke Beranda</a>
      </section>

      <!-- Main Content -->
      <template v-else>
        <!-- Intro -->
        <section class="intro-section">
          <div>
            <span class="eyebrow">ORDER TRACKING</span>
            <h1>Lacak Pesanan<span>.</span></h1>
            <p>
              Pantau perjalanan pesanan laundry-mu dengan mudah. Semua informasi
              pesanan tersedia di sini.
            </p>
          </div>

          <div class="intro-decoration">
            <svg viewBox="0 0 120 120" fill="none">
              <circle
                cx="60"
                cy="60"
                r="51"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-dasharray="4 6"
              />
              <path
                d="M34 47h52l-4 39H38l-4-39Z"
                stroke="currentColor"
                stroke-width="2"
                stroke-linejoin="round"
              />
              <path
                d="M47 47a13 13 0 0 1 26 0M47 62h26M47 70h17"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
              />
            </svg>
          </div>
        </section>

        <!-- Main Tracking Card -->
        <section class="status-card">
          <div class="status-top">
            <div class="invoice-info">
              <span class="muted-label">NOMOR NOTA</span>
              <h2 class="invoice-number">
                {{ order.nomor_nota || normalizedKode }}
              </h2>
            </div>

            <span class="status-badge" :class="`badge-${currentStatus.color}`">
              <span class="badge-dot"></span>
              {{ currentStatus.label }}
            </span>
          </div>

          <div class="status-divider"></div>

          <div class="status-content">
            <div>
              <span class="eyebrow">STATUS PESANAN</span>
              <h2>{{ currentStatus.label }}</h2>
              <p>{{ currentStatus.description }}</p>
            </div>

            <div class="status-symbol" :class="`symbol-${currentStatus.color}`">
              <svg
                v-if="order.status === 'selesai'"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <path
                  d="m5 12 4 4L19 6"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>

              <svg
                v-else-if="order.status === 'dibatalkan'"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <path d="m7 7 10 10M17 7 7 17" stroke-linecap="round" />
              </svg>

              <svg
                v-else
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7v5l3 2" stroke-linecap="round" />
              </svg>
            </div>
          </div>

          <!-- Progress -->
          <div v-if="order.status !== 'dibatalkan'" class="progress-section">
            <div class="progress-track">
              <div
                class="progress-fill"
                :style="{ width: `${((currentStatus.step - 1) / 2) * 100}%` }"
              ></div>
            </div>

            <div class="progress-steps">
              <div
                v-for="(step, index) in progressSteps"
                :key="step.key"
                class="progress-step"
                :class="{
                  active: isStepCompleted(index + 1),
                  current: order.status === step.key,
                }"
              >
                <div class="step-circle">
                  <svg
                    v-if="isStepCompleted(index + 1)"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                  <span v-else>{{ index + 1 }}</span>
                </div>

                <span>{{ step.label }}</span>
              </div>
            </div>
          </div>

          <div v-else class="cancelled-notice">
            <span>!</span>
            Pesanan ini tidak lagi dalam proses pengerjaan.
          </div>
        </section>

        <!-- Customer Information -->
        <section class="info-grid">
          <div class="info-card">
            <div class="info-icon">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" stroke-linecap="round" />
              </svg>
            </div>

            <div class="info-detail">
              <span class="muted-label">NAMA PELANGGAN</span>
              <h3>{{ order.nama_pelanggan || "-" }}</h3>
            </div>
          </div>

          <div class="info-card">
            <div class="info-icon estimate-icon">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <path d="M16 3v4M8 3v4M3 10h18" stroke-linecap="round" />
              </svg>
            </div>

            <div class="info-detail">
              <span class="muted-label">ESTIMASI SELESAI</span>
              <h3>
                {{
                  order.estimasi_selesai
                    ? formatDate(order.estimasi_selesai)
                    : "Belum tersedia"
                }}
              </h3>
            </div>
          </div>
        </section>

        <!-- Service Details -->
        <section v-if="order.layanan?.length" class="content-card">
          <div class="section-heading">
            <div>
              <span class="eyebrow">RINCIAN TRANSAKSI</span>
              <h2>Detail Layanan</h2>
              <p>Rincian layanan yang terdapat pada pesanan ini.</p>
            </div>

            <span class="item-count">{{ order.layanan.length }} Layanan</span>
          </div>

          <div class="table-wrapper">
            <table class="service-table">
              <thead>
                <tr>
                  <th>Layanan</th>
                  <th>Jumlah</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(item, index) in order.layanan" :key="index">
                  <td>
                    <span class="service-name">{{ item.nama }}</span>
                  </td>

                  <td>
                    <span class="quantity"
                      >{{ item.jumlah }} {{ item.satuan }}</span
                    >
                  </td>

                  <td class="text-right price">
                    {{ formatCurrency(item.subtotal) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="total-row">
            <div>
              <span>Total Pembayaran</span>
              <small>Jumlah keseluruhan layanan</small>
            </div>

            <strong>{{ formatCurrency(order.subtotal) }}</strong>
          </div>
        </section>

        <!-- History -->
        <section v-if="order.riwayat_status?.length" class="content-card">
          <div class="section-heading">
            <div>
              <span class="eyebrow">ORDER ACTIVITY</span>
              <h2>Riwayat Pesanan</h2>
              <p>Perjalanan dan perubahan status pesananmu.</p>
            </div>

            <span class="item-count"
              >{{ order.riwayat_status.length }} Aktivitas</span
            >
          </div>

          <div class="timeline">
            <div
              v-for="(item, index) in order.riwayat_status"
              :key="index"
              class="timeline-item"
              :class="{
                'last-item': index === order.riwayat_status.length - 1,
              }"
            >
              <div class="timeline-indicator">
                <div class="timeline-dot">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </div>

                <div
                  v-if="index !== order.riwayat_status.length - 1"
                  class="timeline-line"
                ></div>
              </div>

              <div class="timeline-content">
                <div class="timeline-title">
                  <h3>{{ statusMap[item.status]?.label || item.status }}</h3>
                  <span v-if="index === 0" class="latest-badge">Terbaru</span>
                </div>

                <p>{{ formatDate(item.waktu) }}</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Footer -->
        <footer class="page-footer">
          <div class="footer-brand">Pemuda<span>Laundry</span></div>

          <p>
            Terima kasih telah mempercayakan perawatan pakaianmu kepada kami.
          </p>

          <a href="/" class="back-link">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="m15 18-6-6 6-6M9 12h12"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            Kembali ke Beranda
          </a>

          <div class="footer-copy">
            © {{ new Date().getFullYear() }} Pemuda Laundry. All rights
            reserved.
          </div>
        </footer>
      </template>
    </div>
  </main>
</template>

<style scoped>
.tracking-page {
  --primary: #0891b2;
  --primary-dark: #0e7490;
  --primary-soft: #ecfeff;
  --text: #172b3a;
  --muted: #718096;
  --border: #e8edf2;

  min-height: 100vh;
  padding: 0 24px 50px;
  background: #f7f9fb;
  color: var(--text);
}

.tracking-container {
  max-width: 920px;
  margin: 0 auto;
}

/* Header */
.page-header {
  height: 78px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border);
  margin-bottom: 54px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 11px;
  color: var(--text);
  text-decoration: none;
}

.brand-icon {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  color: white;
  background: var(--primary);
  border-radius: 12px;
}

.brand-icon svg {
  width: 23px;
  height: 23px;
}

.brand-name {
  font-size: 19px;
  font-weight: 800;
  letter-spacing: -0.8px;
}

.brand-name span,
.footer-brand span {
  color: var(--primary);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
}

.online-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 0 4px #d1fae5;
}

/* Intro */
.intro-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  min-height: 125px;
}

.eyebrow {
  display: block;
  color: var(--primary);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1.6px;
}

.intro-section h1 {
  margin: 10px 0 8px;
  font-size: clamp(30px, 5vw, 39px);
  font-weight: 800;
  letter-spacing: -1.8px;
  line-height: 1.15;
}

.intro-section h1 span {
  color: var(--primary);
}

.intro-section p {
  max-width: 490px;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.8;
}

.intro-decoration {
  width: 105px;
  height: 105px;
  display: grid;
  place-items: center;
  color: #a5dce6;
  background: #e9f9fb;
  border-radius: 30px;
  transform: rotate(5deg);
}

.intro-decoration svg {
  width: 80px;
  height: 80px;
}

/* Cards */
.status-card,
.content-card,
.info-card,
.state-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 18px;
  box-shadow: 0 5px 25px rgba(23, 43, 58, 0.035);
}

.status-card {
  padding: 30px;
  margin-bottom: 18px;
}

.status-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.muted-label {
  display: block;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1.1px;
}

.invoice-number {
  margin-top: 8px;
  font-family: monospace;
  font-size: 23px;
  font-weight: 800;
  letter-spacing: 0.5px;
  overflow-wrap: anywhere;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  border-radius: 30px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.badge-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: currentColor;
}

.badge-amber {
  color: #b45309;
  background: #fffbeb;
}

.badge-blue {
  color: #1d4ed8;
  background: #eff6ff;
}

.badge-green {
  color: #047857;
  background: #ecfdf5;
}

.badge-red {
  color: #b91c1c;
  background: #fef2f2;
}

.badge-gray {
  color: #475569;
  background: #f1f5f9;
}

.status-divider {
  height: 1px;
  background: var(--border);
  margin: 25px 0;
}

.status-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.status-content h2 {
  margin: 9px 0 5px;
  font-size: 24px;
  font-weight: 800;
  letter-spacing: -0.8px;
}

.status-content p {
  color: var(--muted);
  font-size: 13px;
  line-height: 1.7;
}

.status-symbol {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  flex-shrink: 0;
  border-radius: 18px;
}

.status-symbol svg {
  width: 30px;
  height: 30px;
}

.symbol-amber {
  color: #d97706;
  background: #fffbeb;
}

.symbol-blue {
  color: #2563eb;
  background: #eff6ff;
}

.symbol-green {
  color: #059669;
  background: #ecfdf5;
}

.symbol-red {
  color: #dc2626;
  background: #fef2f2;
}

.symbol-gray {
  color: #64748b;
  background: #f1f5f9;
}

/* Progress */
.progress-section {
  position: relative;
  margin-top: 45px;
  padding: 0 10px;
}

.progress-track {
  position: absolute;
  top: 14px;
  left: 16.6%;
  right: 16.6%;
  height: 4px;
  background: #e9eff4;
  border-radius: 10px;
}

.progress-fill {
  height: 100%;
  background: var(--primary);
  border-radius: 10px;
  transition: width 0.5s ease;
}

.progress-steps {
  position: relative;
  display: flex;
  justify-content: space-between;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
}

.step-circle {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border: 3px solid white;
  border-radius: 50%;
  background: #e9eff4;
  color: #94a3b8;
  box-shadow: 0 0 0 1px #e9eff4;
}

.step-circle svg {
  width: 14px;
  height: 14px;
}

.progress-step.active {
  color: var(--primary-dark);
}

.progress-step.active .step-circle {
  color: white;
  background: var(--primary);
  box-shadow: 0 0 0 1px var(--primary);
}

.progress-step.current .step-circle {
  box-shadow: 0 0 0 4px #cffafe;
}

.cancelled-notice {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 25px;
  padding: 14px;
  border-radius: 10px;
  color: #b91c1c;
  background: #fef2f2;
  font-size: 12px;
}

.cancelled-notice span {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #fee2e2;
  font-weight: 800;
}

/* Customer information */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  margin-bottom: 18px;
}

.info-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 23px;
  min-width: 0;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.info-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 9px 25px rgba(23, 43, 58, 0.06);
}

.info-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  color: var(--primary);
  background: var(--primary-soft);
  border-radius: 13px;
}

.info-icon svg {
  width: 23px;
  height: 23px;
}

.estimate-icon {
  color: #3b82f6;
  background: #eff6ff;
}

.info-detail {
  min-width: 0;
}

.info-card h3 {
  margin-top: 7px;
  font-size: 14px;
  font-weight: 700;
  line-height: 1.6;
  overflow-wrap: anywhere;
}

/* Content sections */
.content-card {
  padding: 28px;
  margin-bottom: 18px;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  margin-bottom: 24px;
}

.section-heading h2 {
  margin-top: 7px;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.6px;
}

.section-heading p {
  margin-top: 5px;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.6;
}

.item-count {
  padding: 7px 11px;
  color: #64748b;
  background: #f1f5f9;
  font-size: 10px;
  font-weight: 700;
  border-radius: 20px;
  white-space: nowrap;
}

/* Table */
.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.service-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.service-table th {
  padding: 14px 12px;
  color: #8794a3;
  background: #f8fafc;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.7px;
}

.service-table th:first-child {
  border-radius: 9px 0 0 9px;
}

.service-table th:last-child {
  border-radius: 0 9px 9px 0;
}

.service-table td {
  padding: 17px 12px;
  border-bottom: 1px solid #f0f3f6;
  font-size: 12px;
}

.service-name {
  color: var(--text);
  font-weight: 700;
}

.quantity {
  color: var(--muted);
}

.text-right {
  text-align: right;
}

.price {
  color: var(--text);
  font-family: monospace;
  font-weight: 700;
  white-space: nowrap;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  padding: 22px 12px 0;
}

.total-row span {
  display: block;
  font-size: 13px;
  font-weight: 700;
}

.total-row small {
  display: block;
  margin-top: 5px;
  color: var(--muted);
  font-size: 11px;
}

.total-row strong {
  color: var(--primary-dark);
  font-size: 21px;
  font-weight: 800;
  white-space: nowrap;
}

/* Timeline */
.timeline {
  padding-top: 5px;
}

.timeline-item {
  display: flex;
  gap: 16px;
  min-height: 75px;
}

.timeline-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 30px;
  flex-shrink: 0;
}

.timeline-dot {
  z-index: 1;
  display: grid;
  place-items: center;
  width: 29px;
  height: 29px;
  color: var(--primary);
  background: var(--primary-soft);
  border: 1px solid #c9f1f6;
  border-radius: 50%;
}

.timeline-dot svg {
  width: 14px;
  height: 14px;
}

.timeline-line {
  width: 2px;
  flex: 1;
  margin: 4px 0;
  background: #dce9ee;
}

.timeline-content {
  flex: 1;
  padding: 3px 0 23px;
}

.timeline-title {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.timeline-title h3 {
  font-size: 13px;
  font-weight: 700;
}

.timeline-content p {
  margin-top: 6px;
  color: var(--muted);
  font-size: 11px;
}

.latest-badge {
  padding: 5px 9px;
  color: var(--primary-dark);
  background: #ecfeff;
  border-radius: 20px;
  font-size: 9px;
  font-weight: 700;
}

.last-item {
  min-height: auto;
}

/* Footer */
.page-footer {
  padding: 40px 10px 10px;
  text-align: center;
}

.footer-brand {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.page-footer > p {
  margin: 9px 0 18px;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.7;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--primary-dark);
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  transition: gap 0.2s ease;
}

.back-link:hover {
  gap: 12px;
}

.back-link svg {
  width: 17px;
  height: 17px;
}

.footer-copy {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
  color: #94a3b8;
  font-size: 10px;
}

/* Loading and error */
.state-card {
  max-width: 500px;
  margin: 90px auto;
  padding: 45px 25px;
  text-align: center;
}

.state-card h2 {
  margin-top: 16px;
  font-size: 20px;
  font-weight: 800;
}

.state-card p {
  margin-top: 8px;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.7;
}

.loader {
  width: 38px;
  height: 38px;
  margin: auto;
  border: 3px solid #e0f2fe;
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.state-icon {
  display: grid;
  place-items: center;
  width: 58px;
  height: 58px;
  margin: auto;
  border-radius: 50%;
}

.state-icon svg {
  width: 29px;
  height: 29px;
}

.error-icon {
  color: #dc2626;
  background: #fef2f2;
}

.state-card .eyebrow {
  margin-top: 20px;
}

.primary-button {
  display: inline-block;
  margin-top: 23px;
  padding: 12px 21px;
  color: white;
  background: var(--primary);
  border-radius: 9px;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s ease;
}

.primary-button:hover {
  background: var(--primary-dark);
}

/* Responsive */
@media (max-width: 600px) {
  .tracking-page {
    padding: 0 16px 35px;
  }

  .page-header {
    height: 68px;
    margin-bottom: 35px;
  }

  .brand-name {
    font-size: 16px;
  }

  .header-right {
    font-size: 10px;
  }

  .intro-section {
    min-height: auto;
    margin-bottom: 24px;
  }

  .intro-section h1 {
    font-size: 30px;
  }

  .intro-section p {
    font-size: 12px;
  }

  .intro-decoration {
    width: 68px;
    height: 68px;
    border-radius: 20px;
    flex-shrink: 0;
  }

  .intro-decoration svg {
    width: 52px;
    height: 52px;
  }

  .status-card,
  .content-card {
    padding: 21px 17px;
    border-radius: 15px;
  }

  .invoice-number {
    font-size: 17px;
  }

  .status-badge {
    padding: 7px 9px;
    font-size: 9px;
  }

  .status-content h2 {
    font-size: 20px;
  }

  .status-content p {
    font-size: 11px;
  }

  .status-symbol {
    width: 49px;
    height: 49px;
    border-radius: 14px;
  }

  .status-symbol svg {
    width: 24px;
    height: 24px;
  }

  .progress-section {
    margin-top: 35px;
    padding: 0 3px;
  }

  .progress-track {
    left: 16.6%;
    right: 16.6%;
  }

  .progress-step {
    font-size: 10px;
    gap: 9px;
  }

  .step-circle {
    width: 29px;
    height: 29px;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 11px;
  }

  .info-card {
    padding: 17px;
  }

  .section-heading h2 {
    font-size: 18px;
  }

  .section-heading p {
    font-size: 11px;
  }

  .item-count {
    padding: 6px 8px;
    font-size: 9px;
  }

  .service-table th,
  .service-table td {
    padding: 13px 8px;
    font-size: 10px;
  }

  .total-row {
    padding: 19px 8px 0;
  }

  .total-row strong {
    font-size: 17px;
  }

  .timeline-item {
    gap: 12px;
  }

  .page-footer {
    padding-top: 32px;
  }
}

@media (max-width: 360px) {
  .intro-decoration {
    display: none;
  }

  .status-top {
    align-items: flex-start;
    flex-direction: column;
  }

  .section-heading {
    align-items: flex-start;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    scroll-behavior: auto !important;
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
