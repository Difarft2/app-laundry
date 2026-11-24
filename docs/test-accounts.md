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
| Pesanan | Lihat, ubah status, hapus | Lihat | **Buat**, ubah, ubah status, scan QR, cetak struk & antar | Tidak |
| Pembayaran | Lihat | Lihat | Lihat, catat | Tidak |
| Pengeluaran | Lihat, catat/ubah/hapus | Lihat | Tidak | Tidak |
| Proses laundry | Tidak | Lihat | Kelola | Tidak |
| Antar-jemput | Lihat, tugaskan, verifikasi landing | Lihat, verifikasi landing | Lihat, **verifikasi/tolak landing** | **Terima tugas & kelola status** |
| Laporan | Ya | Ya | Tidak | Tidak |
| Pengaturan aplikasi | Tidak | Ya | Tidak | Tidak |
| Role | Ya | Tidak | Tidak | Tidak |

Aturan utama:

-   **Hanya karyawan** yang memiliki permission `orders.create` (membuat
    pesanan) dan `payments.create` (mencatat pembayaran). Tombol **Scan QR
    pesanan** dan **Cetak struk** juga hanya muncul untuk karyawan.
-   **Admin bersifat read-only untuk pesanan dan pembayaran**: hanya memiliki
    `orders.view` dan `payments.view`. Admin adalah satu-satunya role dengan
    `settings.manage`, sehingga dapat mengubah nama, versi, logo, dan nomor
    laundry lewat menu **Pengaturan**.
-   **Owner tidak dapat membuat atau mengubah data pesanan**, tetapi dapat
    melihat, mengubah status, dan menghapus pesanan. Untuk pembayaran, owner
    hanya dapat melihat riwayat (tanpa mencatat).
-   **Owner tidak dapat membuat pesanan** dan tidak dapat menambah, mengubah,
    atau menghapus layanan, waktu layanan, serta metode pembayaran. Owner
    tetap dapat mengelola akun pengguna, memantau transaksi, dan melihat
    laporan/histori.
-   **Owner adalah satu-satunya role dengan `finance.manage`**: dapat mencatat,
    mengubah, dan menghapus pengeluaran. Admin hanya memiliki `finance.view`
    (read-only).
-   **Kurir hanya memiliki akses antar-jemput** (`delivery.view` dan
    `delivery.update`), tanpa akses ke pesanan laundry, pembayaran, pengeluaran,
    layanan, pengguna, atau laporan.
-   Pengaturan aplikasi bisa **dilihat** semua role lewat `GET /settings`
    (untuk branding), tetapi hanya admin yang bisa mengubahnya.

Footer seluruh dashboard menampilkan tahun berjalan, nama aplikasi, dan versi
aplikasi. Widget sidebar **Status Outlet** digantikan **Status App** yang
menampilkan status aktif, versi aplikasi, versi build, dan nomor laundry.

Role `admin` tidak memiliki permission mutasi pesanan/pembayaran (read-only),
tetapi tetap dapat mengelola master data layanan, waktu layanan, dan metode
pembayaran. Role `owner` tidak memiliki seluruh permission; pembatasan owner
diatur eksplisit di `RolePermissionSeeder`.

## 3. Tampilan dashboard per role

| Role | Isi dashboard |
|---|---|
| Owner | Pendapatan, pengeluaran, metode pembayaran, pesanan terbaru, histori aktivitas karyawan, histori kurir, dan antar-jemput menunggu. |
| Admin | Mode pemantauan read-only: pendapatan, pembayaran, pesanan terbaru, riwayat aktivitas, dan riwayat kurir. |
| Karyawan | Aksi operasional: buat pesanan, antrean proses laundry, aktivitas proses, pesanan terbaru, pembayaran, antar-jemput, dan panel permintaan kurir dari website (verifikasi/tolak). |
| Kurir | Tugas antar-jemput, aktivitas tugas, dan riwayat tugas. Tidak ada modul pesanan laundry. |

Dashboard selalu mengikuti role akun yang sedang login. Tidak ada pratinjau
role, sehingga setiap akun hanya melihat tampilannya sendiri.

## 4. Menu sidebar per role

- **Owner**: dashboard, riwayat pesanan, layanan, waktu layanan, metode
  pembayaran, riwayat pembayaran, pengguna, dan laporan.
- **Admin**: dashboard, riwayat pesanan, layanan, waktu layanan, metode
  pembayaran, riwayat pembayaran, pengguna, laporan, dan pengaturan aplikasi.
- **Karyawan**: dashboard, pesanan, proses laundry, dan antar-jemput. Pada grup
  **Histori**, hanya **Riwayat Pembayaran** yang ditampilkan; menu Layanan,
  Waktu Layanan, dan Metode Pembayaran disembunyikan.
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
