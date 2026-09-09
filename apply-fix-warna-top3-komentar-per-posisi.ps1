# ============================================================
# FIX: Warna kartu Top 3 Komentar (Testimoni) sekarang per-posisi
#      (Top 1 / Top 2 / Top 3) -- sebelumnya cuma 1 warna yang
#      dipakai buat ketiga kartu sekaligus.
#
# Perubahan:
# 1. resources/views/pages/admin/edit-web.blade.php
#    - $testimoniUseCustomCardColor & $testimoniCardColor jadi array
#      keyed 1/2/3 (independen per posisi).
#    - mount() migrasi otomatis dari skema lama (1 warna) kalau ada.
#    - selectTestimoniCardPreset() & saveTestimoni() ikut disesuaikan.
#    - UI jadi 3 blok terpisah: Top 1 / Top 2 / Top 3.
# 2. resources/views/partials/frontend/testimonials.blade.php
#    - Baca card_colors per posisi, fallback ke pola bergantian
#      gelap/krem kalau posisi itu belum diisi admin.
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai (dari VS Code integrated terminal, di root project
#   C:\xampp\htdocs\karyaIdeEdi):
#   .\apply-fix-warna-top3-komentar-per-posisi.ps1
#
# Setelah itu langsung cek di browser (php artisan serve) --
# tidak perlu npm run build karena tidak ada class Tailwind baru.
# Tidak perlu migration baru juga, datanya nyimpen di kolom JSON
# `data` yang sudah ada di tabel home_sections.
# ============================================================

$ErrorActionPreference = "Stop"

function Backup-File {
    param([string]$Path)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-before-warna-top3-komentar-$stamp"
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

    # Potongan Old/New di script ini ditulis pakai LF (`n). Kalau file
    # target ternyata pakai CRLF (`r`n, umum di Windows/Git autocrlf),
    # sesuaikan dulu Old/New ke gaya yang sama supaya pencocokan tetap
    # presisi -- tanpa ini, script bisa gagal cuma gara-gara beda gaya
    # baris baru, padahal isinya sama persis.
    if ($content -match "`r`n") {
        $Old = $Old -replace "`r?`n", "`r`n"
        $New = $New -replace "`r?`n", "`r`n"
    } else {
        $Old = $Old -replace "`r`n", "`n"
        $New = $New -replace "`r`n", "`n"
    }

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

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Warna Top 3 Komentar Per-Posisi (Testimoni)" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# FILE 1: resources/views/pages/admin/edit-web.blade.php
# (file ini ADA BOM di awalnya -- pertahankan, UseBom = $true)
# ------------------------------------------------------------
$editWebPath = "resources\views\pages\admin\edit-web.blade.php"

Write-Host "[1/8] Ubah state $testimoniUseCustomCardColor / $testimoniCardColor jadi array per-rank ..." -ForegroundColor Yellow
$editWebOld1 = @'
     * 2. Warna Top 3 Komentar = warna kartu testimoni (biasanya bergantian
     *    gelap/krem secara otomatis -- kalau admin pilih warna sendiri di
     *    sini, ketiga kartu jadi satu warna yang sama, teks otomatis
     *    menyesuaikan kontras).
     */
    public bool $testimoniUseCustomBg = false;

    public string $testimoniBgColor = '#FAF8F4';

    public bool $testimoniUseCustomCardColor = false;

    public string $testimoniCardColor = '#2A1B12';
'@

$editWebNew1 = @'
     * 2. Warna Top 3 Komentar = warna kartu testimoni per posisi (Top 1,
     *    Top 2, Top 3) -- masing-masing opsional & independen satu sama
     *    lain. Kalau salah satu posisi tidak dicentang, kartu di posisi
     *    itu fallback ke pola bawaan (bergantian gelap/krem berdasarkan
     *    posisi, seperti sebelumnya). Teks tiap kartu custom otomatis
     *    menyesuaikan kontras (terang/gelap) sesuai warna yang dipilih.
     */
    public bool $testimoniUseCustomBg = false;

    public string $testimoniBgColor = '#FAF8F4';

    /**
     * Keyed 1-3 (Top 1, Top 2, Top 3), masing-masing independen.
     *
     * @var array<int, bool>
     */
    public array $testimoniUseCustomCardColor = [1 => false, 2 => false, 3 => false];

    /**
     * @var array<int, string>
     */
    public array $testimoniCardColor = [1 => '#2A1B12', 2 => '#2A1B12', 3 => '#2A1B12'];
'@

Replace-ExactlyOnce -Path $editWebPath -Old $editWebOld1 -New $editWebNew1 -UseBom $true
Write-Host ""
Write-Host "[2/8] Migrasi & load data per-rank di mount() ..." -ForegroundColor Yellow
$editWebOld2 = @'
        $testimoniData = HomeSection::dataFor('testimoni', ['bg_color' => null, 'card_color' => null]);
        $this->testimoniUseCustomBg = filled($testimoniData['bg_color']);
        $this->testimoniBgColor = $testimoniData['bg_color'] ?: $this->testimoniBgColor;
        $this->testimoniUseCustomCardColor = filled($testimoniData['card_color']);
        $this->testimoniCardColor = $testimoniData['card_color'] ?: $this->testimoniCardColor;
    }
'@

$editWebNew2 = @'
        $testimoniData = HomeSection::dataFor('testimoni', [
            'bg_color' => null,
            'card_color' => null, // skema lama (1 warna utk ketiga kartu) -- dipakai cuma buat migrasi di bawah
            'card_colors' => [1 => null, 2 => null, 3 => null],
        ]);
        $this->testimoniUseCustomBg = filled($testimoniData['bg_color']);
        $this->testimoniBgColor = $testimoniData['bg_color'] ?: $this->testimoniBgColor;

        $testimoniCardColors = $testimoniData['card_colors'] ?? [1 => null, 2 => null, 3 => null];

        // Migrasi data lama: dulu 1 warna dipakai utk ketiga kartu sekaligus.
        // Kalau skema baru (per-rank) belum pernah disimpan sama sekali tapi
        // ada data lama, isi ketiga rank dengan warna lama itu sebagai awalan.
        if (filled($testimoniData['card_color']) && ! array_filter($testimoniCardColors)) {
            $testimoniCardColors = [1 => $testimoniData['card_color'], 2 => $testimoniData['card_color'], 3 => $testimoniData['card_color']];
        }

        foreach ([1, 2, 3] as $rank) {
            $this->testimoniUseCustomCardColor[$rank] = filled($testimoniCardColors[$rank] ?? null);
            $this->testimoniCardColor[$rank] = $testimoniCardColors[$rank] ?: $this->testimoniCardColor[$rank];
        }
    }
'@

Replace-ExactlyOnce -Path $editWebPath -Old $editWebOld2 -New $editWebNew2 -UseBom $true
Write-Host ""
Write-Host "[3/8] Update selectTestimoniCardPreset() terima $rank ..." -ForegroundColor Yellow
$editWebOld3 = @'
    public function selectTestimoniCardPreset(string $hex): void
    {
        $this->testimoniUseCustomCardColor = true;
        $this->testimoniCardColor = $hex;
    }
'@

$editWebNew3 = @'
    public function selectTestimoniCardPreset(int $rank, string $hex): void
    {
        $this->testimoniUseCustomCardColor[$rank] = true;
        $this->testimoniCardColor[$rank] = $hex;
    }
'@

Replace-ExactlyOnce -Path $editWebPath -Old $editWebOld3 -New $editWebNew3 -UseBom $true
Write-Host ""
Write-Host "[4/8] Update saveTestimoni() simpan card_colors per-rank ..." -ForegroundColor Yellow
$editWebOld4 = @'
    public function saveTestimoni(): void
    {
        $validated = $this->validate([
            'testimoniBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        HomeSection::forSection('testimoni')->update([
            'data' => [
                'bg_color' => $this->testimoniUseCustomBg ? $validated['testimoniBgColor'] : null,
                'card_color' => $this->testimoniUseCustomCardColor ? $validated['testimoniCardColor'] : null,
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }
'@

$editWebNew4 = @'
    public function saveTestimoni(): void
    {
        $validated = $this->validate([
            'testimoniBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.1' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.2' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'testimoniCardColor.3' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        HomeSection::forSection('testimoni')->update([
            'data' => [
                'bg_color' => $this->testimoniUseCustomBg ? $validated['testimoniBgColor'] : null,
                'card_color' => null, // skema lama sudah tidak dipakai lagi, dikosongkan
                'card_colors' => [
                    1 => $this->testimoniUseCustomCardColor[1] ? $validated['testimoniCardColor'][1] : null,
                    2 => $this->testimoniUseCustomCardColor[2] ? $validated['testimoniCardColor'][2] : null,
                    3 => $this->testimoniUseCustomCardColor[3] ? $validated['testimoniCardColor'][3] : null,
                ],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }
'@

Replace-ExactlyOnce -Path $editWebPath -Old $editWebOld4 -New $editWebNew4 -UseBom $true
Write-Host ""
Write-Host "[5/8] Update teks deskripsi form testimoni ..." -ForegroundColor Yellow
$editWebOld5 = @'
                            lewat menu Testimoni (nama, foto, rating, dan komentar ambil otomatis dari situ,
                            jadi tidak diedit di sini). Yang bisa diatur cuma warna latar section dan warna
                            kartu Top 3 Komentar-nya.
'@

$editWebNew5 = @'
                            lewat menu Testimoni (nama, foto, rating, dan komentar ambil otomatis dari situ,
                            jadi tidak diedit di sini). Yang bisa diatur cuma warna latar section dan warna
                            tiap kartu Top 3 Komentar (Top 1, Top 2, Top 3, bisa beda-beda).
'@

Replace-ExactlyOnce -Path $editWebPath -Old $editWebOld5 -New $editWebNew5 -UseBom $true
Write-Host ""
Write-Host "[6/8] Ganti markup 1 color-picker jadi 3 blok (Top 1/2/3) ..." -ForegroundColor Yellow
$editWebOld6 = @'
                    {{-- WARNA TOP 3 KOMENTAR (KARTU TESTIMONI) --}}
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Top 3 Komentar (Kartu Testimoni)</p>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="testimoniUseCustomCardColor" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna kartu khusus
                            </label>
                            <input
                                type="color" wire:model="testimoniCardColor"
                                @disabled(! $testimoniUseCustomCardColor)
                                class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                            >
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($testimoniCardPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectTestimoniCardPreset('{{ $preset['value'] }}')"
                                        title="{{ $preset['label'] }}"
                                        class="group flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                            {{ $testimoniUseCustomCardColor && strtoupper($testimoniCardColor) === $preset['value']
                                                ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                            style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                        >
                                            @if ($testimoniUseCustomCardColor && strtoupper($testimoniCardColor) === $preset['value'])
                                                <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                            @endif
                                        </span>
                                        <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-admin-ink-soft">
                            Kalau tidak dicentang, ketiga kartu ikut warna bergantian gelap/krem seperti
                            biasa. Kalau dicentang, KETIGA kartu jadi satu warna yang sama persis (bukan
                            bergantian lagi) -- warna nama, komentar, dan badge produk otomatis menyesuaikan
                            (terang/gelap) supaya tetap kebaca.
                        </p>
                    </div>
'@

$editWebNew6 = @'
                    {{-- WARNA TOP 3 KOMENTAR (KARTU TESTIMONI) --}}
                    {{-- Tiap posisi (Top 1/2/3) independen -- boleh isi salah satu, semua, atau tidak
                    sama sekali. Posisi yang tidak dicentang otomatis fallback ke pola bawaan
                    (bergantian gelap/krem berdasarkan posisi kartu). --}}
                    @php
                        $testimoniCardRankLabels = [1 => 'Top 1', 2 => 'Top 2', 3 => 'Top 3'];
                    @endphp
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Top 3 Komentar (Kartu Testimoni)</p>
                            <p class="mt-1 text-xs text-admin-ink-soft">
                                Atur warna kartu per posisi. Posisi yang tidak dicentang ikut warna
                                bergantian gelap/krem seperti biasa -- warna nama, komentar, dan badge
                                produk otomatis menyesuaikan (terang/gelap) di kartu yang dicentang.
                            </p>
                        </div>

                        <div class="space-y-5 divide-y divide-admin-border">
                            @foreach ($testimoniCardRankLabels as $rank => $rankLabel)
                                <div class="{{ $loop->first ? '' : 'pt-5' }} space-y-3">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <label class="flex items-center gap-2 text-sm text-admin-ink">
                                            <input type="checkbox" wire:model.live="testimoniUseCustomCardColor.{{ $rank }}" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                            <span class="rounded-full bg-admin-cream px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-admin-ink-soft">{{ $rankLabel }}</span>
                                            Pakai warna kartu khusus
                                        </label>
                                        <input
                                            type="color" wire:model="testimoniCardColor.{{ $rank }}"
                                            @disabled(! $testimoniUseCustomCardColor[$rank])
                                            class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                    </div>

                                    <div>
                                        <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-admin-ink-soft">Atau pilih dari rekomendasi</p>
                                        <div class="flex flex-wrap gap-2.5">
                                            @foreach ($testimoniCardPresets as $preset)
                                                <button
                                                    type="button"
                                                    wire:click="selectTestimoniCardPreset({{ $rank }}, '{{ $preset['value'] }}')"
                                                    title="{{ $preset['label'] }}"
                                                    class="group flex flex-col items-center gap-1"
                                                >
                                                    <span
                                                        class="flex h-9 w-9 items-center justify-center rounded-full shadow-sm ring-1 ring-inset ring-white/40 transition duration-200 group-hover:scale-110 group-hover:shadow-md
                                                        {{ $testimoniUseCustomCardColor[$rank] && strtoupper($testimoniCardColor[$rank]) === $preset['value']
                                                            ? 'border-2 border-admin-accent ring-2 ring-admin-accent ring-offset-2 ring-offset-admin-surface'
                                                            : 'border border-admin-border group-hover:border-admin-accent/60' }}"
                                                        style="background: linear-gradient(135deg, color-mix(in oklab, {{ $preset['value'] }} 100%, white 30%), {{ $preset['value'] }} 55%, color-mix(in oklab, {{ $preset['value'] }} 100%, black 16%));"
                                                    >
                                                        @if ($testimoniUseCustomCardColor[$rank] && strtoupper($testimoniCardColor[$rank]) === $preset['value'])
                                                            <i class="fa-solid fa-check text-xs drop-shadow-sm" style="color: {{ $preset['check'] }};"></i>
                                                        @endif
                                                    </span>
                                                    <span class="max-w-14 truncate text-[10px] text-admin-ink-soft">{{ $preset['label'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
'@

Replace-ExactlyOnce -Path $editWebPath -Old $editWebOld6 -New $editWebNew6 -UseBom $true
Write-Host ""
# ------------------------------------------------------------
# FILE 2: resources/views/partials/frontend/testimonials.blade.php
# (file ini TIDAK ada BOM -- pertahankan, UseBom = $false)
# ------------------------------------------------------------
$testimonialsPath = "resources\views\partials\frontend\testimonials.blade.php"

Write-Host "[7/8] Baca card_colors per posisi (bukan 1 card_color) ..." -ForegroundColor Yellow
$testiOld1 = @'
    // Warna latar section & warna kartu "Top 3 Komentar" bisa diatur admin
    // lewat Edit Web > Testimoni Pelanggan (lihat pages/admin/edit-web.blade.php)
    // -- data testimoninya sendiri TIDAK diedit dari sana, tetap dipilih
    // lewat menu Testimoni seperti biasa. Data warna tersimpan di
    // home_sections, section_key 'testimoni'. Pola sama dengan
    // partials/frontend/products.blade.php & categories.blade.php.
    $testimoniSection = \App\Models\HomeSection::dataFor('testimoni', ['bg_color' => null, 'card_color' => null]);
    $testimoniBgColor = $testimoniSection['bg_color'] ?: '#FAF8F4';

    // null = admin belum pilih warna kartu sendiri -> pakai desain bawaan
    // (kartu bergantian gelap/krem berdasarkan posisi, seperti sebelumnya).
    // Kalau admin sudah pilih, SEMUA kartu pakai warna yang sama persis.
    $testimoniCardColor = $testimoniSection['card_color'];

    $testimoniCardIsDark = null;
    if ($testimoniCardColor) {
        $hex = ltrim($testimoniCardColor, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);
        $testimoniCardIsDark = $luminance <= 0.5;
    }
@endphp
'@

$testiNew1 = @'
    // Warna latar section & warna kartu "Top 3 Komentar" bisa diatur admin
    // lewat Edit Web > Testimoni Pelanggan (lihat pages/admin/edit-web.blade.php)
    // -- data testimoninya sendiri TIDAK diedit dari sana, tetap dipilih
    // lewat menu Testimoni seperti biasa. Data warna tersimpan di
    // home_sections, section_key 'testimoni'. Pola sama dengan
    // partials/frontend/products.blade.php & categories.blade.php.
    $testimoniSection = \App\Models\HomeSection::dataFor('testimoni', [
        'bg_color' => null,
        'card_colors' => [1 => null, 2 => null, 3 => null],
    ]);
    $testimoniBgColor = $testimoniSection['bg_color'] ?: '#FAF8F4';

    // Warna kartu per posisi (Top 1/2/3), masing-masing independen & opsional.
    // null di posisi tertentu = admin belum pilih warna khusus utk kartu itu
    // -> kartu itu pakai desain bawaan (bergantian gelap/krem berdasarkan
    // posisi, seperti sebelumnya). Posisi lain yang sudah dipilih tetap
    // pakai warnanya masing-masing, tidak saling mempengaruhi.
    $testimoniCardColors = $testimoniSection['card_colors'] ?? [1 => null, 2 => null, 3 => null];
@endphp
'@

Replace-ExactlyOnce -Path $testimonialsPath -Old $testiOld1 -New $testiNew1 -UseBom $false
Write-Host ""
Write-Host "[8/8] Terapkan warna per-rank di loop kartu + fallback pola lama ..." -ForegroundColor Yellow
$testiOld2 = @'
                @foreach ($testimonialList as $index => $testimonial)
                    @php $isDark = $testimoniCardColor ? $testimoniCardIsDark : ($index % 3 !== 1); @endphp
                    <div
                        x-data="{ shown: false }"
                        x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { setTimeout(() => shown = true, {{ ($index % 3) * 100 }}); } }, { threshold: 0.15 }).observe($el)"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="flex flex-col rounded-3xl p-6 shadow-sm transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-xl
                            {{ $testimoniCardColor
                                ? ($isDark ? 'text-white' : 'text-admin-ink')
                                : ($isDark ? 'bg-admin-panel text-white' : 'bg-admin-cream text-admin-ink') }}"
                        @if ($testimoniCardColor) style="background: {{ $testimoniCardColor }};" @endif
                    >
'@

$testiNew2 = @'
                @foreach ($testimonialList as $index => $testimonial)
                    @php
                        // Rank 1 = kartu pertama (Top 1), dst -- cocok dengan urutan
                        // Top 1/2/3 di form Edit Web.
                        $rank = $index + 1;
                        $customCardColor = $testimoniCardColors[$rank] ?? null;

                        if ($customCardColor) {
                            $hex = ltrim($customCardColor, '#');
                            $r = hexdec(substr($hex, 0, 2)) / 255;
                            $g = hexdec(substr($hex, 2, 2)) / 255;
                            $b = hexdec(substr($hex, 4, 2)) / 255;
                            $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
                            $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);
                            $isDark = $luminance <= 0.5;
                        } else {
                            // Fallback: pola bawaan bergantian gelap/krem berdasarkan posisi.
                            $isDark = $index % 3 !== 1;
                        }
                    @endphp
                    <div
                        x-data="{ shown: false }"
                        x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { setTimeout(() => shown = true, {{ ($index % 3) * 100 }}); } }, { threshold: 0.15 }).observe($el)"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="flex flex-col rounded-3xl p-6 shadow-sm transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-xl
                            {{ $customCardColor
                                ? ($isDark ? 'text-white' : 'text-admin-ink')
                                : ($isDark ? 'bg-admin-panel text-white' : 'bg-admin-cream text-admin-ink') }}"
                        @if ($customCardColor) style="background: {{ $customCardColor }};" @endif
                    >
'@

Replace-ExactlyOnce -Path $testimonialsPath -Old $testiOld2 -New $testiNew2 -UseBom $false
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai. Cek di browser (php artisan serve):" -ForegroundColor Cyan
Write-Host " Admin > Edit Web > Testimoni Pelanggan -- section" -ForegroundColor Cyan
Write-Host " 'Warna Top 3 Komentar' sekarang punya 3 color" -ForegroundColor Cyan
Write-Host " picker terpisah (Top 1 / Top 2 / Top 3)." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
