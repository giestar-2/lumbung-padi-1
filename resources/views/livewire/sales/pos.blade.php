<div x-data="{ cartOpen: false }">
<div class="page-heading"><div><p class="eyebrow">Point of sale</p><h1 class="page-title">Transaksi baru</h1><p class="page-description">Pilih produk, masukkan berat, lalu konfirmasi pembayaran.</p></div><a href="{{ route('sales.index') }}" class="btn"><x-icon name="clock"/>Riwayat penjualan</a></div>
<x-feedback/>
<div x-show="cartOpen" x-cloak @click="cartOpen = false" class="fixed inset-0 z-30 bg-[#183247]/25 backdrop-blur-[2px] xl:hidden"></div>
<button type="button" @click="cartOpen = !cartOpen" :aria-expanded="cartOpen" aria-controls="pos-cart" class="fixed inset-x-4 bottom-4 z-50 flex min-h-12 items-center justify-between rounded-xl bg-brand-primary px-4 text-sm font-semibold text-white shadow-elegant xl:hidden">
    <span class="flex items-center gap-2"><x-icon name="sales"/>Keranjang ({{ count($cart) }})</span>
    <svg class="h-5 w-5 transition-transform" :class="cartOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
</button>
<div class="grid items-start gap-6 xl:grid-cols-[1fr_390px]">
<section class="panel overflow-hidden"><div class="filters"><label class="field min-w-40 flex-1">Cari produk<input wire:model.live.debounce.300ms="search" class="input" placeholder="Nama atau kode produk..."></label><label class="field">Kategori<select wire:model.live="category" class="input"><option value="">Semua kategori</option>@foreach(['Beras','Dedek','Pupuk','Lainnya','Bahan Baku'] as $type)<option>{{ $type }}</option>@endforeach</select></label></div>
<div class="grid grid-cols-2 gap-3 p-4 2xl:grid-cols-3">
@forelse($products as $product)<button wire:key="product-{{ $product->id }}" wire:click="addToCart({{ $product->id }})" class="group flex min-h-40 flex-col rounded-xl border border-[#e5ebf1] bg-[#f8fafc] p-4 text-left transition hover:border-[#56b69d] hover:bg-[#ecfaf5]">
<div class="mb-4 flex w-full items-center justify-between"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#d5f3e8] text-[#708399]"><x-icon name="box"/></span><span class="text-[10px] text-brand-textGray">{{ $product->type }}</span></div><span class="text-sm font-semibold">{{ $product->name }}</span><span class="mt-1 text-[10px] text-brand-textGray">{{ $product->code }} · {{ number_format($product->stock_kg, 3, ',', '.') }} kg tersedia</span><span class="mt-4 flex w-full items-center justify-between text-sm font-semibold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}<x-icon name="plus" class="text-brand-primary"/></span></button>
@empty<div class="empty-state col-span-full">Produk aktif dengan stok tersedia belum ditemukan.<br><a href="{{ route('products.form') }}" class="font-semibold text-brand-primary">Tambah produk →</a></div>@endforelse
</div><div class="px-5 pb-5">{{ $products->links() }}</div></section>
<section id="pos-cart" class="panel scroll-mt-24 overflow-hidden transition-transform duration-200 ease-out xl:static xl:max-h-none xl:overflow-visible" :class="cartOpen ? 'fixed inset-x-0 bottom-0 z-40 max-h-[calc(100dvh-4rem)] translate-y-0 rounded-b-none overflow-y-auto' : 'fixed inset-x-0 bottom-0 z-40 translate-y-full pointer-events-none xl:static xl:translate-y-0 xl:pointer-events-auto'">
<div class="sticky top-0 z-20 flex items-center justify-between border-b border-[#e5ebf1] bg-white p-5"><h2 class="text-sm font-semibold">Keranjang transaksi</h2><span class="badge">{{ count($cart) }} produk</span></div>
<div class="max-h-[360px] space-y-4 overflow-y-auto p-5">
@forelse($cart as $id => $item)<div wire:key="cart-{{ $id }}" class="border-b border-[#e5ebf1] pb-4"><div class="flex justify-between gap-2"><span class="text-sm font-medium">{{ $item['name'] }}</span><button wire:click="removeItem({{ $id }})" aria-label="Hapus {{ $item['name'] }}" class="text-brand-textGray hover:text-red-600"><x-icon name="close"/></button></div><p class="mt-1 text-[11px] text-brand-textGray">Rp {{ number_format($item['price'], 0, ',', '.') }} / kg</p><div class="mt-3 flex items-center justify-between"><label class="flex items-center gap-2 text-xs text-brand-textGray"><input aria-label="Berat {{ $item['name'] }}" wire:model.blur="cart.{{ $id }}.qty" wire:change="calculateCart" inputmode="decimal" class="input max-w-24">kg</label><span class="text-sm font-semibold">Rp {{ number_format($item['subtotal'] ?? 0, 2, ',', '.') }}</span></div></div>
@empty<div class="empty-state"><x-icon name="sales" class="mb-3 h-8 w-8 text-[#708399]"/><p>Keranjang masih kosong.</p><p class="text-xs">Pilih produk untuk memulai.</p></div>@endforelse
</div>
<div class="space-y-4 border-t border-[#e5ebf1] bg-[#f8fafc] p-5">
<div class="flex justify-between text-sm"><span class="text-brand-textGray">Subtotal</span><span>Rp {{ number_format($this->subtotal, 2, ',', '.') }}</span></div>
<div class="grid grid-cols-2 gap-3"><label class="field">Jenis diskon<select wire:model.live="discount_type" class="input"><option>Tidak Ada</option><option>Persen</option><option>Rupiah</option></select></label><label class="field">Nilai diskon<input wire:model.live.debounce.300ms="discount" inputmode="decimal" class="input" @disabled($discount_type === 'Tidak Ada')></label></div>
<div class="flex justify-between border-t border-[#e5ebf1] pt-4 text-lg font-semibold"><span>Total</span><span class="text-brand-primary">Rp {{ number_format($this->total, 2, ',', '.') }}</span></div>
<div class="grid grid-cols-2 gap-3"><label class="field">Pelanggan<select wire:model="customer_id" class="input"><option value="">Umum</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></label><label class="field">Status pembayaran<select wire:model.live="payment_status" class="input"><option value="Lunas">Lunas</option><option value="Belum Lunas">Belum lunas</option></select></label></div>
<details class="text-xs text-brand-primary"><summary>Tambah pelanggan tanpa meninggalkan transaksi</summary><div class="mt-3 space-y-3"><label class="field">Nama<input class="input" wire:model="customer_name"></label><label class="field">Telepon<input class="input" wire:model="customer_phone"></label><button class="btn" wire:click="addCustomer">Simpan pelanggan</button></div></details>
<label class="field">Nominal (Rp)<input wire:model.live.debounce.300ms="paid_amount" inputmode="decimal" class="input"></label>
<div class="flex flex-wrap gap-2" aria-label="Nominal cepat">
    <button type="button" wire:click="setPaymentAmount('{{ $this->total }}')" class="rounded-lg border border-[#bce8da] bg-[#ecfaf5] px-3 py-2 text-[11px] font-semibold text-brand-primary hover:bg-[#d8f5e9]">Uang pas</button>
    @foreach(['10000', '20000', '50000', '100000', '200000', '500000'] as $quickAmount)
        <button type="button" wire:click="setPaymentAmount('{{ $quickAmount }}')" class="rounded-lg border border-[#dce4ec] bg-white px-3 py-2 text-[11px] font-medium text-[#526579] hover:border-[#a9dace] hover:bg-[#f0faf6]">Rp {{ number_format($quickAmount, 0, ',', '.') }}</button>
    @endforeach
</div>
<p class="flex justify-between text-xs text-brand-textGray"><span>Sisa piutang</span><span>Rp {{ number_format(max(0, (float)$this->total - (float)str_replace(',', '.', (string)$paid_amount)), 2, ',', '.') }}</span></p>
<label class="field">Catatan<input wire:model="notes" class="input" placeholder="Keterangan transaksi..."></label>
<p class="text-[10px] leading-5 text-brand-textGray">Berat mendukung 3 desimal. Contoh: 12,500 = 12,5 kg. Jangan gunakan pemisah ribuan.</p>
<button wire:click="checkout" wire:confirm="Konfirmasi penjualan? Item, berat, harga dan diskon akan dikunci." wire:loading.attr="disabled" class="btn btn-primary w-full"><x-icon name="check"/><span wire:loading.remove wire:target="checkout">Konfirmasi penjualan</span><span wire:loading wire:target="checkout">Menyimpan transaksi…</span></button>
</div>
</section>
</div>
</div>
