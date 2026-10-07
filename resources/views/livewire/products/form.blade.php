<div>
<div class="page-heading"><div><p class="eyebrow">Persediaan</p><h1 class="page-title">{{ $productId ? 'Detail & edit produk' : 'Tambah produk' }}</h1><p class="page-description">Kelola identitas, harga, dan batas minimum stok.</p></div><a href="{{ route('products.index') }}" class="btn">Kembali ke produk</a></div>
<x-feedback/>
<form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_320px]">
<section class="panel space-y-6 p-6"><h2 class="text-sm font-semibold">Informasi produk</h2>
<div class="grid gap-5 sm:grid-cols-2"><label class="field">Kode produk<input class="input" wire:model="code" required></label><label class="field">Kategori<select class="input" wire:model="type">@foreach(['Beras','Dedek','Pupuk','Lainnya','Bahan Baku'] as $category)<option value="{{ $category }}">{{ $category === 'Pupuk' ? 'Merang / Pupuk' : $category }}</option>@endforeach</select></label></div>
<label class="field">Nama produk<input class="input" wire:model="name" maxlength="150" required placeholder="Contoh: Beras premium"></label>
<div class="grid gap-5 sm:grid-cols-2"><label class="field">Harga jual / kg (Rp)<input class="input" wire:model="selling_price" inputmode="decimal" required></label>
@if($productId)<div class="field">Modal rata-rata / kg<p class="input bg-brand-bgMain font-semibold">Rp {{ \App\Decimal::display((float)$cogs_per_kg, 2) }}</p><p class="text-xs font-normal text-brand-textGray">Dihitung otomatis dari pembelian stok dan hasil produksi.</p></div>
@else<label class="field">Total belanja stok (Rp)<input class="input" wire:model.live.debounce.300ms="total_cost" inputmode="decimal" required><span class="text-xs font-normal text-brand-textGray">Total biaya pembelian seluruh stok awal.</span></label>@endif</div>
<div class="grid gap-5 sm:grid-cols-2"><label class="field">{{ $productId ? 'Stok saat ini (kg)' : 'Stok awal pembelian (kg)' }}<input class="input" wire:model.live.debounce.300ms="stock_kg" inputmode="decimal" @disabled($productId)></label><label class="field">Batas stok minimum (kg)<input class="input" wire:model="stock_minimum" inputmode="decimal"></label></div>
<label class="field">Asal produk (opsional)<input class="input" wire:model="origin" placeholder="Asal atau pemasok"></label><label class="field">Catatan<textarea class="input" wire:model="notes" rows="3"></textarea></label>
</section>
<aside class="panel space-y-5 p-6 self-start"><h2 class="text-sm font-semibold">Status & penyimpanan</h2><label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active">Produk aktif</label><p class="text-xs leading-6 text-brand-textGray">Produk nonaktif tetap terlihat di riwayat, tetapi tidak tersedia untuk transaksi baru.</p>
@if($productId)<div class="rounded-lg bg-[#eef4ff] p-4 text-xs leading-6"><p class="font-semibold">Tambah stok pembelian</p><p class="mt-2 text-brand-textGray">Buka tombol Stok di daftar produk untuk mencatat pembelian berikutnya. Modal rata-rata diperbarui otomatis sesuai jumlah stok dan total belanja.</p><a class="btn mt-3 w-full" href="{{ route('products.index') }}">Buka daftar produk</a></div>
@else<div class="rounded-lg bg-[#eef4ff] p-4 text-xs leading-6"><p class="font-semibold">Modal per kg produk ini</p><p class="mt-2 text-xl font-bold text-brand-primary">{{ $initialCostPerKg !== null ? 'Rp '.\App\Decimal::display((float)$initialCostPerKg, 2) : 'Isi stok dan total belanja' }}</p><p class="mt-2 text-brand-textGray">Total belanja dibagi stok awal. Saat disimpan, total belanja otomatis tercatat di Pengeluaran pada tanggal hari ini. Isi stok dan total belanja 0 jika hanya membuat daftar produk.</p></div>@endif
<p class="text-[11px] leading-5 text-brand-textGray">Input kg menerima koma atau titik desimal, maksimal 3 angka. Hindari pemisah ribuan.</p>
<button class="btn btn-primary w-full" wire:loading.attr="disabled">Simpan produk</button>
@if($productId)<a href="{{ route('sales.index',['product'=>$productId]) }}" class="btn w-full">Lihat riwayat penjualan</a>@endif</aside>
</form>
</div>
