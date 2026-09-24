# apply-fix-warna-teks-ikut-frame.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-fix-warna-teks-ikut-frame.ps1
#
# MASALAH:
#   Kalau admin pilih warna frame/latar lain (mis. Coklat Kayu Tua, Navy
#   Malam, Hitam Elegan) di Edit Web, sebagian teks TIDAK ikut menyesuaikan
#   -- tetap pakai warna coklat tua bawaan, jadi nyaris tidak kelihatan di
#   atas latar gelap. Kebanyakan section (Header, Sejak Berdiri, Produk
#   Unggulan, Kategori, Lokasi, Tentang Kami, Sejarah, Nilai Kami, Why
#   Choose Us) SUDAH BENAR -- sudah punya perhitungan kontras otomatis
#   (rumus WCAG relative luminance) yang dipakai di seluruh project ini.
#   2 tempat yang KETINGGALAN pola itu:
#
#   1) resources/views/pages/frontend/produk-index.blade.php
#      Halaman Katalog Produk (/produk) -- SEMUA teks di section "Filter
#      Options" & daftar produk (judul, label kategori, checkbox, "Showing
#      X of Y", "Sort by", "Active Filter", chip filter, "Clear All",
#      pesan kosong, judul/harga/kategori tiap kartu produk, garis
#      pembatas) masih hardcode warna coklat tua, tidak ikut warna yang
#      dipilih admin di Edit Web > Produk > Warna. Ini yang kelihatan di
#      screenshot (latar navy tapi teks tetap gelap, jadi hilang).
#
#   2) resources/views/partials/frontend/testimonials.blade.php
#      Judul "Ulasan Pelanggan Kami" (section Testimoni Pelanggan di
#      Beranda) masih hardcode warna gelap (text-admin-ink), padahal
#      latar section ini bisa diganti admin. Kartu Top 1/2/3 di
#      dalamnya sendiri SUDAH benar (sudah ikut kontras), cuma judulnya
#      yang ketinggalan.
#
# PERBAIKAN: menambah fungsi hitung kontras (rumus & pola PERSIS sama
# dengan $contrastMissionColors/$contrastTextColors yang sudah dipakai di
# file lain), lalu memakainya lewat atribut style="color: ...". Kalau
# warna latar MASIH warna bawaan (belum pernah diganti admin), fungsi ini
# mengembalikan warna PERSIS sama dengan yang sudah hardcode sekarang --
# jadi tampilan default TIDAK BERUBAH SAMA SEKALI. Baru kalau admin ganti
# warna latar, teks otomatis ikut kontras (putih di latar gelap, coklat
# tua di latar terang).
#
# Tidak ada logic PHP/data lain yang diubah, tidak ada bagian yang sudah
# benar (Header/Sejak Berdiri/Produk Unggulan/Kategori/Lokasi/Tentang
# Kami/Sejarah/Nilai Kami/Why Choose Us/kartu Testimoni) yang disentuh.
# Pola dicek harus cocok PERSIS 1 kali sebelum diganti, backup dibuat
# lebih dulu untuk tiap file, aman dijalankan berulang (dilewati kalau
# sudah pernah dipatch).

$ErrorActionPreference = "Stop"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

function Read-TextFile($path) {
    if (-not (Test-Path $path)) { throw "Tidak ketemu: $path (jalankan script ini dari root project, folder yang ada file 'artisan')" }
    $full  = (Resolve-Path $path).Path
    $bytes = [System.IO.File]::ReadAllBytes($full)
    $hasBom = ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)
    $text  = [System.IO.File]::ReadAllText($full, (New-Object System.Text.UTF8Encoding($false)))
    return @{ Full = $full; HasBom = $hasBom; Text = $text }
}

$patchedFiles = @{}

function Apply-Fix($relPath, $label, $old, $new, $backupSuffix) {
    Write-Host "-> $label" -ForegroundColor Cyan

    if ($patchedFiles.ContainsKey($relPath)) {
        $text = $patchedFiles[$relPath].Text
    } else {
        $f = Read-TextFile $relPath
        $patchedFiles[$relPath] = $f
        $text = $f.Text
    }

    $idx = $text.IndexOf($old)
    $idxLast = $text.LastIndexOf($old)
    if ($idx -lt 0) {
        if ($text.IndexOf($new) -ge 0) {
            Write-Host "   Sudah dalam kondisi yang diperbaiki, dilewati." -ForegroundColor Yellow
            return
        }
        throw "Pola lama tidak ditemukan persis di $relPath. Kemungkinan file sudah diubah manual sebelumnya -- cek manual, tidak ada yang ditimpa."
    }
    if ($idx -ne $idxLast) {
        throw "Pola lama ditemukan LEBIH dari 1 kali di $relPath -- tidak aman diganti otomatis, cek manual, tidak ada yang ditimpa."
    }

    if (-not (Test-Path "$relPath.bak-$backupSuffix-$stamp")) {
        Copy-Item $relPath "$relPath.bak-$backupSuffix-$stamp"
    }

    $patchedFiles[$relPath].Text = $text.Replace($old, $new)
    Write-Host "   OK (disimpan di akhir, sekaligus dengan patch lain di file yang sama)." -ForegroundColor Green
}

function Save-PatchedFiles {
    foreach ($relPath in $patchedFiles.Keys) {
        $f = $patchedFiles[$relPath]
        [System.IO.File]::WriteAllText($f.Full, $f.Text, (New-Object System.Text.UTF8Encoding($f.HasBom)))
        Write-Host "Tersimpan: $relPath" -ForegroundColor Green
    }
}

# ============================================================
# FILE 1: resources/views/pages/frontend/produk-index.blade.php
# ============================================================
$produkIndex = "resources/views/pages/frontend/produk-index.blade.php"

# 1a) Tambah 2 fungsi kontras (hero/breadcrumb & konten Filter+Produk)
#     tepat setelah 2 warna latar diambil.
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: tambah fungsi kontras warna teks (hero & konten)" `
    -old @'
    $produkWarnaData = \App\Models\HomeSection::dataFor('produk', ['bg_color' => null, 'bg_color_hero' => null]);
    $produkWarnaHeroBg = $produkWarnaData['bg_color_hero'] ?? '#F6F9F6';
    $produkWarnaBg = $produkWarnaData['bg_color'] ?? '#FEEDD8';
@endphp
'@ `
    -new @'
    $produkWarnaData = \App\Models\HomeSection::dataFor('produk', ['bg_color' => null, 'bg_color_hero' => null]);
    $produkWarnaHeroBg = $produkWarnaData['bg_color_hero'] ?? '#F6F9F6';
    $produkWarnaBg = $produkWarnaData['bg_color'] ?? '#FEEDD8';

    // Warna teks/garis di section HERO (judul "Produk" + breadcrumb) TIDAK
    // diatur manual -- dihitung otomatis dari kontras $produkWarnaHeroBg,
    // pola sama persis dengan $contrastTextColors di partials/frontend/
    // hero.blade.php. Warna latar bawaan (belum diganti admin, #F6F9F6)
    // selalu menghasilkan warna PERSIS sama dengan sebelum section ini
    // bisa diberi warna sendiri.
    $produkHeroKontras = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'F6F9F6') {
            return [
                'heading' => '#171717',
                'soft' => '#A29587',
                'strong' => '#2A211B',
                'dot' => '#C7B6A3',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? ['heading' => '#1A1208', 'soft' => 'rgba(26, 18, 8, 0.6)', 'strong' => '#1A1208', 'dot' => 'rgba(26, 18, 8, 0.35)']
            : ['heading' => '#FFFFFF', 'soft' => 'rgba(255, 255, 255, 0.65)', 'strong' => '#FFFFFF', 'dot' => 'rgba(255, 255, 255, 0.4)'];
    };

    $produkHeroWarna = $produkHeroKontras($produkWarnaHeroBg);

    // Warna teks/garis di section KONTEN (Filter Options + daftar produk)
    // TIDAK diatur manual -- dihitung otomatis dari kontras $produkWarnaBg,
    // pola sama persis dengan $contrastMissionColors di partials/frontend/
    // mission.blade.php. Warna latar bawaan (belum diganti admin, #FEEDD8)
    // selalu menghasilkan warna PERSIS sama dengan sebelum section ini
    // bisa diberi warna sendiri.
    $produkKontenKontras = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'FEEDD8') {
            return [
                'heading' => '#2A211B',
                'body' => '#75685B',
                'muted' => '#8D7E6F',
                'faint' => '#A09384',
                'border' => '#E7D9C8',
                'checkboxBorder' => '#B9A995',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5
            ? [
                'heading' => '#1A1208',
                'body' => 'rgba(26, 18, 8, 0.72)',
                'muted' => 'rgba(26, 18, 8, 0.58)',
                'faint' => 'rgba(26, 18, 8, 0.45)',
                'border' => 'rgba(26, 18, 8, 0.16)',
                'checkboxBorder' => 'rgba(26, 18, 8, 0.35)',
            ]
            : [
                'heading' => '#FFFFFF',
                'body' => 'rgba(255, 255, 255, 0.78)',
                'muted' => 'rgba(255, 255, 255, 0.65)',
                'faint' => 'rgba(255, 255, 255, 0.5)',
                'border' => 'rgba(255, 255, 255, 0.2)',
                'checkboxBorder' => 'rgba(255, 255, 255, 0.4)',
            ];
    };

    $produkKontenWarna = $produkKontenKontras($produkWarnaBg);
@endphp
'@ `
    -backupSuffix "warna-kontras-fungsi"

# 1b) Titik dekoratif kiri (sebelum judul "Produk")
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: titik dekoratif kiri ikut kontras" `
    -old @'
            <div class="hidden shrink-0 -translate-y-3 grid-cols-2 gap-1 sm:grid">
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
            </div>
'@ `
    -new @'
            <div class="hidden shrink-0 -translate-y-3 grid-cols-2 gap-1 sm:grid">
                <span class="h-1 w-1 rounded-full" style="background-color: {{ $produkHeroWarna['dot'] }};"></span>
                <span class="h-1 w-1 rounded-full" style="background-color: {{ $produkHeroWarna['dot'] }};"></span>
                <span class="h-1 w-1 rounded-full" style="background-color: {{ $produkHeroWarna['dot'] }};"></span>
            </div>
'@ `
    -backupSuffix "dot-kiri"

# 1c) Judul "Produk" + breadcrumb (Home / Produk)
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: judul & breadcrumb ikut kontras" `
    -old @'
                <h1 class="font-display text-4xl font-semibold tracking-tight text-[#171717] sm:text-5xl">Produk</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px] text-[#A29587]">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium text-[#2A211B]">Produk</span>
                </div>
'@ `
    -new @'
                <h1 class="font-display text-4xl font-semibold tracking-tight text-[#171717] sm:text-5xl" style="color: {{ $produkHeroWarna['heading'] }};">Produk</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px] text-[#A29587]" style="color: {{ $produkHeroWarna['soft'] }};">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium text-[#2A211B]" style="color: {{ $produkHeroWarna['strong'] }};">Produk</span>
                </div>
'@ `
    -backupSuffix "judul-breadcrumb"

# 1d) Titik dekoratif kanan (setelah judul "Produk")
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: titik dekoratif kanan ikut kontras" `
    -old @'
            <div class="hidden shrink-0 translate-y-3 grid-cols-2 gap-1 sm:grid">
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
                <span class="h-1 w-1 rounded-full bg-[#C7B6A3]"></span>
            </div>
'@ `
    -new @'
            <div class="hidden shrink-0 translate-y-3 grid-cols-2 gap-1 sm:grid">
                <span class="h-1 w-1 rounded-full" style="background-color: {{ $produkHeroWarna['dot'] }};"></span>
                <span class="h-1 w-1 rounded-full" style="background-color: {{ $produkHeroWarna['dot'] }};"></span>
                <span class="h-1 w-1 rounded-full" style="background-color: {{ $produkHeroWarna['dot'] }};"></span>
            </div>
'@ `
    -backupSuffix "dot-kanan"

# 1e) "Filter Options" + "Category"
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: judul Filter Options & Category ikut kontras" `
    -old @'
                        <div class="flex items-center justify-between lg:block">
                            <h2 class="text-sm font-semibold text-[#2A211B]">Filter Options</h2>
                        </div>

                        <div class="mt-8">
                            <p class="text-xs font-semibold text-[#2A211B]">Category</p>
'@ `
    -new @'
                        <div class="flex items-center justify-between lg:block">
                            <h2 class="text-sm font-semibold text-[#2A211B]" style="color: {{ $produkKontenWarna['heading'] }};">Filter Options</h2>
                        </div>

                        <div class="mt-8">
                            <p class="text-xs font-semibold text-[#2A211B]" style="color: {{ $produkKontenWarna['heading'] }};">Category</p>
'@ `
    -backupSuffix "filter-options"

# 1f) Label & border checkbox kategori
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: label & border checkbox kategori ikut kontras" `
    -old @'
                                    <label class="flex cursor-pointer items-center gap-2 text-[11px] text-[#75685B] transition-colors hover:text-[#2A211B]">
                                        <input
                                            type="checkbox"
                                            name="categories[]"
                                            value="{{ $category->slug }}"
                                            @checked(in_array($category->slug, (array) request('categories', []), true) || request('category') === $category->slug)
                                            class="h-3 w-3 rounded-sm border-[#B9A995] text-[#2A211B] accent-[#2A211B] focus:ring-0"
                                        >
'@ `
    -new @'
                                    <label class="flex cursor-pointer items-center gap-2 text-[11px] text-[#75685B] transition-colors hover:text-[#2A211B]" style="color: {{ $produkKontenWarna['body'] }};">
                                        <input
                                            type="checkbox"
                                            name="categories[]"
                                            value="{{ $category->slug }}"
                                            @checked(in_array($category->slug, (array) request('categories', []), true) || request('category') === $category->slug)
                                            class="h-3 w-3 rounded-sm border-[#B9A995] text-[#2A211B] accent-[#2A211B] focus:ring-0"
                                            style="border-color: {{ $produkKontenWarna['checkboxBorder'] }}; accent-color: {{ $produkKontenWarna['heading'] }};"
                                        >
'@ `
    -backupSuffix "checkbox-kategori"

# 1g) "Showing X of Y results" + "Sort by"
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: Showing/Sort by ikut kontras" `
    -old @'
                        <div class="flex flex-col gap-5 border-b border-[#E7D9C8] pb-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-[10px] text-[#8D7E6F]">
                                Showing
                                <span class="font-medium text-[#2A211B]">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</span>
                                of
                                <span class="font-medium text-[#2A211B]">{{ $products->total() }}</span>
                                results
                            </p>

                            <label class="flex items-center gap-2 text-[10px] text-[#8D7E6F]">
                                <span>Sort by :</span>
                                <select name="sort" onchange="this.form.submit()" class="cursor-pointer border-0 bg-transparent py-1 pl-1 pr-5 text-[10px] font-medium text-[#2A211B] outline-none focus:ring-0">
'@ `
    -new @'
                        <div class="flex flex-col gap-5 border-b border-[#E7D9C8] pb-5 sm:flex-row sm:items-center sm:justify-between" style="border-color: {{ $produkKontenWarna['border'] }};">
                            <p class="text-[10px] text-[#8D7E6F]" style="color: {{ $produkKontenWarna['muted'] }};">
                                Showing
                                <span class="font-medium text-[#2A211B]" style="color: {{ $produkKontenWarna['heading'] }};">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</span>
                                of
                                <span class="font-medium text-[#2A211B]" style="color: {{ $produkKontenWarna['heading'] }};">{{ $products->total() }}</span>
                                results
                            </p>

                            <label class="flex items-center gap-2 text-[10px] text-[#8D7E6F]" style="color: {{ $produkKontenWarna['muted'] }};">
                                <span>Sort by :</span>
                                <select name="sort" onchange="this.form.submit()" class="cursor-pointer border-0 bg-transparent py-1 pl-1 pr-5 text-[10px] font-medium text-[#2A211B] outline-none focus:ring-0" style="color: {{ $produkKontenWarna['heading'] }};">
'@ `
    -backupSuffix "showing-sort"

# 1h) Label "Active Filter"
Apply-Fix `
    -relPath $produkIndex `
    -label 'Produk: label "Active Filter" ikut kontras' `
    -old @'
                            <span class="text-[10px] font-medium text-[#75685B]">Active Filter</span>
'@ `
    -new @'
                            <span class="text-[10px] font-medium text-[#75685B]" style="color: {{ $produkKontenWarna['body'] }};">Active Filter</span>
'@ `
    -backupSuffix "active-filter-label"

# 1i) Link "Clear All"
Apply-Fix `
    -relPath $produkIndex `
    -label 'Produk: link "Clear All" ikut kontras' `
    -old @'
                            <a href="{{ route('products.index') }}" class="ml-auto text-[10px] font-semibold text-[#4D433A] underline decoration-[#C7B6A3] underline-offset-4 transition hover:text-admin-accent">
'@ `
    -new @'
                            <a href="{{ route('products.index') }}" class="ml-auto text-[10px] font-semibold text-[#4D433A] underline decoration-[#C7B6A3] underline-offset-4 transition hover:text-admin-accent" style="color: {{ $produkKontenWarna['heading'] }}; text-decoration-color: {{ $produkKontenWarna['border'] }};">
'@ `
    -backupSuffix "clear-all"

# 1j) Pesan "Produk tidak ditemukan" (state kosong)
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: pesan hasil kosong ikut kontras" `
    -old @'
                                    <p class="mt-4 text-sm font-semibold text-[#2A211B]">Produk tidak ditemukan</p>
                                    <p class="mt-1 text-xs text-[#8D7E6F]">Coba ubah filter atau kata pencarian Anda.</p>
'@ `
    -new @'
                                    <p class="mt-4 text-sm font-semibold text-[#2A211B]" style="color: {{ $produkKontenWarna['heading'] }};">Produk tidak ditemukan</p>
                                    <p class="mt-1 text-xs text-[#8D7E6F]" style="color: {{ $produkKontenWarna['muted'] }};">Coba ubah filter atau kata pencarian Anda.</p>
'@ `
    -backupSuffix "hasil-kosong"

# 1k) Label kategori tiap kartu produk
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: label kategori tiap kartu ikut kontras" `
    -old @'
                                                    <p class="text-[9px] text-[#A09384]">{{ $product->category?->name ?? 'Furniture' }}</p>
'@ `
    -new @'
                                                    <p class="text-[9px] text-[#A09384]" style="color: {{ $produkKontenWarna['faint'] }};">{{ $product->category?->name ?? 'Furniture' }}</p>
'@ `
    -backupSuffix "kartu-kategori"

# 1l) Judul (nama) tiap kartu produk
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: nama produk tiap kartu ikut kontras" `
    -old @'
                                                <h3 class="mt-1 text-sm font-semibold text-[#2A211B] transition-colors group-hover:text-[#8C6A45]">
'@ `
    -new @'
                                                <h3 class="mt-1 text-sm font-semibold text-[#2A211B] transition-colors group-hover:text-[#8C6A45]" style="color: {{ $produkKontenWarna['heading'] }};">
'@ `
    -backupSuffix "kartu-nama"

# 1m) Harga & harga coret tiap kartu produk
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: harga tiap kartu ikut kontras" `
    -old @'
                                                <div class="mt-1.5 flex items-center gap-2">
                                                    <span class="text-sm font-semibold text-[#2A211B]">
                                                        Rp{{ number_format($displayPrice, 0, ',', '.') }}
                                                    </span>
                                                    @if ($hasDiscount)
                                                        <span class="text-[10px] text-[#A09384] line-through">
                                                            Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                </div>
'@ `
    -new @'
                                                <div class="mt-1.5 flex items-center gap-2">
                                                    <span class="text-sm font-semibold text-[#2A211B]" style="color: {{ $produkKontenWarna['heading'] }};">
                                                        Rp{{ number_format($displayPrice, 0, ',', '.') }}
                                                    </span>
                                                    @if ($hasDiscount)
                                                        <span class="text-[10px] text-[#A09384] line-through" style="color: {{ $produkKontenWarna['faint'] }};">
                                                            Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                </div>
'@ `
    -backupSuffix "kartu-harga"

# 1n) Garis pembatas di atas pagination
Apply-Fix `
    -relPath $produkIndex `
    -label "Produk: garis pembatas pagination ikut kontras" `
    -old @'
                            @if ($products->hasPages())
                                <div class="mt-12 border-t border-[#E7D9C8] pt-6">
'@ `
    -new @'
                            @if ($products->hasPages())
                                <div class="mt-12 border-t border-[#E7D9C8] pt-6" style="border-color: {{ $produkKontenWarna['border'] }};">
'@ `
    -backupSuffix "pagination-border"

# ============================================================
# FILE 2: resources/views/partials/frontend/testimonials.blade.php
# ============================================================
$testimonials = "resources/views/partials/frontend/testimonials.blade.php"

# 2a) Tambah fungsi kontras khusus judul section (kartu Top 1/2/3 di
#     bawahnya SUDAH benar lewat $isDark, tidak disentuh).
Apply-Fix `
    -relPath $testimonials `
    -label "Testimoni: tambah fungsi kontras judul section" `
    -old @'
    $testimoniFrame = \App\Support\FrameBackground::resolve($testimoniSection['bg_color'] ?? null, $testimoniSection['bg_gradient'] ?? null, '#FAF8F4');
    $testimoniBgColor = $testimoniFrame['base'];
'@ `
    -new @'
    $testimoniFrame = \App\Support\FrameBackground::resolve($testimoniSection['bg_color'] ?? null, $testimoniSection['bg_gradient'] ?? null, '#FAF8F4');
    $testimoniBgColor = $testimoniFrame['base'];

    // Warna judul "Ulasan Pelanggan Kami" TIDAK diatur manual -- dihitung
    // otomatis dari kontras $testimoniBgColor, pola sama persis dengan
    // $contrastMissionColors di partials/frontend/mission.blade.php. Kartu
    // Top 1/2/3 di bawah sudah punya kontrasnya sendiri lewat $isDark,
    // ini KHUSUS judul section yang duduk langsung di atas warna latar.
    // Warna latar bawaan (belum diganti admin, #FAF8F4) selalu
    // menghasilkan warna PERSIS sama dengan text-admin-ink sekarang.
    $contrastTestimoniHeading = function (string $hex): string {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'FAF8F4') {
            return '#221A14';
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        return $luminance > 0.5 ? '#1A1208' : '#FFFFFF';
    };

    $testimoniHeadingColor = $contrastTestimoniHeading($testimoniBgColor);
'@ `
    -backupSuffix "kontras-judul"

# 2b) Pakai warna itu di judul section
Apply-Fix `
    -relPath $testimonials `
    -label 'Testimoni: judul "Ulasan Pelanggan Kami" ikut kontras' `
    -old @'
        <h2 class="font-display text-2xl text-admin-ink sm:text-3xl">Ulasan Pelanggan Kami</h2>
'@ `
    -new @'
        <h2 class="font-display text-2xl text-admin-ink sm:text-3xl" style="color: {{ $testimoniHeadingColor }};">Ulasan Pelanggan Kami</h2>
'@ `
    -backupSuffix "judul-warna"

Save-PatchedFiles

Write-Host ""
Write-Host "Selesai. Cek tampilan di /produk dan Beranda (section Testimoni) dengan beberapa" -ForegroundColor Cyan
Write-Host "pilihan warna frame gelap & terang di Edit Web -- teks sekarang harus tetap" -ForegroundColor Cyan
Write-Host "kebaca di semua warna. Kalau frontend belum ikut berubah, jalankan 'npm run dev'" -ForegroundColor Cyan
Write-Host "atau 'npm run build' (perubahan ini murni Blade, bukan CSS/JS, jadi biasanya" -ForegroundColor Cyan
Write-Host "tidak perlu build ulang -- cukup refresh browser)." -ForegroundColor Cyan
