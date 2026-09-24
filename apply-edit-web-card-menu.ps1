# ============================================================
# Edit Web (Admin) -- ganti dari SIDEBAR TAB jadi KARTU PILIHAN
#
# Sebelumnya (dari patch apply-tentang-kami-group.ps1): begitu masuk
# tab "Edit Web", langsung tampil sidebar berisi 2 kotak (grup
# "Beranda" + grup "Tentang Kami") yang keduanya SELALU kelihatan
# bareng, dan section aktif langsung tampil di kanan.
#
# Sekarang: begitu masuk "Edit Web", yang tampil PERTAMA adalah grid
# 2 kartu rectangle -- "Beranda" (bisa diklik) dan "Tentang Kami"
# (badge "Segera", tidak bisa diklik, karena belum ada satu pun
# section-nya yang siap). Klik kartu "Beranda" -> baru masuk ke
# tampilan sidebar 7 tab (Header, Keunggulan, dst) + form di
# kanannya, sama seperti sebelum ada patch Tentang Kami dulu --
# ditambah tombol "Kembali ke Edit Web" di atasnya untuk balik ke
# grid kartu.
#
# Kartu "Tentang Kami" TIDAK dibuka jadi sidebar tab lagi (karena
# semua isinya masih "Segera" / belum ada form-nya) -- cukup jadi
# 1 kartu nonaktif di grid dengan deskripsi singkat isi apa saja
# nantinya (Profil Toko, Sejarah, Pengrajin, Keberlanjutan, Karier).
#
# PENTING: script ini mengasumsikan apply-tentang-kami-group.ps1
# SUDAH dijalankan sebelumnya (sesuai screenshot terakhir). Kalau
# belum, atau file sudah beda, Replace-ExactlyOnce di bawah akan
# berhenti sendiri tanpa mengubah apa pun -- aman.
#
# File ditulis pakai [System.IO.File]::WriteAllText dengan encoding
# yang SAMA seperti file aslinya (edit-web.blade.php: UTF-8 DENGAN
# BOM -- dipertahankan sama persis).
#
# Cara pakai (dari VS Code integrated terminal, di root project
# C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-edit-web-card-menu.ps1
#
# Setelah itu langsung cek di browser (php artisan serve) --
# tidak perlu npm run build.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-edit-web-card-menu-$stamp"
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
        Write-Host "        Kemungkinan file ini sudah beda dari yang saya kira (atau patch sebelumnya belum dijalankan) -- SAYA BERHENTI, tidak ada yang diubah di file ini." -ForegroundColor Red
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
# PATCH A: ganti properti $aboutSections (daftar tab "Tentang Kami"
# yang dulu dipakai di sidebar) jadi $groups (metadata kartu) +
# $activeGroup + selectGroup().
# ------------------------------------------------------------
$patchA_Old = @'
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

$patchA_New = @'
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

    /**
     * Halaman apa saja yang bisa dikelola dari "Edit Web", ditampilkan
     * sebagai KARTU PILIHAN dulu (bukan langsung sidebar tab). Klik kartu
     * yang `ready` => baru masuk ke daftar section (sidebar + form)
     * halaman itu, lihat $activeGroup & selectGroup() di bawah. Kartu
     * yang belum `ready` (Tentang Kami) tampil dengan badge "Segera" dan
     * tidak bisa diklik -- halaman Profil di frontend-nya sendiri sudah
     * ada, tapi form edit dari sisi admin belum dibangun sama sekali.
     */
    public array $groups = [
        [
            'key' => 'beranda',
            'label' => 'Beranda',
            'icon' => 'fa-house',
            'description' => 'Header, Keunggulan, Sejak Berdiri, Produk Unggulan, Kategori Produk, Testimoni, Kenapa Pilih Kami.',
            'ready' => true,
        ],
        [
            'key' => 'tentang-kami',
            'label' => 'Tentang Kami',
            'icon' => 'fa-circle-info',
            'description' => 'Profil Toko, Sejarah, Pengrajin, Keberlanjutan, Karier.',
            'ready' => false,
        ],
    ];

    /** Kartu mana yang lagi aktif di "Edit Web". 'menu' = tampil grid kartu pilihan. */
    public string $activeGroup = 'menu';

    /** Pindah antara grid kartu pilihan ('menu') dan halaman yang dipilih. Kartu yang belum `ready` diabaikan. */
    public function selectGroup(string $key): void
    {
        if ($key !== 'menu') {
            $group = collect($this->groups)->firstWhere('key', $key);

            if (! $group || ! $group['ready']) {
                return;
            }
        }

        $this->activeGroup = $key;
    }
'@

Replace-ExactlyOnce -Path $editWebPath -Old $patchA_Old -New $patchA_New -UseBom $true

# ------------------------------------------------------------
# PATCH B1: bagian AWAL layout -- ganti 2 kotak sidebar (Beranda +
# Tentang Kami) yang selalu tampil, jadi @if grid-kartu / @else
# sidebar-Beranda-saja (+ tombol "Kembali ke Edit Web").
# ------------------------------------------------------------
$patchB1_Old = @'
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

$patchB1_New = @'
    @if ($activeGroup === 'menu')

        {{-- ================= PILIH HALAMAN (kartu) ================= --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($groups as $group)
                <button
                    type="button"
                    wire:click="selectGroup('{{ $group['key'] }}')"
                    @if(! $group['ready']) disabled @endif
                    class="flex items-start gap-4 rounded-2xl border border-admin-border bg-admin-surface p-5 text-left transition
                    {{ $group['ready']
                        ? 'hover:border-admin-accent hover:shadow-sm'
                        : 'cursor-not-allowed opacity-60' }}"
                >
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-admin-cream text-admin-panel">
                        <i class="fa-solid {{ $group['icon'] }} text-lg"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="font-display text-base font-semibold text-admin-ink">{{ $group['label'] }}</span>
                            @unless ($group['ready'])
                                <span class="rounded-full bg-admin-cream px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                                    Segera
                                </span>
                            @endunless
                        </span>
                        <span class="mt-0.5 block text-xs text-admin-ink-soft">
                            {{ $group['description'] }}
                        </span>
                    </span>
                    @if ($group['ready'])
                        <i class="fa-solid fa-chevron-right mt-1 shrink-0 text-xs text-admin-ink-soft"></i>
                    @endif
                </button>
            @endforeach
        </div>

    @else

        <button
            type="button"
            wire:click="selectGroup('menu')"
            class="mb-2 inline-flex items-center gap-2 text-sm font-medium text-admin-ink-soft transition hover:text-admin-ink"
        >
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Kembali ke Edit Web
        </button>

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

Replace-ExactlyOnce -Path $editWebPath -Old $patchB1_Old -New $patchB1_New -UseBom $true

# ------------------------------------------------------------
# PATCH B2: bagian AKHIR layout -- tutup @if/@else di atas, tepat
# sebelum modal crop foto header.
# ------------------------------------------------------------
$patchB2_Old = @'
            @endif
        </div>
    </div>

    {{-- ================= MODAL CROP FOTO HEADER ================= --}}
'@

$patchB2_New = @'
            @endif
        </div>
    </div>

    @endif

    {{-- ================= MODAL CROP FOTO HEADER ================= --}}
'@

Replace-ExactlyOnce -Path $editWebPath -Old $patchB2_Old -New $patchB2_New -UseBom $true

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Backup .bak-before-edit-web-card-menu-* dibuat di lokasi file aslinya." -ForegroundColor Cyan
Write-Host " Langsung dicek di browser (php artisan serve) -> Admin > Edit Web, tidak perlu npm run build." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
