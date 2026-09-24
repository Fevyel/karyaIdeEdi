$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$admin = ".\resources\views\pages\admin\edit-web.blade.php"
$front = ".\resources\views\pages\frontend\keberlanjutan.blade.php"

if (-not (Test-Path $admin)) { throw "File tidak ditemukan: $admin" }
if (-not (Test-Path $front)) { throw "File tidak ditemukan: $front" }

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-sustainability-edit-web-$stamp"

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Sustainability - Edit Web + Sinkronisasi Frontend" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/5] Membuat backup ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\admin" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
Copy-Item $admin "$backupDir\resources\views\pages\admin\edit-web.blade.php" -Force
Copy-Item $front "$backupDir\resources\views\pages\frontend\keberlanjutan.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Menambahkan card + 2 tab Sustainability di Edit Web ..."
$tmp = Join-Path $env:TEMP "patch-sustainability-edit-web-$stamp.php"

@'
<?php
$root = getcwd();
$adminPath = $root.'/resources/views/pages/admin/edit-web.blade.php';
$frontPath = $root.'/resources/views/pages/frontend/keberlanjutan.blade.php';
$admin = file_get_contents($adminPath);

function insertBeforeOnce(string $content, string $needle, string $insert, string $label): string {
    if (str_contains($content, trim($insert))) return $content;
    $pos = strpos($content, $needle);
    if ($pos === false) throw new RuntimeException("Anchor tidak ditemukan: $label");
    return substr($content,0,$pos).$insert.substr($content,$pos);
}
function addMatchLine(string $content, string $anchorLine, string $newLine, string $label): string {
    if (str_contains($content, $newLine)) return $content;
    $pos = strpos($content, $anchorLine);
    if ($pos === false) throw new RuntimeException("Match anchor tidak ditemukan: $label");
    return substr($content,0,$pos).$newLine.substr($content,$pos);
}

$sections = <<<'BLADE'
    /** Dua section halaman Sustainability: Hero + Prinsip Sustainability. */
    public array $sustainabilitySections = [
        ['key' => 'sustainability-hero', 'label' => 'Hero', 'icon' => 'fa-leaf', 'ready' => true],
        ['key' => 'sustainability-points', 'label' => 'Prinsip Sustainability', 'icon' => 'fa-seedling', 'ready' => true],
    ];

BLADE;
$admin = insertBeforeOnce($admin, '    public array $ourCraftsmenSections = [', $sections, 'sebelum ourCraftsmenSections');

$props = <<<'BLADE'
    // ================= SUSTAINABILITY =================

    public bool $sustainabilityHeroUseCustomBg = false;
    public string $sustainabilityHeroBgColor = '#F9F7F2';
    public string $sustainabilityHeroEyebrow = 'Sustainability';
    public string $sustainabilityHeroHeading = 'Kualitas yang Dibuat untuk Bertahan';
    public string $sustainabilityHeroDescription = 'Bagi kami, cara paling nyata untuk mengurangi limbah adalah membuat furnitur yang benar-benar awet dan tidak perlu cepat diganti — dikerjakan sesuai pesanan, dari bahan yang dipilih dengan hati-hati.';
    public array $sustainabilityHeroBgPresets = \App\Support\ColorPalette::PRESETS;

    public bool $sustainabilityPointsUseCustomBg = false;
    public string $sustainabilityPointsBgColor = '#FFFFFF';
    public array $sustainabilityPoints = [];
    public string $sustainabilityNote = 'Catatan: halaman ini menjelaskan prinsip kerja kami secara umum. Kami akan memperbarui halaman ini kalau ke depannya ada praktik atau sertifikasi keberlanjutan yang lebih spesifik untuk ditampilkan.';
    public array $sustainabilityPointsBgPresets = \App\Support\ColorPalette::PRESETS;
    public array $sustainabilityIconOptions = [
        'fa-tree', 'fa-ruler-combined', 'fa-hammer', 'fa-couch',
        'fa-leaf', 'fa-seedling', 'fa-recycle', 'fa-screwdriver-wrench',
    ];

    private function sustainabilityHeroDefaults(): array
    {
        return [
            'bg_color' => null,
            'eyebrow' => 'Sustainability',
            'heading' => 'Kualitas yang Dibuat untuk Bertahan',
            'description' => 'Bagi kami, cara paling nyata untuk mengurangi limbah adalah membuat furnitur yang benar-benar awet dan tidak perlu cepat diganti — dikerjakan sesuai pesanan, dari bahan yang dipilih dengan hati-hati.',
        ];
    }

    private function sustainabilityPointsDefaults(): array
    {
        return [
            'bg_color' => null,
            'items' => [
                ['icon' => 'fa-tree', 'title' => 'Bahan Baku Pilihan', 'text' => 'Setiap kayu diseleksi manual sebelum masuk proses produksi, supaya hasil akhirnya kuat dan awet dipakai bertahun-tahun.'],
                ['icon' => 'fa-ruler-combined', 'title' => 'Dibuat Sesuai Pesanan', 'text' => 'Produk custom dikerjakan sesuai ukuran & kebutuhan pemesan — mengurangi kelebihan stok dan sisa bahan yang terbuang percuma.'],
                ['icon' => 'fa-hammer', 'title' => 'Dikerjakan Tangan, Bukan Massal', 'text' => 'Diproses langsung oleh tukang kayu berpengalaman, bukan produksi pabrik — sehingga tiap detail bisa diperiksa satu per satu.'],
                ['icon' => 'fa-couch', 'title' => 'Furnitur untuk Jangka Panjang', 'text' => 'Kami merancang furnitur yang tahan lama secara struktur, bukan sekadar tampilan — supaya lebih jarang perlu diganti.'],
            ],
            'note' => 'Catatan: halaman ini menjelaskan prinsip kerja kami secara umum. Kami akan memperbarui halaman ini kalau ke depannya ada praktik atau sertifikasi keberlanjutan yang lebih spesifik untuk ditampilkan.',
        ];
    }

BLADE;
$admin = insertBeforeOnce($admin, '    public bool $ourCraftsmenHeroUseCustomBg = false;', $props, 'sebelum properti Our Craftsmen');

if (!str_contains($admin, "'key' => 'sustainability'")) {
    $group = <<<'BLADE'
        [
            'key' => 'sustainability',
            'label' => 'Sustainability',
            'icon' => 'fa-leaf',
            'description' => 'Hero dan Prinsip Sustainability.',
            'ready' => true,
        ],
BLADE;
    $marker = "        [\n            'key' => 'our-craftsmen',";
    $admin = insertBeforeOnce($admin, $marker, $group, 'group Sustainability');
}

$admin = addMatchLine($admin, "            'our-craftsmen' => \$this->ourCraftsmenSections[0]['key'],\n", "            'sustainability' => \$this->sustainabilitySections[0]['key'],\n", 'selectGroup sustainability');
$admin = addMatchLine($admin, "                'our-craftsmen' => \$ourCraftsmenSections,\n", "                'sustainability' => \$sustainabilitySections,\n", 'activeGroupSections sustainability');
$admin = addMatchLine($admin, "                'our-craftsmen' => 'Our Craftsmen',\n", "                'sustainability' => 'Sustainability',\n", 'activeGroupLabel sustainability');

$mount = <<<'BLADE'
        $sustainHeroData = HomeSection::dataFor('sustainability-hero', $this->sustainabilityHeroDefaults());
        $this->sustainabilityHeroUseCustomBg = filled($sustainHeroData['bg_color'] ?? null);
        $this->sustainabilityHeroBgColor = $sustainHeroData['bg_color'] ?: $this->sustainabilityHeroBgColor;
        $this->sustainabilityHeroEyebrow = (string) $sustainHeroData['eyebrow'];
        $this->sustainabilityHeroHeading = (string) $sustainHeroData['heading'];
        $this->sustainabilityHeroDescription = (string) $sustainHeroData['description'];

        $sustainPointsData = HomeSection::dataFor('sustainability-points', $this->sustainabilityPointsDefaults());
        $this->sustainabilityPointsUseCustomBg = filled($sustainPointsData['bg_color'] ?? null);
        $this->sustainabilityPointsBgColor = $sustainPointsData['bg_color'] ?: $this->sustainabilityPointsBgColor;
        $this->sustainabilityPoints = array_values(is_array($sustainPointsData['items'] ?? null) ? $sustainPointsData['items'] : $this->sustainabilityPointsDefaults()['items']);
        $this->sustainabilityNote = (string) ($sustainPointsData['note'] ?? $this->sustainabilityPointsDefaults()['note']);

BLADE;
$admin = insertBeforeOnce($admin, '        $ourCraftsmenHeroDefaults = $this->ourCraftsmenHeroDefaults();', $mount, 'mount Sustainability');

$saves = <<<'BLADE'
    public function saveSustainabilityHero(): void
    {
        $validated = $this->validate([
            'sustainabilityHeroBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sustainabilityHeroEyebrow' => ['required', 'string', 'max:40'],
            'sustainabilityHeroHeading' => ['required', 'string', 'max:100'],
            'sustainabilityHeroDescription' => ['required', 'string', 'max:500'],
        ]);

        HomeSection::forSection('sustainability-hero')->update([
            'data' => [
                'bg_color' => $this->sustainabilityHeroUseCustomBg ? $validated['sustainabilityHeroBgColor'] : null,
                'eyebrow' => $validated['sustainabilityHeroEyebrow'],
                'heading' => $validated['sustainabilityHeroHeading'],
                'description' => $validated['sustainabilityHeroDescription'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

    public function saveSustainabilityPoints(): void
    {
        $validated = $this->validate([
            'sustainabilityPointsBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sustainabilityPoints' => ['required', 'array', 'size:4'],
            'sustainabilityPoints.*.icon' => ['required', 'string', 'in:'.implode(',', $this->sustainabilityIconOptions)],
            'sustainabilityPoints.*.title' => ['required', 'string', 'max:80'],
            'sustainabilityPoints.*.text' => ['required', 'string', 'max:300'],
            'sustainabilityNote' => ['required', 'string', 'max:500'],
        ]);

        HomeSection::forSection('sustainability-points')->update([
            'data' => [
                'bg_color' => $this->sustainabilityPointsUseCustomBg ? $validated['sustainabilityPointsBgColor'] : null,
                'items' => array_values($validated['sustainabilityPoints']),
                'note' => $validated['sustainabilityNote'],
            ],
        ]);

        session()->flash('edit-web-tersimpan', true);
    }

BLADE;
$admin = insertBeforeOnce($admin, '    public function saveOurCraftsmenHero(): void', $saves, 'save Sustainability');

$presets = <<<'BLADE'
    public function selectSustainabilityHeroBgPreset(string $hex): void
    {
        $this->sustainabilityHeroUseCustomBg = true;
        $this->sustainabilityHeroBgColor = $hex;
    }

    public function selectSustainabilityPointsBgPreset(string $hex): void
    {
        $this->sustainabilityPointsUseCustomBg = true;
        $this->sustainabilityPointsBgColor = $hex;
    }

BLADE;
$admin = insertBeforeOnce($admin, '    public function selectMissionBgPreset(string $hex): void', $presets, 'preset Sustainability');

$ui = <<<'BLADE'
            @elseif ($activeSection === 'sustainability-hero')
                <form wire:submit="saveSustainabilityHero" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-leaf text-admin-accent"></i>
                            Hero Sustainability
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Terhubung langsung ke section paling atas halaman Sustainability (/keberlanjutan).</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="sustainabilityHeroUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input type="color" wire:model="sustainabilityHeroBgColor" @disabled(! $sustainabilityHeroUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($sustainabilityHeroBgPresets as $preset)
                                <button type="button" wire:click="selectSustainabilityHeroBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-border shadow-sm" style="background: {{ $preset['value'] }};">
                                    @if ($sustainabilityHeroUseCustomBg && strtoupper($sustainabilityHeroBgColor) === $preset['value'])<i class="fa-solid fa-check text-xs" style="color: {{ $preset['check'] }};"></i>@endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Hero</p>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label>
                            <input type="text" maxlength="40" wire:model="sustainabilityHeroEyebrow" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('sustainabilityHeroEyebrow')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                            <input type="text" maxlength="100" wire:model="sustainabilityHeroHeading" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                            @error('sustainabilityHeroHeading')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                            <textarea rows="5" maxlength="500" wire:model="sustainabilityHeroDescription" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                            @error('sustainabilityHeroDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Hero</button></div>
                </form>

            @elseif ($activeSection === 'sustainability-points')
                <form wire:submit="saveSustainabilityPoints" class="space-y-6">
                    <div>
                        <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-admin-ink">
                            <i class="fa-solid fa-seedling text-admin-accent"></i>
                            Prinsip Sustainability
                        </h3>
                        <p class="text-xs text-admin-ink-soft">Mengatur empat kartu prinsip dan catatan di bawahnya. Semua field terhubung langsung ke halaman Sustainability.</p>
                    </div>

                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="sustainabilityPointsUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna latar khusus
                            </label>
                            <input type="color" wire:model="sustainabilityPointsBgColor" @disabled(! $sustainabilityPointsUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($sustainabilityPointsBgPresets as $preset)
                                <button type="button" wire:click="selectSustainabilityPointsBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-admin-border shadow-sm" style="background: {{ $preset['value'] }};">
                                    @if ($sustainabilityPointsUseCustomBg && strtoupper($sustainabilityPointsBgColor) === $preset['value'])<i class="fa-solid fa-check text-xs" style="color: {{ $preset['check'] }};"></i>@endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($sustainabilityPoints as $i => $point)
                            <div class="space-y-3 rounded-xl border border-admin-border p-4">
                                <div class="flex items-center justify-between"><p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Kartu {{ $i + 1 }}</p><i class="fa-solid {{ $point['icon'] ?? 'fa-leaf' }} text-admin-accent"></i></div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Ikon</label>
                                    <select wire:model="sustainabilityPoints.{{ $i }}.icon" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                                        @foreach ($sustainabilityIconOptions as $icon)<option value="{{ $icon }}">{{ str_replace('fa-', '', $icon) }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Judul</label>
                                    <input type="text" maxlength="80" wire:model="sustainabilityPoints.{{ $i }}.title" class="w-full rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Deskripsi</label>
                                    <textarea rows="4" maxlength="300" wire:model="sustainabilityPoints.{{ $i }}.text" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-xl border border-admin-border p-4">
                        <label class="mb-1.5 block text-sm font-medium text-admin-ink">Catatan bawah</label>
                        <textarea rows="4" maxlength="500" wire:model="sustainabilityNote" class="w-full resize-none rounded-lg border border-admin-border bg-admin-surface px-3 py-2.5 text-sm text-admin-ink"></textarea>
                        @error('sustainabilityNote')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end"><button type="submit" class="rounded-full bg-admin-panel px-6 py-3 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-2 text-xs"></i>Simpan Prinsip</button></div>
                </form>

BLADE;
$admin = insertBeforeOnce($admin, "            @elseif (\$activeSection === 'our-craftsmen-hero')", $ui, 'UI Sustainability');

file_put_contents($adminPath,$admin);

$front = <<<'BLADE'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sustainability | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $sustainHero = \App\Models\HomeSection::dataFor('sustainability-hero', [
            'bg_color' => null,
            'eyebrow' => 'Sustainability',
            'heading' => 'Kualitas yang Dibuat untuk Bertahan',
            'description' => 'Bagi kami, cara paling nyata untuk mengurangi limbah adalah membuat furnitur yang benar-benar awet dan tidak perlu cepat diganti — dikerjakan sesuai pesanan, dari bahan yang dipilih dengan hati-hati.',
        ]);
        $sustainPoints = \App\Models\HomeSection::dataFor('sustainability-points', [
            'bg_color' => null,
            'items' => [
                ['icon' => 'fa-tree', 'title' => 'Bahan Baku Pilihan', 'text' => 'Setiap kayu diseleksi manual sebelum masuk proses produksi, supaya hasil akhirnya kuat dan awet dipakai bertahun-tahun.'],
                ['icon' => 'fa-ruler-combined', 'title' => 'Dibuat Sesuai Pesanan', 'text' => 'Produk custom dikerjakan sesuai ukuran & kebutuhan pemesan — mengurangi kelebihan stok dan sisa bahan yang terbuang percuma.'],
                ['icon' => 'fa-hammer', 'title' => 'Dikerjakan Tangan, Bukan Massal', 'text' => 'Diproses langsung oleh tukang kayu berpengalaman, bukan produksi pabrik — sehingga tiap detail bisa diperiksa satu per satu.'],
                ['icon' => 'fa-couch', 'title' => 'Furnitur untuk Jangka Panjang', 'text' => 'Kami merancang furnitur yang tahan lama secara struktur, bukan sekadar tampilan — supaya lebih jarang perlu diganti.'],
            ],
            'note' => 'Catatan: halaman ini menjelaskan prinsip kerja kami secara umum. Kami akan memperbarui halaman ini kalau ke depannya ada praktik atau sertifikasi keberlanjutan yang lebih spesifik untuk ditampilkan.',
        ]);

        $contrast = function (?string $hex, bool $defaultLight = true): array {
            $hex = $hex ?: ($defaultLight ? '#F9F7F2' : '#FFFFFF');
            $h = ltrim($hex, '#');
            if (! preg_match('/^[0-9A-Fa-f]{6}$/', $h)) $h = 'F9F7F2';
            $r = hexdec(substr($h,0,2)); $g = hexdec(substr($h,2,2)); $b = hexdec(substr($h,4,2));
            $lum = (0.2126*$r + 0.7152*$g + 0.0722*$b) / 255;
            $dark = $lum < .48;
            return $dark
                ? ['title'=>'#FFFFFF','body'=>'rgba(255,255,255,.76)','accent'=>'#E5B878','card'=>'rgba(255,255,255,.07)','border'=>'rgba(255,255,255,.14)','iconbg'=>'rgba(255,255,255,.12)']
                : ['title'=>'#1A1A1A','body'=>'#6B6E76','accent'=>'#A66D32','card'=>'#FFFFFF','border'=>'rgba(26,26,26,.10)','iconbg'=>'#F6ECDB'];
        };

        $heroBg = $sustainHero['bg_color'] ?: '#F9F7F2';
        $pointsBg = $sustainPoints['bg_color'] ?: '#FFFFFF';
        $heroColors = $contrast($heroBg);
        $pointsColors = $contrast($pointsBg, false);
    @endphp

    <section class="relative overflow-hidden" style="background: {{ $heroBg }};">
        <div class="mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em]" style="color: {{ $heroColors['accent'] }};">
                <span class="h-px w-8" style="background: {{ $heroColors['accent'] }};"></span>
                {{ $sustainHero['eyebrow'] }}
                <span class="h-px w-8" style="background: {{ $heroColors['accent'] }};"></span>
            </div>
            <h1 class="mt-4 font-display text-4xl font-semibold leading-tight sm:text-5xl" style="color: {{ $heroColors['title'] }};">
                {{ $sustainHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed sm:text-base" style="color: {{ $heroColors['body'] }};">
                {{ $sustainHero['description'] }}
            </p>
        </div>
    </section>

    <section style="background: {{ $pointsBg }};">
        <div class="mx-auto max-w-5xl px-6 py-14 sm:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                @foreach ($sustainPoints['items'] as $point)
                    <div class="flex items-start gap-4 rounded-2xl p-6" style="background: {{ $pointsColors['card'] }}; border: 1px solid {{ $pointsColors['border'] }};">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" style="background: {{ $pointsColors['iconbg'] }}; color: {{ $pointsColors['accent'] }};">
                            <i class="fa-solid {{ $point['icon'] }}"></i>
                        </span>
                        <div>
                            <h3 class="font-display text-lg font-semibold" style="color: {{ $pointsColors['title'] }};">{{ $point['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed" style="color: {{ $pointsColors['body'] }};">{{ $point['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mx-auto mt-10 max-w-2xl text-center text-xs leading-relaxed" style="color: {{ $pointsColors['body'] }};">
                {{ $sustainPoints['note'] }}
            </p>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
BLADE;
file_put_contents($frontPath,$front);

echo "OK\n";

'@ | Set-Content -Path $tmp -Encoding UTF8

php $tmp
Remove-Item $tmp -Force

Step "[3/5] Validasi syntax PHP ..."
php -l $admin | Out-Host
php -l $front | Out-Host

Step "[4/5] Bersihkan cache view ..."
php artisan view:clear | Out-Host

Step "[5/5] Selesai ..."
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Edit Web sekarang punya card: Sustainability" -ForegroundColor Yellow
Write-Host "  - Hero" -ForegroundColor White
Write-Host "  - Prinsip Sustainability" -ForegroundColor White
Write-Host ""
Write-Host "Kedua tab aktif dan terhubung langsung ke /keberlanjutan." -ForegroundColor Green
Write-Host "Tidak ada migration/database schema baru." -ForegroundColor DarkGray
