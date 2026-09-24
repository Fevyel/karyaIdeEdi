# ============================================================
# Edit Web (Admin) -- Grup "Tentang Kami" (daftar tab placeholder)
#
# FIX 1: Daftar tab section Beranda (Header, Keunggulan, Sejak
#        Berdiri, Produk Unggulan, Kategori Produk, Testimoni
#        Pelanggan, Kenapa Pilih Kami) di sidebar kiri "Edit Web"
#        SUDAH dibungkus 1 kotak rounded-2xl dengan label "Beranda"
#        di atasnya (bukan tab yang bisa diklik) -- ini kondisi yang
#        sudah ada sebelumnya, TIDAK diubah oleh patch ini.
#
# FIX 2 (baru): Menambah grup KEDUA di bawahnya, dengan label kecil
#        "Tentang Kami", berisi daftar tab placeholder:
#        Profil Toko, Sejarah, Pengrajin, Keberlanjutan, Karier.
#        Semua statusnya "Segera" dan tombolnya disabled/tidak bisa
#        diklik -- persis pola tombol "Segera" yang sudah ada di
#        grup Beranda, cuma sekarang seluruh tombol di grup ini
#        begitu (belum ada satu pun yang siap).
#
#        Nama-nama tab di atas cuma placeholder awal (dicocokkan
#        dengan halaman frontend yang sudah ada: profil, pengrajin,
#        keberlanjutan, karier) -- gampang diganti belakangan kalau
#        mau beda, karena belum ada logic/form yang tersambung ke
#        tab-tab ini sama sekali.
#
# TIDAK ADA bagian lain di file ini yang disentuh.
#
# File ditulis pakai [System.IO.File]::WriteAllText dengan encoding
# yang SAMA seperti file aslinya (edit-web.blade.php: UTF-8 DENGAN
# BOM -- sudah dicek, dipertahankan sama persis).
#
# Cara pakai (dari VS Code integrated terminal, di root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-tentang-kami-group.ps1
#
# Setelah itu langsung cek di browser (php artisan serve) --
# tidak perlu npm run build karena tidak ada class Tailwind baru
# yang sebelumnya belum pernah dipakai di project ini.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-tentang-kami-group-$stamp"
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

$editWebPath = "resources\views\pages\admin\edit-web.blade.php"

# ------------------------------------------------------------
# PATCH 1: tambah properti $aboutSections (placeholder "Tentang
# Kami"), ditaruh tepat di bawah $sections yang sudah ada.
# ------------------------------------------------------------
$patch1Old = @'
    public array $sections = [
        ['key' => 'header', 'label' => 'Header', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'keunggulan', 'label' => 'Keunggulan', 'icon' => 'fa-star', 'ready' => true],
        ['key' => 'sejak-berdiri', 'label' => 'Sejak Berdiri', 'icon' => 'fa-calendar-day', 'ready' => true],
        ['key' => 'produk-unggulan', 'label' => 'Produk Unggulan', 'icon' => 'fa-layer-group', 'ready' => true],
        ['key' => 'kategori', 'label' => 'Kategori Produk', 'icon' => 'fa-th-large', 'ready' => true],
        ['key' => 'testimoni', 'label' => 'Testimoni Pelanggan', 'icon' => 'fa-quote-left', 'ready' => true],
        ['key' => 'keahlian', 'label' => 'Kenapa Pilih Kami', 'icon' => 'fa-award', 'ready' => true],
    ];

    public string $activeSection = 'header';
'@

$patch1New = @'
    public array $sections = [
        ['key' => 'header', 'label' => 'Header', 'icon' => 'fa-image', 'ready' => true],
        ['key' => 'keunggulan', 'label' => 'Keunggulan', 'icon' => 'fa-star', 'ready' => true],
        ['key' => 'sejak-berdiri', 'label' => 'Sejak Berdiri', 'icon' => 'fa-calendar-day', 'ready' => true],
        ['key' => 'produk-unggulan', 'label' => 'Produk Unggulan', 'icon' => 'fa-layer-group', 'ready' => true],
        ['key' => 'kategori', 'label' => 'Kategori Produk', 'icon' => 'fa-th-large', 'ready' => true],
        ['key' => 'testimoni', 'label' => 'Testimoni Pelanggan', 'icon' => 'fa-quote-left', 'ready' => true],
        ['key' => 'keahlian', 'label' => 'Kenapa Pilih Kami', 'icon' => 'fa-award', 'ready' => true],
    ];

    /**
     * Daftar tab placeholder untuk grup "Tentang Kami" (halaman
     * Profil frontend, bukan Beranda). SEMUA belum ada form/section
     * aslinya -- baru sebatas daftar nama tab yang tampil "Segera"
     * di sidebar Edit Web, tidak tersambung ke $activeSection atau
     * fungsi save manapun. Nama tab cuma placeholder awal, gampang
     * diganti/ditambah belakangan.
     */
    public array $aboutSections = [
        ['key' => 'profil-toko', 'label' => 'Profil Toko', 'icon' => 'fa-store'],
        ['key' => 'sejarah', 'label' => 'Sejarah', 'icon' => 'fa-clock-rotate-left'],
        ['key' => 'pengrajin', 'label' => 'Pengrajin', 'icon' => 'fa-hammer'],
        ['key' => 'keberlanjutan', 'label' => 'Keberlanjutan', 'icon' => 'fa-leaf'],
        ['key' => 'karier', 'label' => 'Karier', 'icon' => 'fa-briefcase'],
    ];

    public string $activeSection = 'header';
'@

Replace-ExactlyOnce -Path $editWebPath -Old $patch1Old -New $patch1New -UseBom $true

# ------------------------------------------------------------
# PATCH 2: bungkus kotak "Beranda" (sudah ada) + kotak baru
# "Tentang Kami" dalam 1 wrapper flex-col supaya keduanya tersusun
# vertikal di kolom sidebar kiri yang sama.
# ------------------------------------------------------------
$patch2Old = @'
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">

        {{-- ================= DAFTAR SECTION ================= --}}
        <div class="flex flex-col gap-2 rounded-2xl border border-admin-border bg-admin-surface p-2">
            <p class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                Beranda
            </p>
            <div class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible">
                @foreach ($sections as $section)
                    <button
                        type="button"
                        wire:click="selectSection('{{ $section['key'] }}')"
                        @if(! $section['ready']) disabled @endif
                        class="flex shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-left text-sm font-medium transition
                        {{ $activeSection === $section['key']
                            ? 'bg-admin-panel text-white'
                            : ($section['ready']
                                ? 'text-admin-ink hover:bg-admin-cream'
                                : 'cursor-not-allowed text-admin-ink-soft/60') }}"
                    >
                        <i class="fa-solid {{ $section['icon'] }} w-4 text-center text-xs"></i>
                        <span class="whitespace-nowrap">{{ $section['label'] }}</span>
                        @unless ($section['ready'])
                            <span class="ml-auto rounded-full bg-admin-cream px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                                Segera
                            </span>
                        @endunless
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ================= PANEL SECTION AKTIF ================= --}}
'@

$patch2New = @'
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">

        {{-- ================= DAFTAR SECTION (kiri) ================= --}}
        <div class="flex flex-col gap-4">

            {{-- Grup "Beranda" -- daftar section yang bisa diedit di halaman Beranda --}}
            <div class="flex flex-col gap-2 rounded-2xl border border-admin-border bg-admin-surface p-2">
                <p class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                    Beranda
                </p>
                <div class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible">
                    @foreach ($sections as $section)
                        <button
                            type="button"
                            wire:click="selectSection('{{ $section['key'] }}')"
                            @if(! $section['ready']) disabled @endif
                            class="flex shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-left text-sm font-medium transition
                            {{ $activeSection === $section['key']
                                ? 'bg-admin-panel text-white'
                                : ($section['ready']
                                    ? 'text-admin-ink hover:bg-admin-cream'
                                    : 'cursor-not-allowed text-admin-ink-soft/60') }}"
                        >
                            <i class="fa-solid {{ $section['icon'] }} w-4 text-center text-xs"></i>
                            <span class="whitespace-nowrap">{{ $section['label'] }}</span>
                            @unless ($section['ready'])
                                <span class="ml-auto rounded-full bg-admin-cream px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                                    Segera
                                </span>
                            @endunless
                        </button>
                    @endforeach
                </div>
            </div>

            {{--
                Grup "Tentang Kami" -- baru sebatas daftar tab placeholder,
                semua masih "Segera". Belum ada $activeSection/form yang
                tersambung ke sini, jadi tombolnya sengaja disabled total
                (bukan cuma dari kondisi `ready`, karena belum ada satu pun
                yang ready) -- mengikuti pola visual tombol "Segera" yang
                sudah ada di grup Beranda di atas.
            --}}
            <div class="flex flex-col gap-2 rounded-2xl border border-admin-border bg-admin-surface p-2">
                <p class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                    Tentang Kami
                </p>
                <div class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible">
                    @foreach ($aboutSections as $section)
                        <button
                            type="button"
                            disabled
                            class="flex shrink-0 cursor-not-allowed items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-left text-sm font-medium text-admin-ink-soft/60 transition"
                        >
                            <i class="fa-solid {{ $section['icon'] }} w-4 text-center text-xs"></i>
                            <span class="whitespace-nowrap">{{ $section['label'] }}</span>
                            <span class="ml-auto rounded-full bg-admin-cream px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                                Segera
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ================= PANEL SECTION AKTIF ================= --}}
'@

Replace-ExactlyOnce -Path $editWebPath -Old $patch2Old -New $patch2New -UseBom $true

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Backup .bak-before-tentang-kami-group-* dibuat di lokasi file aslinya." -ForegroundColor Cyan
Write-Host " Langsung dicek di browser (php artisan serve) -> Admin > Edit Web, tidak perlu npm run build." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
