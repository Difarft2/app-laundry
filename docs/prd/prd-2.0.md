# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## PEMUDA LAUNDRY
### Business System Requirements

| Informasi | Detail |
|---|---|
| Nama Produk | Pemuda Laundry |
| Dokumen | Product Requirements Document |
| Versi | 2.0.0 |
| Tanggal | 28 November 2026 |
| Status | Draft kebutuhan bisnis |
| Fokus | Sistem operasional, keuangan, dan Business Intelligence |

---

# 1. Pendahuluan

## 1.1 Latar Belakang

Pemuda Laundry merupakan sistem pengelolaan usaha laundry yang dirancang untuk membantu operasional bisnis laundry keluarga secara menyeluruh.

Sistem mencakup proses penerimaan pakaian, pengelolaan pesanan, pengerjaan laundry, pembayaran, layanan antar-jemput, hingga pakaian diterima kembali oleh pelanggan.

Selain membantu kegiatan operasional, sistem juga harus memberikan informasi yang transparan kepada pemilik usaha mengenai kondisi bisnis, keuangan, aktivitas pesanan, dan perkembangan usaha.

## 1.2 Tujuan Produk

1. Mengelola seluruh proses operasional laundry secara terstruktur.
2. Mempermudah pengelolaan pesanan dan proses pengerjaan.
3. Menyediakan informasi pembayaran dan piutang secara akurat.
4. Mencatat seluruh pengeluaran usaha.
5. Menyediakan laporan bisnis dan keuangan berdasarkan periode.
6. Membantu Owner mengevaluasi kondisi usaha melalui data aktual.
7. Menyediakan informasi yang transparan dan dapat ditelusuri hingga transaksi asalnya.

## 1.3 Sasaran Pengguna

Sistem memiliki empat kelompok pengguna utama:

- Owner
- Admin
- Karyawan
- Kurir

Pembagian kewenangan mengikuti sistem Role & Permission yang telah tersedia. PRD ini tidak dimaksudkan untuk mengganti pembagian hak akses yang sudah berjalan.

---

# 2. Ruang Lingkup Sistem

## 2.1 Ruang Lingkup Utama

Pemuda Laundry mencakup:

1. Penerimaan pesanan.
2. Pengelolaan data pelanggan dan pesanan.
3. Pengelolaan layanan dan harga.
4. Pengelolaan antrean laundry.
5. Proses produksi dan penyelesaian pakaian.
6. Layanan penjemputan dan pengantaran.
7. Pembayaran dan pelunasan.
8. Pengembalian dana.
9. Pencatatan pengeluaran.
10. Pengelolaan diskon.
11. Pelacakan status pesanan.
12. Dashboard dan Business Intelligence.
13. Laporan operasional dan keuangan.
14. Ekspor laporan PDF dan Excel.

## 2.2 Prinsip Pengembangan

- Mempertahankan proses bisnis yang sudah berjalan.
- Tidak menghilangkan fitur yang sudah tersedia tanpa keputusan bisnis.
- Informasi transaksi harus transparan.
- Data keuangan harus dapat ditelusuri.
- Status produksi dan status antar-jemput harus dibedakan.
- Nilai pesanan tidak boleh disamakan dengan uang yang sudah diterima.
- Informasi kinerja bisnis tidak diarahkan menjadi sistem pemeringkatan individu karyawan atau kurir.

---

# 3. Aktor dan Tanggung Jawab

## 3.1 Owner

Owner merupakan pemilik usaha dan pengambil keputusan utama.

Kebutuhan Owner:
- Melihat kondisi keseluruhan bisnis.
- Memantau pesanan masuk dan selesai.
- Memantau pembayaran dan piutang.
- Melihat seluruh pengeluaran.
- Melihat nilai transaksi dan diskon.
- Memantau antrean laundry.
- Melihat layanan yang paling banyak digunakan.
- Mengakses laporan historis.
- Mengekspor laporan.
- Menyetujui dan memproses pengembalian dana.

Owner tidak membutuhkan sistem penilaian atau pemeringkatan kinerja individu karyawan dan kurir.

## 3.2 Admin

Admin bertanggung jawab atas kegiatan administrasi dan pengelolaan operasional sesuai kewenangan yang telah ditentukan dalam sistem Role & Permission.

## 3.3 Karyawan

Karyawan bertanggung jawab atas pengerjaan pakaian dan proses produksi laundry.

Satu karyawan dapat menangani seluruh tahapan produksi, mulai dari pencucian sampai finishing.

Tidak diwajibkan adanya pembagian karyawan berdasarkan tahapan produksi.

## 3.4 Kurir

Kurir bertanggung jawab atas tugas penjemputan dan pengantaran pakaian.

Kurir dapat menerima tugas penjemputan dan pengantaran yang berbeda, termasuk untuk pesanan yang sama.

---

# 4. Proses Bisnis Penerimaan Pesanan

## 4.1 Jalur Penerimaan

Terdapat dua jalur penerimaan pakaian.

### A. Pelanggan Datang Langsung (Drop-off)

1. Pelanggan datang ke outlet laundry.
2. Pelanggan menyerahkan pakaian.
3. Pakaian ditimbang dan diperiksa di outlet.
4. Layanan dan ketentuan pengerjaan ditentukan.
5. Harga dan estimasi penyelesaian diinformasikan kepada pelanggan.
6. Pesanan dibuat dan dicatat dalam sistem.

### B. Pelanggan Meminta Penjemputan (Pickup)

1. Pelanggan mengajukan permintaan penjemputan.
2. Permintaan penjemputan dicatat.
3. karyawan mengatur penugasan kurir.
4. Kurir mengambil pakaian dari pelanggan.
5. Pakaian dibawa ke outlet laundry.
6. Pakaian ditimbang dan diperiksa di outlet.
7. Informasi layanan, berat, harga, dan estimasi disampaikan kepada pelanggan.
8. Pesanan laundry baru dibuat setelah pakaian tiba dan diperiksa di outlet.

Ketentuan penting:
- Permintaan penjemputan bukan otomatis merupakan pesanan laundry.
- Kurir tidak melakukan penimbangan atau pemeriksaan akhir di rumah pelanggan.
- Penimbangan dan pemeriksaan dilakukan di outlet.

## 4.2 Transparansi Pesanan

Informasi pesanan harus disampaikan secara transparan kepada pelanggan, meliputi:

- Jenis layanan.
- Berat atau jumlah pakaian.
- Harga layanan.
- Diskon apabila ada.
- Total tagihan.
- Estimasi penyelesaian.
- Informasi tambahan yang relevan terhadap pesanan.

Pelanggan dapat membatalkan pesanan selama masih memenuhi aturan pembatalan.

## 4.3 Pemeriksaan Kondisi Pakaian

Apabila ditemukan pakaian rusak atau memiliki kondisi khusus:

1. Owner atau karyawan menghubungi pelanggan.
2. Kondisi pakaian dibicarakan dengan pelanggan.
3. Tindakan selanjutnya ditentukan melalui kesepakatan bersama.

Sistem tidak boleh mengasumsikan adanya persetujuan otomatis dari pelanggan.

## 4.4 Ketentuan Nota

- Satu layanan direpresentasikan sebagai satu nota atau pesanan.
- Jika pelanggan menggunakan beberapa layanan, masing-masing layanan dicatat sebagai pesanan terpisah sesuai aturan bisnis yang sudah berlaku.

---

# 5. Pengelolaan Layanan Laundry

## 5.1 Jenis Layanan dan Estimasi

| Layanan | Estimasi |
|---|---|
| Reguler | 48–72 jam |
| Express 1 Hari | 24 jam |
| Express 4 Jam | 4 jam |

Express 4 Jam hanya tersedia untuk layanan kiloan dengan harga Rp15.000/kg.

Harga dan ketentuan layanan lain mengikuti konfigurasi layanan yang sudah berlaku dalam sistem.

## 5.2 Aturan Finishing

| Jenis Layanan | Proses Akhir |
|---|---|
| Cuci Setrika | Disetrika |
| Setrika | Disetrika |
| Cuci Kering | Dilipat |
| Cuci Basah | Dicuci kemudian dilipat |
| Kering Saja | Dikeringkan kemudian dilipat |

---

# 6. Proses Produksi Laundry

## 6.1 Alur Status Pesanan

Alur utama produksi:

MENUNGGU → GILIRAN DICUCI → DICUCI → DIKERINGKAN → FINISHING → SELESAI

Tahapan finishing mengikuti jenis layanan:
- Dilipat.
- Disetrika.

## 6.2 Ketentuan Produksi

1. Satu karyawan dapat menangani seluruh tahapan pengerjaan.
2. Sistem tidak mewajibkan pembagian pekerjaan berdasarkan tahapan produksi.
3. Perubahan status harus menggambarkan kondisi pengerjaan sebenarnya.
4. Riwayat perubahan status tetap menjadi informasi penting untuk menelusuri perjalanan pesanan.
5. Status SELESAI hanya diberikan setelah pelanggan menerima pakaian.

## 6.3 Pembatalan Pesanan

| Status Pesanan | Pembatalan |
|---|---|
| MENUNGGU | Diperbolehkan |
| GILIRAN DICUCI | Tidak diperbolehkan |
| DICUCI | Tidak diperbolehkan |
| DIKERINGKAN | Tidak diperbolehkan |
| FINISHING | Tidak diperbolehkan |
| SELESAI | Tidak berlaku |

Batas pembatalan adalah sebelum pesanan memasuki tahap GILIRAN DICUCI.

---

# 7. Pengelolaan Antar-Jemput

## 7.1 Aturan Umum

Layanan antar-jemput diberikan secara gratis kepada pelanggan.

Seluruh biaya operasional layanan tersebut ditanggung Owner dan dicatat sebagai pengeluaran usaha.

Tidak ada biaya antar-jemput tambahan yang dibebankan ke tagihan pelanggan.

## 7.2 Penjemputan

- Penjemputan merupakan proses logistik tersendiri.
- karyawan dapat menugaskan kurir.
- Kurir mengambil dan membawa pakaian ke outlet.
- Pesanan laundry dibuat setelah pakaian tiba di outlet dan diperiksa.

## 7.3 Pengantaran

- Pengantaran dilakukan setelah pakaian selesai diproses.
- Admin menugaskan kurir untuk pengantaran.
- Kurir pengantaran tidak harus sama dengan kurir penjemputan.
- Pesanan baru berstatus SELESAI setelah pakaian diterima pelanggan.

## 7.4 Pemisahan Status

Status produksi laundry dan status tugas antar-jemput harus dibedakan.

Satu pesanan dapat memiliki:
- Penjemputan saja.
- Pengantaran saja.
- Penjemputan dan pengantaran sekaligus.

---

# 8. Pengelolaan Pembayaran

## 8.1 Metode Pembayaran

Sistem mendukung:
- Tunai.
- Transfer bank.
- QRIS.

## 8.2 Waktu Pembayaran

Pelanggan dapat melakukan pembayaran dengan pilihan:

1. Membayar penuh di awal.
2. Membayar sebagian sebagai DP dan melunasi kemudian.
3. Membayar setelah proses laundry selesai.

Pesanan tetap dapat diproses meskipun belum dibayar.

Status pembayaran tidak boleh menghambat perubahan status produksi secara otomatis.

## 8.3 Status Pembayaran

Status pembayaran utama:

- BELUM BAYAR.
- SUDAH BAYAR.

Status ditentukan berdasarkan pembayaran berhasil yang dialokasikan ke tagihan.

Rumus:

Sisa Tagihan = Total Tagihan − Total Pembayaran yang Berhasil Dialokasikan

Ketentuan:
- Jika sisa tagihan lebih dari nol, status BELUM BAYAR.
- Jika sisa tagihan sama dengan nol, status SUDAH BAYAR.

Pembayaran sebagian tetap dicatat sebagai pembayaran yang berhasil, meskipun pesanan belum lunas.

## 8.4 Pembayaran Tunai

Pembayaran tunai membedakan:

- Jumlah uang diterima.
- Jumlah yang dialokasikan untuk tagihan.
- Kembalian.

Contoh:

Tagihan Rp50.000, pelanggan menyerahkan Rp60.000.

- Uang diterima: Rp60.000.
- Pembayaran dialokasikan: Rp50.000.
- Kembalian: Rp10.000.

Kembalian bukan pendapatan dan bukan saldo pelanggan.

## 8.5 Transfer Bank dan QRIS

Pembayaran transfer bank dan QRIS tidak menghasilkan kembalian.

Pembayaran melebihi tagihan tidak boleh otomatis dianggap sebagai saldo pelanggan.

## 8.6 Ketentuan Perhitungan

- Hanya pembayaran berhasil yang dihitung sebagai pembayaran aktual.
- Pembayaran yang menunggu atau dibatalkan tidak dihitung sebagai uang masuk.
- Jumlah pembayaran yang dialokasikan tidak boleh disamakan dengan jumlah uang tunai yang diterima.
- Pembayaran yang sama tidak boleh dihitung berulang kali.
- DP merupakan bagian dari total pembayaran, bukan komponen tambahan yang dijumlahkan kembali.

---

# 9. Pembatalan dan Pengembalian Dana

## 9.1 Kebijakan Refund

Apabila pesanan dibatalkan saat berstatus MENUNGGU:

- Pelanggan mendapatkan pengembalian dana 100%.
- Tidak ada potongan administrasi.
- Tidak ada biaya pembatalan.
- Pembayaran DP dikembalikan seluruhnya.
- Pembayaran lunas dikembalikan seluruhnya.
- Jika belum ada pembayaran, tidak ada refund yang perlu dilakukan.

## 9.2 Metode Pengembalian Dana

Refund dapat dilakukan melalui:

- Transfer bank.
- Tunai.

Metode refund tidak wajib sama dengan metode pembayaran awal.

Contoh:
- Pembayaran QRIS dapat dikembalikan melalui transfer atau tunai.
- Pembayaran transfer dapat dikembalikan melalui transfer atau tunai.
- Pembayaran tunai dapat dikembalikan melalui tunai atau transfer.

## 9.3 Kewenangan Refund

Hanya Owner yang berwenang menyetujui dan memproses refund.

Refund harus tercatat sebagai transaksi keuangan tersendiri, mencakup:
- Pesanan terkait.
- Nominal refund.
- Metode pengembalian.
- Tanggal pengembalian.
- Pihak yang memproses.

Refund harus dibedakan dari pembayaran masuk dan pengeluaran operasional biasa.

---

# 10. Kebijakan Diskon

Sistem mempertahankan fitur diskon yang sudah tersedia.

Informasi diskon harus dapat ditelusuri dalam laporan bisnis.

Laporan diskon sekurang-kurangnya menampilkan:
- Jumlah pesanan yang memperoleh diskon.
- Total diskon yang diberikan.
- Rincian diskon per pesanan.
- Nilai pesanan sebelum diskon.
- Nilai pesanan setelah diskon.

Mekanisme dan jenis diskon mengikuti aturan yang sudah diterapkan dalam sistem sebelumnya.

---

# 11. Pencatatan Pengeluaran

## 11.1 Prinsip Pengeluaran

Seluruh pengeluaran usaha harus dapat dicatat, bukan hanya biaya yang berhubungan langsung dengan proses pencucian.

Contoh pengeluaran:

| Kelompok | Contoh |
|---|---|
| Utilitas | Listrik, air, internet |
| Bahan operasional | Deterjen, pewangi, plastik |
| Transportasi | Biaya penjemputan dan pengantaran |
| Perawatan | Perbaikan mesin dan peralatan |
| Peralatan | Pembelian mesin atau perlengkapan |
| SDM | Gaji dan tunjangan |
| Operasional umum | Kebutuhan usaha lainnya |

Kategori tersebut merupakan pengelompokan bisnis awal dan tidak membatasi kategori yang dapat digunakan Owner.

## 11.2 Ketentuan

1. Seluruh biaya usaha dapat dicatat.
2. Pengeluaran harus memiliki nominal dan tanggal transaksi.
3. Pengeluaran harus dapat ditelusuri berdasarkan kategorinya.
4. Pengeluaran harus masuk dalam laporan sesuai tanggal transaksi.
5. Pengeluaran layanan antar-jemput tetap dicatat meskipun pelanggan tidak dikenakan biaya.

---

# 12. Business Intelligence dan Dashboard Owner

## 12.1 Tujuan Dashboard

Dashboard Owner berfungsi memberikan gambaran menyeluruh mengenai kondisi usaha.

Informasi harus membantu Owner memahami:
- Aktivitas pesanan.
- Penyelesaian pesanan.
- Penerimaan uang.
- Tagihan yang belum lunas.
- Pengeluaran.
- Nilai transaksi.
- Diskon.
- Kondisi antrean.
- Layanan yang paling banyak digunakan.

Dashboard tidak ditujukan untuk menilai atau memeringkat produktivitas individu karyawan maupun kurir.

## 12.2 Indikator Bisnis

### A. Informasi Pesanan

- Jumlah pesanan masuk.
- Jumlah pesanan selesai.
- Jumlah pesanan berdasarkan status.
- Tren pesanan berdasarkan periode.
- Jumlah pesanan yang dibatalkan.

### B. Informasi Pembayaran

- Total pembayaran berhasil.
- Total pembayaran berdasarkan metode.
- Pembayaran penuh.
- Pembayaran sebagian.
- Pembayaran yang belum lunas.
- Tren penerimaan uang.

### C. Informasi Piutang

- Total tagihan belum lunas.
- Jumlah pesanan belum lunas.
- Total sisa tagihan.
- Rincian pembayaran setiap pesanan.

### D. Informasi Pengeluaran

- Total seluruh pengeluaran.
- Pengeluaran berdasarkan kategori.
- Tren pengeluaran.
- Rincian transaksi pengeluaran.

### E. Informasi Layanan

- Layanan paling populer.
- Jumlah penggunaan setiap layanan.
- Nilai transaksi berdasarkan layanan.
- Tren penggunaan layanan.

### F. Informasi Operasional

- Jumlah antrean laundry.
- Distribusi pesanan berdasarkan tahapan produksi.
- Jumlah pesanan yang sedang dikerjakan.
- Jumlah pesanan yang siap diserahkan.

---

# 13. Laporan Keuangan dan Nilai Transaksi

## 13.1 Prinsip Pemisahan Laporan

Sistem harus membedakan nilai transaksi, pembayaran aktual, piutang, refund, pengeluaran, dan hasil selisih.

Nilai pesanan bukan uang yang sudah diterima.

Pembayaran DP bukan nilai transaksi tambahan.

Pengeluaran tidak boleh langsung dianggap sebagai pengurang nilai pesanan tanpa memperhatikan periode transaksi.

## 13.2 Laporan Uang Aktual

Laporan ini menggambarkan uang yang benar-benar telah diterima oleh usaha.

Informasi:
- Total pembayaran berhasil.
- Pembayaran tunai.
- Pembayaran transfer bank.
- Pembayaran QRIS.
- Pembayaran DP.
- Pembayaran pelunasan.
- Pembayaran berdasarkan tanggal penerimaan.
- Refund yang telah dikeluarkan.
- Selisih penerimaan dan pengembalian dana.

Uang aktual harus berdasarkan transaksi pembayaran yang berhasil, bukan sekadar nilai pesanan.

## 13.3 Laporan Nilai Pesanan atau Estimasi

Laporan ini menggambarkan nilai tagihan pesanan, termasuk pesanan yang belum dibayar.

Informasi:
1. Total nilai pesanan sebelum diskon.
2. Total diskon.
3. Total nilai pesanan setelah diskon.
4. Total pembayaran yang sudah diterima.
5. Total pembayaran DP.
6. Total sisa tagihan.
7. Total pesanan belum dibayar.
8. Total pesanan sudah lunas.

Seluruh nilai harus dapat diperiksa sampai ke rincian pesanan.

## 13.4 Laporan Pengeluaran

Menampilkan:
- Total pengeluaran.
- Pengeluaran berdasarkan kategori.
- Pengeluaran berdasarkan tanggal.
- Rincian transaksi pengeluaran.

## 13.5 Laporan Selisih

Sistem menyediakan hasil perhitungan:

Selisih = Pendapatan Aktual − Pengeluaran

Hasil tersebut ditampilkan bersama komponen penyusunnya.

Istilah yang digunakan adalah **Selisih Pendapatan dan Pengeluaran**, bukan otomatis disebut laba bersih atau laba usaha, karena perhitungan tersebut belum tentu mencakup seluruh komponen akuntansi dan biaya.

Owner dapat memeriksa seluruh komponen secara mandiri.

## 13.6 Laporan Diskon

Laporan diskon harus tersedia secara khusus dan dapat difilter berdasarkan periode.

---

# 14. Periode dan Fitur Pelaporan

## 14.1 Periode Laporan

Semua laporan mendukung periode:

- Harian.
- Mingguan.
- Bulanan.
- Tahunan.
- Rentang tanggal khusus.

## 14.2 Perbandingan Periode

Owner dapat membandingkan data antarperiode, misalnya:

- Hari ini dengan hari sebelumnya.
- Minggu ini dengan minggu sebelumnya.
- Bulan ini dengan bulan sebelumnya.
- Tahun ini dengan tahun sebelumnya.

Perbandingan berlaku untuk indikator yang relevan, termasuk pesanan, pembayaran, pengeluaran, diskon, dan layanan.

## 14.3 Rincian Transaksi (Drill-down)

Setiap angka laporan yang memiliki sumber transaksi harus dapat ditelusuri ke data penyusunnya.

Contoh:
- Total pengeluaran membuka rincian pengeluaran.
- Total pembayaran membuka daftar transaksi pembayaran.
- Total piutang membuka daftar pesanan beserta sisa tagihan.
- Jumlah pesanan selesai membuka daftar pesanan selesai.
- Total diskon membuka daftar pesanan yang memperoleh diskon.

Informasi ringkasan dan rinciannya harus berasal dari data yang sama dan konsisten.

## 14.4 Ekspor Laporan

Sistem menyediakan dua format:

**PDF**
- Untuk dokumentasi dan arsip.
- Menampilkan ringkasan dan informasi laporan.
- Dapat digunakan untuk laporan formal.

**Excel**
- Untuk pengolahan data lebih lanjut.
- Memuat rincian transaksi sesuai jenis laporan.
- Mendukung pemeriksaan dan analisis mandiri oleh Owner.

Ekspor tersedia untuk periode harian, mingguan, bulanan, tahunan, dan rentang tanggal khusus.

---

# 15. Kebijakan Komplain

1. Pelanggan dapat mengajukan komplain maksimal 1 × 24 jam setelah pakaian diterima.
2. Komplain dibahas langsung dengan Owner atau karyawan.
3. Penyelesaian ditentukan berdasarkan kesepakatan bersama.
4. Tidak ada kompensasi otomatis yang ditetapkan dalam kebijakan bisnis ini.
5. Pencatatan komplain melalui sistem tidak diwajibkan.
6. Sistem tidak diwajibkan menyediakan modul khusus pengelolaan komplain.

---

# 16. Kebutuhan Fungsional Bisnis

| ID | Kebutuhan | Prioritas |
|---|---|---|
| BR-001 | Mendukung penerimaan pakaian secara langsung | Wajib |
| BR-002 | Mendukung permintaan penjemputan | Wajib |
| BR-003 | Membuat pesanan setelah pakaian pickup tiba dan diperiksa di outlet | Wajib |
| BR-004 | Menyediakan informasi harga dan estimasi secara transparan | Wajib |
| BR-005 | Mendukung seluruh jenis layanan yang berlaku | Wajib |
| BR-006 | Mengelola status produksi laundry | Wajib |
| BR-007 | Memisahkan status produksi dan tugas antar-jemput | Wajib |
| BR-008 | Mendukung satu karyawan menangani seluruh proses produksi | Wajib |
| BR-009 | Mendukung pembayaran tunai, transfer, dan QRIS | Wajib |
| BR-010 | Mendukung pembayaran penuh, DP, dan pembayaran setelah selesai | Wajib |
| BR-011 | Menghitung status pembayaran berdasarkan alokasi pembayaran berhasil | Wajib |
| BR-012 | Mendukung pembatalan sesuai batas status | Wajib |
| BR-013 | Mendukung refund 100% dengan kewenangan Owner | Wajib |
| BR-014 | Mencatat seluruh pengeluaran usaha | Wajib |
| BR-015 | Menyediakan laporan diskon khusus | Wajib |
| BR-016 | Menyediakan laporan uang aktual dan estimasi nilai pesanan | Wajib |
| BR-017 | Menyediakan informasi piutang | Wajib |
| BR-018 | Menyediakan dashboard bisnis Owner | Wajib |
| BR-019 | Menyediakan laporan berdasarkan berbagai periode | Wajib |
| BR-020 | Menyediakan filter rentang tanggal khusus | Wajib |
| BR-021 | Menyediakan perbandingan antarperiode | Wajib |
| BR-022 | Menyediakan rincian transaksi dari angka laporan | Wajib |
| BR-023 | Mendukung ekspor PDF dan Excel | Wajib |
| BR-024 | Mendukung pelacakan pesanan pelanggan | Wajib |

---

# 17. Kriteria Penerimaan Bisnis

Sistem dapat dinyatakan memenuhi kebutuhan bisnis apabila:

1. Pelanggan dapat menggunakan jalur drop-off maupun pickup.
2. Pesanan pickup baru dibuat setelah pakaian tiba dan diperiksa di outlet.
3. Informasi berat, layanan, harga, diskon, dan estimasi dapat disampaikan secara transparan.
4. Alur produksi mengikuti aturan status yang ditetapkan.
5. Status SELESAI hanya diberikan setelah pelanggan menerima pakaian.
6. Satu karyawan dapat menangani seluruh tahapan produksi.
7. Penjemputan dan pengantaran dapat menggunakan kurir yang berbeda.
8. Layanan antar-jemput tidak menambah tagihan pelanggan.
9. Pembayaran penuh, DP, dan pembayaran setelah selesai dapat dikelola.
10. Pesanan dapat diproses meskipun belum dibayar.
11. Status pembayaran mencerminkan jumlah yang benar-benar dialokasikan.
12. Pembatalan hanya diperbolehkan pada status MENUNGGU.
13. Pembatalan yang memenuhi syarat menghasilkan refund 100%.
14. Refund hanya dapat diproses oleh Owner.
15. Semua pengeluaran usaha dapat dicatat dan dilaporkan.
16. Diskon memiliki laporan tersendiri.
17. Laporan uang aktual terpisah dari laporan estimasi nilai pesanan.
18. Piutang dapat diperiksa berdasarkan rincian pesanan.
19. Laporan dapat ditampilkan untuk harian, mingguan, bulanan, tahunan, dan rentang tanggal khusus.
20. Owner dapat membandingkan data antarperiode.
21. Angka laporan dapat ditelusuri ke transaksi sumbernya.
22. Laporan dapat diekspor dalam PDF dan Excel.
23. Dashboard tidak memuat pemeringkatan kinerja individu karyawan maupun kurir.
24. Perhitungan selisih pendapatan dan pengeluaran tidak disalahartikan sebagai laba bersih.

---

# 18. Batasan dan Hal yang Tidak Termasuk

Hal-hal berikut tidak ditetapkan sebagai kebutuhan wajib dalam PRD ini:

1. Sistem penilaian atau pemeringkatan kinerja individu karyawan.
2. Sistem penilaian atau pemeringkatan kinerja individu kurir.
3. Modul khusus komplain pelanggan.
4. Kompensasi komplain otomatis.
5. Prediksi bisnis atau prediksi pendapatan berbasis kecerdasan buatan.
6. Perhitungan laba bersih akuntansi tanpa komponen biaya dan dasar akuntansi yang memadai.
7. Penambahan biaya antar-jemput kepada pelanggan.

Fitur dan aturan yang sudah berjalan tetap dipertahankan apabila tidak bertentangan dengan keputusan bisnis yang tercantum dalam dokumen ini.

---

# 19. Prinsip Konsistensi Data Bisnis

Untuk menjaga keakuratan informasi:

- Setiap transaksi pembayaran harus dapat ditelusuri.
- Setiap refund harus dapat ditelusuri.
- Nilai pesanan, diskon, pembayaran, dan piutang harus dibedakan.
- Pembayaran DP tidak boleh dihitung dua kali.
- Kembalian tunai tidak boleh dianggap sebagai pendapatan.
- Refund tidak boleh dianggap sebagai pembayaran masuk.
- Pengeluaran harus dicatat berdasarkan transaksi sebenarnya.
- Laporan berdasarkan tanggal pesanan harus dibedakan dari laporan berdasarkan tanggal pembayaran.
- Laporan berdasarkan tanggal pengeluaran harus menggunakan tanggal pengeluaran.
- Ringkasan dashboard dan rincian laporan harus konsisten.

---

# 20. Penutup

PRD Pemuda Laundry versi 2.0.0 ini menjadi acuan kebutuhan bisnis untuk pengembangan dan evaluasi sistem selanjutnya.

Dokumen ini menggabungkan proses bisnis yang sudah ditetapkan sebelumnya dengan kebutuhan tambahan yang disepakati dalam diskusi tanggal 28 November 2026.

Prioritas utama adalah menjaga operasional laundry tetap berjalan sesuai aturan usaha, menyediakan informasi keuangan yang transparan, dan membantu Owner mengambil keputusan berdasarkan data yang dapat diperiksa.

**Prinsip utama Pemuda Laundry: setiap pesanan, pembayaran, pengeluaran, dan aktivitas operasional harus memiliki informasi yang jelas serta dapat dipertanggungjawabkan.**
