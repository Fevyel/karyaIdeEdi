$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$form = ".\resources\views\pages\frontend\lamar-kerja.blade.php"
$controller = ".\app\Http\Controllers\JobApplicationController.php"

foreach ($file in @($form, $controller)) {
    if (-not (Test-Path $file)) {
        throw "File tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-portfolio-file-drive-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - Portofolio File ATAU Link Google Drive" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/5] Membuat backup ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\app\Http\Controllers" -Force | Out-Null
Copy-Item $form "$backupDir\resources\views\pages\frontend\lamar-kerja.blade.php" -Force
Copy-Item $controller "$backupDir\app\Http\Controllers\JobApplicationController.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Merapikan form Portofolio ..."

$formText = [System.IO.File]::ReadAllText((Resolve-Path $form))

# Hapus field Link portofolio lama yang berdiri sendiri.
$standalonePattern = '(?s)\s*<div class="sm:col-span-2">\s*<label[^>]*>\s*Link portofolio.*?</label>\s*<input\s+name="portfolio_url".*?</div>\s*'
if ([regex]::IsMatch($formText, $standalonePattern)) {
    $formText = [regex]::Replace($formText, $standalonePattern, "`r`n", 1)
    Write-Host "  - Field Link portofolio lama dihapus." -ForegroundColor Green
} elseif ($formText -notmatch 'Link portofolio') {
    Write-Host "  - Field Link portofolio lama sudah tidak ada, dilewati." -ForegroundColor DarkGray
} else {
    throw "Field Link portofolio lama terdeteksi tetapi polanya tidak cocok. Berhenti agar tidak merusak form."
}

# Ganti blok Portofolio file menjadi satu section: Upload file ATAU Link Google Drive.
$portfolioFilePattern = '(?s)<div>\s*<label[^>]*>\s*Portofolio file\s*</label>\s*<div class="rounded-2xl border border-dashed border-\[#D7C6B2\] bg-\[#FCFAF7\] p-4">\s*<input\s+name="portfolio_file".*?</div>\s*</div>'

$newPortfolioBlock = @'
<div class="sm:col-span-2">
    <div class="mb-3">
        <label class="block text-sm font-semibold text-[#493224]">
            Portofolio <span class="font-normal text-[#8A7B6D]">(opsional)</span>
        </label>
        <p class="mt-1 text-xs leading-6 text-[#8A7B6D]">
            Lampirkan portofolio dengan salah satu cara: upload file langsung atau tempel link Google Drive.
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1fr_auto_1fr] lg:items-stretch">
        <div class="rounded-3xl border border-dashed border-[#D7C6B2] bg-[#FCFAF7] p-5 transition focus-within:border-[#A96D37] focus-within:ring-2 focus-within:ring-[#A96D37]/10">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#3B2518] text-white">
                    <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-[#493224]">Upload file</p>
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
        </div>

        <div class="hidden items-center justify-center lg:flex">
            <span class="rounded-full border border-[#DDCEBB] bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-[0.2em] text-[#A17B58]">
                atau
            </span>
        </div>

        <div class="rounded-3xl border border-[#E0D4C6] bg-[#FCFAF7] p-5 transition focus-within:border-[#A96D37] focus-within:ring-2 focus-within:ring-[#A96D37]/10">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#A96D37] text-white">
                    <i class="fa-brands fa-google-drive text-sm"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-[#493224]">Link Google Drive</p>
                    <p class="text-[11px] text-[#8A7B6D]">Pastikan akses link dapat dibuka</p>
                </div>
            </div>

            <input
                name="portfolio_url"
                type="url"
                value="{{ old('portfolio_url') }}"
                placeholder="https://drive.google.com/..."
                class="w-full rounded-2xl border border-[#DED1C1] bg-white px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"
            >
            <p class="mt-2 text-[11px] text-[#8A7B6D]">Gunakan link Drive yang sudah diatur agar dapat dilihat oleh penerima link.</p>
        </div>
    </div>

    <p class="mt-3 text-[11px] leading-5 text-[#8A7B6D]">
        Tidak perlu mengisi keduanya. Pilih cara yang paling nyaman.
    </p>
</div>
'@

if ([regex]::IsMatch($formText, $portfolioFilePattern)) {
    $formText = [regex]::Replace($formText, $portfolioFilePattern, $newPortfolioBlock.Trim(), 1)
    Write-Host "  - Portofolio sekarang bisa Upload File ATAU Link Google Drive." -ForegroundColor Green
} elseif ($formText -match 'fa-google-drive' -and $formText -match 'name="portfolio_url"') {
    Write-Host "  - Section Portofolio baru sudah ada, dilewati." -ForegroundColor DarkGray
} else {
    throw "Blok Portofolio file lama tidak ditemukan. Tidak ada perubahan dilakukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $form), $formText, $utf8NoBom)

Step "[3/5] Memperketat validasi Link Google Drive ..."

$controllerText = [System.IO.File]::ReadAllText((Resolve-Path $controller))

$oldRule = "'portfolio_url' => ['nullable', 'url', 'max:500'],"
$newRule = "'portfolio_url' => ['nullable', 'url', 'max:500', 'regex:/^https?:\/\/(?:drive|docs)\.google\.com\//i'],"

if ($controllerText.Contains($oldRule)) {
    $controllerText = $controllerText.Replace($oldRule, $newRule)
    Write-Host "  - portfolio_url dibatasi ke Google Drive/Google Docs." -ForegroundColor Green
} elseif ($controllerText.Contains("'portfolio_url' => ['nullable', 'url', 'max:500', 'regex:/^https?:\/\/(?:drive|docs)\.google\.com\//i'],")) {
    Write-Host "  - Rule Google Drive sudah aktif, dilewati." -ForegroundColor DarkGray
} else {
    throw "Rule portfolio_url tidak ditemukan."
}

$anchorMessage = "'portfolio_file.mimes' => 'Portofolio harus berupa PDF, JPG, PNG, atau ZIP.',"
$newMessages = @"
'portfolio_url.url' => 'Link portofolio harus berupa URL yang valid.',
            'portfolio_url.regex' => 'Link portofolio harus berasal dari Google Drive.',
            'portfolio_file.mimes' => 'Portofolio harus berupa PDF, JPG, PNG, atau ZIP.',
"@

if ($controllerText.Contains($anchorMessage) -and -not $controllerText.Contains("'portfolio_url.regex'")) {
    $controllerText = $controllerText.Replace($anchorMessage, $newMessages.TrimEnd())
    Write-Host "  - Pesan validasi Link Google Drive ditambahkan." -ForegroundColor Green
}

[System.IO.File]::WriteAllText((Resolve-Path $controller), $controllerText, $utf8NoBom)

Step "[4/5] Validasi syntax ..."
php -l $form | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax form lamaran bermasalah. Restore: $backupDir" }

php -l $controller | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax controller bermasalah. Restore: $backupDir" }

Step "[5/5] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Sekarang form Portofolio:" -ForegroundColor Yellow
Write-Host "  - Field Link portofolio lama sudah dihapus." -ForegroundColor White
Write-Host "  - Di bagian bawah tersedia Upload File ATAU Link Google Drive." -ForegroundColor White
Write-Host "  - Link hanya menerima Google Drive / Google Docs." -ForegroundColor White
Write-Host "  - Keduanya tetap opsional; pelamar cukup memilih salah satu." -ForegroundColor White
Write-Host "  - Tidak ada migration/database baru." -ForegroundColor DarkGray
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
