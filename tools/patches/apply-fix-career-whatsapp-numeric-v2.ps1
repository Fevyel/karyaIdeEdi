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
$backupDir = ".backup-fix-whatsapp-numeric-v2-$stamp"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Career - WhatsApp Angka Only (Resume/Fix v2)" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/4] Membuat backup ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\app\Http\Controllers" -Force | Out-Null
Copy-Item $form "$backupDir\resources\views\pages\frontend\lamar-kerja.blade.php" -Force
Copy-Item $controller "$backupDir\app\Http\Controllers\JobApplicationController.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/4] Memastikan frontend dan backend sama-sama angka only ..."

# ---------- FRONTEND ----------
$formText = [System.IO.File]::ReadAllText((Resolve-Path $form))

if ($formText.Contains('oninput="this.value = this.value.replace(/\D/g, '''')"')) {
    Write-Host "  - Frontend: input WhatsApp sudah angka-only, dilewati." -ForegroundColor DarkGray
} else {
    $oldInput = @'
<div><label class="mb-1.5 block text-sm font-semibold text-[#493224]">WhatsApp *</label><input name="whatsapp" value="{{ old('whatsapp') }}" placeholder="08xxxxxxxxxx" required class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"></div>
'@

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

    if (-not $formText.Contains($oldInput.Trim())) {
        throw "Frontend: blok WhatsApp lama tidak ditemukan dan versi baru juga belum terdeteksi."
    }

    $formText = $formText.Replace($oldInput.Trim(), $newInput.Trim())
    [System.IO.File]::WriteAllText((Resolve-Path $form), $formText, $utf8NoBom)
    Write-Host "  - Frontend: input WhatsApp diubah menjadi angka-only." -ForegroundColor Green
}

# ---------- BACKEND ----------
$controllerText = [System.IO.File]::ReadAllText((Resolve-Path $controller))

# Rule validation. Pakai regex replacement supaya tidak bergantung pada whitespace.
$rulePattern = "(?m)^\s*'whatsapp'\s*=>\s*\[[^\r\n]*\],\s*$"
$ruleReplacement = "            'whatsapp' => ['required', 'regex:/^\d{8,15}$/'],"

if ($controllerText -match "'whatsapp'\s*=>\s*\['required',\s*'regex:/\^\\d\{8,15\}\$/'\]") {
    Write-Host "  - Backend: rule WhatsApp sudah 8-15 digit, dilewati." -ForegroundColor DarkGray
} elseif ([regex]::IsMatch($controllerText, $rulePattern)) {
    $controllerText = [regex]::Replace($controllerText, $rulePattern, $ruleReplacement, 1)
    Write-Host "  - Backend: validasi WhatsApp sekarang 8-15 digit angka." -ForegroundColor Green
} else {
    throw "Backend: rule WhatsApp tidak ditemukan."
}

# Pesan validasi.
$controllerText = $controllerText.Replace(
    "'whatsapp.regex' => 'Format nomor WhatsApp belum valid.',",
    "'whatsapp.regex' => 'Nomor WhatsApp harus berisi 8-15 digit angka tanpa huruf atau simbol.',"
)

# Normalisasi saat create(). Ini yang gagal pada script v1 karena PowerShell
# menginterpolasi `$validated` di string pencarian.
$oldStore = @'
                    'whatsapp' => trim($validated['whatsapp']),
'@

$newStore = @'
                    'whatsapp' => preg_replace('/\D+/', '', trim($validated['whatsapp'])),
'@

if ($controllerText.Contains("preg_replace('/\D+/', '', trim(`$validated['whatsapp']))")) {
    Write-Host "  - Backend: normalisasi nomor sudah ada, dilewati." -ForegroundColor DarkGray
} elseif ($controllerText.Contains($oldStore.TrimEnd())) {
    $controllerText = $controllerText.Replace($oldStore.TrimEnd(), $newStore.TrimEnd())
    Write-Host "  - Backend: nomor dinormalisasi sebelum masuk database." -ForegroundColor Green
} else {
    throw "Backend: baris penyimpanan WhatsApp tidak ditemukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $controller), $controllerText, $utf8NoBom)

Step "[3/4] Validasi syntax ..."
php -l $form | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax form lamaran bermasalah. Restore: $backupDir" }

php -l $controller | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax controller bermasalah. Restore: $backupDir" }

Step "[4/4] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host "WhatsApp sekarang benar-benar angka only:" -ForegroundColor Yellow
Write-Host "  - Browser langsung membuang huruf/simbol." -ForegroundColor White
Write-Host "  - Backend hanya menerima 8-15 digit." -ForegroundColor White
Write-Host "  - Sebelum database, nomor dinormalisasi menjadi digit murni." -ForegroundColor White
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
