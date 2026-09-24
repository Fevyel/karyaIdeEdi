$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-NoBom($Path, $Content) {
    $full = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $Path))
    $dir = [System.IO.Path]::GetDirectoryName($full)
    if (-not [System.IO.Directory]::Exists($dir)) {
        [System.IO.Directory]::CreateDirectory($dir) | Out-Null
    }
    [System.IO.File]::WriteAllText($full, $Content, $utf8NoBom)
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-career-submit-notif-preview-v2-$stamp"

$targets = @(
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\database\migrations\2026_09_24_000003_make_job_application_cv_nullable.php",
    ".\resources\views\pages\frontend\lamar-kerja.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\components\admin\nav-badge.blade.php",
    ".\resources\views\layouts\admin-panel.blade.php",
    ".\resources\views\components\notification-bell.blade.php"
)

Write-Host "====================================================================" -ForegroundColor Yellow
Write-Host " Career - Resume Fix Submit + Preview + Notifikasi (v2)" -ForegroundColor Yellow
Write-Host "====================================================================" -ForegroundColor Yellow

Step "[1/7] Membuat backup kondisi SEKARANG ..."
foreach ($item in $targets) {
    if (Test-Path $item) {
        $relative = $item.TrimStart('.', '\')
        $dest = Join-Path $backupDir $relative
        $destDir = Split-Path $dest -Parent
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        Copy-Item $item $dest -Force
    }
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/7] Menormalkan nama field file + menambahkan preview ..."

$form = ".\resources\views\pages\frontend\lamar-kerja.blade.php"
if (-not (Test-Path $form)) {
    throw "Form lamaran tidak ditemukan: $form"
}

$formText = [System.IO.File]::ReadAllText((Resolve-Path $form))

# Form wajib multipart.
if ($formText -notmatch 'enctype\s*=\s*["'']multipart/form-data["'']') {
    $formText = [regex]::Replace(
        $formText,
        '(<form\b[^>]*method\s*=\s*["'']POST["''][^>]*)(>)',
        '$1 enctype="multipart/form-data"$2',
        1
    )
}

# Kompatibilitas dengan versi lama: name="cv" -> name="cv_file".
if ($formText -match 'name\s*=\s*["'']cv["'']') {
    $formText = [regex]::Replace(
        $formText,
        'name\s*=\s*(["''])cv\1',
        'name="cv_file"',
        1
    )
    Write-Host "  - Field CV lama dinormalkan: cv -> cv_file." -ForegroundColor Green
}

if ($formText -notmatch 'name\s*=\s*["'']cv_file["'']') {
    throw "Input CV tidak ditemukan (baik name=cv maupun name=cv_file)."
}

if ($formText -notmatch 'name\s*=\s*["'']portfolio_file["'']') {
    throw "Input portfolio_file tidak ditemukan."
}

# Tambah handler preview CV. (BUG v1 diperbaiki: tidak memakai \b setelah tanda kutip)
if ($formText -notmatch "careerPreviewSelectedFile\(this,\s*['""]cv['""]\)") {
    $count = 0
    $formText = [regex]::Replace(
        $formText,
        '(<input\b(?=[^>]*name\s*=\s*["'']cv_file["''])[^>]*)(>)',
        '$1 onchange="careerPreviewSelectedFile(this, ''cv'')"$2',
        1,
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    if ($count -eq 0) {
        # PowerShell overload di atas tidak mengembalikan count; verifikasi hasil langsung.
        if ($formText -notmatch "careerPreviewSelectedFile\(this,\s*['""]cv['""]\)") {
            throw "Gagal memasang preview pada input CV."
        }
    }
    Write-Host "  - Preview CV diaktifkan." -ForegroundColor Green
}

# Tambah card preview CV.
if ($formText -notmatch 'id\s*=\s*["'']cv-file-preview["'']') {
    $pattern = '(<input\b(?=[^>]*name\s*=\s*["'']cv_file["''])[^>]*>)'
    $preview = @'
$1
<div id="cv-file-preview" class="mt-3 hidden items-center gap-3 rounded-2xl border border-[#E4D8CA] bg-white px-3.5 py-3">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#3B2518] text-white">
        <i class="fa-solid fa-file-lines text-xs"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p id="cv-file-name" class="truncate text-xs font-semibold text-[#493224]"></p>
        <p id="cv-file-size" class="mt-0.5 text-[10px] text-[#8A7B6D]"></p>
    </div>
    <a id="cv-file-open" href="#" target="_blank" rel="noopener" class="shrink-0 rounded-full border border-[#DCCAB7] px-3 py-1.5 text-[10px] font-semibold text-[#7A5738]">
        Lihat file
    </a>
</div>
'@
    $new = [regex]::Replace($formText, $pattern, $preview.TrimEnd(), 1)
    if ($new -eq $formText) { throw "Gagal menambahkan card preview CV." }
    $formText = $new
}

# Tambah handler preview Portofolio.
if ($formText -notmatch "careerPreviewSelectedFile\(this,\s*['""]portfolio['""]\)") {
    $new = [regex]::Replace(
        $formText,
        '(<input\b(?=[^>]*name\s*=\s*["'']portfolio_file["''])[^>]*)(>)',
        '$1 onchange="careerPreviewSelectedFile(this, ''portfolio'')"$2',
        1
    )
    if ($new -eq $formText) { throw "Gagal memasang preview pada input Portofolio." }
    $formText = $new
    Write-Host "  - Preview Portofolio diaktifkan." -ForegroundColor Green
}

# Tambah card preview Portofolio.
if ($formText -notmatch 'id\s*=\s*["'']portfolio-file-preview["'']') {
    $pattern = '(<input\b(?=[^>]*name\s*=\s*["'']portfolio_file["''])[^>]*>)'
    $preview = @'
$1
<div id="portfolio-file-preview" class="mt-3 hidden items-center gap-3 rounded-2xl border border-[#E4D8CA] bg-white px-3.5 py-3">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#8A5A31] text-white">
        <i class="fa-solid fa-images text-xs"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p id="portfolio-file-name" class="truncate text-xs font-semibold text-[#493224]"></p>
        <p id="portfolio-file-size" class="mt-0.5 text-[10px] text-[#8A7B6D]"></p>
    </div>
    <a id="portfolio-file-open" href="#" target="_blank" rel="noopener" class="shrink-0 rounded-full border border-[#DCCAB7] px-3 py-1.5 text-[10px] font-semibold text-[#7A5738]">
        Lihat file
    </a>
</div>
'@
    $new = [regex]::Replace($formText, $pattern, $preview.TrimEnd(), 1)
    if ($new -eq $formText) { throw "Gagal menambahkan card preview Portofolio." }
    $formText = $new
}

# Tampilkan error sistem/dokumen dekat tombol submit.
if ($formText -notmatch 'career-submit-system-errors') {
    $anchor = '<div class="sm:col-span-2 flex flex-col gap-3 border-t border-[#E8DED2] pt-5'
    $pos = $formText.IndexOf($anchor)
    if ($pos -ge 0) {
        $errorBlock = @'
<div class="career-submit-system-errors sm:col-span-2 space-y-2">
    @error('documents')
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $message }}
        </div>
    @enderror
    @error('submission')
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $message }}
        </div>
    @enderror
</div>

'@
        $formText = $formText.Substring(0, $pos) + $errorBlock + $formText.Substring($pos)
    }
}

# JS preview: nama + ukuran + buka file lokal sebelum submit.
if ($formText -notmatch 'function\s+careerPreviewSelectedFile') {
    $js = @'
<script>
(() => {
    const careerObjectUrls = {};

    window.careerPreviewSelectedFile = function (input, key) {
        const preview = document.getElementById(key + '-file-preview');
        const nameEl = document.getElementById(key + '-file-name');
        const sizeEl = document.getElementById(key + '-file-size');
        const openEl = document.getElementById(key + '-file-open');

        if (!preview || !nameEl || !sizeEl || !openEl) return;

        if (careerObjectUrls[key]) {
            URL.revokeObjectURL(careerObjectUrls[key]);
            delete careerObjectUrls[key];
        }

        const file = input.files && input.files.length ? input.files[0] : null;

        if (!file) {
            preview.classList.add('hidden');
            preview.classList.remove('flex');
            nameEl.textContent = '';
            sizeEl.textContent = '';
            openEl.removeAttribute('href');
            return;
        }

        careerObjectUrls[key] = URL.createObjectURL(file);
        nameEl.textContent = file.name;
        sizeEl.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        openEl.href = careerObjectUrls[key];

        preview.classList.remove('hidden');
        preview.classList.add('flex');
    };
})();
</script>
'@
    if ($formText -notmatch '</body>') {
        throw "Penutup </body> tidak ditemukan pada form lamaran."
    }
    $formText = $formText.Replace('</body>', $js + "`r`n</body>")
}

Write-NoBom $form $formText
Write-Host "  - Form lamaran sudah memakai preview file yang bisa dicek kandidat." -ForegroundColor Green

Step "[3/7] Memastikan controller menyimpan dengan nama field yang benar ..."

$controller = ".\app\Http\Controllers\JobApplicationController.php"
if (-not (Test-Path $controller)) {
    throw "Controller lamaran tidak ditemukan."
}

$controllerText = [System.IO.File]::ReadAllText((Resolve-Path $controller))

# Pastikan backend membaca cv_file, bukan cv lama.
$controllerText = $controllerText.Replace("hasFile('cv')", "hasFile('cv_file')")
$controllerText = $controllerText.Replace("file('cv')->", "file('cv_file')->")

if ($controllerText -notmatch "'cv_file'\s*=>") {
    throw "Rule cv_file tidak ditemukan pada controller. Jangan lanjut agar tidak membuat submit palsu."
}
if ($controllerText -notmatch "hasFile\('cv_file'\)|file\('cv_file'\)") {
    throw "Controller belum membaca cv_file."
}
if ($controllerText -notmatch "'is_read_admin'\s*=>\s*false") {
    throw "Controller belum menandai pelamar baru sebagai unread admin."
}

Write-NoBom $controller $controllerText
Write-Host "  - Controller sudah sinkron dengan cv_file / portfolio_file." -ForegroundColor Green

Step "[4/7] Memastikan migration Drive-only bisa menyimpan lamaran ..."

$migration = ".\database\migrations\2026_09_24_000003_make_job_application_cv_nullable.php"
if (-not (Test-Path $migration)) {
    $migrationContent = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_applications') && Schema::hasColumn('job_applications', 'cv_path')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->string('cv_path', 500)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan ke NOT NULL karena mungkin sudah ada lamaran via Google Drive.
    }
};
'@
    Write-NoBom $migration $migrationContent
}

Step "[5/7] Memastikan notifikasi Pelamar terhubung ..."

# Loker: polling agar badge/daftar pelamar berubah tanpa refresh manual.
$admin = ".\resources\views\pages\admin\loker.blade.php"
if (Test-Path $admin) {
    $adminText = [System.IO.File]::ReadAllText((Resolve-Path $admin))
    if ($adminText -notmatch 'wire:poll\.\d+s') {
        $adminText = [regex]::Replace(
            $adminText,
            '<div class="space-y-6">',
            '<div class="space-y-6" wire:poll.15s>',
            1
        )
    }
    Write-NoBom $admin $adminText
}

# Sidebar harus memiliki nav-badge untuk admin.jobs.
$sidebar = ".\resources\views\layouts\admin-panel.blade.php"
if (Test-Path $sidebar) {
    $sidebarText = [System.IO.File]::ReadAllText((Resolve-Path $sidebar))

    if ($sidebarText -notmatch 'type\s*=\s*["'']pelamar["'']') {
        $pattern = '(?s)(@elseif\s*\(\s*\$item\[''route''\]\s*===\s*''admin\.transactions''\s*\).*?<livewire:admin\.nav-badge\s+type="pesanan".*?/>)'
        if ([regex]::IsMatch($sidebarText, $pattern)) {
            $sidebarText = [regex]::Replace(
                $sidebarText,
                $pattern,
                '$1' + "`r`n" + '                                    @elseif ($item[''route''] === ''admin.jobs'')' + "`r`n" + '                                        <livewire:admin.nav-badge type="pelamar" :active="$item[''active'']" :key="''nav-badge-pelamar''" />',
                1
            )
        }
    }
    Write-NoBom $sidebar $sidebarText
}

# nav-badge source.
$navBadge = ".\resources\views\components\admin\nav-badge.blade.php"
if (Test-Path $navBadge) {
    $badgeText = [System.IO.File]::ReadAllText((Resolve-Path $navBadge))
    if ($badgeText -notmatch "'pelamar'\s*=>") {
        $anchorPattern = "'dashboard'\s*=>\s*\$user->unreadDashboardCount\(\),"
        if ([regex]::IsMatch($badgeText, $anchorPattern)) {
            $badgeText = [regex]::Replace(
                $badgeText,
                $anchorPattern,
                '$0' + "`r`n" + "            'pelamar' => \App\Models\JobApplication::query()->unreadAdmin()->count(),",
                1
            )
        }
    }
    Write-NoBom $navBadge $badgeText
}

# Notification bell frontend: hitung Pelamar Baru.
$bell = ".\resources\views\components\notification-bell.blade.php"
if (Test-Path $bell) {
    $bellText = [System.IO.File]::ReadAllText((Resolve-Path $bell))

    if ($bellText -notmatch 'unreadJobApplications') {
        $old = '        return $user ? $user->unreadNotificationsCount() : 0;'
        if ($bellText.Contains($old)) {
            $new = @'
        $unreadJobApplications = \App\Models\JobApplication::query()->unreadAdmin()->count();

        return $user ? $user->unreadNotificationsCount() + $unreadJobApplications : 0;
'@
            $bellText = $bellText.Replace($old, $new.TrimEnd())
        }
    }

    if ($bellText -notmatch "'label'\s*=>\s*'Pelamar Baru'") {
        $anchor = "            ['icon' => 'fa-triangle-exclamation', 'label' => 'Stok Menipis', 'count' => `$user->unreadDashboardCount()],"
        if ($bellText.Contains($anchor)) {
            $bellText = $bellText.Replace(
                $anchor,
                $anchor + "`r`n" + "            ['icon' => 'fa-user-plus', 'label' => 'Pelamar Baru', 'count' => \App\Models\JobApplication::query()->unreadAdmin()->count()],"
            )
        }
    }
    Write-NoBom $bell $bellText
}

# Career success banner diberi anchor supaya redirect langsung terlihat.
$career = ".\resources\views\pages\frontend\karier.blade.php"
if (Test-Path $career) {
    $careerText = [System.IO.File]::ReadAllText((Resolve-Path $career))
    if ($careerText -notmatch 'id\s*=\s*["'']career-status["'']') {
        $careerText = [regex]::Replace(
            $careerText,
            '(@if\s*\(\s*session\(''career-application-sent''\)\s*\)\s*)<section\b',
            '$1<section id="career-status"',
            1
        )
    }
    Write-NoBom $career $careerText
}

Step "[6/7] Validasi syntax + jalankan migration ..."

$check = @(
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\database\migrations\2026_09_24_000003_make_job_application_cv_nullable.php",
    ".\resources\views\pages\frontend\lamar-kerja.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\layouts\admin-panel.blade.php",
    ".\resources\views\components\admin\nav-badge.blade.php",
    ".\resources\views\components\notification-bell.blade.php"
)

foreach ($file in $check) {
    if (Test-Path $file) {
        php -l $file | Out-Host
        if ($LASTEXITCODE -ne 0) {
            throw "Syntax error pada $file. Backup tersedia di: $backupDir"
        }
    }
}

php artisan migrate
if ($LASTEXITCODE -ne 0) {
    throw "Migration gagal. Backup tersedia di: $backupDir"
}

php artisan optimize:clear | Out-Host

Step "[7/7] Cek route dan database ..."

php artisan route:list --name=careers.apply | Out-Host
php artisan route:list --name=admin.jobs | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Yang sudah dibenahi:" -ForegroundColor Yellow
Write-Host "  - Bug patch v1 (regex cv_file) diperbaiki." -ForegroundColor White
Write-Host "  - Jika form masih memakai name=cv, otomatis diubah menjadi cv_file." -ForegroundColor White
Write-Host "  - CV dan Portofolio punya preview nama, ukuran, dan tombol Lihat file." -ForegroundColor White
Write-Host "  - Submit membaca field file yang benar." -ForegroundColor White
Write-Host "  - Pelamar baru disimpan sebagai unread untuk Admin." -ForegroundColor White
Write-Host "  - Admin Loker polling tiap 15 detik." -ForegroundColor White
Write-Host "  - Badge/sidebar/notifikasi frontend dipastikan terhubung ke Pelamar Baru." -ForegroundColor White
Write-Host "  - Google Drive-only didukung setelah migration cv_path nullable." -ForegroundColor White
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
