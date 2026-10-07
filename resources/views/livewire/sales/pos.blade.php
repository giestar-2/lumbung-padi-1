<div x-data="{ cartOpen: false }" @keydown.escape.window="cartOpen = false" @pos-completed.window="cartOpen = false" class="pb-16 xl:pb-0">
<div class="page-heading"><div><p class="eyebrow">Point of sale</p><h1 class="page-title">Transaksi baru</h1><p class="page-description">Pilih produk, masukkan berat, lalu konfirmasi pembayaran.</p></div><a href="{{ route('sales.index') }}" class="btn"><x-icon name="clock"/>Riwayat penjualan</a></div>
<x-feedback/>
<div x-show="cartOpen" x-cloak @click="cartOpen = false" class="fixed inset-0 z-30 bg-[#183247]/25 backdrop-blur-[2px] xl:hidden"></div>
<button type="button" x-show="!menuOpen" @click="cartOpen = !cartOpen" :aria-expanded="cartOpen" aria-controls="pos-cart" class="pos-cart-toggle fixed inset-x-0 bottom-0 z-50 flex min-h-14 items-center justify-between bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-elegant xl:hidden">
    <span class="flex items-center gap-2"><x-icon name="sales"/>Keranjang ({{ count($cart) }})</span>
    <svg class="h-5 w-5 transition-transform" :class="cartOpen ? '' : 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
</button>
<div class="grid items-start gap-6 xl:grid-cols-[1fr_390px]">
<section class="panel overflow-hidden"><div class="filters"><label class="field min-w-40 flex-1">Cari produk<input wire:model.live.debounce.300ms="search" class="input" placeholder="Nama atau kode produk..."></label><label class="field">Kategori<select wire:model.live="category" class="input"><option value="">Semua kategori</option>@foreach(['Beras','Dedek','Pupuk','Lainnya','Bahan Baku'] as $type)<option>{{ $type }}</option>@endforeach</select></label></div>
<div class="grid grid-cols-2 gap-2.5 p-3 sm:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4">
@forelse($products as $product)
    @php($inCart = isset($cart[$product->id]))
    <button type="button" wire:key="product-{{ $product->id }}" wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $product->id }}),clearCart,checkout" @class(['pos-product-card group p-3 sm:p-3', 'is-selected' => $inCart]) aria-label="Tambah {{ $product->name }} ke keranjang{{ $inCart ? ' (sudah dipilih)' : '' }}">
        <span class="mb-2.5 flex items-center justify-between gap-2">
            <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-lg', 'bg-[#eef4ff] text-brand-primary' => !in_array($product->type, ['Dedek', 'Pupuk']), 'bg-orange-50 text-orange-700' => $product->type === 'Dedek', 'bg-emerald-50 text-emerald-700' => $product->type === 'Pupuk'])><x-icon :name="$product->type === 'Pupuk' ? 'leaf' : 'box'" class="h-4 w-4"/></span>
            <span class="truncate rounded-md bg-[#f0f4f8] px-2 py-1 text-[9px] font-medium text-brand-textGray">{{ $product->type }}</span>
        </span>
        <span class="line-clamp-2 min-h-8 font-heading text-xs font-semibold leading-4">{{ $product->name }}</span>
        <span class="mt-1 truncate text-[10px] text-[#747780]">{{ $product->code }}</span>
        <span class="mt-2 flex items-start gap-1.5 text-[10px] leading-4 text-brand-textGray"><span @class(['mt-1 h-1.5 w-1.5 shrink-0 rounded-full', 'bg-amber-500' => $product->stock_kg <= $product->stock_minimum, 'bg-emerald-500' => $product->stock_kg > $product->stock_minimum])></span>{{ \App\Decimal::display($product->stock_kg, 3) }} kg</span>
        <span class="mt-2.5 flex items-center justify-between gap-1 border-t border-[#eaeef2] pt-2.5">
            <span class="min-w-0"><span class="block font-heading text-sm font-bold tracking-tight text-brand-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span><span class="text-[9px] text-brand-textGray">per kg</span></span>
            <span class="pos-product-add flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"><x-icon name="plus" class="h-4 w-4"/></span>
        </span>
    </button>
@empty<div class="empty-state col-span-full">Produk aktif dengan stok tersedia belum ditemukan.<br><a href="{{ route('products.form') }}" class="font-semibold text-brand-primary">Tambah produk →</a></div>@endforelse
</div><div class="px-5 pb-5">{{ $products->links() }}</div></section>
<section id="pos-cart" class="panel pos-drawer overflow-hidden" :class="cartOpen ? 'is-open' : 'is-closed'">
<div class="flex shrink-0 items-center justify-between gap-3 border-b border-[#eaeef2] bg-white p-5">
    <div class="min-w-0"><h2 class="text-base font-semibold">Keranjang transaksi</h2><p class="mt-1 text-[11px] text-brand-textGray">{{ count($cart) }} produk dipilih</p></div>
    <button type="button" wire:click="clearCart" wire:loading.attr="disabled" wire:target="clearCart,checkout" @disabled(count($cart) === 0) class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-2.5 py-2 text-[11px] font-semibold text-red-700 transition-colors hover:bg-red-100"><x-icon name="trash" class="h-3.5 w-3.5"/>Kosongkan</button>
</div>
<div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-16 xl:pb-0">
<div class="space-y-4 overflow-y-auto p-5 xl:max-h-[360px]">
@forelse($cart as $id => $item)<div wire:key="cart-{{ $id }}" class="border-b border-[#eaeef2] pb-4"><div class="flex justify-between gap-2"><span class="text-sm font-medium">{{ $item['name'] }}</span><button wire:click="removeItem({{ $id }})" aria-label="Hapus {{ $item['name'] }}" class="text-brand-textGray hover:text-red-600"><x-icon name="close"/></button></div><p class="mt-1 text-[11px] text-brand-textGray">Rp {{ number_format($item['price'], 0, ',', '.') }} / kg</p><div class="mt-3 flex items-center justify-between"><label class="flex items-center gap-2 text-xs text-brand-textGray"><input aria-label="Berat {{ $item['name'] }}" wire:model.blur="cart.{{ $id }}.qty" wire:change="calculateCart" inputmode="decimal" class="input max-w-24">kg</label><span class="text-sm font-semibold">Rp {{ \App\Decimal::display($item['subtotal'] ?? 0, 2) }}</span></div></div>
@empty<div class="empty-state"><x-icon name="sales" class="mb-3 h-8 w-8 text-[#747780]"/><p>Keranjang masih kosong.</p><p class="text-xs">Pilih produk untuk memulai.</p></div>@endforelse
</div>
<div class="space-y-4 border-t border-[#eaeef2] bg-[#f6fafe] p-5">
<div class="flex justify-between text-sm"><span class="text-brand-textGray">Subtotal</span><span>Rp {{ \App\Decimal::display($this->subtotal, 2) }}</span></div>
<div class="grid grid-cols-2 gap-3"><label class="field">Jenis diskon<select wire:model.live="discount_type" class="input"><option>Tidak Ada</option><option>Persen</option><option>Rupiah</option></select></label><label class="field">Nilai diskon<input wire:model.live.debounce.300ms="discount" inputmode="decimal" class="input" @disabled($discount_type === 'Tidak Ada')></label></div>
<div class="flex justify-between border-t border-[#eaeef2] pt-4 text-lg font-semibold"><span>Total</span><span class="text-brand-primary">Rp {{ \App\Decimal::display($this->total, 2) }}</span></div>
<div class="grid grid-cols-2 gap-3"><label class="field">Pelanggan<select wire:model="customer_id" class="input"><option value="">Umum</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></label><label class="field">Status pembayaran<select wire:model.live="payment_status" class="input"><option value="Lunas">Lunas</option><option value="Belum Lunas">Belum lunas</option></select></label></div>
<details class="text-xs text-brand-primary"><summary>Tambah pelanggan tanpa meninggalkan transaksi</summary><div class="mt-3 space-y-3"><label class="field">Nama<input class="input" wire:model="customer_name"></label><label class="field">Telepon<input class="input" wire:model="customer_phone"></label><button class="btn" wire:click="addCustomer">Simpan pelanggan</button></div></details>
<label class="field">Nominal (Rp)<input wire:model.live.debounce.300ms="paid_amount" inputmode="decimal" class="input"></label>
<div class="flex flex-wrap gap-2" aria-label="Nominal cepat">
    <button type="button" wire:click="setPaymentAmount('{{ $this->total }}')" class="rounded-lg border border-[#adc6ff] bg-[#eef4ff] px-3 py-2 text-[11px] font-semibold text-brand-primary hover:bg-[#d8e2ff]">Uang pas</button>
    @foreach(['10000', '20000', '50000', '100000', '200000', '500000'] as $quickAmount)
        <button type="button" wire:click="setPaymentAmount('{{ $quickAmount }}')" class="rounded-lg border border-[#dfe3e7] bg-white px-3 py-2 text-[11px] font-medium text-[#44474f] hover:border-[#adc6ff] hover:bg-[#f0f4f8]">Rp {{ number_format($quickAmount, 0, ',', '.') }}</button>
    @endforeach
</div>
<p class="flex justify-between text-xs text-brand-textGray"><span>Sisa piutang</span><span>Rp {{ \App\Decimal::display(max(0, (float)$this->total - (float)str_replace(',', '.', (string)$paid_amount)), 2) }}</span></p>
<label class="field">Catatan<input wire:model="notes" class="input" placeholder="Keterangan transaksi..."></label>
<p class="text-[10px] leading-5 text-brand-textGray">Berat mendukung 3 desimal. Contoh: 12,5 untuk dua belas setengah kg. Jangan gunakan pemisah ribuan.</p>
<button wire:click="checkout" data-confirm-title="Konfirmasi penjualan" data-confirm-label="Konfirmasi penjualan" data-confirm="Simpan penjualan ini? Stok dan pembayaran akan dicatat sesuai isi keranjang." wire:loading.attr="disabled" class="btn btn-primary w-full"><x-icon name="check"/><span wire:loading.remove wire:target="checkout">Konfirmasi penjualan</span><span wire:loading wire:target="checkout">Menyimpan transaksi…</span></button>
</div>
</div>
</section>
</div>
</div>
