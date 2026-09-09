@php
    $navSetting = \App\Models\Setting::current();

    $navMenu = [
        ['label' => 'Beranda',   'route' => 'home',              'icon' => 'fa-house'],
        ['label' => 'Tentang Kami', 'route' => 'profile.index',     'icon' => 'fa-couch'],
        ['label' => 'Produk',    'route' => 'products.index',    'icon' => 'fa-layer-group'],
        ['label' => 'Testimoni', 'route' => 'testimonials.index','icon' => 'fa-star'],
        ['label' => 'Booking',   'route' => 'booking.index',     'icon' => 'fa-calendar-check'],
    ];

    $navCategories = \App\Models\Category::query()
        ->active()
        ->ordered()
        ->get();

    $currentSearch = trim((string) request('search', ''));
    $currentCategory = trim((string) request('category', ''));
@endphp

<header class="sticky top-0 z-100 border-b border-admin-border/80 bg-admin-surface/95 shadow-[0_4px_20px_-10px_rgba(34,26,20,0.22)] backdrop-blur-md">

    <div class="mx-auto flex h-17 max-w-360 items-center gap-4 px-5 sm:px-7 lg:gap-7 lg:px-10">

        {{-- =========================================================
             LOGO
        ========================================================== --}}
        <a
            href="{{ route('home') }}"
            class="group flex shrink-0 items-center gap-2.5"
            aria-label="Karya Ide Edi - Beranda"
        >
            @include('partials.logo', [
                'boxSize' => 'h-9 w-9',
                'rounded' => 'rounded-lg',
                'boxClass' => 'bg-admin-panel',
                'iconClass' => 'text-sm text-white',
                'icon' => 'fa-couch',
            ])

            <span class="hidden font-display text-[17px] font-semibold tracking-[-0.02em] text-admin-ink transition-colors group-hover:text-admin-panel sm:block">
                {{ $navSetting->site_name }}
            </span>
        </a>


        {{-- =========================================================
             NAVIGASI DESKTOP
        ========================================================== --}}
        <nav class="hidden items-center lg:flex">
            <div class="flex items-center gap-0.5">

                @foreach ($navMenu as $item)
                    @php
                        $isActive = $item['route'] && request()->routeIs($item['route']);
                        $href = $item['route'] ? route($item['route']) : '#';
                    @endphp

                    <a
                        href="{{ $href }}"
                        class="group relative flex h-10 items-center px-3 text-[13px] font-semibold tracking-[-0.01em] transition-colors duration-200
                        {{ $isActive
                            ? 'text-admin-panel'
                            : 'text-admin-ink-soft hover:text-admin-ink' }}"
                    >
                        {{ $item['label'] }}

                        <span
                            class="absolute bottom-0.5 left-3 right-3 h-0.5 origin-center rounded-full bg-admin-gold transition-transform duration-200
                            {{ $isActive ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"
                        ></span>
                    </a>
                @endforeach

            </div>
        </nav>


        {{-- =========================================================
             AREA KANAN
        ========================================================== --}}
        <div class="ml-auto flex min-w-0 items-center gap-2.5 lg:gap-3">


            {{-- =====================================================
                 SEARCH DESKTOP
            ====================================================== --}}
            <form
                action="{{ route('products.index') }}"
                method="GET"
                class="hidden h-10 min-w-0 overflow-hidden rounded-xl border border-admin-border bg-admin-canvas transition-all duration-200 focus-within:border-admin-accent focus-within:ring-4 focus-within:ring-admin-accent/10 md:flex lg:w-97.5"
            >

                <div class="flex min-w-0 flex-1 items-center">
                    <i class="fa-solid fa-magnifying-glass ml-3.5 shrink-0 text-[12px] text-admin-ink-soft"></i>

                    <input
                        type="search"
                        name="search"
                        value="{{ $currentSearch }}"
                        placeholder="Cari produk..."
                        autocomplete="off"
                        class="min-w-0 flex-1 bg-transparent px-3 text-[13px] text-admin-ink placeholder:text-admin-ink-soft/80 focus:outline-none"
                    >
                </div>


                {{-- Dropdown kategori --}}
                <div class="relative hidden border-l border-admin-border lg:block">
                    <select
                        name="category"
                        onchange="this.form.submit()"
                        class="h-full w-31.25 cursor-pointer appearance-none bg-transparent px-3 pr-7 text-[12px] font-medium text-admin-ink-soft outline-none"
                        aria-label="Pilih kategori"
                    >
                        <option value="">Semua Produk</option>

                        @foreach ($navCategories as $category)
                            <option
                                value="{{ $category->slug }}"
                                {{ $currentCategory === $category->slug ? 'selected' : '' }}
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <i class="fa-solid fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-admin-ink-soft"></i>
                </div>


                {{-- Tombol search --}}
                <button
                    type="submit"
                    aria-label="Cari produk"
                    class="flex w-11 shrink-0 items-center justify-center bg-admin-panel text-white transition-colors duration-200 hover:bg-admin-panel/90"
                >
                    <i class="fa-solid fa-magnifying-glass text-[12px]"></i>
                </button>

            </form>


            {{-- =====================================================
                 IKON FAVORIT / KERANJANG / ADMIN
            ====================================================== --}}
            <div class="flex shrink-0 items-center">

                {{-- Favorit --}}
                <a
                    href="{{ route('favorites.index') }}"
                    aria-label="Favorit"
                    class="group relative flex h-10 w-10 items-center justify-center rounded-full text-admin-ink-soft transition-all duration-200 hover:bg-admin-canvas hover:text-admin-accent"
                >
                    <i class="fa-regular fa-heart text-[16px] transition-transform duration-200 group-hover:scale-110"></i>

                    <span
                        data-favorite-count
                        class="absolute right-0 top-0 hidden min-h-4 min-w-4 items-center justify-center rounded-full bg-admin-accent px-1 text-[9px] font-bold leading-none text-white shadow-sm"
                    >0</span>
                </a>


                {{-- Keranjang --}}
                <a
                    href="{{ route('cart.index') }}"
                    aria-label="Keranjang"
                    class="group relative flex h-10 w-10 items-center justify-center rounded-full text-admin-ink-soft transition-all duration-200 hover:bg-admin-canvas hover:text-admin-accent"
                >
                    <i class="fa-solid fa-bag-shopping text-[15px] transition-transform duration-200 group-hover:scale-110"></i>

                    <span
                        data-cart-count
                        class="absolute right-0 top-0 hidden min-h-4 min-w-4 items-center justify-center rounded-full bg-admin-accent px-1 text-[9px] font-bold leading-none text-white shadow-sm"
                    >0</span>
                </a>


                {{-- Notifikasi/admin hanya untuk pemilik --}}
                @auth
                    <div class="ml-0.5">
                        <livewire:notification-bell />
                    </div>
                @endauth

            </div>


            {{-- =====================================================
                 MOBILE MENU BUTTON
            ====================================================== --}}
            <button
                type="button"
                aria-label="Buka menu navigasi"
                aria-controls="navbar-mobile-menu"
                aria-expanded="false"
                onclick="
                    const menu = document.getElementById('navbar-mobile-menu');
                    const expanded = this.getAttribute('aria-expanded') === 'true';
                    menu.classList.toggle('hidden');
                    this.setAttribute('aria-expanded', String(!expanded));
                "
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-admin-ink-soft transition-colors hover:bg-admin-canvas hover:text-admin-ink lg:hidden"
            >
                <i class="fa-solid fa-bars text-[16px]"></i>
            </button>

        </div>
    </div>


    {{-- =============================================================
         MOBILE MENU
    ============================================================== --}}
    <div
        id="navbar-mobile-menu"
        class="hidden border-t border-admin-border bg-admin-surface lg:hidden"
    >

        <div class="mx-auto max-w-360 space-y-2 px-5 py-4 sm:px-7">

            {{-- Search mobile --}}
            <form
                action="{{ route('products.index') }}"
                method="GET"
                class="flex h-11 overflow-hidden rounded-xl border border-admin-border bg-admin-canvas"
            >
                <input
                    type="search"
                    name="search"
                    value="{{ $currentSearch }}"
                    placeholder="Cari produk..."
                    class="min-w-0 flex-1 bg-transparent px-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:outline-none"
                >

                <button
                    type="submit"
                    aria-label="Cari"
                    class="flex w-12 items-center justify-center bg-admin-panel text-white"
                >
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </button>
            </form>


            {{-- Menu --}}
            <div class="grid gap-1 sm:grid-cols-2">

                @foreach ($navMenu as $item)
                    @php
                        $isActive = $item['route'] && request()->routeIs($item['route']);
                        $href = $item['route'] ? route($item['route']) : '#';
                    @endphp

                    <a
                        href="{{ $href }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-colors
                        {{ $isActive
                            ? 'bg-admin-canvas text-admin-panel'
                            : 'text-admin-ink-soft hover:bg-admin-canvas hover:text-admin-ink' }}"
                    >
                        <i class="fa-solid {{ $item['icon'] }} w-5 text-center text-xs"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach

            </div>


            {{-- Koleksi pembeli --}}
            <div class="grid grid-cols-2 gap-2 pt-1">

                <a
                    href="{{ route('favorites.index') }}"
                    class="flex items-center justify-center gap-2 rounded-xl border border-admin-border px-4 py-3 text-xs font-semibold text-admin-ink-soft transition-colors hover:bg-admin-canvas hover:text-admin-ink"
                >
                    <i class="fa-regular fa-heart"></i>
                    Favorit
                </a>

                <a
                    href="{{ route('cart.index') }}"
                    class="flex items-center justify-center gap-2 rounded-xl border border-admin-border px-4 py-3 text-xs font-semibold text-admin-ink-soft transition-colors hover:bg-admin-canvas hover:text-admin-ink"
                >
                    <i class="fa-solid fa-bag-shopping"></i>
                    Keranjang
                </a>

            </div>

        </div>
    </div>

</header>