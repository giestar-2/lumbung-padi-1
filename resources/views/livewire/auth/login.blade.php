<div class="grid overflow-hidden rounded-3xl bg-white shadow-soft lg:grid-cols-2">
    <aside class="relative hidden flex-col justify-between overflow-hidden bg-brand-primary p-10 text-white lg:flex">
        <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"><x-icon name="leaf" class="h-7 w-7"/></span><span class="font-heading text-xl font-bold tracking-tight">Lumbung Beras</span></div>
        <div class="my-14">
            <p class="text-xs font-medium uppercase tracking-[.12em] text-[#adc6ff]">Ruang kerja pemilik usaha</p>
            <h2 class="mt-4 text-[32px] font-bold leading-[1.3] tracking-[-.02em]">Seluruh aktivitas lumbung dalam satu tempat.</h2>
            <p class="mt-5 text-sm leading-7 text-[#d8e2ff]">Pantau produksi, kelola persediaan, dan catat penjualan dengan lebih mudah.</p>
            <div class="mt-8 space-y-3">
                @foreach([['leaf', 'Produksi & persediaan'], ['sales', 'Penjualan & pelanggan'], ['wallet', 'Keuangan usaha']] as [$icon, $label])
                    <div class="flex items-center gap-3 rounded-xl bg-white/10 p-3"><x-icon :name="$icon"/><span class="text-[13px] font-medium">{{ $label }}</span><x-icon name="check" class="ml-auto text-[#adc6ff]"/></div>
                @endforeach
            </div>
        </div>
        <p class="text-xs text-[#adc6ff]">Manajemen lumbung beras</p>
    </aside>
    <section class="p-7 sm:p-10 lg:p-12">
        <div class="mb-8 flex items-center gap-3 lg:hidden"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-primary text-white"><x-icon name="leaf"/></span><span class="font-heading text-lg font-bold">Lumbung Beras</span></div>
        <p class="eyebrow">Masuk ke akun</p>
        <h1 class="page-title">Selamat datang kembali.</h1>
        <p class="mb-7 mt-3 text-sm leading-6 text-brand-textGray">Masuk untuk mengelola aktivitas lumbung Anda.</p>
        <x-feedback/>
        <form wire:submit="login" class="space-y-5">
            <label class="field" for="email">Email<input id="email" wire:model="email" type="email" class="input" required autofocus autocomplete="username" placeholder="Email pemilik usaha"></label>
            <div class="field" x-data="{ show: false }"><label for="password">Password</label><div class="relative"><input id="password" wire:model="password" :type="show ? 'text' : 'password'" type="password" class="input pr-24" required autocomplete="current-password"><button type="button" @click="show = !show" :aria-pressed="show" class="absolute inset-y-0 right-3 text-[11px] font-medium text-brand-primary" x-text="show ? 'Sembunyikan' : 'Tampilkan'">Tampilkan</button></div></div>
            <label class="flex items-center gap-2 text-xs text-brand-textGray"><input wire:model="remember" type="checkbox">Ingat saya di perangkat ini</label>
            <button class="btn btn-primary w-full" type="submit" wire:loading.attr="disabled"><span wire:loading.remove>Masuk ke ruang kerja</span><span wire:loading>Memeriksa akun…</span><x-icon name="arrow"/></button>
        </form>
        <p class="mt-8 border-t border-[#eaeef2] pt-5 text-xs leading-5 text-brand-textGray">Satu akun untuk pemilik usaha. Karyawan dikelola melalui menu Karyawan.</p>
    </section>
</div>
