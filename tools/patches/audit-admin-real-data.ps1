$ErrorActionPreference = 'Stop'

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$tempPhp = Join-Path $env:TEMP "audit-real-data-$stamp.php"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host " AUDIT READ-ONLY - Admin vs Data Asli Website" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host "Script ini TIDAK mengubah database atau file project." -ForegroundColor DarkGray

$php = @'
<?php

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function yn(bool $value): string
{
    return $value ? 'YA' : 'TIDAK';
}

function valueOrEmpty(mixed $value): string
{
    $value = trim((string) $value);
    return $value === '' ? '[KOSONG]' : $value;
}

function line(string $label, mixed $value): void
{
    echo str_pad($label, 34).": ".$value.PHP_EOL;
}

echo PHP_EOL."=== 1. DATABASE AKTUAL ===".PHP_EOL;
line('Driver', DB::connection()->getDriverName());
line('Database', DB::connection()->getDatabaseName());

$settings = Setting::query()->find(1);

line('Baris settings id=1 ada', yn($settings !== null));

if ($settings) {
    line('Nama website', valueOrEmpty($settings->site_name));
    line('Tagline', valueOrEmpty($settings->tagline));
    line('WhatsApp', valueOrEmpty($settings->whatsapp));
    line('Email', valueOrEmpty($settings->email));
    line('Alamat', valueOrEmpty($settings->alamat));
    line('Instagram', valueOrEmpty($settings->instagram_url));
    line('TikTok', valueOrEmpty($settings->tiktok_url));
    line('Facebook', valueOrEmpty($settings->facebook_url));

    echo PHP_EOL."--- Rekening Pembayaran ---".PHP_EOL;
    line('BCA nomor', valueOrEmpty($settings->bca_account_number));
    line('BCA atas nama', valueOrEmpty($settings->bca_account_name));
    line('BRI nomor', valueOrEmpty($settings->bri_account_number));
    line('BRI atas nama', valueOrEmpty($settings->bri_account_name));
    line('DANA nomor', valueOrEmpty($settings->dana_account_number));
    line('DANA atas nama', valueOrEmpty($settings->dana_account_name));
}

echo PHP_EOL."=== 2. DATA OPERASIONAL ===".PHP_EOL;
line('Total produk', Product::query()->count());
line('Produk aktif', Product::query()->where('status', 'aktif')->count());
line('Produk nonaktif', Product::query()->where('status', '!=', 'aktif')->count());
line('Total kategori', Category::query()->count());
line('Kategori aktif', Category::query()->where('is_active', true)->count());
line('Total pesanan', Transaction::query()->count());
line('Pesanan aktif', Transaction::query()->whereIn('status', Transaction::ACTIVE_STATUSES)->count());
line('Pesanan selesai', Transaction::query()->where('status', 'completed')->count());
line('Pesanan dibatalkan', Transaction::query()->where('status', 'cancelled')->count());
line('Total testimoni/interaksi', Testimonial::query()->count());
line('Interaksi pending', Testimonial::query()->where('approval_status', 'pending')->count());
line('Testimoni approved aktif', Testimonial::query()->where('approval_status', 'approved')->where('is_active', true)->count());

echo PHP_EOL."=== 3. CEK DATA DUMMY YANG DIKENAL ===".PHP_EOL;

$dummyUser = User::query()->where('email', 'test@example.com')->first();
line('User test@example.com ada', yn($dummyUser !== null));
if ($dummyUser) {
    line('Nama user dummy', $dummyUser->name);
}

$dummyNames = [
    'Budi Santoso',
    'Rina Maharani',
    'Ahmad Fauzi',
    'Dinda Putri',
    'Yoga Pratama',
    'Siti Nurhaliza',
    'Bambang Wijaya',
    'Maya Anggraini',
    'Fajar Ramadhan',
    'Lestari Wulandari',
];

$dummyTestimonials = Testimonial::query()
    ->whereIn('customer_name', $dummyNames)
    ->whereNull('product_id')
    ->get(['id', 'customer_name', 'approval_status']);

line('Dummy TestimonialSeeder terdeteksi', $dummyTestimonials->count());

foreach ($dummyTestimonials as $row) {
    echo "  - #{$row->id} {$row->customer_name} [{$row->approval_status}]".PHP_EOL;
}

$sampleMediaSections = HomeSection::query()
    ->where('data', 'like', '%commondatastorage.googleapis.com/gtv-videos-bucket/sample/%')
    ->pluck('section_key')
    ->all();

line(
    'Sample Google video di database',
    $sampleMediaSections === [] ? 'TIDAK' : 'YA -> '.implode(', ', $sampleMediaSections)
);

echo PHP_EOL."=== 4. HOME SECTIONS - ADMIN EDIT WEB ===".PHP_EOL;

$expected = [
    'header',
    'keunggulan',
    'sejak-berdiri',
    'produk-unggulan',
    'kategori',
    'testimoni',
    'keahlian',
    'faq',
    'lokasi',
    'profil-toko',
    'tentang-kami-2',
    'sejarah',
    'nilai-kami',
    'why-choose-us-profil',
    'produk',
    'dokumentasi',
    'dokumentasi-3',
    'dokumentasi-foto',
    'sustainability-hero',
    'sustainability-points',
    'privacy-hero',
    'privacy-information',
    'privacy-usage',
    'privacy-security',
    'privacy-sharing',
    'privacy-rights',
    'privacy-changes',
    'privacy-contact',
    'cookies-hero',
    'cookies-summary',
    'cookies-categories',
    'cookies-browser',
    'cookies-contact',
    'terms-hero',
    'terms-acceptance',
    'terms-custom-order',
    'terms-payment',
    'terms-delivery',
    'terms-cancellation',
    'terms-warranty',
    'terms-copyright',
    'terms-liability',
    'terms-changes',
    'terms-contact',
    'our-craftsmen-hero',
    'our-craftsmen-daftar',
    'our-craftsmen-cta',
];

$stored = HomeSection::query()->pluck('section_key')->all();
$missing = array_values(array_diff($expected, $stored));
$unexpected = array_values(array_diff($stored, $expected));

line('Section frontend/admin tersimpan', count(array_intersect($expected, $stored)).'/'.count($expected));
line('Section belum pernah disimpan', count($missing));

foreach ($missing as $key) {
    echo "  - fallback default masih dipakai: {$key}".PHP_EOL;
}

line('Section di luar daftar aktif', count($unexpected));
foreach ($unexpected as $key) {
    echo "  - {$key}".PHP_EOL;
}

echo PHP_EOL."=== 5. RINGKASAN DATABASE ===".PHP_EOL;

$warnings = [];

if ($dummyUser) {
    $warnings[] = 'Ada user dummy test@example.com.';
}

if ($dummyTestimonials->isNotEmpty()) {
    $warnings[] = 'Ada testimoni dummy dari TestimonialSeeder.';
}

if ($sampleMediaSections !== []) {
    $warnings[] = 'Ada URL video sample di home_sections.';
}

if (! $settings) {
    $warnings[] = 'Settings id=1 belum ada.';
} else {
    foreach ([
        'site_name' => $settings->site_name,
        'whatsapp' => $settings->whatsapp,
        'alamat' => $settings->alamat,
    ] as $field => $value) {
        if (blank($value)) {
            $warnings[] = "Pengaturan penting {$field} masih kosong.";
        }
    }
}

if ($warnings === []) {
    echo "[DB PASS] Tidak ditemukan dummy yang dikenal pada database aktif.".PHP_EOL;
} else {
    echo "[DB WARN] Ditemukan ".count($warnings)." hal yang perlu dicek:".PHP_EOL;
    foreach ($warnings as $warning) {
        echo "  - {$warning}".PHP_EOL;
    }
}
'@

[System.IO.File]::WriteAllText($tempPhp, $php, $utf8NoBom)

try {
    php $tempPhp
    if ($LASTEXITCODE -ne 0) {
        throw "Audit database gagal."
    }

    Write-Host "`n=== 6. AUDIT SOURCE CODE ===" -ForegroundColor Cyan

    $checks = @()

    $databaseSeeder = ".\database\seeders\DatabaseSeeder.php"
    $booking = ".\resources\views\pages\frontend\booking.blade.php"
    $editWeb = ".\resources\views\pages\admin\edit-web.blade.php"
    $dashboard = ".\resources\views\pages\admin\dashboard.blade.php"
    $craftsmen = ".\resources\views\pages\frontend\pengrajin.blade.php"
    $trackingController = ".\app\Http\Controllers\TrackingController.php"

    $dbSeederText = [System.IO.File]::ReadAllText((Resolve-Path $databaseSeeder))
    $bookingText = [System.IO.File]::ReadAllText((Resolve-Path $booking))
    $editWebText = [System.IO.File]::ReadAllText((Resolve-Path $editWeb))
    $dashboardText = [System.IO.File]::ReadAllText((Resolve-Path $dashboard))
    $craftsmenText = [System.IO.File]::ReadAllText((Resolve-Path $craftsmen))
    $trackingText = [System.IO.File]::ReadAllText((Resolve-Path $trackingController))

    if ($dbSeederText.Contains("test@example.com")) {
        $checks += "[WARN] DatabaseSeeder masih membuat Test User."
    } else {
        $checks += "[PASS] DatabaseSeeder tidak membuat Test User."
    }

    if ($dbSeederText.Contains("TestimonialSeeder::class")) {
        $checks += "[WARN] DatabaseSeeder masih memanggil TestimonialSeeder dummy."
    } else {
        $checks += "[PASS] DatabaseSeeder tidak memanggil TestimonialSeeder dummy."
    }

    if ($bookingText.Contains("commondatastorage.googleapis.com/gtv-videos-bucket/sample/")) {
        $checks += "[WARN] Dokumentasi masih punya fallback video sample/dummy."
    } else {
        $checks += "[PASS] Dokumentasi tidak punya fallback video sample."
    }

    $adminHasMediaStrip = $editWebText.Contains("'key' => 'dokumentasi-media'")
    $frontHasMediaStrip = $bookingText.Contains("dataFor('dokumentasi-media'") -or $bookingText.Contains("dokumentasi-media")

    if ($adminHasMediaStrip -and -not $frontHasMediaStrip) {
        $checks += "[WARN] Edit Web masih punya 'Pita Foto & Video', tetapi frontend Dokumentasi sudah tidak memakainya."
    } else {
        $checks += "[PASS] Menu Dokumentasi admin sinkron dengan frontend."
    }

    if ($dashboardText.Contains("private const MONTHLY_TARGET")) {
        $checks += "[WARN] Dashboard masih memakai target pendapatan statis/hardcoded."
    } else {
        $checks += "[PASS] Dashboard tidak memakai target pendapatan hardcoded."
    }

    if ($dashboardText.Contains("Product::query()->count()") -and $dashboardText.Contains("'label' => 'Produk Aktif'")) {
        $checks += "[WARN] Kartu 'Produk Aktif' menghitung SEMUA produk, bukan hanya status aktif."
    } else {
        $checks += "[PASS] Kartu Produk Aktif memakai filter status aktif."
    }

    if ($craftsmenText.Contains("dataFor('our-craftsmen-hero'") -and
        $craftsmenText.Contains("dataFor('our-craftsmen-daftar'") -and
        $craftsmenText.Contains("dataFor('our-craftsmen-cta'")) {
        $checks += "[PASS] Our Craftsmen sudah tersambung ke Edit Web/HomeSection."
    } else {
        $checks += "[WARN] Our Craftsmen belum sepenuhnya tersambung ke HomeSection."
    }

    if ($trackingText.Contains("Testimonial::query()->create([") -and
        $trackingText.Contains("'transaction_id' => `$transaction->id")) {
        $checks += "[PASS] Interaksi pelanggan asli sudah masuk dari halaman tracking ke tabel testimonials."
    } else {
        $checks += "[WARN] Alur interaksi pelanggan asli tidak terdeteksi."
    }

    foreach ($check in $checks) {
        if ($check.StartsWith("[PASS]")) {
            Write-Host $check -ForegroundColor Green
        } else {
            Write-Host $check -ForegroundColor Yellow
        }
    }

    $warningCount = @($checks | Where-Object { $_.StartsWith("[WARN]") }).Count

    Write-Host ""
    Write-Host "======================================================================" -ForegroundColor Yellow

    if ($warningCount -eq 0) {
        Write-Host " HASIL: SOURCE CODE CLEAN - tidak ada warning yang dikenal." -ForegroundColor Green
    } else {
        Write-Host " HASIL: MASIH ADA $warningCount WARNING SOURCE CODE." -ForegroundColor Yellow
    }

    Write-Host "======================================================================" -ForegroundColor Yellow
}
finally {
    Remove-Item $tempPhp -Force -ErrorAction SilentlyContinue
}
