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

    // Warna latar section ini bisa diatur admin lewat Edit Web > Produk
    // Unggulan (lihat pages/admin/edit-web.blade.php) -- data produknya
    // sendiri TIDAK diedit dari sana, ambil langsung dari database seperti
    // di atas. Data warna tersimpan di home_sections, section_key
    // 'produk-unggulan'.
    $produkUnggulanSection = \App\Models\HomeSection::dataFor('produk-unggulan', ['bg_color' => null]);
    // Latar: warna POLOS secara bawaan; gradasi hanya kalau admin menyalakannya di
    // Edit Web > Produk Unggulan > Gradasi (lihat App\Support\FrameBackground).
    $produkUnggulanFrame = \App\Support\FrameBackground::resolve($produkUnggulanSection['bg_color'] ?? null, $produkUnggulanSection['bg_gradient'] ?? null, '#FFFFFF');
    $produkUnggulanBgColor = $produkUnggulanFrame['base'];

    // Warna judul & link "Lihat Semua Produk" TIDAK diatur manual --
    // dihitung otomatis dari kontras warna latar, pola sama dengan
    // $contrastTextColors di partials/frontend/hero.blade.php. Warna latar
    // bawaan (putih, belum diganti admin) selalu menghasilkan warna coklat
    // tua persis seperti sebelum section ini bisa diedit.
    // 'title'/'body'/'price' dipakai oleh partials/frontend/product-card.blade.php
    // (lewat $cardTextColors) supaya teks di dalam kartu produk -- judul,
    // deskripsi, rating, harga -- ikut menyesuaikan warna latar section ini,
    // sama seperti heading & link di atas. Sebelumnya kartu produk hardcode
    // warna coklat tua/hitam jadi nyaris tidak kelihatan kalau admin pilih
    // warna latar gelap.
    $contrastProdukColors = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'FFFFFF') {
            return [
                'heading' => '#4B3A26',
                'link' => '#4B3A26',
                // Sama persis dengan warna kartu produk bawaan sebelumnya
                // (judul #4B3A26, deskripsi admin-ink-soft ~#756A5D, harga
                // #1A1A1A) -- supaya tampilan default (belum diganti admin)
                // tidak berubah sama sekali.
                'title' => '#4B3A26',
                'body' => '#756A5D',
                'price' => '#1A1A1A',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'link' => '#1A1208', 'title' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'price' => '#1A1208']
            : ['heading' => '#FFFFFF', 'link' => '#FFFFFF', 'title' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.75)', 'price' => '#FFFFFF'];
    };

    $produkUnggulanColors = $contrastProdukColors($produkUnggulanBgColor);
@endphp

<section
    id="produk"
    class="relative scroll-mt-24 overflow-hidden"
    style="background: {{ $produkUnggulanFrame['css'] }};"
>
    {{-- Latar polos: tanpa lapisan glow/tekstur supaya warna pilihan admin tampil apa adanya. --}}

    <div class="relative mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-20">

        {{-- ============ Header: judul + subjudul (kiri), link (kanan) ============ --}}
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-xl">
                <span class="mb-3 block h-0.75 w-12 rounded-full bg-[#C97B5A]"></span>
                <h2 class="font-display text-3xl sm:text-4xl" style="color: {{ $produkUnggulanColors['heading'] }};">Semua Produk</h2>
                <p class="mt-2 text-sm" style="color: {{ $produkUnggulanColors['link'] }};">Karya terbaru yang paling banyak dilihat pelanggan kami.</p>
            </div>

            <a
                href="{{ route('products.index') }}"
                class="group inline-flex items-center gap-2 border-b pb-0.5 text-sm font-medium transition-colors duration-300 hover:border-admin-accent hover:text-admin-accent"
                style="color: {{ $produkUnggulanColors['link'] }}; border-color: color-mix(in oklab, {{ $produkUnggulanColors['link'] }} 30%, transparent);"
            >
                Lihat Semua Produk
                <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
            </a>
        </div>

        {{-- ============ Grid produk ============ --}}
        @if ($products->isEmpty())
            <p class="mt-10 text-sm" style="color: {{ $produkUnggulanColors['link'] }};">Belum ada produk yang tersedia saat ini.</p>
        @else
            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-2 sm:gap-8 lg:grid-cols-3">
                @foreach ($products as $product)
                    @include('partials.frontend.product-card', ['product' => $product, 'cardTextColors' => $produkUnggulanColors])
                @endforeach
            </div>
        @endif
    </div>
</section>