@php
    $productNavSetting = \App\Models\Setting::current();

    $productNavMenu = [
        ['label' => 'Home', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Shop', 'href' => route('products.index'), 'active' => request()->routeIs('products.index')],
        ['label' => 'Blog', 'href' => '#', 'active' => false],
        ['label' => 'Vendors', 'href' => '#', 'active' => false],
        ['label' => 'Pages', 'href' => '#', 'active' => false],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-[#E8D9C6] bg-[#FEEDD8]/95 backdrop-blur">
    <div class="mx-auto flex min-h-[68px] max-w-[1180px] items-center gap-6 px-5 sm:px-7 lg:px-8">

        {{-- Brand --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
            @include('partials.logo', [
                'boxSize' => 'h-9 w-9',
                'rounded' => 'rounded-full',
                'boxClass' => 'bg-[#2A1B12]',
                'iconClass' => 'text-sm text-white',
                'icon' => 'fa-couch',
            ])
            <span class="hidden font-display text-base font-semibold tracking-tight text-[#2A211B] md:block">
                {{ $productNavSetting->site_name }}
            </span>
        </a>

        {{-- Main navigation --}}
        <nav class="hidden items-center gap-5 lg:flex">
            @foreach ($productNavMenu as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="relative py-2 text-[12px] font-medium transition-colors duration-200 {{ $item['active'] ? 'text-[#2A211B]' : 'text-[#786B5D] hover:text-[#2A211B]' }}"
                >
                    {{ $item['label'] }}
                    @if ($item['active'])
                        <span class="absolute inset-x-0 -bottom-0.5 mx-auto h-px w-5 bg-[#9C6B3F]"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- Search --}}
        <form
            action="{{ route('products.index') }}"
            method="GET"
            class="ml-auto hidden h-9 min-w-0 max-w-[360px] flex-1 items-stretch overflow-hidden rounded-md border border-[#E5D8C8] bg-white/80 md:flex"
        >
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search..."
                class="min-w-0 flex-1 bg-transparent px-3 text-[11px] text-[#2A211B] placeholder:text-[#9B8D7E] outline-none"
            >
            <select
                name="category"
                class="w-[112px] border-l border-[#E5D8C8] bg-transparent px-2 text-[10px] text-[#75685B] outline-none"
            >
                <option value="">All Category</option>
                @foreach (\App\Models\Category::active()->ordered()->get(['slug', 'name']) as $category)
                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button
                type="submit"
                aria-label="Search"
                class="flex w-10 shrink-0 items-center justify-center bg-[#211B17] text-white transition-colors hover:bg-[#3A2E27]"
            >
                <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
            </button>
        </form>

        {{-- Account / wishlist / bag --}}
        <div class="hidden shrink-0 items-center gap-3 text-[#4D433A] md:flex">
            <a href="{{ route('admin.login') }}" class="inline-flex items-center gap-1.5 text-[11px] font-medium hover:text-[#9C6B3F]">
                <i class="fa-solid fa-arrow-right-to-bracket text-[11px]"></i>
                Login
            </a>
            <button type="button" aria-label="Wishlist" class="text-[13px] transition-colors hover:text-[#9C6B3F]">
                <i class="fa-regular fa-heart"></i>
            </button>
            <button type="button" aria-label="Bag" class="text-[13px] transition-colors hover:text-[#9C6B3F]">
                <i class="fa-solid fa-bag-shopping"></i>
            </button>
        </div>

        {{-- Mobile menu --}}
        <button
            type="button"
            aria-label="Buka menu"
            class="ml-auto flex h-9 w-9 items-center justify-center rounded-full text-[#4D433A] md:hidden"
            onclick="document.getElementById('shop-mobile-menu').classList.toggle('hidden')"
        >
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <div id="shop-mobile-menu" class="hidden border-t border-[#E8D9C6] bg-[#FEEDD8] px-5 py-3 md:hidden">
        <div class="grid grid-cols-2 gap-1">
            @foreach ($productNavMenu as $item)
                <a href="{{ $item['href'] }}" class="rounded-md px-3 py-2 text-xs {{ $item['active'] ? 'bg-white/70 font-semibold text-[#2A211B]' : 'text-[#786B5D]' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        <form action="{{ route('products.index') }}" method="GET" class="mt-2 flex overflow-hidden rounded-md border border-[#E5D8C8] bg-white">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search..." class="min-w-0 flex-1 px-3 py-2 text-xs outline-none">
            <button class="w-10 bg-[#211B17] text-white"><i class="fa-solid fa-magnifying-glass text-xs"></i></button>
        </form>
    </div>
</header>
