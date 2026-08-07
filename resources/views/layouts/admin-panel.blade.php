<!DOCTYPE html>
<html lang="id" data-theme="{{ auth()->user()->theme ?? 'glow' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Admin Panel' }} - Karya Ide Edi</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,600i,700|instrument-sans:400,500,600,700" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        @livewireStyles
    </head>
    <body
        x-data="{ sidebarOpen: false }"
        class="bg-admin-canvas font-sans text-admin-ink antialiased"
    >
        {{--
            Garis aksen emas: SENGAJA ditaruh di sini, sebagai anak langsung
            <body>, BUKAN di dalam <header>. <header> memakai backdrop-blur-xl,
            dan backdrop-filter membuat browser menganggap header sebagai
            containing block baru — akibatnya `fixed inset-x-0` jadi relatif
            terhadap header (tidak full-width, terpotong di sisi sidebar),
            bukan relatif terhadap viewport. Ditaruh di luar semua ancestor
            ber-filter/transform supaya `fixed` benar-benar relatif ke
            viewport dan membentang penuh dari ujung kiri ke ujung kanan
            browser, termasuk melewati area sidebar.
        --}}
        <div class="fixed inset-x-0 top-0 z-50 h-0.75 bg-linear-to-r from-admin-gold via-admin-accent to-admin-gold"></div>

        <div class="flex min-h-screen">

            {{-- ================= SIDEBAR ================= --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                class="fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 flex-col bg-admin-sidebar text-admin-sidebar-ink shadow-2xl shadow-black/30 transition-transform duration-300 lg:translate-x-0"
            >
                {{-- garis aksen emas tipis di tepi kanan sidebar --}}
                <div class="pointer-events-none absolute inset-y-0 right-0 w-px bg-linear-to-b from-transparent via-admin-gold/30 to-transparent"></div>

                {{-- logo lockup — sumber tunggal: partials.logo (Pengaturan > Identitas Website) --}}
                @php $siteSetting = \App\Models\Setting::current(); @endphp
                <div class="relative flex h-18 shrink-0 items-center gap-3 border-b border-admin-sidebar-border bg-admin-sidebar-ink/3 px-6">
                    @include('partials.logo', [
                        'boxSize' => 'h-11 w-11',
                        'rounded' => 'rounded-2xl',
                        'boxClass' => 'bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-black/30 ring-1 ring-admin-sidebar-ink/10',
                        'imgClass' => 'shadow-lg shadow-black/30 ring-1 ring-admin-sidebar-ink/10',
                        'iconClass' => 'text-lg text-admin-sidebar-ink',
                    ])
                    <div class="min-w-0">
                        <span class="block truncate font-display text-base font-semibold leading-tight text-admin-sidebar-ink">
                            {{ $siteSetting->site_name }}
                        </span>
                        <span class="block text-[10.5px] font-semibold uppercase tracking-[0.18em] text-admin-sidebar-ink/40">
                            Admin Panel
                        </span>
                    </div>
                </div>

                <div class="px-6 pt-5">
                    <span class="inline-flex items-center gap-2 rounded-full border border-admin-sidebar-border bg-admin-sidebar-ink/6 px-3 py-1.5 text-[10.5px] font-medium text-admin-sidebar-ink/50 shadow-inner shadow-black/20">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        </span>
                        Toko Aktif
                    </span>
                </div>

                <nav data-sidebar-scroll class="admin-scroll flex-1 overflow-y-auto px-5 py-6">
                    <p class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-admin-sidebar-ink/35">
                        Menu
                    </p>
                    <ul class="space-y-1.5">
                        @php
                            $navItem = fn (string $route, string $icon, string $label) => [
                                'route' => $route,
                                'icon' => $icon,
                                'label' => $label,
                                'active' => request()->routeIs($route),
                            ];
                            $mainNav = [
                                $navItem('admin.dashboard', 'fa-gauge', 'Dashboard'),
                                $navItem('admin.products', 'fa-couch', 'Produk'),
                                $navItem('admin.categories', 'fa-tags', 'Kategori'),
                                $navItem('admin.transactions', 'fa-receipt', 'Pesanan'),
                                $navItem('admin.customers', 'fa-users', 'Pelanggan'),
                                $navItem('admin.reports', 'fa-chart-column', 'Laporan'),
                            ];
                        @endphp

                        <li>
                            <a
                                href="{{ route('home') }}"
                                class="group relative flex items-center gap-3 overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium text-admin-sidebar-ink/55 transition-all duration-200 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink"
                            >
                                <span class="absolute inset-y-0 left-0 w-0.75 scale-y-0 rounded-r-full bg-admin-gold transition-transform duration-200 group-hover:scale-y-100"></span>
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-admin-sidebar-ink/0 transition-colors duration-200 group-hover:bg-admin-sidebar-ink/8">
                                    <i class="fa-solid fa-house text-center text-[13px] text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80"></i>
                                </span>
                                Home
                            </a>
                        </li>

                        @foreach ($mainNav as $item)
                            <li>
                                <a
                                    href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                    wire:navigate
                                    class="group relative flex items-center justify-between overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium transition-all duration-200
                                        {{ $item['active']
                                            ? 'bg-linear-to-r from-admin-accent to-admin-accent-strong text-white shadow-md shadow-black/25'
                                            : 'text-admin-sidebar-ink/55 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink' }}"
                                >
                                    <span class="absolute inset-y-0 left-0 w-0.75 rounded-r-full bg-admin-gold transition-transform duration-200
                                        {{ $item['active'] ? 'scale-y-100' : 'scale-y-0 group-hover:scale-y-100' }}"></span>
                                    <span class="flex items-center gap-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors duration-200
                                            {{ $item['active'] ? 'bg-white/15' : 'bg-admin-sidebar-ink/0 group-hover:bg-admin-sidebar-ink/8' }}">
                                            <i class="fa-solid {{ $item['icon'] }} text-center text-[13px] {{ $item['active'] ? 'text-white' : 'text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80' }}"></i>
                                        </span>
                                        {{ $item['label'] }}
                                    </span>
                                    @if ($item['route'] === 'admin.dashboard')
                                        <livewire:admin.nav-badge type="dashboard" :active="$item['active']" :key="'nav-badge-dashboard'" />
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 border-t border-admin-sidebar-border pt-6">
                    <p class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-admin-sidebar-ink/35">
                        Lainnya
                    </p>
                    <ul class="space-y-1.5">
                        @php
                            $otherNav = [
                                $navItem('admin.settings', 'fa-gear', 'Pengaturan'),
                            ];
                            $isInteraksiActive = request()->routeIs('admin.interaksi');
                        @endphp

                        @foreach ($otherNav as $item)
                            <li>
                                <a
                                    href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                    wire:navigate
                                    class="group relative flex items-center gap-3 overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium transition-all duration-200
                                        {{ $item['active']
                                            ? 'bg-linear-to-r from-admin-accent to-admin-accent-strong text-white shadow-md shadow-black/25'
                                            : 'text-admin-sidebar-ink/55 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink' }}"
                                >
                                    <span class="absolute inset-y-0 left-0 w-0.75 rounded-r-full bg-admin-gold transition-transform duration-200
                                        {{ $item['active'] ? 'scale-y-100' : 'scale-y-0 group-hover:scale-y-100' }}"></span>
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors duration-200
                                        {{ $item['active'] ? 'bg-white/15' : 'bg-admin-sidebar-ink/0 group-hover:bg-admin-sidebar-ink/8' }}">
                                        <i class="fa-solid {{ $item['icon'] }} text-center text-[13px] {{ $item['active'] ? 'text-white' : 'text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80' }}"></i>
                                    </span>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach

                        {{-- Interaksi — moderasi komentar/testimoni pembeli --}}
                        <li>
                            <a
                                href="{{ route('admin.interaksi') }}"
                                wire:navigate
                                class="group relative flex items-center justify-between overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium transition-all duration-200
                                    {{ $isInteraksiActive
                                        ? 'bg-linear-to-r from-admin-accent to-admin-accent-strong text-white shadow-md shadow-black/25'
                                        : 'text-admin-sidebar-ink/55 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink' }}"
                            >
                                <span class="absolute inset-y-0 left-0 w-0.75 rounded-r-full bg-admin-gold transition-transform duration-200
                                    {{ $isInteraksiActive ? 'scale-y-100' : 'scale-y-0 group-hover:scale-y-100' }}"></span>
                                <span class="flex items-center gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors duration-200
                                        {{ $isInteraksiActive ? 'bg-white/15' : 'bg-admin-sidebar-ink/0 group-hover:bg-admin-sidebar-ink/8' }}">
                                        <i class="fa-solid fa-comments text-center text-[13px] {{ $isInteraksiActive ? 'text-white' : 'text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80' }}"></i>
                                    </span>
                                    Interaksi
                                </span>
                                <livewire:admin.nav-badge type="interaksi" :active="$isInteraksiActive" :key="'nav-badge-interaksi'" />
                            </a>
                        </li>
                    </ul>
                    </div>
                </nav>

                <div class="border-t border-admin-sidebar-border bg-admin-sidebar-ink/3 p-5">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.75 text-sm font-medium text-admin-sidebar-ink/55 transition-all duration-200 hover:bg-red-500/10 hover:text-red-300"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-admin-sidebar-ink/0 transition-colors duration-200 group-hover:bg-red-500/15">
                                <x-icon-arrow direction="logout" class="text-center" />
                            </span>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            {{-- overlay mobile --}}
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm lg:hidden"
            ></div>

            {{-- ================= MAIN CONTENT ================= --}}
            <div class="flex min-h-screen flex-1 flex-col lg:ml-72">

                {{-- topbar --}}
                <header class="sticky top-0 z-20 border-b border-admin-border bg-admin-surface/90 shadow-sm shadow-black/3 backdrop-blur-xl">
                    <div class="flex h-18 shrink-0 items-center justify-between px-5 sm:px-8">
                        <div class="flex items-center gap-3.5">
                            <button
                                @click="sidebarOpen = true"
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-ink lg:hidden"
                            >
                                <i class="fa-solid fa-bars"></i>
                            </button>
                            <div>
                                <h1 class="font-display text-base font-semibold leading-tight tracking-tight text-admin-ink sm:text-lg">
                                    {{ $title ?? 'Dashboard' }}
                                </h1>
                                <p class="hidden text-[11px] font-medium uppercase tracking-[0.08em] text-admin-ink-soft/80 sm:block">
                                    Karya Ide Edi &middot; Panel Admin
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3.5 sm:gap-5">
                            {{-- toggle tema Glow / Dark --}}
                            <button
                                type="button"
                                x-data="{ dark: {{ (auth()->user()->theme ?? 'glow') === 'dark' ? 'true' : 'false' }} }"
                                @click="
                                    dark = !dark;
                                    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'glow');
                                    fetch('{{ route('admin.theme.update') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json',
                                        },
                                        body: JSON.stringify({ theme: dark ? 'dark' : 'glow' }),
                                    });
                                "
                                class="relative flex h-8 w-15 shrink-0 items-center rounded-full border border-admin-border bg-admin-cream px-1 transition-all duration-300 ease-out hover:border-admin-accent/40"
                                title="Ganti tema Glow / Dark"
                            >
                                <i class="fa-solid fa-sun absolute left-1.5 text-[11px] text-admin-gold"></i>
                                <i class="fa-solid fa-moon absolute right-1.5 text-[11px] text-admin-ink-soft"></i>
                                <span
                                    class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full bg-admin-accent text-white shadow-md shadow-black/20 transition-transform duration-300"
                                    :class="dark ? 'translate-x-[1.85rem]' : 'translate-x-0'"
                                >
                                    <i class="fa-solid text-[10px]" :class="dark ? 'fa-moon' : 'fa-sun'"></i>
                                </span>
                            </button>

                            {{-- jam & tanggal --}}
                            <div
                                x-data="{ time: '' }"
                                x-init="
                                    const tick = () => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                                    tick();
                                    setInterval(tick, 1000);
                                "
                                class="hidden items-center gap-2.5 rounded-full border border-admin-border bg-admin-cream/60 px-3.5 py-2 text-xs font-medium text-admin-ink-soft md:flex"
                            >
                                <i class="fa-regular fa-clock text-admin-accent"></i>
                                <span x-text="time" class="font-medium tabular-nums text-admin-ink"></span>
                                <span class="text-admin-border">|</span>
                                <span>{{ now()->translatedFormat('l, d M Y') }}</span>
                            </div>

                            {{-- dropdown profil --}}
                            <div x-data="{ profileOpen: false }" class="relative">
                                <button
                                    @click="profileOpen = !profileOpen"
                                    @click.outside="profileOpen = false"
                                    class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-2 transition-colors duration-200 hover:bg-admin-cream"
                                >
                                    @if (auth()->user()?->fotoProfilUrl())
                                        <img
                                            src="{{ auth()->user()->fotoProfilUrl() }}"
                                            alt="Foto profil"
                                            class="h-9 w-9 rounded-full object-cover ring-2 ring-admin-cream"
                                        >
                                    @else
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-admin-panel text-sm font-semibold text-white ring-2 ring-admin-cream">
                                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                                        </span>
                                    @endif
                                    <span class="hidden text-left sm:block">
                                        <span class="block text-sm font-semibold leading-tight text-admin-ink">
                                            {{ auth()->user()->name ?? 'Admin' }}
                                        </span>
                                        <span class="block text-xs leading-tight text-admin-ink-soft">
                                            Pemilik Toko
                                        </span>
                                    </span>
                                    <x-icon-arrow direction="chevron-down" size="text-[10px]" class="hidden text-admin-ink-soft sm:block" />
                                </button>

                                <div
                                    x-show="profileOpen"
                                    x-cloak
                                    x-transition.origin.top.right
                                    class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-2xl border border-admin-border bg-admin-surface py-2 shadow-xl shadow-black/10"
                                >
                                    <div class="border-b border-admin-border bg-admin-cream/50 px-4 py-3">
                                        <p class="truncate text-sm font-semibold text-admin-ink">
                                            {{ auth()->user()->name ?? 'Admin' }}
                                        </p>
                                        <p class="truncate text-xs text-admin-ink-soft">
                                            {{ auth()->user()->email ?? '' }}
                                        </p>
                                    </div>

                                    <div class="my-1 border-t border-admin-border"></div>

                                    <form method="POST" action="{{ route('admin.logout') }}" class="p-1.5 pt-0">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-500 transition-all duration-150 hover:translate-x-0.5 hover:bg-red-500/10"
                                        >
                                            <x-icon-arrow direction="logout" class="w-4 text-center" />
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main wire:key="main-{{ request()->path() }}" class="flex-1 animate-fade-in-up px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts

        {{--
            UX FIX: input angka bernilai default "0" (harga, stok, berat,
            urutan kategori, dll) — nol di depan otomatis "hilang" begitu
            admin mulai mengetik, sehingga:
                "0" + ketik "9764"   -> "9764"   (bukan "09764")
                "0" + ketik "5"      -> "5"
                "0" + paste "350000" -> "350000"
            Field yang sudah berisi data asli (mis. "125000") TIDAK
            tersentuh sama sekali — aturan cuma berlaku kalau ada nol di
            paling depan yang diikuti angka lain. Klik, pilih sebagian,
            Ctrl+A, edit manual semuanya tetap normal. Field kosong
            dibiarkan kosong, tidak dipaksa jadi "0". Angka desimal
            seperti "0.5" tidak diganggu (setelah "0" ada "." bukan
            digit, jadi tidak match).

            SENGAJA inline di sini (bukan resources/js/app.js) supaya
            aktif tanpa bergantung pada `npm run build`. Dipasang di
            document dengan { capture: true } supaya perbaikan nilai ini
            terjadi SEBELUM listener wire:model milik Livewire membaca
            value-nya — jadi wire:model / .live / .lazy / .defer semua
            selalu menerima angka yang sudah bersih, apa pun mode-nya.
            Berlaku otomatis untuk SEMUA <input type="number"> di seluruh
            panel admin, termasuk yang dirender ulang oleh Livewire dan
            halaman admin yang dibuat setelah ini.
        --}}
        {{--
            BUG FIX: sidebar admin (menu Dashboard/Produk/Kategori/.../Interaksi)
            punya scroll sendiri (overflow-y-auto). Setiap kali pindah halaman
            lewat wire:navigate, seluruh <body> dimuat ulang dari server supaya
            badge notifikasi selalu segar — efek sampingnya, tanpa kode ini,
            posisi scroll sidebar ikut ke-reset ke paling atas setiap navigasi.

            Simpan posisi scroll sidebar sesaat SEBELUM navigasi dimulai
            (livewire:navigate), lalu kembalikan lagi begitu halaman baru
            selesai dimuat (livewire:navigated) — jadi sidebar terasa "diam"
            seperti dashboard modern (TikTok/Facebook/Discord), walau
            sebenarnya di-render ulang dari server tiap pindah menu.
        --}}
        <script>
            (function () {
                var STORAGE_KEY = 'adminSidebarScrollTop';

                document.addEventListener('livewire:navigate', function () {
                    var sidebar = document.querySelector('[data-sidebar-scroll]');
                    if (sidebar) {
                        sessionStorage.setItem(STORAGE_KEY, String(sidebar.scrollTop));
                    }
                });

                document.addEventListener('livewire:navigated', function () {
                    var sidebar = document.querySelector('[data-sidebar-scroll]');
                    var saved = sessionStorage.getItem(STORAGE_KEY);
                    if (sidebar && saved !== null) {
                        sidebar.scrollTop = parseInt(saved, 10);
                    }
                });
            })();
        </script>

        <script>
            document.addEventListener('input', function (event) {
                var input = event.target;

                if (input.tagName !== 'INPUT' || input.type !== 'number') {
                    return;
                }

                if (/^0+\d/.test(input.value)) {
                    input.value = input.value.replace(/^0+(?=\d)/, '');
                }
            }, true);

            // Bonus UX: begitu field yang masih persis "0" difokus,
            // teksnya diseleksi supaya kelihatan siap diganti.
            document.addEventListener('focusin', function (event) {
                var input = event.target;

                if (
                    input.tagName === 'INPUT'
                    && input.type === 'number'
                    && input.value === '0'
                ) {
                    input.select();
                }
            });

            {{--
                Helper Alpine dipakai bareng <x-icon-arrow> untuk tombol
                naik/turun kustom di setiap <input type="number"> (gantinya
                spinner bawaan browser yang disembunyikan lewat CSS di
                app.css). SATU tempat, dipakai ulang di semua field angka —
                lihat resources/views/pages/admin/produk-form.blade.php dan
                kategori-form.blade.php untuk contoh pemakaian.
            --}}
            document.addEventListener('alpine:init', function () {
                Alpine.data('numberStepper', function () {
                    return {
                        step(delta) {
                            var el = this.$refs.numInput;
                            var stepAttr = parseFloat(el.step) || 1;
                            var min = el.min !== '' ? parseFloat(el.min) : -Infinity;
                            var max = el.max !== '' ? parseFloat(el.max) : Infinity;
                            var current = parseFloat(el.value);
                            if (isNaN(current)) current = 0;

                            var next = Math.min(max, Math.max(min, current + delta * stepAttr));
                            var decimals = (stepAttr.toString().split('.')[1] || '').length;
                            el.value = decimals ? next.toFixed(decimals) : String(next);

                            el.dispatchEvent(new Event('input', { bubbles: true }));
                        },
                    };
                });
            });
        </script>
    </body>
</html>