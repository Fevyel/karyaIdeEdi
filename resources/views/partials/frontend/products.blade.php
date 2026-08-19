{{--
    ==========================================================
    SEMUA PRODUK — Homepage Karya Ide Edi
    ==========================================================
    Heading + link "Lihat Semua Produk" di kanan, grid 3 kolom
    kartu produk.

    - Data produk (thumbnail, nama, deskripsi, harga, harga
      diskon, kategori) diambil LANGSUNG dari tabel `products`
      lewat model App\Models\Product — sinkron dengan menu
      "Produk" di Admin Panel. Hanya produk berstatus "aktif"
      yang ditampilkan, dibatasi maksimal 3 produk terbaru
      (preview di homepage — katalog lengkap ada di link
      "Lihat Semua Produk").
    - Kartu produk (thumbnail + rating) memakai partial reusable
      partials/frontend/product-card.blade.php, supaya thumbnail
      selalu object-cover penuh & rating selalu dihitung dari
      testimonial approved di database (tidak ada hardcode).
    - Filter pill kategori di bagian atas section SUDAH DIHAPUS
      dari tampilan (permintaan pemilik toko). Logic PHP untuk
      memfilter berdasarkan query string ?kategori=<slug> TETAP
      dipertahankan apa adanya — masih dipakai oleh kartu kategori
      di section "Produk Berdasarkan Kategori" (kategori.blade.php)
      yang link-nya mengarah ke sini. Kalau logic ini ikut dihapus,
      klik kartu kategori akan berhenti memfilter. Saat sedang
      difilter, batas 3 produk preview otomatis dilepas supaya
      semua produk di kategori itu tampil. Link "Lihat Semua Produk"
      MASIH belum berfungsi (href="#"), menunggu halaman katalog penuh.

    Pemakaian:
        @include('partials.frontend.products')
    ==========================================================
--}}

@php
    $activeCategorySlug = request()->query('kategori');

    $productCategories = \App\Models\Category::query()
        ->active()
        ->ordered()
        ->get();

    $activeCategory = $activeCategorySlug
        ? $productCategories->firstWhere('slug', $activeCategorySlug)
        : null;

    $products = \App\Models\Product::query()
        ->where('status', 'aktif')
        ->when($activeCategory, fn ($query) => $query->where('category_id', $activeCategory->id))
        ->withCount(['testimonials as approved_testimonials_count' => fn ($query) => $query->approved()])
        ->withAvg(['testimonials as average_rating' => fn ($query) => $query->approved()], 'rating')
        ->latest()
        ->when(! $activeCategory, fn ($query) => $query->take(3))
        ->get();
@endphp

<section id="produk" class="bg-white scroll-mt-24">
    <div class="mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-20">

        {{-- ============ Header: judul (kiri), link (kanan) ============ --}}
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <h2 class="font-display text-2xl text-[#4B3A26] sm:text-3xl">Semua Produk</h2>
            </div>

            <a
                href="{{ route('products.index') }}"
                class="group inline-flex items-center gap-2 border-b border-admin-ink/30 pb-0.5 text-sm font-medium text-[#4B3A26] transition-colors duration-300 hover:border-admin-accent hover:text-admin-accent"
            >
                Lihat Semua Produk
                <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
            </a>
        </div>

        {{-- ============ Grid produk ============ --}}
        @if ($products->isEmpty())
            <p class="mt-10 text-sm text-admin-ink-soft">Belum ada produk yang tersedia saat ini.</p>
        @else
            <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    @include('partials.frontend.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </div>
</section>
