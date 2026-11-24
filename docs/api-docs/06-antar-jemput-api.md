# API Documentation — Manajemen Antar-Jemput

**Project:** Pemuda Laundry
**Module:** Courier (Antar-Jemput) Management
**API Version:** v1
**Base URL:** `http://127.0.0.1:8000/api/v1`

---

## 1. Deskripsi

API Manajemen Antar-Jemput digunakan untuk mengelola tugas penjemputan dan pengantaran laundry.

Alur pengantaran dari pesanan:

1. Karyawan menekan tombol **Antar** pada pesanan berstatus `siap_diambil`,
   lalu mengisi alamat tujuan di dialog **Antar pesanan**
   (`POST /orders/{order}/delivery`).
2. Tugas dibuat dengan status `menunggu` — tampil sebagai **Menunggu kurir**.
3. Kurir menekan **Terima tugas** (`PATCH /delivery-tasks/{id}/accept`);
   status berubah menjadi `ditugaskan` — tampil sebagai **Proses antar**.
4. Kurir mengubah status menjadi `selesai` — tampil sebagai **Selesai diantar**.
   Backend otomatis mengubah pesanan menjadi `selesai`.

Proses pengantaran kini dikelola sepenuhnya lewat tugas antar-jemput. Pesanan
tetap `siap_diambil` selama proses antar dan baru menjadi `selesai` setelah
tugas pengantaran selesai.

Permintaan dari landing page mengikuti alur terpisah: form landing membuat
tugas `penjemputan` dengan `sumber = landing` dan status
`menunggu_verifikasi`. Karyawan memeriksa datanya (bisa menghubungi pengirim
lewat tombol WhatsApp), lalu memilih **Verifikasi** (`menunggu`, siap
ditugaskan) atau **Tolak** (`gagal`). Saat karyawan menugaskan kurir, status
langsung menjadi `menuju_lokasi`; kurir lalu mengubahnya menjadi
`menuju_laundry` (**Pesanan menuju laundry**), lalu
`pakaian_sampai_laundry` (**Pakaian sampai di laundry**), dan akhirnya
`selesai`. Kurir tidak melihat tugas selama masih `menunggu_verifikasi`.
Detailnya ada di [10-landing-delivery-api.md](./10-landing-delivery-api.md).

Setiap tugas yang diterima kurir menampilkan tombol **WhatsApp pelanggan**
untuk komunikasi. Nomor diambil dari `pesanan.pelanggan.no_hp` untuk tugas
internal dan dari `no_hp_penerima` untuk tugas landing.

Fitur yang tersedia:
- Melihat daftar tugas antar-jemput.
- Membuat permintaan antar-jemput.
- Menerima tugas yang menunggu kurir.
- Menugaskan kurir ke tugas antar-jemput.
- Memperbarui status tugas antar-jemput oleh kurir.
- Riwayat status tugas antar-jemput.

## 2. Autentikasi

Seluruh endpoint memerlukan autentikasi menggunakan Laravel Sanctum.

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
Content-Type: application/json
```

## 3. Hak Akses

| Role | View | Verify/Reject | Accept | Assign | Update Status |
|---|---|---|---|---|---|
| Owner | ✓ | ✓ | - | ✓ | ✓ |
| Admin | ✓ | ✓ | - | - | ✓ |
| Karyawan | ✓ | ✓ | - | - | - |
| Kurir | ✓ | - | ✓ | - | ✓ |

Catatan:

- Endpoint `GET /delivery-tasks` dan `GET /delivery-tasks/{id}` untuk role Kurir
  mengembalikan tugas miliknya sendiri **dan** tugas yang belum ditugaskan
  (`id_kurir = null`, status `menunggu`) agar bisa diambil.
- Kurir hanya dapat mengubah status tugas yang sudah ia terima. Mengakses tugas
  kurir lain menghasilkan `403`.
- Membuat tugas dari pesanan memakai permission `orders.update-status` melalui
  endpoint di modul Orders, bukan `delivery.assign`.
- Menugaskan kurir ke tugas internal yang masih `menunggu` tidak mengubah
  statusnya. Kurir tetap harus menekan **Terima tugas** (`accept`) supaya status
  menjadi `ditugaskan`.
- Pengecualian: tugas `penjemputan` ber-`sumber = landing` langsung berstatus
  `menuju_lokasi` saat kurir ditugaskan, tanpa perlu `accept`.

Permission:
- `delivery.view`
- `delivery.verify`
- `delivery.assign`
- `delivery.update`
- `orders.update-status`

## 4. Daftar Endpoint

| Method | Endpoint | Permission | Deskripsi |
|---|---|---|---|
| GET | `/delivery-tasks` | `delivery.view` | Daftar tugas |
| POST | `/delivery-tasks` | `delivery.assign` | Buat permintaan |
| POST | `/orders/{order}/delivery` | `orders.update-status` | Buat tugas antar dari pesanan siap diambil |
| GET | `/delivery-tasks/{id}` | `delivery.view` | Detail tugas |
| PATCH | `/delivery-tasks/{id}/accept` | `delivery.update` | Kurir menerima tugas |
| PATCH | `/delivery-tasks/{id}/verify` | `delivery.verify` | Verifikasi permintaan landing |
| PATCH | `/delivery-tasks/{id}/reject` | `delivery.verify` | Tolak permintaan landing |
| PATCH | `/delivery-tasks/{id}/assign` | `delivery.assign` | Tugaskan/ganti kurir |
| PATCH | `/delivery-tasks/{id}/status` | `delivery.update` | Update status (kurir) |
| GET | `/delivery-tasks/couriers` | `delivery.assign` | Daftar kurir tersedia |

---

## 5. Daftar Tugas Antar-Jemput

Mengambil daftar tugas antar-jemput. Kurir melihat tugas miliknya dan tugas
yang masih menunggu kurir.

**Endpoint**

```http
GET /delivery-tasks?search=...&status=menunggu
```

**Query Parameters:** `search`, `status`, `jenis`, `id_kurir`, `unassigned` (boolean), `assigned` (boolean), `tanggal_mulai`, `tanggal_selesai`.

---

## 6. Terima Tugas (Kurir)

Kurir mengambil tugas yang masih `menunggu`. Endpoint ini sekaligus menetapkan
`id_kurir` ke kurir yang sedang login.

**Endpoint**

```http
PATCH /delivery-tasks/{id}/accept
```

**Permission:** `delivery.update` (khusus role kurir)

**Response**: tugas dengan status `ditugaskan` (**Proses antar**).

Syarat:
- Role harus `kurir`.
- Status harus `menunggu`.
- `id_kurir` masih `null`.

Jika tugas sudah diterima kurir lain, backend mengembalikan `422`. Endpoint ini
juga dipakai kurir untuk menerima ulang tugas yang sudah ditugaskan kepadanya.
Tugas penjemputan dari landing tidak memakai endpoint ini: statusnya langsung
`menuju_lokasi` setelah karyawan menugaskan kurir.

---

## 7. Update Status Tugas

Mengubah status tugas oleh kurir.

**Endpoint**

```http
PATCH /delivery-tasks/{id}/status
```

**Permission:** `delivery.update`

**Request Body:** `status_antar_jemput` (enum), `keterangan` (optional string).

**Status Valid:** `menunggu_verifikasi`, `menunggu`, `ditugaskan`, `menuju_lokasi`, `menuju_laundry`, `pakaian_sampai_laundry`, `barang_diambil`, `barang_diantar`, `selesai`, `gagal`.

**Label yang ditampilkan frontend:**

| Status internal | Label |
|---|---|
| `menunggu_verifikasi` | Menunggu verifikasi |
| `menunggu` | Menunggu kurir |
| `ditugaskan` | Proses antar |
| `menuju_lokasi` | Menuju lokasi |
| `menuju_laundry` | Pesanan menuju laundry |
| `pakaian_sampai_laundry` | Pakaian sampai di laundry |
| `barang_diambil` | Barang diambil |
| `barang_diantar` | Barang diantar |
| `selesai` | Selesai diantar |
| `gagal` | Gagal |

Transisi utama pengantaran internal:
`menunggu` → `ditugaskan` → (`menuju_lokasi` →) `barang_diantar` → `selesai`.
Untuk pengantaran, kurir juga boleh langsung `ditugaskan` → `selesai`.

Transisi penjemputan dari landing:
`menuju_lokasi` → `menuju_laundry` → `pakaian_sampai_laundry` → `selesai`.

Status `menuju_laundry` dan `pakaian_sampai_laundry` hanya diterima pada tugas
`penjemputan`. Status lama `barang_diambil` tetap didukung untuk tugas lama.

Saat status menjadi `selesai` pada tugas `pengantaran`, backend otomatis:
- mengubah `status_pesanan` menjadi `selesai`,
- mengisi `tanggal_selesai` pesanan bila belum ada,
- menambah entri `riwayat_status_pesanan`.

Jika tugas `gagal`, pesanan tetap `siap_diambil` dan karyawan dapat membuat
tugas pengantaran baru.

---

## 8. Verifikasi / Penolakan Permintaan Landing

```http
PATCH /delivery-tasks/{id}/verify
PATCH /delivery-tasks/{id}/reject
```

**Permission:** `delivery.verify`

Keduanya hanya berlaku untuk tugas dengan `status_antar_jemput =
menunggu_verifikasi`. `verify` mengubah status menjadi `menunggu` sehingga
tugas masuk ke daftar **Menunggu kurir**; `reject` mengubah status menjadi
`gagal`. Satu tugas hanya bisa diproses sekali, baik oleh verifikasi maupun
penolakan.

Response memakai `DeliveryTaskResource`, termasuk field `sumber`
(`internal` atau `landing`).
