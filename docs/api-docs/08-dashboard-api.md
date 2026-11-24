# API Documentation — Dashboard

**Project:** Pemuda Laundry
**API Version:** v1
**Base URL:** `http://127.0.0.1:8000/api/v1`

---

## 1. Deskripsi

Endpoint dashboard menyediakan data ringkas yang dihitung langsung dari database.
Seluruh kartu, grafik, notifikasi, widget sidebar, dan aktivitas pada dashboard
frontend memakai endpoint ini, bukan data contoh/hardcoded.

Frontend menggunakan satu store bersama (`useDashboardStore`) sehingga seluruh
widget berbagi satu permintaan API untuk satu periode grafik.

Data dibedakan berdasarkan role pengguna yang login:

| Role | Data yang diterima |
|---|---|
| Owner | Seluruh pesanan, keuangan (termasuk pengeluaran), proses laundry, dan antar-jemput |
| Admin | Seluruh pesanan, keuangan, proses laundry, dan antar-jemput (mode pantau, read-only) |
| Karyawan | Data operasional pesanan, proses laundry, unpaid amount, antar-jemput, dan permintaan kurir dari landing page |
| Kurir | Hanya tugas miliknya dan tugas yang masih menunggu kurir; tidak ada data keuangan/pesanan |

## 2. Endpoint

```http
GET /dashboard?period=7d
```

**Permission:** `dashboard.view` (keempat role memilikinya)

**Header**

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Query parameter**

| Nama | Tipe | Wajib | Nilai | Keterangan |
|---|---|---|---|---|
| `period` | string | Tidak | `7d` (default), `30d`, `month` | Rentang `charts.revenue`. Nilai selain itu jatuh ke `7d`. |

## 3. Response

```json
{
  "success": true,
  "data": {
    "role": "owner",
    "metrics": {
      "totalOrders": 24,
      "completedOrders": 18,
      "revenue": 1250000,
      "expenses": 175000,
      "activeEmployees": 2,
      "activeCouriers": 1,
      "pendingPickups": 3,
      "newOrdersToday": 4,
      "queueWashing": 5,
      "washingInProgress": 2,
      "finishing": 1,
      "doneToday": 6,
      "unpaid": 250000,
      "waitingCourier": 2,
      "onDelivery": 1,
      "completedDeliveriesToday": 3
    },
    "charts": {
      "revenue": {
        "id": "7d",
        "label": "7 Hari",
        "categories": ["18 Nov", "19 Nov", "20 Nov", "21 Nov", "22 Nov", "23 Nov", "24 Nov"],
        "orders": [1, 3, 2, 4, 2, 5, 4],
        "revenue": [150000, 320000, 210000, 410000, 180000, 500000, 380000]
      },
      "payment_methods": [
        {
          "id": 1,
          "label": "Tunai",
          "kode": "tunai",
          "amount": 800000,
          "transactions": 8
        }
      ],
      "expenses": [
        {
          "id": "expense-1",
          "label": "Listrik",
          "amount": 125000,
          "percentage": 71
        },
        {
          "id": "expense-2",
          "label": "Air",
          "amount": 50000,
          "percentage": 29
        }
      ]
    },
    "recent": {
      "orders": [],
      "delivery_tasks": [],
      "activities": [
        {
          "id": "status-12",
          "type": "order",
          "title": "Status pesanan ORD-20261124-XXXX diperbarui",
          "description": "dicuci → dikeringkan",
          "user": "Karyawan Laundry",
          "role": "karyawan",
          "time": "2026-11-24T08:15:00+07:00"
        }
      ]
    },
    "laundry": {
      "queue": [
        {
          "id_pesanan": 4,
          "kode_pesanan": "ORD-20261123-S54P830C",
          "pelanggan": { "nama": "Nama pelanggan", "no_hp": "08xxxxxxxxxx" },
          "tanggal_masuk": "2026-11-23T22:30:00+00:00",
          "estimasi_selesai": "2026-11-24T22:30:00+00:00",
          "status_pesanan": "dicuci",
          "layanan": "Cuci Setrika",
          "jumlah_layanan": 1,
          "total_jumlah": 2,
          "satuan": "kg"
        }
      ]
    },
    "delivery": {
      "active_tasks": [],
      "history": [],
      "pickup_requests": [],
      "landing_requests": [
        {
          "id_antar_jemput": 12,
          "sumber": "landing",
          "jenis_antar_jemput": "penjemputan",
          "status_antar_jemput": "menunggu_verifikasi",
          "nama_penerima": "Budi Santoso",
          "no_hp_penerima": "081234567890",
          "alamat_tujuan": "Jl. Mawar No. 12, Bojonegoro",
          "created_at": "2026-11-24T10:15:00.000000Z"
        }
      ]
    }
  }
}
```

### Catatan metric

- `revenue`: total pembayaran dengan `status_pembayaran = berhasil`.
- `unpaid`: total tagihan pesanan bukan `dibatalkan` dikurangi pembayaran berhasil.
- `expenses`: total pengeluaran bulan berjalan dari tabel `pengeluaran`.
- `queueWashing`, `washingInProgress`, `finishing`: jumlah proses laundry dengan status
  `menunggu`, `pencucian`, dan (`pengeringan` atau `finishing`).
- `waitingCourier`: tugas antar-jemput dengan status `menunggu`.
- `onDelivery`: tugas pengantaran yang belum `selesai`/`gagal`.
- Untuk role kurir, metric keuangan/pesanan bernilai `0` dan ditambah
  `myTasks` (tugas aktif milik kurir) serta `myCompletedToday` (tugas selesai hari ini).

### Catatan charts

- `charts.revenue`: per hari. `7d` = 7 hari terakhir, `30d` = 30 hari terakhir,
  `month` = tanggal 1 sampai hari ini. `categories` memakai format `D MMM`
  (`7d`/`30d`) atau nomor tanggal (`month`).
- `charts.payment_methods`: total dan jumlah transaksi pembayaran berhasil per
  metode pembayaran (seluruh data, bukan 30 hari terakhir).
- `charts.expenses`: pengeluaran bulan berjalan dikelompokkan per `kategori`,
  beserta persentase terhadap total pengeluaran bulan tersebut.

### Catatan data terkait role

- `recent.orders`: struktur `OrderResource`, maksimal 10 pesanan terbaru. Kosong untuk kurir.
- `recent.delivery_tasks`: struktur `DeliveryTaskResource`, maksimal 5 tugas terbaru yang
  masih relevan bagi role yang login.
- `recent.activities`: gabungan riwayat status pesanan, riwayat proses laundry,
  pembayaran, dan riwayat antar-jemput (maksimal 8 entri terbaru). Untuk kurir hanya
  riwayat tugasnya sendiri dan riwayat `menunggu_verifikasi` disembunyikan.
  Tugas tanpa pesanan (landing) diberi judul `Tugas jemput AJ-<id> diperbarui`.
  Field `role` diambil dari role user yang melakukan aksi.
- `laundry.queue`: maksimal 10 pesanan aktif untuk antrean proses laundry. Kosong untuk kurir.
- `delivery.active_tasks`: tugas dengan status `menunggu`, `ditugaskan`, `menuju_lokasi`,
  `menuju_laundry`, `pakaian_sampai_laundry`, `barang_diambil`, atau `barang_diantar`.
- `delivery.history`: 10 tugas terakhir dengan status `selesai`.
- `delivery.pickup_requests`: permintaan penjemputan dengan status `menunggu`.
- `delivery.landing_requests`: permintaan dari landing page (`sumber = landing`)
  dengan status `menunggu_verifikasi`, maksimal 10 entri terbaru. Kosong untuk
  kurir. Panel karyawan memakai daftar ini untuk **Verifikasi / Tolak** dan
  tombol WhatsApp konfirmasi ke pengirim form; proses selengkapnya ada di
  [10-landing-delivery-api.md](./10-landing-delivery-api.md).

Untuk kurir, semua daftar di atas dibatasi pada tugas miliknya ditambah tugas
tanpa kurir yang masih berstatus `menunggu`. Status `menunggu_verifikasi`
sengaja tidak termasuk, sehingga kurir baru melihat tugas setelah diverifikasi.
Riwayat aktivitas kurir juga menyembunyikan transisi `menunggu_verifikasi`.

### Aksi pada widget Pesanan Terbaru

Pada dashboard karyawan, setiap baris pesanan menyediakan:

- **Hubungi pelanggan**: tautan `https://wa.me/<no_hp>` dengan pesan konfirmasi
  nomor nota. Tidak tampil bila nomor pelanggan kosong.
- **Cetak struk**: memanggil `GET /orders/{id}/receipt` dan membuka PNG di tab
  baru (atau mengunduhnya bila pop-up diblokir).
- **Panah detail**: menuju halaman detail pesanan.

Tombol **Hubungi pelanggan** juga tersedia di daftar dan detail pesanan.
