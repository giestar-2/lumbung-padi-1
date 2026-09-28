# Desain Sistem Manajemen Lumbung Beras — Brief untuk Google Stitch

Versi 1.0 · 23 September 2026 · Pendamping `PRD.md`.

Gunakan dokumen ini bersama gambar referensi desain yang dikirim owner. Buat rancangan aplikasi web berbahasa Indonesia, responsif, dan lengkap untuk satu owner usaha lumbung beras. Hasil yang diminta adalah desain layar aplikasi beserta variasi interaksinya, bukan landing page atau slide presentasi.

## 1. Instruksi utama untuk Stitch

**Gambar referensi yang disertakan owner adalah acuan visual utama. Dokumen ini menentukan fitur, isi, hubungan antarlayar, dan perilakunya. Keduanya harus terpenuhi.**

1. Baca semua referensi gambar terlebih dahulu. Identifikasi warna, tipografi, sidebar, top bar, bentuk kartu, kepadatan tabel, jarak, ikon, tombol, input, radius, border, dan bayangan yang digunakan.
2. Terapkan bahasa visual tersebut secara konsisten pada seluruh layar. Pertahankan karakter desain referensi; jangan menggantinya dengan template dashboard generik atau tema baru pilihan sendiri.
3. Jika gambar hanya memperlihatkan sebagian layar, turunkan komponen lain dari pola visual yang sama. Ketiadaan contoh modal, tabel, atau layar tertentu bukan alasan untuk menghilangkan fitur.
4. Jika owner menandai referensi utama, gunakan referensi itu sebagai dasar. Gambar lainnya menjadi acuan komponen yang disebut owner. Jika tidak ditandai, gambar pertama menjadi dasar dan gambar berikutnya melengkapi; jangan mencampur gaya yang saling bertentangan.
5. Contoh data, bahasa, logo, menu, dan fitur produk lain di dalam gambar tidak otomatis menjadi kebutuhan aplikasi ini. Ganti isinya dengan konteks lumbung beras sesuai dokumen.
6. Bila referensi memakai tata letak berbeda dari saran dalam dokumen ini, ikuti komposisi referensi selama semua informasi, aksi, dan keadaan wajib tetap tersedia dan mudah digunakan.
7. Jangan menghapus fitur untuk membuat tampilan lebih bersih. Gunakan pengelompokan, panel detail, modal, atau drawer yang konsisten dengan referensi.
8. Warna fallback biru hanya dipakai jika referensi tidak menentukan warna. Bila referensi menentukan warna lain, referensi menang. Preferensi biru pada materi presentasi tidak mengunci tema aplikasi.
9. Jaga keterbacaan dan kontras. Jika ada elemen referensi yang terlalu kecil atau sulit dibaca, lakukan penyesuaian minimum sambil mempertahankan karakter visualnya.
10. Buat seluruh layar dan varian pada inventaris bagian 5–8. Jika pengerjaan harus dibagi beberapa putaran, gunakan komponen dan token yang sama, lalu lanjutkan sampai semua ID tercakup. Jangan menyatakan lengkap bila baru membuat Dashboard.

Prioritas keputusan: instruksi terbaru owner → breakdown/PRD untuk fungsi → gambar referensi untuk visual → saran tata letak dalam dokumen ini. Jika visual bertentangan dengan fungsi, sesuaikan wadah visualnya tanpa menghilangkan fungsi.

Dokumen ini merupakan brief rancangan. Kesesuaian hasil generasi tetap diperiksa menggunakan checklist pada bagian 12; dokumen tidak menjamin Stitch akan mengikuti semua instruksi tanpa iterasi.

## 2. Konteks produk dan batas cakupan

Aplikasi menghubungkan pengolahan gabah, stok siap jual, penjualan, piutang, gaji, pemasukan, dan pengeluaran. Pengguna hanya satu owner. Data karyawan dipakai untuk pencatatan gaji, bukan akun staf.

Istilah **dedek** dan **merang/pupuk** mengikuti bahasa usaha owner. Semua berat menggunakan **kg**, termasuk pecahan. Format angka Indonesia: `1.250,500 kg`, `Rp1.250.000`; tampilkan desimal uang hanya jika diperlukan. Tanggal menggunakan bahasa Indonesia dan zona waktu Asia/Jakarta.

Tiga perbedaan yang harus tampak jelas:

- Bahan di tracking belum menjadi stok produk siap jual.
- Produksi **Selesai** belum berarti **Sudah Masuk Stok**.
- Nilai penjualan berbeda dari uang yang sudah diterima dan sisa piutang.

Tidak membuat menu pembelian gabah, stok, laporan, atau piutang yang terpisah. Pembelian berada di tracking; stok berada di Produk; ringkasan berada di Dashboard/Produk; piutang berada di Riwayat Penjualan. Tidak membuat multi-user, role, absensi, gaji otomatis, unggah bukti transaksi, retur/refund, portal pelanggan, chat, integrasi pembayaran, pengiriman, pajak, atau pengaturan teknis deployment.

Teknologi implementasi adalah Laravel, Livewire, MySQL, Tailwind, CSS dan JavaScript. Desain sebaiknya dapat diterapkan sebagai komponen Blade/Livewire. Cache, index database, pengujian, dan Railway tidak perlu muncul sebagai fitur di antarmuka owner.

## 3. Sistem visual, navigasi, dan komponen

### 3.1 Token dan gaya

Turunkan token dari referensi: warna utama/sekunder, latar, permukaan, teks, border, font, ukuran judul/isi/label, jarak, radius dan bayangan. Gunakan satu keluarga ikon yang seragam. Jangan menambah gradient besar, ilustrasi dekoratif, grafik acak, atau efek kaca bila referensi tidak menggunakannya.

Fallback bila referensi tidak memberi arah: latar netral terang, aksen biru `#2563EB`, teks gelap `#0F172A`, kartu putih, border lembut, font sans-serif yang terbaca, dan ruang antarbagian yang cukup. Ini fallback, bukan instruksi untuk menimpa gambar referensi.

Angka uang/kg rata kanan dan memakai angka dengan lebar konsisten. Jangan memotong nominal penting tanpa cara melihat nilai utuh. Status harus menggunakan teks dan ikon, bukan warna saja. Gaya sukses/peringatan/error mengikuti token referensi dengan kontras yang memadai.

### 3.2 Kerangka aplikasi

Desktop: sidebar, top bar, judul halaman, deskripsi singkat bila membantu, aksi utama, ringkasan, filter, lalu konten. Nama toko tampil di sidebar/top bar sesuai komposisi referensi. Gunakan menu aktif dan breadcrumb pada detail batch/transaksi.

Top bar cukup berisi konteks halaman dan akses akun owner/logout. Jangan menambahkan pemilih organisasi, manajemen tim, atau notifikasi kosong yang tidak ada fungsinya. Semua tautan kembali harus mempertahankan filter daftar sebelumnya.

| Kelompok navigasi | Menu wajib | Saran ikon yang sesuai konteks |
|---|---|---|
| Ringkasan | Dashboard | Panel ringkasan |
| Produk dan Produksi | Manajemen Produk | Kotak atau karung |
| Produk dan Produksi | Tracking Gabah ke Beras | Bulir padi |
| Produk dan Produksi | Tracking Sisa Gabah ke Dedek | Penggilingan |
| Produk dan Produksi | Tracking Sisa Gabah ke Merang/Pupuk | Daun |
| Penjualan | Penjualan/POS | Keranjang atau kasir |
| Penjualan | Riwayat Penjualan dan Piutang | Nota |
| Data Usaha | Manajemen Pelanggan | Pelanggan |
| Data Usaha | Manajemen Karyawan | Identitas karyawan |
| Keuangan | Manajemen Gaji | Dompet |
| Keuangan | Manajemen Pemasukan | Uang masuk |
| Keuangan | Manajemen Pengeluaran | Uang keluar |
| Sistem | Pengaturan | Pengaturan |

Kelompok hanya membantu navigasi; tidak menjadi menu baru. Label panjang boleh dibungkus atau memakai label pendek di sidebar, tetapi judul layar memakai nama lengkap. Jangan mengganti tiga tracking dengan satu halaman umum yang menyembunyikan perbedaannya.

### 3.3 Komponen yang dipakai ulang

- Kartu ringkasan: label, nilai, satuan/keterangan cakupan, dan ikon yang relevan. Empat kartu wajib tetap empat pada modul yang ditentukan.
- Filter: pencarian, select yang dapat dicari, rentang tanggal, chip filter aktif, Reset Filter, jumlah hasil. Empat kartu dan tabel berubah bersama saat filter diterapkan.
- Tabel: header jelas, nominal rata kanan, status badge, menu aksi, pagination 10/25/50. Pada pengurutan, tampilkan arah sort yang aktif.
- Form: label selalu terlihat, penanda wajib, label “Opsional”, helper singkat, error dekat field, dan ringkasan error bila diperlukan.
- Modal/drawer: judul, isi, tombol Batal/Tutup dan satu aksi utama. Form panjang memakai panel berukuran cukup atau layar penuh di ponsel.
- Konfirmasi: ringkasan dampak yang konkret sebelum menyimpan stok, pembayaran, atau penghapusan.
- Feedback: loading/skeleton, tombol sedang menyimpan, sukses, gagal, kosong, tidak ada hasil filter, dan data terkunci.

Untuk filter yang perlu tombol Terapkan, tampilkan bahwa perubahan belum diterapkan. Kartu, tabel dan ekspor selalu memakai filter yang sudah aktif. Jangan memperbarui tabel tetapi membiarkan kartu memakai filter lama.

### 3.4 Responsivitas dan aksesibilitas

Rancang desktop sekitar 1440 px dan mobile sekitar 390 px; perilaku tablet harus masuk akal. Sidebar menjadi drawer di mobile. Empat kartu menjadi dua kolom atau satu kolom tanpa kehilangan informasi. Filter dapat masuk panel; chip filter tetap terlihat.

Tabel mobile berubah menjadi kartu baris atau tabel scroll horizontal yang jelas, dengan identitas dan aksi penting mudah dicapai. POS harus menjaga keranjang, total, dan tombol konfirmasi tetap terjangkau. Blok tracking disusun vertikal pada layar sempit dan tetap menampilkan urutan lengkap.

Semua aksi bisa dijangkau keyboard, memiliki fokus terlihat dan nama aksesibel. Target sentuh sekitar 44 px. Ikon tanpa teks mempunyai label/tooltip; informasi penting tidak hanya tersedia melalui hover. Modal mengelola fokus dengan benar dan bisa ditutup ketika tidak sedang memproses penyimpanan.

## 4. Aturan isi dan perilaku yang berlaku di semua layar

1. **Hapus tersedia di semua CRUD.** Aktif hanya untuk data tanpa relasi bisnis. Untuk data berelasi, tampilkan nonaktif dengan penjelasan seperti “Produk sudah digunakan pada transaksi”. Jangan mengganti semua aksi hapus menjadi arsip.
2. Produk dan karyawan memakai **Aktif/Nonaktif**. Data nonaktif tetap terbaca pada transaksi lama, tetapi tidak dipilih untuk transaksi baru.
3. Sumber otomatis mempunyai badge asal dan tautan **Buka Transaksi Asal**. Catatan ini tidak diedit/dihapus terpisah sebagai catatan manual.
4. Klik ganda, loading, dan retry tidak boleh tampak membuat dua transaksi. Setelah berhasil, tampilkan hasil yang tersimpan dan keadaan tombol yang sesuai.
5. Berat hasil tracking yang sudah ada tidak diminta ulang saat masuk produk. Tampilkan read-only beserta produk tujuan dan dampak stok.
6. Penyesuaian stok tidak meminta alasan wajib dan tidak membuka riwayat pergerakan stok.
7. Form pemasukan/pengeluaran tidak mempunyai unggah bukti. Catatan/keterangan bersifat opsional.
8. Transaksi penjualan terkonfirmasi mengunci item, harga, kg dan diskon. Penambahan pembayaran masih tersedia selama ada sisa tagihan. Berat dan biaya batch dikunci setelah digunakan stok atau batch turunan.
9. Bila penyimpanan ditolak karena stok/sisa/piutang berubah di tab lain, pertahankan input yang masih sah, tampilkan nilai terbaru dan minta owner meninjau ulang. Jangan hanya memberikan “Error”.
10. Semua angka contoh adalah ilustrasi, bukan rendemen atau ketentuan harga usaha.

## 5. Inventaris layar utama

Setiap ID berikut harus memiliki desain yang dapat ditinjau. Jangan menganggap satu gambar Dashboard sudah mewakili seluruh aplikasi.

| ID | Layar | Fokus wajib |
|---|---|---|
| S00 | Login | Akun owner, password, error dan sesi kedaluwarsa |
| S01 | Dashboard | Ringkasan, grafik kas, transaksi, stok, laba produk, batch, jatuh tempo |
| S02 | Manajemen Produk | Empat kartu, filter, tabel, CRUD, stok dan status |
| S03L | Daftar Batch Gabah | Pencarian, filter, Buat Batch, Buka Tracking |
| S03D | Dashboard Batch Gabah | Empat blok tahapan, hasil, sisa bahan, biaya, masuk stok |
| S04L | Daftar Batch Dedek | Daftar dan sumber batch asal |
| S04D | Dashboard Batch Dedek | Tiga blok tahapan dan hasil |
| S05L | Daftar Batch Merang/Pupuk | Daftar dan sumber batch asal |
| S05D | Dashboard Batch Merang/Pupuk | Tiga blok, campuran, hasil |
| S06 | Penjualan/POS | Produk, keranjang kg, diskon, pelanggan, pembayaran |
| S06N | Hasil Transaksi dan Nota | Hasil simpan, nota siap cetak, transaksi baru |
| S07 | Riwayat Penjualan dan Piutang | Empat kartu, filter gabungan, pembayaran, ekspor |
| S07D | Detail Transaksi | Seluruh item, diskon, pembayaran, sisa tagihan |
| S08 | Manajemen Pelanggan | Pencarian, tabel dan CRUD |
| S08D | Detail Pelanggan | Data, pembelian, piutang, riwayat terfilter |
| S09 | Manajemen Karyawan | Pencarian, filter, tarif default dan status |
| S10 | Manajemen Gaji | Empat kartu, pembayaran manual, filter |
| S11 | Manajemen Pemasukan | Empat kartu, manual/otomatis, sumber |
| S12 | Manajemen Pengeluaran | Empat kartu, kategori, sumber, klasifikasi |
| S13 | Pengaturan | Nama toko dan password |

### S00 — Login

Ikuti gaya referensi untuk panel login. Isi: nama toko, email, password, tampil/sembunyikan password, tombol Masuk. Sediakan error kredensial, pembatasan percobaan, loading, dan sesi kedaluwarsa. Tidak ada daftar akun publik, login karyawan, atau pilihan role.

### S01 — Dashboard

Judul Dashboard; periode default Bulan Ini, pilihan Hari Ini/Bulan Ini/Rentang Tanggal.

Enam kartu utama:

| Kartu | Isi dan bantuan singkat |
|---|---|
| Penjualan Bersih | Total transaksi setelah diskon pada tanggal penjualan terpilih |
| Pemasukan Kas | Uang yang diterima pada periode |
| Pengeluaran Kas | Uang yang dibayar pada periode |
| Laba Usaha | Penjualan bersih − HPP − biaya operasional |
| Sisa Piutang Transaksi Periode | Sisa tagihan dari transaksi penjualan periode terpilih |
| Arus Kas Bersih | Pemasukan kas − pengeluaran kas |

Komponen di bawah kartu:

- Grafik pemasukan/pengeluaran per waktu, label rupiah, legenda, tooltip nilai/tanggal, dan keadaan kosong.
- Transaksi terbaru: nomor, tanggal, pelanggan, total, status, tautan detail serta Lihat Semua.
- Produk stok menipis: produk aktif, stok sekarang, batas minimum, badge Habis bila nol, tautan Produk. Label “Stok saat ini”.
- Produk paling menguntungkan: nama, kg terjual, nilai penjualan bersih item, laba kotor. Urut berdasarkan laba item setelah diskon dan modal, bukan jumlah terjual.
- Progres batch: jenis, nomor batch, tahap aktif, tautan Buka Tracking. Cakupan periode memakai tanggal batch.
- Pengingat jatuh tempo: pelanggan, nomor, jatuh tempo, sisa tagihan, aksi detail. Hanya tagihan belum lunas dengan tanggal jatuh tempo; label “Tagihan terbuka saat ini”.

Tidak membuat grafik dekoratif tanpa makna. Hindari menyatakan kas bersih sebagai keuntungan. Stok saat ini dan pengingat global tidak berubah menjadi data historis saat filter periode berubah.

### S02 — Manajemen Produk

Header dengan Tambah Produk. Empat kartu persis:

1. **Total Harga Produk** — jumlah stok × harga jual/kg; label “Nilai persediaan berdasarkan harga jual”.
2. **Total Harga Modal** — nilai modal persediaan saat ini.
3. **Jumlah Produk** — jumlah jenis produk, bukan total kg.
4. **Produk Stok Menipis** — jumlah produk aktif pada/di bawah batas minimum.

Filter: cari nama, kategori, status Semua/Aktif/Nonaktif, stok Semua/Menipis. Default status Semua. Semua kartu mengikuti hasil filter; kartu menipis tetap hanya menghitung yang aktif. Tabel: produk, kategori, stok kg, minimum, modal/kg, jual/kg, status, aksi.

Aksi baris: Detail, Edit, Sesuaikan Stok, Nonaktifkan/Aktifkan, Lihat Penjualan, Hapus. Lihat Penjualan menuju S07 dengan produk terpilih. Tidak ada grafik penjualan per produk atau histori pergerakan stok baru di halaman ini.

Form dan panel:

| ID | Rancangan |
|---|---|
| M02A | Tambah/Edit Produk: nama, kategori, harga modal/kg, harga jual/kg, saldo awal kg saat tambah biasa, batas minimum, status, asal produk opsional, catatan opsional. Saat edit produk yang sudah ada, perubahan kuantitas melalui M02C. |
| M02B | Detail Produk: seluruh data, stok, nilai persediaan, asal/catatan, status, dan akses Lihat Penjualan. Relasi batch tampil sebagai asal yang dapat ditelusuri jika ada, tanpa mengubahnya menjadi histori pergeseran stok. |
| M02C | Sesuaikan Stok: stok saat ini, input stok aktual akhir, selisih otomatis, pratinjau nilai persediaan, Batal dan Simpan Penyesuaian. Tidak meminta alasan wajib. |

Varian M02A dari tracking: judul Tambah Produk untuk Hasil Batch, kategori sesuai hasil, stok awal `0 kg` read-only, berat batch tetap pada ringkasan terpisah. Setelah Simpan Produk, kembali ke pemilihan produk MTR4 dengan produk baru terpilih. Membuat produk saja belum menambah stok.

Saat edit harga modal, tampilkan dampak pada nilai persediaan dan bantuan “Perubahan ini tidak mengubah modal penjualan sebelumnya”. Status tidak memakai label Arsip.

### S03L / S04L / S05L — Daftar Batch

Ketiga layar memakai pola tabel yang sama dengan nama, ikon dan istilah bahan yang sesuai. Aksi utama **Buat Batch**. Filter: cari nomor/nama, rentang tanggal batch, tahap. Tabel: nomor/nama, tanggal, asal bahan, berat awal kg, tahap saat ini, status masuk stok, Buka Tracking, serta Edit/Hapus menurut relasi.

Setelah form berhasil disimpan, kembali ke daftar dan tampilkan baris batch baru dengan badge Belum Diproses. Owner menekan Buka Tracking untuk masuk dashboard batch tersebut. Tidak langsung memasukkan bahan ke Produk.

| ID | Form Buat Batch |
|---|---|
| M03A | Gabah: nomor/nama, tanggal penerimaan, asal/pemasok, berat awal kg, harga beli/kg, total pembelian otomatis, biaya tambahan bila ada, status pembayaran, tanggal bayar bila sudah dibayar, catatan opsional. Penerimaan tanpa pembelian dapat memiliki biaya nol. |
| M04A | Dedek: nomor/nama, tanggal, pilih batch gabah asal dengan bahan dedek tersedia, sisa tersedia read-only, berat yang digunakan, catatan opsional. Tampilkan sisa sesudah alokasi. |
| M05A | Merang/Pupuk: sama seperti M04A, hanya memakai bahan tujuan merang/pupuk. |

Pencarian batch asal menampilkan nomor, tanggal dan sisa bahan. Sumber habis tetap dapat dijelaskan tetapi tidak dipilih. Bila belum ada bahan tersedia, arahkan ke Tracking Gabah tanpa menghapus input form. Satu batch lanjutan memakai satu batch asal; satu asal dapat dipakai beberapa batch lanjutan sesuai sisa.

Alokasi terjadi saat form disimpan. Menutup form yang belum disimpan tidak menghabiskan bahan. Batch lanjutan yang sudah tersimpan memiliki relasi sumber sehingga asal/berat alokasi dan Hapus terkunci. Tidak membuat fitur pembatalan batch tersimpan.

### S03D — Dashboard Satu Batch Gabah

Breadcrumb: Tracking Gabah ke Beras → nomor batch. Header memuat nomor/nama, tanggal, asal, berat awal, tahap aktif, status stok dan kembali ke daftar. Sediakan ringkasan biaya dengan akses detail biaya.

**Blok tahapan menunjukkan satu batch yang sedang dibuka, bukan kartu-kartu batch dari seluruh produksi.** Jangan mengubahnya menjadi papan Kanban kumpulan batch atau drag-and-drop status.

| Blok | Isi dan aksi |
|---|---|
| Belum Diproses | Data penerimaan/pembelian, berat awal, status pembayaran, Mulai Pengeringan |
| Pengeringan | Tanggal mulai/selesai, berat masuk, hasil kering, susut, biaya, catatan; Simpan Hasil Pengeringan untuk lanjut Pemisahan |
| Pemisahan | Berat masuk, hasil beras, bahan untuk dedek, bahan untuk merang/pupuk, susut/sisa tidak terpakai; Catat Hasil Pemisahan |
| Selesai | Berat beras final, tanggal, modal hasil, status biaya, Tambahkan ke Produk dan status Belum/Sudah Masuk Stok |

Tampilkan tahap selesai, aktif dan belum tersedia dengan teks/ikon yang berbeda. Hanya tahap aktif dapat dilanjutkan. Tahap lama bisa dibuka untuk membaca hasil. Tidak mengharuskan berat akhir sama dengan berat awal.

Di bawah tahapan, tampilkan dua ringkasan bahan lanjutan: **Untuk Dedek** dan **Untuk Merang/Pupuk**, masing-masing berisi total hasil, sudah dialokasikan, sisa tersedia dan tautan batch lanjutan. Aksi Buat Batch Dedek/Pupuk boleh membuka form terkait dengan sumber terisi. Bahan baru dapat dipakai setelah hasil dan biaya sumber difinalkan.

Tampilkan Selesai Produksi dan status masuk stok sebagai dua informasi berbeda. Jika sudah masuk stok, tampilkan produk tujuan, berat, tanggal konfirmasi dan tautan produk; tombol Tambahkan ke Produk nonaktif/berganti menjadi Sudah Masuk Stok.

### S04D — Dashboard Satu Batch Dedek

Header sama seperti S03D; asal menaut ke batch gabah dan memperlihatkan berat bahan yang dialokasikan. Tiga blok persis:

1. **Belum Diproses** — identitas bahan dan aksi Mulai Penggilingan.
2. **Digiling** — tanggal proses, berat masuk, biaya, catatan, berat dedek aktual; Simpan Hasil Penggilingan.
3. **Selesai** — berat dedek final, susut, modal hasil, Tambahkan ke Produk dan status stok.

Hasil tidak boleh melebihi bahan masuk. Hasil nol dapat menutup proses dengan label Tidak Ada Hasil; tidak ada posting stok. Ringkasan biaya membedakan nilai bahan dari batch asal dan tambahan biaya proses; biaya warisan bukan pembayaran baru.

### S05D — Dashboard Satu Batch Merang/Pupuk

Header sama dengan S04D. Tiga blok persis:

1. **Belum Diproses** — asal dan berat bahan; Mulai Pencampuran.
2. **Pencampuran dengan Bahan Lain** — bahan asal, daftar campuran, total berat masuk, biaya dan catatan; Catat Hasil Pencampuran.
3. **Selesai** — hasil aktual, susut, modal, Tambahkan ke Produk dan status stok.

Label pendek Pencampuran boleh dipakai pada navigasi sempit; judul/detail tetap menampilkan nama lengkap. Sediakan tabel campuran: nama/jenis bahan, kg, biaya, status pembayaran dan aksi baris. Berat hasil boleh lebih besar dari bahan asal, tetapi tidak melebihi bahan asal + campuran tercatat.

### Dialog tracking bersama — MTR1 sampai MTR5

| ID | Isi wajib dan varian |
|---|---|
| MTR1 — Proses | Varian Mulai Tahap dengan tanggal/catatan; varian Hasil Pengeringan atau Penggilingan dengan tanggal, berat masuk read-only, hasil aktual, susut otomatis, biaya tambahan, status/tanggal pembayaran, catatan opsional. Ringkasan menunjukkan tahap berikutnya. |
| MTR2 — Hasil Pemisahan | Tanggal, berat masuk read-only, berat beras, sisa untuk dedek, sisa untuk merang/pupuk, susut/sisa tidak terpakai, biaya proses, catatan. Tampilkan total tercatat dan selisih; cegah total melebihi bahan masuk. |
| MTR3 — Campuran dan Hasil | Baris campuran yang dapat ditambah/dihapus sebelum dikunci: nama/jenis, kg, biaya, status/tanggal pembayaran. Berat asal read-only, total tambahan, total masuk, hasil aktual, susut, biaya proses lain, tanggal dan catatan. |
| MTR4 — Tambahkan ke Produk | Batch, jenis hasil, berat otomatis read-only, modal hasil, pilih produk aktif yang sesuai, pencarian dan Tambah Produk Baru. Pratinjau produk tujuan, stok sebelum, tambahan kg, stok sesudah, dan modal setelah penambahan. Aksi Konfirmasi Masuk Stok. |
| MTR5 — Biaya Batch | Daftar biaya bahan, proses, campuran dan gaji terkait; nominal, sumber, pembayaran, total. Varian alokasi biaya ke hasil, finalisasi, serta konfirmasi pembayaran biaya yang belum dibayar. |

MTR4 memiliki kondisi daftar kosong/tidak ditemukan, produk nonaktif, biaya belum final, hasil nol, konfirmasi berjalan, gagal karena data berubah, berhasil, dan sudah pernah diposting. Berat tidak dapat diketik ulang. Satu hasil masuk seluruhnya ke satu produk dan hanya sekali. Jika biaya belum final, tampilkan Lengkapi Pembagian Biaya menuju MTR5.

MTR5 untuk gabah menyediakan alokasi nominal ke beras, bahan dedek dan bahan merang/pupuk, total biaya serta sisa belum dialokasikan. Finalisasi baru aktif bila pembagian valid. Pada batch lanjutan, tampilkan biaya bahan warisan + biaya tambahan, tanpa menggandakan pengeluaran bahan asal. Nilai final terkunci setelah posting atau dipakai batch turunan. Konfirmasi pembayaran menampilkan nominal komponen yang belum dibayar dan tanggal; pembayaran penuh satu kali per komponen, tanpa fitur cicilan pemasok.

**Catatan untuk reviewer, bukan banner wajib antarmuka:** rata-rata modal, pembagian biaya, tanggal pengakuan gaji dan pembayaran biaya mengikuti baseline D01–D04 di PRD yang perlu validasi tim. Buat wadah UI-nya, tetapi jangan mengarang rasio pembagian biaya tetap.

### S06 — Penjualan/POS

Desktop mengikuti komposisi referensi, dengan katalog produk dan keranjang/ringkasan pembayaran terlihat bersama. Mobile boleh memakai dua panel dengan ringkasan total tetap mudah dijangkau; keranjang tidak hilang saat pindah panel.

Katalog: pencarian nama, kategori, hanya produk aktif, nama, harga/kg, stok tersedia, Tambah. Produk habis tidak dapat ditambahkan. Keranjang: produk, harga/kg, kuantitas kg desimal, subtotal, hapus item. Pemilihan produk yang sama menambah kuantitas pada baris yang sama.

Bagian pembayaran:

- Pilih pelanggan yang dapat dicari dan Tambah Pelanggan tanpa menghilangkan keranjang.
- Subtotal; pilih diskon **Tidak Ada / Persen / Rupiah**; input nilai dengan unit yang jelas; nominal potongan; total akhir.
- Pembayaran awal aktual dan sisa tagihan otomatis. Sediakan pilihan praktis Bayar Lunas / Bayar Sebagian / Bayar di Akhir yang mengisi/mengarahkan nominal secara konsisten.
- Bayar di Akhir = pembayaran `Rp0` dan seluruh total menjadi piutang.
- Jika ada sisa tagihan, pelanggan wajib; jatuh tempo opsional muncul. Jika lunas tanpa pelanggan, tampilkan Umum.
- Konfirmasi Penjualan dengan ringkasan kg, total, dibayar dan sisa.

Diskon persen 0–100, nominal maksimal subtotal. Satu jenis diskon per nota. Bayar tidak melebihi total; tidak menambah fitur uang diserahkan/kembalian pada versi ini. Konfirmasi nonaktif bila keranjang kosong atau field invalid. Kesalahan stok menyorot item terkait dan menunjukkan stok terbaru.

### S06N — Hasil Transaksi dan Nota

Setelah simpan: nomor nota, status, total, sudah dibayar, sisa, Lihat/Cetak Nota, Buka Detail, Transaksi Baru. Jangan mengarahkan penjualan kredit ke formulir pembayaran yang memaksa lunas.

Nota siap cetak: nama toko, nomor, tanggal, pelanggan/Umum, nama item, kg, harga/kg, subtotal, diskon, total, pembayaran dan sisa. Tata letak cetak tidak memuat sidebar, tombol aplikasi atau elemen interaktif. Catat bahwa nota bisa memuat piutang; bukan selalu bukti lunas.

### S07 — Riwayat Penjualan dan Piutang

Empat kartu persis:

1. **Total Penjualan Setelah Diskon**.
2. **Jumlah Transaksi**.
3. **Total Sudah Dibayar**.
4. **Sisa Piutang**.

Filter gabungan: pencarian nomor nota, pelanggan, produk, tanggal/rentang tanggal **penjualan**, dan status pembayaran Semua/Belum Dibayar/Sebagian/Lunas. Sertakan Reset Filter dan chip filter. Tombol **Ekspor Excel** memakai filter yang sedang aktif. Empat kartu menghitung seluruh hasil filter, bukan hanya halaman tabel yang terlihat.

Tabel: nomor, tanggal, pelanggan, ringkasan produk, total setelah diskon, sudah dibayar, sisa, jatuh tempo bila ada, status, Detail Transaksi, aksi pembayaran, dan Hapus yang terkunci karena berelasi. Penanda Terlambat terpisah dari status pembayaran.

Penjelasan ringan di dekat filter produk atau ikon bantuan:

> Filter produk menampilkan nota yang memuat produk tersebut. Empat kartu menghitung seluruh nilai nota yang ditemukan. Ekspor mengikuti filter yang sama; rincian produk tersedia pada sheet terpisah.

Tambahkan helper kartu pembayaran: “Seluruh pembayaran yang tercatat untuk nota terpilih.” Tanggal filter memilih tanggal penjualan, sehingga pembayaran sesudah periode tersebut tetap masuk kartu jika berasal dari nota terpilih.

Pilihan status di baris harus tersedia sesuai breakdown. Desain berupa select/aksi status dengan pilihan **Tandai Lunas** saat masih ada sisa; pemilihan membuka konfirmasi, bukan langsung mengganti badge. Tidak membolehkan nota lunas kembali menjadi belum dibayar melalui dropdown.

### S07D — Detail Transaksi

Tampilkan nomor, tanggal penjualan, pelanggan, status, jatuh tempo opsional; seluruh item, kg, harga, subtotal, diskon teralokasi, nilai bersih, total nota; sudah dibayar dan sisa. Modal/HPP bisa berada pada rincian perhitungan laba yang dapat dibuka owner, tidak mengganggu nota pelanggan.

Riwayat pembayaran memuat tanggal, jenis Awal/Lanjutan/Pelunasan, nominal, dan keterangan bila ada. Aksi: Tambah Pembayaran, Tandai Lunas, Cetak Nota, kembali ke riwayat dengan filter sebelumnya. Saat datang dari filter produk, sorot item yang cocok; item lain tetap terlihat agar total nota mudah dipahami.

Item/harga/kg/diskon nota terkonfirmasi read-only. Saat lunas, aksi pembayaran nonaktif dengan penjelasan Lunas, bukan tetap membuka form.

| ID | Dialog |
|---|---|
| M07A — Tambah Pembayaran | Nomor/pelanggan, sisa sebelum bayar, tanggal, nominal positif maksimal sisa, keterangan opsional, sisa setelah bayar, Simpan Pembayaran. |
| M07B — Tandai Lunas | Nomor/pelanggan, sisa terbaru otomatis read-only, tanggal bayar, keterangan opsional, pesan “Mencatat pemasukan sebesar sisa tagihan”, Konfirmasi Pelunasan. |
| M07C — Ekspor Excel | Ringkasan filter aktif, jumlah nota seluruh halaman, cakupan empat sheet, persiapan unduhan, berhasil/gagal/tidak ada hasil. Tidak meminta filter kedua yang berbeda. |

Ekspor terdiri dari Ringkasan, Transaksi, Item Produk, Pembayaran. Transaksi satu baris per nota; Item Produk berisi item yang cocok dengan filter produk, atau seluruh item bila tanpa filter produk; Pembayaran memuat seluruh pembayaran nota terpilih. Tampilkan “Semua hasil filter, bukan hanya halaman ini”. Jika data berubah sebelum ekspor, gunakan keadaan data saat ekspor dimulai, dengan waktu snapshot pada file.

### S08 / S08D — Manajemen dan Detail Pelanggan

S08: Tambah Pelanggan, pencarian nama/telepon, tabel nama, telepon, alamat ringkas, aksi Detail/Edit/Hapus. Tidak menambah empat kartu baru sebagai syarat wajib modul ini.

M08A: nama wajib, telepon/alamat/catatan opsional, Simpan. Digunakan juga dari POS lalu kembali ke POS dengan pelanggan baru terpilih.

S08D: data lengkap dan ringkasan jumlah transaksi, total pembelian setelah diskon, total sudah dibayar, sisa piutang. Tombol Lihat Riwayat Penjualan mengaktifkan filter pelanggan di S07. Jika belum pernah transaksi, tampilkan keadaan kosong yang informatif. Pelanggan yang sudah berelasi tidak dapat dihapus.

### S09 — Manajemen Karyawan

Tambah Karyawan, pencarian nama/kontak, filter status Aktif/Nonaktif dan jenis gaji. Tabel: nama, kontak, posisi, jenis gaji default, tarif default, status, aksi. Aksi Edit, Aktifkan/Nonaktifkan, Hapus sesuai relasi.

M09A: nama wajib, kontak, posisi, jenis gaji default Harian/Bulanan, tarif default dengan satuan per hari/per bulan, status, catatan opsional. Bantuan: “Tarif digunakan sebagai isian awal saat mencatat gaji; pembayaran lama tidak berubah.” Tidak ada email login, role, jadwal kerja atau absensi.

### S10 — Manajemen Gaji

Empat kartu: **Total Gaji Dibayar**, **Total Gaji Harian**, **Total Gaji Bulanan**, **Jumlah Karyawan Dibayar**. Kartu terakhir menghitung orang unik. Filter tanggal pembayaran, karyawan, jenis Harian/Bulanan; seluruh kartu dan tabel mengikuti filter.

Tabel: tanggal bayar, karyawan, jenis, periode kerja, nominal, klasifikasi Operasional/Produksi, sumber batch jika ada, aksi detail/edit/hapus menurut penguncian dan relasi. Tombol Tambah Pembayaran Gaji.

M10A:

- Karyawan aktif yang dapat dicari, tanggal bayar, jenis, periode kerja mulai–akhir.
- Harian: tarif/hari × jumlah hari kerja dan nominal akhir. Bulanan: nominal default yang dapat disesuaikan. Nilai final selalu jelas.
- Klasifikasi Operasional (default) atau Biaya Produksi; pilih batch bila Produksi. Batch terkunci tidak dapat dipilih untuk tambahan biaya.
- Keterangan opsional. Ringkasan penerima, periode, jumlah dibayar, serta “Tercatat otomatis satu kali di Pengeluaran”.
- Tombol **Simpan Pembayaran Gaji**; menyimpan berarti uang sudah dibayar, bukan draft jadwal.
- Varian peringatan pembayaran karyawan/periode serupa, detail read-only setelah terkait data terkunci, dan perbaikan melalui sumber bila diizinkan PRD.

Label filter harus membedakan tanggal bayar dari periode kerja. Jangan menambahkan status Belum Dibayar/utang gaji, absensi atau pembayaran gaji otomatis.

### S11 — Manajemen Pemasukan

Empat kartu: **Total Pemasukan**, **Penerimaan Penjualan Langsung**, **Penerimaan Piutang**, **Pemasukan Lainnya**. Penerimaan Penjualan Langsung mencakup pembayaran awal POS, termasuk sebagian; pembayaran sesudah POS masuk Penerimaan Piutang.

Filter tanggal penerimaan, kategori, sumber; cari nama/nomor referensi. Tabel: tanggal, nama, kategori, nominal, sumber Manual/POS/Pembayaran Piutang, nomor referensi, aksi. Sumber otomatis memiliki Buka Transaksi Asal; Edit/Hapus terpisah nonaktif.

M11A: nama, nominal, tanggal, kategori, keterangan opsional. Bedakan Modal Pemilik dari Pemasukan Lainnya; tampilkan bantuan bahwa setoran modal bukan omzet/laba. Tidak ada unggah bukti. Manual dapat diedit/dihapus jika tidak dipakai relasi lain. Sediakan varian panel detail untuk data manual/otomatis.

### S12 — Manajemen Pengeluaran

Empat kartu: **Total Pengeluaran**, **Pembelian Bahan**, **Biaya Pengolahan**, **Gaji serta Operasional Lainnya**. Tiga kelompok bagian tidak tumpang tindih dan jumlahnya sama dengan total.

Filter tanggal pembayaran, kategori, sumber; cari nama/referensi. Tabel: tanggal, nama, kategori, nominal, sumber Manual/Pembelian Gabah/Pengolahan/Gaji, klasifikasi biaya, referensi, aksi. Pembelian gabah dan campuran masuk kelompok bahan; biaya proses selain gaji masuk pengolahan; seluruh pembayaran gaji masuk kelompok gaji, termasuk yang dipakai sebagai biaya produksi.

M12A: nama, nominal positif, tanggal bayar, kategori, klasifikasi Operasional/Produksi, batch tujuan wajib jika Produksi, keterangan opsional. Bantuan singkat menjelaskan bahwa klasifikasi menentukan biaya operasional atau modal batch. Tidak ada bukti transaksi. Simpan berarti pengeluaran aktual.

Catatan otomatis read-only dengan Buka Transaksi Asal. Menautkan biaya manual ke batch tidak membuat kas keluar kedua. Biaya pembelian yang belum dibayar hanya tampil sebagai biaya/status pada batch dan belum masuk daftar kas keluar. Sediakan panel detail untuk melihat perbedaan sumber dan klasifikasi tanpa membuat menu tambahan.

### S13 — Pengaturan

Dua kelompok form yang jelas:

1. **Nama Toko**: input wajib, pratinjau penampilan pada sidebar/top bar dan nota, Simpan Nama Toko; feedback berhasil/gagal.
2. **Ubah Password**: password lama, password baru, konfirmasi, tombol tampil/sembunyikan, helper minimal 12 karakter, Simpan Password. Error password lama/konfirmasi muncul dekat field. Setelah berhasil tampilkan bahwa owner perlu login ulang.

Jangan menambahkan pengaturan database, cache, API, role atau tema sebagai fitur baru. Tema mengikuti referensi desain pada tahap implementasi.

## 6. Keadaan bersama — G01 sampai G08

| ID | Keadaan | Bentuk yang harus dirancang |
|---|---|---|
| G01 | Loading dan menyimpan | Skeleton awal, loading perubahan filter, tombol dengan status Memproses; cegah klik ganda tanpa menghilangkan konteks. |
| G02 | Kosong dan tidak ada hasil | Bedakan belum ada data dengan hasil filter kosong. Tampilkan Tambah/Buat Batch pada data kosong dan Reset Filter pada pencarian kosong. |
| G03 | Validasi form | Field invalid, error jelas, fokus ke kesalahan, unit kg/%/Rp tidak ambigu, input tetap tersimpan di form. |
| G04 | Berhasil dan gagal simpan | Konfirmasi berhasil dengan objek yang berubah, kegagalan dengan opsi coba lagi dan informasi data yang tetap aman. |
| G05 | Hapus dan relasi | Konfirmasi nama item bila boleh hapus; tombol nonaktif beserta alasan jika berelasi. Termasuk konfirmasi Aktif/Nonaktif produk/karyawan. |
| G06 | Data terkunci dan konflik terbaru | Nota terkonfirmasi, hasil sudah masuk stok, biaya dipakai turunan, atau stok/piutang berubah di tab lain; tampilkan alasan dan aksi berikutnya yang sah. |
| G07 | Unduhan dan cetak | Ekspor diproses/berhasil/gagal/tidak ada data; nota versi cetak; daftar dan filter tidak terhapus setelah unduh/cetak. |
| G08 | Sesi dan layar kecil | Sesi kedaluwarsa/login ulang, drawer navigasi, filter mobile, modal layar kecil, serta input belum disimpan saat berpindah. |

Keadaan ini harus diterapkan pada modul yang relevan, bukan dibuat sebagai halaman status terpisah yang tidak terhubung. Tombol Hapus nonaktif tetap dapat dijelaskan lewat teks atau tombol informasi yang aksesibel.

## 7. Contoh data yang konsisten untuk desain

Gunakan nama toko **Lumbung Beras** sebagai placeholder yang dapat diubah. Nama orang dan angka berikut adalah data contoh, bukan data usaha sebenarnya.

### 7.1 Satu batch gabah

GB-001: bahan awal `1.000 kg`, setelah kering `800 kg`; pemisahan menghasilkan beras `550 kg`, sisa dedek `100 kg`, sisa merang/pupuk `120 kg`, susut/sisa tidak terpakai `30 kg`. Total hasil pemisahan `800 kg`. Angka ini ilustrasi, bukan standar rendemen.

Pada konfirmasi ke Beras A: stok sebelumnya `100 kg`, hasil `550 kg`, stok sesudah `650 kg`. Setelah posting, kartu selesai tetap menunjukkan hasil `550 kg` dan Sudah Masuk Stok; angka hasil tidak berubah menjadi stok keseluruhan produk.

Contoh dedek: alokasikan `60 kg` dari sumber `100 kg`, sehingga sisa sumber `40 kg`; hasil penggilingan `50 kg`. Contoh pupuk pada varian form: bahan asal `40 kg` + campuran `10 kg`, hasil `45 kg`, susut `5 kg`. Jangan membatasi hasil pupuk menjadi maksimal `40 kg`.

### 7.2 Satu nota dengan dua produk

| Item | Kg | Harga/kg | Subtotal | Diskon teralokasi | Nilai bersih |
|---|---|---|---|---|---|
| Beras A | 20 | Rp15.000 | Rp300.000 | Rp30.000 | Rp270.000 |
| Dedek | 40 | Rp5.000 | Rp200.000 | Rp20.000 | Rp180.000 |
| Total | — | — | Rp500.000 | Rp50.000 | Rp450.000 |

Pelanggan contoh Bu Sari. Pembayaran awal `Rp100.000`; piutang `Rp350.000`; status Sebagian. Jika hanya nota ini cocok dengan filter Beras A, empat kartu menampilkan `Rp450.000`, `1 transaksi`, `Rp100.000`, `Rp350.000`. Sheet Item Produk untuk filter tersebut hanya menampilkan Beras A dengan nilai bersih `Rp270.000`.

Saat memilih Tandai Lunas: modal meminta pembayaran `Rp350.000`. Setelah berhasil, total penjualan tetap `Rp450.000`, dibayar `Rp450.000`, piutang `Rp0`, status Lunas. Jangan mengurangi stok lagi atau membuat penerimaan pelunasan `Rp450.000`.

Saat membuat varian beberapa layar dari contoh ini, gunakan keadaan sebelum atau sesudah tindakan secara konsisten. Jangan menampilkan varian belum lunas dan nilai setelah pelunasan sebagai satu keadaan yang sama. Angka dashboard, kas, dan kartu produk harus diturunkan dari dataset contoh yang dipakai; jangan menambahkan total dekoratif yang tidak cocok.

## 8. Daftar variasi interaksi yang wajib terlihat

| Area | Variasi minimum |
|---|---|
| Produk | Aktif stok aman, aktif stok menipis/nol, nonaktif, form tambah/edit, detail, penyesuaian, hapus boleh/terkunci |
| Daftar batch | Kosong, ada batch berbagai tahap, filter aktif, buat batch gabah, pilih sumber dedek/pupuk, sumber tidak tersedia |
| Detail batch | Awal, proses berjalan, selesai belum masuk stok, pemilihan produk, produk baru, hasil sudah masuk stok, hasil nol |
| Bahan/biaya | Sisa tersedia/teralokasi/habis, campuran pupuk, biaya belum dibayar, konfirmasi pembayaran, pembagian biaya belum/final/terkunci |
| POS | Keranjang kosong/terisi, diskon persen/rupiah, bayar lunas/sebagian/akhir, pelanggan wajib, stok berubah, berhasil dan nota |
| Riwayat | Kombinasi filter, empat kartu sesuai filter, detail item, pembayaran sebagian, pelunasan, nota lunas, ekspor |
| Pelanggan/karyawan | Tambah/edit/detail yang relevan, data tanpa relasi/berelasi, karyawan aktif/nonaktif |
| Gaji | Harian, bulanan, biaya operasional, biaya produksi dengan batch, peringatan periode serupa, berhasil |
| Pemasukan/pengeluaran | Manual, otomatis dengan tautan sumber, form, filter, rincian kategori/klasifikasi, edit/hapus sesuai relasi |
| Pengaturan | Nama berhasil berubah, password error/berhasil dan login ulang |

## 9. Hubungan antarlayar yang harus dipertahankan

| Asal tindakan | Tujuan dan data yang dibawa |
|---|---|
| Buat Batch pada daftar | Form jenis terkait → kembali ke daftar dengan baris Belum Diproses |
| Buka Tracking | Dashboard khusus ID batch terpilih |
| Buat batch lanjutan dari sisa bahan | Form Dedek/Pupuk dengan sumber terpilih dan ketersediaan terbaru |
| Tambahkan ke Produk | MTR4 → pilih/buat produk → konfirmasi → hasil bertanda Sudah Masuk Stok |
| Lihat Penjualan dari produk | S07 dengan filter produk aktif |
| Riwayat dari detail pelanggan | S07 dengan filter pelanggan aktif |
| Tambah pelanggan dari POS | M08A → kembali ke keranjang yang sama, pelanggan baru terpilih |
| Konfirmasi penjualan | S06N → nota/detail/transaksi baru; stok, penerimaan dan piutang mengikuti nilai aktual |
| Tandai Lunas | M07B → detail/riwayat yang sama; kartu, status dan kas diperbarui |
| Catatan kas otomatis | Buka sumber penjualan/pembayaran/gaji/batch yang tepat |
| Simpan pembayaran gaji | Gaji tersimpan dan satu pengeluaran terkait tersedia |
| Kembali dari detail | Daftar dengan pencarian/filter/pagination sebelumnya tetap terjaga |

## 10. Batas desain agar tidak menambah ruang lingkup

- Jangan menggabungkan ketiga tracking menjadi alur yang sama. Tahap dan sumber bahannya berbeda.
- Jangan menganggap tombol Selesai otomatis menaikkan stok. Konfirmasi produk tetap diperlukan.
- Jangan memasukkan sisa bahan produksi ke katalog siap jual sebelum pengolahan/konfirmasi hasilnya.
- Jangan menambahkan field wajib alasan penyesuaian stok atau bukti pembayaran.
- Jangan membuat produk nonaktif hilang dari transaksi lama.
- Jangan membuat status Lunas hanya berupa perubahan warna atau label tanpa pencatatan sisa pembayaran.
- Jangan menambah empat kartu di semua modul secara otomatis; hanya modul yang diminta memakai empat kartu wajib.
- Jangan membuat laporan penjualan produk sebagai menu baru. Gunakan Riwayat Penjualan dan ekspornya.
- Jangan menampilkan harga modal, index, cache, queue, atau detail teknis di nota pelanggan atau flow yang tidak memerlukannya.
- Jangan menambahkan rasio susut/konversi, alokasi biaya tetap, atau proyeksi laba tanpa dasar data dan keputusan owner.

## 11. Hasil desain yang diminta

1. Sistem komponen/token yang mengikuti referensi gambar.
2. Seluruh 20 layar utama pada inventaris, dengan ID yang sama agar mudah ditinjau.
3. Form, modal/drawer dan varian M02A–M02C, M03A, M04A, M05A, MTR1–MTR5, M07A–M07C, M08A, M09A, M10A, M11A, M12A.
4. Keadaan bersama G01–G08 diterapkan pada konteks yang tepat.
5. Versi mobile minimal untuk Dashboard, Produk, daftar/detail tracking, POS, Riwayat/detail, form gaji, kas dan Pengaturan. Layar lain mengikuti komponen responsif yang sama.
6. Hubungan klik utama yang dapat ditinjau/prototipe bila didukung alat. Jika tidak, anotasi tujuan aksi tanpa mengklaim interaksi sudah berjalan.
7. Satu daftar kelengkapan dengan ID layar, status sudah dirancang/belum, dan referensi visual yang diterapkan. Jika ada layar belum dibuat, sebutkan secara eksplisit lalu lanjutkan; jangan menghilangkannya dari daftar.

## 12. Checklist penerimaan desain

- [ ] Warna, tipografi, layout, kepadatan, ikon dan bentuk komponen mengikuti gambar referensi owner.
- [ ] Ada 13 menu bisnis yang benar serta akses login/logout owner.
- [ ] Ada 20 layar utama dan seluruh form/varian yang diminta; ID dapat ditelusuri ke PRD.
- [ ] Dashboard memuat semua ringkasan, grafik dan daftar; cakupan waktu setiap blok jelas.
- [ ] Produk mempunyai empat kartu tepat, stok kg, modal/jual, minimum, asal/catatan opsional, aktif/nonaktif dan penyesuaian tanpa alasan wajib.
- [ ] Ketiga tracking mempunyai daftar batch terlebih dahulu dan dashboard satu batch sesudahnya.
- [ ] Tahap gabah empat blok; dedek dan pupuk masing-masing tiga blok sesuai nama yang ditentukan.
- [ ] Pembelian gabah berada pada form batch, bukan menu terpisah.
- [ ] Asal sisa, jumlah alokasi, sisa tersedia dan campuran pupuk terlihat; tidak ada pemakaian bahan ganda.
- [ ] Kartu Selesai membawa berat otomatis, pilihan produk lama/baru, konfirmasi satu kali dan status Sudah Masuk Stok.
- [ ] Ada wadah biaya, pembagian biaya dan pembayaran yang diperlukan untuk menghitung modal tanpa pencatatan ganda.
- [ ] POS mendukung kg, diskon persen/rupiah, bayar lunas/sebagian/akhir, pelanggan wajib untuk piutang dan nota.
- [ ] Riwayat memiliki empat kartu, filter pelanggan/produk/tanggal/status, detail dan ekspor sesuai seluruh hasil filter.
- [ ] Pelunasan mengonfirmasi sisa/tanggal dan tidak bisa berulang; pembayaran sebagian tersedia.
- [ ] Pelanggan dan karyawan lengkap, tanpa akun staf; nonaktif dan penghapusan mengikuti relasi.
- [ ] Gaji memiliki empat kartu, input harian/bulanan manual, periode, keterangan opsional dan hubungan ke pengeluaran.
- [ ] Pemasukan/pengeluaran mempunyai empat kartu, form manual tanpa bukti, filter serta sumber otomatis.
- [ ] Nama toko memengaruhi sidebar/top bar/nota; perubahan password mempunyai validasi dan login ulang.
- [ ] Tombol Hapus tersedia sesuai aturan pada semua CRUD; data berelasi menampilkan alasan nonaktif.
- [ ] Empty/loading/error/success/locked/export/mobile dapat ditinjau, bukan hanya happy path desktop.
- [ ] Tidak ada tambahan menu atau proses bisnis di luar breakdown dan PRD.

## 13. Cara menggunakan dokumen ini

Kirim `desain.md` dan gambar referensi dalam sesi Stitch yang sama. `PRD.md` dapat disertakan untuk memeriksa aturan bisnis dan implementasi. Gunakan instruksi pembuka berikut:

> Buat desain aplikasi Sistem Manajemen Lumbung Beras berdasarkan desain.md. Ikuti bahasa visual gambar referensi yang saya lampirkan: warna, tipografi, sidebar, kartu, tabel, form dan ikon. Dokumen menentukan semua fitur yang harus tersedia; gambar menentukan tampilannya. Buat seluruh layar, form dan state dalam inventaris, termasuk ketiga tracking per batch, POS, piutang, gaji dan kas. Jangan mengurangi fitur, jangan menambah menu di luar scope, dan tunjukkan daftar kelengkapan layar. Jika perlu beberapa putaran, lanjutkan dengan sistem komponen yang sama sampai semua ID selesai.

Setelah hasil tersedia, tinjau checklist bagian 12 dan minta revisi menggunakan ID layar yang belum sesuai. Jika fitur dan referensi visual bertentangan, pertahankan fitur dan sesuaikan komponennya mengikuti gaya referensi.

