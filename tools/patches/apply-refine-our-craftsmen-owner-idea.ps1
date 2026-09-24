$ErrorActionPreference = 'Stop'

function Step($message) {
    Write-Host "`n$message" -ForegroundColor Cyan
}

if (-not (Test-Path '.\artisan')) {
    throw "File artisan tidak ditemukan. Jalankan script dari root project: C:\xampp\htdocs\karyaIdeEdi"
}

$adminPath = '.\resources\views\pages\admin\edit-web.blade.php'
$frontPath = '.\resources\views\pages\frontend\pengrajin.blade.php'

if (-not (Test-Path $adminPath)) { throw "File $adminPath tidak ditemukan." }
if (-not (Test-Path $frontPath)) { throw "File $frontPath tidak ditemukan." }

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupDir = ".backup-our-craftsmen-refine-$stamp"

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Refine Our Craftsmen: Owner + Mulai dari Sebuah Ide' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

Step '[1/6] Backup file ...'
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\admin" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
Copy-Item $adminPath "$backupDir\resources\views\pages\admin\edit-web.blade.php" -Force
Copy-Item $frontPath "$backupDir\resources\views\pages\frontend\pengrajin.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step '[2/6] Patch Admin Edit Web + frontend ...'
$tmp = Join-Path $env:TEMP "patch-our-craftsmen-refine-$stamp.php"

@'
<?php
$root = getcwd();
$adminPath = $root . DIRECTORY_SEPARATOR . 'resources/views/pages/admin/edit-web.blade.php';
$frontPath = $root . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/pengrajin.blade.php';

$admin = file_get_contents($adminPath);
$front = file_get_contents($frontPath);
if ($admin === false || $front === false) {
    throw new RuntimeException('Gagal membaca file target.');
}

function replaceOne(string $text, string $old, string $new, string $label): string
{
    if (strpos($text, $old) === false) {
        if (strpos($text, $new) !== false) {
            echo "  - {$label}: sudah sesuai\n";
            return $text;
        }
        throw new RuntimeException("Bagian '{$label}' tidak ditemukan. Script berhenti supaya tidak merusak file.");
    }

    echo "  - {$label}: diperbarui\n";
    return str_replace($old, $new, $text);
}

// ============================================================
// ADMIN: nama section
// ============================================================
$admin = replaceOne(
    $admin,
    "        ['key' => 'our-craftsmen-daftar', 'label' => 'Profil Pemilik', 'icon' => 'fa-user-tie', 'ready' => true],",
    "        ['key' => 'our-craftsmen-daftar', 'label' => 'Pemilik & Founder', 'icon' => 'fa-user-tie', 'ready' => true],",
    'label Pemilik & Founder'
);

$admin = replaceOne(
    $admin,
    "        ['key' => 'our-craftsmen-cta', 'label' => 'CTA', 'icon' => 'fa-bullhorn', 'ready' => true],",
    "        ['key' => 'our-craftsmen-cta', 'label' => 'Mulai dari Sebuah Ide', 'icon' => 'fa-lightbulb', 'ready' => true],",
    'label Mulai dari Sebuah Ide'
);

$admin = str_replace(
    "            'description' => 'Hero, Profil Pemilik, CTA.',",
    "            'description' => 'Hero, Pemilik & Founder, Mulai dari Sebuah Ide.',",
    $admin
);

// ============================================================
// ADMIN: properti warna backframe
// ============================================================
if (strpos($admin, 'public bool $ourCraftsmenOwnerUseCustomBg') === false) {
    $needle = "    public string \$ourCraftsmenOwnerEyebrow = 'Pemilik & Founder';";
    $insert = <<<'BLADE'
    public bool $ourCraftsmenOwnerUseCustomBg = false;

    public string $ourCraftsmenOwnerBgColor = '#F8F5F0';

    public array $ourCraftsmenOwnerBgPresets = \App\Support\ColorPalette::PRESETS;

    public string $ourCraftsmenOwnerEyebrow = 'Pemilik & Founder';
BLADE;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker properti owner tidak ditemukan.');
    $admin = str_replace($needle, $insert, $admin);
    echo "  - properti warna Pemilik & Founder: ditambahkan\n";
}

if (strpos($admin, 'public bool $ourCraftsmenCtaUseCustomBg') === false) {
    $needle = "    public string \$ourCraftsmenCtaEyebrow = 'Mulai dari Sebuah Ide';";
    $insert = <<<'BLADE'
    public bool $ourCraftsmenCtaUseCustomBg = false;

    public string $ourCraftsmenCtaBgColor = '#21140D';

    public array $ourCraftsmenCtaBgPresets = \App\Support\ColorPalette::PRESETS;

    public string $ourCraftsmenCtaEyebrow = 'Mulai dari Sebuah Ide';
BLADE;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker properti CTA tidak ditemukan.');
    $admin = str_replace($needle, $insert, $admin);
    echo "  - properti warna Mulai dari Sebuah Ide: ditambahkan\n";
}

// Tambahkan bg_color ke defaults supaya data lama tetap kompatibel.
if (! preg_match("/private function ourCraftsmenOwnerDefaults\\(\\): array.*?'bg_color'\\s*=>/s", $admin)) {
    $admin = preg_replace(
        "/(private function ourCraftsmenOwnerDefaults\\(\\): array\\s*\\{\\s*return \\[\\s*)(['\\\"]eyebrow['\\\"])/s",
        "$1'bg_color' => null,\\n            $2",
        $admin,
        1
    );
}
if (! preg_match("/private function ourCraftsmenCtaDefaults\\(\\): array.*?'bg_color'\\s*=>/s", $admin)) {
    $admin = preg_replace(
        "/(private function ourCraftsmenCtaDefaults\\(\\): array\\s*\\{\\s*return \\[\\s*)(['\\\"]eyebrow['\\\"])/s",
        "$1'bg_color' => null,\\n            $2",
        $admin,
        1
    );
}

// ============================================================
// ADMIN: load warna saat mount
// ============================================================
if (strpos($admin, '$this->ourCraftsmenOwnerUseCustomBg = filled(') === false) {
    $needle = "        \$ourCraftsmenOwnerData = HomeSection::dataFor('our-craftsmen-daftar', \$this->ourCraftsmenOwnerDefaults());";
    $insert = $needle . <<<'BLADE'

        $this->ourCraftsmenOwnerUseCustomBg = filled($ourCraftsmenOwnerData['bg_color'] ?? null);
        $this->ourCraftsmenOwnerBgColor = $ourCraftsmenOwnerData['bg_color'] ?? $this->ourCraftsmenOwnerBgColor;
BLADE;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker mount owner tidak ditemukan.');
    $admin = str_replace($needle, $insert, $admin);
}

if (strpos($admin, '$this->ourCraftsmenCtaUseCustomBg = filled(') === false) {
    $needle = "        \$ourCraftsmenCtaData = HomeSection::dataFor('our-craftsmen-cta', \$this->ourCraftsmenCtaDefaults());";
    $insert = $needle . <<<'BLADE'

        $this->ourCraftsmenCtaUseCustomBg = filled($ourCraftsmenCtaData['bg_color'] ?? null);
        $this->ourCraftsmenCtaBgColor = $ourCraftsmenCtaData['bg_color'] ?? $this->ourCraftsmenCtaBgColor;
BLADE;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker mount CTA tidak ditemukan.');
    $admin = str_replace($needle, $insert, $admin);
}

// Preset warna: satu klik langsung aktifkan backframe khusus.
if (strpos($admin, 'public function selectOurCraftsmenOwnerBgPreset') === false) {
    $needle = "    public function saveOurCraftsmenOwner(): void\n";
    $method = <<<'BLADE'
    public function selectOurCraftsmenOwnerBgPreset(string $hex): void
    {
        $this->ourCraftsmenOwnerUseCustomBg = true;
        $this->ourCraftsmenOwnerBgColor = $hex;
    }

    public function selectOurCraftsmenCtaBgPreset(string $hex): void
    {
        $this->ourCraftsmenCtaUseCustomBg = true;
        $this->ourCraftsmenCtaBgColor = $hex;
    }

BLADE;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker saveOurCraftsmenOwner tidak ditemukan.');
    $admin = str_replace($needle, $method . $needle, $admin);
}

// ============================================================
// ADMIN: validasi + simpan warna
// ============================================================
if (strpos($admin, "'ourCraftsmenOwnerBgColor' => ['required'") === false) {
    $needle = "            'ourCraftsmenOwnerEyebrow' => ['required', 'string', 'max:50'],";
    $replace = "            'ourCraftsmenOwnerBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],\n" . $needle;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker validasi owner tidak ditemukan.');
    $admin = str_replace($needle, $replace, $admin);
}

if (strpos($admin, "'bg_color' => \$this->ourCraftsmenOwnerUseCustomBg") === false) {
    $needle = "            'data' => [\n                'eyebrow' => \$validated['ourCraftsmenOwnerEyebrow'],";
    $replace = "            'data' => [\n                'bg_color' => \$this->ourCraftsmenOwnerUseCustomBg ? strtoupper(\$validated['ourCraftsmenOwnerBgColor']) : null,\n                'eyebrow' => \$validated['ourCraftsmenOwnerEyebrow'],";
    $pos = strpos($admin, "HomeSection::forSection('our-craftsmen-daftar')->update");
    $sub = substr($admin, $pos);
    if ($pos === false || strpos($sub, $needle) === false) throw new RuntimeException('Marker save owner tidak ditemukan.');
    $sub = str_replace($needle, $replace, $sub);
    $admin = substr($admin, 0, $pos) . $sub;
}

if (strpos($admin, "'ourCraftsmenCtaBgColor' => ['required'") === false) {
    $needle = "            'ourCraftsmenCtaEyebrow' => ['required', 'string', 'max:50'],";
    $replace = "            'ourCraftsmenCtaBgColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],\n" . $needle;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker validasi CTA tidak ditemukan.');
    $admin = str_replace($needle, $replace, $admin);
}

if (strpos($admin, "'bg_color' => \$this->ourCraftsmenCtaUseCustomBg") === false) {
    $needle = "            'data' => [\n                'eyebrow' => \$validated['ourCraftsmenCtaEyebrow'],";
    $replace = "            'data' => [\n                'bg_color' => \$this->ourCraftsmenCtaUseCustomBg ? strtoupper(\$validated['ourCraftsmenCtaBgColor']) : null,\n                'eyebrow' => \$validated['ourCraftsmenCtaEyebrow'],";
    $pos = strpos($admin, "HomeSection::forSection('our-craftsmen-cta')->update");
    $sub = substr($admin, $pos);
    if ($pos === false || strpos($sub, $needle) === false) throw new RuntimeException('Marker save CTA tidak ditemukan.');
    $sub = str_replace($needle, $replace, $sub);
    $admin = substr($admin, 0, $pos) . $sub;
}

// ============================================================
// ADMIN UI: rename + warna backframe
// ============================================================
$admin = str_replace('Profil Pemilik Karya Ide Edi', 'Pemilik & Founder', $admin);
$admin = str_replace('Simpan Profil Pemilik', 'Simpan Pemilik & Founder', $admin);
$admin = str_replace('CTA Our Craftsmen', 'Mulai dari Sebuah Ide', $admin);

if (strpos($admin, 'wire:model.live="ourCraftsmenOwnerUseCustomBg"') === false) {
    $needle = <<<'BLADE'
                    <div class="grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
BLADE;
    $colorBox = <<<'BLADE'
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="ourCraftsmenOwnerUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna backframe khusus
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="ourCraftsmenOwnerBgColor" @disabled(! $ourCraftsmenOwnerUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                                <span class="font-mono text-xs text-admin-ink-soft">{{ strtoupper($ourCraftsmenOwnerBgColor) }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($ourCraftsmenOwnerBgPresets as $preset)
                                <button type="button" wire:click="selectOurCraftsmenOwnerBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="h-9 w-9 rounded-full border border-admin-border shadow-sm transition hover:scale-110" style="background: {{ $preset['value'] }};"></button>
                            @endforeach
                        </div>
                        @error('ourCraftsmenOwnerBgColor')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>

BLADE;
    if (strpos($admin, $needle) === false) throw new RuntimeException('Marker UI owner grid tidak ditemukan.');
    $admin = str_replace($needle, $colorBox . $needle, $admin);
}

if (strpos($admin, 'wire:model.live="ourCraftsmenCtaUseCustomBg"') === false) {
    $needle = <<<'BLADE'
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-admin-ink">Label kecil</label><input type="text" maxlength="50" wire:model="ourCraftsmenCtaEyebrow"
BLADE;
    $colorBox = <<<'BLADE'
                    <div class="space-y-4 rounded-xl border border-admin-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Warna Backframe</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-admin-ink">
                                <input type="checkbox" wire:model.live="ourCraftsmenCtaUseCustomBg" class="h-4 w-4 rounded border-admin-border text-admin-accent focus:ring-admin-accent">
                                Pakai warna backframe khusus
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="ourCraftsmenCtaBgColor" @disabled(! $ourCraftsmenCtaUseCustomBg) class="h-10 w-16 cursor-pointer rounded-lg border border-admin-border disabled:opacity-40">
                                <span class="font-mono text-xs text-admin-ink-soft">{{ strtoupper($ourCraftsmenCtaBgColor) }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($ourCraftsmenCtaBgPresets as $preset)
                                <button type="button" wire:click="selectOurCraftsmenCtaBgPreset('{{ $preset['value'] }}')" title="{{ $preset['label'] }}" class="h-9 w-9 rounded-full border border-admin-border shadow-sm transition hover:scale-110" style="background: {{ $preset['value'] }};"></button>
                            @endforeach
                        </div>
                        @error('ourCraftsmenCtaBgColor')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>

BLADE;
    $pos = strpos($admin, "@elseif (\$activeSection === 'our-craftsmen-cta')");
    if ($pos === false) throw new RuntimeException('Section UI CTA tidak ditemukan.');
    $sub = substr($admin, $pos);
    if (strpos($sub, $needle) === false) throw new RuntimeException('Marker field CTA tidak ditemukan.');
    $sub = str_replace($needle, $colorBox . $needle, $sub);
    $admin = substr($admin, 0, $pos) . $sub;
}

// ============================================================
// FRONTEND: data bg_color + contrast
// ============================================================
if (strpos($front, "'bg_color' => null,\n            'eyebrow' => 'Pemilik & Founder'") === false) {
    $front = str_replace(
        "        \$owner = \\App\\Models\\HomeSection::dataFor('our-craftsmen-daftar', [\n            'eyebrow' => 'Pemilik & Founder',",
        "        \$owner = \\App\\Models\\HomeSection::dataFor('our-craftsmen-daftar', [\n            'bg_color' => null,\n            'eyebrow' => 'Pemilik & Founder',",
        $front
    );
}

if (strpos($front, "'bg_color' => null,\n            'eyebrow' => 'Mulai dari Sebuah Ide'") === false) {
    $front = str_replace(
        "        \$cta = \\App\\Models\\HomeSection::dataFor('our-craftsmen-cta', [\n            'eyebrow' => 'Mulai dari Sebuah Ide',",
        "        \$cta = \\App\\Models\\HomeSection::dataFor('our-craftsmen-cta', [\n            'bg_color' => null,\n            'eyebrow' => 'Mulai dari Sebuah Ide',",
        $front
    );
}

if (strpos($front, '$ownerFrame = \App\Support\FrameBackground::resolve') === false) {
    $needle = "        \$ownerStats = is_array(\$owner['stats'] ?? null) ? array_slice(array_values(\$owner['stats']), 0, 3) : [];";
    $insert = $needle . <<<'BLADE'

        $ownerFrame = \App\Support\FrameBackground::resolve($owner['bg_color'] ?? null, null, '#F8F5F0');
        $ownerColors = $heroContrast($ownerFrame['base']);
BLADE;
    if (strpos($front, $needle) === false) throw new RuntimeException('Marker ownerStats frontend tidak ditemukan.');
    $front = str_replace($needle, $insert, $front);
}

if (strpos($front, '$ctaFrame = \App\Support\FrameBackground::resolve') === false) {
    $needle = <<<'BLADE'
        ]);
    @endphp
BLADE;
    $pos = strpos($front, "        \$cta = \\App\\Models\\HomeSection::dataFor('our-craftsmen-cta'");
    if ($pos === false) throw new RuntimeException('Data CTA frontend tidak ditemukan.');
    $end = strpos($front, $needle, $pos);
    if ($end === false) throw new RuntimeException('Akhir blok data CTA tidak ditemukan.');
    $end += strlen("        ]);\n");
    $insert = <<<'BLADE'
        $ctaFrame = \App\Support\FrameBackground::resolve($cta['bg_color'] ?? null, null, '#21140D');
        $ctaColors = $heroContrast($ctaFrame['base']);
BLADE;
    $front = substr($front, 0, $end) . $insert . substr($front, $end);
}

// ============================================================
// FRONTEND: kecilkan section Pemilik & Founder
// ============================================================
$front = str_replace(
    '<section class="relative overflow-hidden bg-[#F8F5F0] py-18 sm:py-20 lg:py-28">',
    '<section class="relative overflow-hidden py-14 sm:py-16 lg:py-20" style="background: {{ $ownerFrame[\'css\'] }};">',
    $front
);
$front = str_replace('lg:grid-cols-[0.86fr_1.14fr] lg:gap-20', 'lg:grid-cols-[0.78fr_1.22fr] lg:gap-14', $front);
$front = str_replace('max-w-lg lg:mx-0', 'max-w-md lg:mx-0', $front);
$front = str_replace('h-28 w-28', 'h-24 w-24', $front);
$front = str_replace('class="mt-5 max-w-3xl font-display text-4xl font-semibold leading-tight text-[#3A2418] sm:text-5xl lg:text-6xl"', 'class="mt-5 max-w-3xl font-display text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl" style="color: {{ $ownerColors[\'title\'] }};"', $front);
$front = str_replace('class="mt-7 max-w-3xl text-base leading-8 text-[#6E6258] sm:text-lg sm:leading-9"', 'class="mt-6 max-w-3xl text-base leading-8 sm:text-[1.05rem] sm:leading-8" style="color: {{ $ownerColors[\'body\'] }};"', $front);
$front = str_replace('class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.32em] text-[#A66E38]"', 'class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.32em]" style="color: {{ $ownerColors[\'accent\'] }};"', $front);
$front = str_replace('<span class="h-px w-10 bg-[#A66E38]"></span>', '<span class="h-px w-10" style="background-color: {{ $ownerColors[\'accent\'] }};"></span>', $front);
$front = str_replace('class="mt-8 border-l-2 border-[#C9955C] pl-6 font-display text-xl italic leading-9 text-[#543929] sm:text-2xl"', 'class="mt-7 border-l-2 pl-5 font-display text-lg italic leading-8 sm:text-xl" style="border-color: {{ $ownerColors[\'accent\'] }}; color: {{ $ownerColors[\'title\'] }};"', $front);
$front = str_replace('“{{ $owner[\'quote\'] }}”', '&ldquo;{{ $owner[\'quote\'] }}&rdquo;', $front);
$front = str_replace('class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3"', 'class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-3"', $front);
$front = str_replace("Edit Web &gt; Our Craftsmen &gt; Profil Pemilik.", "Edit Web &gt; Our Craftsmen &gt; Pemilik &amp; Founder.", $front);
$front = str_replace("@include('partials.frontend.frame-seam', ['from' => \$heroBase, 'to' => '#F8F5F0'])", "@include('partials.frontend.frame-seam', ['from' => \$heroBase, 'to' => \$ownerFrame['base']])", $front);
$front = str_replace('rounded-3xl border border-[#E4D6C5] bg-white/70 p-5', 'rounded-3xl border border-white/20 bg-white/65 p-4', $front);
$front = str_replace('font-display text-2xl font-semibold text-[#4B2F1F]', 'font-display text-xl font-semibold text-[#4B2F1F]', $front);

// ============================================================
// FRONTEND: hapus strip identitas sepenuhnya
// ============================================================
$front = preg_replace(
    '/\s*\{\{-- STRIP NILAI \/ IDENTITAS --\}\}.*?<\/section>\s*(?=\{\{-- CTA --\}\})/s',
    "\n\n    ",
    $front,
    1
);

// ============================================================
// FRONTEND: CTA jadi Mulai dari Sebuah Ide + warna backframe
// ============================================================
$front = str_replace(
    '<section class="relative overflow-hidden bg-[#21140D]">',
    '<section class="relative overflow-hidden" style="background: {{ $ctaFrame[\'css\'] }};">',
    $front
);
$front = str_replace('class="text-xs font-semibold uppercase tracking-[0.34em] text-[#D6A76F]"', 'class="text-xs font-semibold uppercase tracking-[0.34em]" style="color: {{ $ctaColors[\'accent\'] }};"', $front);
$front = str_replace('class="mt-4 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl"', 'class="mt-4 font-display text-3xl font-semibold leading-tight sm:text-4xl" style="color: {{ $ctaColors[\'title\'] }};"', $front);
$front = str_replace('class="mt-5 max-w-2xl text-base leading-8 text-white/65"', 'class="mt-5 max-w-2xl text-base leading-8" style="color: {{ $ctaColors[\'body\'] }};"', $front);

file_put_contents($adminPath, $admin);
file_put_contents($frontPath, $front);

echo "PATCH_OK\n";
'@ | Set-Content -Path $tmp -Encoding UTF8

php $tmp
Remove-Item $tmp -Force

Step '[3/6] Validasi syntax ...'
php -l $adminPath | Out-Host
php -l $frontPath | Out-Host

Step '[4/6] Bersihkan cache view ...'
php artisan view:clear | Out-Host

Step '[5/6] Ringkasan perubahan ...'
Write-Host '  Frontend Our Craftsmen:' -ForegroundColor DarkGray
Write-Host '   - Strip CUSTOM FURNITURE / DETAIL MATTERS / MADE BY HAND: DIHAPUS' -ForegroundColor DarkGray
Write-Host '   - Pemilik & Founder: ukuran diperkecil sedikit' -ForegroundColor DarkGray
Write-Host '   - Pemilik & Founder: backframe bisa diatur dari Edit Web' -ForegroundColor DarkGray
Write-Host '   - Mulai dari Sebuah Ide: backframe bisa diatur dari Edit Web' -ForegroundColor DarkGray
Write-Host '  Edit Web > Our Craftsmen:' -ForegroundColor DarkGray
Write-Host '   - Hero: tetap aktif' -ForegroundColor DarkGray
Write-Host '   - Pemilik & Founder: foto + teks + warna backframe' -ForegroundColor DarkGray
Write-Host '   - Mulai dari Sebuah Ide: teks + warna backframe' -ForegroundColor DarkGray

Step '[6/6] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Setelah ini jalankan: npm run build' -ForegroundColor Yellow
