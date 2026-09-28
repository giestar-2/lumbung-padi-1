<div>
<div class="page-heading"><div><p class="eyebrow">Persediaan</p><h1 class="page-title">{{ $productId ? 'Detail & edit produk' : 'Tambah produk' }}</h1><p class="page-description">Kelola identitas, harga, dan batas minimum stok.</p></div><a href="{{ route('products.index') }}" class="btn">Kembali ke produk</a></div>
<x-feedback/>
<form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_320px]">
<section class="panel space-y-6 p-6"><h2 class="text-sm font-semibold">Informasi produk</h2>
<div class="grid gap-5 sm:grid-cols-2"><label class="field">Kode produk<input class="input" wire:model="code" required></label><label class="field">Kategori<select class="input" wire:model="type">@foreach(['Beras','Dedek','Pupuk','Lainnya','Bahan Baku'] as $category)<option value="{{ $category }}">{{ $category === 'Pupuk' ? 'Merang / Pupuk' : $category }}</option>@endforeach</select></label></div>
<label class="field">Nama produk<input class="input" wire:model="name" maxlength="150" required placeholder="Contoh: Beras premium"></label>
<div class="grid gap-5 sm:grid-cols-2"><label class="field">Harga jual / kg (Rp)<input class="input" wire:model="selling_price" inputmode="decimal" required></label><label class="field">Modal berjalan / kg (Rp)<input class="input" wire:model.live.debounce.300ms="cogs_per_kg" inputmode="decimal" required></label></div>
<div class="grid gap-5 sm:grid-cols-2"><label class="field">{{ $productId ? 'Stok saat ini (kg)' : 'Saldo awal stok (kg)' }}<input class="input" wire:model="stock_kg" inputmode="decimal" @disabled($productId)></label><label class="field">Batas stok minimum (kg)<input class="input" wire:model="stock_minimum" inputmode="decimal"></label></div>
<label class="field">Asal produk (opsional)<input class="input" wire:model="origin" placeholder="Asal atau pemasok"></label><label class="field">Catatan<textarea class="input" wire:model="notes" rows="3"></textarea></label>
</section>
<aside class="panel space-y-5 p-6 self-start"><h2 class="text-sm font-semibold">Status & penyimpanan</h2><label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active">Produk aktif</label><p class="text-xs leading-6 text-brand-textGray">Produk nonaktif tetap terlihat di riwayat, tetapi tidak tersedia untuk transaksi baru.</p>
@if($productId)<div class="rounded-lg bg-[#ecfaf5] p-4 text-xs leading-6"><p class="font-semibold">Dampak perubahan modal</p><p>Nilai lama: Rp {{ number_format((float)$currentStock * (float)$oldCost, 2, ',', '.') }}</p><p>Nilai baru: Rp {{ number_format((float)$currentStock * (float)str_replace(',', '.', (string)$cogs_per_kg), 2, ',', '.') }}</p><p class="mt-2 text-brand-textGray">HPP penjualan lama tetap. Perubahan ini tidak mencatat kas. Gunakan penyesuaian di daftar produk untuk mengubah stok.</p></div>@endif
<p class="text-[11px] leading-5 text-brand-textGray">Input kg menerima koma atau titik desimal, maksimal 3 angka. Hindari pemisah ribuan.</p>
<button class="btn btn-primary w-full" wire:loading.attr="disabled">Simpan produk</button>
@if($productId)<a href="{{ route('sales.index',['product'=>$productId]) }}" class="btn w-full">Lihat riwayat penjualan</a>@endif</aside>
</form>
</div>
