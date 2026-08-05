{{--
    ==========================================================
    NAVBAR FRONTEND — Karya Ide Edi
    ==========================================================
    Komponen UI murni (belum ada logika bisnis apapun).
    - Logo & nama situs: WAJIB dari Pengaturan Website (App\Models\Setting),
      lewat partial terpusat `partials.logo` — bukan file statis.
    - Menu tengah: Beranda, Produk, Kategori, Testimoni, Booking.
      Indikator aktif memakai request()->routeIs() / url()->current(),
      jadi begitu route Produk/Kategori/Testimoni/Booking sudah dibuat,
      navbar ini otomatis mendeteksi tanpa perlu diedit lagi.
    - Search bar, dropdown kategori, wishlist, dan keranjang SENGAJA
      belum difungsikan (murni tampilan) — sesuai instruksi.
    - Tombol admin memakai sistem auth yang sudah ada:
      belum login -> route('admin.login'), sudah login -> route('admin.dashboard').

    Pemakaian:
        @include('partials.frontend.navbar')

    Butuh Font Awesome sudah ter-load di halaman (lihat home-placeholder.blade.php).
    ==========================================================
--}}
@php
    $navSetting = \App\Models\Setting::current();

    // Menu tengah. `route` bernilai null untuk halaman yang belum dibuat,
    // supaya link mengarah ke '#' tanpa memicu RouteNotFoundException.
    $navMenu = [
        ['label' => 'Beranda',   'route' => 'home', 'icon' => 'fa-house'],
        ['label' => 'Produk',    'route' => null,   'icon' => 'fa-couch'],
        ['label' => 'Kategori',  'route' => null,   'icon' => 'fa-layer-group'],
        ['label' => 'Testimoni', 'route' => null,   'icon' => 'fa-star'],
        ['label' => 'Booking',   'route' => null,   'icon' => 'fa-calendar-check'],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-admin-border bg-admin-surface shadow-[0_2px_12px_-4px_rgba(34,26,20,0.08)]">
    <div class="mx-auto flex max-w-7xl items-center gap-6 px-6 py-2.5 sm:px-8 lg:px-10 lg:py-3">

        {{-- ============ KIRI: Logo + Nama Situs ============ --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
            @include('partials.logo', [
                'boxSize' => 'h-9 w-9',
                'rounded' => 'rounded-lg',
                'boxClass' => 'bg-admin-panel',
                'iconClass' => 'text-sm text-white',
                'icon' => 'fa-couch',
            ])
            <span class="hidden font-display text-base font-semibold tracking-tight text-admin-ink sm:block">
                {{ $navSetting->site_name }}
            </span>
        </a>

        {{-- ============ Menu Navigasi (desktop), rapat setelah logo ============ --}}
        <nav class="hidden shrink-0 items-center gap-1 lg:flex">
            @foreach ($navMenu as $item)
                @php
                    $isActive = $item['route'] && request()->routeIs($item['route']);
                    $href = $item['route'] ? route($item['route']) : '#';
                @endphp
                <a
                    href="{{ $href }}"
                    class="group relative px-3 py-1.5 text-sm font-medium transition-colors duration-300 {{ $isActive ? 'text-admin-panel' : 'text-admin-ink-soft hover:text-admin-panel' }}"
                >
                    {{ $item['label'] }}
                    {{-- Indikator aktif / hover: garis bawah halus --}}
                    <span class="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-admin-gold transition-transform duration-300 ease-out {{ $isActive ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"></span>
                </a>
            @endforeach
        </nav>

        {{-- ============ KANAN: Search + Wishlist + Keranjang + Admin ============ --}}
        <div class="ml-auto flex flex-1 items-center justify-end gap-5 lg:flex-none">

            {{-- Search bar modern + dropdown kategori + tombol search (tampilan saja) --}}
            <form class="hidden items-stretch overflow-hidden rounded-lg border border-admin-border bg-admin-canvas transition-all duration-300 focus-within:border-admin-accent focus-within:shadow-[0_0_0_3px_rgba(156,107,63,0.12)] md:flex" onsubmit="return false;">
                <input
                    type="text"
                    placeholder="Cari produk..."
                    disabled
                    class="w-44 bg-transparent px-4 py-2 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:outline-none lg:w-64"
                >
                <span class="my-2 h-auto w-px shrink-0 bg-admin-border"></span>
                <select
                    disabled
                    class="hidden shrink-0 bg-transparent px-3 py-2 text-sm text-admin-ink-soft focus:outline-none lg:block"
                >
                    <option>Semua Produk</option>
                    <option>Kursi</option>
                    <option>Meja</option>
                    <option>Lemari</option>
                    <option>Sofa</option>
                </select>
                <button
                    type="button"
                    disabled
                    aria-label="Cari"
                    class="flex shrink-0 items-center justify-center bg-admin-panel px-3.5 text-white transition-colors duration-300"
                >
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </button>
            </form>

            {{-- Grup ikon: Wishlist, Keranjang, Admin — dirapatkan jadi satu klaster --}}
            <div class="flex shrink-0 items-center gap-1">
                {{-- Wishlist --}}
                <button
                    type="button"
                    aria-label="Wishlist"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-admin-ink-soft transition-all duration-300 hover:bg-admin-canvas hover:text-admin-accent"
                >
                    <i class="fa-regular fa-heart text-sm"></i>
                </button>

                {{-- Keranjang --}}
                <button
                    type="button"
                    aria-label="Keranjang"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-admin-ink-soft transition-all duration-300 hover:bg-admin-canvas hover:text-admin-accent"
                >
                    <i class="fa-solid fa-bag-shopping text-sm"></i>
                </button>

                {{-- Tombol Admin: HANYA tampil untuk pemilik yang sudah login. Pengunjung umum tidak melihat ikon ini sama sekali.
                     Sekaligus jadi pusat notifikasi admin (badge + panel, mirip inbox TikTok) — lihat resources/views/components/⚡notification-bell.blade.php --}}
                @auth
                    <livewire:notification-bell />
                @endauth
            </div>

            {{-- Tombol menu mobile --}}
            <button
                type="button"
                aria-label="Buka menu"
                onclick="document.getElementById('navbar-mobile-menu').classList.toggle('hidden')"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-admin-ink-soft transition-colors duration-300 hover:bg-admin-canvas lg:hidden"
            >
                <i class="fa-solid fa-bars text-base"></i>
            </button>
        </div>
    </div>

    {{-- ============ Menu mobile (dropdown) ============ --}}
    <nav id="navbar-mobile-menu" class="hidden border-t border-admin-border bg-admin-surface px-4 py-2 lg:hidden">
        @foreach ($navMenu as $item)
            @php
                $isActive = $item['route'] && request()->routeIs($item['route']);
                $href = $item['route'] ? route($item['route']) : '#';
            @endphp
            <a
                href="{{ $href }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-300 {{ $isActive ? 'bg-admin-canvas text-admin-panel' : 'text-admin-ink-soft hover:bg-admin-canvas hover:text-admin-panel' }}"
            >
                <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-xs"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</header>