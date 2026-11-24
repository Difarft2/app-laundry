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

| Role | View | Accept | Assign | Update Status |
|---|---|---|---|---|
| Owner | ✓ | - | ✓ | ✓ |
| Admin | ✓ | - | - | ✓ |
| Karyawan | ✓ | - | - | - |
| Kurir | ✓ | ✓ | - | ✓ |

Catatan:

- Endpoint `GET /delivery-tasks` dan `GET /delivery-tasks/{id}` untuk role Kurir
  mengembalikan tugas miliknya sendiri **dan** tugas yang belum ditugaskan
  (`id_kurir = null`, status `menunggu`) agar bisa diambil.
- Kurir hanya dapat mengubah status tugas yang sudah ia terima. Mengakses tugas
  kurir lain menghasilkan `403`.
- Membuat tugas dari pesanan memakai permission `orders.update-status` melalui
  endpoint di modul Orders, bukan `delivery.assign`.

Permission:
- `delivery.view`
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

Jika tugas sudah diterima kurir lain, backend mengembalikan `422`.

---

## 7. Update Status Tugas

Mengubah status tugas oleh kurir.

**Endpoint**

```http
PATCH /delivery-tasks/{id}/status
```

**Permission:** `delivery.update`

**Request Body:** `status_antar_jemput` (enum), `keterangan` (optional string).

**Status Valid:** `menunggu`, `ditugaskan`, `menuju_lokasi`, `barang_diambil`, `barang_diantar`, `selesai`, `gagal`.

**Label yang ditampilkan frontend:**

| Status internal | Label |
|---|---|
| `menunggu` | Menunggu kurir |
| `ditugaskan` | Proses antar |
| `menuju_lokasi` | Menuju lokasi |
| `barang_diambil` | Barang diambil |
| `barang_diantar` | Barang diantar |
| `selesai` | Selesai diantar |
| `gagal` | Gagal |

Transisi utama:
`menunggu` → `ditugaskan` → (`menuju_lokasi` →) `barang_diantar` → `selesai`.
Untuk pengantaran, kurir juga boleh langsung `ditugaskan` → `selesai`.

Saat status menjadi `selesai` pada tugas `pengantaran`, backend otomatis:
- mengubah `status_pesanan` menjadi `selesai`,
- mengisi `tanggal_selesai` pesanan bila belum ada,
- menambah entri `riwayat_status_pesanan`.

Jika tugas `gagal`, pesanan tetap `siap_diambil` dan karyawan dapat membuat
tugas pengantaran baru.
