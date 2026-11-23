# Akun demo dan pemisahan dashboard

Dokumen ini menjelaskan akun testing lokal yang dibuat oleh
`AdminUserSeeder`, serta tampilan yang muncul untuk setiap role.

> Password di bawah hanya untuk testing lokal. Jangan dipakai di produksi.

## 1. Akun demo

| Role | Email | Password | Nama |
|---|---|---|---|
| Owner | `owner@owner.com` | `owner123` | Owner Laundry |
| Admin | `admin@admin.com` | `admin123` | Administrator |
| Karyawan | `karyawan@karyawan.com` | `karyawan123` | Karyawan Laundry |
| Kurir | `kurir@kurir.com` | `kurir123` | Kurir Laundry |

Jalankan ulang seeder jika akun belum ada atau ingin mengembalikan password:

```bash
cd backend
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan permission:cache-reset
```

Setelah menjalankan seeder, logout lalu login kembali agar frontend memuat
role dan permission terbaru.

## 2. Hak akses per role

| Modul | Owner | Admin | Karyawan | Kurir |
|---|---|---|---|---|
| Dashboard | Ya | Ya | Ya | Ya |
| Pengguna | Kelola | Lihat | Tidak | Tidak |
| Layanan | Lihat | Lihat | Kelola | Tidak |
| Waktu layanan | Lihat | Lihat | Kelola | Tidak |
| Metode pembayaran | Lihat | Kelola | Lihat | Tidak |
| Pesanan | Lihat | Lihat | **Buat**, ubah, ubah status | Tidak |
| Pembayaran | Lihat | Lihat | Lihat, catat | Tidak |
| Proses laundry | Lihat, ubah | Lihat | Kelola | Tidak |
| Antar-jemput | Lihat, tugaskan | Lihat | Lihat | **Kelola** |
| Laporan | Ya | Ya | Tidak | Tidak |
| Pengaturan dan role | Ya | Tidak | Tidak | Tidak |

Aturan utama:

-   **Hanya karyawan** yang memiliki permission `orders.create` (membuat
    pesanan) dan `payments.create` (mencatat pembayaran).
-   **Admin dan owner bersifat read-only untuk pesanan dan pembayaran**:
    keduanya hanya memiliki `orders.view` dan `payments.view`, sehingga hanya
    dapat melihat riwayat, bukan membuat pesanan atau mencatat pembayaran.
-   **Owner tidak dapat membuat pesanan** dan tidak dapat menambah, mengubah,
    atau menghapus layanan, waktu layanan, serta metode pembayaran. Owner
    tetap dapat mengelola akun pengguna, memantau transaksi, dan melihat
    laporan/histori.
-   **Kurir hanya memiliki akses antar-jemput** (`delivery.view` dan
    `delivery.update`), tanpa akses ke pesanan laundry, pembayaran, layanan,
    pengguna, atau laporan.

Role `admin` sengaja tidak memiliki permission mutasi apa pun. Role `owner`
tidak lagi memiliki seluruh permission; pembatasan owner diatur eksplisit di
`RolePermissionSeeder`.

## 3. Tampilan dashboard per role

| Role | Isi dashboard |
|---|---|
| Owner | Pendapatan, pengeluaran, metode pembayaran, pesanan terbaru, histori aktivitas karyawan, histori kurir, dan antar-jemput menunggu. |
| Admin | Mode pemantauan read-only: pendapatan, pembayaran, pesanan terbaru, riwayat aktivitas, dan riwayat kurir. |
| Karyawan | Aksi operasional: buat pesanan, antrean proses laundry, aktivitas proses, pesanan terbaru, pembayaran, dan antar-jemput. |
| Kurir | Tugas antar-jemput, aktivitas tugas, dan riwayat tugas. Tidak ada modul pesanan laundry. |

Dashboard selalu mengikuti role akun yang sedang login. Tidak ada pratinjau
role, sehingga setiap akun hanya melihat tampilannya sendiri.

## 4. Menu sidebar per role

- **Owner**: dashboard, riwayat pesanan, layanan, waktu layanan, metode
  pembayaran, riwayat pembayaran, pengguna, dan laporan.
- **Admin**: dashboard, riwayat pesanan, layanan, waktu layanan, metode
  pembayaran, riwayat pembayaran, pengguna, dan laporan.
- **Karyawan**: dashboard, pesanan, layanan, waktu layanan, metode pembayaran,
  riwayat pembayaran, proses laundry, dan antar-jemput.
- **Kurir**: dashboard dan antar-jemput.

Menu yang halamannya belum dibuat tetap tampil sebagai penanda "Segera",
tetapi hanya untuk role yang memiliki permission modul tersebut.

## 5. Menambah dan mengedit waktu layanan

Waktu layanan dapat dikelola melalui:

1. Menu **Layanan → Waktu Layanan** pada sidebar.
2. Tombol **Kelola waktu layanan** pada halaman Layanan.
3. Tautan **Tambah / edit waktu** pada field Waktu layanan di form layanan.

Halaman Waktu Layanan menyediakan tombol tambah, aksi edit, dan hapus sesuai
permission `service-times.*`.
