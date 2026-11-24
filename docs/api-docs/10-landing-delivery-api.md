# API Documentation — Permintaan Kurir dari Landing Page

**Project:** Pemuda Laundry
**Module:** Landing → Antar-Jemput
**API Version:** v1
**Base URL:** `http://127.0.0.1:8000/api/v1`

---

## 1. Deskripsi

Landing page tidak memiliki sistem login. Pengunjung bisa memesan kurir antar
lewat tombol **Pesan Kurir Antar** dengan mengisi nama, nomor telepon, dan alamat.

Karena tidak ada token Sanctum, komunikasi landing ↔ backend memakai **kunci
rahasia bersama** yang hanya dikenal dua server:

- Backend Laravel: `LANDING_API_KEY` (dibaca lewat `config('landing.api_key')`).
- Landing page Nuxt: `NUXT_LANDING_API_KEY` pada `runtimeConfig` **private**
  (server Nitro saja). Kunci tidak pernah masuk `runtimeConfig.public` dan tidak
  dikirim ke browser.

Kunci dikirim pada header `X-Landing-Api-Key`. Nilai yang salah atau kosong
menghasilkan `401`.

Alur lengkap:

1. Pengunjung landing mengirim form ke `POST /api/landing/delivery-requests`.
2. Backend membuat tugas `antar_jemput` dengan:
   - `sumber = landing`
   - `jenis_antar_jemput = penjemputan`
   - `status_antar_jemput = menunggu_verifikasi`
   - `id_pesanan = null`, `id_kurir = null`
3. Permintaan muncul di panel **Permintaan Kurir dari Website** pada dashboard
   karyawan (role dengan `delivery.verify`).
4. Karyawan memilih:
   - **Verifikasi** → status `menunggu`, lalu karyawan/owner menugaskan kurir.
   - **Tolak** → status `gagal`.
5. Di samping tombol **Verifikasi**, panel karyawan menyediakan tombol
   **WhatsApp** yang membuka `https://wa.me/<nomor pengirim form>` dengan pesan
   konfirmasi. Nomor lokal `08…` dinormalisasi menjadi `628…`.
6. Setelah verifikasi, karyawan menugaskan kurir lewat
   `PATCH /delivery-tasks/{id}/assign`. Untuk penjemputan landing, status
   langsung menjadi `menuju_lokasi` (**Menuju lokasi**) tanpa perlu `accept`.
   Tugas internal tetap `menunggu` sampai kurir menekan **Terima tugas**
   (`accept`) dan menjadi `ditugaskan` (**Proses antar**).
7. Kurir memperbarui status secara berurutan:
   `menuju_lokasi` → `menuju_laundry` (**Pesanan menuju laundry**) →
   `pakaian_sampai_laundry` (**Pakaian sampai di laundry**) → `selesai`.
   Status `barang_diambil` tetap didukung untuk tugas lama.
8. Setiap tugas yang diterima kurir — baik penjemputan maupun pengantaran —
   menampilkan tombol **WhatsApp pelanggan** untuk komunikasi langsung. Untuk
   tugas landing, nomor pelanggan diambil dari `no_hp_penerima` karena
   `id_pesanan = null`.

Status `menunggu_verifikasi` **sengaja tidak terlihat oleh kurir**; kurir baru
melihat tugas setelah diverifikasi karyawan.

## 2. Konfigurasi Kunci

Backend — `.env`:

```dotenv
LANDING_API_KEY= nilai_acak_panjang
```

Landing page — `.env`:

```dotenv
NUXT_PUBLIC_API_BASE="http://127.0.0.1:8000/api"
NUXT_LANDING_API_KEY= nilai_acak_yang_sama
```

Kedua file `.env` tidak masuk git. Isi placeholder ada di `.env.example`.
Gunakan nilai yang sama di kedua aplikasi, misalnya hasil `openssl rand -hex 32`.

> `NUXT_PUBLIC_API_BASE` adalah base URL tanpa suffix `/v1` (mengikuti
> konvensi dashboard). Server route menambahkan `/v1/landing/delivery-requests`.

## 3. Endpoint Publik (dilindungi kunci)

### POST `/landing/delivery-requests`

**Middleware:** `landing.key`, `throttle:10,1`
**Autentikasi:** tidak ada token; wajib header kunci.

```http
POST /api/v1/landing/delivery-requests
Accept: application/json
Content-Type: application/json
X-Landing-Api-Key: <LANDING_API_KEY>
```

**Request Body**

| Field | Tipe | Wajib | Batas | Keterangan |
|---|---|---|---|---|
| `nama` | string | Ya | 150 | Nama pengirim/penerima, disimpan ke `nama_penerima` |
| `telepon` | string | Ya | 20 | Nomor telepon, disimpan ke `no_hp_penerima` |
| `alamat` | string | Ya | 1000 | Alamat penjemputan, disimpan ke `alamat_tujuan` |

```json
{
  "nama": "Budi Santoso",
  "telepon": "081234567890",
  "alamat": "Jl. Mawar No. 12, Bojonegoro"
}
```

**Response `201 Created`**

```json
{
  "success": true,
  "message": "Permintaan penjemputan berhasil dikirim. Tim kami akan memverifikasi data Anda.",
  "data": {
    "id": 12,
    "status": "menunggu_verifikasi",
    "created_at": "2026-11-24T10:15:00.000000Z"
  }
}
```

**Error**

| Status | Penyebab |
|---|---|
| `401` | Kunci API tidak ada/tidak cocok |
| `422` | Validasi nama, telepon, atau alamat gagal |
| `429` | Melebihi 10 permintaan per menit per IP |

Contoh:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/landing/delivery-requests \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H "X-Landing-Api-Key: $LANDING_API_KEY" \
  -d '{"nama":"Budi Santoso","telepon":"081234567890","alamat":"Jl. Mawar No. 12, Bojonegoro"}'
```

## 4. Endpoint Review (butuh login)

Keduanya memakai permission `delivery.verify` (owner, admin, karyawan).

### PATCH `/delivery-tasks/{id}/verify`

Mengubah `menunggu_verifikasi` → `menunggu` dan menulis riwayat dengan
`id_user` karyawan yang memverifikasi. Sebelum memverifikasi, karyawan bisa
menghubungi pengirim form lewat tombol WhatsApp di panel yang sama.

### PATCH `/delivery-tasks/{id}/reject`

Mengubah `menunggu_verifikasi` → `gagal`, mengisi `tanggal_selesai`, dan
menulis riwayat. Body opsional: `{ "keterangan": "Alasan penolakan" }`.

Keduanya mengembalikan `422` jika permintaan sudah diverifikasi/ditolak.

Contoh:

```bash
curl -X PATCH http://127.0.0.1:8000/api/v1/delivery-tasks/12/verify \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json'
```

## 5. Perubahan Skema

Migrasi `2026_11_24_000200_add_landing_delivery_request_to_antar_jemput_table`:

- `antar_jemput.id_pesanan` menjadi nullable (permintaan landing belum punya pesanan).
- Kolom `sumber` enum `internal`/`landing`, default `internal`.
- Status `menunggu_verifikasi` ditambahkan ke enum `status_antar_jemput`.

Migrasi `2026_11_24_000300_add_pickup_journey_statuses` menambahkan status
`menuju_laundry` dan `pakaian_sampai_laundry` ke enum `status_antar_jemput`.

`DeliveryTaskResource` mengekspos field `sumber` sehingga frontend bisa
membedakan tugas internal dan tugas landing.

### Alur status penjemputan landing

| Aksi | Status | Label frontend |
|---|---|---|
| Form landing dikirim | `menunggu_verifikasi` | Menunggu verifikasi |
| Karyawan verifikasi | `menunggu` | Menunggu kurir |
| Karyawan tugaskan kurir | `menuju_lokasi` | Menuju lokasi |
| Kurir: pesanan menuju laundry | `menuju_laundry` | Pesanan menuju laundry |
| Kurir: pakaian sampai di laundry | `pakaian_sampai_laundry` | Pakaian sampai di laundry |
| Kurir selesaikan | `selesai` | Selesai diantar |
| Karyawan tolak | `gagal` | Gagal |

Transisi ini divalidasi backend; status `menuju_laundry` dan
`pakaian_sampai_laundry` hanya berlaku untuk tugas `penjemputan`.

### Tombol WhatsApp

- Panel karyawan: tautan dibuat dari `no_hp_penerima` dengan pesan konfirmasi.
- Panel dasbor kurir: tautan dibuat dari `pesanan.pelanggan.no_hp` untuk tugas
  internal, atau `no_hp_penerima` untuk tugas landing.
- Helper frontend: `dashboard/src/utils/whatsapp.ts` (`whatsappLink`).

## 6. Catatan Frontend Landing

- Tombol **Pesan Kurir Antar** ada di hero `landingpage/app/app.vue`.
- Form menampilkan pesan sukses/gagal tanpa memuat ulang halaman.
- Permintaan dikirim ke route internal Nuxt `POST /api/delivery-requests`; route
  inilah yang menambahkan header kunci saat memanggil backend.
- Jangan pernah memindahkan pemanggilan backend langsung ke `app.vue`, karena
  kunci akan ikut terkirim ke browser.
