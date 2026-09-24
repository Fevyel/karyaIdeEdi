$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-remove-career-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-NoBom($Path, $Content) {
    $full = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $Path))
    $dir = [System.IO.Path]::GetDirectoryName($full)

    if (-not [System.IO.Directory]::Exists($dir)) {
        [System.IO.Directory]::CreateDirectory($dir) | Out-Null
    }

    [System.IO.File]::WriteAllText($full, $Content, $utf8NoBom)
}

Write-Host "====================================================================" -ForegroundColor Yellow
Write-Host " Hapus Shipping & Returns + Hapus Seluruh Fitur Career/Loker" -ForegroundColor Yellow
Write-Host "====================================================================" -ForegroundColor Yellow

$careerFiles = @(
    ".\app\Models\JobVacancy.php",
    ".\app\Models\JobApplication.php",
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\app\Services\CareerSelectionService.php",
    ".\app\Console\Commands\FinalizeCareerSelections.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\pages\admin\file-preview-unsupported.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\lamar-kerja.blade.php",
    ".\resources\views\pages\frontend\hasil-lamaran.blade.php",
    ".\resources\views\pages\frontend\cek-lamaran.blade.php",
    ".\resources\views\emails\career-decision.blade.php"
)

$survivingFiles = @(
    ".\routes\web.php",
    ".\routes\console.php",
    ".\resources\views\layouts\admin-panel.blade.php",
    ".\resources\views\components\admin\nav-badge.blade.php",
    ".\resources\views\components\notification-bell.blade.php",
    ".\resources\views\partials\frontend\footer.blade.php"
)

Step "[1/8] Backup semua file Career dan file yang akan dibersihkan ..."

$backupTargets = @($careerFiles + $survivingFiles)

$migrationFiles = @(
    Get-ChildItem ".\database\migrations" -File -Filter "*.php" -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -match '(?i)(job_vacanc|job_application|career)' } |
        ForEach-Object { $_.FullName }
)

foreach ($item in $backupTargets) {
    if (Test-Path $item) {
        $relative = $item.TrimStart('.', '\')
        $dest = Join-Path $backupDir $relative
        $destDir = Split-Path $dest -Parent
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        Copy-Item $item $dest -Force
    }
}

foreach ($full in $migrationFiles) {
    $relative = $full.Substring((Get-Location).Path.Length).TrimStart('\')
    $dest = Join-Path $backupDir $relative
    $destDir = Split-Path $dest -Parent
    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    Copy-Item $full $dest -Force
}

if (Test-Path ".\storage\app\job-applications") {
    $storageBackup = Join-Path $backupDir "storage\app\job-applications"
    New-Item -ItemType Directory -Path (Split-Path $storageBackup -Parent) -Force | Out-Null
    Copy-Item ".\storage\app\job-applications" $storageBackup -Recurse -Force
}

Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/8] Export data Career lalu hapus tabel database ..."

$cleanupMigration = ".\database\migrations\2026_09_24_235959_remove_career_feature_tables.php"

$migrationContent = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $backupDir = base_path('__BACKUP_DIR__');
        File::ensureDirectoryExists($backupDir);

        if (Schema::hasTable('job_applications')) {
            File::put(
                $backupDir.'/job_applications.json',
                json_encode(
                    DB::table('job_applications')->orderBy('id')->get(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        }

        if (Schema::hasTable('job_vacancies')) {
            File::put(
                $backupDir.'/job_vacancies.json',
                json_encode(
                    DB::table('job_vacancies')->orderBy('id')->get(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        }

        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_vacancies');
    }

    public function down(): void
    {
        // Feature Career sengaja dihapus. Restore dilakukan dari backup bila benar-benar dibutuhkan.
    }
};
'@

$backupForPhp = $backupDir.Replace('\', '/')
$migrationContent = $migrationContent.Replace('__BACKUP_DIR__', $backupForPhp)
Write-NoBom $cleanupMigration $migrationContent

php artisan migrate
if ($LASTEXITCODE -ne 0) {
    throw "Gagal menghapus tabel Career. Project belum dilanjutkan. Backup: $backupDir"
}

Write-Host "  - Data Career diekspor ke JSON di backup." -ForegroundColor Green
Write-Host "  - Tabel job_applications dan job_vacancies dihapus." -ForegroundColor Green

Step "[3/8] Membersihkan routes, sidebar, badge, notification bell, footer ..."

$patchPhp = @'
<?php

$root = $argv[1] ?? getcwd();

function full_path(string $root, string $relative): string
{
    return rtrim($root, DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function read_text(string $root, string $relative): ?string
{
    $path = full_path($root, $relative);

    if (!is_file($path)) {
        return null;
    }

    $content = file_get_contents($path);

    if ($content === false) {
        throw new RuntimeException("Gagal membaca {$relative}");
    }

    return $content;
}

function write_text(string $root, string $relative, string $content): void
{
    $path = full_path($root, $relative);

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("Gagal menulis {$relative}");
    }
}

function preg_clean(string $text, string $pattern, string $replacement = ''): string
{
    $result = preg_replace($pattern, $replacement, $text);

    if ($result === null) {
        throw new RuntimeException("Regex gagal: {$pattern}");
    }

    return $result;
}

/*
|--------------------------------------------------------------------------
| routes/web.php
|--------------------------------------------------------------------------
*/
$rel = 'routes/web.php';
$text = read_text($root, $rel);

if ($text !== null) {
    // Public Career routes.
    $text = preg_clean(
        $text,
        '~^[ \t]*Route::view\(\s*[\'"]/karier[\'"][^;]*;\h*\R?~mi'
    );

    $text = preg_clean(
        $text,
        '~^[ \t]*Route::(?:get|post)\(\s*[\'"]/karier[^;]*;\h*\R?~mi'
    );

    // Multiline public Career routes using ->whereIn() etc.
    $text = preg_clean(
        $text,
        '~^[ \t]*Route::(?:get|post)\(\s*[\'"]/karier.*?->name\(\s*[\'"]careers\.[^\'"]+[\'"]\s*\);\h*\R?~msi'
    );

    // Admin Loker main route.
    $text = preg_clean(
        $text,
        '~^[ \t]*Route::livewire\(\s*[\'"]/loker[\'"].*?;\h*\R?~mi'
    );

    // Admin applicant file route closure.
    $text = preg_clean(
        $text,
        '~\R?[ \t]*Route::get\(\s*[\'"]/loker/pelamar/\{application\}/file/\{file\}[\'"].*?->name\(\s*[\'"]jobs\.applications\.file[\'"]\s*\);\h*\R?~msi'
    );

    // Any leftover named Career/Loker route statement.
    $text = preg_clean(
        $text,
        '~^[ \t]*Route::.*?->name\(\s*[\'"](?:careers\.[^\'"]+|jobs(?:\.[^\'"]+)*)[\'"]\s*\);\h*\R?~mi'
    );

    write_text($root, $rel, $text);
}

/*
|--------------------------------------------------------------------------
| routes/console.php
|--------------------------------------------------------------------------
*/
$rel = 'routes/console.php';
$text = read_text($root, $rel);

if ($text !== null) {
    $text = preg_clean(
        $text,
        '~^[ \t]*Schedule::command\(\s*[\'"]career:finalize-selections[\'"]\s*\).*?;\h*\R?~mi'
    );

    if (!str_contains($text, 'Schedule::')) {
        $text = preg_clean(
            $text,
            '~^[ \t]*use Illuminate\\\\Support\\\\Facades\\\\Schedule;\h*\R?~mi'
        );
    }

    write_text($root, $rel, $text);
}

/*
|--------------------------------------------------------------------------
| Admin sidebar
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/layouts/admin-panel.blade.php';
$text = read_text($root, $rel);

if ($text !== null) {
    $text = preg_clean(
        $text,
        '~^.*\$navItem\(\s*[\'"]admin\.jobs[\'"].*\R?~mi'
    );

    $text = preg_clean(
        $text,
        '~^[ \t]*@elseif\s*\(\s*\$item\[[\'"]route[\'"]\]\s*===\s*[\'"]admin\.jobs[\'"]\s*\)\s*\R[ \t]*<livewire:admin\.nav-badge[^>]*type=[\'"]pelamar[\'"][^>]*/>\s*\R?~mi'
    );

    write_text($root, $rel, $text);
}

/*
|--------------------------------------------------------------------------
| Admin nav badge
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/components/admin/nav-badge.blade.php';
$text = read_text($root, $rel);

if ($text !== null) {
    $text = preg_clean(
        $text,
        '~^.*[\'"]pelamar[\'"]\s*=>\s*\\\\App\\\\Models\\\\JobApplication::query\(\)->unreadAdmin\(\)->count\(\),?\s*\R?~mi'
    );

    write_text($root, $rel, $text);
}

/*
|--------------------------------------------------------------------------
| Frontend notification bell
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/components/notification-bell.blade.php';
$text = read_text($root, $rel);

if ($text !== null) {
    // Remove JobApplication count variable.
    $text = preg_clean(
        $text,
        '~^[ \t]*\$unreadJobApplications\s*=\s*\\\\App\\\\Models\\\\JobApplication::query\(\)->unreadAdmin\(\)->count\(\);\h*\R?~mi'
    );

    // Restore standard total count.
    $text = preg_clean(
        $text,
        '~return\s+\$user\s*\?\s*\$user->unreadNotificationsCount\(\)\s*\+\s*\$unreadJobApplications\s*:\s*0\s*;~',
        'return $user ? $user->unreadNotificationsCount() : 0;'
    );

    // Remove Pelamar Baru row.
    $text = preg_clean(
        $text,
        '~^.*[\'"]label[\'"]\s*=>\s*[\'"]Pelamar Baru[\'"].*\R?~mi'
    );

    write_text($root, $rel, $text);
}

/*
|--------------------------------------------------------------------------
| Footer + all remaining frontend Career links
|--------------------------------------------------------------------------
*/
$rel = 'resources/views/partials/frontend/footer.blade.php';
$text = read_text($root, $rel);

if ($text !== null) {
    // Remove list item / anchor Shipping & Returns.
    $text = preg_clean(
        $text,
        '~<li\b[^>]*>\s*<a\b[^>]*(?:#pengiriman)[^>]*>\s*Shipping\s*&(?:amp;)?\s*Returns\s*</a>\s*</li>~is'
    );
    $text = preg_clean(
        $text,
        '~<a\b[^>]*(?:#pengiriman)[^>]*>\s*Shipping\s*&(?:amp;)?\s*Returns\s*</a>~is'
    );

    // Remove Career list item / anchor.
    $text = preg_clean(
        $text,
        '~<li\b[^>]*>\s*<a\b[^>]*(?:careers\.index|/karier)[^>]*>.*?</a>\s*</li>~is'
    );
    $text = preg_clean(
        $text,
        '~<a\b[^>]*(?:careers\.index|/karier)[^>]*>.*?</a>~is'
    );

    write_text($root, $rel, $text);
}

// Remove any remaining Career links from other Blade files, but do not
// touch backup folders or generated vendor files.
$viewsRoot = full_path($root, 'resources/views');

if (is_dir($viewsRoot)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $path = $file->getPathname();
        $content = file_get_contents($path);

        if ($content === false) {
            continue;
        }

        $original = $content;

        $content = preg_clean(
            $content,
            '~<li\b[^>]*>\s*<a\b[^>]*(?:careers\.index|/karier)[^>]*>.*?</a>\s*</li>~is'
        );

        $content = preg_clean(
            $content,
            '~<a\b[^>]*(?:careers\.index|/karier)[^>]*>.*?</a>~is'
        );

        if ($content !== $original) {
            file_put_contents($path, $content);
        }
    }
}

echo "CLEAN_ACTIVE_REFERENCES_OK\n";
'@

$tmpPatch = Join-Path $env:TEMP "remove-career-active-references-$stamp.php"
[System.IO.File]::WriteAllText($tmpPatch, $patchPhp, $utf8NoBom)

php $tmpPatch (Get-Location).Path
$patchExit = $LASTEXITCODE

Remove-Item $tmpPatch -Force -ErrorAction SilentlyContinue

if ($patchExit -ne 0) {
    throw "Gagal membersihkan referensi Career. Backup tersedia di: $backupDir"
}

Write-Host "  - Shipping & Returns di footer dihapus." -ForegroundColor Green
Write-Host "  - Career link, routes, sidebar Loker, badge, dan notifikasi dibersihkan." -ForegroundColor Green

Step "[4/8] Menghapus source file Career/Loker ..."

foreach ($file in $careerFiles) {
    if (Test-Path $file) {
        Remove-Item $file -Force
        Write-Host "  - Hapus: $file" -ForegroundColor DarkGray
    }
}

if (Test-Path ".\storage\app\job-applications") {
    Remove-Item ".\storage\app\job-applications" -Recurse -Force
    Write-Host "  - Storage CV/portofolio Career dihapus setelah dibackup." -ForegroundColor DarkGray
}

Step "[5/8] Menghapus seluruh migration Career yang lama ..."

$allCareerMigrations = @(
    Get-ChildItem ".\database\migrations" -File -Filter "*.php" -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -match '(?i)(job_vacanc|job_application|career)' }
)

foreach ($migration in $allCareerMigrations) {
    Remove-Item $migration.FullName -Force
    Write-Host "  - Hapus migration: $($migration.Name)" -ForegroundColor DarkGray
}

Step "[6/8] Validasi file project yang tersisa ..."

foreach ($file in $survivingFiles) {
    if (Test-Path $file) {
        php -l $file | Out-Host

        if ($LASTEXITCODE -ne 0) {
            throw "Syntax error setelah cleanup pada $file. Backup: $backupDir"
        }
    }
}

Step "[7/8] Scan sisa referensi aktif Career/Loker ..."

$scanRoots = @(".\app", ".\routes", ".\resources\views")
$patterns = @(
    "JobVacancy",
    "JobApplication",
    "CareerSelectionService",
    "career:finalize-selections",
    "careers.",
    "admin.jobs",
    "Pelamar Baru"
)

$remaining = @()

foreach ($root in $scanRoots) {
    if (Test-Path $root) {
        foreach ($pattern in $patterns) {
            $matches = Get-ChildItem $root -Recurse -File -ErrorAction SilentlyContinue |
                Select-String -SimpleMatch $pattern -ErrorAction SilentlyContinue

            if ($matches) {
                $remaining += $matches
            }
        }
    }
}

if ($remaining.Count -gt 0) {
    Write-Host ""
    Write-Host "[PERINGATAN] Masih ada referensi Career yang perlu dicek:" -ForegroundColor Yellow
    $remaining |
        Select-Object -First 30 Path, LineNumber, Line |
        Format-Table -AutoSize | Out-Host
    Write-Host "Cleanup utama sudah dilakukan, tetapi kirim output peringatan ini sebelum mengubah file lain." -ForegroundColor Yellow
} else {
    Write-Host "  - Tidak ada referensi aktif Career/Loker yang ditemukan." -ForegroundColor Green
}

Step "[8/8] Bersihkan cache dan tampilkan route terkait ..."

php artisan optimize:clear | Out-Host

Write-Host ""
Write-Host "Route yang mengandung career / karier / loker / jobs (seharusnya kosong):" -ForegroundColor Cyan
php artisan route:list | Select-String -Pattern "career|karier|loker|admin\.jobs" | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Yang dihapus:" -ForegroundColor Yellow
Write-Host "  - Footer: Shipping & Returns." -ForegroundColor White
Write-Host "  - Halaman Career dan seluruh form/hasil/cek lamaran." -ForegroundColor White
Write-Host "  - Admin > Loker dan badge Pelamar." -ForegroundColor White
Write-Host "  - Route Career/Loker." -ForegroundColor White
Write-Host "  - Model, controller, service, command, email Career." -ForegroundColor White
Write-Host "  - Scheduler career:finalize-selections." -ForegroundColor White
Write-Host "  - Notifikasi Pelamar Baru." -ForegroundColor White
Write-Host "  - Tabel job_vacancies dan job_applications." -ForegroundColor White
Write-Host "  - Storage CV/portofolio Career." -ForegroundColor White
Write-Host "  - Migration Career lama." -ForegroundColor White
Write-Host ""
Write-Host "Backup lengkap sebelum penghapusan:" -ForegroundColor Yellow
Write-Host "  $backupDir" -ForegroundColor White
Write-Host "Data tabel Career juga diekspor menjadi JSON di folder backup tersebut." -ForegroundColor DarkGray
