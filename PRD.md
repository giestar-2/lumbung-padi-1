# PRD Sistem Manajemen Lumbung Beras

Versi 1.0 · 23 September 2026 · Status: acuan implementasi dengan keputusan biaya yang perlu divalidasi tim.

Dokumen ini menerjemahkan **Breakdown_Sistem_Lumbung_Beras (1)(1).docx** yang diperbarui owner menjadi kebutuhan produk, aturan bisnis, rancangan teknis, dan kriteria penerimaan. Dokumen sumber tersebut menjadi acuan utama. `desain.md` adalah pendamping untuk rancangan seluruh layar di Google Stitch, dengan referensi visual yang akan disertakan owner.

## 1. Prioritas acuan dan batas keputusan

Urutan acuan: instruksi terbaru owner, breakdown terbaru, keputusan implementasi dalam PRD ini, lalu rancangan visual. Referensi gambar menentukan penampilan, bukan mengganti fitur atau aturan bisnis.

- **Kebutuhan tetap:** fitur dan batas ruang lingkup yang tertulis dalam breakdown.
- **Keputusan implementasi:** rincian agar fitur dapat dibangun secara konsisten, misalnya presisi kg, struktur query, dan pencegahan klik ganda.
- **Usulan yang perlu validasi:** metode modal, pembagian biaya hasil produksi, dan tanggal pengakuan biaya. Status usulan dalam breakdown tidak diubah menjadi persetujuan owner.

PRD ini tidak menyatakan aplikasi sudah dibangun atau berhasil dideploy. Kompatibilitas teknologi mengacu pada dokumentasi resmi yang diperiksa pada tanggal dokumen; instalasi dependency dan build Railway wajib diverifikasi saat implementasi.

## 2. Masalah, tujuan, dan pengguna

Owner membutuhkan satu aplikasi untuk membedakan bahan dalam proses, hasil produksi yang belum masuk stok, dan produk siap dijual. Penjualan, uang yang diterima, piutang, biaya produksi, serta gaji harus terhubung tanpa pencatatan ganda.

Tujuan penerimaan produk:

1. Owner dapat menelusuri setiap batch beserta tahapan dan hasil timbangnya.
2. Penambahan hasil ke stok, penggunaan sisa bahan, pembayaran, dan gaji terjadi tepat satu kali.
3. Penjualan kredit tetap mengurangi stok, tetapi hanya pembayaran aktual yang masuk kas.
4. Daftar, kartu ringkasan, dan ekspor memakai definisi filter yang sama.
5. Owner dapat memakai seluruh fitur melalui desktop maupun ponsel.

Pengguna aplikasi hanya **satu owner**. Karyawan adalah data penggajian, tanpa akun. Pelanggan dan pemasok tidak memiliki portal. Satu akun tetap dapat membuka beberapa tab/perangkat; backend harus menangani permintaan bersamaan.

| Anggota | Tanggung jawab |
|---|---|
| Arya | Wawancara, dokumentasi, pengujian fungsional |
| Nafis | Project Manager, brief, PRD, prioritas dan evaluasi |
| Iman | Analisis sistem, alur dan ERD |
| Enggal | UI/UX, referensi owner, frontend |
| Aji | Backend, database, environment, integrasi dan automated testing |

## 3. Ruang lingkup dan menu

| ID | Menu | Cakupan wajib |
|---|---|---|
| F01 | Dashboard | Ringkasan, grafik kas, transaksi terbaru, stok menipis, laba produk, progres batch, jatuh tempo |
| F02 | Manajemen Produk | Produk dan stok, empat kartu, CRUD, aktif/nonaktif, penyesuaian |
| F03 | Tracking Gabah ke Beras | Daftar batch, pembelian/penerimaan, empat tahapan, hasil dan sisa bahan |
| F04 | Tracking Sisa Gabah ke Dedek | Daftar batch, alokasi bahan asal, tiga tahapan, hasil |
| F05 | Tracking Sisa Gabah ke Merang/Pupuk | Daftar batch, bahan asal dan campuran, tiga tahapan, hasil |
| F06 | Penjualan/POS | Keranjang kg, diskon, pelanggan, pembayaran, piutang, nota |
| F07 | Riwayat Penjualan dan Piutang | Empat kartu, filter gabungan, detail, pembayaran, pelunasan, Excel |
| F08 | Manajemen Pelanggan | CRUD, detail pembelian dan piutang |
| F09 | Manajemen Karyawan | CRUD, aktif/nonaktif, tarif gaji default |
| F10 | Manajemen Gaji | Pencatatan manual, empat kartu, filter, integrasi pengeluaran |
| F11 | Manajemen Pemasukan | Manual dan otomatis, empat kartu, filter, sumber |
| F12 | Manajemen Pengeluaran | Manual dan otomatis, empat kartu, filter, klasifikasi biaya |
| F13 | Pengaturan | Nama toko dan password |

F00 adalah login/logout dan pengamanan akses, bukan tambahan menu bisnis.

Tidak termasuk versi awal: akun staf/multi-role, banyak toko, portal pelanggan, menu pembelian gabah terpisah, menu piutang terpisah, menu laporan terpisah, histori pergerakan stok untuk owner, unggah bukti transaksi, absensi, gaji otomatis, payment gateway, perpajakan, pengiriman, retur/refund, dan manajemen utang pemasok. Pemasok/asal bahan cukup dicatat pada batch. Kategori cukup berupa pilihan dalam form; tidak perlu menu master baru.

## 4. Aturan bersama

### 4.1 Format dan validasi

- Bahasa Indonesia, mata uang IDR, zona waktu tampilan `Asia/Jakarta`.
- Semua berat menggunakan kg, mendukung tiga angka desimal. UI menerima input lokal seperti `12,500`, menormalisasikannya secara jelas, lalu mengirim nilai desimal baku ke server.
- Gunakan `DECIMAL(18,3)` untuk berat, `DECIMAL(20,2)` untuk nominal, dan `DECIMAL(20,6)` untuk modal per kg. Gunakan kalkulasi desimal/BCMath; jangan memakai floating point sebagai dasar uang.
- Pembulatan nominal dua desimal dengan half-up. Tampilan dapat menyembunyikan `,00`, tetapi nilai ekspor tetap numerik dan presisinya sama.
- Tanggal bisnis disimpan sebagai tanggal lokal; timestamp kejadian disimpan UTC. Filter tanggal inklusif diterjemahkan ke batas awal hari sampai sebelum awal hari berikutnya.
- Berat/nominal proses harus positif; stok dan modal tidak boleh negatif. Catatan, asal produk, dan keterangan tidak wajib kecuali disebut lain.
- Server menghitung ulang harga, diskon, total, sisa bahan, dan sisa tagihan. Nilai tampilan Livewire/JavaScript tidak dipercaya sebagai nilai final.

### 4.2 CRUD dan penguncian

- Aksi **Hapus** tersedia pada semua CRUD. Data tanpa relasi bisnis boleh dihapus setelah konfirmasi; data berelasi menampilkan tombol nonaktif dengan alasan yang spesifik.
- Jangan menganggap relasi teknis seperti owner pembuat sebagai alasan yang membuat semua data mustahil dihapus. Relasi penghalang adalah pemakaian pada transaksi, hasil, alokasi, pembayaran, dan catatan keuangan.
- Produk/karyawan berelasi dapat dinonaktifkan. Data historis tetap terbaca.
- Catatan kas otomatis hanya dapat diperbaiki melalui transaksi asal. Tidak boleh diedit/dihapus sebagai transaksi manual.
- **Keputusan versi awal:** setelah transaksi penjualan dikonfirmasi, item, kg, harga, diskon, dan nilai total dikunci. Pembayaran lanjutan tetap tersedia. Batch yang sudah memiliki stok masuk atau alokasi turunan mengunci berat dan biaya yang menjadi dasar hubungan tersebut. Ini memakai opsi penguncian yang diizinkan breakdown.
- Metadata yang tidak memengaruhi nilai boleh diedit. Koreksi transaksi sumber yang masih diizinkan harus memperbarui catatan terkait dalam satu transaksi database.
- Jangan menambahkan pembatalan/refund atau koreksi stok hasil produksi secara diam-diam. Koreksi besar pada data terkunci ditangani sebagai perubahan ruang lingkup terpisah.

### 4.3 Konsistensi penyimpanan

Setiap aksi yang memengaruhi stok/kas/piutang/alokasi menggunakan transaksi database. Kunci baris terkait, hitung ulang ketersediaan di server, simpan perubahan, lalu perbarui ringkasan sesudah commit. Pakai kunci idempotensi dan unique constraint untuk konfirmasi penjualan, posting hasil, pembayaran, dan pencatatan kas otomatis. Menonaktifkan tombol di frontend saja tidak cukup.

## 5. Kebutuhan fungsional

### F00. Akses owner

Login menggunakan email dan password akun owner yang dibuat saat setup. Tidak ada registrasi publik. Sediakan tampil/sembunyikan password, pesan salah login yang jelas, logout, serta penanganan sesi kedaluwarsa. Lindungi seluruh halaman bisnis, aksi Livewire, nota, dan unduhan dengan autentikasi dan pemeriksaan owner. Password di-hash; batasi percobaan login. Password default tidak disimpan dalam repository.

### F01. Dashboard

Filter periode default bulan berjalan, dengan pilihan hari ini/bulan ini/rentang tanggal. Tampilkan enam indikator: Penjualan Bersih, Pemasukan Kas, Pengeluaran Kas, Laba Usaha, Sisa Piutang Transaksi Periode, dan Arus Kas Bersih.

Komponen tambahan wajib:

- Transaksi terbaru: nomor, tanggal, pelanggan, total, status, akses detail.
- Stok menipis: produk aktif dengan stok `<= batas minimum`, termasuk stok nol; akses ke Produk.
- Produk paling menguntungkan: urutan laba kotor item setelah diskon dan HPP pada periode, bukan berdasarkan jumlah terjual atau penerimaan kas.
- Grafik pemasukan dan pengeluaran berdasarkan tanggal pembayaran, dengan legenda, satuan rupiah, dan keadaan tanpa data.
- Progres batch menurut jenis dan tahap, dengan tautan ke daftar batch atau batch terkait.
- Pengingat jatuh tempo hanya untuk transaksi yang memiliki tanggal jatuh tempo dan belum lunas.

Label cakupan harus jelas: stok menipis adalah **stok saat ini**; pengingat adalah **tagihan yang masih terbuka saat ini**. Filter periode penjualan tidak membuat stok menjadi stok historis. Progres batch memakai tanggal batch. Tampilkan rumus singkat laba/arus kas melalui bantuan kontekstual.

### F02. Manajemen Produk

Empat kartu: **Total Harga Produk**, **Total Harga Modal**, **Jumlah Produk**, **Produk Stok Menipis**. Dua kartu pertama adalah nilai persediaan, bukan omzet/laba. Ringkasan mengikuti pencarian dan filter produk; default daftar mencakup aktif dan nonaktif. Kartu stok menipis hanya menghitung produk aktif yang memenuhi batas minimum.

| Field | Aturan |
|---|---|
| Nama | Wajib, maksimal 150 karakter |
| Kategori | Wajib; mencakup Beras, Dedek, Merang/Pupuk, dan Lainnya sesuai kebutuhan data |
| Harga jual/kg | Wajib, tidak negatif |
| Harga modal/kg | Wajib, tidak negatif; modal berjalan memakai kebijakan bagian 8 |
| Stok kg | Saldo awal pada pembuatan biasa; nol pada pembuatan dari hasil tracking |
| Batas stok minimum | Tidak negatif, default 0 |
| Status | Aktif/nonaktif |
| Asal produk | Opsional, teks; berbeda dari relasi batch asal yang dicatat sistem |
| Catatan | Opsional |

Daftar memuat nama, kategori, stok kg, batas minimum, harga modal/kg, harga jual/kg, status, dan aksi. Sediakan pencarian nama serta filter kategori/status/stok menipis. Aksi: tambah, edit, detail, penyesuaian stok, aktif/nonaktif, hapus sesuai relasi, dan tautan ke Riwayat Penjualan terfilter produk.

Penyesuaian mengisi **stok aktual akhir**, bukan penambahan stok. Tampilkan stok lama, stok baru, dan selisih sebelum konfirmasi. Tidak wajib alasan dan tidak membuat kas baru. Modal berjalan/kg tetap, nilai persediaan mengikuti kuantitas baru. Perubahan modal saat edit adalah perubahan modal berjalan; wajib menampilkan dampak pada nilai persediaan, tanpa mengubah HPP transaksi lama atau mencatat kas. Tidak ada halaman riwayat pergeseran stok.

### F03–F05. Pola tracking bersama

Ketiga menu harus mempunyai **halaman daftar batch** dan **halaman dashboard satu batch**. Blok tahapan adalah proses satu batch yang sedang dibuka, bukan papan seluruh batch.

Daftar batch: nomor/nama, tanggal, asal bahan, berat awal, tahap aktif, status masuk stok, aksi Buka Tracking, edit/hapus sesuai relasi. Pencarian nomor/nama, filter rentang tanggal dan tahap, serta pagination. Setelah membuat batch, tampilkan baris baru dengan tahap Belum Diproses dan akses Buka Tracking.

Dashboard batch: identitas, asal, berat awal, tahap aktif, ringkasan biaya, blok berurutan, rincian catatan tiap tahap, hasil akhir dan status masuk stok. Hanya tahap aktif yang memiliki aksi lanjut. Tahap masa depan terkunci; tahap selesai tetap dapat dibaca.

| Jenis | Tahapan tetap |
|---|---|
| Gabah ke beras | Belum Diproses → Pengeringan → Pemisahan → Selesai |
| Sisa gabah ke dedek | Belum Diproses → Digiling → Selesai |
| Sisa gabah ke merang/pupuk | Belum Diproses → Pencampuran dengan Bahan Lain → Selesai |

Semua tahap menyimpan tanggal dan catatan opsional. Form yang memerlukan hasil menyediakan berat timbang aktual; biaya proses dicatat sebagai komponen biaya yang terkait batch. Perpindahan tahap memindahkan status, tidak menggandakan kuantitas. Riwayat tahapan ini berbeda dari histori pergerakan stok produk yang memang tidak ditampilkan.

#### F03. Gabah ke beras

Form Buat Batch: nomor unik yang dapat diusulkan sistem, nama, tanggal penerimaan, asal/pemasok, berat awal kg, harga beli/kg, total pembelian hasil perhitungan, biaya tambahan bila ada, status pembayaran, tanggal bayar jika sudah dibayar, dan catatan opsional. Penerimaan tanpa pembelian boleh bernilai biaya nol. Pencatatan ini tidak menambah stok siap jual.

- **Belum Diproses:** data penerimaan dan pembelian; aksi Mulai Pengeringan.
- **Pengeringan:** tanggal mulai/selesai, hasil berat kering, biaya proses, catatan. Susut dihitung dari berat masuk dikurangi berat kering. Tidak memakai rendemen tetap.
- **Pemisahan:** tanggal, berat beras, sisa bahan untuk dedek, sisa bahan untuk merang/pupuk, dan susut/sisa tidak terpakai. Jumlah seluruh hasil tidak melebihi berat masuk pemisahan; selisih ditampilkan. Tidak boleh mengharuskan berat awal sama dengan berat akhir.
- **Selesai:** tampilkan berat beras yang sudah ditimbang dan aksi Tambahkan ke Produk. Status Selesai Produksi terpisah dari Belum Masuk Stok/Sudah Masuk Stok.

Pemisahan membentuk ketersediaan bahan lanjutan sesuai tujuan. Sediakan ringkasan total, sudah dialokasikan, dan sisa tersedia untuk masing-masing tujuan. Bahan yang telah dialokasikan tidak dapat dipindahkan atau digunakan lagi oleh batch lain.

#### F04. Sisa gabah ke dedek

Form Buat Batch: nomor/nama, tanggal, pilih batch gabah asal dan alokasi tujuan dedek, sisa tersedia read-only, berat yang dipakai, catatan opsional. Versi awal satu batch lanjutan memakai satu batch asal; batch asal dapat melayani beberapa batch lanjutan selama tersedia.

Belum Diproses menyimpan bahan yang sudah dialokasikan. Digiling mencatat tanggal, biaya dan hasil timbang. Selesai menampilkan berat dedek aktual. Berat akhir tidak melebihi bahan masuk karena tidak ada penambahan bahan pada alur ini. Hasil nol boleh ditutup sebagai tidak ada hasil; tombol masuk stok tidak aktif.

#### F05. Sisa gabah ke merang/pupuk

Form awal sama dengan F04, tetapi memakai alokasi tujuan merang/pupuk. Tahap pencampuran menyediakan baris bahan tambahan: nama/jenis, jumlah kg, biaya, dan konfirmasi pembayaran bila ada pembelian. Baris dapat ditambah/dihapus sebelum dikunci.

Hasil akhir boleh lebih berat daripada sisa gabah awal karena ada campuran. Batas validasi adalah berat bahan asal ditambah campuran tercatat, bukan berat asal saja. Catat hasil aktual dan susut. Tampilkan nama tahap lengkap pada layar meskipun navigasi memakai label singkat Pencampuran.

#### Konfirmasi hasil ke produk pada ketiga tracking

1. Kartu Selesai membawa berat hasil dan modal hasil yang sudah ditetapkan; berat tidak diketik ulang.
2. Owner memilih produk yang cocok. Jika tidak ditemukan, tersedia Tambah Produk Baru dalam alur yang sama.
3. Produk baru dibuat stok nol, lalu diisi melalui satu posting hasil. Jangan sekaligus membuat saldo awal.
4. Pratinjau menampilkan batch, jenis hasil, kg, produk tujuan, stok sebelum/sesudah, dan modal berjalan setelah penambahan.
5. Konfirmasi menyimpan receipt unik, menambah stok dan nilai modal sekali, lalu memberi tanda Sudah Masuk Stok.
6. Klik ulang atau retry mengembalikan hasil posting sebelumnya. Produk nonaktif tidak menjadi tujuan posting baru; owner dapat mengaktifkannya lebih dulu.

### F06. Penjualan/POS

Daftar pilihan produk aktif memuat nama, kategori, harga/kg, dan stok tersedia. Pencarian produk dan filter kategori; jangan memuat seluruh katalog besar sekaligus. Keranjang memuat produk, harga/kg, jumlah kg, subtotal per item, dan aksi hapus item. Pengulangan produk digabung ke baris yang sama.

Ringkasan: subtotal, pilihan diskon Tidak Ada/Persen/Rupiah, nilai diskon, total akhir, nominal pembayaran aktual, sisa piutang. Persen 0–100; rupiah 0–subtotal; hanya satu jenis diskon berlaku per transaksi. Pelanggan boleh kosong untuk transaksi lunas, ditampilkan sebagai Umum. Pelanggan wajib bila ada sisa tagihan; tanggal jatuh tempo opsional. Tersedia akses tambah pelanggan tanpa kehilangan keranjang.

Konfirmasi memvalidasi ulang stok dan status produk, menyimpan nota serta snapshot item/harga/modal, mengurangi stok, mencatat pembayaran aktual ke pemasukan, dan menghitung piutang. Pembayaran awal nol sah untuk bayar di akhir. Pembayaran tidak melebihi total tagihan; fitur uang diserahkan/kembalian tidak ditambahkan pada versi awal. Transaksi dengan total nol akibat diskon penuh dianggap lunas dan tidak membuat pemasukan nol.

Setelah berhasil tampilkan nomor transaksi, total, dibayar, sisa tagihan, aksi Lihat/Cetak Nota dan Transaksi Baru. Nota HTML siap cetak dengan nama toko, nomor, tanggal, pelanggan, item/kg/harga, diskon, total, pembayaran dan sisa. Tidak perlu dependency PDF atau printer khusus.

### F07. Riwayat Penjualan dan Piutang

Empat kartu tetap: **Total Penjualan Setelah Diskon**, **Jumlah Transaksi**, **Total Sudah Dibayar**, **Sisa Piutang**. Nilainya dihitung untuk seluruh hasil filter sebelum pagination, bukan hanya halaman yang sedang terlihat.

Filter gabungan: pelanggan, produk, tanggal/rentang tanggal penjualan, dan status pembayaran Semua/Belum Dibayar/Sebagian/Lunas. Tambahkan pencarian nomor nota. Setiap perubahan filter memperbarui tabel, empat kartu, jumlah hasil, dan parameter ekspor.

Definisi yang mudah dipahami:

> Filter produk menampilkan nota yang memuat produk tersebut. Empat kartu menghitung keseluruhan nota yang ditemukan, masing-masing satu kali. Ekspor Excel memakai filter yang sama. Rincian item produk tersedia terpisah agar nilai satu produk tidak tercampur dengan total seluruh nota.

Tanggal filter adalah **tanggal penjualan**. Total Sudah Dibayar adalah seluruh pembayaran yang sudah tercatat untuk nota terpilih, termasuk pembayaran di luar tanggal penjualan yang difilter. Dengan demikian `penjualan setelah diskon = sudah dibayar + sisa piutang`. Laporan penerimaan menurut tanggal pembayaran berada di Pemasukan/Dashboard.

Tabel memuat nomor, tanggal, pelanggan, ringkasan item, total setelah diskon, dibayar, sisa, jatuh tempo bila ada, status, dan Detail Transaksi. Status berasal dari nilai pembayaran. Pilihan **Lunas** membuka konfirmasi sisa nominal dan tanggal, bukan sekadar mengganti label.

Detail menampilkan seluruh item nota, kg, harga, subtotal, alokasi diskon item, total, snapshot modal bila diperlukan owner, serta daftar pembayaran awal/lanjutan. Jika datang dari filter produk, tandai item yang cocok tanpa menyembunyikan item lain dari detail nota.

- **Tambah Pembayaran:** tanggal, nominal positif maksimal sisa, keterangan opsional. Jumlah belum cukup mempertahankan status Sebagian.
- **Tandai Lunas:** nominal otomatis sebesar sisa terbaru, tanggal wajib, konfirmasi. Catat penerimaan hanya sebesar sisa.
- Nota lunas menonaktifkan aksi pembayaran. Tidak menyediakan perpindahan manual dari Lunas ke Belum Dibayar.
- Jatuh tempo bukan status pembayaran baru; gunakan penanda Terlambat bila tanggal terlewati dan sisa masih positif.

#### Ekspor Excel

Hasil adalah file `.xlsx`, semua baris hasil filter tanpa batas pagination, urutan sama dengan tabel, disertai identitas toko, waktu ekspor, filter aktif dan total. Bekukan kumpulan data pada saat ekspor dimulai agar antar-sheet konsisten.

| Sheet | Isi |
|---|---|
| Ringkasan | Filter, waktu snapshot, empat nilai kartu dan definisi cakupannya |
| Transaksi | Satu baris per nota terpilih, tanpa duplikasi akibat banyak item/pembayaran |
| Item Produk | Item yang cocok dengan filter produk; jika tanpa filter produk, seluruh item nota terpilih. Sertakan diskon teralokasi dan nilai bersih item |
| Pembayaran | Riwayat pembayaran nota terpilih, tanggal pembayaran aktual dan jenis awal/lanjutan |

Total sheet Transaksi harus sama dengan empat kartu pada snapshot yang sama. Total Item Produk diberi label **Nilai item yang cocok**, tidak dipakai mengganti total nota. Tanggal/nominal/kg berupa tipe data Excel yang sesuai. Teks dari owner/pelanggan ditulis sebagai teks, bukan formula spreadsheet. Jika data tidak ada, UI memberi pesan; jangan mengirim file yang tampak seolah berisi transaksi.

### F08. Manajemen Pelanggan

CRUD dengan nama wajib; nomor telepon, alamat, catatan opsional. Cari nama/telepon. Detail memuat informasi pelanggan, jumlah transaksi, total pembelian, sudah dibayar, sisa piutang, dan akses ke Riwayat Penjualan dengan filter pelanggan aktif. Pelanggan yang dipakai pada penjualan tidak dapat dihapus. Tidak menambahkan akun atau login pelanggan.

### F09. Manajemen Karyawan

Nama wajib, kontak, posisi, jenis gaji default Harian/Bulanan, tarif default tidak negatif, status Aktif/Nonaktif, catatan opsional. Cari nama/kontak, filter status dan jenis gaji. Tarif hanya membantu pengisian pembayaran berikutnya; perubahan tarif tidak mengubah gaji lama. Karyawan berelasi tidak dapat dihapus; yang nonaktif tidak dipilih untuk pembayaran baru, tetapi data lama tetap terlihat.

### F10. Manajemen Gaji

Empat kartu: **Total Gaji Dibayar**, **Total Gaji Harian**, **Total Gaji Bulanan**, **Jumlah Karyawan Dibayar**. Karyawan dihitung unik. Filter tanggal pembayaran/periode tampilan, karyawan, dan jenis gaji berlaku bersama untuk kartu/tabel. Rentang kerja tersimpan terpisah dari filter tanggal bayar.

Form: karyawan, tanggal bayar, jenis gaji, periode kerja mulai–akhir, nominal, keterangan opsional. Harian menampilkan tarif/hari dan jumlah hari kerja lalu menghitung nominal; jika owner menyesuaikan nominal, tampilkan nilai final yang akan dibayar. Bulanan menampilkan nominal default yang dapat diubah.

Tambahan teknis untuk mencegah biaya ganda: pilihan **Operasional** (default) atau **Biaya Produksi**, dengan batch tujuan wajib pada pilihan produksi. Batch harus masih menerima penambahan biaya. Ini bukan modul absensi atau perhitungan gaji otomatis.

Simpan berarti sudah dibayar; tombol berlabel **Simpan Pembayaran Gaji**. Sistem membuat satu pengeluaran terkait. Gaji operasional mengurangi laba menurut periode pengakuan; gaji produksi menjadi komponen modal batch dan tidak dikurangkan lagi sebagai beban operasional. Deteksi karyawan/jenis/periode serupa sebagai peringatan duplikasi, tanpa otomatis melarang pembayaran sah yang berbeda. Kunci idempotensi mencegah pengiriman formulir yang sama dua kali.

Nominal final wajib positif. Edit dari sumber gaji yang masih diizinkan memperbarui pengeluaran yang sama, komponen biaya yang terkait, serta ringkasan periode lama/baru secara atomik; tidak membuat pengeluaran pengganti kedua. Nilai gaji produksi dikunci jika biaya batch sudah dipakai hasil atau turunan. Hapus mengikuti aturan relasi; pengeluaran otomatis tidak dapat dihapus sendiri agar gaji seolah-olah belum dibayar.

### F11. Manajemen Pemasukan

Empat kartu: **Total Pemasukan**, **Penerimaan Penjualan Langsung**, **Penerimaan Piutang**, **Pemasukan Lainnya**. Istilah langsung berarti pembayaran awal pada POS, termasuk pembayaran awal sebagian. Semua pembayaran setelah POS masuk Penerimaan Piutang. Manual masuk Pemasukan Lainnya agar kelompok tidak tumpang tindih.

Form manual: nama, nominal positif, tanggal, kategori, keterangan opsional. Kategori minimal membedakan Modal Pemilik dan Pemasukan Lainnya. Tidak ada unggah bukti. Filter tanggal pembayaran, kategori, sumber, pencarian nama/nomor referensi. Tabel memuat nama, tanggal, kategori, nominal, sumber Manual/POS/Pembayaran Piutang, referensi dan aksi.

Catatan otomatis read-only dengan Buka Transaksi Asal. Catatan manual boleh diedit/dihapus jika tidak menjadi sumber relasi lain. Setoran modal adalah kas masuk, bukan penjualan atau laba usaha.

### F12. Manajemen Pengeluaran

Empat kartu: **Total Pengeluaran**, **Pembelian Bahan**, **Biaya Pengolahan**, **Gaji serta Operasional Lainnya**. Ketiga kategori bagian harus tepat berjumlah Total Pengeluaran.

- Pembelian gabah/bahan campuran yang dibayar dikelompokkan sebagai Pembelian Bahan.
- Biaya proses non-gaji dikelompokkan sebagai Biaya Pengolahan.
- Seluruh pembayaran gaji dan biaya operasional lain dikelompokkan sebagai Gaji serta Operasional Lainnya, termasuk gaji produksi. Kelompok kas berbeda dari klasifikasi laba/modal.

Form manual: nama, nominal positif, tanggal bayar, kategori, klasifikasi Operasional/Produksi, batch wajib jika Produksi, dan keterangan opsional. Tidak ada bukti transaksi. Filter tanggal, kategori, sumber; pencarian nama/referensi. Tabel menampilkan sumber Manual/Pembelian Gabah/Pengolahan/Gaji dan tautan ke sumber.

Satu kejadian pembayaran hanya memiliki satu baris kas. Menghubungkan pengeluaran manual ke biaya batch tidak boleh membuat pengeluaran otomatis kedua. Pembelian/biaya pada batch yang belum dikonfirmasi dibayar belum masuk kas keluar.

### F13. Pengaturan

Form Nama Toko wajib; perubahan tampil di sidebar/top bar dan nota yang dirender setelah perubahan. Nama toko default saat setup adalah Lumbung Beras dan bisa diganti. Form password terpisah: password lama, password baru, konfirmasi password baru. Validasi password lama dan konfirmasi di server; password minimal 12 karakter sebagai kebijakan implementasi. Setelah berhasil, minta login ulang untuk sesi aktif dan cabut sesi lain yang masih berlaku. Tidak menambahkan pengaturan API, manajemen akun, atau tema sebagai fitur wajib.

## 6. Kontrak stok dan alokasi bahan

| Kejadian | Perubahan stok siap jual | Kas |
|---|---|---|
| Saldo awal produk | Bertambah sesuai saldo awal | Tidak ada |
| Pembelian/penerimaan gabah | Tidak berubah; bahan menjadi milik batch | Keluar hanya bila pembayaran dikonfirmasi |
| Perpindahan tahap | Tidak berubah | Hanya pembayaran biaya yang tercatat |
| Produksi selesai | Tidak berubah sampai posting hasil | Tidak otomatis membuat kas |
| Tambahkan ke Produk | Bertambah sebesar hasil aktual satu kali | Tidak ada |
| Penjualan terkonfirmasi | Berkurang sebesar kg terjual | Masuk sebesar pembayaran aktual saja |
| Pelunasan | Tidak berubah | Masuk sebesar pembayaran lanjutan |
| Penyesuaian | Menjadi jumlah aktual yang dimasukkan | Tidak ada |

Alokasi bahan lanjutan dilakukan saat batch lanjutan dibuat dan langsung mengurangi sisa tersedia. Simpan asal dan kg alokasi secara eksplisit. Kunci sumber ketika membuat alokasi; perhitungan server: `tersedia = hasil_sisa - total_alokasi_aktif`. Tidak boleh memakai total bahan batch asal sebagai ketersediaan tiap batch baru.

Menutup form sebelum disimpan tidak membuat alokasi. Setelah batch lanjutan tersimpan, hubungan bahan asal merupakan relasi bisnis: asal/kuantitas dikunci dan penghapusan dinonaktifkan sesuai aturan data berelasi. Jangan menambahkan pembatalan atau pelepasan alokasi batch tersimpan pada versi awal tanpa keputusan perubahan ruang lingkup.

Satu hasil selesai dapat diposting seluruhnya ke satu produk. Pemecahan satu hasil ke beberapa produk atau posting sebagian bukan kebutuhan versi awal. Pembatasan ini membuat berat otomatis dan satu kali konfirmasi sesuai breakdown.

## 7. Kontrak penjualan, diskon, piutang dan kas

`subtotal = Σ(kg × harga jual snapshot)`

`total penjualan bersih = subtotal − diskon transaksi`

`sisa piutang = total penjualan bersih − Σ pembayaran aktual`

Diskon transaksi dialokasikan sebanding subtotal item untuk menghitung nilai bersih/laba tiap produk. Setelah pembulatan, selisih sen dialokasikan secara deterministik ke item dengan sisa pecahan terbesar, tie-break ID item. Jumlah diskon item wajib sama dengan diskon transaksi. Produk dengan subtotal nol tidak menerima alokasi; subtotal seluruh nota nol tidak membagi dengan nol.

Status: Belum Dibayar jika pembayaran 0 dan tagihan positif; Sebagian jika pembayaran di antara 0 dan total; Lunas jika sisa 0. Owner tidak dapat menentukan status bertentangan dengan nilainya.

Contoh wajib pengujian: total setelah diskon Rp1.000.000, dibayar awal Rp200.000, piutang Rp800.000. Pelunasan mencatat tambahan Rp800.000. Total penjualan tetap Rp1.000.000, total kas diterima menjadi Rp1.000.000, stok tidak berubah lagi.

Contoh filter produk: satu nota berisi Beras A Rp300.000 dan Dedek Rp200.000, diskon Rp50.000. Filter Beras A tetap menemukan satu nota dengan total Rp450.000. Sheet Item Produk untuk Beras A menampilkan nilai bersih Rp270.000 setelah alokasi diskon Rp30.000. Jika dibayar Rp100.000, kartu nota menunjukkan dibayar Rp100.000 dan piutang Rp350.000. Tidak membagi pembayaran ke produk untuk kartu nota.

## 8. Modal dan laba

### 8.1 Definisi indikator

| Indikator | Definisi |
|---|---|
| Total Harga Produk | Σ stok saat ini × harga jual/kg saat ini |
| Total Harga Modal | Σ nilai modal persediaan saat ini |
| Penjualan Bersih | Penjualan terkonfirmasi setelah diskon berdasarkan tanggal penjualan |
| HPP | Modal barang yang terjual dari snapshot transaksi |
| Laba kotor item | Nilai bersih item − HPP item |
| Laba Usaha | Penjualan Bersih − HPP − Biaya Operasional periode |
| Arus Kas Bersih | Kas masuk − kas keluar berdasarkan tanggal pembayaran |

Nilai dua kartu persediaan tidak boleh diberi label omzet/keuntungan. Piutang belum tertagih tetap termasuk penjualan, tetapi belum kas. Pelunasan tidak membuat laba baru. Pemasukan manual tercatat pada arus kas; tidak otomatis ditambahkan ke omzet/laba dengan rumus versi awal di atas. Pengakuan pendapatan usaha selain penjualan perlu kesepakatan kategori sebelum memperluas rumus.

### 8.2 Usulan modal rata-rata tertimbang

Baseline yang diusulkan breakdown:

`modal/kg baru = (nilai modal stok lama + biaya hasil yang masuk) / (stok lama + kg hasil masuk)`

Simpan nilai modal persediaan dan modal/kg dengan presisi memadai. Jika stok lama nol, modal baru berasal dari biaya hasil dibagi kg hasil. Jika hasil nol, jangan membagi atau mengaktifkan posting. Saldo awal memiliki nilai modal awal tanpa transaksi kas baru. Simpan modal/kg dan HPP pada sale item; perubahan harga/modal berikutnya tidak menghitung ulang laba penjualan lama.

### 8.3 Usulan pembagian biaya batch

Untuk menutup celah biaya ganda, gunakan alokasi nominal yang dapat ditinjau owner: biaya batch gabah dibagi ke **beras**, **bahan untuk dedek**, dan **bahan untuk merang/pupuk**. Saat mengisi, jumlah tidak boleh melebihi total biaya. Sebelum pembagian difinalkan, seluruh biaya harus teralokasi; sisa alokasi terlihat jelas. Biaya susut normal diserap ke hasil yang ada, bukan dibuat menjadi produk stok baru.

Biaya yang dialokasikan ke sisa bahan ikut pindah secara proporsional ketika bahan dipakai batch lanjutan, dengan sisa pembulatan diberikan pada alokasi terakhir. Batch lanjutan menambah biaya proses/campuran miliknya. Biaya warisan dari batch asal tidak menciptakan kas keluar baru. Batch dengan bahan belum dipakai tetap menyimpan nilai modal bahan, tidak langsung menjadi HPP.

Finalisasi biaya diperlukan sebelum posting hasil pertama atau penggunaan bahan oleh batch turunan. Sesudah ada pemakaian, dasar biaya dikunci. Form alokasi biaya berada di detail batch, bukan menu baru. Sediakan nilai default/usulan untuk memudahkan, tetapi jangan mengarang rasio rendemen atau pembagian biaya tetap tanpa validasi owner.

Pembayaran gaji produksi hanya masuk biaya batch sekali. Gaji operasional masuk beban operasional. **Usulan tanggal pengakuan gaji operasional:** akhir periode kerja; arus kas tetap tanggal pembayaran. Untuk pengeluaran operasional manual, gunakan tanggal transaksi. Validasi kebijakan ini dengan tim sebelum angka laba dipakai sebagai laporan final.

### 8.4 Keputusan baseline implementasi

Pada 27 September 2026, owner meminta pekerjaan dilanjutkan setelah penjelasan kebutuhan persetujuan D01-D04. Implementasi dilanjutkan dengan empat baseline di bawah: modal rata-rata tertimbang, alokasi nominal yang dikonfirmasi owner per batch, pengakuan gaji operasional pada akhir periode kerja, serta pembayaran penuh per komponen biaya. Rasio biaya tidak ditentukan otomatis.


| ID | Keputusan | Baseline rancangan saat ini |
|---|---|---|
| D01 | Metode modal | Rata-rata tertimbang berjalan |
| D02 | Pembagian biaya gabah | Alokasi nominal ke beras dan dua kelompok sisa bahan |
| D03 | Pengakuan gaji | Akhir periode kerja untuk laba, tanggal bayar untuk kas |
| D04 | Pembelian/biaya belum dibayar | Catat status pada sumber; konfirmasi lunas penuh per komponen, tanpa modul utang/cicilan pemasok |

D04 menyediakan input nominal biaya, status Belum Dibayar/Sudah Dibayar dan tanggal. Nominal biaya produksi dapat ada sebelum kas keluar. Status pembayaran yang diulang tidak boleh membuat kas kedua. Keempat keputusan ini tidak menambah menu bisnis; dokumentasikan hasil validasi pada PRD sebelum implementasi modul laba final.

## 9. Arsitektur implementasi

Gunakan aplikasi monolit Laravel dengan Blade + Livewire, MySQL sebagai penyimpanan utama, Tailwind untuk gaya, serta JavaScript/Alpine bawaan Livewire untuk interaksi ringan. CSS khusus hanya untuk kebutuhan yang tidak tercakup utility. Tidak memerlukan SPA/API terpisah.

Komponen Livewire mengelola input/tampilan; aturan bisnis berada pada action/service yang dapat diuji dan digunakan ulang: `CreateBatch`, `AdvanceBatchStage`, `AllocateResidue`, `PostProductionOutput`, `ConfirmSale`, `RecordSalePayment`, `RecordPayroll`, `UpdateManualCashEntry`, serta query `SalesHistoryQuery` dan `DashboardSummaryQuery`.

Validasi form menggunakan Livewire Form/rules; endpoint HTTP menggunakan Form Request. Pakai aturan reusable yang sama agar POS, detail transaksi, dan service tidak berbeda. Validasi format di awal, lalu validasi stok/alokasi/piutang kembali di dalam transaksi setelah lock. Hanya input tervalidasi yang diteruskan ke service. Otorisasi diperiksa di setiap action, termasuk ID hasil manipulasi pada request Livewire.

### 9.1 Dependency dan runtime yang dipilih

Ini baseline versi yang dipilih berdasarkan dokumentasi, bukan klaim bahwa Railway menjamin semua package pihak ketiga. Commit lockfile setelah dependency berhasil diselesaikan pada environment yang sama dengan build.

| Komponen | Baseline | Catatan kompatibilitas |
|---|---|---|
| PHP | 8.4.x, constraint `~8.4.0` | Sesuai Laravel 13 dan OpenSpout 5; Railpack membaca composer.json [S1][S2] |
| Laravel | 13.x, `^13.0` | Minimal PHP 8.3; gunakan patch stabil yang terkunci [S1] |
| Livewire | 4.x, `^4.0` | Manifest mendukung Illuminate/Laravel 13 [S3] |
| MySQL | 8.4.x LTS, InnoDB, utf8mb4 | Gunakan service MySQL Railway dan image/tag eksplisit 8.4, jangan mengandalkan tag latest [S4] |
| Composer | 2.x | Install dari composer.lock, validasi platform |
| Node.js | 22.x, minimal 22.12 | Untuk build Vite; `engines.node: >=22.12.0 <23` dan `RAILPACK_NODE_VERSION=22` [S5][S6] |
| Tailwind CSS | 4.x, `^4.0` | Plugin `@tailwindcss/vite` 4.x; CSS-first [S7] |
| Vite | 8.x, `^8.0` | Dengan `laravel-vite-plugin` `^3.1`, mengikuti skeleton Laravel 13 yang diperiksa [S8] |
| JavaScript | ES modules | Alpine dari Livewire, tidak dimuat dua kali [S9] |
| Excel | `openspout/openspout` `^5.11` | Rilis 5.11.3 yang diperiksa mendukung PHP 8.4/8.5; ekspor XLSX melalui service [S10] |
| Pengujian | PHPUnit 12.5.x di require-dev | Mengikuti skeleton Laravel 13; jalankan melalui `php artisan test` [S11] |
| Cache/session | Driver database Laravel | Tidak membutuhkan service Redis untuk baseline satu owner [S12] |

Ekstensi PHP: kebutuhan Laravel seperti ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, session, tokenizer, xml; tambahkan **pdo_mysql, bcmath, zip, xmlreader, libxml** sesuai perhitungan dan Excel. Deklarasikan ekstensi yang diperlukan dalam `composer.json` dan verifikasi `composer check-platform-reqs`; jangan meloloskan build dengan `--ignore-platform-reqs`. Tidak membutuhkan GD/Imagick/Chromium untuk fitur yang direncanakan.

Dependency tambahan untuk grafik/ikon dipilih saat frontend dan dikunci di package-lock.json, bukan diambil dari CDN runtime. PHPUnit/dev tools tidak dipasang ke image produksi. PHP 8.3 walaupun cukup untuk Laravel tidak memenuhi baseline OpenSpout yang dipilih, sehingga proyek ini memakai PHP 8.4.

### 9.2 Alasan pemilihan

Laravel mendukung pengembangan CRUD, validasi, autentikasi, transaksi database, migration dan testing dalam satu pola kerja. MySQL cocok untuk hubungan produk–item penjualan–pembayaran serta batch asal–alokasi–hasil. Livewire memudahkan form, filter, modal dan pembaruan ringkasan tanpa membangun frontend API terpisah. Jumlah satu user adalah batas kebutuhan, bukan alasan teknis bahwa Laravel hanya sesuai untuk satu user.

## 10. Rancangan data minimum

Nama tabel berikut adalah usulan implementasi, bukan menu baru. Gunakan foreign key dengan pembatasan hapus pada relasi bisnis, migration, unique constraint, dan timestamp.

| Entitas | Data/relasi utama |
|---|---|
| users | Akun owner, email unik, password hash |
| settings | Singleton nama toko |
| products | Nama, kategori, harga jual, modal berjalan, nilai modal persediaan, stock_qty, stock_minimum, status, asal, catatan |
| customers | Nama, telepon, alamat, catatan |
| employees | Nama, kontak, posisi, jenis/tarif default, status, catatan |
| production_batches | Kode unik, jenis, tanggal, asal bahan, berat awal, tahap, status biaya, status hasil |
| batch_stages | Batch, kode tahap, tanggal mulai/selesai, input/output kg, catatan; unik batch+tahap |
| batch_costs | Batch, jenis, nominal, tanggal, status pembayaran, paid_at, referensi payroll/cash bila berasal dari sana |
| batch_mixtures | Batch pupuk, nama bahan, kg, relasi komponen biaya terkait |
| batch_outputs | Batch, tipe beras/dedek/pupuk, kg akhir, allocated_cost, finalized_at |
| residue_pools | Batch asal, tujuan dedek/pupuk, kg hasil, kg teralokasi, nilai modal alokasi |
| residue_allocations | Pool sumber, batch tujuan, kg, nilai biaya warisan; unik batch tujuan untuk baseline satu asal |
| stock_receipts | Output unik, produk, kg, nilai biaya, posted_at, idempotency key |
| sales | Nomor unik, tanggal, pelanggan nullable, subtotal, jenis/nilai diskon, total, total_paid, balance_due, status, due_date |
| sale_items | Sale, product, snapshot nama/kategori, kg, harga, modal/kg, subtotal, alokasi diskon, net_amount, hpp |
| sale_payments | Sale, tanggal, nominal, awal/lanjutan, catatan, idempotency key unik |
| payrolls | Employee, tanggal bayar, jenis, periode, tarif/hari, hari kerja, nominal, klasifikasi, batch opsional |
| cash_entries | In/out, nama, nominal, tanggal, kategori, klasifikasi, sumber, referensi eksplisit ke payment/payroll/batch_cost bila otomatis |
| action_keys | Jenis aksi, kunci unik, hash input, ID hasil; retry dengan input berbeda ditolak |
| cache_versions | Nomor revisi domain untuk key cache agregat |
| cache, cache_locks, sessions | Tabel infrastruktur Laravel |

Satu cash entry otomatis harus punya tepat satu asal. Terapkan unique FK untuk payment/payroll/cost agar satu sumber tidak muncul dua kali. Jangan sekaligus membuat cash entry dari payroll dan dari batch_cost yang mereferensikan payroll itu. `total_paid`, `balance_due`, dan status sales adalah ringkasan transaksi yang diperbarui atomik serta diuji terhadap detail payments.

## 11. Index dan pencegahan N+1

### 11.1 Index berdasarkan akses

| Tabel | Index yang direncanakan | Query sasaran |
|---|---|---|
| products | `(is_active, category, id)`, index nama jika pencarian prefix | POS/filter produk |
| production_batches | unique code; `(type, batch_date, id)`; `(type, current_stage, batch_date, id)` | Daftar tracking dan filter tahap |
| batch_stages | unique `(batch_id, stage_code)` | Detail tahapan |
| residue_pools | `(source_batch_id, target_type)` | Pilih sisa bahan |
| residue_allocations | index pool_id; unique target_batch_id | Total pemakaian dan penelusuran asal |
| stock_receipts | unique output_id; index product_id | Posting sekali dan relasi produk |
| sales | unique number; `(sold_at, id)`; `(customer_id, sold_at, id)`; `(payment_status, sold_at, id)`; `(payment_status, due_date, id)` | Riwayat dan jatuh tempo |
| sale_items | `(sale_id, product_id)` dan `(product_id, sale_id)` | Detail nota, filter produk, laba per produk |
| sale_payments | `(sale_id, paid_at, id)`; unique idempotency_key | Detail pembayaran |
| payrolls | `(paid_at, id)`; `(employee_id, paid_at, id)`; `(salary_type, paid_at, id)` | Daftar/filter gaji |
| cash_entries | `(direction, occurred_on, id)`; `(direction, category, occurred_on, id)`; `(direction, source_type, occurred_on, id)`; unique FK sumber | Ringkasan/filter kas dan deduplikasi |

Ini daftar kandidat awal. Hindari index duplikat yang sudah tercakup prefix atau dibuat untuk FK. Evaluasi dengan `EXPLAIN ANALYZE` pada query dan volume representatif sebelum menetapkan migration final. Index tunggal boolean tidak otomatis berguna. Perbandingan `stock_qty <= stock_minimum` dan pencarian `%kata%` tidak otomatis cepat hanya karena B-tree; evaluasi ukuran data tanpa menambah mesin pencarian baru.

### 11.2 Kontrak query

- Daftar penjualan memakai `whereHas/EXISTS` untuk filter produk agar nota tidak berlipat. Gunakan eager loading customer yang dibutuhkan serta aggregate payments/item count; jangan memuat semua detail untuk setiap baris.
- Detail nota baru memuat items dan payments ketika dibuka. Detail batch memuat tahap, biaya, campuran, hasil dan alokasi secara terencana.
- Daftar gaji memakai `with(employee)`; ringkasan karyawan memakai aggregate SQL, bukan menghitung melalui loop PHP.
- `withCount`, `withSum`, subquery aggregate atau join ke hasil agregasi digunakan untuk angka ringkasan. Jangan menjumlah sales setelah join ke banyak payments dan items yang menghasilkan perkalian baris.
- Gunakan satu objek filter/query builder untuk tabel, kartu, dan ekspor. Clone query sebelum pagination; whitelist kolom sort.
- Blade/accessor/loop render tidak boleh memanggil query per item. Ambil kolom yang diperlukan dan FK yang dibutuhkan relasi.
- Aktifkan `Model::preventLazyLoading(! app()->isProduction())` pada development/testing [S13]. Jangan mengandalkan automatic eager loading untuk menutupi desain query yang tidak diperiksa.
- Pagination server default 25, pilihan 10/25/50. Search debounce sekitar 300 ms, reset page saat filter berubah. Selector produk/pelanggan/batch asal memakai pencarian server dengan hasil terbatas.
- Ekspor memakai chunk/keyset dan eager loading per chunk atau query flat terkontrol. Jumlah query bertambah per chunk, bukan per item. Jangan `get()` seluruh relasi besar hanya untuk mengekspor.

Gate performa: bandingkan render daftar berisi 5 dan 25 baris pada cache dingin. Jumlah query relasi tetap konstan terhadap jumlah baris. Ukur endpoint daftar/dashboard; target awal p95 di bawah 2 detik untuk 10.000 nota dan 50.000 item pada staging yang kapasitasnya dicatat. Angka ini target pengujian, bukan jaminan paket Railway tertentu.

## 12. Strategi cache

Baseline `CACHE_STORE=database` dan `SESSION_DRIVER=database`. Redis bukan dependency wajib. Cache hanya mempercepat pembacaan; stok, sisa bahan, pembayaran, dan piutang untuk validasi mutasi selalu dibaca dari MySQL dalam transaksi.

| Data | TTL awal | Pemicu revisi/invalidation |
|---|---|---|
| Dashboard/ranking/grafik | 60 detik | Penjualan, pembayaran, kas, payroll, batch dan produk |
| Empat kartu daftar | 30–60 detik | Mutasi modul yang bersangkutan dan dependensinya |
| Nama toko | 10 menit | Update pengaturan |
| Pilihan produk aktif | Maksimal 30 detik jika diperlukan | Harga, stok, status dan posting hasil |

Key memuat owner, nama ringkasan, filter ternormalisasi, periode, dan versi domain. Naikkan versi domain dalam transaksi yang sama dengan mutasi; baca cache memakai versi terbaru. Respons sukses menyegarkan komponen terkait setelah commit. Cara ini mencegah proses yang lambat menyimpan ulang cache lama ke key yang masih dipakai. TTL menjadi pengaman tambahan.

Jangan memakai `Cache::tags()` pada driver database karena tidak didukung [S12]. Hindari cache kumpulan model besar, hasil ekspor, serta validasi saldo. Uji data sebelum/sesudah mutasi dan setelah rollback. Stok POS wajib dicek ulang ketika konfirmasi meskipun angka yang terlihat berasal dari cache.

## 13. Deployment Railway

### 13.1 Topologi dan build

Dua service baseline: **Laravel Web** dan **MySQL** dalam project/environment Railway yang sama. Gunakan private networking untuk koneksi database. MySQL memiliki volume persisten dan jadwal backup. Web tidak mengandalkan local disk untuk data permanen; file Excel dibuat sementara lalu diunduh dan dibersihkan.

Gunakan **Railpack** untuk baseline. Dokumentasi provider PHP saat pemeriksaan memakai FrankenPHP dan membaca versi PHP dari composer.json [S2]. Panduan Laravel Railway juga memuat contoh konfigurasi server yang berbeda; verifikasi provider aktif dari log build, jangan menyalin start command PHP-FPM/Caddy secara campur. Dockerfile adalah alternatif jika kemudian dibutuhkan kontrol build penuh, bukan dependency tambahan wajib.

Root repository berisi artisan, composer.json, composer.lock, package.json dan package-lock.json. Target web root `/app/public`. Build menghasilkan `public/build/manifest.json` melalui `npm ci` dan `npm run build`; produksi tidak menjalankan Vite dev server. Install backend dari lockfile dengan `composer install --no-dev --prefer-dist --optimize-autoloader`.

### 13.2 Environment minimum

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-aplikasi
APP_KEY=nilai-tetap-dari-setup
APP_LOCALE=id
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
RAILPACK_NODE_VERSION=22
RAILPACK_SKIP_MIGRATIONS=true
```

`MySQL` pada reference variable harus diganti dengan nama service sebenarnya. Jangan memakai host private untuk koneksi dari laptop. Atur timezone aplikasi ke Asia/Jakarta di konfigurasi; jika memakai `APP_TIMEZONE`, hubungkan variabel itu secara eksplisit di config. APP_KEY dibuat sekali dan tidak diganti tiap deploy. APP_URL menggunakan HTTPS, trusted proxy disesuaikan agar Livewire tidak mengalami mixed content/CSRF akibat URL yang salah.

### 13.3 Migration dan start

Gunakan satu jalur migration: Railway **pre-deploy command** `php artisan migrate --force` [S14]. Pastikan lingkungan pre-deploy memperoleh variabel dan akses database pada project yang digunakan. `RAILPACK_SKIP_MIGRATIONS=true` menonaktifkan jalur migration/seeding otomatis provider; jangan menjalankan kedua jalur sekaligus. Jika lingkungan yang dipakai mengharuskan migration saat start, gunakan startup terkendali sebagai pengganti pre-deploy, bukan tambahan.

Jangan menjalankan seeder dummy atau membuat ulang password owner di setiap deploy. Pembuatan owner awal menggunakan command sekali jalan dengan input aman. Perubahan schema pada deploy harus kompatibel dengan instance lama yang masih melayani request. Build tidak boleh melakukan query ke database private atau membaca tabel di service provider; koneksi privat tersedia pada fase runtime yang sesuai.

Biarkan server provider mendengarkan `PORT` Railway dan seluruh interface. Healthcheck `/up` harus dapat diakses tanpa login dan mengembalikan HTTP 200 saat aplikasi siap [S15]. Periksa koneksi MySQL lewat smoke test terpisah. Jalankan cache config/route/view setelah environment tersedia, hindari config cache yang berisi nilai environment kosong. Set permission writable pada storage dan bootstrap/cache.

### 13.4 Gate rilis

1. Resolve dependency pada PHP 8.4/Node 22 yang dipilih, commit kedua lockfile.
2. `composer validate --strict`, `composer check-platform-reqs`, audit dependency, dan semua automated test lulus di CI.
3. `npm ci` dan `npm run build` lulus; manifest dan asset tersedia pada image hasil build.
4. Migration staging lulus, MySQL memakai InnoDB/utf8mb4 dan volume persisten.
5. Smoke test login, form Livewire, transaksi, pelunasan, ekspor XLSX dan cetak nota lulus di URL staging.
6. Redeploy tidak menghapus data, sesi/nama toko tetap sesuai kebijakan, APP_KEY tetap sama.
7. Backup dan prosedur restore dicoba pada environment terpisah. Jangan melakukan migration rollback destruktif otomatis pada data produksi.

## 14. Pengujian Laravel

User meminta Laravel unit test. Gunakan **Unit Tests** untuk kalkulasi murni, dilengkapi **Feature/Integration Tests** untuk perilaku database/Livewire. Unit test saja tidak membuktikan integrasi stok dan kas. PHPUnit berada di development/CI; jangan menguji dengan database produksi.

| ID uji | Skenario | Hasil wajib |
|---|---|---|
| T01 | Buat batch gabah | Satu baris, Belum Diproses, stok produk tidak bertambah |
| T02 | Lanjut tahap atau lompat tahap | Urutan sah berhasil; lompatan ditolak |
| T03 | Posting hasil 550 kg, lalu retry | Stok bertambah tepat 550 kg, satu receipt |
| T04 | Tambah produk dari hasil selesai | Produk stok nol lalu satu posting, tidak menjadi 1.100 kg |
| T05 | Dua request mengambil sisa bahan sama | Total alokasi tidak melebihi tersedia |
| T06 | Hasil pengeringan dan pupuk | Susut diterima; pupuk dapat bertambah sesuai campuran |
| T07 | POS stok kurang/nonaktif | Ditolak tanpa penjualan/kas parsial |
| T08 | Diskon persen/rupiah dan pembulatan | Batas benar, Σ diskon item = diskon nota |
| T09 | Bayar awal 0 dan 200 ribu | Piutang benar; hanya uang aktual masuk kas |
| T10 | Pelunasan 800 ribu dengan retry | Kas tambahan tepat 800 ribu, total penjualan tetap |
| T11 | Pembayaran melebihi sisa | Ditolak; saldo tetap konsisten |
| T12 | Dua tab menjual stok terakhir | Hanya operasi yang stoknya mencukupi berhasil |
| T13 | Simpan gaji, retry | Satu payroll dan satu pengeluaran |
| T14 | Gaji produksi vs operasional | Tidak dikurangkan dua kali dari laba |
| T15 | Modal tertimbang dan snapshot | Modal baru benar, laba penjualan lama tetap |
| T16 | Warisan biaya bahan lanjutan | Tidak membuat kas baru; total alokasi biaya terjaga |
| T17 | Filter pelanggan+produk+tanggal+status | Tabel, kartu, Excel memakai kumpulan nota sama |
| T18 | Satu nota berisi banyak produk/pembayaran | Total nota dihitung sekali |
| T19 | Export lintas pagination dan teks berawalan = | Semua hasil diekspor; teks tidak berubah menjadi formula |
| T20 | Hapus berelasi/nonaktif | Hapus berelasi ditolak server, data historis tetap |
| T21 | Cache setelah mutasi/rollback | Ringkasan segar setelah commit; rollback tidak meninggalkan efek |
| T22 | Daftar 5 vs 25 baris | Tidak muncul pertambahan query per baris/N+1 |
| T23 | Kas otomatis diedit dari menu kas | Ditolak; link sumber tersedia |
| T24 | Ubah nama toko/password | Nama konsisten; password lama wajib, sesi sesuai kebijakan |
| T25 | Akses tanpa login/ID tidak sah | Halaman, action dan export terlindungi |
| T26 | Pembelian belum dibayar lalu dikonfirmasi | Kas baru muncul sekali pada tanggal bayar |
| T27 | Periode penjualan beda tanggal pembayaran | Kartu riwayat kumulatif benar; kas memakai tanggal pembayaran |
| T28 | Penyesuaian stok/saldo awal | Tidak membuat pemasukan/pengeluaran atau penjualan |

Jalankan tes integrasi dengan **MySQL 8.4** agar foreign key, decimal, row lock, dan query sesuai produksi. Tes konkurensi memakai koneksi/proses berbeda; test transaction biasa pada satu koneksi tidak cukup. `RefreshDatabase` hanya pada database test. Simpan fixture yang mencakup produk nonaktif, stok nol, pecahan kg, nota campuran, piutang sebagian, biaya belum dibayar, dan gaji kedua jenis.

## 15. Pengembangan dengan Spiral

| Siklus | Hasil | Risiko dan gate |
|---|---|---|
| 1 | PRD, desain semua layar, ERD, skeleton Railway staging | Owner memvalidasi alur dan referensi visual; D01–D04 diputuskan |
| 2 | Produk, tiga tracking, bahan lanjutan, posting stok | T01–T06, T15–T16, T28; tidak ada stok/bahan ganda |
| 3 | POS, riwayat, piutang, pelanggan, Excel, nota | T07–T12, T17–T19, T27; stok/kas konsisten |
| 4 | Karyawan, gaji, keuangan, dashboard, pengaturan | Seluruh integrasi, N+1, cache, UAT dan deployment |

Setiap siklus: tetapkan tujuan, analisis risiko, bangun dan uji, lalu evaluasi bersama owner. Temuan dicatat oleh Arya, prioritas dikelola Nafis, dampak alur/data ditinjau Iman, antarmuka oleh Enggal, backend/database oleh Aji.

## 16. Matriks kelengkapan ke desain

| Kebutuhan | Bagian sumber | ID layar pada desain.md |
|---|---|---|
| F00 Login/logout | Pengguna owner dan password | S00 |
| F01 Dashboard | Tabel menu Dashboard | S01 |
| F02 Produk/stok | Bagian 2 dan tabel Produk | S02, M02A–M02C |
| F03 Tracking gabah | Bagian 3 dan tabel Gabah | S03L, S03D, M03A, MTR1, MTR2, MTR4, MTR5 |
| F04 Tracking dedek | Bagian 2.2/3 dan tabel Dedek | S04L, S04D, M04A, MTR1, MTR4, MTR5 |
| F05 Tracking pupuk | Bagian 2.2/3 dan tabel Pupuk | S05L, S05D, M05A, MTR1, MTR3, MTR4, MTR5 |
| F06 POS dan nota | Bagian 4.1 dan tabel POS | S06, S06N |
| F07 Riwayat/piutang/Excel | Bagian 4.1/6 dan tabel Riwayat | S07, S07D, M07A–M07C |
| F08 Pelanggan | Tabel Pelanggan | S08, S08D, M08A |
| F09 Karyawan | Tabel Karyawan | S09, M09A |
| F10 Gaji | Bagian 4.2 dan tabel Gaji | S10, M10A |
| F11 Pemasukan | Bagian 4.2 dan tabel Pemasukan | S11, M11A |
| F12 Pengeluaran | Bagian 4.2/4.3 dan tabel Pengeluaran | S12, M12A |
| F13 Pengaturan | Tabel Pengaturan | S13 |
| Aturan lintas menu | Bagian 6 | G01–G08 di desain.md |

Selesai berarti seluruh kebutuhan mempunyai layar/form, aturan server, serta skenario penerimaan. Kelengkapan tidak dinilai hanya dari banyaknya halaman yang tampak pada sidebar.

## 17. Sumber teknis

Sumber bisnis utama: lampiran owner `Breakdown_Sistem_Lumbung_Beras (1)(1).docx`. Referensi berikut untuk keputusan teknis, diperiksa 23 September 2026.

- [S1 Laravel 13 release notes](https://laravel.com/docs/13.x/releases)
- [S2 Railpack PHP](https://railpack.com/languages/php/)
- [S3 Livewire installation](https://livewire.laravel.com/docs/4.x/installation) dan [manifest resmi](https://github.com/livewire/livewire/blob/main/composer.json)
- [S4 Railway MySQL](https://docs.railway.com/databases/mysql) dan [persistensi database](https://docs.railway.com/databases/reference)
- [S5 Railpack Node.js](https://railpack.com/languages/node)
- [S6 Vite requirements](https://vite.dev/guide/)
- [S7 Tailwind dengan Laravel/Vite](https://tailwindcss.com/docs/installation/framework-guides/laravel/vite)
- [S8 package.json Laravel 13](https://github.com/laravel/laravel/blob/13.x/package.json)
- [S9 Alpine bawaan Livewire](https://livewire.laravel.com/docs/4.x/alpine)
- [S10 OpenSpout metadata rilis](https://packagist.org/packages/openspout/openspout) dan [manifest resmi](https://github.com/openspout/openspout/blob/5.x/composer.json)
- [S11 composer.json Laravel 13](https://github.com/laravel/laravel/blob/13.x/composer.json) dan [Laravel testing](https://laravel.com/docs/13.x/testing)
- [S12 Laravel cache](https://laravel.com/docs/13.x/cache)
- [S13 Eloquent eager loading dan lazy loading](https://laravel.com/docs/13.x/eloquent-relationships)
- [S14 Railway pre-deploy](https://docs.railway.com/deployments/pre-deploy-command)
- [S15 Railway healthchecks](https://docs.railway.com/deployments/healthchecks)
- [S16 Laravel validation](https://laravel.com/docs/13.x/validation) dan [transaksi database](https://laravel.com/docs/13.x/database)
- [S17 Railway private networking](https://docs.railway.com/networking/private-networking)
