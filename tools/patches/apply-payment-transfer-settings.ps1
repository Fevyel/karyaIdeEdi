$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$settingsModel = ".\app\Models\Setting.php"
$adminSettings = ".\resources\views\pages\admin\pengaturan.blade.php"
$footer = ".\resources\views\partials\frontend\footer.blade.php"
$routes = ".\routes\web.php"
$paymentView = ".\resources\views\pages\frontend\payment-transfer.blade.php"
$migration = ".\database\migrations\2026_09_24_193000_add_payment_accounts_to_settings.php"

foreach ($file in @($settingsModel, $adminSettings, $footer, $routes)) {
    if (-not (Test-Path $file)) {
        throw "File wajib tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-payment-accounts-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host " Payment Transfer - BCA / BRI / DANA + Pengaturan Admin" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Yellow

function Backup-File {
    param([string]$File)

    if (-not (Test-Path $File)) {
        return
    }

    $relative = $File.TrimStart('.', '\')
    $destination = Join-Path $backupDir $relative
    $destinationDir = Split-Path $destination -Parent

    New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    Copy-Item $File $destination -Force
}

Step "[1/7] Backup file yang akan disentuh ..."

foreach ($file in @($settingsModel, $adminSettings, $footer, $routes, $paymentView, $migration)) {
    Backup-File $file
}

Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/7] Buat migration kolom rekening pembayaran ..."

$migrationContent = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'bca_account_number',
            'bca_account_name',
            'bri_account_number',
            'bri_account_name',
            'dana_account_number',
            'dana_account_name',
        ];

        foreach ($columns as $column) {
            if (! Schema::hasColumn('settings', $column)) {
                Schema::table('settings', function (Blueprint $table) use ($column): void {
                    $table->string($column, 120)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'bca_account_number',
            'bca_account_name',
            'bri_account_number',
            'bri_account_name',
            'dana_account_number',
            'dana_account_name',
        ];

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('settings', $column)
        ));

        if ($existing !== []) {
            Schema::table('settings', function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            });
        }
    }
};
'@

[System.IO.File]::WriteAllText((Join-Path (Get-Location) $migration.TrimStart('.', '\')), $migrationContent, $utf8NoBom)
Write-Host "  - $migration" -ForegroundColor Green

Step "[3/7] Patch Setting model + Admin Pengaturan + route + footer ..."

$phpPatch = @'
<?php

$root = getcwd();

function path_of(string $root, string $relative): string
{
    return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function read_or_fail(string $root, string $relative): string
{
    $path = path_of($root, $relative);
    $text = file_get_contents($path);

    if ($text === false) {
        throw new RuntimeException("Gagal membaca {$relative}");
    }

    return $text;
}

function write_no_bom(string $root, string $relative, string $text): void
{
    $path = path_of($root, $relative);

    if (file_put_contents($path, $text) === false) {
        throw new RuntimeException("Gagal menulis {$relative}");
    }
}

function insert_before_once(string $text, string $anchor, string $insert, string $label): string
{
    if (str_contains($text, trim($insert))) {
        echo "  - {$label}: sudah ada\n";
        return $text;
    }

    $pos = strpos($text, $anchor);

    if ($pos === false) {
        throw new RuntimeException("Anchor tidak ditemukan: {$label}");
    }

    echo "  - {$label}: ditambahkan\n";

    return substr($text, 0, $pos).$insert.substr($text, $pos);
}

/*
|--------------------------------------------------------------------------
| 1) app/Models/Setting.php
|--------------------------------------------------------------------------
*/
$rel = 'app/Models/Setting.php';
$text = read_or_fail($root, $rel);

if (! str_contains($text, "'bca_account_number'")) {
    $text = preg_replace_callback(
        '~#\[Fillable\(\[(.*?)\]\)\]~s',
        function (array $m): string {
            $inside = rtrim($m[1]);

            $extra = ", 'bca_account_number', 'bca_account_name', 'bri_account_number', 'bri_account_name', 'dana_account_number', 'dana_account_name'";

            return '#[Fillable(['.$inside.$extra.'])]';
        },
        $text,
        1,
        $count
    );

    if ($count !== 1) {
        throw new RuntimeException('Attribute #[Fillable] pada Setting.php tidak ditemukan.');
    }
}

if (! str_contains($text, '@property string|null $bca_account_number')) {
    $anchor = " * @property string|null \$alamat\n";

    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Anchor PHPDoc Setting::$alamat tidak ditemukan.');
    }

    $props = <<<'PHPDOC'
 * @property string|null $bca_account_number
 * @property string|null $bca_account_name
 * @property string|null $bri_account_number
 * @property string|null $bri_account_name
 * @property string|null $dana_account_number
 * @property string|null $dana_account_name
PHPDOC;

    $text = str_replace($anchor, $anchor.$props."\n", $text);
}

write_no_bom($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| 2) Admin > Pengaturan
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/pages/admin/pengaturan.blade.php';
$text = read_or_fail($root, $rel);

$props = <<<'BLADE'
    public string $bca_account_number = '';

    public string $bca_account_name = '';

    public string $bri_account_number = '';

    public string $bri_account_name = '';

    public string $dana_account_number = '';

    public string $dana_account_name = '';

BLADE;

$text = insert_before_once(
    $text,
    '    /** Hasil crop dari kanvas JS',
    $props,
    'property rekening'
);

$mountLines = <<<'BLADE'
        $this->bca_account_number = (string) $setting->bca_account_number;
        $this->bca_account_name = (string) $setting->bca_account_name;
        $this->bri_account_number = (string) $setting->bri_account_number;
        $this->bri_account_name = (string) $setting->bri_account_name;
        $this->dana_account_number = (string) $setting->dana_account_number;
        $this->dana_account_name = (string) $setting->dana_account_name;
BLADE;

if (! str_contains($text, '$this->bca_account_number =')) {
    $anchor = '        $this->facebook_url = (string) $setting->facebook_url;'."\n";

    if (! str_contains($text, $anchor)) {
        $anchor = '        $this->facebook_url = (string) $setting->facebook_url;'."\r\n";
    }

    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Anchor mount facebook_url tidak ditemukan.');
    }

    $text = str_replace($anchor, rtrim($anchor)."\n".$mountLines."\n", $text);
}

$validation = <<<'BLADE'
            'bca_account_number' => ['nullable', 'regex:/^[0-9]+$/', 'max:30'],
            'bca_account_name' => ['nullable', 'string', 'max:100'],
            'bri_account_number' => ['nullable', 'regex:/^[0-9]+$/', 'max:30'],
            'bri_account_name' => ['nullable', 'string', 'max:100'],
            'dana_account_number' => ['nullable', 'regex:/^[0-9]+$/', 'max:30'],
            'dana_account_name' => ['nullable', 'string', 'max:100'],
BLADE;

if (! str_contains($text, "'bca_account_number' =>")) {
    $anchor = "            'facebook_url' => ['nullable', 'url', 'max:255', \$this->socialPlatformMismatchRule('facebook_url')],";

    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Anchor validasi facebook_url tidak ditemukan.');
    }

    $text = str_replace($anchor, $anchor."\n".$validation, $text);
}

$paymentSection = <<<'BLADE'
        {{-- ================= SECTION 3: REKENING PEMBAYARAN ================= --}}
        <div class="flex flex-col rounded-2xl border border-[var(--color-admin-border)] bg-admin-surface p-5 shadow-sm sm:p-6">
            <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-[var(--color-admin-ink)]">
                <i class="fa-solid fa-building-columns text-[var(--color-admin-accent)]"></i>
                Rekening Pembayaran
            </h3>
            <p class="mb-5 text-xs leading-relaxed text-[var(--color-admin-ink-soft)]">
                Nomor ini dipakai otomatis pada halaman pembayaran saat pelanggan menekan logo BCA, BRI, atau DANA di footer.
                Isi hanya angka pada kolom nomor rekening/nomor DANA.
            </p>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="rounded-xl border border-[var(--color-admin-border)] p-4">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-12 items-center justify-center rounded-md bg-[#1677C8]/10 text-xs font-extrabold text-[#1677C8]">BCA</span>
                        <p class="text-sm font-semibold text-[var(--color-admin-ink)]">Bank BCA</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="setting_bca_account_number" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">Nomor Rekening BCA</label>
                            <input
                                id="setting_bca_account_number"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                wire:model="bca_account_number"
                                placeholder="Contoh: 1234567890"
                                class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface px-3 py-2.5 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                            >
                            @error('bca_account_number')
                                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="setting_bca_account_name" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">Atas Nama</label>
                            <input
                                id="setting_bca_account_name"
                                type="text"
                                wire:model="bca_account_name"
                                placeholder="Nama pemilik rekening"
                                class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface px-3 py-2.5 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                            >
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-[var(--color-admin-border)] p-4">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-12 items-center justify-center rounded-md bg-[#00529C]/10 text-xs font-extrabold text-[#00529C]">BRI</span>
                        <p class="text-sm font-semibold text-[var(--color-admin-ink)]">Bank BRI</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="setting_bri_account_number" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">Nomor Rekening BRI</label>
                            <input
                                id="setting_bri_account_number"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                wire:model="bri_account_number"
                                placeholder="Contoh: 123456789012345"
                                class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface px-3 py-2.5 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                            >
                            @error('bri_account_number')
                                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="setting_bri_account_name" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">Atas Nama</label>
                            <input
                                id="setting_bri_account_name"
                                type="text"
                                wire:model="bri_account_name"
                                placeholder="Nama pemilik rekening"
                                class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface px-3 py-2.5 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                            >
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-[var(--color-admin-border)] p-4">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-12 items-center justify-center rounded-md bg-[#1689E8]/10 text-xs font-extrabold text-[#1689E8]">DANA</span>
                        <p class="text-sm font-semibold text-[var(--color-admin-ink)]">DANA</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="setting_dana_account_number" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">Nomor DANA</label>
                            <input
                                id="setting_dana_account_number"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                wire:model="dana_account_number"
                                placeholder="Contoh: 081234567890"
                                class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface px-3 py-2.5 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                            >
                            @error('dana_account_number')
                                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="setting_dana_account_name" class="mb-1.5 block text-sm font-medium text-[var(--color-admin-ink)]">Atas Nama</label>
                            <input
                                id="setting_dana_account_name"
                                type="text"
                                wire:model="dana_account_name"
                                placeholder="Nama pemilik akun DANA"
                                class="w-full rounded-lg border border-[var(--color-admin-border)] bg-admin-surface px-3 py-2.5 text-sm text-[var(--color-admin-ink)] transition focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-admin-accent)]/20"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

BLADE;

$text = insert_before_once(
    $text,
    '        {{-- ================= TOMBOL SIMPAN ================= --}}',
    $paymentSection,
    'section Rekening Pembayaran'
);

write_no_bom($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| 3) Route halaman pembayaran transfer
|--------------------------------------------------------------------------
*/
$rel = 'routes/web.php';
$text = read_or_fail($root, $rel);

$routeBlock = <<<'PHP'
Route::get('/pembayaran/{method}', function (string $method) {
    $setting = \App\Models\Setting::current();

    $methods = [
        'bca' => [
            'key' => 'bca',
            'label' => 'BCA',
            'type' => 'Transfer Bank',
            'number_label' => 'Nomor Rekening',
            'number' => (string) $setting->bca_account_number,
            'account_name' => (string) $setting->bca_account_name,
            'logo' => 'images/payment-official/bca.png',
        ],
        'bri' => [
            'key' => 'bri',
            'label' => 'Bank BRI',
            'type' => 'Transfer Bank',
            'number_label' => 'Nomor Rekening',
            'number' => (string) $setting->bri_account_number,
            'account_name' => (string) $setting->bri_account_name,
            'logo' => 'images/payment-official/bri.png',
        ],
        'dana' => [
            'key' => 'dana',
            'label' => 'DANA',
            'type' => 'E-Wallet',
            'number_label' => 'Nomor DANA',
            'number' => (string) $setting->dana_account_number,
            'account_name' => (string) $setting->dana_account_name,
            'logo' => 'images/payment-official/dana.svg',
        ],
    ];

    abort_unless(isset($methods[$method]), 404);

    return view('pages.frontend.payment-transfer', [
        'siteSetting' => $setting,
        'paymentMethod' => $methods[$method],
    ]);
})->whereIn('method', ['bca', 'bri', 'dana'])->name('payment.transfer');

PHP;

if (! str_contains($text, "name('payment.transfer')")) {
    $anchor = "// Legal";

    $pos = strpos($text, $anchor);

    if ($pos === false) {
        $anchor = "Route::view('/kebijakan-privasi'";
        $pos = strpos($text, $anchor);
    }

    if ($pos === false) {
        throw new RuntimeException('Anchor route Legal tidak ditemukan.');
    }

    $text = substr($text, 0, $pos).$routeBlock.substr($text, $pos);
    echo "  - route payment.transfer: ditambahkan\n";
} else {
    echo "  - route payment.transfer: sudah ada\n";
}

write_no_bom($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| 4) Footer: jadikan 3 badge sebagai tombol/link.
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/partials/frontend/footer.blade.php';
$text = read_or_fail($root, $rel);

$methods = [
    'bca' => 'bca.png',
    'bri' => 'bri.png',
    'dana' => 'dana.svg',
];

foreach ($methods as $method => $asset) {
    if (
        str_contains($text, "route('payment.transfer', '{$method}')") ||
        str_contains($text, 'route("payment.transfer", "'.$method.'")')
    ) {
        echo "  - footer {$method}: sudah clickable\n";
        continue;
    }

    $pattern = '~<span\s+class="([^"]*)">\s*(<img\b[^>]*payment-official/'.preg_quote($asset, '~').'[^>]*>)\s*</span>~is';

    $replacement = '<a href="{{ route(\'payment.transfer\', \''.$method.'\') }}" class="$1" aria-label="Pembayaran via '.strtoupper($method).'" title="Pembayaran via '.strtoupper($method).'">$2</a>';

    $updated = preg_replace($pattern, $replacement, $text, 1, $count);

    if ($updated === null || $count !== 1) {
        throw new RuntimeException("Badge footer {$method} tidak ditemukan dalam bentuk yang diharapkan.");
    }

    $text = $updated;
    echo "  - footer {$method}: dibuat clickable\n";
}

write_no_bom($root, $rel, $text);

echo "PATCH_CORE_OK\n";
'@

$tmpPatch = Join-Path $env:TEMP "patch-payment-accounts-$stamp.php"
[System.IO.File]::WriteAllText($tmpPatch, $phpPatch, $utf8NoBom)

php $tmpPatch
$patchExit = $LASTEXITCODE

Remove-Item $tmpPatch -Force -ErrorAction SilentlyContinue

if ($patchExit -ne 0) {
    throw "Patch source gagal. Backup tersedia di: $backupDir"
}

Step "[4/7] Buat halaman Pembayaran Transfer ..."

$paymentViewContent = @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran {{ $paymentMethod['label'] }} &mdash; {{ $siteSetting->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F5F1EA] font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $paymentNumber = trim((string) ($paymentMethod['number'] ?? ''));
        $paymentDigits = preg_replace('/\D+/', '', $paymentNumber);
        $paymentReady = $paymentDigits !== '';
        $waDigits = $siteSetting->whatsappDigits();

        $waMessage = 'Halo '.$siteSetting->site_name.', saya ingin konfirmasi pembayaran melalui '.$paymentMethod['label'].'.';
        $waConfirmUrl = $waDigits
            ? 'https://wa.me/'.$waDigits.'?text='.rawurlencode($waMessage)
            : null;
    @endphp

    <main class="relative overflow-hidden">
        <div class="pointer-events-none absolute -right-24 top-12 h-72 w-72 rounded-full bg-admin-accent/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-24 bottom-10 h-72 w-72 rounded-full bg-[#1A1A1A]/5 blur-3xl"></div>

        <section class="relative mx-auto max-w-3xl px-5 py-12 sm:px-8 sm:py-16">
            <div class="mb-6">
                <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#6B625B] transition hover:text-admin-accent">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Kembali
                </a>
            </div>

            <div class="overflow-hidden rounded-[28px] border border-[#2A211B]/10 bg-white shadow-[0_24px_70px_-35px_rgba(42,33,27,0.35)]">
                <div class="border-b border-[#2A211B]/10 bg-[#1A1A1A] px-6 py-8 text-center sm:px-10">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/45">Pembayaran</p>

                    <div class="mx-auto mt-4 flex h-16 w-28 items-center justify-center rounded-2xl bg-[#4A4A4A] px-4">
                        @if (file_exists(public_path($paymentMethod['logo'])))
                            <img
                                src="{{ asset($paymentMethod['logo']) }}"
                                alt="{{ $paymentMethod['label'] }}"
                                class="max-h-9 max-w-full object-contain"
                            >
                        @else
                            <span class="text-lg font-bold text-white">{{ $paymentMethod['label'] }}</span>
                        @endif
                    </div>

                    <h1 class="mt-5 font-display text-3xl font-semibold text-white sm:text-4xl">
                        {{ $paymentMethod['label'] }}
                    </h1>
                    <p class="mt-2 text-sm text-white/55">{{ $paymentMethod['type'] }}</p>
                </div>

                <div class="p-6 sm:p-10">
                    @if ($paymentReady)
                        <div
                            x-data="{
                                copied: false,
                                copyNumber() {
                                    const value = @js($paymentDigits);
                                    navigator.clipboard.writeText(value).then(() => {
                                        this.copied = true;
                                        setTimeout(() => this.copied = false, 1800);
                                    });
                                }
                            }"
                            class="space-y-6"
                        >
                            <div class="rounded-2xl border border-admin-accent/20 bg-admin-cream/40 p-5 sm:p-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7B716A]">
                                    {{ $paymentMethod['number_label'] }}
                                </p>

                                <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-display text-2xl font-semibold tracking-[0.06em] text-[#1A1A1A] sm:text-3xl">
                                            {{ $paymentNumber }}
                                        </p>

                                        @if (trim((string) $paymentMethod['account_name']) !== '')
                                            <p class="mt-1 text-sm text-[#6B625B]">
                                                a.n. <span class="font-semibold text-[#2A211B]">{{ $paymentMethod['account_name'] }}</span>
                                            </p>
                                        @endif
                                    </div>

                                    <button
                                        type="button"
                                        x-on:click="copyNumber()"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-[#1A1A1A] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-admin-accent"
                                    >
                                        <i class="fa-regular fa-copy"></i>
                                        <span x-show="!copied">Salin Nomor</span>
                                        <span x-show="copied" x-cloak>Tersalin</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-2xl bg-[#F8F6F2] p-4">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-admin-accent shadow-sm">1</span>
                                    <p class="mt-3 text-sm font-semibold text-[#2A211B]">Buka aplikasi pembayaran</p>
                                    <p class="mt-1 text-xs leading-relaxed text-[#746B64]">Gunakan m-banking, ATM, atau aplikasi DANA sesuai metode yang dipilih.</p>
                                </div>

                                <div class="rounded-2xl bg-[#F8F6F2] p-4">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-admin-accent shadow-sm">2</span>
                                    <p class="mt-3 text-sm font-semibold text-[#2A211B]">Transfer ke nomor di atas</p>
                                    <p class="mt-1 text-xs leading-relaxed text-[#746B64]">Masukkan nominal sesuai nilai yang telah disepakati pada pesanan Anda.</p>
                                </div>

                                <div class="rounded-2xl bg-[#F8F6F2] p-4">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-admin-accent shadow-sm">3</span>
                                    <p class="mt-3 text-sm font-semibold text-[#2A211B]">Konfirmasi pembayaran</p>
                                    <p class="mt-1 text-xs leading-relaxed text-[#746B64]">Simpan bukti transfer dan kirimkan kepada tim kami untuk verifikasi.</p>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-shield-halved mt-0.5 text-amber-600"></i>
                                    <p class="text-xs leading-relaxed text-amber-900">
                                        Pastikan nama tujuan pembayaran sesuai sebelum menyelesaikan transfer.
                                        {{ $siteSetting->site_name }} tidak pernah meminta PIN, password, atau kode OTP melalui halaman ini.
                                    </p>
                                </div>
                            </div>

                            @if ($waConfirmUrl)
                                <a
                                    href="{{ $waConfirmUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex w-full items-center justify-center gap-2 rounded-full bg-admin-accent px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-admin-accent-strong"
                                >
                                    <i class="fa-brands fa-whatsapp text-base"></i>
                                    Konfirmasi Pembayaran via WhatsApp
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center">
                            <i class="fa-solid fa-circle-info text-xl text-amber-600"></i>
                            <h2 class="mt-3 text-lg font-semibold text-amber-950">Metode pembayaran belum tersedia</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-amber-800">
                                Nomor {{ $paymentMethod['label'] }} belum diisi oleh admin.
                                Silakan pilih metode pembayaran lain atau hubungi toko.
                            </p>

                            @if ($waConfirmUrl)
                                <a
                                    href="{{ $waConfirmUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-5 inline-flex items-center gap-2 rounded-full bg-[#1A1A1A] px-5 py-2.5 text-sm font-semibold text-white"
                                >
                                    <i class="fa-brands fa-whatsapp"></i>
                                    Hubungi Toko
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>

    @include('partials.frontend.footer')
</body>
</html>
'@

$paymentViewPath = Join-Path (Get-Location) $paymentView.TrimStart('.', '\')
$paymentViewDir = Split-Path $paymentViewPath -Parent
New-Item -ItemType Directory -Path $paymentViewDir -Force | Out-Null
[System.IO.File]::WriteAllText($paymentViewPath, $paymentViewContent, $utf8NoBom)

Write-Host "  - $paymentView" -ForegroundColor Green

Step "[5/7] Validasi syntax source ..."

foreach ($file in @($settingsModel, $adminSettings, $footer, $routes, $paymentView, $migration)) {
    php -l $file | Out-Host

    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Backup tersedia di: $backupDir"
    }
}

Step "[6/7] Jalankan migration ..."
php artisan migrate --force | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Migration gagal. Backup source tersedia di: $backupDir"
}

Step "[7/7] Clear cache + verifikasi route ..."
php artisan optimize:clear | Out-Host

Write-Host "`nRoute pembayaran:" -ForegroundColor Cyan
php artisan route:list | Select-String -Pattern "pembayaran|payment.transfer" | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Yang sekarang tersedia:" -ForegroundColor Yellow
Write-Host "  - Admin > Pengaturan > Rekening Pembayaran" -ForegroundColor White
Write-Host "  - Nomor rekening + atas nama BCA" -ForegroundColor White
Write-Host "  - Nomor rekening + atas nama BRI" -ForegroundColor White
Write-Host "  - Nomor DANA + atas nama" -ForegroundColor White
Write-Host "  - Logo BCA/BRI/DANA di footer menjadi tombol" -ForegroundColor White
Write-Host "  - Klik logo membuka halaman Pembayaran Transfer yang sesuai" -ForegroundColor White
Write-Host "  - Nomor pada halaman pembayaran selalu membaca Pengaturan Admin" -ForegroundColor White
Write-Host "  - Tombol Salin Nomor + Konfirmasi WhatsApp" -ForegroundColor White
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
