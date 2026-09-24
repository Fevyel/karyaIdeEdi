$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$routes = ".\routes\web.php"
$admin = ".\resources\views\pages\admin\loker.blade.php"
$form = ".\resources\views\pages\frontend\lamar-kerja.blade.php"
$controller = ".\app\Http\Controllers\JobApplicationController.php"

foreach ($file in @($routes, $admin, $form, $controller)) {
    if (-not (Test-Path $file)) {
        throw "File tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-career-inline-preview-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-NoBom($Path, $Content) {
    [System.IO.File]::WriteAllText((Resolve-Path $Path), $Content, $utf8NoBom)
}

Write-Host "================================================================" -ForegroundColor Yellow
Write-Host " Career - Admin Lihat CV/Portofolio Tanpa Download ke Perangkat" -ForegroundColor Yellow
Write-Host "================================================================" -ForegroundColor Yellow

Step "[1/6] Membuat backup ..."
foreach ($item in @($routes, $admin, $form, $controller)) {
    $relative = $item.TrimStart('.', '\')
    $dest = Join-Path $backupDir $relative
    $destDir = Split-Path $dest -Parent
    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    Copy-Item $item $dest -Force
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/6] Mengubah route file Admin menjadi inline preview ..."

$routeText = [System.IO.File]::ReadAllText((Resolve-Path $routes))

$pattern = '(?s)\s*Route::get\(''/loker/pelamar/\{application\}/file/\{file\}'',\s*function\s*\(\\App\\Models\\JobApplication\s+\$application,\s*string\s+\$file\)\s*\{.*?\}\)->whereIn\(''file'',\s*\[''cv'',\s*''portfolio''\]\)->name\(''jobs\.applications\.file''\);'

$replacement = @'

    Route::get('/loker/pelamar/{application}/file/{file}', function (\App\Models\JobApplication $application, string $file) {
        $path = match ($file) {
            'cv' => $application->cv_path,
            'portfolio' => $application->portfolio_path,
            default => null,
        };

        abort_unless(
            $path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path),
            404
        );

        $absolutePath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $originalName = match ($file) {
            'cv' => $application->cv_original_name ?: 'CV-'.$application->full_name,
            'portfolio' => $application->portfolio_original_name ?: 'Portofolio-'.$application->full_name,
        };

        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };

        if ($mime) {
            return response()->file($absolutePath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.addslashes($originalName).'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        return response()->view('pages.admin.file-preview-unsupported', [
            'application' => $application,
            'fileType' => $file,
            'fileName' => $originalName,
            'extension' => strtoupper($extension ?: 'UNKNOWN'),
        ], 415);
    })->whereIn('file', ['cv', 'portfolio'])->name('jobs.applications.file');
'@

if ([regex]::IsMatch($routeText, $pattern)) {
    $routeText = [regex]::Replace($routeText, $pattern, $replacement, 1)
    Write-Host "  - Route file Admin sekarang inline preview." -ForegroundColor Green
} else {
    throw "Route admin.jobs.applications.file tidak ditemukan dalam bentuk yang diharapkan."
}

Write-NoBom $routes $routeText

Step "[3/6] Mengganti tombol Admin menjadi Lihat, bukan Unduh ..."

$adminText = [System.IO.File]::ReadAllText((Resolve-Path $admin))
$adminText = $adminText.Replace('fa-file-arrow-down mr-1.5"></i>Unduh CV', 'fa-eye mr-1.5"></i>Lihat CV')
$adminText = $adminText.Replace('fa-folder-open mr-1.5"></i>Unduh Portofolio', 'fa-eye mr-1.5"></i>Lihat Portofolio')

$adminText = [regex]::Replace(
    $adminText,
    '(<a\s+href="\{\{\s*route\(''admin\.jobs\.applications\.file''[^>]*)(class=)',
    '$1target="_blank" rel="noopener" $2'
)

Write-NoBom $admin $adminText
Write-Host "  - Tombol menjadi Lihat CV / Lihat Portofolio." -ForegroundColor Green

Step "[4/6] Membatasi upload baru ke format yang bisa dipreview browser ..."

$formText = [System.IO.File]::ReadAllText((Resolve-Path $form))

$formText = [regex]::Replace(
    $formText,
    '(name="cv_file"[\s\S]*?accept=")[^"]*(")',
    '$1.pdf$2',
    1
)
$formText = $formText.Replace('PDF, DOC, atau DOCX', 'PDF')
$formText = $formText.Replace('PDF/DOC/DOCX, maksimal 5 MB.', 'PDF, maksimal 5 MB.')

$formText = [regex]::Replace(
    $formText,
    '(name="portfolio_file"[\s\S]*?accept=")[^"]*(")',
    '$1.pdf,.jpg,.jpeg,.png$2',
    1
)
$formText = $formText.Replace('PDF, JPG, PNG, atau ZIP', 'PDF, JPG, atau PNG')
$formText = $formText.Replace('PDF/JPG/PNG/ZIP, maksimal 10 MB.', 'PDF/JPG/PNG, maksimal 10 MB.')

Write-NoBom $form $formText

$controllerText = [System.IO.File]::ReadAllText((Resolve-Path $controller))
$controllerText = $controllerText.Replace("'mimes:pdf,doc,docx'", "'mimes:pdf'")
$controllerText = $controllerText.Replace("'mimes:pdf,jpg,jpeg,png,zip'", "'mimes:pdf,jpg,jpeg,png'")
$controllerText = $controllerText.Replace('CV harus berupa PDF, DOC, atau DOCX.', 'CV harus berupa PDF agar dapat dipreview oleh admin tanpa diunduh.')
$controllerText = $controllerText.Replace('Portofolio harus berupa PDF, JPG, PNG, atau ZIP.', 'Portofolio harus berupa PDF, JPG, atau PNG agar dapat dipreview oleh admin tanpa diunduh.')

Write-NoBom $controller $controllerText
Write-Host "  - Upload baru dibatasi ke format previewable." -ForegroundColor Green

Step "[5/6] Membuat halaman aman untuk file lama yang tidak bisa dipreview ..."

$unsupported = ".\resources\views\pages\admin\file-preview-unsupported.blade.php"
$unsupportedDir = Split-Path $unsupported -Parent
New-Item -ItemType Directory -Path $unsupportedDir -Force | Out-Null

$unsupportedContent = @'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview File Pelamar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F7F4EF] font-sans text-[#2A211B] antialiased">
    <main class="flex min-h-screen items-center justify-center p-6">
        <section class="w-full max-w-xl rounded-4xl border border-[#E2D6C8] bg-white p-7 text-center shadow-[0_24px_70px_-45px_rgba(61,43,31,.45)] sm:p-9">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F2E5D5] text-[#A96D37]">
                <i class="fa-solid fa-file-circle-exclamation text-xl"></i>
            </span>

            <p class="mt-6 text-[10px] font-semibold uppercase tracking-[0.24em] text-[#A96D37]">File Lama</p>
            <h1 class="mt-2 font-display text-2xl font-semibold text-[#3D2B1F]">
                Format {{ $extension }} tidak dapat dipreview langsung
            </h1>

            <p class="mt-4 text-sm leading-7 text-[#75695D]">
                File <strong>{{ $fileName }}</strong> milik <strong>{{ $application->full_name }}</strong>
                tersimpan aman di server, tetapi format ini tidak bisa ditampilkan langsung oleh browser
                tanpa mengunduhnya ke perangkat.
            </p>

            <div class="mt-5 rounded-2xl bg-[#FCF7F1] p-4 text-left text-xs leading-6 text-[#7B6A5C]">
                Untuk lamaran baru, sistem sekarang hanya menerima format yang dapat dipreview langsung:
                CV dalam PDF dan portofolio dalam PDF/JPG/PNG.
            </div>

            <button
                type="button"
                onclick="window.close()"
                class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-[#3B2518] px-5 py-3 text-sm font-semibold text-white"
            >
                <i class="fa-solid fa-xmark text-xs"></i>Tutup Preview
            </button>
        </section>
    </main>
</body>
</html>
'@

[System.IO.File]::WriteAllText(
    [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $unsupported)),
    $unsupportedContent,
    $utf8NoBom
)

Step "[6/6] Validasi syntax + clear cache ..."

$check = @($routes, $admin, $form, $controller, $unsupported)
foreach ($file in $check) {
    php -l $file | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Backup tersedia di: $backupDir"
    }
}

php artisan optimize:clear | Out-Host
php artisan route:list --name=admin.jobs.applications.file | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Sekarang:" -ForegroundColor Yellow
Write-Host "  - Admin klik Lihat CV / Lihat Portofolio." -ForegroundColor White
Write-Host "  - PDF/JPG/PNG dibuka langsung di tab browser." -ForegroundColor White
Write-Host "  - Tidak ada download otomatis ke perangkat Admin." -ForegroundColor White
Write-Host "  - Upload baru dibatasi ke format yang dapat dipreview." -ForegroundColor White
Write-Host "  - File tetap private di storage Laravel." -ForegroundColor White
Write-Host "  - File lama DOC/DOCX/ZIP tidak dipaksa download." -ForegroundColor White
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
