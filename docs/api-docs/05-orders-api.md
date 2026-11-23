# Orders API Documentation

**Project:** Pemuda Laundry
**API Version:** v1
**Base URL:** `http://localhost:8000/api/v1`
**Authentication:** Laravel Sanctum
**Content-Type:** `application/json`

---

## 1. Overview

Orders API digunakan untuk mengelola transaksi pesanan laundry, mulai dari membuat pesanan, melihat daftar pesanan, melihat detail, mengubah data, mengubah status, hingga menghapus pesanan.

Setiap pesanan dapat memiliki beberapa detail layanan. Ketika pesanan dibuat, sistem juga membuat data proses laundry untuk setiap detail layanan dan mencatat riwayat status awal pesanan.

## 2. Authentication

Seluruh endpoint Orders membutuhkan autentikasi menggunakan Laravel Sanctum.

Tambahkan header berikut pada setiap request:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

Token diperoleh melalui endpoint login.

## 3. Permission

| Role | Permission |
|---|---|
| Owner | Melihat, mengubah status, dan menghapus pesanan. **Tidak dapat membuat atau mengubah data pesanan.** |
| Admin | Hanya melihat daftar/detail pesanan (read-only). |
| Karyawan | Melihat, **membuat**, mengubah, dan mengubah status pesanan. |
| Kurir | Tidak memiliki akses Orders. |

Daftar permission:

- `orders.view`
- `orders.create`
- `orders.update`
- `orders.update-status`
- `orders.delete`

> Catatan: Admin bersifat read-only untuk pesanan. Owner dapat melihat,
> mengubah status, dan menghapus pesanan, tetapi tidak dapat membuat atau
> mengubah data pesanan. Hanya karyawan yang dapat membuat dan mengubah data
> pesanan. Endpoint pembayaran terkait pesanan dijelaskan pada dokumen
> **Payments API** (`07-payments.md`).

## 4. Endpoint Summary

| Method | Endpoint | Fungsi | Permission |
|---|---|---|---|
| GET | `/orders` | Daftar pesanan | `orders.view` |
| POST | `/orders` | Membuat pesanan | `orders.create` |
| GET | `/orders/lookup?kode_pesanan=` | Cari pesanan dari QR / kode pesanan | `orders.view` |
| GET | `/orders/{order}` | Detail pesanan | `orders.view` |
| GET | `/orders/{order}/pdf` | Cetak kartu pesanan (PDF + QR) | `orders.view` |
| PUT | `/orders/{order}` | Mengubah pesanan | `orders.update` |
| PATCH | `/orders/{order}/status` | Mengubah status pesanan | `orders.update-status` |
| DELETE | `/orders/{order}` | Menghapus pesanan | `orders.delete` |

---

## 5. Daftar Pesanan

Mengambil daftar pesanan yang tersimpan di database.

**Endpoint**

```http
GET /orders
```

**Query Parameters**

| Parameter | Tipe | Keterangan |
|---|---|---|
| `search` | string | Pencarian berdasarkan kode pesanan, nama pelanggan, atau nomor HP |
| `status_pesanan` | string | Filter berdasarkan status pesanan |
| `tanggal_mulai` | date | Filter tanggal masuk mulai |
| `tanggal_selesai` | date | Filter tanggal masuk sampai |
| `per_page` | integer | Jumlah data per halaman, maksimal 100 |
| `page` | integer | Nomor halaman |

**Contoh Request**

```http
GET /orders?search=Budi&status_pesanan=menunggu&per_page=10
```

**Status Pesanan**

| Status | Keterangan |
|---|---|
| `menunggu` | Pesanan baru diterima |
| `diproses` | Pesanan sedang diproses |
| `siap_diambil` | Laundry sudah siap diambil |
| `dalam_pengantaran` | Pesanan sedang diantar |
| `selesai` | Pesanan selesai |
| `dibatalkan` | Pesanan dibatalkan |

**Contoh Response**

```json
{
    "data": [
        {
            "id_pesanan": 1,
            "kode_pesanan": "ORD-20261118-ABC12345",
            "pelanggan": {
                "nama": "Budi",
                "no_hp": "081234567890",
                "alamat": "Tuban"
            },
            "tanggal_masuk": "2026-11-18T19:00:00.000000Z",
            "estimasi_selesai": null,
            "tanggal_selesai": null,
            "status_pesanan": "menunggu",
            "subtotal": "21000.00",
            "diskon": "0.00",
            "total_harga": "21000.00",
            "total_terbayar": "0.00",
            "sisa_pembayaran": "21000.00",
            "catatan": "Tes pesanan API",
            "pembayaran": []
        }
    ],
    "links": {},
    "meta": {}
}
```

`links` dan `meta` merupakan informasi pagination dari Laravel Resource Collection. Nilainya mengikuti hasil pagination.

Catatan:

- `estimasi_selesai` dihitung otomatis oleh backend dari durasi layanan dan dapat bernilai `null` jika layanan tidak memiliki durasi. Lihat bagian 6.
- `pembayaran` berisi riwayat pembayaran (terbaru lebih dahulu). Detail bentuknya dijelaskan pada dokumen **Payments API**.
- `total_terbayar` dan `sisa_pembayaran` hanya menghitung pembayaran berstatus `berhasil`.

---

## 6. Membuat Pesanan

Membuat pesanan baru beserta detail layanan dan proses laundry.

**Endpoint**

```http
POST /orders
```

**Permission:** `orders.create`

**Request Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `nama_pelanggan` | string | Ya | Nama pelanggan, maksimal 150 karakter |
| `no_hp` | string | Ya | Nomor HP, maksimal 20 karakter |
| `alamat` | string/null | Tidak | Alamat pelanggan |
| `tanggal_masuk` | datetime | Ya | Tanggal dan waktu pesanan masuk |
| `diskon` | numeric | Tidak | Diskon, minimal 0 |
| `catatan` | string/null | Tidak | Catatan pesanan |
| `detail_pesanan` | array | Ya | Minimal satu detail layanan |
| `detail_pesanan.*.id_layanan` | integer | Ya | ID layanan aktif |
| `detail_pesanan.*.jumlah` | numeric | Ya | Berat atau jumlah layanan |
| `detail_pesanan.*.catatan` | string/null | Tidak | Catatan detail layanan |
| `cara_bayar` | string | Tidak | `now` (bayar sekarang) atau `later` (bayar nanti). Default `later` |
| `id_metode_pembayaran` | integer | Wajib jika `cara_bayar=now` | ID metode pembayaran aktif |
| `jumlah_bayar` | numeric | Wajib jika `cara_bayar=now` | Nominal pembayaran awal, harus > 0 |
| `status_pembayaran` | string | Tidak | `pending` atau `berhasil`. `pending` disimpan sebagai `menunggu` |

> Pembayaran pada pembuatan pesanan **hanya** disimpan ke tabel `pembayaran`,
> tidak ke tabel `pesanan`. Jika `cara_bayar` tidak dikirim, pesanan dianggap
> **bayar nanti** dan pembayaran dicatat belakangan melalui `POST /payments`.

**Contoh Request**

```json
{
    "nama_pelanggan": "Budi",
    "no_hp": "081234567890",
    "alamat": "Tuban",
    "tanggal_masuk": "2026-11-18 19:00:00",
    "diskon": 0,
    "catatan": "Pisahkan pakaian putih",
    "cara_bayar": "later",
    "detail_pesanan": [
        {
            "id_layanan": 1,
            "jumlah": 3,
            "catatan": "Pakaian sehari-hari"
        },
        {
            "id_layanan": 2,
            "jumlah": 2,
            "catatan": null
        }
    ]
}
```

**Contoh Request — Bayar Sekarang**

```json
{
    "nama_pelanggan": "Budi",
    "no_hp": "081234567890",
    "alamat": "Tuban",
    "tanggal_masuk": "2026-11-18 19:00:00",
    "cara_bayar": "now",
    "id_metode_pembayaran": 1,
    "jumlah_bayar": 31000,
    "status_pembayaran": "berhasil",
    "detail_pesanan": [
        {
            "id_layanan": 1,
            "jumlah": 3,
            "catatan": "Pakaian sehari-hari"
        }
    ]
}
```

Ketentuan pembayaran saat create:

- `jenis_pembayaran` ditentukan otomatis: `pelunasan` jika `jumlah_bayar`
  lebih besar atau sama dengan total harga, selain itu `dp`.
- `status_pembayaran = pending` diubah backend menjadi `menunggu`;
  `berhasil` tetap `berhasil`.
- Metode pembayaran harus aktif.

**Estimasi Selesai Otomatis**

`estimasi_selesai` tidak dikirim frontend dan tidak disimpan sebagai input
bebas. Backend menghitungnya dari durasi layanan:

1. Ambil `waktu_layanan.estimasi_max_jam` setiap layanan; jika kosong gunakan
   `estimasi_min_jam`.
2. Ambil nilai terbesar di antara layanan (durasi terlama).
3. Tambahkan durasi tersebut ke `tanggal_masuk`.

Jika tidak ada layanan yang memiliki durasi, `estimasi_selesai` bernilai `null`.

**Contoh Response — 201 Created**

```json
{
    "data": {
        "id_pesanan": 1,
        "kode_pesanan": "ORD-20261118-ABC12345",
        "pelanggan": {
            "nama": "Budi",
            "no_hp": "081234567890",
            "alamat": "Tuban"
        },
        "tanggal_masuk": "2026-11-18T19:00:00.000000Z",
        "estimasi_selesai": null,
        "tanggal_selesai": null,
        "status_pesanan": "menunggu",
        "subtotal": "31000.00",
        "diskon": "0.00",
        "total_harga": "31000.00",
        "total_terbayar": "0.00",
        "sisa_pembayaran": "31000.00",
        "catatan": "Pisahkan pakaian putih",
        "pembayaran": [],
        "detail_pesanan": [],
        "riwayat_status": []
    }
}
```

Data `detail_pesanan`, `pembayaran`, dan `riwayat_status` pada response sebenarnya akan berisi data hasil pembuatan pesanan. Struktur lengkapnya dijelaskan pada bagian detail pesanan dan riwayat status.

**Proses otomatis ketika pesanan dibuat:**

1. Backend memvalidasi pelanggan dan detail layanan.
2. Backend mengambil harga layanan dari database.
3. Backend menghitung `estimasi_selesai` dari durasi layanan.
4. Backend menghitung subtotal, diskon, dan total harga.
5. Backend membuat data pesanan.
6. Backend membuat detail pesanan.
7. Backend membuat proses laundry dengan status awal `menunggu` untuk setiap detail.
8. Jika `cara_bayar = now`, backend membuat data pembayaran di tabel `pembayaran`.
9. Backend mencatat riwayat status awal pesanan.

Semua proses penyimpanan dilakukan menggunakan database transaction.

---

## 7. Detail Pesanan

Mengambil informasi lengkap satu pesanan.

**Endpoint**

```http
GET /orders/{order}
```

**Permission:** `orders.view`

Contoh:

```http
GET /orders/1
```

**Informasi yang dikembalikan:**

- Identitas pesanan.
- Data pelanggan.
- Tanggal masuk dan estimasi selesai.
- Status pesanan.
- Rincian harga.
- Total pembayaran dan sisa pembayaran.
- Riwayat pembayaran (`pembayaran`).
- Detail layanan.
- Proses laundry setiap detail.
- Riwayat perubahan status pesanan.

**Struktur Detail Layanan**

| Field | Keterangan |
|---|---|
| `id_detail_pesanan` | ID detail pesanan |
| `id_layanan` | ID layanan |
| `nama_layanan` | Nama layanan saat pesanan dibuat |
| `harga_satuan` | Harga layanan saat pesanan dibuat |
| `jumlah` | Berat atau jumlah item |
| `satuan` | Satuan `kg` atau `pcs` |
| `subtotal` | Total harga detail |
| `catatan` | Catatan detail |
| `proses_laundry` | Informasi proses laundry |

**Struktur Riwayat Pembayaran**

Field `pembayaran` berisi array pembayaran terurut dari `tanggal_pembayaran`
terbaru.

| Field | Keterangan |
|---|---|
| `id_pembayaran` | ID pembayaran |
| `kode_pembayaran` | Kode unik pembayaran |
| `id_pesanan` | ID pesanan terkait |
| `kode_pesanan` | Kode pesanan |
| `nama_pelanggan` | Nama pelanggan |
| `id_metode_pembayaran` | ID metode pembayaran |
| `nama_metode` | Nama metode pembayaran |
| `kode_metode` | Kode metode pembayaran |
| `id_user` | ID pengguna yang mencatat |
| `nama_user` | Nama pengguna yang mencatat |
| `jumlah_bayar` | Nominal pembayaran |
| `jenis_pembayaran` | `dp`, `angsuran`, atau `pelunasan` |
| `status_pembayaran` | `berhasil`, `menunggu`, atau `dibatalkan` |
| `tanggal_pembayaran` | Waktu pembayaran |
| `catatan` | Catatan pembayaran |

Struktur lengkap dan endpoint pembayaran dijelaskan pada dokumen
**Payments API** (`07-payments.md`).

---

## 8. Mengubah Pesanan

Mengubah data pelanggan, catatan, diskon, tanggal masuk, atau detail layanan pesanan.

**Endpoint**

```http
PUT /orders/{order}
```

**Permission:** `orders.update`

**Request Body**

Field yang dikirim bersifat opsional (`sometimes`).

Contoh mengubah data pelanggan:

```json
{
    "nama_pelanggan": "Budi Santoso",
    "no_hp": "081234567899",
    "alamat": "Surabaya",
    "catatan": "Pakaian dipisahkan"
}
```

Contoh mengubah detail layanan:

```json
{
    "detail_pesanan": [
        {
            "id_layanan": 1,
            "jumlah": 4,
            "catatan": "Tambahan pakaian"
        }
    ]
}
```

**Aturan Pengubahan**

- Pesanan dengan status `selesai` atau `dibatalkan` tidak dapat diubah.
- Detail layanan hanya dapat diganti jika seluruh proses laundry pada detail lama masih berstatus `menunggu` dan belum memiliki riwayat proses.
- Harga layanan selalu diambil dari database.
- Total pembayaran yang sudah tercatat tidak boleh melebihi total harga baru.
- Diskon tidak boleh melebihi subtotal.
- Jika `tanggal_masuk` atau `detail_pesanan` berubah, `estimasi_selesai` dihitung ulang otomatis oleh backend.

---

## 9. Mengubah Status Pesanan

Mengubah status keseluruhan pesanan.

**Endpoint**

```http
PATCH /orders/{order}/status
```

**Permission:** `orders.update-status`

**Request Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `status_pesanan` | string | Ya | Status baru |
| `keterangan` | string/null | Tidak | Catatan perubahan status |

**Contoh Request**

```json
{
    "status_pesanan": "diproses",
    "keterangan": "Pesanan mulai dikerjakan"
}
```

Status yang diperbolehkan:

- `menunggu` (Menunggu giliran)
- `dicuci` (Di cuci)
- `dikeringkan` (Di keringkan)
- `dilipat` (Di lipat)
- `disetrika` (Di setrika)
- `diproses`
- `siap_diambil`
- `dalam_pengantaran`
- `selesai`
- `dibatalkan`

Alur tahapan laundry yang dipakai pada fitur scan QR:
`menunggu` → `dicuci` → `dikeringkan` → `dilipat` → `disetrika` → `selesai`.

Setiap perubahan status akan dicatat ke tabel `riwayat_status_pesanan` dengan
`status_sebelumnya`, `status_selanjutnya`, user yang melakukan perubahan,
waktu, dan keterangan. Riwayat ini tersedia pada `riwayat_status` di response
detail pesanan.

Jika status baru adalah `selesai`, sistem juga mengisi `tanggal_selesai`.

Pesanan dengan status `selesai` atau `dibatalkan` tidak dapat diubah statusnya lagi.

---

## 10. Menghapus Pesanan

Menghapus pesanan yang belum memiliki aktivitas operasional.

**Endpoint**

```http
DELETE /orders/{order}
```

**Permission:** `orders.delete`

**Aturan Penghapusan**

Pesanan tidak dapat dihapus apabila:

- Sudah memiliki data pembayaran.
- Salah satu proses laundry sudah tidak berstatus `menunggu`.
- Salah satu proses laundry sudah memiliki riwayat proses.

Jika memenuhi ketentuan, pesanan dapat dihapus beserta detail dan data proses terkait sesuai relasi database.

---

## 11. Business Rules

| No. | Aturan | Keterangan |
|---|---|---|
| 1 | Harga layanan | Selalu diambil dari database |
| 2 | Subtotal | Dihitung dari harga layanan dikali jumlah |
| 3 | Diskon | Tidak boleh melebihi subtotal |
| 4 | Total harga | Subtotal dikurangi diskon |
| 5 | Kiloan | Menggunakan satuan kg |
| 6 | Satuan | Menggunakan satuan pcs |
| 7 | Layanan satuan | Jumlah harus berupa bilangan bulat |
| 8 | Cuci Basah | Minimal 5 kg |
| 9 | Kering Saja | Minimal 5 kg |
| 10 | Pembayaran | Total pembayaran berstatus `berhasil` tidak boleh melebihi total tagihan |
| 11 | Status awal | `menunggu` |
| 12 | Proses laundry awal | `menunggu` |
| 13 | Estimasi selesai | Dihitung otomatis dari durasi layanan (jam) dan `tanggal_masuk` |
| 14 | Penyimpanan pembayaran | Hanya di tabel `pembayaran`, bukan di tabel `pesanan` |
| 15 | Pelunasan | Nominal harus sama dengan sisa tagihan |
| 16 | Sumber kebenaran | Frontend menampilkan hasil hitung backend, bukan menghitung sendiri |

### Status Proses Laundry

Terdapat dua level status yang berbeda:

1. **`status_pesanan`** — status keseluruhan pesanan. Kini mencakup tahapan
   laundry (`menunggu`, `dicuci`, `dikeringkan`, `dilipat`, `disetrika`,
   `selesai`) dan diubah karyawan lewat scan QR pada dashboard.
2. **`status_proses`** — status per detail layanan di tabel `proses_laundry`
   (`menunggu`, `pencucian`, `pengeringan`, `finishing`, `selesai`).

Keduanya bukan sumber kebenaran yang sama. Perubahan `status_pesanan` melalui
`PATCH /orders/{order}/status` tidak mengubah `status_proses` tiap detail.

---

## 12. Error Response

Format error mengikuti standar respons Laravel.

**401 Unauthorized**

Token tidak tersedia atau tidak valid.

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden**

Pengguna tidak memiliki permission yang diperlukan.

```json
{
    "message": "User does not have the right permissions."
}
```

**404 Not Found**

Pesanan tidak ditemukan.

```json
{
    "message": "Not Found"
}
```

**422 Unprocessable Entity**

Validasi request gagal atau aturan bisnis tidak terpenuhi.

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "nama_pelanggan": [
            "The nama pelanggan field is required."
        ]
    }
}
```

---

## 13. Catatan Integrasi Frontend

Frontend harus memperhatikan hal-hal berikut:

1. Gunakan token Sanctum pada setiap request.
2. Jangan menghitung harga akhir sebagai sumber data utama; gunakan hasil perhitungan backend.
3. Tampilkan pesan validasi dari response `422`.
4. Tampilkan akses ditolak jika mendapatkan response `403`.
5. Pisahkan status pesanan, status pembayaran, dan status proses laundry.
6. Gunakan `id_pesanan` untuk mengambil detail pesanan.
7. Jangan menganggap pesanan selesai hanya karena proses laundry selesai; status pesanan memiliki alur tersendiri.
8. Tampilkan `estimasi_selesai` dari backend; jangan menghitung sendiri dari durasi layanan.
9. Gunakan `sisa_pembayaran` dari backend untuk memutuskan apakah tombol **Catat pembayaran** perlu ditampilkan.
10. Gunakan `pembayaran` dari detail pesanan sebagai sumber riwayat pembayaran; endpoint pembayaran dijelaskan pada dokumen **Payments API**.
11. Setelah create berhasil, tampilkan dialog sukses dengan tombol cetak PDF pesanan.
12. Untuk scan QR, gunakan `GET /orders/lookup`; QR berisi `kode_pesanan` (bukan URL).

---

## 14. Lookup Pesanan dari QR

Mencari satu pesanan berdasarkan kode yang tertanam pada QR kartu pesanan.

**Endpoint**

```http
GET /orders/lookup?kode_pesanan=ORD-20261123-ABC12345
```

**Permission:** `orders.view`

| Query | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `kode_pesanan` | string | Ya | Kode pesanan. Huruf kecil dibuat uppercase oleh backend. |

Response sukses berupa `Order` dengan relasi lengkap (`detail_pesanan`,
`pembayaran`, `riwayat_status`, dll.). Jika tidak ditemukan, backend
mengembalikan `404`:

```json
{
    "success": false,
    "message": "Pesanan dengan kode tersebut tidak ditemukan."
}
```

## 15. Cetak Kartu Pesanan (PDF)

Menghasilkan PDF kartu pesanan berisi nama pelanggan, nomor pesanan, daftar
layanan, total, dan QR berisi `kode_pesanan`.

**Endpoint**

```http
GET /orders/{order}/pdf
```

**Permission:** `orders.view`

Response berupa stream PDF (`application/pdf`) dengan header
`Content-Disposition: inline; filename="pesanan-{kode_pesanan}.pdf"`.

QR pada PDF dapat di-scan dari dashboard karyawan, lalu aplikasi membuka
rincian pesanan dan form ubah status beserta riwayatnya.

---

**Dokumen:** Orders API
**Versi:** 1.0
**Base Path:** `/api/v1/orders`
