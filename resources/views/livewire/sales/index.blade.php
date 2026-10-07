<div>
<div class="page-heading"><div><p class="eyebrow">Transaksi</p><h1 class="page-title">Penjualan</h1><p class="page-description">Semua nota, pembayaran, dan tagihan dalam satu tempat.</p></div><a href="{{ route('sales.pos') }}" class="btn btn-primary"><x-icon name="plus"/>Transaksi baru</a></div>
<x-feedback/>
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
<x-stat label="Total penjualan setelah diskon" :value="'Rp '.number_format($totalPenjualan, 0, ',', '.')" icon="sales"/>
<x-stat label="Jumlah transaksi" :value="number_format($jumlahTransaksi)" icon="clock"/>
<x-stat label="Total sudah dibayar" :value="'Rp '.number_format($totalDibayar, 0, ',', '.')" icon="down"/>
<x-stat label="Sisa piutang" :value="'Rp '.number_format($sisaPiutang, 0, ',', '.')" icon="wallet"/>
</div>
<section class="panel overflow-hidden">
<div class="filters">
<label class="field flex-1 min-w-40">Cari nota<input wire:model.live.debounce.300ms="search" class="input" placeholder="Nomor transaksi..."></label>
<label class="field">Pelanggan<input class="input" wire:model.live.debounce.300ms="customerSearch" placeholder="Cari pelanggan..."><select wire:model.live="customer" class="input"><option value="">Semua pelanggan</option>@foreach($customers as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label>
<label class="field">Produk<input class="input" wire:model.live.debounce.300ms="productSearch" placeholder="Cari produk..."><select wire:model.live="product" class="input"><option value="">Semua produk</option>@foreach($products as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label>
<label class="field">Status<select wire:model.live="status" class="input"><option value="">Semua status</option>@foreach(['Belum Dibayar','Sebagian','Lunas'] as $value)<option>{{ $value }}</option>@endforeach</select></label>
<label class="field">Dari tanggal penjualan<input type="date" wire:model.live="from" class="input"></label>
<label class="field">Sampai tanggal<input type="date" wire:model.live="to" class="input"></label>
<button wire:click="resetFilters" class="btn">Reset</button><button wire:click="export" wire:loading.attr="disabled" class="btn"><x-icon name="down"/><span wire:loading.remove wire:target="export">Ekspor Excel</span><span wire:loading wire:target="export">Menyiapkan...</span></button>
</div>
@if($product)<p class="border-b border-[#eaeef2] bg-[#eef4ff] px-5 py-3 text-xs text-brand-textGray">Filter produk menemukan nota yang memuat produk tersebut. Kartu menghitung keseluruhan nota, masing-masing satu kali.</p>@endif
<div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Nomor transaksi</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Dibayar</th><th>Sisa</th><th>Jatuh tempo</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($sales as $sale)<tr wire:key="sale-{{ $sale->id }}"><td><button wire:click="openDetail({{ $sale->id }})" class="font-semibold text-brand-primary">{{ $sale->invoice_number }}</button><p class="mt-1 text-[11px] text-brand-textGray">{{ $sale->items_count }} produk</p></td><td class="whitespace-nowrap text-brand-textGray">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }}</td><td>{{ $sale->customer?->name ?? 'Umum' }}</td><td class="whitespace-nowrap">Rp {{ number_format($sale->total, 0, ',', '.') }}</td><td class="whitespace-nowrap">{{ number_format($sale->paid_amount, 0, ',', '.') }}</td><td class="whitespace-nowrap">{{ number_format($sale->balance, 0, ',', '.') }}</td><td>{{ $sale->due_date ? \Carbon\Carbon::parse($sale->due_date)->format('d/m/Y') : '-' }}@if($sale->due_date && $sale->due_date < \App\Decimal::today() && $sale->balance > 0)<span class="badge badge-danger mt-1">Terlambat</span>@endif</td><td><span @class(['badge', 'badge-warning' => $sale->status_label !== 'Lunas'])>{{ $sale->status_label }}</span></td><td><button wire:click="openDetail({{ $sale->id }})" class="btn">Detail</button></td></tr>
@empty<tr><td colspan="9"><div class="empty-state">Tidak ada transaksi yang sesuai filter.<br>Ubah filter atau mulai transaksi baru.</div></td></tr>@endforelse
</tbody></table></div>
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#eaeef2] p-5"><label class="flex items-center gap-2 text-xs text-brand-textGray">Baris<select wire:model.live="perPage" class="input">@foreach([10,25,50] as $size)<option>{{ $size }}</option>@endforeach</select></label>{{ $sales->links() }}</div>
</section>
@if($selectedSale)
<section class="panel mt-6 overflow-hidden" id="sale-detail">
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#eaeef2] p-5"><div><p class="eyebrow">Detail transaksi</p><h2 class="text-lg font-semibold">{{ $selectedSale->invoice_number }}</h2><p class="mt-1 text-xs text-brand-textGray">{{ $selectedSale->customer?->name ?? 'Umum' }} · {{ \Carbon\Carbon::parse($selectedSale->sale_date)->format('d/m/Y') }} · {{ $selectedSale->status_label }}</p></div><div class="flex gap-2"><a href="{{ route('sales.receipt', $selectedSale) }}" target="_blank" class="btn">Lihat / cetak nota</a><button wire:click="$set('detail', null)" class="btn" aria-label="Tutup detail"><x-icon name="close"/></button></div></div>
<div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Produk</th><th>Berat kg</th><th>Harga/kg</th><th>Subtotal</th><th>Diskon item</th><th>Nilai bersih</th></tr></thead><tbody>
@foreach($selectedSale->items as $item)<tr @class(['bg-[#eef4ff]' => (string)$item->product_id === $product])><td>{{ $item->product_name ?? 'Produk #'.$item->product_id }} @if((string)$item->product_id === $product)<span class="badge">Cocok filter</span>@endif</td><td>{{ \App\Decimal::display($item->quantity, 3) }}</td><td>{{ \App\Decimal::display($item->price, 2) }}</td><td>{{ \App\Decimal::display($item->subtotal, 2) }}</td><td>{{ \App\Decimal::display($item->discount_amount, 2) }}</td><td>{{ \App\Decimal::display($item->net_amount ?? $item->subtotal, 2) }}</td></tr>@endforeach
</tbody></table></div>
<div class="grid gap-6 border-t border-[#eaeef2] p-5 lg:grid-cols-2">
<div><h3 class="mb-4 text-sm font-semibold">Riwayat pembayaran</h3>@forelse($selectedSale->payments as $payment)<div class="flex justify-between border-b border-[#eaeef2] py-3 text-xs"><div>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}<p class="mt-1 text-brand-textGray">{{ $payment->is_initial ? 'Pembayaran awal POS' : 'Pembayaran lanjutan' }} {{ $payment->notes }}</p></div><span class="font-semibold">Rp {{ \App\Decimal::display($payment->amount, 2) }}</span></div>@empty<p class="text-xs text-brand-textGray">Belum ada rincian pembayaran tercatat.</p>@endforelse
@if($selectedSale->notes)<p class="mt-4 text-sm text-brand-textGray">{{ $selectedSale->notes }}</p>@endif</div>
<div>
@if($selectedSale->balance > 0)
<form wire:submit="recordPayment" class="space-y-4"><h3 class="text-sm font-semibold">Tambah pembayaran · Sisa Rp {{ \App\Decimal::display($selectedSale->balance, 2) }}</h3>
<label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model.live="settle">Lunasi seluruh sisa tagihan</label>
<div class="grid gap-3 sm:grid-cols-2"><label class="field">Nominal (Rp)<input class="input" wire:model="payment_amount" inputmode="decimal" @disabled($settle)></label><label class="field">Tanggal pembayaran<input class="input" type="date" wire:model="payment_date" required></label></div>
@if($settle)<p class="text-xs text-brand-primary">Konfirmasi akan mencatat sisa tagihan terbaru sebagai pemasukan.</p>@endif
<label class="field">Keterangan<input class="input" wire:model="payment_notes"></label><button class="btn btn-primary" wire:loading.attr="disabled" data-confirm="Catat pembayaran ini?">Konfirmasi pembayaran</button></form>
@else<div class="rounded-xl bg-[#eef4ff] p-5 text-sm text-brand-primary"><x-icon name="check" class="mr-2"/>Transaksi sudah lunas.</div>@endif
</div></div>
</section>
@endif
</div>
