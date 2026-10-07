<div>
    <div class="page-heading">
        <div><h1 class="page-title">Dashboard</h1><p class="page-description">Ringkasan produksi, penjualan, dan keuangan lumbung Anda.</p></div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('sales.index') }}" class="btn"><x-icon name="clock"/>Riwayat penjualan</a><a href="{{ route('sales.pos') }}" class="btn btn-primary"><x-icon name="plus"/>Transaksi baru</a></div>
    </div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex rounded-xl bg-white p-1 shadow-soft">
            @foreach(['today' => 'Hari ini', 'month' => 'Bulan ini', 'custom' => 'Rentang tanggal'] as $value => $label)
                <button wire:click="$set('period', '{{ $value }}')" @class(['period-tab', 'is-active' => $period === $value])>{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex min-w-0 items-center gap-2 text-xs text-brand-textGray">
            @if($period === 'custom')
                <label class="sr-only" for="dashboard-from">Dari tanggal</label><input id="dashboard-from" class="input min-w-0 max-w-40" type="date" wire:model.live="from">
                <span>—</span><label class="sr-only" for="dashboard-to">Sampai tanggal</label><input id="dashboard-to" class="input min-w-0 max-w-40" type="date" wire:model.live="to">
            @else
                <x-icon name="calendar"/>{{ \Carbon\Carbon::parse($from)->locale('id')->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($to)->locale('id')->translatedFormat('d M Y') }}
            @endif
        </div>
    </div>
    @unless($validRange)<p role="alert" class="mb-4 text-sm text-red-600">Rentang tanggal tidak valid. Ringkasan sementara menampilkan bulan berjalan.</p>@endunless
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Penjualan bersih" :value="'Rp '.number_format($penjualanBersih, 0, ',', '.')" :note="$transactionCount.' transaksi dalam periode terpilih'" icon="sales"/>
        <x-stat label="Pemasukan kas" :value="'Rp '.number_format($pemasukanKas, 0, ',', '.')" note="Uang yang diterima dalam periode terpilih" icon="down"/>
        <x-stat label="Pengeluaran kas" :value="'Rp '.number_format($pengeluaranKas, 0, ',', '.')" note="Pembayaran dalam periode terpilih" icon="up"/>
        <x-stat label="Laba usaha" :value="'Rp '.number_format($labaUsaha, 0, ',', '.')" note="Penjualan − HPP − beban operasional" icon="up"/>
    </div>
    @php
        $plotDays = array_values($chart);
        $plotDenominator = max(count($plotDays) - 1, 1);
        $labelStep = max(1, (int) ceil(count($plotDays) / 7));
        $inPoints = [];
        $outPoints = [];
        foreach ($plotDays as $index => $day) {
            $x = round(42 + $index / $plotDenominator * 570, 2);
            $inPoints[] = $x.' '.round(180 - $day['in'] / $chartMax * 145, 2);
            $outPoints[] = $x.' '.round(180 - $day['out'] / $chartMax * 145, 2);
        }
        if (count($plotDays) === 1) {
            $inPoints[] = '612 '.round(180 - $plotDays[0]['in'] / $chartMax * 145, 2);
            $outPoints[] = '612 '.round(180 - $plotDays[0]['out'] / $chartMax * 145, 2);
        }
        $cashMovement = bcadd((string) $pemasukanKas, (string) $pengeluaranKas, 2);
        $incomingPercent = bccomp($cashMovement, '0', 2) > 0 ? (float) $pemasukanKas / (float) $cashMovement * 100 : 0;
        $outgoingPercent = bccomp($cashMovement, '0', 2) > 0 ? 100 - $incomingPercent : 0;
    @endphp
    <div class="mb-6 grid gap-4 lg:grid-cols-12">
        <section class="panel min-w-0 p-6 lg:col-span-7" aria-labelledby="cash-chart-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2 id="cash-chart-title" class="text-base font-semibold">Arus kas harian</h2><p class="mt-1 text-xs text-brand-textGray">Penerimaan dan pembayaran aktual · Rupiah</p></div>
                <div class="flex gap-3 text-[11px] text-brand-textGray"><span class="flex items-center gap-1.5"><span class="chart-legend-dot bg-brand-primary"></span>Pemasukan</span><span class="flex items-center gap-1.5"><span class="chart-legend-dot bg-chart-accent"></span>Pengeluaran</span></div>
            </div>
            @if(count($plotDays))
                <div class="mt-5 overflow-x-auto">
                    <svg class="block h-auto min-w-[360px] w-full" viewBox="0 0 640 224" role="img" aria-label="Grafik pemasukan dan pengeluaran harian. Rincian nominal tersedia pada tabel di bawah grafik.">
                        <defs><linearGradient id="cash-out-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#f67113" stop-opacity=".2"/><stop offset="100%" stop-color="#f67113" stop-opacity="0"/></linearGradient></defs>
                        @for($line = 0; $line < 4; $line++)
                            <line x1="42" x2="612" y1="{{ 35 + $line * 145 / 3 }}" y2="{{ 35 + $line * 145 / 3 }}" stroke="#dfe3e7" stroke-width="1" stroke-dasharray="3 4"/>
                            <text x="34" y="{{ 38 + $line * 145 / 3 }}" text-anchor="end" fill="#747780" font-size="9">{{ $chartMax >= 1000000 ? number_format($chartMax * (3 - $line) / 3 / 1000000, 1, ',', '.').' jt' : number_format($chartMax * (3 - $line) / 3 / 1000, 0, ',', '.').' rb' }}</text>
                        @endfor
                        <path d="M 42 180 L {{ implode(' L ', $outPoints) }} L 612 180 Z" fill="url(#cash-out-fill)"/>
                        <path d="M {{ implode(' L ', $outPoints) }}" fill="none" stroke="#f67113" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M {{ implode(' L ', $inPoints) }}" fill="none" stroke="#00478a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        @foreach($plotDays as $day)
                            @php($x = round(42 + $loop->index / $plotDenominator * 570, 2))
                            <circle cx="{{ $x }}" cy="{{ round(180 - $day['in'] / $chartMax * 145, 2) }}" r="3.5" fill="white" stroke="#00478a" stroke-width="2"><title>{{ $day['date'] }} · Pemasukan Rp {{ number_format($day['in'], 0, ',', '.') }}</title></circle>
                            <circle cx="{{ $x }}" cy="{{ round(180 - $day['out'] / $chartMax * 145, 2) }}" r="3" fill="white" stroke="#f67113" stroke-width="2"><title>{{ $day['date'] }} · Pengeluaran Rp {{ number_format($day['out'], 0, ',', '.') }}</title></circle>
                            @if($loop->index % $labelStep === 0 || $loop->last)<text x="{{ $x }}" y="207" text-anchor="middle" fill="#44474f" font-size="10">{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</text>@endif
                        @endforeach
                    </svg>
                </div>
                <details class="mt-3 text-xs text-brand-textGray"><summary>Lihat rincian grafik</summary><div class="mt-3 max-h-48 overflow-auto"><table class="data-table"><thead><tr><th>Tanggal</th><th>Pemasukan</th><th>Pengeluaran</th></tr></thead><tbody>@foreach($plotDays as $day)<tr><td>{{ $day['date'] }}</td><td>Rp {{ number_format($day['in'], 0, ',', '.') }}</td><td>Rp {{ number_format($day['out'], 0, ',', '.') }}</td></tr>@endforeach</tbody></table></div></details>
            @else
                <div class="empty-state flex min-h-56 flex-col items-center justify-center"><span class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-light text-brand-primary"><x-icon name="wallet" class="h-6 w-6"/></span><p class="font-medium">Belum ada aktivitas kas</p><p class="mt-1 text-xs">Grafik muncul setelah ada penerimaan atau pembayaran.</p><a href="{{ route('sales.pos') }}" class="mt-4 text-xs font-semibold text-brand-primary">Catat penjualan pertama →</a></div>
            @endif
        </section>
        <section class="panel flex min-w-0 flex-col p-6 lg:col-span-5">
            <h2 class="text-base font-semibold">Komposisi kas</h2><p class="mt-1 text-xs text-brand-textGray">Pemasukan dan pengeluaran pada periode terpilih</p>
            <div class="flex flex-1 flex-wrap items-center justify-center gap-6 py-6">
                <div class="relative h-40 w-40 shrink-0 rounded-full" style="background: {{ bccomp($cashMovement, '0', 2) > 0 ? 'conic-gradient(#00478a 0% '.$incomingPercent.'%, #f67113 '.$incomingPercent.'% 100%)' : '#eaeef2' }};" role="img" aria-label="Pemasukan {{ number_format($incomingPercent, 1, ',', '.') }} persen dan pengeluaran {{ number_format($outgoingPercent, 1, ',', '.') }} persen">
                    <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-white px-2 text-center"><span class="text-[10px] text-brand-textGray">Total pergerakan</span><span class="mt-1 font-heading text-lg font-bold">{{ (float) $cashMovement >= 1000000 ? 'Rp '.number_format((float) $cashMovement / 1000000, 1, ',', '.').' jt' : 'Rp '.number_format($cashMovement, 0, ',', '.') }}</span></div>
                </div>
                <dl class="min-w-36 flex-1 space-y-5">
                    <div><dt class="flex items-center gap-2 text-xs"><span class="chart-legend-dot bg-brand-primary"></span>Pemasukan <span class="ml-auto font-semibold">{{ number_format($incomingPercent, 1, ',', '.') }}%</span></dt><dd class="mt-1 pl-4 text-[11px] text-brand-textGray">Rp {{ number_format($pemasukanKas, 0, ',', '.') }}</dd></div>
                    <div><dt class="flex items-center gap-2 text-xs"><span class="chart-legend-dot bg-chart-accent"></span>Pengeluaran <span class="ml-auto font-semibold">{{ number_format($outgoingPercent, 1, ',', '.') }}%</span></dt><dd class="mt-1 pl-4 text-[11px] text-brand-textGray">Rp {{ number_format($pengeluaranKas, 0, ',', '.') }}</dd></div>
                </dl>
            </div>
            <dl class="grid grid-cols-2 gap-4 border-t border-[#eaeef2] pt-4">
                <div class="min-w-0"><dt class="text-[11px] text-brand-textGray">Piutang transaksi periode</dt><dd class="mt-1 break-words font-heading text-base font-bold">Rp {{ number_format($sisaPiutang, 0, ',', '.') }}</dd></div>
                <div class="min-w-0"><dt class="text-[11px] text-brand-textGray">Arus kas bersih</dt><dd class="mt-1 break-words font-heading text-base font-bold">Rp {{ number_format($arusKasBersih, 0, ',', '.') }}</dd></div>
            </dl>
            <a href="{{ route('cash.index', ['from' => $from, 'to' => $to]) }}" class="mt-4 flex items-center justify-between text-xs font-medium text-brand-primary">Lihat catatan kas<x-icon name="arrow"/></a>
        </section>
    </div>
    <div class="mb-6 grid gap-4 lg:grid-cols-12">
        <section class="panel min-w-0 p-6 lg:col-span-8">
            <div class="flex items-start justify-between gap-3"><div><h2 class="text-base font-semibold">Pemasukan per hari</h2><p class="mt-1 text-xs text-brand-textGray">Uang yang diterima pada tanggal pembayaran</p></div><span class="rounded-lg bg-[#f0f4f8] px-2.5 py-1 text-[11px] text-brand-textGray">Periode terpilih</span></div>
            @if(count($plotDays))
                <div class="mt-6 overflow-x-auto"><div class="relative flex h-52 min-w-[360px] items-end gap-2 border-b border-[#dfe3e7] px-2 pb-6">
                    <div class="pointer-events-none absolute inset-x-0 top-0 flex h-44 flex-col justify-between">@for($line = 0; $line < 4; $line++)<div class="border-t border-dashed border-[#dfe3e7]"><span class="relative -top-2 bg-white pr-2 text-[9px] text-[#747780]">{{ number_format($chartMax * (3 - $line) / 3, 0, ',', '.') }}</span></div>@endfor</div>
                    @foreach($plotDays as $day)<div class="relative z-10 flex h-40 min-w-3 flex-1 items-end justify-center"><div class="chart-bar w-3 max-w-4 rounded-t-full bg-brand-secondary" style="height: {{ $day['in'] / $chartMax * 100 }}%;" title="{{ $day['date'] }} · Rp {{ number_format($day['in'], 0, ',', '.') }}"></div>@if($loop->index % $labelStep === 0 || $loop->last)<span class="absolute -bottom-5 text-[10px] text-brand-textGray">{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</span>@endif</div>@endforeach
                </div></div>
            @else
                <div class="empty-state flex min-h-52 items-center justify-center">Belum ada pemasukan dalam periode ini.</div>
            @endif
        </section>
        <section class="panel flex min-w-0 flex-col p-6 lg:col-span-4">
            <h2 class="text-base font-semibold">Produk paling menguntungkan</h2><p class="mt-1 text-xs text-brand-textGray">Laba kotor setelah diskon & HPP</p>
            <div class="mt-5 flex-1 space-y-3">
                @forelse($topProducts as $product)
                    <a href="{{ route('sales.index', ['product' => $product->id, 'from' => $from, 'to' => $to]) }}" class="flex items-center gap-3 rounded-xl py-2 transition-colors hover:bg-[#f0f4f8]">
                        <span @class(['flex h-9 w-9 shrink-0 items-center justify-center rounded-full', 'bg-[#eef4ff] text-brand-primary' => $product->type === 'Beras', 'bg-orange-50 text-orange-700' => $product->type === 'Dedek', 'bg-emerald-50 text-emerald-700' => !in_array($product->type, ['Beras', 'Dedek'])])><x-icon name="box"/></span>
                        <div class="min-w-0 flex-1"><p class="truncate text-xs font-medium">{{ $product->name }}</p><p class="mt-1 text-[10px] text-brand-textGray">{{ \App\Decimal::display($product->quantity_sold, 3) }} kg terjual</p></div>
                        <span @class(['text-[11px] font-semibold', 'text-[#059669]' => $product->profit >= 0, 'text-red-700' => $product->profit < 0])>Rp {{ number_format($product->profit, 0, ',', '.') }}</span>
                    </a>
                @empty
                    <div class="empty-state px-0">Belum ada item penjualan dalam periode ini.</div>
                @endforelse
            </div>
            <a href="{{ route('products.index') }}" class="mt-4 flex items-center justify-between border-t border-[#eaeef2] pt-4 text-xs font-medium text-brand-primary">Kelola produk<x-icon name="arrow"/></a>
        </section>
    </div>
    <section class="panel mb-6 overflow-hidden">
        <div class="flex items-center justify-between gap-3 p-6"><div><h2 class="text-base font-semibold">Transaksi terbaru</h2><p class="mt-1 text-xs text-brand-textGray">Penjualan dalam periode terpilih</p></div><a href="{{ route('sales.index', ['from' => $from, 'to' => $to]) }}" class="flex items-center gap-2 text-xs font-medium text-brand-primary">Lihat semua<x-icon name="arrow"/></a></div>
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Nomor transaksi</th><th>Pelanggan</th><th>Tanggal</th><th>Total penjualan</th><th>Status pembayaran</th><th></th></tr></thead><tbody>
            @forelse($recentSales as $sale)<tr><td class="font-medium">{{ $sale->invoice_number }}</td><td>{{ $sale->customer?->name ?? 'Umum' }}</td><td class="text-brand-textGray">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td><td class="font-medium">Rp {{ number_format($sale->total, 0, ',', '.') }}</td><td><span @class(['badge', 'badge-warning' => $sale->status_label !== 'Lunas'])><span class="h-1 w-1 rounded-full bg-current"></span>{{ $sale->status_label }}</span></td><td><a href="{{ route('sales.index', ['detail' => $sale->id]) }}" aria-label="Detail {{ $sale->invoice_number }}" class="text-brand-textGray"><x-icon name="arrow"/></a></td></tr>
            @empty<tr><td colspan="6"><div class="empty-state">Belum ada penjualan dalam periode ini.</div></td></tr>@endforelse
        </tbody></table></div>
    </section>
    <div class="grid gap-4 lg:grid-cols-3">
        <section class="panel p-6"><div class="flex justify-between"><h2 class="text-base font-semibold">Stok perlu perhatian</h2><x-icon name="box" class="text-[#f67113]"/></div><p class="mt-1 text-[11px] text-brand-textGray">Stok saat ini · seluruh produk aktif</p>
            <div class="mt-4 divide-y divide-[#eaeef2]">@forelse($lowStockProducts as $product)<a href="{{ route('products.form', $product->id) }}" class="flex items-center justify-between gap-2 py-3"><span class="text-xs font-medium">{{ $product->name }}</span><span class="badge badge-warning">{{ \App\Decimal::display($product->stock_kg, 3) }} kg</span></a>@empty<p class="py-6 text-xs text-brand-textGray">Semua stok berada di atas batas minimum.</p>@endforelse</div>
            <a href="{{ route('products.index', ['lowStock' => 1]) }}" class="mt-3 inline-block text-xs font-medium text-brand-primary">Lihat persediaan →</a>
        </section>
        <section class="panel p-6"><div class="flex justify-between"><h2 class="text-base font-semibold">Progres produksi</h2><x-icon name="leaf" class="text-brand-primary"/></div><p class="mt-1 text-[11px] text-brand-textGray">Produksi yang dimulai dalam periode</p>
            <div class="mt-4 space-y-4">@forelse($activeBatches as $batch)<a href="{{ route('batches.show', ['id' => $batch->id, 'type' => $batch->type]) }}" class="block"><div class="flex justify-between gap-2 text-xs"><span class="font-medium">{{ $batch->batch_number }}</span><span class="text-brand-textGray">{{ $batch->type }}</span></div><div class="mt-2 flex items-center gap-2"><span class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#d8e2ff]"><span class="block h-full bg-brand-secondary" style="width: {{ $batch->current_stage === 'Belum Diproses' ? 8 : ($batch->current_stage === 'Pemisahan' ? 75 : 40) }}%;"></span></span><span class="text-[10px] text-brand-textGray">{{ $batch->current_stage }}</span></div></a>@empty<p class="py-6 text-xs text-brand-textGray">Tidak ada produksi aktif dalam periode ini.</p>@endforelse</div>
        </section>
        <section class="panel p-6"><div class="flex justify-between"><h2 class="text-base font-semibold">Pengingat jatuh tempo</h2><x-icon name="clock" class="text-[#f67113]"/></div><p class="mt-1 text-[11px] text-brand-textGray">Tagihan yang masih terbuka saat ini</p>
            <div class="mt-4 divide-y divide-[#eaeef2]">@forelse($dueSales as $sale)<a href="{{ route('sales.index', ['detail' => $sale->id]) }}" class="flex items-center justify-between gap-2 py-3"><div><p class="text-xs font-medium">{{ $sale->customer?->name ?? 'Umum' }}</p><p @class(['mt-1 text-[10px]', 'text-red-600' => $sale->due_date < \App\Decimal::today(), 'text-brand-textGray' => $sale->due_date >= \App\Decimal::today()])>{{ $sale->due_date < \App\Decimal::today() ? 'Terlambat · ' : '' }}{{ \Carbon\Carbon::parse($sale->due_date)->format('d M Y') }}</p></div><span class="text-xs font-semibold">Rp {{ number_format($sale->balance, 0, ',', '.') }}</span></a>@empty<p class="py-6 text-xs text-brand-textGray">Tidak ada tagihan dengan tanggal jatuh tempo.</p>@endforelse</div>
        </section>
    </div>
</div>
