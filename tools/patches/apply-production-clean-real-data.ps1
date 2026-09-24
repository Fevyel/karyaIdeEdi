$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$files = @(
    ".\database\seeders\DatabaseSeeder.php",
    ".\database\seeders\AdminUserSeeder.php",
    ".\database\seeders\TestimonialSeeder.php",
    ".\app\Models\Testimonial.php",
    ".\resources\views\pages\frontend\booking.blade.php",
    ".\resources\views\pages\admin\edit-web.blade.php",
    ".\resources\views\pages\admin\dashboard.blade.php"
)

foreach ($file in $files) {
    if (-not (Test-Path $file)) {
        throw "File wajib tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-production-clean-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$dbCommitted = $false

Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host " PRODUCTION CLEAN - Admin = Data Asli Website, Tanpa Dummy" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Yellow
Write-Host "Tidak menghapus produk, kategori, pesanan, pelanggan, atau testimoni asli." -ForegroundColor DarkGray

function Backup-File {
    param([string]$File)

    $relative = $File.TrimStart('.', '\')
    $destination = Join-Path $backupDir $relative
    $destinationDir = Split-Path $destination -Parent

    New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    Copy-Item $File $destination -Force
}

function Restore-Files {
    foreach ($file in $files) {
        $relative = $file.TrimStart('.', '\')
        $backup = Join-Path $backupDir $relative

        if (Test-Path $backup) {
            Copy-Item $backup $file -Force
        }
    }
}

try {
    Step "[1/6] Backup source ..."
    foreach ($file in $files) {
        Backup-File $file
    }
    Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

    Step "[2/6] Bersihkan source dummy/orphan + perbaiki Dashboard ..."

    $sourcePatch = @'
<?php

$root = $argv[1] ?? getcwd();

function fullpath(string $root, string $rel): string {
    return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
}
function readText(string $root, string $rel): string {
    $path = fullpath($root, $rel);
    $text = file_get_contents($path);
    if ($text === false) throw new RuntimeException("Gagal membaca {$rel}");
    return $text;
}
function writeText(string $root, string $rel, string $text): void {
    $path = fullpath($root, $rel);
    if (file_put_contents($path, $text) === false) throw new RuntimeException("Gagal menulis {$rel}");
}
function regexReplace(string $text, string $pattern, string $replacement, string $label, int $min = 1, ?int $max = null): string {
    $updated = preg_replace($pattern, $replacement, $text, -1, $count);
    if ($updated === null) throw new RuntimeException("Regex error: {$label}");
    if ($count < $min || ($max !== null && $count > $max)) {
        throw new RuntimeException("Patch {$label} tidak sesuai ekspektasi. count={$count}");
    }
    echo "  - {$label}: {$count}\n";
    return $updated;
}

/* DatabaseSeeder production-safe */
writeText($root, 'database/seeders/DatabaseSeeder.php', <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Production-safe: tidak menyisipkan akun, kategori, testimoni,
     * atau data contoh apa pun ke database aktif.
     *
     * Data operasional dikelola melalui panel Admin.
     */
    public function run(): void
    {
        $this->command?->info('Production-safe seeder: tidak ada data demo yang ditambahkan.');
    }
}
PHP);

/* AdminUserSeeder no hardcoded secret */
writeText($root, 'database/seeders/AdminUserSeeder.php', <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Dinonaktifkan untuk production agar source code tidak menyimpan
     * email/password admin bawaan.
     *
     * Akun admin yang sudah ada di database TIDAK diubah atau dihapus.
     */
    public function run(): void
    {
        $this->command?->warn('AdminUserSeeder dinonaktifkan. Kelola akun admin dari database/proses provisioning yang aman.');
    }
}
PHP);

/* TestimonialSeeder no dummy */
writeText($root, 'database/seeders/TestimonialSeeder.php', <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Dinonaktifkan: testimoni/interaksi harus berasal dari pelanggan asli
     * melalui alur Lacak Pesanan atau dibuat admin secara sengaja.
     */
    public function run(): void
    {
        $this->command?->info('TestimonialSeeder dinonaktifkan: tidak ada testimoni demo yang ditambahkan.');
    }
}
PHP);

/* Testimonial model comment */
$rel = 'app/Models/Testimonial.php';
$text = readText($root, $rel);
$text = str_replace(
    '* - "Interaksi": komentar (untuk sekarang: dummy, nanti dari pembeli asli)'."\n".
    ' *   masuk dengan approval_status = pending -> admin menyetujui (approved)'."\n",
    '* - "Interaksi": komentar asli dari pembeli melalui halaman Lacak Pesanan'."\n".
    ' *   masuk dengan approval_status = pending -> admin menyetujui (approved)'."\n",
    $text,
    $commentCount
);
if ($commentCount === 0 && str_contains($text, 'untuk sekarang: dummy')) {
    throw new RuntimeException('Komentar lama Testimonial model tidak berhasil dibersihkan.');
}
writeText($root, $rel, $text);

/* Booking: hapus video sample fallback */
$rel = 'resources/views/pages/frontend/booking.blade.php';
$text = readText($root, $rel);
$text = str_replace(
    '// item bertipe video). Kalau masih kosong juga, pakai dummy.',
    '// item bertipe video). Kalau masih kosong juga, section video tidak ditampilkan.',
    $text
);

if (str_contains($text, '$dokFallbackVideos')) {
    $text = regexReplace(
        $text,
        '~\R[ \t]*\$dokFallbackVideos\s*=\s*\[\R.*?^[ \t]*\];\R~ms',
        "\n",
        'hapus daftar video sample',
        1,
        1
    );
}

$oldAssignment = <<<'BLADE'
        $dokVideoItems = count($dokVideoFromDok3) > 0
            ? $dokVideoFromDok3
            : (count($dokVideoFromLegacyGallery) > 0 ? $dokVideoFromLegacyGallery : $dokFallbackVideos);
BLADE;
$newAssignment = <<<'BLADE'
        $dokVideoItems = count($dokVideoFromDok3) > 0
            ? $dokVideoFromDok3
            : $dokVideoFromLegacyGallery;
BLADE;

if (str_contains($text, $oldAssignment)) {
    $text = str_replace($oldAssignment, $newAssignment, $text, $assignmentCount);
    echo "  - fallback video Dokumentasi: {$assignmentCount}\n";
} elseif (str_contains($text, '$dokFallbackVideos')) {
    throw new RuntimeException('Assignment fallback video tidak berhasil dibersihkan.');
}

if (str_contains($text, 'commondatastorage.googleapis.com/gtv-videos-bucket/sample/')) {
    throw new RuntimeException('URL video sample masih ada di booking.blade.php.');
}
writeText($root, $rel, $text);

/* Edit Web: hapus Pita Foto & Video yang sudah tidak dipakai frontend */
$rel = 'resources/views/pages/admin/edit-web.blade.php';
$text = readText($root, $rel);

$text = preg_replace(
    "~^[ \t]*\\['key'\\s*=>\\s*'dokumentasi-media'[^\\r\\n]*\\R~m",
    '',
    $text,
    -1,
    $sectionLineCount
);
echo "  - menu Pita Foto & Video: {$sectionLineCount}\n";

if (str_contains($text, 'public array $dokMediaKeys')) {
    $text = regexReplace(
        $text,
        '~\R[ \t]*// ---- Pita Foto & Video Dokumentasi.*?public array \$dokMediaUploadBaru = \[\];\R~s',
        "\n",
        'property dokMedia',
        1,
        1
    );
}

if (str_contains($text, 'function dokumentasiMediaDefaults')) {
    $text = regexReplace(
        $text,
        '~\R[ \t]*private function dokumentasiMediaDefaults\(\): array\s*\{\s*return \[\'items\' => \[\]\];\s*\}\R~s',
        "\n",
        'dokumentasiMediaDefaults',
        1,
        1
    );
}

if (str_contains($text, '$dokMediaData = HomeSection::dataFor')) {
    $text = regexReplace(
        $text,
        '~^[ \t]*\$dokMediaData = HomeSection::dataFor\(\'dokumentasi-media\', \$this->dokumentasiMediaDefaults\(\)\);\R[ \t]*\$this->muatItemDinamis\(\'dokMedia\', is_array\(\$dokMediaData\[\'items\'\] \?\? null\) \? \$dokMediaData\[\'items\'\] : \[\]\);\R~m',
        '',
        'mount dokMedia',
        1,
        1
    );
}

if (str_contains($text, 'public function getDokMediaPreviewUrlsProperty')) {
    $text = regexReplace(
        $text,
        '~\R[ \t]*public function getDokMediaPreviewUrlsProperty\(\): array.*?(?=\R[ \t]*/\*\*\R[ \t]*\* Decode data URL)~s',
        "\n",
        'helper dokMedia',
        1,
        1
    );
}

if (str_contains($text, 'public function saveDokumentasiMedia')) {
    $text = regexReplace(
        $text,
        '~\R[ \t]*public function saveDokumentasiMedia\(\): void.*?(?=\R[ \t]*/\*\* Klik salah satu swatch preset)~s',
        "\n",
        'saveDokumentasiMedia',
        1,
        1
    );
}

if (str_contains($text, "@elseif (\$activeSection === 'dokumentasi-media')")) {
    $text = preg_replace(
        '~\R?[ \t]*@elseif \(\$activeSection === \'dokumentasi-media\'\).*?@elseif \(\$activeSection === \'warna\'\)~s',
        "\n            @elseif (\$activeSection === 'warna')",
        $text,
        1,
        $uiCount
    );
    if ($uiCount !== 1) {
        throw new RuntimeException("UI dokumentasi-media gagal dibersihkan. count={$uiCount}");
    }
    echo "  - UI Pita Foto & Video: {$uiCount}\n";
}

if (str_contains($text, 'dokumentasi-media') || str_contains($text, 'dokMedia')) {
    throw new RuntimeException('Masih ada referensi dokumentasi-media/dokMedia di Edit Web.');
}
writeText($root, $rel, $text);

/* Dashboard: semua metrik dari data nyata */
$rel = 'resources/views/pages/admin/dashboard.blade.php';
$text = readText($root, $rel);

if (str_contains($text, 'private const MONTHLY_TARGET')) {
    $text = regexReplace(
        $text,
        '~\R[ \t]*/\*\*\R[ \t]*\* Target pendapatan bulanan.*?\R[ \t]*private const MONTHLY_TARGET = [^;]+;\R~s',
        "\n",
        'target pendapatan statis',
        1,
        1
    );
}

if (str_contains($text, '$targetAchievedPercent')) {
    $text = regexReplace(
        $text,
        '~\R[ \t]*\$targetAchievedPercent = self::MONTHLY_TARGET > 0\R[ \t]*\? min\(100, round\(\(\$revenueThisMonth / self::MONTHLY_TARGET\) \* 100\)\)\R[ \t]*: 0;\R~',
        "\n",
        'perhitungan target statis',
        1,
        1
    );

    $text = preg_replace(
        '~^[ \t]*\'targetAchievedPercent\' => \$targetAchievedPercent,\R~m',
        "            'revenueThisMonthCompact' => \$this->formatCompact((float) \$revenueThisMonth),\n",
        $text,
        1,
        $returnCount
    );
    if ($returnCount !== 1) {
        throw new RuntimeException('Return targetAchievedPercent tidak berhasil diganti.');
    }

    $text = str_replace(
        '{{ $targetAchievedPercent }}%',
        '{{ $revenueThisMonthCompact }}',
        $text,
        $targetUiCount
    );
    $text = str_replace(
        '>dari target<',
        '>bulan ini<',
        $text,
        $targetLabelCount
    );

    if ($targetUiCount !== 1 || $targetLabelCount !== 1) {
        throw new RuntimeException("UI target dashboard gagal diganti ({$targetUiCount}/{$targetLabelCount}).");
    }
    echo "  - pusat grafik dashboard memakai pendapatan aktual\n";
}

$oldProductCount = "Product::query()->count()";
$newProductCount = "Product::query()->where('status', 'aktif')->count()";
if (str_contains($text, $oldProductCount)) {
    $text = str_replace($oldProductCount, $newProductCount, $text, $productCount);
    echo "  - Produk Aktif memakai filter status aktif: {$productCount}\n";
}
if (str_contains($text, 'MONTHLY_TARGET') || str_contains($text, 'targetAchievedPercent')) {
    throw new RuntimeException('Hardcoded target masih tersisa di Dashboard.');
}
writeText($root, $rel, $text);

echo "SOURCE_PATCH_OK\n";

'@

    $tmpSourcePatch = Join-Path $env:TEMP "kie-production-clean-source-$stamp.php"
    [System.IO.File]::WriteAllText($tmpSourcePatch, $sourcePatch, $utf8NoBom)

    php $tmpSourcePatch (Resolve-Path ".")
    $sourceExit = $LASTEXITCODE

    Remove-Item $tmpSourcePatch -Force -ErrorAction SilentlyContinue

    if ($sourceExit -ne 0) {
        throw "Patch source gagal."
    }

    Step "[3/6] Validasi syntax semua file yang disentuh ..."

    foreach ($file in $files) {
        php -l $file | Out-Host

        if ($LASTEXITCODE -ne 0) {
            throw "Syntax error pada $file"
        }
    }

    Step "[4/6] Backup database + hapus dummy + sinkronkan 28 fallback ke HomeSection ..."

    $dbCleanup = @'
<?php

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$backupPath = $argv[1] ?? null;

if (! $backupPath) {
    throw new RuntimeException('Path backup database tidak diberikan.');
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

$dummySignatures = [
    ['Budi Santoso', 'Arsitek', 'Sofa custom dari Karya Ide Edi hasilnya rapi banget'],
    ['Rina Maharani', 'Interior Designer', 'Sering rekomendasikan Karya Ide Edi ke klien saya'],
    ['Ahmad Fauzi', 'Pengusaha', 'Kualitas lemarinya bagus, kokoh dan engselnya halus'],
    ['Dinda Putri', 'Ibu Rumah Tangga', 'Rak dapur yang saya beli pas banget sama ukuran ruangan'],
    ['Yoga Pratama', 'Karyawan Swasta', 'Tempat tidur kayu jatinya kokoh'],
    ['Siti Nurhaliza', 'Guru', 'Kursi belajar buat anak saya modelnya lucu dan kuat'],
    ['Bambang Wijaya', 'Wiraswasta', 'Meja kerja yang saya pesan sesuai gambar di katalog'],
    ['Maya Anggraini', 'Dokter', 'Set kursi ruang tamu nyaman dipakai duduk lama'],
    ['Fajar Ramadhan', 'Konsultan', 'Lemari pakaiannya oke, tapi komentar ini saya kirim dua kali'],
    ['Lestari Wulandari', 'Freelancer', 'Rak sepatunya bagus, tapi komentar saya ini isinya lebih ke pertanyaan'],
];

$backup = [
    'created_at' => now()->toIso8601String(),
    'test_user' => User::query()
        ->where('email', 'test@example.com')
        ->get()
        ->toArray(),
    'possible_demo_testimonials' => Testimonial::query()
        ->whereIn('customer_name', $dummyNames)
        ->whereNull('product_id')
        ->whereNull('transaction_id')
        ->get()
        ->toArray(),
    'stale_home_sections' => HomeSection::query()
        ->whereIn('section_key', ['why-choose-us', 'dokumentasi-media'])
        ->get()
        ->toArray(),
    'home_sections_before' => HomeSection::query()
        ->orderBy('section_key')
        ->get(['id', 'section_key', 'data', 'created_at', 'updated_at'])
        ->toArray(),
];

if (file_put_contents(
    $backupPath,
    json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
) === false) {
    throw new RuntimeException('Gagal membuat backup database JSON.');
}

function sourceUsesExactHomeSectionKey(string $key): bool
{
    $roots = [app_path(), resource_path('views')];
    $quoted = preg_quote($key, '~');
    $pattern = '~(?:dataFor|forSection)\(\s*[\'"]'.$quoted.'[\'"]~';

    foreach ($roots as $root) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $normalized = str_replace('\\', '/', $path);

            if (
                str_contains($normalized, '/.backup') ||
                str_contains($normalized, '.bak-') ||
                str_ends_with($normalized, '.bak')
            ) {
                continue;
            }

            if (! preg_match('/\.(php|blade\.php)$/i', $normalized)) {
                continue;
            }

            $text = file_get_contents($path);

            if ($text !== false && preg_match($pattern, $text)) {
                return true;
            }
        }
    }

    return false;
}

function loadEditWebComponent(): object
{
    $path = resource_path('views/pages/admin/edit-web.blade.php');
    $source = file_get_contents($path);

    if ($source === false) {
        throw new RuntimeException('Gagal membaca Edit Web untuk sinkronisasi default.');
    }

    $end = strpos($source, '?>');

    if ($end === false) {
        throw new RuntimeException('Batas PHP Edit Web tidak ditemukan.');
    }

    $php = substr($source, 0, $end + 2);

    $php = preg_replace(
        '/\bnew\s+(#\[Layout)/',
        'return new $1',
        $php,
        1,
        $count
    );

    if ($php === null || $count !== 1) {
        throw new RuntimeException('Gagal menyiapkan komponen Edit Web untuk membaca default.');
    }

    $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kie-edit-web-defaults-'.bin2hex(random_bytes(6)).'.php';

    if (file_put_contents($tmp, $php) === false) {
        throw new RuntimeException('Gagal membuat file sementara Edit Web.');
    }

    try {
        $component = include $tmp;
    } finally {
        @unlink($tmp);
    }

    if (! is_object($component)) {
        throw new RuntimeException('Komponen Edit Web tidak berhasil dimuat.');
    }

    return $component;
}

function callPrivateDefault(object $component, string $method): array
{
    if (! method_exists($component, $method)) {
        throw new RuntimeException("Method default tidak ditemukan: {$method}");
    }

    $reflection = new ReflectionMethod($component, $method);
    $value = $reflection->invoke($component);

    if (! is_array($value)) {
        throw new RuntimeException("Method {$method} tidak mengembalikan array.");
    }

    return $value;
}

$expectedKeys = [
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

$result = DB::transaction(function () use ($dummySignatures, $expectedKeys): array {
    $deletedUsers = User::query()
        ->where('email', 'test@example.com')
        ->where('name', 'Test User')
        ->delete();

    $deletedTestimonials = 0;

    foreach ($dummySignatures as [$name, $job, $commentPrefix]) {
        $matches = Testimonial::query()
            ->where('customer_name', $name)
            ->where('jabatan', $job)
            ->whereNull('product_id')
            ->whereNull('transaction_id')
            ->where('comment', 'like', $commentPrefix.'%')
            ->get();

        foreach ($matches as $match) {
            if (Testimonial::query()->where('parent_id', $match->id)->exists()) {
                throw new RuntimeException(
                    "Testimoni demo #{$match->id} memiliki komentar turunan. Cleanup dihentikan agar data tidak terhapus tanpa review."
                );
            }

            $match->delete();
            $deletedTestimonials++;
        }
    }

    foreach (['why-choose-us', 'dokumentasi-media'] as $staleKey) {
        if (sourceUsesExactHomeSectionKey($staleKey)) {
            throw new RuntimeException(
                "HomeSection lama '{$staleKey}' masih direferensikan source aktif. Tidak dihapus."
            );
        }
    }

    $deletedStaleSections = HomeSection::query()
        ->whereIn('section_key', ['why-choose-us', 'dokumentasi-media'])
        ->delete();

    $component = loadEditWebComponent();

    $payloads = [
        'faq' => callPrivateDefault($component, 'faqDefaults'),
        'dokumentasi-3' => callPrivateDefault($component, 'dokumentasi3Defaults'),
        'dokumentasi-foto' => callPrivateDefault($component, 'dokumentasiFotoDefaults'),
        'privacy-hero' => callPrivateDefault($component, 'privacyHeroDefaults'),
        'privacy-contact' => callPrivateDefault($component, 'privacyContactDefaults'),
        'cookies-hero' => callPrivateDefault($component, 'cookiesHeroDefaults'),
        'cookies-summary' => callPrivateDefault($component, 'cookiesSummaryDefaults'),
        'cookies-categories' => callPrivateDefault($component, 'cookiesCategoriesDefaults'),
        'cookies-browser' => callPrivateDefault($component, 'cookiesBrowserDefaults'),
        'cookies-contact' => callPrivateDefault($component, 'cookiesContactDefaults'),
        'terms-hero' => callPrivateDefault($component, 'termsHeroDefaults'),
        'terms-contact' => callPrivateDefault($component, 'termsContactDefaults'),
        'our-craftsmen-cta' => callPrivateDefault($component, 'ourCraftsmenCtaDefaults'),
    ];

    foreach (callPrivateDefault($component, 'privacyContentDefaults') as $key => $data) {
        $payloads['privacy-'.$key] = $data;
    }

    foreach (callPrivateDefault($component, 'termsContentDefaults') as $key => $data) {
        $payloads['terms-'.$key] = $data;
    }

    $insertedDefaults = [];

    foreach ($payloads as $key => $data) {
        if (! HomeSection::query()->where('section_key', $key)->exists()) {
            HomeSection::query()->create([
                'section_key' => $key,
                'data' => $data,
            ]);
            $insertedDefaults[] = $key;
        }
    }

    $storedKeys = HomeSection::query()->pluck('section_key')->all();
    $missing = array_values(array_diff($expectedKeys, $storedKeys));

    if ($missing !== []) {
        throw new RuntimeException(
            'Masih ada section admin/frontend yang belum tersimpan: '.implode(', ', $missing)
        );
    }

    $remainingTestUser = User::query()
        ->where('email', 'test@example.com')
        ->where('name', 'Test User')
        ->count();

    if ($remainingTestUser > 0) {
        throw new RuntimeException('Test User masih ada setelah cleanup.');
    }

    $remainingDemo = 0;

    foreach ($dummySignatures as [$name, $job, $commentPrefix]) {
        $remainingDemo += Testimonial::query()
            ->where('customer_name', $name)
            ->where('jabatan', $job)
            ->whereNull('product_id')
            ->whereNull('transaction_id')
            ->where('comment', 'like', $commentPrefix.'%')
            ->count();
    }

    if ($remainingDemo > 0) {
        throw new RuntimeException("Masih ada {$remainingDemo} testimoni demo.");
    }

    $staleCount = HomeSection::query()
        ->whereIn('section_key', ['why-choose-us', 'dokumentasi-media'])
        ->count();

    if ($staleCount > 0) {
        throw new RuntimeException('HomeSection stale masih ada.');
    }

    return [
        'deleted_users' => $deletedUsers,
        'deleted_testimonials' => $deletedTestimonials,
        'deleted_stale_sections' => $deletedStaleSections,
        'inserted_defaults' => $insertedDefaults,
    ];
});

echo 'DELETED_TEST_USERS='.$result['deleted_users'].PHP_EOL;
echo 'DELETED_DEMO_TESTIMONIALS='.$result['deleted_testimonials'].PHP_EOL;
echo 'DELETED_STALE_HOME_SECTIONS='.$result['deleted_stale_sections'].PHP_EOL;
echo 'MATERIALIZED_DEFAULTS='.count($result['inserted_defaults']).PHP_EOL;

foreach ($result['inserted_defaults'] as $key) {
    echo '  + '.$key.PHP_EOL;
}

echo PHP_EOL.'=== FINAL DATA CHECK ==='.PHP_EOL;
echo 'Products total        : '.Product::query()->count().PHP_EOL;
echo 'Products active       : '.Product::query()->where('status', 'aktif')->count().PHP_EOL;
echo 'Orders total          : '.Transaction::query()->count().PHP_EOL;
echo 'Testimonials total    : '.Testimonial::query()->count().PHP_EOL;
echo 'HomeSections stored   : '.HomeSection::query()->whereIn('section_key', $expectedKeys)->count().'/'.count($expectedKeys).PHP_EOL;

$setting = Setting::current();

foreach ([
    'BCA atas nama' => $setting->bca_account_name,
    'BRI atas nama' => $setting->bri_account_name,
    'DANA atas nama' => $setting->dana_account_name,
] as $label => $value) {
    if (blank($value)) {
        echo '[INFO] '.$label.' masih kosong - isi dari Admin > Pengaturan bila ingin nama tujuan tampil di halaman transfer.'.PHP_EOL;
    }
}

$header = HomeSection::query()->where('section_key', 'header')->value('data') ?? [];
if (is_string($header)) {
    $header = json_decode($header, true) ?: [];
}
$stats = is_array($header['stats'] ?? null) ? $header['stats'] : [];
$statValues = array_map(fn ($item) => is_array($item) ? ($item['value'] ?? null) : null, $stats);

if (in_array('500+', $statValues, true) || in_array('2.000+', $statValues, true) || in_array('12th', $statValues, true)) {
    echo '[INFO] Statistik Hero masih memakai salah satu nilai bawaan (500+ / 2.000+ / 12th). Pastikan angka itu memang fakta bisnis; jika tidak, ubah di Admin > Edit Web > Beranda > Hero.'.PHP_EOL;
}

$owner = HomeSection::query()->where('section_key', 'our-craftsmen-daftar')->value('data') ?? [];
if (is_string($owner)) {
    $owner = json_decode($owner, true) ?: [];
}

if (($owner['name'] ?? null) === 'Pemilik Karya Ide Edi') {
    echo '[INFO] Nama profil Our Craftsmen masih generik "Pemilik Karya Ide Edi". Isi nama asli lewat Admin > Edit Web > Our Craftsmen > Profil Pemilik.'.PHP_EOL;
}

echo 'DATABASE_PRODUCTION_CLEAN_OK'.PHP_EOL;

'@

    $tmpDbCleanup = Join-Path $env:TEMP "kie-production-clean-db-$stamp.php"
    [System.IO.File]::WriteAllText($tmpDbCleanup, $dbCleanup, $utf8NoBom)

    $dbBackupPath = Join-Path (Resolve-Path $backupDir) "database-before-cleanup.json"

    php $tmpDbCleanup $dbBackupPath
    $dbExit = $LASTEXITCODE

    Remove-Item $tmpDbCleanup -Force -ErrorAction SilentlyContinue

    if ($dbExit -ne 0) {
        throw "Cleanup/sinkronisasi database gagal. Transaksi database otomatis dibatalkan."
    }

    $dbCommitted = $true

    Step "[5/6] Clear cache ..."
    php artisan optimize:clear | Out-Host

    Step "[6/6] Verifikasi cepat ..."
    $auditIssues = @()

    $booking = [System.IO.File]::ReadAllText((Resolve-Path ".\resources\views\pages\frontend\booking.blade.php"))
    $editWeb = [System.IO.File]::ReadAllText((Resolve-Path ".\resources\views\pages\admin\edit-web.blade.php"))
    $dashboard = [System.IO.File]::ReadAllText((Resolve-Path ".\resources\views\pages\admin\dashboard.blade.php"))
    $databaseSeeder = [System.IO.File]::ReadAllText((Resolve-Path ".\database\seeders\DatabaseSeeder.php"))

    if ($booking.Contains("commondatastorage.googleapis.com/gtv-videos-bucket/sample/")) {
        $auditIssues += "Video sample masih ada."
    }

    if ($editWeb.Contains("'key' => 'dokumentasi-media'") -or $editWeb.Contains("dokMedia")) {
        $auditIssues += "Pita Foto & Video lama masih ada di Edit Web."
    }

    if ($dashboard.Contains("MONTHLY_TARGET") -or $dashboard.Contains("targetAchievedPercent")) {
        $auditIssues += "Target dashboard hardcoded masih ada."
    }

    if (-not $dashboard.Contains("Product::query()->where('status', 'aktif')->count()")) {
        $auditIssues += "Produk Aktif belum memakai filter status aktif."
    }

    if ($databaseSeeder.Contains("test@example.com") -or $databaseSeeder.Contains("TestimonialSeeder::class")) {
        $auditIssues += "DatabaseSeeder masih mengandung data demo."
    }

    if ($auditIssues.Count -gt 0) {
        Write-Host ""
        Write-Host "SOURCE VERIFY WARNING:" -ForegroundColor Yellow
        foreach ($issue in $auditIssues) {
            Write-Host "  - $issue" -ForegroundColor Yellow
        }
        throw "Verifikasi source belum 100% clean."
    }

    Write-Host ""
    Write-Host "======================================================================" -ForegroundColor Green
    Write-Host " PRODUCTION CLEAN SELESAI" -ForegroundColor Green
    Write-Host "======================================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Yang sudah dibersihkan:" -ForegroundColor Yellow
    Write-Host "  - Test User dummy di database dihapus (hanya exact Test User)." -ForegroundColor White
    Write-Host "  - Testimoni dummy seeder dihapus berdasarkan signature demo yang exact." -ForegroundColor White
    Write-Host "  - DatabaseSeeder tidak lagi menyuntikkan data demo." -ForegroundColor White
    Write-Host "  - AdminUserSeeder tidak lagi menyimpan credential bawaan di source." -ForegroundColor White
    Write-Host "  - TestimonialSeeder dinonaktifkan." -ForegroundColor White
    Write-Host "  - Video sample Google di Dokumentasi dihapus." -ForegroundColor White
    Write-Host "  - Pita Foto & Video orphan di Edit Web dihapus." -ForegroundColor White
    Write-Host "  - Dashboard tidak lagi memakai target Rp35 juta hardcoded." -ForegroundColor White
    Write-Host "  - Tengah grafik Dashboard sekarang menampilkan pendapatan bulan ini yang nyata." -ForegroundColor White
    Write-Host "  - Produk Aktif sekarang benar-benar hanya menghitung produk status aktif." -ForegroundColor White
    Write-Host "  - 28 section yang sebelumnya fallback disimpan ke HomeSection tanpa mengubah data existing." -ForegroundColor White
    Write-Host "  - HomeSection lama why-choose-us / dokumentasi-media dibersihkan jika tidak dipakai source aktif." -ForegroundColor White
    Write-Host ""
    Write-Host "TIDAK disentuh:" -ForegroundColor Yellow
    Write-Host "  - produk asli" -ForegroundColor White
    Write-Host "  - kategori asli" -ForegroundColor White
    Write-Host "  - pesanan asli" -ForegroundColor White
    Write-Host "  - pelanggan asli" -ForegroundColor White
    Write-Host "  - testimoni/interaksi asli" -ForegroundColor White
    Write-Host "  - nomor rekening BCA/BRI/DANA" -ForegroundColor White
    Write-Host ""
    Write-Host "Backup source + snapshot database: $backupDir" -ForegroundColor DarkGray
}
catch {
    Write-Host ""
    Write-Host "PATCH GAGAL: $($_.Exception.Message)" -ForegroundColor Red

    if (-not $dbCommitted) {
        Write-Host "Mengembalikan source dari backup ..." -ForegroundColor Yellow
        Restore-Files
        php artisan view:clear | Out-Null
        Write-Host "Source sudah dikembalikan. Database cleanup tidak ter-commit." -ForegroundColor Yellow
    }
    else {
        Write-Host "Database sudah commit sebelum error terakhir." -ForegroundColor Yellow
        Write-Host "Backup lengkap tersedia di: $backupDir" -ForegroundColor Yellow
    }

    throw
}
