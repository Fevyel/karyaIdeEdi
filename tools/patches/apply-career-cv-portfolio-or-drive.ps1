$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$form = ".\resources\views\pages\frontend\lamar-kerja.blade.php"
$controller = ".\app\Http\Controllers\JobApplicationController.php"
$admin = ".\resources\views\pages\admin\loker.blade.php"

foreach ($file in @($form, $controller, $admin)) {
    if (-not (Test-Path $file)) {
        throw "File tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-career-documents-method-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "==================================================================" -ForegroundColor Yellow
Write-Host " Career - CV & Portofolio Wajib + Alternatif Link Google Drive" -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Yellow

Step "[1/5] Membuat backup ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\admin" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\app\Http\Controllers" -Force | Out-Null
Copy-Item $form "$backupDir\resources\views\pages\frontend\lamar-kerja.blade.php" -Force
Copy-Item $controller "$backupDir\app\Http\Controllers\JobApplicationController.php" -Force
Copy-Item $admin "$backupDir\resources\views\pages\admin\loker.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Merapikan layout dokumen lamaran ..."

$formText = [System.IO.File]::ReadAllText((Resolve-Path $form))

# Cari area dari CV sampai sebelum penutup form/action berikutnya.
$cvStart = $formText.IndexOf('<label class="mb-1.5 block text-sm font-semibold text-[#493224]">CV *')
if ($cvStart -lt 0) {
    throw "Label CV tidak ditemukan."
}
$cvBlockStart = $formText.LastIndexOf('<div', $cvStart)
if ($cvBlockStart -lt 0) {
    throw "Awal blok CV tidak ditemukan."
}

$portfolioEndMarker = '<p class="mt-3 text-[11px] leading-5 text-[#8A7B6D]">'
$portfolioEndStart = $formText.IndexOf($portfolioEndMarker, $cvStart)
if ($portfolioEndStart -lt 0) {
    throw "Blok Portofolio baru tidak ditemukan."
}
$portfolioEndClose = $formText.IndexOf('</p>', $portfolioEndStart)
if ($portfolioEndClose -lt 0) {
    throw "Penutup catatan Portofolio tidak ditemukan."
}
$portfolioOuterClose = $formText.IndexOf('</div>', $portfolioEndClose + 4)
if ($portfolioOuterClose -lt 0) {
    throw "Penutup blok Portofolio tidak ditemukan."
}
$replaceEnd = $portfolioOuterClose + 6

$newDocs = @'
<div class="sm:col-span-2">
    <div class="mb-4">
        <p class="text-sm font-semibold text-[#493224]">Dokumen Lamaran *</p>
        <p class="mt-1 text-xs leading-6 text-[#8A7B6D]">
            CV dan portofolio sama-sama wajib. Pilih salah satu metode pengiriman:
            upload kedua file langsung, atau kirim satu link Google Drive yang berisi CV + portofolio.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        {{-- CV --}}
        <div class="rounded-3xl border border-dashed border-[#D7C6B2] bg-[#FCFAF7] p-5">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#3B2518] text-white">
                    <i class="fa-solid fa-file-lines text-sm"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-[#493224]">CV *</p>
                    <p class="text-[11px] text-[#8A7B6D]">PDF, DOC, atau DOCX</p>
                </div>
            </div>

            <input
                name="cv_file"
                type="file"
                accept=".pdf,.doc,.docx"
                class="block w-full text-xs text-[#75695D] file:mr-3 file:rounded-full file:border-0 file:bg-[#3B2518] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
            >
            <p class="mt-2 text-[11px] text-[#8A7B6D]">Maksimal 5 MB.</p>
            @error('cv_file')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- PORTOFOLIO --}}
        <div class="rounded-3xl border border-dashed border-[#D7C6B2] bg-[#FCFAF7] p-5">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#8A5A31] text-white">
                    <i class="fa-solid fa-images text-sm"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-[#493224]">Portofolio *</p>
                    <p class="text-[11px] text-[#8A7B6D]">PDF, JPG, PNG, atau ZIP</p>
                </div>
            </div>

            <input
                name="portfolio_file"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,.zip"
                class="block w-full text-xs text-[#75695D] file:mr-3 file:rounded-full file:border-0 file:bg-[#8A5A31] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white"
            >
            <p class="mt-2 text-[11px] text-[#8A7B6D]">Maksimal 10 MB.</p>
            @error('portfolio_file')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="my-5 flex items-center gap-4">
        <span class="h-px flex-1 bg-[#E3D7C8]"></span>
        <span class="rounded-full border border-[#DDCEBB] bg-white px-4 py-2 text-[10px] font-bold uppercase tracking-[0.24em] text-[#A17B58]">
            atau
        </span>
        <span class="h-px flex-1 bg-[#E3D7C8]"></span>
    </div>

    {{-- FULL WIDTH GOOGLE DRIVE --}}
    <div class="rounded-3xl border border-[#E0D4C6] bg-[#FCFAF7] p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#A96D37] text-white">
                    <i class="fa-brands fa-google-drive text-base"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-[#493224]">Kirim lewat Google Drive</p>
                    <p class="text-[11px] leading-5 text-[#8A7B6D]">
                        Alternatif upload langsung. Link wajib berisi CV dan portofolio.
                    </p>
                </div>
            </div>

            <span class="inline-flex w-fit rounded-full bg-[#F2E5D5] px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-[#9B6A3C]">
                CV + Portofolio
            </span>
        </div>

        <input
            name="portfolio_url"
            type="url"
            value="{{ old('portfolio_url') }}"
            placeholder="https://drive.google.com/..."
            class="mt-4 w-full rounded-2xl border border-[#DED1C1] bg-white px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"
        >

        <div class="mt-3 flex items-start gap-2 text-[11px] leading-5 text-[#8A7B6D]">
            <i class="fa-solid fa-circle-info mt-0.5 text-[#A96D37]"></i>
            <p>Pastikan akses Google Drive diatur menjadi dapat dilihat oleh siapa saja yang memiliki link.</p>
        </div>

        @error('portfolio_url')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
'@

$formText = $formText.Substring(0, $cvBlockStart) + $newDocs.Trim() + $formText.Substring($replaceEnd)
[System.IO.File]::WriteAllText((Resolve-Path $form), $formText, $utf8NoBom)
Write-Host "  - Layout sekarang: CV + Portofolio berdampingan, Google Drive full width di bawah." -ForegroundColor Green

Step "[3/5] Menyesuaikan validasi: upload KEDUA file ATAU Google Drive ..."

$controllerText = [System.IO.File]::ReadAllText((Resolve-Path $controller))

# Ganti rule dengan regex agar tahan whitespace.
$controllerText = [regex]::Replace(
    $controllerText,
    "(?m)^\s*'cv_file'\s*=>\s*\[[^\r\n]*\],\s*$",
    "            'cv_file' => ['nullable', 'required_without:portfolio_url', 'file', 'mimes:pdf,doc,docx', 'max:5120'],",
    1
)

$controllerText = [regex]::Replace(
    $controllerText,
    "(?m)^\s*'portfolio_file'\s*=>\s*\[[^\r\n]*\],\s*$",
    "            'portfolio_file' => ['nullable', 'required_without:portfolio_url', 'file', 'mimes:pdf,jpg,jpeg,png,zip', 'max:10240'],",
    1
)

$controllerText = [regex]::Replace(
    $controllerText,
    "(?m)^\s*'portfolio_url'\s*=>\s*\[[^\r\n]*\],\s*$",
    "            'portfolio_url' => ['nullable', 'required_without_all:cv_file,portfolio_file', 'url', 'max:500', 'regex:/^https?:\/\/(?:drive|docs)\.google\.com\//i'],",
    1
)

# Tambah/ubah pesan validasi.
$messageAnchor = "'portfolio_url.regex' => 'Link portofolio harus berasal dari Google Drive.',"
$messages = @"
'cv_file.required_without' => 'Upload CV wajib jika Anda tidak menggunakan link Google Drive.',
            'portfolio_file.required_without' => 'Upload portofolio wajib jika Anda tidak menggunakan link Google Drive.',
            'portfolio_url.required_without_all' => 'Upload CV + portofolio, atau isi link Google Drive yang berisi keduanya.',
            'portfolio_url.regex' => 'Link harus berasal dari Google Drive.',
"@

if ($controllerText.Contains($messageAnchor)) {
    $controllerText = $controllerText.Replace($messageAnchor, $messages.TrimEnd())
} elseif (-not $controllerText.Contains("'cv_file.required_without'")) {
    $genericAnchor = "'cv_file.mimes' => 'CV harus berupa PDF, DOC, atau DOCX.',"
    if ($controllerText.Contains($genericAnchor)) {
        $controllerText = $controllerText.Replace($genericAnchor, $messages.TrimEnd() + "`r`n            " + $genericAnchor)
    }
}

[System.IO.File]::WriteAllText((Resolve-Path $controller), $controllerText, $utf8NoBom)
Write-Host "  - Backend: wajib CV + portofolio jika tidak memakai Google Drive." -ForegroundColor Green
Write-Host "  - Backend: link Google Drive dapat menggantikan upload kedua file." -ForegroundColor Green

Step "[4/5] Merapikan label dokumen di Admin Pelamar ..."

$adminText = [System.IO.File]::ReadAllText((Resolve-Path $admin))
$adminText = $adminText.Replace('Link portofolio', 'Google Drive (CV + Portofolio)')
$adminText = $adminText.Replace('Portofolio URL', 'Google Drive (CV + Portofolio)')
[System.IO.File]::WriteAllText((Resolve-Path $admin), $adminText, $utf8NoBom)

php -l $form | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax form lamaran bermasalah. Restore: $backupDir" }

php -l $controller | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax controller bermasalah. Restore: $backupDir" }

php -l $admin | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax admin Loker bermasalah. Restore: $backupDir" }

Step "[5/5] Membersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Alur dokumen sekarang:" -ForegroundColor Yellow
Write-Host "  OPSI A: upload CV + upload Portofolio (keduanya wajib)." -ForegroundColor White
Write-Host "  OPSI B: isi 1 link Google Drive full width yang berisi CV + Portofolio." -ForegroundColor White
Write-Host "  Jika memilih Drive, upload file tidak wajib." -ForegroundColor White
Write-Host "  Jika tidak memakai Drive, kedua file wajib." -ForegroundColor White
Write-Host "  Tidak ada migration baru." -ForegroundColor DarkGray
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
