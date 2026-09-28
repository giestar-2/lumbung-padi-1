<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kelola produksi, persediaan, penjualan, dan keuangan lumbung dalam satu ruang kerja.">
    <title>{{ $title ?? 'Ruang kerja' }} · {{ auth()->user()->store_name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-white focus:p-3">Langsung ke konten</a>
<div x-show="menuOpen" x-cloak @click="menuOpen = false" class="fixed inset-0 z-30 bg-[#172b3a]/30 backdrop-blur-sm lg:hidden"></div>
<aside id="main-navigation" :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-40 flex w-[232px] flex-col border-r border-[#e5ebf1] bg-[#ffffff] transition-transform duration-200">
    <a href="{{ route('dashboard') }}" class="flex h-[83px] items-center gap-3 px-6">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-primary text-white shadow-elegant"><x-icon name="leaf" class="h-6 w-6"/></span>
        <span class="min-w-0"><span class="block truncate text-[17px] font-semibold tracking-tight">{{ auth()->user()->store_name }}</span><span class="text-[10px] font-medium uppercase tracking-[.18em] text-[#708399]">Workspace</span></span>
    </a>
    <div class="mx-4 mb-5 flex items-center gap-3 rounded-xl border border-[#d7eee6] bg-[#f0fbf6] px-3 py-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-xs font-semibold text-brand-primary">LB</span>
        <div><p class="text-xs font-semibold">Operasional usaha</p><p class="mt-0.5 text-[10px] text-brand-textGray">Ruang kerja owner</p></div>
        <span class="ml-auto h-1.5 w-1.5 rounded-full bg-brand-primary"></span>
    </div>
    <nav aria-label="Navigasi utama" class="flex-1 overflow-y-auto px-4 pb-4">
        @php
        $groups = [
            'Ringkasan' => [
                ['dashboard', [], 'Dashboard', 'grid', request()->routeIs('dashboard')],
                ['sales.pos', [], 'Kasir / POS', 'sales', request()->routeIs('sales.pos')],
            ],
            'Operasional' => [
                ['products.index', [], 'Produk & stok', 'box', request()->routeIs('products.*')],
                ['sales.index', [], 'Penjualan', 'clock', request()->routeIs('sales.index')],
                ['customers.index', [], 'Pelanggan', 'users', request()->routeIs('customers.*')],
            ],
            'Keuangan & tim' => [
                ['cash.index', ['direction' => 'Pemasukan'], 'Pemasukan', 'down', request()->routeIs('cash.*') && request('direction', 'Pemasukan') === 'Pemasukan'],
                ['cash.index', ['direction' => 'Pengeluaran'], 'Pengeluaran', 'up', request()->routeIs('cash.*') && request('direction') === 'Pengeluaran'],
                ['payrolls.index', [], 'Pembayaran gaji', 'wallet', request()->routeIs('payrolls.*')],
                ['employees.index', [], 'Karyawan', 'users', request()->routeIs('employees.*')],
            ],
        ];
        @endphp
        @foreach($groups as $group => $links)
            <p class="mb-2 mt-5 px-3 text-[11px] font-medium text-[#708399]">{{ $group }}</p>
            <div class="space-y-0.5">
            @foreach($links as [$route, $params, $label, $icon, $active])
                <a href="{{ route($route, $params) }}" @class(['nav-link', 'is-active' => $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon"/>{{ $label }}</a>
            @endforeach
            </div>
            @if($group === 'Operasional')
                @php($productionActive = request()->routeIs('batches.*'))
                <details class="mt-1 group/production" @if($productionActive) open @endif>
                    <summary @class(['nav-link list-none', 'is-active' => $productionActive])>
                        <x-icon name="leaf"/><span class="flex-1">Produksi</span><svg class="h-4 w-4 transition-transform group-open/production:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="ml-5 mt-1 space-y-0.5 border-l border-[#cfe9df] pl-3">
                        @foreach([
                            ['Beras', 'Beras dari gabah'],
                            ['Dedek', 'Dedek dari sisa gabah'],
                            ['Pupuk', 'Pupuk dari sisa gabah'],
                        ] as [$type, $label])
                            <a href="{{ route('batches.index', ['type' => $type]) }}" @class(['nav-sublink', 'is-active' => $productionActive && request('type', 'Beras') === $type]) @if($productionActive && request('type', 'Beras') === $type) aria-current="page" @endif>
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $label }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </nav>
    <div class="border-t border-[#e5ebf1] p-4">
        <a href="{{ route('settings') }}" @class(['nav-link', 'is-active' => request()->routeIs('settings')])><x-icon name="settings"/>Pengaturan</a>
        <form action="{{ route('logout') }}" method="POST">@csrf<button class="nav-link w-full" type="submit"><x-icon name="logout"/>Keluar</button></form>
    </div>
</aside>
<div class="min-h-dvh lg:pl-[232px]">
    <header class="no-print sticky top-0 z-20 flex h-[72px] items-center justify-between gap-4 border-b border-[#e5ebf1] bg-white/95 px-5 backdrop-blur-md sm:px-8">
        <div class="flex items-center gap-3">
            <button @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="main-navigation" aria-label="Buka navigasi" class="btn px-2 lg:hidden"><x-icon name="menu"/></button>
            <span class="hidden text-xs text-brand-textGray sm:inline">Ruang kerja</span><span class="hidden text-[#d5dfe9] sm:inline">/</span><span class="text-xs font-medium">Manajemen lumbung</span>
        </div>
        <div class="flex items-center gap-5">
            <span class="hidden items-center gap-2 text-[11px] text-brand-textGray xl:flex"><x-icon name="calendar"/>{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d M Y') }}</span>
            <a href="{{ route('settings') }}" class="flex items-center gap-3 border-l border-[#e5ebf1] pl-5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#def7ef] text-xs font-semibold text-[#087861]">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="hidden sm:block"><span class="block text-xs font-semibold">{{ auth()->user()->name }}</span><span class="block text-[10px] text-brand-textGray">Pemilik usaha</span></span>
            </a>
        </div>
    </header>
    <main id="main-content" class="mx-auto max-w-[1600px] px-4 py-7 sm:px-8 sm:py-8">
        {{ $slot }}
    </main>
    <footer class="no-print mx-5 flex justify-between border-t border-[#e5ebf1] py-5 text-[10px] text-[#708399] sm:mx-8"><span>{{ auth()->user()->store_name }} · Ruang kerja owner</span><span>Waktu Indonesia Barat</span></footer>
</div>
@livewireScripts
</body>
</html>
