# ============================================================
# Memperbaiki section "Lokasi" (Kunjungi Kami) di Beranda:
#
# 1) Peta di kolom kanan sekarang Google Maps ASLI (iframe embed,
#    dibangun otomatis dari Nama Toko + field "alamat" di Admin >
#    Pengaturan) -- bukan ilustrasi SVG fiktif (jalan/rute palsu)
#    seperti sebelumnya. Makanya sebelumnya, ganti tautan Maps di
#    Edit Web > Lokasi tidak mengubah tampilan kanan -- karena
#    memang tidak pernah terhubung ke sana.
# 2) Peta diberi filter CSS (grayscale + sepia + hue-rotate) biar
#    warnanya condong coklat/krem khas situs, bukan biru/hijau
#    bawaan Google -- pendekatan reskin lewat CSS filter di
#    browser, bukan custom style resmi dari Google (butuh API key
#    berbayar untuk itu, belum ada di project ini).
# 3) Tautan admin di Edit Web > Lokasi TETAP dipakai untuk tombol
#    "Lihat di Google Maps" (boleh format apa saja) -- HANYA untuk
#    peta iframe kanan yang sekarang dibangun otomatis dari alamat.
# 4) Tag "Workshop Kami" & marker berdenyut dipertahankan sebagai
#    aksen dekoratif di atas peta asli. Garis rute & nama jalan
#    fiktif (Jl. Kauman, Gg. Jeruk, dst) dihapus karena sekarang
#    ada peta asli di bawahnya.
#
# Yang berubah: HANYA resources/views/partials/frontend/lokasi.blade.php
# (ditulis ulang penuh). Tidak ada file lain yang disentuh.
#
# CATATAN: field "alamat" di Admin > Pengaturan masih kosong saat
# ini -- peta kanan baru akurat setelah field itu diisi. Sebelum
# diisi, peta akan menampilkan hasil pencarian nama toko saja.
#
# Cara pakai (dari VS Code integrated terminal, root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-perbaiki-peta-lokasi-beranda.ps1
#
# Setelah itu cek di browser (php artisan serve):
#   - Buka "/" (Beranda), scroll ke section "Kunjungi Kami" --
#     kanan harus menampilkan Google Maps asli (bukan gambar
#     garis-garis lagi), dengan nuansa warna coklat/krem.
# Tidak perlu npm run build (tidak ada class Tailwind baru).
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-perbaiki-peta-lokasi-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
    return $backupPath
}

$lokasiPath = "resources\views\partials\frontend\lokasi.blade.php"

if (-not (Test-Path $lokasiPath)) {
    Write-Host "[ERROR] File tidak ditemukan: $lokasiPath" -ForegroundColor Red
    Write-Host "        SAYA BERHENTI, tidak ada yang diubah." -ForegroundColor Red
    exit 1
}

Backup-File -Path $lokasiPath | Out-Null

$newContent = @'
{{--
    ==========================================================
    LOKASI / ALAMAT ("Kunjungi Kami") — Homepage Karya Ide Edi
    ==========================================================
    STRUKTUR: bukan kartu/card bermargin di kolom kanan. Peta
    jadi BACKGROUND full-bleed di seluruh section, memudar ke
    arah kiri (mask-image) supaya teks di atasnya tetap gampang
    dibaca — persis strukturnya referensi hero Uber: teks kiri,
    peta nge-bleed sampai tepi kanan, tanpa border/rounded/
    shadow yang bikin dia kelihatan seperti "widget/kartu".

    PETA SEKARANG BENERAN Google Maps (iframe embed, bukan
    ilustrasi SVG fiktif lagi), diberi filter CSS (grayscale +
    sepia + hue-rotate) supaya nada warnanya condong ke coklat/
    krem khas situs ini, bukan biru/hijau bawaan Google. Ini
    reskin lewat CSS filter di browser, BUKAN custom style resmi
    dari Google -- karena project belum pasang API key Google
    Maps berbayar. Kalau nanti mau hasil lebih presisi (custom
    style asli lewat Google Cloud Console), perlu API key +
    billing di Google Cloud.

    Peta di-generate OTOMATIS dari Nama Toko + field "alamat" di
    Admin > Pengaturan (BUKAN dari tautan yang di-paste di Edit
    Web > Lokasi) -- soalnya link Google Maps biasa (search/share)
    diblokir Google untuk di-embed di iframe. Tautan admin di
    Edit Web > Lokasi tetap dipakai, tapi HANYA untuk tombol
    "Lihat di Google Maps" (buka tab baru, bebas format apa saja).
    Kalau admin isi tautan yang sudah berupa format embed resmi
    (dari Google Maps: Share > Embed a map > copy src), tautan itu
    otomatis dipakai juga untuk peta iframe-nya.

    SUMBER DATA — section ini bisa diedit admin lewat
    Admin Panel > Edit Web > Beranda > Lokasi (section_key 'lokasi'
    di tabel home_sections, lihat pages/admin/edit-web.blade.php
    method saveLokasi()). Yang diedit dari sana: label kecil, judul
    2 baris, deskripsi, tautan Google Maps (untuk tombol), dan
    warna latar.

    Alamat yang tampil di teks & yang dipakai membangun peta TETAP
    diambil dari App\Models\Setting (menu Admin > Pengaturan),
    bukan dari Edit Web.

    FALLBACK (penting, jangan dihapus): tautan Maps di Edit Web
    boleh dikosongkan admin. Kalau kosong, tombol "Lihat di Google
    Maps" dibangun otomatis dari nama toko + alamat (Setting).
    Nomor WhatsApp TETAP hanya dari Setting; tombol WhatsApp
    otomatis disembunyikan kalau kosong.

    Pemakaian:
        @include('partials.frontend.lokasi')
    ==========================================================
--}}
@php
    $lokasiSetting = \App\Models\Setting::current();

    $lokasiSection = \App\Models\HomeSection::dataFor('lokasi', [
        'bg_color' => null,
        'eyebrow' => 'Kunjungi Kami',
        'heading_line1' => 'Datang Langsung ke',
        'heading_line2' => 'Workshop Kami',
        'description' => 'Lihat langsung kualitas material dan proses pembuatan furnitur '.$lokasiSetting->site_name.' sebelum memutuskan pesan. Kami dengan senang hati menyambut kunjungan Anda.',
        'maps_url' => null,
    ]);

    // Alamat SELALU ikut Admin > Pengaturan (tidak diedit dari Edit Web).
    $lokasiAlamatSetting = trim((string) $lokasiSetting->alamat);

    $lokasiAlamat = $lokasiAlamatSetting !== ''
        ? $lokasiAlamatSetting
        : 'Alamat toko belum diatur. Silakan lengkapi lewat menu Pengaturan.';

    // Tautan tombol "Lihat di Google Maps": pakai tautan resmi dari admin
    // kalau ada (bebas format apa saja), kalau tidak dibangun otomatis.
    $lokasiMapsUrlAdmin = trim((string) ($lokasiSection['maps_url'] ?? ''));

    if ($lokasiMapsUrlAdmin !== '') {
        $lokasiMapLink = $lokasiMapsUrlAdmin;
    } else {
        $lokasiMapQuery = trim($lokasiSetting->site_name.' '.$lokasiSetting->alamat);
        $lokasiMapLink = 'https://www.google.com/maps/search/?api=1&query='.urlencode($lokasiMapQuery ?: $lokasiSetting->site_name);
    }

    // Sumber (src) peta iframe di background: SELALU dibangun otomatis dari
    // nama toko + alamat (Setting), karena tautan search/share biasa tidak
    // bisa di-embed (diblokir Google). Kalau tautan admin di atas kebetulan
    // sudah format embed resmi, pakai itu langsung.
    $lokasiMapEmbedQuery = trim($lokasiSetting->site_name.' '.$lokasiSetting->alamat);
    $lokasiMapEmbedSrc = 'https://www.google.com/maps?q='.urlencode($lokasiMapEmbedQuery ?: $lokasiSetting->site_name).'&z=15&output=embed';

    if ($lokasiMapsUrlAdmin !== '' && (str_contains($lokasiMapsUrlAdmin, 'output=embed') || str_contains($lokasiMapsUrlAdmin, '/maps/embed'))) {
        $lokasiMapEmbedSrc = $lokasiMapsUrlAdmin;
    }

    $lokasiWhatsappDigits = $lokasiSetting->whatsappDigits();

    // Tag label dekoratif ala "DESTINATION" di atas peta -- tetap/hardcode,
    // murni aksen visual, tidak diedit dari mana pun.
    $lokasiMapTagLabel = 'Workshop Kami';

    // ===== Warna latar & kontras teks =====
    // Pola sama dengan partials/frontend/products.blade.php: kalau admin belum
    // pernah memilih warna (masih putih bawaan), section ini dirender PERSIS
    // seperti sebelumnya — tetap pakai class Tailwind lama, tanpa style inline
    // sama sekali. Style inline baru dipakai kalau admin memilih warna khusus.
    $lokasiBgColor = $lokasiSection['bg_color'] ?: '#FFFFFF';
    $lokasiPakaiWarnaKhusus = strtoupper(ltrim($lokasiBgColor, '#')) !== 'FFFFFF';

    $lokasiHexTerang = function (string $hex): bool {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

        return (0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b)) > 0.5;
    };

    $lokasiColors = $lokasiPakaiWarnaKhusus
        ? ($lokasiHexTerang($lokasiBgColor)
            ? ['ink' => '#1A1208', 'soft' => 'rgba(26, 18, 8, 0.72)', 'line' => 'rgba(26, 18, 8, 0.18)']
            : ['ink' => '#FFFFFF', 'soft' => 'rgba(255, 255, 255, 0.75)', 'line' => 'rgba(255, 255, 255, 0.24)'])
        : ['ink' => '', 'soft' => '', 'line' => ''];

    // Helper kecil: hasilkan atribut style hanya kalau warna khusus dipakai.
    $lokasiStyle = fn (string $key): string => $lokasiPakaiWarnaKhusus ? 'color: '.$lokasiColors[$key].';' : '';
@endphp

<section
    class="relative overflow-hidden {{ $lokasiPakaiWarnaKhusus ? '' : 'bg-white' }}"
    @if ($lokasiPakaiWarnaKhusus)
        style="background: linear-gradient(135deg, color-mix(in oklab, {{ $lokasiBgColor }} 100%, white 10%) 0%, {{ $lokasiBgColor }} 55%, color-mix(in oklab, {{ $lokasiBgColor }} 100%, black 14%) 100%);"
    @endif
>

    {{-- ============ BACKGROUND: Google Maps asli, di-tema ============ --}}
    <div
        class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block"
        style="-webkit-mask-image: linear-gradient(to right, transparent 0%, transparent 28%, black 55%); mask-image: linear-gradient(to right, transparent 0%, transparent 28%, black 55%);"
        aria-hidden="true"
    >
        <iframe
            src="{{ $lokasiMapEmbedSrc }}"
            class="h-full w-full border-0"
            style="filter: grayscale(45%) sepia(65%) hue-rotate(-8deg) saturate(140%) brightness(1.05) contrast(0.94);"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Peta lokasi {{ $lokasiSetting->site_name }}"
        ></iframe>

        {{-- tag label ala "DESTINATION" -- aksen dekoratif, teks tetap/hardcode --}}
        <span class="absolute -translate-y-1/2 rounded-md bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-admin-accent shadow-sm" style="left:56%; top:33%;">
            {{ $lokasiMapTagLabel }}
        </span>

        {{-- marker lokasi (berdenyut, ikon panah ke atas ala live-location) --}}
        <span class="absolute -translate-x-1/2 -translate-y-1/2" style="left:56%; top:44%;">
            <span class="absolute -inset-3 animate-ping rounded-full bg-admin-accent/25"></span>
            <span class="relative flex h-9 w-9 items-center justify-center rounded-full bg-admin-accent text-white shadow-lg">
                <i class="fa-solid fa-caret-up text-base"></i>
            </span>
        </span>
    </div>

    {{-- ============ KONTEN: Teks & Alamat ============ --}}
    <div class="relative mx-auto max-w-7xl px-6 py-16 sm:px-8 lg:px-10 lg:py-24">
        <div class="max-w-xl">
            <span class="text-xs font-semibold uppercase tracking-widest text-admin-accent">{{ $lokasiSection['eyebrow'] }}</span>

            <h2 class="mt-3 font-display text-3xl leading-tight text-admin-ink sm:text-4xl" style="{{ $lokasiStyle('ink') }}">
                {{ $lokasiSection['heading_line1'] }}<br>{{ $lokasiSection['heading_line2'] }}
            </h2>

            <p class="mt-4 max-w-md text-sm leading-relaxed text-admin-ink-soft" style="{{ $lokasiStyle('soft') }}">
                {{ $lokasiSection['description'] }}
            </p>

            <dl
                class="mt-6 space-y-4 border-t border-admin-border pt-6"
                @if ($lokasiPakaiWarnaKhusus) style="border-color: {{ $lokasiColors['line'] }};" @endif
            >
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                        <i class="fa-solid fa-location-dot text-sm text-admin-accent"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft" style="{{ $lokasiStyle('soft') }}">Alamat</dt>
                        <dd class="mt-0.5 text-sm leading-relaxed text-admin-ink" style="{{ $lokasiStyle('ink') }}">{{ $lokasiAlamat }}</dd>
                    </div>
                </div>

                @if ($lokasiWhatsappDigits)
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                            <i class="fa-brands fa-whatsapp text-sm text-admin-accent"></i>
                        </span>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft" style="{{ $lokasiStyle('soft') }}">WhatsApp</dt>
                            <dd class="mt-0.5 text-sm leading-relaxed text-admin-ink" style="{{ $lokasiStyle('ink') }}">{{ $lokasiSetting->whatsapp }}</dd>
                        </div>
                    </div>
                @endif
            </dl>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a
                    href="{{ $lokasiMapLink }}"
                    target="_blank"
                    rel="noopener"
                    class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                >
                    Lihat di Google Maps
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
                @if ($lokasiWhatsappDigits)
                    <a
                        href="https://wa.me/{{ $lokasiWhatsappDigits }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 rounded-lg border border-admin-border px-5 py-2.5 text-sm font-medium text-admin-ink transition-all duration-300 hover:border-admin-accent! hover:text-admin-accent!"
                        @if ($lokasiPakaiWarnaKhusus) style="border-color: {{ $lokasiColors['line'] }}; color: {{ $lokasiColors['ink'] }};" @endif
                    >
                        Hubungi via WhatsApp
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
'@

$encoding = New-Object System.Text.UTF8Encoding($true)
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $lokasiPath), $newContent, $encoding)

Write-Host "  OK -- $lokasiPath ditulis ulang." -ForegroundColor Green
Write-Host ""
Write-Host "Selesai. Peta di section Lokasi sekarang Google Maps asli bertema coklat/krem." -ForegroundColor Green
