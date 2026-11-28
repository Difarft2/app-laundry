PRODUCT REQUIREMENTS DOCUMENT
PEMUDA LAUNDRY
Sistem Manajemen Operasional Laundry Berbasis Web
Informasi            Detail

Versi dokumen        1.0 Tanggal              16 November 2026 Tujuan dokumen       Acuan implementasi untuk tim frontend Platform             Web Application Backend / Database   Laravel REST API / MySQL atau MariaDB Pengguna             Owner, Admin, Karyawan, Kurir
Dokumen ini menjelaskan ruang lingkup produk, aturan bisnis, hak akses, kebutuhan antarmuka, integrasi API, dan kriteria penerimaan. Detail endpoint dan bentuk payload final mengikuti kontrak API yang disepakati tim frontend dan backend.
Daftar Isi
Ringkasan Produk dan Tujuan
Ruang Lingkup MVP
Role dan Hak Akses
Struktur Navigasi
Spesifikasi Modul
Alur Operasional
UI/UX Requirements
Integrasi Backend
Struktur Frontend
Aturan Bisnis dan Validasi
Acceptance Criteria
Tahapan Pengerjaan
Definition of Done dan Catatan Integrasi
1. Ringkasan Produk dan Tujuan
Pemuda Laundry Management System adalah aplikasi web internal untuk membantu pengelolaan operasional laundry, mulai dari pencatatan pelanggan dan pesanan, pemantauan proses pengerjaan, pembayaran, hingga layanan antar-jemput. Aplikasi ditujukan untuk menggantikan pencatatan manual dan menyediakan data operasional yang lebih terstruktur.
Tujuan produk
Mendigitalisasi pencatatan transaksi dan operasional laundry.
Mengurangi kesalahan pencatatan pesanan, layanan, dan pembayaran.
Mempermudah karyawan memantau antrean dan tahapan pengerjaan.
Mempermudah kurir menjalankan tugas penjemputan dan pengantaran.
Menyediakan ringkasan operasional dan laporan bagi Owner/Admin.
2. Ruang Lingkup MVP

Modul                               Cakupan MVP

Authentication                      Login, logout, profil, pengelolaan sesi.
Dashboard                           Ringkasan aktivitas sesuai role.
Pelanggan                           Tambah, lihat, ubah, hapus sesuai permission; pencarian.
Layanan & waktu layanan             Katalog kiloan/satuan, harga, estimasi dan status aktif.
Pesanan                             Pembuatan nota, detail, pencarian, filter, perubahan sesuai aturan.
Proses laundry                      Antrean, pembaruan status, timeline dan riwayat.
Pembayaran                          Pencatatan pembayaran bertahap, status pelunasan, riwayat.
Antar-jemput                        Permintaan, penugasan kurir, pembaruan status, riwayat.
Pengguna                            Pengelolaan akun dan role sesuai permission.
Laporan                             Ringkasan transaksi, pesanan, pembayaran, dan antar-jemput.
Di luar prioritas MVP
Aplikasi mobile native dan portal akun pelanggan.
Pelacakan GPS real-time.
Payment gateway otomatis dan notifikasi WhatsApp otomatis.
Inventaris bahan laundry dan akuntansi kompleks.
3. Role dan Hak Akses
Aplikasi memiliki empat role: Owner, Admin, Karyawan, dan Kurir. Matriks berikut adalah rancangan kebutuhan antarmuka. Permission final harus mengikuti otorisasi backend; backend tetap menjadi sumber validasi utama.
Fitur                          Owner   Admin   Karyawan           Kurir

Dashboard                         Ya      Ya         Ya              Ya Melihat pelanggan                 Ya      Ya         Ya             --- Mengelola pelanggan               Ya      Ya        ---             --- Melihat/mengelola layanan         Ya      Ya      Lihat             --- Membuat pesanan                   Ya      Ya         Ya             --- Melihat pesanan                   Ya      Ya         Ya   Tugas sendiri Memperbarui proses laundry        Ya      Ya         Ya             --- Mengelola pembayaran              Ya      Ya         Ya             --- Mengelola antar-jemput            Ya      Ya        ---             --- Menjalankan tugas kurir          ---     ---        ---              Ya Mengelola pengguna                Ya    Ya*        ---             --- Melihat laporan                   Ya      Ya        ---             ---
Pengelolaan pengguna oleh Admin hanya jika diizinkan permission backend.
4. Struktur Navigasi
Owner / Admin   Karyawan                  Kurir

Dashboard       Dashboard                 Dashboard Pesanan         Pesanan                   Tugas Antar-Jemput Pelanggan       Proses Laundry            Riwayat Tugas Layanan         Pelanggan (sesuai izin)   Profil Waktu Layanan   Profil
Pembayaran
Antar-Jemput
Pengguna
Laporan
Profil
Gunakan sidebar pada desktop dan drawer pada perangkat mobile. Menu yang tidak memiliki izin tidak ditampilkan. Route guard frontend hanya membantu pengalaman pengguna dan tidak menggantikan pemeriksaan permission backend.
5. Spesifikasi Modul
5.1 Authentication
Halaman login memuat identitas Pemuda Laundry, input email/username sesuai kontrak API, password, tombol tampil/sembunyikan password, tombol login, loading, dan pesan error.
Halaman internal hanya dapat diakses setelah autentikasi berhasil.
Role pengguna diperoleh dari respons backend.
Logout mengakhiri sesi sesuai mekanisme backend dan mengarahkan pengguna ke halaman login.
5.2 Dashboard
Komponen umum: total pesanan, pesanan baru, pesanan dalam proses, pesanan selesai, pesanan belum lunas, permintaan antar-jemput menunggu, daftar pesanan terbaru, dan ringkasan aktivitas harian.
Owner/Admin dapat melihat ringkasan pendapatan, jumlah transaksi, grafik pendapatan, dan metode pembayaran jika endpoint tersedia. Karyawan melihat antrean dan pekerjaan yang perlu diselesaikan. Kurir melihat tugas jemput dan antar yang aktif serta riwayat tugas.
5.3 Pelanggan
Sediakan tabel pelanggan, pencarian nama/nomor telepon, tambah pelanggan, detail, edit, dan hapus sesuai permission. Data dasar meliputi nama, nomor telepon, alamat, serta informasi tambahan bila disediakan backend. Validasi wajib mengikuti aturan API.
5.4 Layanan dan Waktu Layanan

Jenis                   Layanan                 Finishing

Kiloan                  Cuci Setrika            Setrika
Kiloan                  Cuci Kering             Lipat
Kiloan                  Cuci Basah              Lipat
Kiloan                  Setrika                 Setrika
Kiloan                  Kering Saja             Lipat
Satuan                  Barang per item dari    Mengikuti konfigurasi katalog                 layanan
Katalog satuan dapat mencakup jas, bedcover, boneka, sepatu, dan barang lain sesuai konfigurasi. Harga dan satuan harga (misalnya per kg atau per item), status aktif, serta daftar layanan harus berasal dari API; jangan menanamkan harga permanen di frontend.
Aturan Express 4 Jam
Hanya berlaku untuk layanan kiloan. Estimasi 4 jam dengan biaya tambahan Rp15.000/kg. Frontend harus membatasi pilihan Express berdasarkan layanan yang dipilih dan menampilkan perhitungan sesuai respons/aturan backend.
5.5 Manajemen Pesanan
Daftar pesanan menampilkan nomor nota, pelanggan, layanan, tanggal masuk, estimasi selesai, total biaya, status pengerjaan, status pembayaran, dan aksi detail. Sediakan pencarian nomor nota/nama pelanggan, filter status, filter pembayaran, filter tanggal, serta pagination backend.
Form pembuatan pesanan dibagi menjadi:
(A) Pilih pelanggan atau tambah pelanggan baru.
(B) Pilih satu layanan dan waktu layanan.
(C) Masukkan berat untuk kiloan atau jumlah item untuk satuan.
(D) Catatan/informasi barang.
(E) Opsi antar-jemput.
(F) Pembayaran awal dan metode.
(G) Ringkasan dan konfirmasi nota.
Aturan nota
Satu nota hanya boleh memiliki satu layanan. Jika pelanggan memesan dua layanan berbeda, buat dua nota terpisah. Jangan menggabungkan layanan berbeda dalam satu nota.
Detail pesanan menampilkan informasi nota, pelanggan, layanan, berat/jumlah item, waktu layanan, estimasi selesai, total biaya, total pembayaran, sisa tagihan, status laundry, riwayat proses, riwayat pembayaran, serta informasi antar-jemput bila ada. Aksi ubah, perbarui status, tambah pembayaran, lihat riwayat, dan cetak nota ditampilkan sesuai permission.
5.6 Proses Laundry
Status utama              Makna

Menunggu giliran dicuci   Pesanan tercatat dan menunggu antrean. Dicuci                    Proses pencucian berlangsung. Dikeringkan               Proses pengeringan berlangsung. Finishing                 Barang dilipat atau disetrika sesuai layanan. Pesanan selesai           Tahapan pengerjaan yang diperlukan telah selesai.
Status merupakan tahapan proses, bukan daftar tahapan wajib untuk semua layanan. Cuci Basah melewati pengeringan; Kering Saja tidak melalui pencucian; Setrika tidak melalui pencucian/pengeringan; layanan lain mengikuti alur yang telah disepakati. Backend menentukan transisi valid. Frontend menampilkan timeline/progress berdasarkan status aktual dan riwayat.
5.7 Pembayaran
Satu pesanan dapat memiliki beberapa transaksi pembayaran. Tampilkan total tagihan, total dibayar, sisa tagihan, riwayat pembayaran, metode, tanggal, dan nominal. Form pembayaran memuat nomor nota, total tagihan, sudah dibayar, sisa tagihan, nominal pembayaran saat ini, dan metode pembayaran.
Status          Keterangan

Belum Dibayar   Belum ada pembayaran yang tercatat. Belum Lunas     Sudah ada pembayaran, tetapi masih ada sisa tagihan. Lunas           Akumulasi pembayaran memenuhi total tagihan.
Pembayaran tidak boleh melebihi sisa tagihan. Status pelunasan mengikuti akumulasi transaksi. Status pembayaran dan status proses laundry harus ditampilkan terpisah dan tidak saling mengubah secara otomatis.
5.8 Antar-Jemput
Modul antar-jemput terpisah dari proses laundry. Daftar tugas memuat nomor tugas/nota, pelanggan, alamat, jenis tugas, kurir, status, waktu, dan detail. Sediakan filter berdasarkan status dan penugasan.

Status tugas                        Makna

Menunggu kurir                      Permintaan dibuat, belum ada kurir.
Kurir ditugaskan                    Kurir telah dipilih.
Menuju lokasi                       Kurir sedang menuju lokasi.
Barang diambil / Barang diantar     Aktivitas sesuai jenis tugas sedang/baru dilakukan.
Selesai                             Tugas antar-jemput telah diselesaikan.
Permintaan dapat dibuat tanpa kurir. Admin dapat menugaskan kurir kemudian. Kurir hanya melihat tugas miliknya, alamat dan kontak pelanggan, jenis tugas, catatan, serta aksi mulai perjalanan, konfirmasi barang diambil/diantar, dan selesaikan tugas. Setiap perubahan dicatat dalam riwayat. Penyelesaian tugas tidak otomatis mengubah status laundry.
5.9 Pengguna dan Laporan
Pengguna: daftar akun, tambah, edit, nonaktifkan, pengaturan role, serta perubahan/reset password sesuai kemampuan backend. Role yang tersedia adalah Owner, Admin, Karyawan, dan Kurir. Jangan menyediakan pembuatan role baru jika backend hanya menyediakan empat role tersebut.
Laporan untuk Owner/Admin: transaksi dan pendapatan, pembayaran diterima, tagihan belum lunas, jumlah pesanan berdasarkan status/layanan, serta ringkasan tugas antar-jemput. Filter meliputi hari ini, minggu ini, bulan ini, dan rentang tanggal. Ekspor hanya jika endpoint mendukung.
6. Alur Operasional
Alur umum: pengguna login → sistem memuat dashboard sesuai role → pelanggan dipilih/dibuat → pesanan dibuat dengan satu layanan → proses laundry dan pembayaran berjalan sebagai proses terpisah → bila diperlukan dibuat tugas antar-jemput → tugas kurir diselesaikan → pesanan dan riwayat dapat dipantau.
Pemisahan status
Status pengerjaan laundry, status pembayaran, dan status antar-jemput adalah tiga hal yang berbeda. Jangan menyatukan ketiganya menjadi satu status pesanan atau menganggap satu status otomatis menyelesaikan proses lainnya.
7. UI/UX Requirements
Desain dashboard modern, minimalis, profesional, bersih, dan mudah dipahami.
Responsif untuk desktop, tablet, dan mobile.
Sidebar desktop dan drawer mobile.
Kurir mendapatkan tampilan mobile yang sederhana dan mudah digunakan.
Gunakan warna dan badge konsisten untuk membantu identifikasi status.
Hindari animasi berlebihan dan prioritaskan keterbacaan.
Form sederhana dapat memakai modal/drawer; informasi kompleks memakai halaman detail.
Semua aksi penting memiliki konfirmasi yang sesuai.
Komponen reusable yang perlu disiapkan
Sidebar, navbar/header, breadcrumb, statistic card, data table, search, filter, pagination.
Form input, select, date picker, confirmation modal, status badge, timeline.
Toast notification, loading skeleton, empty state, error state.
Status                                     Warna yang disarankan

Menunggu giliran dicuci / Menunggu kurir   Abu-abu Dicuci / Dikeringkan / Kurir ditugaskan    Biru Finishing / Menuju lokasi                  Kuning Pesanan selesai / Tugas selesai / Lunas    Hijau Belum lunas                                Merah
Warna hanya untuk visualisasi. Nilai status aktual harus berasal dari backend.
8. Integrasi Backend
Laravel API adalah sumber data utama. Semua data dinamis diambil dari API. Frontend harus mengikuti kontrak request/response, mekanisme token, pagination, validasi, dan permission yang disediakan backend. Jangan mengasumsikan endpoint, nama field, relasi, atau format token sebelum kontrak tersedia.
HTTP        Penanganan frontend

200 / 201   Tampilkan keberhasilan dan perbarui data. 401         Sesi tidak valid atau belum login; tangani autentikasi. 403         Tampilkan informasi tidak memiliki izin. 404         Tampilkan data tidak ditemukan. 422         Tampilkan kesalahan validasi dari backend. 500         Tampilkan pesan kesalahan server dan opsi mencoba kembali.
Resource backend yang relevan
Users; Layanan; Waktu Layanan; Pesanan; Pembayaran; Metode Pembayaran.
Proses Laundry; Riwayat Proses Laundry; Riwayat Status Pesanan.
Antar Jemput; Riwayat Antar Jemput.
Role/permission menggunakan Spatie Permission. Endpoint, nama field, bentuk relasi response, dan format autentikasi harus disepakati melalui dokumentasi API dan contoh JSON dari backend.
9. Struktur Frontend yang Disarankan
Struktur berikut merupakan rekomendasi modular; tim dapat menyesuaikan dengan framework yang dipilih tanpa mengubah pemisahan tanggung jawab.
src/
├── assets/
├── components/
│   ├── ui/
│   ├── layout/
│   ├── dashboard/
│   ├── orders/
│   ├── customers/
│   ├── payments/
│   └── courier/
├── layouts/
│   ├── AuthLayout
│   └── DashboardLayout
├── pages/
│   ├── auth/
│   ├── dashboard/
│   ├── customers/
│   ├── services/
│   ├── orders/
│   ├── laundry-process/
│   ├── payments/
│   ├── pickup-delivery/
│   ├── users/
│   └── reports/
├── services/
│   └── api/
├── composables/
├── stores/
├── middleware/
├── types/
└── utils/
Pisahkan API service dari komponen UI.
Gunakan komponen reusable dan tipe data yang jelas.
Terapkan route guard berdasarkan autentikasi/role; tetap validasi permission di backend.
Hindari satu komponen besar yang menangani seluruh logika aplikasi.
10. Aturan Bisnis dan Validasi Wajib
Satu nota hanya untuk satu layanan.
Layanan kiloan menggunakan berat; layanan satuan menggunakan jumlah item.
Express 4 Jam hanya untuk kiloan dan tambahan Rp15.000/kg.
Finishing mengikuti jenis layanan; tahapan yang tidak relevan dilewati sesuai aturan backend.
Pembayaran dapat dilakukan beberapa kali dan tidak boleh melebihi sisa tagihan.
Status pembayaran, status laundry, dan status antar-jemput harus terpisah.
Kurir dapat ditugaskan setelah permintaan dibuat; tugas kurir dibatasi pada penugasan miliknya.
Setiap perubahan status harus tercatat dalam riwayat.
Semua harga, layanan, metode pembayaran, dan konfigurasi berasal dari backend.
Pengguna hanya dapat melakukan aksi sesuai permission.
11. Acceptance Criteria

ID                      Kriteria penerimaan       Prioritas

AC-01                   Login dan logout          Must berfungsi.
AC-02                   Navigasi dan aksi         Must menyesuaikan
role/permission.
AC-03                   CRUD pelanggan berjalan   Must sesuai izin.
AC-04                   Pembuatan pesanan         Must berjalan dan satu nota
satu layanan.
AC-05                   Layanan kiloan dan satuan Must didukung.
AC-06                   Express hanya tersedia    Must untuk layanan kiloan.
AC-07                   Proses laundry dan        Must riwayat status dapat
ditampilkan/diperbarui.
AC-08                   Pembayaran bertahap dan   Must status pelunasan akurat.
AC-09                   Permintaan dan penugasan  Must antar-jemput berjalan.
AC-10                   Kurir dapat memperbarui   Must tugas yang ditugaskan.
AC-11                   Riwayat antar-jemput      Must dapat ditampilkan.
AC-12                   Dashboard menampilkan     Must data API.
AC-13                   Aplikasi responsif dan    Must memiliki
loading/error/empty
state.
AC-14                   Laporan dapat difilter    Should berdasarkan tanggal.
12. Tahapan Pengerjaan

Tahap                               Pekerjaan

1. Foundation                      Setup proyek, routing, layout, komponen UI, autentikasi, role-based navigation.
2. Master Data                     Pelanggan, layanan, waktu layanan, metode pembayaran, pengguna.
3. Manajemen Pesanan               Daftar pesanan, pembuatan nota, detail, pencarian dan filter.
4. Operasional Laundry             Antrean, perubahan status, finishing, dan riwayat.
5. Pembayaran                      Transaksi, pembayaran bertahap, status pelunasan.
6. Antar-Jemput                    Penugasan kurir, halaman tugas, pembaruan status, riwayat.
7. Dashboard & Laporan             Statistik, grafik, filter laporan.
8. Testing & Integrasi             Pengujian role, aturan bisnis, API, dan responsive design.
13. Definition of Done
UI telah diimplementasikan sesuai desain dan responsif.
API telah terintegrasi; tidak ada data dummy permanen.
Validasi form dan penanganan error berjalan.
Loading, empty state, dan error state tersedia.
Permission dan route guard diterapkan sesuai kontrak.
Aturan bisnis pada PRD dipenuhi.
Fitur diuji pada desktop dan mobile.
Perubahan data dan status tersimpan melalui API.
14. Koordinasi Frontend dan Backend

Kebutuhan                           Tanggung jawab utama

Endpoint login/logout, token,       Backend response user/role
Endpoint pelanggan, layanan,        Backend pesanan, pembayaran
Endpoint proses laundry,            Backend antar-jemput, dashboard, laporan
Format request/response,            Backend pagination, validasi, permission
Layout, komponen UI, interaksi,     Frontend responsive design
Route guard dan validasi tampilan   Frontend form
Integrasi, pengujian alur bisnis,   Frontend + Backend perbaikan bersama
Prasyarat integrasi
Sebelum integrasi penuh, backend perlu menyediakan dokumentasi endpoint, contoh JSON request/response, format autentikasi, daftar status yang valid, dan aturan permission. PRD ini tidak menetapkan nama endpoint yang belum dikonfirmasi.
15. Catatan Akhir untuk Tim Frontend
PRD ini menjadi acuan utama implementasi frontend Pemuda Laundry. Jangan mengubah aturan bisnis inti---terutama satu nota satu layanan, pembayaran bertahap, pemisahan status laundry/pembayaran/antar-jemput, aturan finishing, batasan Express 4 Jam, dan hak akses role---tanpa koordinasi dengan tim backend/product owner.
Jika terdapat ketidaksesuaian antara tampilan yang direncanakan dan kemampuan API, diskusikan terlebih dahulu dan sepakati perubahan kontrak sebelum implementasi. Tujuan akhir adalah menghasilkan aplikasi operasional yang konsisten, mudah digunakan, dan sesuai proses bisnis Pemuda Laundry.

PEMUDA LAUNDRY · PRODUCT REQUIREMENTS DOCUMENT · VERSION 1.0
