<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kelola produksi, persediaan, penjualan, dan keuangan lumbung dalam satu ruang kerja.">
    <meta name="theme-color" content="#00478a">
    <title>{{ $title ?? 'Ruang kerja' }} · {{ auth()->user()->store_name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
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
    $productionLinks = [['Beras', 'Beras dari gabah'], ['Dedek', 'Dedek dari sisa gabah'], ['Pupuk', 'Pupuk dari sisa gabah']];
    $productionActive = request()->routeIs('batches.*');
@endphp
<body x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
<x-notification/>
<x-confirmation-dialog/>
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:p-3">Langsung ke konten</a>
<div x-show="menuOpen" x-cloak @click="menuOpen = false" class="fixed inset-0 z-40 bg-[#001a41]/40 backdrop-blur-sm lg:hidden"></div>
<aside id="main-navigation" :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="no-print fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-brand-sidebar text-white shadow-lg transition-transform duration-200">
    <a href="{{ route('dashboard') }}" class="flex h-16 shrink-0 items-center gap-3 px-5">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15"><x-icon name="leaf" class="h-6 w-6"/></span>
        <span class="truncate font-heading text-[17px] font-bold tracking-tight">{{ auth()->user()->store_name }}</span>
    </a>
    <nav aria-label="Navigasi utama" class="flex-1 overflow-y-auto px-2 pb-5">
        @foreach($groups as $group => $links)
            <p class="mb-2 mt-5 px-4 text-[10px] font-semibold uppercase tracking-[.1em] text-[#adc6ff]">{{ $group }}</p>
            <div class="space-y-1">
                @foreach($links as [$route, $params, $label, $icon, $active])
                    <a href="{{ route($route, $params) }}" @class(['nav-link', 'is-active' => $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon"/>{{ $label }}</a>
                @endforeach
            </div>
            @if($group === 'Operasional')
                <details class="group/production mt-1" @if($productionActive) open @endif>
                    <summary @class(['nav-link list-none', 'is-active' => $productionActive])>
                        <x-icon name="leaf"/><span class="flex-1">Produksi</span><svg class="h-4 w-4 transition-transform group-open/production:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="ml-6 mt-1 space-y-1 border-l border-white/20 pl-2">
                        @foreach($productionLinks as [$type, $label])
                            <a href="{{ route('batches.index', ['type' => $type]) }}" @class(['nav-sublink', 'is-active' => $productionActive && request('type', 'Beras') === $type]) @if($productionActive && request('type', 'Beras') === $type) aria-current="page" @endif><span class="h-1 w-1 shrink-0 rounded-full bg-current"></span>{{ $label }}</a>
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </nav>
    <div class="shrink-0 space-y-1 border-t border-white/10 px-2 pb-4 pt-3">
        <a href="{{ route('settings') }}" @class(['nav-link', 'is-active' => request()->routeIs('settings')])><x-icon name="settings"/>Pengaturan</a>
        <form action="{{ route('logout') }}" method="POST">@csrf<button class="nav-link w-full" type="submit"><x-icon name="logout"/>Keluar</button></form>
        <a href="{{ route('settings') }}" class="mx-1 mt-3 flex items-center gap-3 rounded-xl bg-brand-secondary px-3 py-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#d8e2ff] text-sm font-semibold text-brand-primary">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            <span class="min-w-0"><span class="block truncate text-[13px] font-medium">{{ auth()->user()->name }}</span><span class="mt-0.5 block text-[11px] text-[#adc6ff]">Pemilik usaha</span></span>
        </a>
    </div>
</aside>
<div class="min-h-dvh lg:pl-64">
    <header class="no-print sticky top-0 z-[35] flex h-16 items-center justify-between gap-3 bg-white/95 px-4 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-xl sm:px-6">
        <button @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="main-navigation" aria-label="Buka navigasi" class="btn shrink-0 rounded-lg border-0 px-2 lg:hidden"><x-icon name="menu"/></button>
        <div class="ml-auto flex shrink-0 items-center gap-3">
            <span class="hidden items-center gap-2 rounded-lg bg-[#f0f4f8] px-3 py-2 text-xs text-brand-textGray xl:flex"><x-icon name="calendar"/>{{ now('Asia/Jakarta')->locale('id')->translatedFormat('d M Y') }}</span>
            <a href="{{ route('settings') }}" aria-label="Pengaturan akun" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#d8e2ff] text-xs font-semibold text-brand-primary">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
        </div>
    </header>
    <main id="main-content" class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6">{{ $slot }}</main>
    <footer class="no-print mx-4 flex justify-between gap-3 border-t border-[#dfe3e7] py-5 text-[10px] text-brand-textGray sm:mx-6"><span>{{ auth()->user()->store_name }} · Ruang kerja owner</span><span>Waktu Indonesia Barat</span></footer>
</div>
@livewireScripts
</body>
</html>
