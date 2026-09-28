<div>
    <div class="page-heading">
        <div><p class="eyebrow">Ringkasan usaha</p><h1 class="page-title">Dashboard usaha</h1><p class="page-description">Selamat datang, {{ auth()->user()->name }}. Berikut ringkasan lumbung Anda.</p></div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('sales.index') }}" class="btn"><x-icon name="clock"/>Riwayat penjualan</a><a href="{{ route('sales.pos') }}" class="btn btn-primary"><x-icon name="plus"/>Transaksi baru</a></div>
    </div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex rounded-xl border border-[#e5ebf1] bg-white p-1 shadow-sm">
            @foreach(['today' => 'Hari ini', 'month' => 'Bulan ini', 'custom' => 'Rentang tanggal'] as $value => $label)
            <button wire:click="$set('period', '{{ $value }}')" @class(['period-tab', 'is-active' => $period === $value])>{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex items-center gap-2 text-xs text-brand-textGray">
            @if($period === 'custom')
                <label class="sr-only" for="dashboard-from">Dari tanggal</label><input id="dashboard-from" class="input max-w-40" type="date" wire:model.live="from">
                <span>—</span><label class="sr-only" for="dashboard-to">Sampai tanggal</label><input id="dashboard-to" class="input max-w-40" type="date" wire:model.live="to">
            @else
                <x-icon name="calendar"/>{{ \Carbon\Carbon::parse($from)->locale('id')->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($to)->locale('id')->translatedFormat('d M Y') }}
            @endif
        </div>
    </div>
    @unless($validRange)<p role="alert" class="mb-4 text-sm text-red-600">Rentang tanggal tidak valid. Ringkasan sementara menampilkan bulan berjalan.</p>@endunless
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat :featured="true" label="Penjualan bersih" :value="'Rp '.number_format($penjualanBersih, 0, ',', '.')" :note="$transactionCount.' transaksi dalam periode terpilih'" icon="sales"/>
        <x-stat label="Pemasukan kas" :value="'Rp '.number_format($pemasukanKas, 0, ',', '.')" note="Uang yang benar-benar diterima" icon="down"/>
        <x-stat label="Pengeluaran kas" :value="'Rp '.number_format($pengeluaranKas, 0, ',', '.')" note="Pembayaran pada periode terpilih" icon="up"/>
        <x-stat label="Laba usaha" :value="'Rp '.number_format($labaUsaha, 0, ',', '.')" note="Penjualan − HPP − beban operasional" icon="up"/>
        <x-stat label="Sisa piutang transaksi periode" :value="'Rp '.number_format($sisaPiutang, 0, ',', '.')" note="Tagihan penjualan periode yang belum diterima" icon="clock"/>
        <x-stat label="Arus kas bersih" :value="'Rp '.number_format($arusKasBersih, 0, ',', '.')" note="Pemasukan kas − pengeluaran kas" icon="wallet"/>
    </div>
    <div class="mb-6 grid gap-6 xl:grid-cols-[1.8fr_1fr]">
        <section class="panel min-w-0 p-5 sm:p-6" aria-labelledby="cash-chart-title">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="cash-chart-title" class="text-sm font-semibold">Arus kas</h2><p class="mt-1 text-xs text-brand-textGray">Penerimaan dan pembayaran aktual · Rupiah</p></div><div class="flex gap-4 text-[10px] text-brand-textGray"><span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[#13866e]"></span>Pemasukan</span><span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[#64cfb0]"></span>Pengeluaran</span></div></div>
            @if(count($chart))
            <div class="mt-6 overflow-x-auto">
                <div class="relative flex h-[230px] min-w-[400px] items-end gap-2 border-b border-[#e5ebf1] px-2 pb-6" role="img" aria-label="Grafik kas harian. Arahkan ke batang untuk melihat tanggal dan nominal.">
                    <div class="pointer-events-none absolute inset-x-0 top-0 flex h-[204px] flex-col justify-between">@for($line=0;$line<5;$line++)<div class="border-t border-dashed border-[#e5ebf1]"><span class="relative -top-2 bg-white pr-2 text-[9px] text-[#708399]">{{ number_format($chartMax * (4-$line)/4, 0, ',', '.') }}</span></div>@endfor</div>
                    @foreach($chart as $day)
                    <div class="relative z-10 flex h-[185px] min-w-5 flex-1 items-end justify-center gap-1">
                        <div class="w-[32%] max-w-5 chart-bar rounded-t-md bg-[#13866e]" style="height: {{ max(0.5, $day['in'] / $chartMax * 100) }}%" title="{{ $day['date'] }} · Pemasukan Rp {{ number_format($day['in'], 0, ',', '.') }}"></div>
                        <div class="w-[32%] max-w-5 chart-bar rounded-t-md bg-[#64cfb0]" style="height: {{ max(0.5, $day['out'] / $chartMax * 100) }}%" title="{{ $day['date'] }} · Pengeluaran Rp {{ number_format($day['out'], 0, ',', '.') }}"></div>
                        @if(count($chart) <= 15 || $loop->iteration % 5 === 0)<span class="absolute -bottom-5 text-[9px] whitespace-nowrap text-[#708399]">{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</span>@endif
                    </div>
                    @endforeach
                </div>
            </div>
            <details class="mt-4 text-xs text-brand-textGray"><summary>Lihat rincian grafik</summary><div class="mt-3 max-h-48 overflow-auto"><table class="data-table"><thead><tr><th>Tanggal</th><th>Pemasukan</th><th>Pengeluaran</th></tr></thead><tbody>@foreach($chart as $day)<tr><td>{{ $day['date'] }}</td><td>Rp {{ number_format($day['in'], 0, ',', '.') }}</td><td>Rp {{ number_format($day['out'], 0, ',', '.') }}</td></tr>@endforeach</tbody></table></div></details>
            @else
            <div class="empty-state flex h-[250px] flex-col items-center justify-center"><span class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-[#ecfaf5] text-[#708399]"><x-icon name="wallet" class="h-6 w-6"/></span><p class="font-medium text-[#13866e]">Belum ada aktivitas kas</p><p class="mt-1 text-xs">Grafik muncul setelah ada penerimaan atau pembayaran.</p><a href="{{ route('sales.pos') }}" class="mt-4 text-xs font-semibold text-brand-primary">Catat penjualan pertama →</a></div>
            @endif
        </section>
        <section class="panel p-5 sm:p-6">
            <div class="flex justify-between"><div><h2 class="text-sm font-semibold">Produk paling menguntungkan</h2><p class="mt-1 text-xs text-brand-textGray">Laba kotor setelah diskon & HPP</p></div><x-icon name="up" class="text-[#708399]"/></div>
            <div class="mt-6 space-y-5">
            @forelse($topProducts as $product)
                <a href="{{ route('sales.index', ['product' => $product->id, 'from' => $from, 'to' => $to]) }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#ecfaf5] text-[#708399]"><x-icon name="box"/></span>
                    <div class="min-w-0 flex-1"><p class="truncate text-xs font-semibold">{{ $product->name }}</p><p class="mt-1 text-[10px] text-brand-textGray">{{ number_format($product->quantity_sold, 3, ',', '.') }} kg terjual</p></div>
                    <span class="text-xs font-semibold text-brand-primary">Rp {{ number_format($product->profit, 0, ',', '.') }}</span>
                </a>
            @empty
                <div class="empty-state">Belum ada item penjualan dalam periode ini.</div>
            @endforelse
            </div>
            <div class="mt-6 border-t border-[#e5ebf1] pt-4"><a href="{{ route('products.index') }}" class="flex items-center justify-between text-xs font-medium text-brand-textGray">Kelola produk <x-icon name="arrow"/></a></div>
        </section>
    </div>
    <section class="panel mb-6 overflow-hidden">
        <div class="flex items-center justify-between gap-3 p-5 sm:px-6"><div><h2 class="text-sm font-semibold">Transaksi terbaru</h2><p class="mt-1 text-xs text-brand-textGray">Penjualan dalam periode terpilih</p></div><a href="{{ route('sales.index', ['from' => $from, 'to' => $to]) }}" class="flex items-center gap-2 text-xs font-medium text-brand-primary">Lihat semua<x-icon name="arrow"/></a></div>
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Nomor transaksi</th><th>Pelanggan</th><th>Tanggal</th><th>Total penjualan</th><th>Status pembayaran</th><th></th></tr></thead><tbody>
            @forelse($recentSales as $sale)<tr><td class="font-medium">{{ $sale->invoice_number }}</td><td>{{ $sale->customer?->name ?? 'Umum' }}</td><td class="text-brand-textGray">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td><td class="font-medium">Rp {{ number_format($sale->total, 0, ',', '.') }}</td><td><span @class(['badge', 'badge-warning' => $sale->status_label !== 'Lunas'])><span class="h-1 w-1 rounded-full bg-current"></span>{{ $sale->status_label }}</span></td><td><a href="{{ route('sales.index', ['detail' => $sale->id]) }}" aria-label="Detail {{ $sale->invoice_number }}" class="text-brand-textGray"><x-icon name="arrow"/></a></td></tr>
            @empty<tr><td colspan="6"><div class="empty-state">Belum ada penjualan dalam periode ini.</div></td></tr>@endforelse
        </tbody></table></div>
    </section>
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel p-5"><div class="flex justify-between"><h2 class="text-sm font-semibold">Stok perlu perhatian</h2><x-icon name="box" class="text-[#b7791f]"/></div><p class="mt-1 text-[11px] text-brand-textGray">Stok saat ini · seluruh produk aktif</p>
            <div class="mt-4 divide-y divide-[#e5ebf1]">@forelse($lowStockProducts as $product)<a href="{{ route('products.form', $product->id) }}" class="flex items-center justify-between gap-2 py-3"><span class="text-xs font-medium">{{ $product->name }}</span><span class="badge badge-warning">{{ number_format($product->stock_kg, 3, ',', '.') }} kg</span></a>@empty<p class="py-6 text-xs text-brand-textGray">Semua stok berada di atas batas minimum.</p>@endforelse</div>
            <a href="{{ route('products.index', ['lowStock' => 1]) }}" class="mt-3 inline-block text-xs font-medium text-brand-primary">Lihat persediaan →</a>
        </section>
        <section class="panel p-5"><div class="flex justify-between"><h2 class="text-sm font-semibold">Progres produksi</h2><x-icon name="leaf" class="text-[#708399]"/></div><p class="mt-1 text-[11px] text-brand-textGray">Batch yang dimulai dalam periode</p>
            <div class="mt-4 space-y-4">@forelse($activeBatches as $batch)<a href="{{ route('batches.show', ['id' => $batch->id, 'type' => $batch->type]) }}" class="block"><div class="flex justify-between gap-2 text-xs"><span class="font-medium">{{ $batch->batch_number }}</span><span class="text-brand-textGray">{{ $batch->type }}</span></div><div class="mt-2 flex items-center gap-2"><span class="h-1 flex-1 overflow-hidden rounded-full bg-[#d5f3e8]"><span class="block h-full bg-[#64cfb0]" style="width: {{ $batch->current_stage === 'Belum Diproses' ? 8 : ($batch->current_stage === 'Pemisahan' ? 75 : 40) }}%"></span></span><span class="text-[10px] text-brand-textGray">{{ $batch->current_stage }}</span></div></a>@empty<p class="py-6 text-xs text-brand-textGray">Tidak ada batch aktif dalam periode ini.</p>@endforelse</div>
        </section>
        <section class="panel p-5"><div class="flex justify-between"><h2 class="text-sm font-semibold">Pengingat jatuh tempo</h2><x-icon name="clock" class="text-[#b7791f]"/></div><p class="mt-1 text-[11px] text-brand-textGray">Tagihan yang masih terbuka saat ini</p>
            <div class="mt-4 divide-y divide-[#e5ebf1]">@forelse($dueSales as $sale)<a href="{{ route('sales.index', ['detail' => $sale->id]) }}" class="flex items-center justify-between gap-2 py-3"><div><p class="text-xs font-medium">{{ $sale->customer?->name ?? 'Umum' }}</p><p @class(['mt-1 text-[10px]', 'text-red-600' => $sale->due_date < \App\Decimal::today(), 'text-brand-textGray' => $sale->due_date >= \App\Decimal::today()])>{{ $sale->due_date < \App\Decimal::today() ? 'Terlambat · ' : '' }}{{ \Carbon\Carbon::parse($sale->due_date)->format('d M Y') }}</p></div><span class="text-xs font-semibold">Rp {{ number_format($sale->balance, 0, ',', '.') }}</span></a>@empty<p class="py-6 text-xs text-brand-textGray">Tidak ada tagihan dengan tanggal jatuh tempo.</p>@endforelse</div>
        </section>
    </div>
</div>
