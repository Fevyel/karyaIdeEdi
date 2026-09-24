# ============================================================
# Menambahkan section "Kunjungi Kami" (alamat + peta) di Beranda,
# terinspirasi layout hero Uber: teks di kiri, kartu rounded
# (di sini: embed Google Maps) di kanan.
#
# Yang berubah:
#
# 1) BARU: resources/views/partials/frontend/lokasi.blade.php
#    - Section 2 kolom: kiri (label kecil, judul, deskripsi,
#      alamat, WhatsApp, tombol "Lihat di Google Maps" &
#      "Hubungi via WhatsApp"), kanan (peta Google Maps embed,
#      tanpa perlu API key, dibungkus rounded-card sama seperti
#      foto di Hero).
#    - Alamat diambil dari Setting::current()->alamat. Kolom ini
#      SUDAH ADA di database tapi BELUM ADA form edit-nya di
#      Admin > Pengaturan -- jadi sampai field itu diisi (lewat
#      tinker atau ditambahkan form-nya nanti), section ini akan
#      menampilkan teks placeholder "Alamat toko belum diatur...".
#      Tombol WhatsApp otomatis hilang kalau field whatsapp masih
#      kosong.
#
# 2) resources/views/home-placeholder.blade.php
#    - Tambah 1 baris @include('partials.frontend.lokasi') di
#      antara FAQ dan FOOTER. Tidak ada baris lain yang disentuh.
#
# TIDAK ADA bagian lain yang disentuh di kedua file ini.
#
# Cara pakai (dari VS Code integrated terminal, root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-tambah-lokasi-beranda.ps1
#
# Setelah itu cek di browser (php artisan serve):
#   - Buka "/" (Beranda), scroll ke bawah FAQ -> harus muncul
#     section "Kunjungi Kami" dengan peta di kanan.
# Tidak perlu npm run build (tidak ada class Tailwind baru).
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-lokasi-beranda-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
    return $backupPath
}

function Replace-ExactlyOnce {
    param(
        [string]$Path,
        [string]$Old,
        [string]$New,
        [bool]$UseBom
    )

    $content = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $Path))

    $occurrences = ([regex]::Matches($content, [regex]::Escape($Old))).Count
    if ($occurrences -eq 0) {
        Write-Host "[ERROR] Teks yang mau diganti tidak ditemukan di $Path." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah beda dari yang saya kira -- SAYA BERHENTI, tidak ada yang diubah di file ini." -ForegroundColor Red
        exit 1
    }
    if ($occurrences -gt 1) {
        Write-Host "[ERROR] Teks yang mau diganti muncul $occurrences kali di $Path (harusnya cuma 1)." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI supaya tidak salah ganti bagian yang lain." -ForegroundColor Red
        exit 1
    }

    Backup-File -Path $Path | Out-Null

    $newContent = $content.Replace($Old, $New)
    $encoding = New-Object System.Text.UTF8Encoding($UseBom)
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $newContent, $encoding)

    Write-Host "  OK -- $Path sudah di-patch." -ForegroundColor Green
}

$lokasiPartialPath = "resources\views\partials\frontend\lokasi.blade.php"
$homePlaceholderPath = "resources\views\home-placeholder.blade.php"

# ------------------------------------------------------------
# 1) Buat file baru: partial "lokasi"
# ------------------------------------------------------------
if (Test-Path $lokasiPartialPath) {
    Write-Host "[LEWAT] $lokasiPartialPath sudah ada -- tidak ditimpa. Hapus manual dulu kalau mau dibuat ulang." -ForegroundColor Yellow
} else {
    $lokasiContent = @'
{{--
    ==========================================================
    LOKASI / ALAMAT ("Kunjungi Kami") — Homepage Karya Ide Edi
    ==========================================================
    Section 2 kolom ala hero: teks + alamat di kiri, peta Google
    Maps (embed tanpa API key) di kanan dalam rounded-card, sama
    gaya visual dengan section Hero.

    Sumber data: App\Models\Setting (field alamat, whatsapp).
    Field "alamat" sudah ada di database tapi belum ada form
    edit-nya di Admin > Pengaturan, jadi selama belum diisi,
    section ini menampilkan teks placeholder di bawah. Tombol
    WhatsApp otomatis disembunyikan kalau field whatsapp kosong.

    Pemakaian:
        @include('partials.frontend.lokasi')
    ==========================================================
--}}
@php
    $lokasiSetting = \App\Models\Setting::current();

    $lokasiAlamat = trim((string) $lokasiSetting->alamat) !== ''
        ? $lokasiSetting->alamat
        : 'Alamat toko belum diatur. Silakan lengkapi lewat menu Pengaturan.';

    $lokasiMapQuery = trim($lokasiSetting->site_name.' '.$lokasiSetting->alamat);
    $lokasiMapUrl = 'https://www.google.com/maps?q='.urlencode($lokasiMapQuery ?: $lokasiSetting->site_name).'&output=embed';
    $lokasiMapLink = 'https://www.google.com/maps/search/?api=1&query='.urlencode($lokasiMapQuery ?: $lokasiSetting->site_name);

    $lokasiWhatsappDigits = $lokasiSetting->whatsappDigits();
@endphp

<section class="bg-admin-canvas">
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-6 py-14 sm:px-8 lg:grid-cols-2 lg:gap-14 lg:px-10 lg:py-20">

        {{-- ============ KIRI: Teks & Alamat ============ --}}
        <div>
            <span class="text-xs font-semibold uppercase tracking-widest text-admin-accent">Kunjungi Kami</span>

            <h2 class="mt-3 font-display text-3xl leading-tight text-admin-ink sm:text-4xl">
                Datang Langsung ke<br>Workshop Kami
            </h2>

            <p class="mt-4 max-w-md text-sm leading-relaxed text-admin-ink-soft">
                Lihat langsung kualitas material dan proses pembuatan furnitur {{ $lokasiSetting->site_name }} sebelum memutuskan pesan. Kami dengan senang hati menyambut kunjungan Anda.
            </p>

            <dl class="mt-6 space-y-4 border-t border-admin-border pt-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                        <i class="fa-solid fa-location-dot text-sm text-admin-accent"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Alamat</dt>
                        <dd class="mt-0.5 text-sm leading-relaxed text-admin-ink">{{ $lokasiAlamat }}</dd>
                    </div>
                </div>

                @if ($lokasiWhatsappDigits)
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-admin-cream">
                            <i class="fa-brands fa-whatsapp text-sm text-admin-accent"></i>
                        </span>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">WhatsApp</dt>
                            <dd class="mt-0.5 text-sm leading-relaxed text-admin-ink">{{ $lokasiSetting->whatsapp }}</dd>
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
                        class="inline-flex items-center gap-2 rounded-lg border border-admin-border px-5 py-2.5 text-sm font-medium text-admin-ink transition-all duration-300 hover:!border-admin-accent hover:!text-admin-accent"
                    >
                        Hubungi via WhatsApp
                    </a>
                @endif
            </div>
        </div>

        {{-- ============ KANAN: Peta ============ --}}
        <div class="relative">
            <div class="aspect-4/3 w-full overflow-hidden rounded-[28px] shadow-xl shadow-black/10">
                <iframe
                    src="{{ $lokasiMapUrl }}"
                    class="h-full w-full border-0 grayscale-[15%]"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Lokasi {{ $lokasiSetting->site_name }}"
                ></iframe>
            </div>
        </div>
    </div>
</section>
'@

    $lokasiDir = Split-Path $lokasiPartialPath -Parent
    if (-not (Test-Path $lokasiDir)) {
        New-Item -ItemType Directory -Path $lokasiDir -Force | Out-Null
    }

    $encoding = New-Object System.Text.UTF8Encoding($true)
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $lokasiPartialPath), $lokasiContent, $encoding)
    Write-Host "  OK -- $lokasiPartialPath dibuat." -ForegroundColor Green
}

# ------------------------------------------------------------
# 2) Sambungkan ke home-placeholder.blade.php (sebelum footer)
# ------------------------------------------------------------
$hp_old = @'
        {{-- ================= FAQ ================= --}}
        @include('partials.frontend.faq')

        {{-- ================= FOOTER ================= --}}
'@

$hp_new = @'
        {{-- ================= FAQ ================= --}}
        @include('partials.frontend.faq')

        {{-- ================= LOKASI / ALAMAT ================= --}}
        @include('partials.frontend.lokasi')

        {{-- ================= FOOTER ================= --}}
'@

Replace-ExactlyOnce -Path $homePlaceholderPath -Old $hp_old -New $hp_new -UseBom $true

Write-Host ""
Write-Host "Selesai. Section 'Kunjungi Kami' sudah tersambung di Beranda, sebelum Footer." -ForegroundColor Green
