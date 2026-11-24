<script setup lang="ts">
const WA_NUMBER = '6281217931918'
const WA_LINK = `https://wa.me/${WA_NUMBER}`
const ADDRESS = 'Jl. Pemuda Timur No.8-2, Ngrowo, Kec. Bojonegoro, Kabupaten Bojonegoro, Jawa Timur 62119'
const MAP_EMBED = 'https://www.google.com/maps?q=Pemuda+laundry+Bojonegoro&output=embed'

const navItems = [
  { label: 'Home', href: '#home' },
  { label: 'Tentang', href: '#tentang' },
  { label: 'Harga', href: '#harga' },
  { label: 'FAQ', href: '#faq' },
  { label: 'Kontak', href: '#kontak' },
]

const menuOpen = ref(false)

const heroItems = [
  { title: 'Cuci Setrika', meta: 'Regular & Express', price: 'Mulai 7K Per Kilo' },
  { title: 'Express 4 Jam', meta: 'Cepat & Bersih', price: '15K' },
  { title: 'Bedcover', meta: 'Bersih & Wangi', price: '15K+', cyan: true },
  { title: 'Boneka', meta: 'Semua ukuran', price: '10K+' },
  { title: 'Jas', meta: 'Bersih & Wangi', price: '20K Per Jas' },
]

const aboutStats = [
  { value: '1 Hari dan 4 Jam', label: 'Layanan Express' },
  { value: 'Fresh', label: 'Pewangi Premium', cyan: true },
]

const aboutPanel = [
  { title: 'Bersih', text: 'Pencucian higienis dan rapi.' },
  { title: 'Cepat', text: 'Tersedia layanan express 1 hari, reguler 2–3 hari, dan express 4 jam.' },
  { title: 'Wangi', text: 'Menggunakan pewangi premium.' },
]

const priceGroups = [
  {
    title: 'Cuci Setrika',
    rows: [
      { name: 'Regular', meta: '2–3 Hari • Min. 2 Kg', value: '7K/kg' },
      { name: 'Express', meta: '1 Hari • Min. 2 Kg', value: '9K/kg', accent: true },
    ],
  },
  {
    title: 'Cuci Kering',
    rows: [
      { name: 'Regular', meta: '2–3 Hari • Min. 2 Kg', value: '5K/kg' },
      { name: 'Express', meta: '1 Hari • Min. 2 Kg', value: '7K/kg', accent: true },
    ],
    note: 'Cocok untuk pakaian harian dan kebutuhan cepat.',
  },
  {
    title: 'Express 4 Jam',
    rows: [{ name: '4 Jam', value: '15K/Kg', plain: true }],
  },
  {
    title: 'Cuci Basah',
    rows: [{ name: 'Cuci Basah', meta: 'Min. 5 Kg', value: '2K/kg', accent: true }],
    note: 'Cocok untuk pakaian harian tanpa setrika.',
  },
  {
    title: 'Setrika',
    rows: [
      { name: 'Regular', meta: '1–3 Hari • Min. 2 Kg', value: '6K/kg' },
      { name: 'Express', meta: '1 Hari • Min. 2 Kg', value: '8K/kg', accent: true },
    ],
    note: 'Hasil rapi, wangi dan siap dipakai.',
  },
  {
    title: 'Kering',
    rows: [{ name: 'Pengeringan', meta: 'Min. 5 Kg', value: '3K/kg', accent: true }],
    note: 'Khusus pakaian basah dari pelanggan untuk proses pengeringan saja.',
  },
  {
    title: 'Bedcover',
    rows: [
      { name: 'Tebal', value: '25K', plain: true },
      { name: 'Tipis 1', value: '20K', plain: true },
      { name: 'Tipis 2', value: '15K', plain: true },
    ],
  },
  {
    title: 'Boneka',
    rows: [
      { name: 'Besar', value: '25K', plain: true },
      { name: 'Sedang', value: '20K', plain: true },
      { name: 'Kecil', value: '10K', plain: true },
    ],
  },
  {
    title: 'Jas',
    rows: [{ name: 'Cuci Jas', meta: 'Per Satuan', value: '20K', accent: true }],
    note: 'Cocok untuk jas kerja, jas formal dan kebutuhan acara.',
  },
]

const faqItems = [
  {
    question: 'Apakah tersedia laundry express?',
    answer: 'Ya, tersedia layanan express dengan pengerjaan 1 hari dan 4 jam.',
  },
  {
    question: 'Apakah bisa antar jemput?',
    answer: 'Bisa, untuk area tertentu di Bojonegoro.',
  },
  {
    question: 'Apakah menerima laundry bedcover?',
    answer: 'Ya, kami menerima bedcover berbagai ukuran.',
  },
  {
    question: 'Buka setiap hari?',
    answer: 'Ya kami buka setiap hari dan libur pada tanggal merah selain Minggu.',
  },
  {
    question: 'Area layanan dimana saja?',
    answer: 'Kami melayani area Bojonegoro.',
  },
]

const openHours = 'Buka setiap hari dan libur pada tanggal merah selain Minggu'

const deliveryForm = reactive({ nama: '', telepon: '', alamat: '' })
const deliveryOpen = ref(false)
const deliverySubmitting = ref(false)
const deliveryError = ref<string | null>(null)
const deliverySuccess = ref<string | null>(null)

const openDeliveryForm = () => {
  deliveryError.value = null
  deliverySuccess.value = null
  deliveryOpen.value = true
}

const closeDeliveryForm = () => {
  if (deliverySubmitting.value) return
  deliveryOpen.value = false
}

const submitDeliveryForm = async () => {
  deliveryError.value = null
  deliverySuccess.value = null
  deliverySubmitting.value = true

  try {
    const response = await $fetch<{ message: string }>('/api/delivery-requests', {
      method: 'POST',
      body: { ...deliveryForm },
    })

    deliverySuccess.value = response.message
    deliveryForm.nama = ''
    deliveryForm.telepon = ''
    deliveryForm.alamat = ''
  } catch (error: unknown) {
    const failure = error as { statusMessage?: string }
    deliveryError.value = failure.statusMessage ?? 'Permintaan gagal dikirim. Silakan coba lagi.'
  } finally {
    deliverySubmitting.value = false
  }
}
</script>

<template>
  <div>
    <header class="site-header">
      <div class="site-header__inner">
        <a href="#home" class="brand">
          <h1 class="brand__title">Pemuda Laundry</h1>
          <p class="brand__tagline">Bersih • Cepat • Wangi</p>
        </a>

        <nav class="site-nav" aria-label="Navigasi utama">
          <a v-for="item in navItems" :key="item.href" :href="item.href">{{ item.label }}</a>
        </nav>

        <div class="header-actions">
          <a :href="WA_LINK" target="_blank" rel="noopener" class="btn-wa">WhatsApp</a>
          <button
            type="button"
            class="menu-toggle"
            :aria-expanded="menuOpen"
            aria-controls="mobile-nav"
            aria-label="Buka menu"
            @click="menuOpen = !menuOpen"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M4 5h16" />
              <path d="M4 12h16" />
              <path d="M4 19h16" />
            </svg>
          </button>
        </div>
      </div>

      <nav v-if="menuOpen" id="mobile-nav" class="mobile-nav" aria-label="Navigasi seluler">
        <a v-for="item in navItems" :key="item.href" :href="item.href" @click="menuOpen = false">
          {{ item.label }}
        </a>
        <a :href="WA_LINK" target="_blank" rel="noopener" class="btn-wa">WhatsApp</a>
      </nav>
    </header>

    <section id="home" class="hero">
      <div class="container hero__grid">
        <div class="">
          <p class="hero__badge">Pemuda Laundry Bojonegoro</p>
          <h1 class="hero__title">Bersih, Cepat &amp; Wangi</h1>
          <p class="hero__text">
            Melayani laundry kiloan, express, setrika, bedcover, jas dan boneka dengan pelayanan
            cepat, bersih dan wangi untuk mahasiswa, keluarga, kos, kantor hingga hotel.
          </p>
          <div class="hero__actions">
            <a :href="WA_LINK" target="_blank" rel="noopener" class="btn btn-primary">
              Order Sekarang
            </a>
            <a href="#harga" class="btn btn-ghost">Lihat Harga</a>
            <button type="button" class="btn btn-outline-light" @click="openDeliveryForm">
              Pesan Kurir Antar
            </button>
          </div>
        </div>

        <div class="hero__card-wrap">
          <div class="hero__card">
            <div
              v-for="item in heroItems"
              :key="item.title"
              class="hero__card-row"
              :class="{ 'hero__card-row--cyan': item.cyan }"
            >
              <div>
                <h3>{{ item.title }}</h3>
                <p>{{ item.meta }}</p>
              </div>
              <span class="hero__card-price">{{ item.price }}</span>
            </div>
          </div>
          <div class="hero__blob" aria-hidden="true" />
        </div>
      </div>
    </section>

    <section id="tentang" class="section bg-white">
      <div class="container about__grid">
        <div class="about__text">
          <p class="eyebrow">Tentang Kami</p>
          <h2 class="section-title">Laundry untuk Semua Kebutuhan</h2>
          <p>
            Pemuda Laundry hadir untuk membantu kebutuhan laundry masyarakat Bojonegoro dengan
            layanan yang bersih, cepat, dan wangi.
          </p>
          <p>
            Kami melayani mahasiswa, anak kos, keluarga, kantor, hingga pelanggan umum dengan
            pilihan layanan yang praktis dan harga terjangkau.
          </p>
          <div class="about__stats">
            <div
              v-for="stat in aboutStats"
              :key="stat.value"
              class="stat-card"
              :class="{ 'stat-card--cyan': stat.cyan }"
            >
              <h3>{{ stat.value }}</h3>
              <p>{{ stat.label }}</p>
            </div>
          </div>
        </div>

        <div class="about__panel-wrap">
          <div class="about__panel">
            <div v-for="card in aboutPanel" :key="card.title" class="about__panel-card">
              <h3>{{ card.title }}</h3>
              <p>{{ card.text }}</p>
            </div>
          </div>
          <div class="about__blob" aria-hidden="true" />
        </div>
      </div>
    </section>

    <section id="harga" class="section bg-alt">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">Harga Laundry</p>
          <h2 class="section-title">Harga Terjangkau untuk Semua</h2>
        </div>

        <div class="price__grid">
          <article v-for="group in priceGroups" :key="group.title" class="price-card">
            <h3>{{ group.title }}</h3>
            <div
              v-for="row in group.rows"
              :key="row.name"
              class="price-row"
              :class="{ 'price-row--plain': row.plain }"
            >
              <div>
                <p class="price-row__name">{{ row.name }}</p>
                <p v-if="row.meta" class="price-row__meta">{{ row.meta }}</p>
              </div>
              <span class="price-row__value" :class="{ 'price-row__value--accent': row.accent }">
                {{ row.value }}
              </span>
            </div>
            <p v-if="group.note" class="price-note">{{ group.note }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="faq" class="section bg-white">
      <div class="container faq__wrap">
        <div class="section-head">
          <p class="eyebrow">FAQ</p>
          <h2 class="section-title">Pertanyaan Umum</h2>
        </div>

        <details v-for="item in faqItems" :key="item.question" class="faq-item">
          <summary>
            {{ item.question }}
            <span class="faq-item__plus" aria-hidden="true">+</span>
          </summary>
          <p>{{ item.answer }}</p>
        </details>
      </div>
    </section>

    <section class="section bg-alt">
      <div class="container">
        <div class="location__lead">
          <p class="eyebrow">Lokasi Kami</p>
          <h2 class="section-title">Pemuda Laundry Bojonegoro</h2>
          <p>{{ ADDRESS }}</p>
        </div>
        <div class="map-frame">
          <iframe
            :src="MAP_EMBED"
            title="Peta lokasi Pemuda Laundry Bojonegoro"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
          />
        </div>
      </div>
    </section>

    <footer id="kontak" class="site-footer">
      <div class="container">
        <div class="site-footer__grid">
          <div>
            <h2 class="site-footer__brand">Pemuda Laundry</h2>
            <p>
              Laundry bersih, cepat dan wangi untuk kebutuhan mahasiswa, keluarga, kos, kantor dan
              umum di Bojonegoro.
            </p>
          </div>

          <div>
            <h3>Menu</h3>
            <ul class="site-footer__menu">
              <li v-for="item in navItems" :key="`footer-${item.href}`">
                <a :href="item.href">{{ item.label }}</a>
              </li>
            </ul>
          </div>

          <div>
            <h3>Kontak</h3>
            <div class="site-footer__contact">
              <p>{{ ADDRESS }}</p>
              <p>WhatsApp: 081217931918</p>
              <p>{{ openHours }}</p>
            </div>
          </div>
        </div>

        <div class="site-footer__bottom">
          © {{ new Date().getFullYear() }} Pemuda Laundry Bojonegoro. All rights reserved.
        </div>
      </div>
    </footer>

    <a
      :href="WA_LINK"
      target="_blank"
      rel="noopener"
      class="wa-float"
      aria-label="Hubungi Pemuda Laundry via WhatsApp"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="30"
        height="30"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path
          d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"
        />
      </svg>
    </a>

    <div v-if="deliveryOpen" class="modal-backdrop" @click.self="closeDeliveryForm">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delivery-title">
        <div class="modal__head">
          <div>
            <p class="eyebrow">Antar-Jemput</p>
            <h2 id="delivery-title" class="modal__title">Pesan Kurir Antar</h2>
            <p class="modal__subtitle">
              Isi data di bawah ini. Tim kami akan memverifikasi permintaan sebelum kurir
              ditugaskan.
            </p>
          </div>
          <button
            type="button"
            class="modal__close"
            aria-label="Tutup formulir"
            @click="closeDeliveryForm"
          >
            ×
          </button>
        </div>

        <form class="delivery-form" @submit.prevent="submitDeliveryForm">
          <label class="field">
            <span>Nama</span>
            <input
              v-model="deliveryForm.nama"
              type="text"
              name="nama"
              autocomplete="name"
              placeholder="Nama lengkap"
              maxlength="150"
              required
            />
          </label>

          <label class="field">
            <span>Nomor Telepon</span>
            <input
              v-model="deliveryForm.telepon"
              type="tel"
              name="telepon"
              inputmode="tel"
              autocomplete="tel"
              placeholder="Contoh: 081234567890"
              maxlength="20"
              required
            />
          </label>

          <label class="field">
            <span>Alamat Penjemputan</span>
            <textarea
              v-model="deliveryForm.alamat"
              name="alamat"
              rows="3"
              placeholder="Alamat lengkap untuk penjemputan"
              maxlength="1000"
              required
            />
          </label>

          <p v-if="deliveryError" class="form-message form-message--error" role="alert">
            {{ deliveryError }}
          </p>
          <p v-if="deliverySuccess" class="form-message form-message--success" role="status">
            {{ deliverySuccess }}
          </p>

          <div class="modal__actions">
            <button type="button" class="btn btn-secondary" @click="closeDeliveryForm">
              Tutup
            </button>
            <button type="submit" class="btn btn-submit" :disabled="deliverySubmitting">
              {{ deliverySubmitting ? 'Mengirim...' : 'Kirim Permintaan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
