{{--
    ==========================================================
    HERO SECTION — Homepage Karya Ide Edi
    ==========================================================
    Ini section "Header" yang bisa diedit admin lewat
    Admin > Edit Web > Header (lihat pages/admin/edit-web.blade.php).
    Data tersimpan di tabel home_sections dengan section_key = 'header'
    (App\Models\HomeSection). Yang BISA diedit admin: warna latar, kata
    pembuka judul, tagline, deskripsi, 3 statistik, dan foto (posisi
    diatur lewat cropper drag+zoom, sama seperti Produk/Kategori/
    Pengaturan). Warna judul/deskripsi/statistik BUKAN field terpisah --
    otomatis dihitung dari kontras warna latar (lihat $contrastTextColors
    di bawah), supaya tidak pernah bertabrakan dengan warna latar apa pun
    yang dipilih admin.

    Yang TIDAK bisa diedit dari admin (sengaja hardcode, sesuai aturan
    "tombol tidak boleh diedit"):
    - Label & link tombol CTA ("Lihat Katalog" -> products.index,
      "Jelajahi Profil" -> profile.index).
    - Nama toko (ambil dari App\Models\Setting, diatur lewat menu
      Pengaturan, bukan di sini).

    Pemakaian:
        @include('partials.frontend.hero')
    ==========================================================
--}}
@php
    $heroSetting = \App\Models\Setting::current();

    $headerSection = \App\Models\HomeSection::dataFor('header', [
        'bg_color' => null,
        'headline_prefix' => 'Furnitur',
        'tagline' => 'Menghidupkan Setiap Sudut',
        'description' => 'Setiap karya dibuat dengan tangan menggunakan material pilihan berkualitas tinggi, dirancang secara teliti dan detail untuk mempercantik interior Anda.',
        'stats' => [
            ['value' => '500+', 'label' => 'Pelanggan Puas'],
            ['value' => '2.000+', 'label' => 'Karya Produk'],
            ['value' => '12th', 'label' => 'Pengalaman'],
        ],
        'image_path' => null,
    ]);

    $heroBgColor = $headerSection['bg_color'] ?: '#F9F7F2';

    /**
     * Warna judul/deskripsi/statistik & garis pembatas TIDAK diatur manual --
     * dihitung otomatis dari kontras warna latar ($heroBgColor), pakai rumus
     * relative luminance (WCAG), supaya teks selalu kebaca & tidak
     * "bertabrakan" dengan warna latar apa pun yang dipilih admin.
     * Warna latar bawaan (belum diganti admin) selalu menghasilkan palet
     * default toko (coklat #3D2B1F) persis seperti sebelumnya.
     */
    $contrastTextColors = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'F9F7F2') {
            return [
                'heading' => '#3D2B1F',
                'body' => '#6B6E76',
                'divider' => 'rgba(61, 43, 31, 0.1)',
                // Sama persis dengan border tombol "Jelajahi Profil" bawaan
                // sebelumnya (#DCDDD7) -- supaya tampilan default tidak berubah.
                'border' => '#DCDDD7',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)', 'divider' => 'rgba(26, 18, 8, 0.12)', 'border' => 'rgba(26, 18, 8, 0.22)']
            : ['heading' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.78)', 'divider' => 'rgba(255, 255, 255, 0.18)', 'border' => 'rgba(255, 255, 255, 0.35)'];
    };

    $heroTextColors = $contrastTextColors($heroBgColor);
    $heroHeadingColor = $heroTextColors['heading'];
    $heroTextColor = $heroTextColors['body'];
    $heroDividerColor = $heroTextColors['divider'];
    // Border tombol outline "Jelajahi Profil" -- dulu hardcode #DCDDD7 sehingga
    // teks & garisnya "hilang" (nyaris tak terlihat) kalau admin pilih warna
    // latar gelap (mis. Coklat Kayu Tua / Navy Malam / Hitam Elegan), karena
    // teks tombol tetap coklat tua di atas latar gelap. Sekarang ikut kontras.
    $heroBorderColor = $heroTextColors['border'];

    $heroImageUrl = $headerSection['image_path']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($headerSection['image_path'])
        : asset('images/admin-login/hero.png');
@endphp

<section
    class="relative overflow-hidden"
    style="background: linear-gradient(135deg, color-mix(in oklab, {{ $heroBgColor }} 100%, white 8%) 0%, {{ $heroBgColor }} 55%, color-mix(in oklab, {{ $heroBgColor }} 100%, black 12%) 100%);"
>
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-6 py-8 sm:px-8 lg:grid-cols-2 lg:gap-8 lg:px-10 lg:py-10">

        {{-- ============ KIRI: Teks, CTA, Statistik ============ --}}
        <div class="animate-fade-in-up">
            <h1 class="font-display text-3xl leading-[1.15] sm:text-4xl lg:text-[2.5rem] xl:text-[2.75rem]" style="color: {{ $heroHeadingColor }};">
                <span class="font-semibold">{{ $headerSection['headline_prefix'] }}</span><br>
                <span class="font-semibold">{{ $heroSetting->site_name }}</span><br>
                <span class="font-normal">{{ $headerSection['tagline'] }}</span>
            </h1>

            <p class="mt-6 max-w-md text-sm leading-relaxed" style="color: {{ $heroTextColor }};">
                {{ $headerSection['description'] }}
            </p>

            {{-- Statistik --}}
            <dl class="mt-5 flex flex-wrap gap-x-10 gap-y-4 border-t pt-4" style="border-color: {{ $heroDividerColor }};">
                @foreach ($headerSection['stats'] as $stat)
                    <div>
                        <dt class="font-display text-xl sm:text-2xl" style="color: {{ $heroHeadingColor }};">{{ $stat['value'] }}</dt>
                        <dd class="mt-1 text-xs font-medium uppercase tracking-wide text-[#8A8880]">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- CTA — TIDAK BISA DIEDIT ADMIN, sengaja hardcode. --}}
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('products.index') }}"
                    class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                >
                    Lihat Katalog
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
                <a
                    href="{{ route('profile.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border px-5 py-2.5 text-sm font-medium transition-all duration-300 hover:!border-admin-accent hover:!text-admin-accent"
                    style="color: {{ $heroHeadingColor }}; border-color: {{ $heroBorderColor }};"
                >
                    Jelajahi Tentang Kami
                </a>
            </div>
        </div>

        {{-- ============ KANAN: Foto ============ --}}
        <div class="relative flex items-center justify-center">
            {{--
                Foto Header — diatur admin lewat Edit Web (cropper drag+zoom,
                rasio 10:9, sama seperti Produk/Kategori). Sebelum admin
                pernah mengganti foto, pakai foto bawaan
                public/images/admin-login/hero.png.
            --}}
            <span class="relative flex aspect-4/3 w-full items-center justify-center overflow-hidden rounded-[28px] bg-[#D7A26E] shadow-xl shadow-black/10">
                <img
                    src="{{ $heroImageUrl }}"
                    alt="Furniture {{ $heroSetting->site_name }}"
                    class="h-full w-full object-cover"
                >
            </span>
        </div>
    </div>
</section>