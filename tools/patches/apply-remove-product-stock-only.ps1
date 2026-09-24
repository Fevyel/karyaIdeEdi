$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$files = @(
    ".\resources\views\pages\admin\produk-form.blade.php",
    ".\resources\views\pages\admin\produk.blade.php",
    ".\resources\views\pages\admin\pesanan.blade.php",
    ".\resources\views\pages\frontend\produk-show.blade.php",
    ".\resources\views\partials\frontend\alur-booking.blade.php",
    ".\resources\views\components\notification-bell.blade.php",
    ".\app\Models\User.php"
)

foreach ($file in $files) {
    if (-not (Test-Path $file)) {
        throw "File wajib tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-remove-product-stock-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host " Produk - Hapus Fitur Stok, BUKAN Produk" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Yellow

function Backup-File {
    param([string]$File)

    $relative = $File.TrimStart('.', '\')
    $destination = Join-Path $backupDir $relative
    New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
    Copy-Item $File $destination -Force
}

function Restore-Backups {
    foreach ($file in $files) {
        $relative = $file.TrimStart('.', '\')
        $backup = Join-Path $backupDir $relative

        if (Test-Path $backup) {
            Copy-Item $backup $file -Force
        }
    }
}

Step "[1/6] Backup file ..."
foreach ($file in $files) {
    Backup-File $file
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

try {
    Step "[2/6] Hapus stok dari UI produk dan matikan notifikasi stok ..."

    $phpPatch = @'
<?php

$root = getcwd();

function p(string $root, string $relative): string
{
    return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function readFileOrFail(string $root, string $relative): string
{
    $path = p($root, $relative);
    $text = file_get_contents($path);

    if ($text === false) {
        throw new RuntimeException("Gagal membaca {$relative}");
    }

    return $text;
}

function writeFileOrFail(string $root, string $relative, string $text): void
{
    if (file_put_contents(p($root, $relative), $text) === false) {
        throw new RuntimeException("Gagal menulis {$relative}");
    }
}

function replaceOnce(string $text, string $old, string $new, string $label, bool $required = true): string
{
    if (! str_contains($text, $old)) {
        if ($required && ! str_contains($text, $new)) {
            throw new RuntimeException("Anchor tidak ditemukan: {$label}");
        }

        echo "  - {$label}: sudah bersih / tidak ada\n";
        return $text;
    }

    $count = 0;
    $text = str_replace($old, $new, $text, $count);

    if ($required && $count < 1) {
        throw new RuntimeException("Gagal mengganti: {$label}");
    }

    echo "  - {$label}: diperbarui\n";
    return $text;
}

function removeLineMatching(string $text, string $pattern, string $label): string
{
    $updated = preg_replace($pattern, '', $text, -1, $count);

    if ($updated === null) {
        throw new RuntimeException("Regex error: {$label}");
    }

    if ($count > 0) {
        echo "  - {$label}: dihapus\n";
    } else {
        echo "  - {$label}: sudah tidak ada\n";
    }

    return $updated;
}

/**
 * Hapus div terluar yang mengandung marker tertentu.
 * Dipakai untuk field Stok agar nested div number-stepper ikut terhapus utuh.
 */
function removeContainingDiv(string $text, string $marker, string $label): string
{
    $markerPos = strpos($text, $marker);

    if ($markerPos === false) {
        echo "  - {$label}: sudah tidak ada\n";
        return $text;
    }

    preg_match_all('~<div\b[^>]*>|</div>~i', substr($text, 0, $markerPos), $before, PREG_OFFSET_CAPTURE);

    $stack = [];

    foreach ($before[0] as [$tag, $offset]) {
        if (stripos($tag, '</div') === 0) {
            array_pop($stack);
        } else {
            $stack[] = $offset;
        }
    }

    if ($stack === []) {
        throw new RuntimeException("Parent div tidak ditemukan: {$label}");
    }

    $start = end($stack);

    preg_match_all('~<div\b[^>]*>|</div>~i', substr($text, $start), $tags, PREG_OFFSET_CAPTURE);

    $depth = 0;
    $end = null;

    foreach ($tags[0] as [$tag, $offset]) {
        if (stripos($tag, '</div') === 0) {
            $depth--;

            if ($depth === 0) {
                $end = $start + $offset + strlen($tag);
                break;
            }
        } else {
            $depth++;
        }
    }

    if ($end === null) {
        throw new RuntimeException("Penutup div tidak ditemukan: {$label}");
    }

    echo "  - {$label}: dihapus\n";

    return substr($text, 0, $start).substr($text, $end);
}

/*
|--------------------------------------------------------------------------
| ADMIN PRODUK FORM
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/pages/admin/produk-form.blade.php';
$text = readFileOrFail($root, $rel);

$text = removeLineMatching(
    $text,
    '/^[ \t]*public string \$stok = [^\r\n]*\R/m',
    'property stok'
);

$text = removeLineMatching(
    $text,
    '/^[ \t]*\$this->stok = \(string\) \$product->stok;\R/m',
    'mount stok'
);

$text = removeLineMatching(
    $text,
    '/^[ \t]*\'stok\' => \[[^\r\n]*\],\R/m',
    'validasi stok'
);

$text = removeLineMatching(
    $text,
    '/^[ \t]*\'stok\.required\' => [^\r\n]*\R/m',
    'pesan validasi stok'
);

$text = preg_replace(
    '/^[ \t]*\$product->stok = \$this->stok;\R/m',
    '        // Kolom stok dipertahankan hanya untuk kompatibilitas kode lama; tidak lagi dikelola admin.'."\n".
    '        $product->stok = 1000000000;'."\n",
    $text,
    1,
    $saveCount
);

if ($saveCount !== 1 && ! str_contains($text, '$product->stok = 1000000000;')) {
    throw new RuntimeException('Assignment stok pada save() tidak ditemukan.');
}

$text = str_replace(
    '{{-- ================= CARD: HARGA, STOK & STATUS ================= --}}',
    '{{-- ================= CARD: HARGA & STATUS ================= --}}',
    $text
);

$text = str_replace(
    'Harga, Stok &amp; Status',
    'Harga &amp; Status',
    $text
);

$text = removeContainingDiv($text, '<label for="stok"', 'field Stok di form produk');

writeFileOrFail($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| ADMIN DAFTAR PRODUK
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/pages/admin/produk.blade.php';
$text = readFileOrFail($root, $rel);

$text = preg_replace(
    '/^[ \t]*<th[^>]*>\s*Stok\s*<\/th>\R?/mi',
    '',
    $text,
    -1,
    $thCount
);

$stockCellPattern = '~^[ \t]*<td\b[^>]*\$product->stok[^>]*>\s*\R?[ \t]*\{\{\s*\$product->stok\s*\}\}\s*\R?[ \t]*</td>\R?~mi';
$text = preg_replace($stockCellPattern, '', $text, -1, $tdCount);

if ($tdCount === 0) {
    // Fallback untuk class yang ekspresinya tidak berada di opening tag.
    $text = preg_replace(
        '~[ \t]*<td\b[^>]*>[\s\S]{0,350}?\{\{\s*\$product->stok\s*\}\}[\s\S]{0,120}?</td>\R?~i',
        '',
        $text,
        1,
        $tdCount2
    );
    $tdCount += $tdCount2;
}

echo "  - kolom Stok daftar produk: header {$thCount}, cell {$tdCount}\n";

writeFileOrFail($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| FRONTEND DETAIL PRODUK
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/pages/frontend/produk-show.blade.php';
$text = readFileOrFail($root, $rel);

$text = str_replace(
    "class=\"h-10 flex flex-1 items-center justify-center rounded-md bg-[#F28A22] px-5 text-[11px] font-semibold text-white transition hover:bg-[#DD7614] {{ (int) \$product->stok < 1 ? 'pointer-events-none cursor-not-allowed opacity-50' : '' }}\"",
    "class=\"h-10 flex flex-1 items-center justify-center rounded-md bg-[#F28A22] px-5 text-[11px] font-semibold text-white transition hover:bg-[#DD7614]\"",
    $text
);

$text = str_replace(
    "{{ (int) \$product->stok < 1 ? 'Stok Habis' : 'Pesan Sekarang' }}",
    'Pesan Sekarang',
    $text
);

$text = removeLineMatching(
    $text,
    '/^[ \t]*\'Stok\' => \$product->stok !== null \? \$product->stok\.\' unit\' : null,\R/m',
    'spesifikasi Stok frontend'
);

$text = str_replace(
    '- Specification: berat, dimensi (p/l/t), kategori, stok',
    '- Specification: berat, dimensi (p/l/t), kategori',
    $text
);

writeFileOrFail($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| ADMIN PESANAN — HANYA BERSIHKAN TEKS/UI STOK.
| Logic lama tetap aman karena stok internal dibuat sangat besar.
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/pages/admin/pesanan.blade.php';
$text = readFileOrFail($root, $rel);

$text = preg_replace(
    '/\{\{\s*\$produkItem->nama\s*\}\}\s*&mdash;\s*Stok\s*\{\{\s*\$produkItem->stok\s*\}\}/',
    '{{ $produkItem->nama }}',
    $text,
    -1,
    $optionCount
);

$text = preg_replace(
    '/\s*&middot;\s*Stok:\s*\{\{\s*\$selectedProduct->stok\s*\}\}/',
    '',
    $text,
    -1,
    $summaryCount
);

$text = str_replace(
    'Batalkan pesanan {{ $detailItem->order_code }}? Stok akan dikembalikan dan antrean dirapatkan.',
    'Batalkan pesanan {{ $detailItem->order_code }}? Antrean akan dirapatkan otomatis.',
    $text
);

$text = str_replace(
    "session()->flash('status', 'Pesanan dibatalkan. Stok dikembalikan dan antrean dirapatkan otomatis.');",
    "session()->flash('status', 'Pesanan dibatalkan dan antrean dirapatkan otomatis.');",
    $text
);

echo "  - label stok di Admin Pesanan: option {$optionCount}, ringkasan {$summaryCount}\n";

writeFileOrFail($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| ALUR BOOKING
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/partials/frontend/alur-booking.blade.php';
$text = readFileOrFail($root, $rel);

$text = str_replace(
    'Klik WhatsApp untuk menanyakan ukuran, bahan, stok, harga, atau kebutuhan khusus.',
    'Klik WhatsApp untuk menanyakan ukuran, bahan, harga, atau kebutuhan khusus.',
    $text
);

writeFileOrFail($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| NOTIFICATION BELL — hapus kategori Stok Menipis.
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/components/notification-bell.blade.php';
$text = readFileOrFail($root, $rel);

$text = removeLineMatching(
    $text,
    '/^[ \t]*\[[^\r\n]*\'Stok Menipis\'[^\r\n]*\],\R/m',
    'notifikasi Stok Menipis'
);

writeFileOrFail($root, $rel, $text);

/*
|--------------------------------------------------------------------------
| USER MODEL — pertahankan method unreadDashboardCount() agar kompatibel,
| tetapi stok tidak lagi menghasilkan notifikasi/badge.
|--------------------------------------------------------------------------
*/
$rel = 'app/Models/User.php';
$text = readFileOrFail($root, $rel);

$text = removeLineMatching(
    $text,
    '/^[ \t]*private const LOW_STOCK_THRESHOLD = 5;\R/m',
    'LOW_STOCK_THRESHOLD'
);

$text = preg_replace(
    '~public function unreadDashboardCount\(\): int\s*\{[\s\S]*?\n[ \t]*\}~',
    "public function unreadDashboardCount(): int\n    {\n        // Fitur stok produk dinonaktifkan; dipertahankan untuk kompatibilitas pemanggil lama.\n        return 0;\n    }",
    $text,
    1,
    $dashboardMethodCount
);

if ($dashboardMethodCount !== 1 && ! str_contains($text, 'Fitur stok produk dinonaktifkan')) {
    throw new RuntimeException('Method unreadDashboardCount() tidak berhasil dinonaktifkan.');
}

$text = str_replace(
    'return $this->unreadInteraksiCount() + $this->unreadPesananCount() + $this->unreadDashboardCount();',
    'return $this->unreadInteraksiCount() + $this->unreadPesananCount();',
    $text
);

writeFileOrFail($root, $rel, $text);

echo "REMOVE_STOCK_UI_OK\n";
'@

    $tmpPatch = Join-Path $env:TEMP "remove-product-stock-$stamp.php"
    [System.IO.File]::WriteAllText($tmpPatch, $phpPatch, $utf8NoBom)

    php $tmpPatch
    $patchExit = $LASTEXITCODE
    Remove-Item $tmpPatch -Force -ErrorAction SilentlyContinue

    if ($patchExit -ne 0) {
        throw "Patch source gagal."
    }

    Step "[3/6] Validasi syntax ..."
    foreach ($file in $files) {
        php -l $file | Out-Host

        if ($LASTEXITCODE -ne 0) {
            throw "Syntax error: $file"
        }
    }

    Step "[4/6] Backup nilai stok lama + nonaktifkan batas stok untuk semua produk ..."

    $dbPatch = @'
<?php

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$backupPath = $argv[1];

$rows = App\Models\Product::query()
    ->orderBy('id')
    ->get(['id', 'stok'])
    ->map(fn ($product) => [
        'id' => $product->id,
        'stok' => $product->getRawOriginal('stok'),
    ])
    ->values()
    ->all();

file_put_contents(
    $backupPath,
    json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

App\Models\Product::query()->update([
    'stok' => 1000000000,
]);

echo 'PRODUCTS_UPDATED='.count($rows).PHP_EOL;
'@

    $tmpDb = Join-Path $env:TEMP "remove-product-stock-db-$stamp.php"
    [System.IO.File]::WriteAllText($tmpDb, $dbPatch, $utf8NoBom)

    $stockBackupPath = Join-Path (Resolve-Path $backupDir) "product-stock-before.json"
    php $tmpDb $stockBackupPath
    $dbExit = $LASTEXITCODE
    Remove-Item $tmpDb -Force -ErrorAction SilentlyContinue

    if ($dbExit -ne 0) {
        throw "Gagal menonaktifkan batas stok di database."
    }

    Step "[5/6] Bersihkan cache ..."
    php artisan optimize:clear | Out-Host

    Step "[6/6] Selesai ..."
    Write-Host ""
    Write-Host "SELESAI" -ForegroundColor Green
    Write-Host ""
    Write-Host "Hasil:" -ForegroundColor Yellow
    Write-Host "  - Produk TIDAK dihapus." -ForegroundColor White
    Write-Host "  - Field Stok di Tambah/Edit Produk dihapus." -ForegroundColor White
    Write-Host "  - Kolom Stok di daftar Produk dihapus." -ForegroundColor White
    Write-Host "  - Stok tidak tampil di detail produk." -ForegroundColor White
    Write-Host "  - Tombol tidak lagi menampilkan 'Stok Habis'." -ForegroundColor White
    Write-Host "  - Label Stok pada Admin Pesanan dibersihkan." -ForegroundColor White
    Write-Host "  - Notifikasi 'Stok Menipis' dimatikan." -ForegroundColor White
    Write-Host "  - Sistem lama yang masih bergantung kolom stok tetap aman secara internal." -ForegroundColor White
    Write-Host ""
    Write-Host "Catatan teknis:" -ForegroundColor Yellow
    Write-Host "  Kolom database 'stok' BELUM di-drop agar alur pesanan/cart lama tidak rusak." -ForegroundColor White
    Write-Host "  Nilainya dibuat internal/unlimited dan tidak lagi perlu diatur admin." -ForegroundColor White
    Write-Host ""
    Write-Host "Backup file + nilai stok lama: $backupDir" -ForegroundColor DarkGray
}
catch {
    Write-Host ""
    Write-Host "PATCH GAGAL - mengembalikan file dari backup ..." -ForegroundColor Red
    Restore-Backups
    php artisan view:clear | Out-Null
    throw
}
