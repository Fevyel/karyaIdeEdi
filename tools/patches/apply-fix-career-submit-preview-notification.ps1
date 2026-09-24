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
$backupDir = ".backup-career-submit-notif-preview-$stamp"

$filesToBackup = @(
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
Write-Host " Career - Fix Submit + Preview File + Notifikasi Pelamar Realtime" -ForegroundColor Yellow
Write-Host "====================================================================" -ForegroundColor Yellow

Step "[1/7] Membuat backup ..."
foreach ($item in $filesToBackup) {
    if (Test-Path $item) {
        $relative = $item.TrimStart('.', '\')
        $dest = Join-Path $backupDir $relative
        $destDir = Split-Path $dest -Parent
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        Copy-Item $item $dest -Force
    }
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/7] Memperbaiki proses simpan lamaran ..."

$controllerContent = @'
<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobVacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class JobApplicationController extends Controller
{
    public function create(JobVacancy $job): View
    {
        abort_unless($job->isOpen(), 404);

        return view('pages.frontend.lamar-kerja', compact('job'));
    }

    public function store(Request $request, JobVacancy $job): RedirectResponse
    {
        // Cek ulang saat submit. Form yang sudah terbuka sebelum deadline
        // tetap ditolak kalau posisi sudah ditutup/expired.
        abort_unless($job->fresh()?->isOpen(), 410, 'Pendaftaran untuk posisi ini sudah ditutup.');

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'whatsapp' => ['required', 'regex:/^\d{8,15}$/'],
            'domicile' => ['required', 'string', 'max:120'],
            'last_education' => ['nullable', 'string', 'max:120'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'portfolio_url' => [
                'nullable',
                'required_without_all:cv_file,portfolio_file',
                'url',
                'max:500',
                'regex:/^https?:\/\/(?:drive|docs)\.google\.com\//i',
            ],
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'cv_file' => [
                'nullable',
                'required_without:portfolio_url',
                'file',
                'mimes:pdf,doc,docx',
                'max:5120',
            ],
            'portfolio_file' => [
                'nullable',
                'required_without:portfolio_url',
                'file',
                'mimes:pdf,jpg,jpeg,png,zip',
                'max:10240',
            ],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum valid.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp harus berisi 8-15 digit angka tanpa huruf atau simbol.',
            'domicile.required' => 'Domisili wajib diisi.',
            'cv_file.required_without' => 'Upload CV wajib jika Anda tidak menggunakan link Google Drive.',
            'cv_file.mimes' => 'CV harus berupa PDF, DOC, atau DOCX.',
            'cv_file.max' => 'Ukuran CV maksimal 5 MB.',
            'portfolio_file.required_without' => 'Upload portofolio wajib jika Anda tidak menggunakan link Google Drive.',
            'portfolio_file.mimes' => 'Portofolio harus berupa PDF, JPG, PNG, atau ZIP.',
            'portfolio_file.max' => 'Ukuran portofolio maksimal 10 MB.',
            'portfolio_url.required_without_all' => 'Upload CV + portofolio, atau isi link Google Drive yang berisi keduanya.',
            'portfolio_url.url' => 'Link Google Drive harus berupa URL yang valid.',
            'portfolio_url.regex' => 'Link dokumen harus berasal dari Google Drive.',
        ]);

        $driveUrl = trim((string) ($validated['portfolio_url'] ?? '')) ?: null;
        $hasCv = $request->hasFile('cv_file');
        $hasPortfolio = $request->hasFile('portfolio_file');

        // Alurnya harus jelas:
        // A. upload CV + Portofolio; ATAU
        // B. satu link Drive yang berisi keduanya.
        if (! $driveUrl && (! $hasCv || ! $hasPortfolio)) {
            return back()
                ->withInput()
                ->withErrors([
                    'documents' => 'Pilih salah satu metode: upload CV + portofolio, atau isi link Google Drive yang berisi keduanya.',
                ]);
        }

        $folder = 'job-applications/'.$job->id.'/'.Str::uuid();
        $cvPath = null;
        $portfolioPath = null;

        try {
            if ($hasCv) {
                $cvPath = $request->file('cv_file')->store($folder, 'local');
            }

            if ($hasPortfolio) {
                $portfolioPath = $request->file('portfolio_file')->store($folder, 'local');
            }

            DB::transaction(function () use ($validated, $driveUrl, $job, $cvPath, $portfolioPath) {
                JobApplication::query()->create([
                    'job_vacancy_id' => $job->id,
                    'job_title' => $job->title,
                    'full_name' => trim($validated['full_name']),
                    'email' => strtolower(trim($validated['email'])),
                    'whatsapp' => preg_replace('/\D+/', '', trim($validated['whatsapp'])),
                    'domicile' => trim($validated['domicile']),
                    'last_education' => trim((string) ($validated['last_education'] ?? '')) ?: null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'portfolio_url' => $driveUrl,
                    'cover_letter' => trim((string) ($validated['cover_letter'] ?? '')) ?: null,
                    'cv_path' => $cvPath,
                    'portfolio_path' => $portfolioPath,
                    'status' => 'new',
                    'is_read_admin' => false,
                ]);
            });
        } catch (Throwable $e) {
            if ($cvPath) {
                Storage::disk('local')->delete($cvPath);
            }

            if ($portfolioPath) {
                Storage::disk('local')->delete($portfolioPath);
            }

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'submission' => 'Lamaran belum berhasil tersimpan. Silakan coba kembali. Jika tetap gagal, hubungi admin Karya Ide Edi.',
                ]);
        }

        return redirect()
            ->to(route('careers.index').'#career-status')
            ->with(
                'career-application-sent',
                'Lamaran untuk posisi '.$job->title.' berhasil dikirim. Data Anda sudah masuk ke sistem rekrutmen Karya Ide Edi.'
            );
    }
}
'@

Write-NoBom ".\app\Http\Controllers\JobApplicationController.php" $controllerContent
Write-Host "  - Controller lamaran diperbaiki." -ForegroundColor Green

Step "[3/7] Mengizinkan cv_path kosong untuk lamaran via Google Drive ..."

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
                // Drive-only application tidak punya file CV lokal.
                $table->string('cv_path', 500)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke NOT NULL karena bisa sudah ada
        // lamaran valid melalui Google Drive dengan cv_path = null.
    }
};
'@

Write-NoBom ".\database\migrations\2026_09_24_000003_make_job_application_cv_nullable.php" $migrationContent
Write-Host "  - Migration nullable cv_path disiapkan." -ForegroundColor Green

Step "[4/7] Menambahkan preview file + memastikan badge/notifikasi ..."

$patchPhpContent = @'
<?php

$root = $argv[1] ?? getcwd();

function path_for(string $root, string $relative): string
{
    return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function read_file_or_fail(string $root, string $relative): string
{
    $path = path_for($root, $relative);
    if (! is_file($path)) {
        throw new RuntimeException("File tidak ditemukan: {$relative}");
    }

    $text = file_get_contents($path);
    if ($text === false) {
        throw new RuntimeException("Gagal membaca: {$relative}");
    }

    return $text;
}

function save_no_bom(string $root, string $relative, string $text): void
{
    $path = path_for($root, $relative);
    if (file_put_contents($path, $text) === false) {
        throw new RuntimeException("Gagal menulis: {$relative}");
    }
}

function replace_once_regex(string $text, string $pattern, string $replacement, string $label): string
{
    $count = 0;
    $result = preg_replace($pattern, $replacement, $text, 1, $count);

    if ($result === null) {
        throw new RuntimeException("Regex error: {$label}");
    }

    if ($count !== 1) {
        throw new RuntimeException("Bagian '{$label}' tidak ditemukan tepat satu kali.");
    }

    return $result;
}

// ============================================================
// 1) FORM LAMARAN: preview file + error submission yang jelas.
// ============================================================
$rel = 'resources/views/pages/frontend/lamar-kerja.blade.php';
$text = read_file_or_fail($root, $rel);

// Tambah onchange preview pada CV.
if (! str_contains($text, "careerPreviewSelectedFile(this, 'cv')")) {
    $count = 0;
    $text = preg_replace(
        '/(<input\b(?=[^>]*\bname="cv_file"\b)[^>]*)(>)/is',
        '$1 onchange="careerPreviewSelectedFile(this, \'cv\')" $2',
        $text,
        1,
        $count
    );
    if ($count !== 1) {
        throw new RuntimeException('Input cv_file tidak ditemukan tepat satu kali.');
    }
}

// Tambah preview card CV tepat setelah input.
if (! str_contains($text, 'id="cv-file-preview"')) {
    $count = 0;
    $text = preg_replace(
        '/(<input\b(?=[^>]*\bname="cv_file"\b)[^>]*>)/is',
        <<<'HTML'
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
HTML,
        $text,
        1,
        $count
    );
    if ($count !== 1) {
        throw new RuntimeException('Gagal menambahkan preview CV.');
    }
}

// Tambah onchange preview pada Portofolio.
if (! str_contains($text, "careerPreviewSelectedFile(this, 'portfolio')")) {
    $count = 0;
    $text = preg_replace(
        '/(<input\b(?=[^>]*\bname="portfolio_file"\b)[^>]*)(>)/is',
        '$1 onchange="careerPreviewSelectedFile(this, \'portfolio\')" $2',
        $text,
        1,
        $count
    );
    if ($count !== 1) {
        throw new RuntimeException('Input portfolio_file tidak ditemukan tepat satu kali.');
    }
}

// Tambah preview card Portofolio.
if (! str_contains($text, 'id="portfolio-file-preview"')) {
    $count = 0;
    $text = preg_replace(
        '/(<input\b(?=[^>]*\bname="portfolio_file"\b)[^>]*>)/is',
        <<<'HTML'
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
HTML,
        $text,
        1,
        $count
    );
    if ($count !== 1) {
        throw new RuntimeException('Gagal menambahkan preview Portofolio.');
    }
}

// Tampilkan error dokumen/submission di atas tombol submit kalau ada.
if (! str_contains($text, "session-style-career-submit-errors")) {
    $submitAnchor = '<div class="sm:col-span-2 flex flex-col gap-3 border-t border-[#E8DED2] pt-5';
    $pos = strpos($text, $submitAnchor);
    if ($pos === false) {
        throw new RuntimeException('Area tombol submit form lamaran tidak ditemukan.');
    }

    $errorBlock = <<<'BLADE'
<div class="session-style-career-submit-errors sm:col-span-2">
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

BLADE;

    $text = substr($text, 0, $pos).$errorBlock.substr($text, $pos);
}

// JS preview file lokal.
if (! str_contains($text, 'function careerPreviewSelectedFile')) {
    $script = <<<'HTML'

<script>
    (() => {
        const objectUrls = {};

        window.careerPreviewSelectedFile = function (input, key) {
            const preview = document.getElementById(key + '-file-preview');
            const name = document.getElementById(key + '-file-name');
            const size = document.getElementById(key + '-file-size');
            const open = document.getElementById(key + '-file-open');

            if (!preview || !name || !size || !open) return;

            if (objectUrls[key]) {
                URL.revokeObjectURL(objectUrls[key]);
                delete objectUrls[key];
            }

            const file = input.files && input.files[0] ? input.files[0] : null;

            if (!file) {
                preview.classList.add('hidden');
                preview.classList.remove('flex');
                name.textContent = '';
                size.textContent = '';
                open.removeAttribute('href');
                return;
            }

            objectUrls[key] = URL.createObjectURL(file);
            name.textContent = file.name;
            size.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            open.href = objectUrls[key];

            preview.classList.remove('hidden');
            preview.classList.add('flex');
        };
    })();
</script>
HTML;

    if (! str_contains($text, '</body>')) {
        throw new RuntimeException('Penutup body form lamaran tidak ditemukan.');
    }

    $text = str_replace('</body>', $script."\n</body>", $text);
}

save_no_bom($root, $rel, $text);

// ============================================================
// 2) HALAMAN CAREERS: success anchor supaya submit terasa jelas.
// ============================================================
$rel = 'resources/views/pages/frontend/karier.blade.php';
$text = read_file_or_fail($root, $rel);

if (! str_contains($text, 'id="career-status"')) {
    $pattern = '/(@if\s*\(\s*session\(\'career-application-sent\'\)\s*\)\s*)<section\b/i';
    $count = 0;
    $text = preg_replace($pattern, '$1<section id="career-status"', $text, 1, $count);

    if ($count !== 1) {
        throw new RuntimeException('Banner sukses Career tidak ditemukan.');
    }
}

save_no_bom($root, $rel, $text);

// ============================================================
// 3) ADMIN LOKER: realtime poll + file links Drive/file yang benar.
// ============================================================
$rel = 'resources/views/pages/admin/loker.blade.php';
$text = read_file_or_fail($root, $rel);

if (! str_contains($text, 'wire:poll.15s')) {
    $count = 0;
    $text = preg_replace(
        '/<div class="space-y-6">/',
        '<div class="space-y-6" wire:poll.15s>',
        $text,
        1,
        $count
    );

    if ($count !== 1) {
        throw new RuntimeException('Root halaman Loker tidak ditemukan.');
    }
}

// Ganti area tombol dokumen pelamar supaya CV Drive-only tidak memberi tombol 404.
$pattern = '/<div class="mt-4 flex flex-wrap gap-2">.*?<\/div>/s';
$matches = [];
preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

$targetIndex = null;
foreach ($matches[0] ?? [] as $i => $match) {
    if (str_contains($match[0], 'admin.jobs.applications.file') || str_contains($match[0], 'portfolio_url')) {
        $targetIndex = $i;
        break;
    }
}

if ($targetIndex !== null) {
    $old = $matches[0][$targetIndex][0];

    $new = <<<'BLADE'
<div class="mt-4 flex flex-wrap gap-2">
    @if ($application->cv_path)
        <a href="{{ route('admin.jobs.applications.file', ['application'=>$application,'file'=>'cv']) }}" class="rounded-full bg-admin-panel px-4 py-2 text-xs font-semibold text-white">
            <i class="fa-solid fa-file-arrow-down mr-1.5"></i>Unduh CV
        </a>
    @endif

    @if ($application->portfolio_path)
        <a href="{{ route('admin.jobs.applications.file', ['application'=>$application,'file'=>'portfolio']) }}" class="rounded-full border border-admin-border px-4 py-2 text-xs font-semibold text-admin-ink">
            <i class="fa-solid fa-folder-open mr-1.5"></i>Unduh Portofolio
        </a>
    @endif

    @if ($application->portfolio_url)
        <a href="{{ $application->portfolio_url }}" target="_blank" rel="noopener" class="rounded-full border border-admin-accent bg-admin-accent/5 px-4 py-2 text-xs font-semibold text-admin-accent">
            <i class="fa-brands fa-google-drive mr-1.5"></i>Google Drive (CV + Portofolio)
        </a>
    @endif
</div>
BLADE;

    $text = substr_replace($text, $new, $matches[0][$targetIndex][1], strlen($old));
} elseif (! str_contains($text, 'Google Drive (CV + Portofolio)')) {
    throw new RuntimeException('Area tombol dokumen Pelamar tidak ditemukan.');
}

save_no_bom($root, $rel, $text);

// ============================================================
// 4) BADGE SIDEBAR: pastikan type pelamar dihitung.
// ============================================================
$rel = 'resources/views/components/admin/nav-badge.blade.php';
$text = read_file_or_fail($root, $rel);

if (! str_contains($text, "'pelamar' =>")) {
    $anchor = "'dashboard' => \$user->unreadDashboardCount(),";
    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Anchor nav-badge dashboard tidak ditemukan.');
    }

    $text = str_replace(
        $anchor,
        $anchor."\n            'pelamar' => \\App\\Models\\JobApplication::query()->unreadAdmin()->count(),",
        $text
    );
}

save_no_bom($root, $rel, $text);

// ============================================================
// 5) SIDEBAR ADMIN: pastikan badge Loker dipasang.
// ============================================================
$rel = 'resources/views/layouts/admin-panel.blade.php';
$text = read_file_or_fail($root, $rel);

if (! str_contains($text, 'type="pelamar"')) {
    $anchor = <<<'BLADE'
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @endif
BLADE;

    $replacement = <<<'BLADE'
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @elseif ($item['route'] === 'admin.jobs')
                                        <livewire:admin.nav-badge type="pelamar" :active="$item['active']" :key="'nav-badge-pelamar'" />
                                    @endif
BLADE;

    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Anchor badge Pesanan pada sidebar tidak ditemukan.');
    }

    $text = str_replace($anchor, $replacement, $text);
}

save_no_bom($root, $rel, $text);

// ============================================================
// 6) NOTIFICATION BELL FRONTEND: pastikan Pelamar Baru masuk count.
// ============================================================
$rel = 'resources/views/components/notification-bell.blade.php';
$text = read_file_or_fail($root, $rel);

if (! str_contains($text, 'unreadJobApplications')) {
    $old = '        return $user ? $user->unreadNotificationsCount() : 0;';
    $new = <<<'PHP'
        $unreadJobApplications = \App\Models\JobApplication::query()->unreadAdmin()->count();

        return $user ? $user->unreadNotificationsCount() + $unreadJobApplications : 0;
PHP;

    if (! str_contains($text, $old)) {
        throw new RuntimeException('Perhitungan total notification-bell tidak ditemukan.');
    }

    $text = str_replace($old, $new, $text);
}

if (! str_contains($text, "'label' => 'Pelamar Baru'")) {
    $anchor = "            ['icon' => 'fa-triangle-exclamation', 'label' => 'Stok Menipis', 'count' => \$user->unreadDashboardCount()],";

    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Breakdown notification-bell tidak ditemukan.');
    }

    $text = str_replace(
        $anchor,
        $anchor."\n            ['icon' => 'fa-user-plus', 'label' => 'Pelamar Baru', 'count' => \\App\\Models\\JobApplication::query()->unreadAdmin()->count()],",
        $text
    );
}

save_no_bom($root, $rel, $text);

echo "PATCH_BLADE_OK\n";
'@

$tmpPatch = Join-Path $env:TEMP "patch-career-submit-notif-preview-$stamp.php"
[System.IO.File]::WriteAllText($tmpPatch, $patchPhpContent, $utf8NoBom)

php $tmpPatch (Get-Location).Path
$patchExit = $LASTEXITCODE
Remove-Item $tmpPatch -Force -ErrorAction SilentlyContinue

if ($patchExit -ne 0) {
    throw "Patch Blade/notifikasi gagal. Backup tersedia di: $backupDir"
}

Step "[5/7] Validasi syntax ..."
$checkFiles = @(
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\database\migrations\2026_09_24_000003_make_job_application_cv_nullable.php",
    ".\resources\views\pages\frontend\lamar-kerja.blade.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\admin\loker.blade.php",
    ".\resources\views\components\admin\nav-badge.blade.php",
    ".\resources\views\layouts\admin-panel.blade.php",
    ".\resources\views\components\notification-bell.blade.php"
)

foreach ($file in $checkFiles) {
    php -l $file | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Backup tersedia di: $backupDir"
    }
}

Step "[6/7] Menjalankan migration dan membersihkan cache ..."
php artisan migrate
if ($LASTEXITCODE -ne 0) {
    throw "Migration gagal. Backup tersedia di: $backupDir"
}

php artisan optimize:clear | Out-Host

Step "[7/7] Verifikasi route dan status fitur ..."
php artisan route:list --name=careers.apply | Out-Host
php artisan route:list --name=admin.jobs | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Perbaikan yang aktif:" -ForegroundColor Yellow
Write-Host "  - Submit lamaran benar-benar menyimpan JobApplication." -ForegroundColor White
Write-Host "  - Upload langsung memakai field cv_file + portfolio_file yang benar." -ForegroundColor White
Write-Host "  - Google Drive-only sekarang valid (cv_path boleh null)." -ForegroundColor White
Write-Host "  - Setelah sukses, pelamar diarahkan ke banner konfirmasi." -ForegroundColor White
Write-Host "  - CV dan Portofolio menampilkan nama, ukuran, dan tombol Lihat file sebelum submit." -ForegroundColor White
Write-Host "  - Pelamar baru tersimpan sebagai unread untuk Admin." -ForegroundColor White
Write-Host "  - Badge Loker dan notification bell menghitung Pelamar Baru." -ForegroundColor White
Write-Host "  - Halaman Admin > Loker polling tiap 15 detik untuk pelamar baru." -ForegroundColor White
Write-Host "  - Drive-only tidak menampilkan tombol Unduh CV yang palsu/404." -ForegroundColor White
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
