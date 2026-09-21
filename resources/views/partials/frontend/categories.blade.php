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
    - Klik kartu kategori -> langsung membuka halaman katalog Produk
      dengan filter kategori aktif (query string ?category=<slug>).

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

    // Warna latar section ini bisa diatur admin lewat Edit Web > Kategori
    // Produk (lihat pages/admin/edit-web.blade.php) -- data kategorinya
    // sendiri TIDAK diedit dari sana, ambil langsung dari database seperti
    // di atas. Data warna tersimpan di home_sections, section_key
    // 'kategori'. Pola sama persis dengan partials/frontend/products.blade.php.
    $kategoriSection = \App\Models\HomeSection::dataFor('kategori', ['bg_color' => null]);
    // Latar: warna POLOS secara bawaan; gradasi hanya kalau admin menyalakannya di
    // Edit Web > Kategori Produk > Gradasi (lihat App\Support\FrameBackground).
    $kategoriFrame = \App\Support\FrameBackground::resolve($kategoriSection['bg_color'] ?? null, $kategoriSection['bg_gradient'] ?? null, '#FEEDD8');
    $kategoriBgColor = $kategoriFrame['base'];

    // Judul, nama kategori & jumlah produk duduk LANGSUNG di atas warna
    // latar (tidak ada kartu putih di belakangnya seperti kartu produk),
    // jadi warnanya dihitung otomatis dari kontras latar -- pola sama
    // dengan $contrastProdukColors di products.blade.php. Warna latar
    // bawaan (peach lembut, belum diganti admin) selalu menghasilkan
    // warna coklat tua persis seperti sebelum section ini bisa diedit.
    $contrastKategoriColors = function (string $hex): array {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#4B3A26', 'title' => '#4B3A26', 'count' => 'rgba(75, 58, 38, 0.75)']
            : ['heading' => '#FFFFFF', 'title' => '#FFFFFF', 'count' => 'rgba(255, 255, 255, 0.75)'];
    };

    $kategoriColors = $contrastKategoriColors($kategoriBgColor);
@endphp

@if ($displayCategories->isNotEmpty())
    <section
        class="relative overflow-hidden"
        style="background: {{ $kategoriFrame['css'] }};"
    >
        {{-- Latar polos: tanpa lapisan glow/tekstur supaya warna pilihan admin tampil apa adanya. --}}

        <div class="relative mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-20">

            {{-- ============ Header: judul + subjudul ============ --}}
            <div class="max-w-xl">
                <span class="mb-3 block h-0.75 w-12 rounded-full bg-[#C97B5A]"></span>
                <h2 class="font-display text-3xl sm:text-4xl" style="color: {{ $kategoriColors['heading'] }};">Produk Berdasarkan Kategori</h2>
                <p class="mt-2 text-sm" style="color: {{ $kategoriColors['count'] }};">Telusuri koleksi kami berdasarkan kebutuhan ruang Anda.</p>
            </div>

            {{-- ============ Grid kategori ============ --}}
            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">
                @foreach ($displayCategories as $category)
                    <a
                        href="{{ route('products.index', ['category' => $category->slug]) }}"
                        class="group block"
                    >
                        {{--
                            "Frame": matting tipis di sekeliling foto (efek
                            bingkai foto galeri) -- warnanya diturunkan dari
                            $kategoriBgColor supaya tetap satu keluarga warna
                            dengan latar section, jadi kelihatan seperti satu
                            komposisi, bukan warna acak.
                        --}}
                        <div
                            class="relative aspect-4/5 w-full overflow-hidden rounded-[28px] p-2 transition-all duration-300 ease-out group-hover:-translate-y-1.5"
                            style="background: linear-gradient(160deg, color-mix(in oklab, {{ $kategoriBgColor }} 100%, white 45%) 0%, color-mix(in oklab, {{ $kategoriBgColor }} 100%, black 8%) 100%); box-shadow: 0 20px 34px -20px color-mix(in oklab, {{ $kategoriBgColor }} 100%, black 55%);"
                        >
                            <div class="h-full w-full overflow-hidden rounded-3xl shadow-inner">
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
                            </div>

                            {{-- Badge ikon kanan atas --}}
                            <span class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-[#4B3A26] shadow-md backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                <x-icon-arrow direction="external" />
                            </span>
                        </div>

                        <p class="mt-4 text-base font-semibold" style="color: {{ $kategoriColors['title'] }};">{{ $category->name }}</p>
                        <p class="mt-0.5 text-sm" style="color: {{ $kategoriColors['count'] }};">{{ $category->products_count }} Produk</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif