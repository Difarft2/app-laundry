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
| Karyawan | Data operasional pesanan, proses laundry, unpaid amount, dan antar-jemput |
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
      "pickup_requests": []
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
  riwayat tugasnya sendiri. Field `role` diambil dari role user yang melakukan aksi.
- `laundry.queue`: maksimal 10 pesanan aktif untuk antrean proses laundry. Kosong untuk kurir.
- `delivery.active_tasks`: tugas dengan status `menunggu`, `ditugaskan`, `menuju_lokasi`,
  `barang_diambil`, atau `barang_diantar`.
- `delivery.history`: 10 tugas terakhir dengan status `selesai`.
- `delivery.pickup_requests`: permintaan penjemputan dengan status `menunggu`.

Untuk kurir, semua daftar di atas dibatasi pada tugas miliknya ditambah tugas
tanpa kurir yang masih berstatus `menunggu`.
