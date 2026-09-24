# apply-frame-boundary-blend.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File apply-frame-boundary-blend.ps1
#
# Fitur: transisi warna halus (blend) di SETIAP batas antar frame yang bisa
# diedit di Admin > Edit Web (kecuali navbar & footer) -- Beranda, Tentang
# Kami, dan Pengrajin Kami. Sebelumnya, kalau dua frame bertetangga warnanya
# beda, sambungannya jadi garis tegas/patah.
#
# Cara kerja (lihat komentar lengkap di file baru
# resources/views/partials/frontend/frame-seam.blade.php):
# satu <div> tipis (120px) ditaruh PERSIS di antara dua <section>, isinya
# linear-gradient(warna section atas -> warna section bawah). z-index:1
# + posisi negative-margin bikin dia menutupi separuh bawah section atas
# & separuh atas section bawah TANPA menambah jarak/gap baru dan TANPA
# kepotong overflow-hidden section manapun.
#
# TIDAK ada warna/gradasi section yang diubah -- murni lapisan penghalus
# di sambungannya. Aman dijalankan walau lagi ada perubahan belum
# di-commit (script ini cuma insert baris baru, tidak menyentuh baris lain).
#
# File yang dibuat:
#   - resources/views/partials/frontend/frame-seam.blade.php   (BARU)
# File yang diedit (di-backup dulu ke *.bak-before-frame-seam-<timestamp>):
#   - resources/views/home-placeholder.blade.php   (Beranda, 9 sambungan)
#   - resources/views/pages/frontend/profil.blade.php   (Tentang Kami, 4 sambungan)
#   - resources/views/pages/frontend/pengrajin.blade.php   (Pengrajin Kami, 2 sambungan)

$ErrorActionPreference = "Stop"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"

function Backup-File($path) {
    Copy-Item $path "$path.bak-before-frame-seam-$stamp"
}

# ========================================================================
# 1) Partial baru: frame-seam.blade.php
# ========================================================================
$seamPath = "resources/views/partials/frontend/frame-seam.blade.php"
if (Test-Path $seamPath) {
    throw "File $seamPath sudah ada -- tidak ditimpa, cek manual (mungkin script ini sudah pernah dijalankan)."
}

$seamContent = @'
{{--
    ==========================================================
    FRAME SEAM — transisi halus warna antar frame Admin > Edit Web
    ==========================================================
    Tiap frame yang bisa diedit di Edit Web (Header, Sejak Berdiri,
    Produk Unggulan, Kategori, Testimoni, Lokasi, Tentang Kami,
    Sejarah, Nilai Kami, Kenapa Pilih Kami, Our Craftsmen Hero, dst)
    adalah <section> sendiri dengan background-nya sendiri (warna
    polos ATAU gradasi -- lihat App\Support\FrameBackground). Kalau
    dua frame bertetangga warnanya beda, sambungannya jadi garis
    tegas/patah.

    Partial ini ditaruh PERSIS DI ANTARA dua @include section (bukan
    DI DALAM salah satunya) dan me-render satu strip tipis berisi
    linear-gradient(warna section ATAS -> warna section BAWAH), yang
    menutupi separuh bawah section atas + separuh atas section bawah.
    Warna & gradasi section itu sendiri SAMA SEKALI TIDAK diubah --
    ini murni lapisan penghalus di sambungannya saja.

    Kenapa position:relative + z-index:1 (bukan negative margin
    polos taruh di dalam salah satu section): hampir semua section
    frame di project ini punya class "overflow-hidden" (buat
    membungkus dekorasi glow/blur internal). Kalau seam ini jadi
    ANAK dari salah satu section, bagian yang nongol keluar kotak
    section (lewat negative margin) bakal kepotong overflow-hidden
    itu. Makanya seam ini sengaja jadi SAUDARA (sibling) dari kedua
    section -- z-index:1 memastikan dia tetap tampil DI ATAS kedua
    section (yang z-index-nya auto/default) walau posisinya di HTML
    ada DI ANTARA keduanya, dan overflow-hidden section manapun
    tidak akan memotongnya lagi karena dia bukan anak dari section
    itu. margin-top & margin-bottom yang sama besar (negatif) bikin
    total tinggi yang ditambahkan ke alur halaman = 0, jadi seam ini
    TIDAK menambah jarak/gap baru antar section.

    Props:
      $from   -- hex warna dasar section DI ATAS seam ini (pakai
                 ['base'] dari App\Support\FrameBackground::resolve(),
                 supaya benar baik section itu lagi pakai gradasi
                 maupun warna polos/bawaan).
      $to     -- hex warna dasar section DI BAWAH seam ini.
      $height -- opsional, total tinggi strip dalam px (default 120px
                 -- 60px nongol ke section atas, 60px ke section bawah).

    Pemakaian -- taruh PERSIS di antara dua section, TIDAK di dalam
    keduanya (lihat home-placeholder.blade.php / profil.blade.php /
    pengrajin.blade.php untuk contoh lengkap):

        @include('partials.frontend.mission')
        @include('partials.frontend.frame-seam', ['from' => $seamMission, 'to' => $seamProduk])
        @include('partials.frontend.products')
    ==========================================================
--}}
@php
    $seamHeight = $height ?? 120;
    $seamHalf = $seamHeight / 2;
@endphp
<div
    class="pointer-events-none"
    style="position: relative; z-index: 1; height: {{ $seamHeight }}px; margin-top: -{{ $seamHalf }}px; margin-bottom: -{{ $seamHalf }}px; background: linear-gradient(to bottom, {{ $from }} 0%, {{ $to }} 100%);"
    aria-hidden="true"
></div>
'@

Set-Content -Path $seamPath -Value $seamContent -Encoding UTF8 -NoNewline
Write-Host "Dibuat: $seamPath"

# ========================================================================
# 2) Beranda -- home-placeholder.blade.php (9 sambungan)
# ========================================================================
$homePath = "resources/views/home-placeholder.blade.php"
if (-not (Test-Path $homePath)) { throw "Tidak ketemu: $homePath (jalankan script ini dari root project)" }

$homeView = (Get-Content $homePath -Raw -Encoding UTF8) -replace "`r`n", "`n"

$oldHomeBlock = @'
        {{-- ================= NAVBAR ================= --}}
        @php $siteSetting = \App\Models\Setting::current(); @endphp
        @include('partials.frontend.navbar')

        {{-- ================= HERO ================= --}}
        @include('partials.frontend.hero')

        {{-- ================= KEUNGGULAN ================= --}}
        @include('partials.frontend.features')

        {{-- ================= SEJAK BERDIRI ================= --}}
        @include('partials.frontend.mission')

        {{-- ================= SEMUA PRODUK ================= --}}
        @include('partials.frontend.products')

        {{-- ================= PRODUK BERDASARKAN KATEGORI ================= --}}
        @include('partials.frontend.categories')

        {{-- ================= ULASAN PELANGGAN KAMI ================= --}}
        @include('partials.frontend.testimonials')

        {{-- ================= KEUNGGULAN KAMI ================= --}}
        @include('partials.frontend.expertise')

        {{-- ================= ALUR BOOKING ================= --}}
        @include('partials.frontend.alur-booking')

        {{-- ================= FAQ ================= --}}
        @include('partials.frontend.faq')

        {{-- ================= LOKASI / ALAMAT ================= --}}
        @include('partials.frontend.lokasi')

        {{-- ================= FOOTER ================= --}}
        @include('partials.frontend.footer')
'@

if ($homeView.IndexOf($oldHomeBlock) -lt 0) {
    throw "Blok section Beranda tidak cocok persis (mungkin sudah pernah diubah). Tidak ada yang ditimpa, cek manual: $homePath"
}

$newHomeBlock = @'
        {{-- ================= NAVBAR ================= --}}
        @php $siteSetting = \App\Models\Setting::current(); @endphp
        @include('partials.frontend.navbar')

        {{--
            ================= WARNA UNTUK FRAME SEAM =================
            Dihitung di sini (bukan mengubah partial section manapun)
            supaya semua batas frame di Beranda (kecuali navbar &
            footer) bisa dikasih transisi halus lewat
            partials.frontend.frame-seam -- lihat komentar lengkap di
            file itu. Section, section_key, dan warna fallback di
            bawah SAMA PERSIS dengan yang dipakai section terkait
            sendiri (hero.blade.php, mission.blade.php, dst) --
            hanya diulang di sini buat ambil warna dasarnya saja,
            TIDAK menyentuh/menduplikasi logika tampilnya.
        --}}
        @php
            $seamHeaderSection = \App\Models\HomeSection::dataFor('header', ['bg_color' => null]);
            $seamHero = $seamHeaderSection['bg_color'] ?: '#F9F7F2';

            $seamFeatures = '#FFFFFF'; // features.blade.php: bg-admin-surface (frontend selalu tema terang)

            $seamMissionSection = \App\Models\HomeSection::dataFor('sejak-berdiri', ['bg_color' => null, 'bg_gradient' => null]);
            $seamMission = \App\Support\FrameBackground::resolve($seamMissionSection['bg_color'] ?? null, $seamMissionSection['bg_gradient'] ?? null, '#FEEDD8')['base'];

            $seamProdukSection = \App\Models\HomeSection::dataFor('produk-unggulan', ['bg_color' => null, 'bg_gradient' => null]);
            $seamProduk = \App\Support\FrameBackground::resolve($seamProdukSection['bg_color'] ?? null, $seamProdukSection['bg_gradient'] ?? null, '#FFFFFF')['base'];

            $seamKategoriSection = \App\Models\HomeSection::dataFor('kategori', ['bg_color' => null, 'bg_gradient' => null]);
            $seamKategori = \App\Support\FrameBackground::resolve($seamKategoriSection['bg_color'] ?? null, $seamKategoriSection['bg_gradient'] ?? null, '#FEEDD8')['base'];

            $seamTestimoniSection = \App\Models\HomeSection::dataFor('testimoni', ['bg_color' => null, 'bg_gradient' => null]);
            $seamTestimoni = \App\Support\FrameBackground::resolve($seamTestimoniSection['bg_color'] ?? null, $seamTestimoniSection['bg_gradient'] ?? null, '#FAF8F4')['base'];

            $seamExpertise = '#FFFFFF'; // expertise.blade.php: bg-white
            $seamAlurBooking = '#F7F4EF'; // alur-booking.blade.php: bg-[#F7F4EF]
            $seamFaq = '#FFFFFF'; // faq.blade.php: bg-white

            $seamLokasiSection = \App\Models\HomeSection::dataFor('lokasi', ['bg_color' => null, 'bg_gradient' => null]);
            $seamLokasi = \App\Support\FrameBackground::resolve($seamLokasiSection['bg_color'] ?? null, $seamLokasiSection['bg_gradient'] ?? null, '#FFFFFF')['base'];
        @endphp

        {{-- ================= HERO ================= --}}
        @include('partials.frontend.hero')

        @include('partials.frontend.frame-seam', ['from' => $seamHero, 'to' => $seamFeatures])

        {{-- ================= KEUNGGULAN ================= --}}
        @include('partials.frontend.features')

        @include('partials.frontend.frame-seam', ['from' => $seamFeatures, 'to' => $seamMission])

        {{-- ================= SEJAK BERDIRI ================= --}}
        @include('partials.frontend.mission')

        @include('partials.frontend.frame-seam', ['from' => $seamMission, 'to' => $seamProduk])

        {{-- ================= SEMUA PRODUK ================= --}}
        @include('partials.frontend.products')

        @include('partials.frontend.frame-seam', ['from' => $seamProduk, 'to' => $seamKategori])

        {{-- ================= PRODUK BERDASARKAN KATEGORI ================= --}}
        @include('partials.frontend.categories')

        @include('partials.frontend.frame-seam', ['from' => $seamKategori, 'to' => $seamTestimoni])

        {{-- ================= ULASAN PELANGGAN KAMI ================= --}}
        @include('partials.frontend.testimonials')

        @include('partials.frontend.frame-seam', ['from' => $seamTestimoni, 'to' => $seamExpertise])

        {{-- ================= KEUNGGULAN KAMI ================= --}}
        @include('partials.frontend.expertise')

        @include('partials.frontend.frame-seam', ['from' => $seamExpertise, 'to' => $seamAlurBooking])

        {{-- ================= ALUR BOOKING ================= --}}
        @include('partials.frontend.alur-booking')

        @include('partials.frontend.frame-seam', ['from' => $seamAlurBooking, 'to' => $seamFaq])

        {{-- ================= FAQ ================= --}}
        @include('partials.frontend.faq')

        @include('partials.frontend.frame-seam', ['from' => $seamFaq, 'to' => $seamLokasi])

        {{-- ================= LOKASI / ALAMAT ================= --}}
        @include('partials.frontend.lokasi')

        {{-- ================= FOOTER ================= --}}
        @include('partials.frontend.footer')
'@

Backup-File $homePath
$homeView = $homeView.Replace($oldHomeBlock, $newHomeBlock)
Set-Content -Path $homePath -Value $homeView -Encoding UTF8 -NoNewline
Write-Host "Diedit: $homePath (backup: $homePath.bak-before-frame-seam-$stamp)"

# ========================================================================
# 3) Tentang Kami -- pages/frontend/profil.blade.php (4 sambungan)
# ========================================================================
$profilPath = "resources/views/pages/frontend/profil.blade.php"
if (-not (Test-Path $profilPath)) { throw "Tidak ketemu: $profilPath" }

$profilView = (Get-Content $profilPath -Raw -Encoding UTF8) -replace "`r`n", "`n"
Backup-File $profilPath

# 3a. Hero Profil -> Tentang Kami 2
$old1 = @'
        $tk2Style = fn (string $key): string => $tk2PakaiWarnaKhusus ? 'color: '.$tk2Colors[$key].';' : '';
    @endphp
    <section
        class="{{ $tk2PakaiWarnaKhusus ? '' : 'bg-admin-cream/40' }}"
'@
$new1 = @'
        $tk2Style = fn (string $key): string => $tk2PakaiWarnaKhusus ? 'color: '.$tk2Colors[$key].';' : '';
    @endphp

    {{-- Frame seam: Hero Profil (bg-white, tidak punya opsi warna) -> Tentang Kami 2 --}}
    @include('partials.frontend.frame-seam', ['from' => '#FFFFFF', 'to' => $tk2Frame['base']])

    <section
        class="{{ $tk2PakaiWarnaKhusus ? '' : 'bg-admin-cream/40' }}"
'@
if ($profilView.IndexOf($old1) -lt 0) { throw "Titik sisip #1 (Hero -> Tentang Kami 2) tidak cocok di $profilPath. Tidak ada yang ditimpa, cek manual." }
$profilView = $profilView.Replace($old1, $new1)

# 3b. Tentang Kami 2 -> Sejarah
$old2 = @'
        $sejarahStyle = fn (string $key): string => $sejarahPakaiWarnaKhusus ? 'color: '.$sejarahColors[$key].';' : '';
    @endphp
    <section
        id="sejarah"
'@
$new2 = @'
        $sejarahStyle = fn (string $key): string => $sejarahPakaiWarnaKhusus ? 'color: '.$sejarahColors[$key].';' : '';
    @endphp

    {{-- Frame seam: Tentang Kami 2 -> Sejarah --}}
    @include('partials.frontend.frame-seam', ['from' => $tk2Frame['base'], 'to' => $sejarahBase])

    <section
        id="sejarah"
'@
if ($profilView.IndexOf($old2) -lt 0) { throw "Titik sisip #2 (Tentang Kami 2 -> Sejarah) tidak cocok di $profilPath. Tidak ada yang ditimpa, cek manual." }
$profilView = $profilView.Replace($old2, $new2)

# 3c. Sejarah -> Nilai Kami
$old3 = @'
        $nilaiStyle = fn (string $key): string => $nilaiPakaiWarnaKhusus ? 'color: '.$nilaiColors[$key].';' : '';
    @endphp
    <section
        class="{{ $nilaiPakaiWarnaKhusus ? '' : 'bg-admin-cream' }}"
'@
$new3 = @'
        $nilaiStyle = fn (string $key): string => $nilaiPakaiWarnaKhusus ? 'color: '.$nilaiColors[$key].';' : '';
    @endphp

    {{-- Frame seam: Sejarah -> Nilai Kami --}}
    @include('partials.frontend.frame-seam', ['from' => $sejarahBase, 'to' => $nilaiFrame['base']])

    <section
        class="{{ $nilaiPakaiWarnaKhusus ? '' : 'bg-admin-cream' }}"
'@
if ($profilView.IndexOf($old3) -lt 0) { throw "Titik sisip #3 (Sejarah -> Nilai Kami) tidak cocok di $profilPath. Tidak ada yang ditimpa, cek manual." }
$profilView = $profilView.Replace($old3, $new3)

# 3d. Nilai Kami -> Why Choose Us
$old4 = @'
        $whyProfilGaya = fn (string $css): string => $whyProfilTerangMode ? ' style="'.e($css).'"' : '';
    @endphp
    <section class="{{ $whyProfilPakaiWarnaKhusus ? '' : 'bg-[#221B14]' }}"@if ($whyProfilPakaiWarnaKhusus) style="background: {{ $whyProfilFrame['css'] }};" @endif>
'@
$new4 = @'
        $whyProfilGaya = fn (string $css): string => $whyProfilTerangMode ? ' style="'.e($css).'"' : '';
    @endphp

    {{-- Frame seam: Nilai Kami -> Why Choose Us --}}
    @include('partials.frontend.frame-seam', ['from' => $nilaiFrame['base'], 'to' => $whyProfilFrame['base']])

    <section class="{{ $whyProfilPakaiWarnaKhusus ? '' : 'bg-[#221B14]' }}"@if ($whyProfilPakaiWarnaKhusus) style="background: {{ $whyProfilFrame['css'] }};" @endif>
'@
if ($profilView.IndexOf($old4) -lt 0) { throw "Titik sisip #4 (Nilai Kami -> Why Choose Us) tidak cocok di $profilPath. Tidak ada yang ditimpa, cek manual." }
$profilView = $profilView.Replace($old4, $new4)

Set-Content -Path $profilPath -Value $profilView -Encoding UTF8 -NoNewline
Write-Host "Diedit: $profilPath (backup: $profilPath.bak-before-frame-seam-$stamp)"

# ========================================================================
# 4) Pengrajin Kami -- pages/frontend/pengrajin.blade.php (2 sambungan)
# ========================================================================
$pengrajinPath = "resources/views/pages/frontend/pengrajin.blade.php"
if (-not (Test-Path $pengrajinPath)) { throw "Tidak ketemu: $pengrajinPath" }

$pengrajinView = (Get-Content $pengrajinPath -Raw -Encoding UTF8) -replace "`r`n", "`n"
Backup-File $pengrajinPath

# 4a. Our Craftsmen Hero -> Grid Pengrajin
$oldP1 = @'
        </div>
    </section>

    {{-- =====================================================
         GRID PENGRAJIN
    ====================================================== --}}
    <section class="bg-white">
'@
$newP1 = @'
        </div>
    </section>

    {{-- Frame seam: Our Craftsmen Hero -> Grid Pengrajin (bg-white) --}}
    @include('partials.frontend.frame-seam', ['from' => $ourCraftsmenHeroFrame['base'], 'to' => '#FFFFFF'])

    {{-- =====================================================
         GRID PENGRAJIN
    ====================================================== --}}
    <section class="bg-white">
'@
$countP1 = ([regex]::Matches($pengrajinView, [regex]::Escape($oldP1))).Count
if ($countP1 -ne 1) { throw "Titik sisip #1 (Our Craftsmen Hero -> Grid Pengrajin) ditemukan $countP1 kali (harusnya 1) di $pengrajinPath. Tidak ada yang ditimpa, cek manual." }
$pengrajinView = $pengrajinView.Replace($oldP1, $newP1)

# 4b. Grid Pengrajin -> CTA
$oldP2 = @'
        </div>
    </section>

    {{-- =====================================================
         CTA
    ====================================================== --}}
    <section class="bg-[#1A1A1A]">
'@
$newP2 = @'
        </div>
    </section>

    {{-- Frame seam: Grid Pengrajin (bg-white) -> CTA (bg-[#1A1A1A]) --}}
    @include('partials.frontend.frame-seam', ['from' => '#FFFFFF', 'to' => '#1A1A1A'])

    {{-- =====================================================
         CTA
    ====================================================== --}}
    <section class="bg-[#1A1A1A]">
'@
$countP2 = ([regex]::Matches($pengrajinView, [regex]::Escape($oldP2))).Count
if ($countP2 -ne 1) { throw "Titik sisip #2 (Grid Pengrajin -> CTA) ditemukan $countP2 kali (harusnya 1) di $pengrajinPath. Tidak ada yang ditimpa, cek manual." }
$pengrajinView = $pengrajinView.Replace($oldP2, $newP2)

Set-Content -Path $pengrajinPath -Value $pengrajinView -Encoding UTF8 -NoNewline
Write-Host "Diedit: $pengrajinPath (backup: $pengrajinPath.bak-before-frame-seam-$stamp)"

Write-Host ""
Write-Host "Selesai. Refresh /  /profil  /pengrajin-kami di browser (php artisan serve harus sudah jalan) untuk cek hasilnya."
Write-Host "Catatan: section 'Keunggulan' (features.blade.php) paddingnya cuma py-8 (32px),"
Write-Host "lebih kecil dari separuh tinggi seam (60px) -- kalau nanti kelihatan bagian atas"
Write-Host "ikon 'Keunggulan' agak ke-blend juga, kabari saya, tinggal saya kecilkan seam di titik itu saja."
