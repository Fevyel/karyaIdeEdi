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
$backupDir = ".backup-fix-whatsapp-numeric-$stamp"

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - WhatsApp Hanya Angka" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\app\Http\Controllers" -Force | Out-Null
Copy-Item $form "$backupDir\resources\views\pages\frontend\lamar-kerja.blade.php" -Force
Copy-Item $controller "$backupDir\app\Http\Controllers\JobApplicationController.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Step "[2/4] Memperbaiki input WhatsApp + validasi server ..."

$formText = [System.IO.File]::ReadAllText((Resolve-Path $form))

$oldInput = '<div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">WhatsApp *</label><input name="whatsapp" value="{{ old(''whatsapp'') }}" placeholder="08xxxxxxxxxx" required class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"></div>'

$newInput = @'
<div>
    <label class="mb-1.5 block text-sm font-semibold text-[#493224]">WhatsApp *</label>
    <input
        name="whatsapp"
        type="text"
        inputmode="numeric"
        pattern="[0-9]*"
        maxlength="15"
        autocomplete="tel"
        value="{{ old('whatsapp') }}"
        placeholder="08xxxxxxxxxx"
        required
        oninput="this.value = this.value.replace(/\D/g, '')"
        class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"
    >
    <p class="mt-1.5 text-[11px] text-[#8A7B6D]">Masukkan 8-15 digit angka, misalnya 081234567890.</p>
</div>
'@

if ($formText.Contains($oldInput)) {
    $formText = $formText.Replace($oldInput, $newInput.Trim())
    Write-Host "  - Form WhatsApp sekarang angka-only." -ForegroundColor Green
} else {
    throw "Input WhatsApp tidak ditemukan persis. Tidak ada perubahan dilakukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $form), $formText, $utf8NoBom)

$controllerText = [System.IO.File]::ReadAllText((Resolve-Path $controller))

$oldRule = "'whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{8,30}$/'],"
$newRule = "'whatsapp' => ['required', 'regex:/^\d{8,15}$/'],"

if ($controllerText.Contains($oldRule)) {
    $controllerText = $controllerText.Replace($oldRule, $newRule)
    Write-Host "  - Validasi server sekarang hanya menerima 8-15 digit." -ForegroundColor Green
} else {
    throw "Rule validasi WhatsApp tidak ditemukan."
}

$oldMessage = "'whatsapp.regex' => 'Format nomor WhatsApp belum valid.',"
$newMessage = "'whatsapp.regex' => 'Nomor WhatsApp harus berisi 8-15 digit angka tanpa huruf atau simbol.',"
$controllerText = $controllerText.Replace($oldMessage, $newMessage)

$oldStore = "'whatsapp' => trim($validated['whatsapp']),"
$newStore = "'whatsapp' => preg_replace('/\D+/', '', trim($validated['whatsapp'])),"

if ($controllerText.Contains($oldStore)) {
    $controllerText = $controllerText.Replace($oldStore, $newStore)
    Write-Host "  - Nomor WhatsApp dinormalisasi sebelum disimpan." -ForegroundColor Green
} else {
    throw "Bagian penyimpanan WhatsApp tidak ditemukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $controller), $controllerText, $utf8NoBom)

Step "[3/4] Validasi syntax ..."
php -l $form | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax form lamaran bermasalah. Restore tersedia di $backupDir" }

php -l $controller | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax JobApplicationController bermasalah. Restore tersedia di $backupDir" }

Step "[4/4] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "WhatsApp sekarang hanya menerima angka 0-9 (8-15 digit)." -ForegroundColor White
Write-Host "Huruf dan simbol langsung dibuang saat diketik dan tetap ditolak oleh backend." -ForegroundColor White
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
