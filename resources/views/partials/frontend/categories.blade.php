{{--
    ==========================================================
    PRODUK BERDASARKAN KATEGORI (FEATURED) — Homepage Karya Ide Edi
    ==========================================================
    Sepenuhnya dinamis dari tabel `categories` (data master yang
    dikelola admin lewat menu "Kategori").

    - Hanya kategori is_active = true yang tampil.
    - Featured = 4 kategori TERATAS berdasarkan urutan drag & drop
      admin (kolom sort_order). Tidak ada checkbox "featured"
      terpisah — posisi 1-4 di drag & drop OTOMATIS jadi featured.
    - Kategori aktif TIDAK mensyaratkan sudah punya produk supaya
      tetap muncul di sini (sebelumnya ada filter having() yang
      menyembunyikan kategori tanpa produk — itu penyebab kenapa
      banyak kategori aktif tidak pernah muncul walau sudah
      diaktifkan admin; filter itu sudah dihapus).
    - COVER: diambil dari kolom `cover` milik kategori (diunggah
      admin lewat cropper di form Kategori) — BUKAN dari foto
      produk. Kalau admin belum unggah cover, dipakai placeholder
      ikon.
    - JUMLAH PRODUK dihitung otomatis (withCount, hanya produk
      berstatus "aktif") — ikut berubah begitu admin tambah/edit/
      hapus/ubah status produk, tanpa perlu ubah kode di sini.
    - Klik kartu kategori -> mengarah ke section "Semua Produk" di
      halaman ini juga (route('home')) dengan query string
      ?kategori=<slug-kategori>, lalu discroll ke #produk.

    Pemakaian:
        @include('partials.frontend.categories')
    ==========================================================
--}}

@php
    $displayCategories = \App\Models\Category::query()
        ->active()
        ->withCount(['products' => fn ($query) => $query->where('status', 'aktif')])
        ->ordered()
        ->take(4)
        ->get();
@endphp

@if ($displayCategories->isNotEmpty())
    <section class="bg-[#FEEDD8]">
        <div class="mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-20">

            {{-- ============ Header: judul ============ --}}
            <div class="flex flex-wrap items-end justify-between gap-6">
                <h2 class="font-display text-2xl text-[#4B3A26] sm:text-3xl">Produk Berdasarkan Kategori</h2>
            </div>

            {{-- ============ Grid kategori ============ --}}
            <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($displayCategories as $category)
                    <a
                        href="{{ route('home', ['kategori' => $category->slug]) }}#produk"
                        class="group block"
                    >
                        <div class="relative aspect-4/5 w-full overflow-hidden rounded-3xl shadow-sm transition-all duration-300 ease-out group-hover:-translate-y-1.5 group-hover:shadow-xl">
                            @if ($category->coverUrl())
                                <img
                                    src="{{ $category->coverUrl() }}"
                                    alt="Kategori {{ $category->name }}"
                                    class="h-full w-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-105"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-admin-cream text-admin-ink-soft/40">
                                    <i class="fa-solid fa-tags text-4xl"></i>
                                </div>
                            @endif

                            {{-- Badge ikon kanan atas --}}
                            <span class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/85 text-[#4B3A26] shadow-sm backdrop-blur-sm">
                                <x-icon-arrow direction="external" />
                            </span>
                        </div>

                        <p class="mt-4 text-base font-semibold text-[#4B3A26]">{{ $category->name }}</p>
                        <p class="mt-0.5 text-sm text-admin-ink-soft">{{ $category->products_count }} Produk</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
