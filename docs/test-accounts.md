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
| Pengguna | Ya | Lihat, tambah, ubah | Tidak | Tidak |
| Layanan | Ya | Ya | Lihat | Tidak |
| Waktu layanan | Ya | Ya | Lihat | Tidak |
| Pesanan | Ya | Ya | Ya | Tidak |
| Pembayaran | Ya | Ya | Ya | Tidak |
| Proses laundry | Ya | Ya | Ya | Tidak |
| Antar-jemput | Ya | Ya | Lihat | Ya |
| Laporan | Ya | Ya | Tidak | Tidak |
| Pengaturan dan role | Ya | Tidak | Tidak | Tidak |

Role `admin` sengaja tidak memiliki `users.delete`, `settings.manage`, dan
`roles.manage`. Role `owner` memiliki seluruh permission.

## 3. Tampilan dashboard per role

| Role | Isi dashboard |
|---|---|
| Owner | Ringkasan pendapatan, metode pembayaran, pesanan terbaru, aktivitas, dan antar-jemput menunggu. |
| Admin | Antrean proses laundry, pesanan terbaru, pembayaran, aktivitas operasional, dan antar-jemput menunggu. |
| Karyawan | Antrean proses laundry, aktivitas proses, dan pesanan terbaru. |
| Kurir | Tugas antar-jemput, aktivitas tugas, dan riwayat tugas. |

Dashboard selalu mengikuti role akun yang sedang login. Tidak ada lagi
pratinjau role, sehingga setiap akun hanya melihat tampilannya sendiri.

## 4. Menu sidebar per role

- **Owner**: semua menu yang tersedia.
- **Admin**: dashboard, pesanan, pelanggan, proses laundry, antar-jemput,
  layanan, waktu layanan, pembayaran, pengguna, dan laporan.
- **Karyawan**: dashboard, pesanan, pelanggan, proses laundry, antar-jemput,
  layanan, waktu layanan, dan pembayaran.
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
