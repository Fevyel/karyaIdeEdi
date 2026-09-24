$ErrorActionPreference = 'Stop'

function Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

if (-not (Test-Path ".\artisan")) {
    throw "Jalankan script ini dari root project Laravel: C:\xampp\htdocs\karyaIdeEdi"
}

$admin = ".\resources\views\pages\admin\loker.blade.php"
$front = ".\resources\views\pages\frontend\karier.blade.php"

foreach ($file in @($admin, $front)) {
    if (-not (Test-Path $file)) {
        throw "File tidak ditemukan: $file"
    }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = ".backup-fix-loker-salary-arrow-$stamp"

Write-Host "=============================================================" -ForegroundColor Yellow
Write-Host " Loker - Rapikan Arrow + Input Gaji Rupiah Numerik" -ForegroundColor Yellow
Write-Host "=============================================================" -ForegroundColor Yellow

Step "[1/5] Membuat backup ..."
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\admin" -Force | Out-Null
New-Item -ItemType Directory -Path "$backupDir\resources\views\pages\frontend" -Force | Out-Null
Copy-Item $admin "$backupDir\resources\views\pages\admin\loker.blade.php" -Force
Copy-Item $front "$backupDir\resources\views\pages\frontend\karier.blade.php" -Force
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Step "[2/5] Memperbaiki form Loker ..."

$content = [System.IO.File]::ReadAllText((Resolve-Path $admin))

# A. Saat edit data lama, ambil digit saja.
$oldEdit = '$this->salary_label = (string) $job->salary_label;'
$newEdit = '$this->salary_label = preg_replace(''/\D+/'', '''', (string) $job->salary_label) ?: '''';'

if ($content.Contains($oldEdit)) {
    $content = $content.Replace($oldEdit, $newEdit)
    Write-Host "  - Nilai gaji lama dinormalisasi ke angka saat diedit." -ForegroundColor Green
} elseif (-not $content.Contains("preg_replace('/\D+/', '', (string) `$job->salary_label)")) {
    throw "Bagian edit salary_label tidak ditemukan. Berhenti agar file tidak diubah sembarangan."
}

# B. Validasi hanya digit.
$oldRule = "'salary_label' => ['nullable', 'string', 'max:120'],"
$newRule = "'salary_label' => ['nullable', 'regex:/^\d{1,15}$/'],"

if ($content.Contains($oldRule)) {
    $content = $content.Replace($oldRule, $newRule)
    Write-Host "  - Validasi gaji diubah menjadi angka saja." -ForegroundColor Green
} elseif (-not $content.Contains("'salary_label' => ['nullable', 'regex:/^\d{1,15}$/'],")) {
    throw "Rule salary_label tidak ditemukan."
}

# C. Pesan validasi.
$oldMessages = "'location.required' => 'Lokasi kerja wajib diisi.',"
$newMessages = @"
'location.required' => 'Lokasi kerja wajib diisi.',
            'salary_label.regex' => 'Gaji hanya boleh berisi angka, tanpa huruf atau simbol.',
"@

if ($content.Contains($oldMessages) -and -not $content.Contains("'salary_label.regex'")) {
    $content = $content.Replace($oldMessages, $newMessages.TrimEnd())
    Write-Host "  - Pesan validasi gaji ditambahkan." -ForegroundColor Green
}

# D. Normalisasi sebelum save.
$oldNormalize = '$data[''salary_label''] = trim($data[''salary_label''] ?? '''') ?: null;'
$newNormalize = '$data[''salary_label''] = preg_replace(''/\D+/'', '''', trim((string) ($data[''salary_label''] ?? ''''))) ?: null;'

if ($content.Contains($oldNormalize)) {
    $content = $content.Replace($oldNormalize, $newNormalize)
    Write-Host "  - Nilai gaji disimpan sebagai digit murni." -ForegroundColor Green
}

# E. UI gaji: prefix Rp + numeric-only.
$oldSalaryUi = @'
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Gaji / kompensasi <span class="text-admin-ink-soft">(opsional)</span></label>
                    <input wire:model="salary_label" type="text" placeholder="Contoh: Rp3–5 juta / bulan atau Kompetitif" class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3.5 py-3 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20">
                </div>
'@

$newSalaryUi = @'
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-admin-ink">Gaji / kompensasi <span class="text-admin-ink-soft">(opsional)</span></label>
                    <div class="flex overflow-hidden rounded-xl border border-admin-border bg-admin-canvas transition focus-within:border-admin-accent focus-within:ring-2 focus-within:ring-admin-accent/20">
                        <span class="flex shrink-0 items-center border-r border-admin-border bg-admin-cream px-4 text-sm font-semibold text-admin-accent">Rp</span>
                        <input
                            wire:model="salary_label"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="15"
                            autocomplete="off"
                            placeholder="3500000"
                            oninput="this.value = this.value.replace(/\D/g, '')"
                            class="min-w-0 flex-1 bg-transparent px-3.5 py-3 text-sm text-admin-ink outline-none"
                        >
                    </div>
                    <p class="mt-1.5 text-[11px] text-admin-ink-soft">Masukkan angka saja. Contoh: <span class="font-semibold">3500000</span> akan tampil sebagai <span class="font-semibold">Rp 3.500.000</span>.</p>
                    @error('salary_label')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
'@

if ($content.Contains($oldSalaryUi)) {
    $content = $content.Replace($oldSalaryUi, $newSalaryUi)
    Write-Host "  - Input gaji sekarang memakai prefix Rp dan menolak huruf." -ForegroundColor Green
} elseif (-not $content.Contains('oninput="this.value = this.value.replace(/\D/g, '''')"')) {
    throw "Blok UI gaji tidak ditemukan persis."
}

# F. Form berada di atas utility arrow, jadi arrow tidak menimpa field/action.
$formOld = '<form wire:submit="save" class="overflow-hidden rounded-3xl border border-admin-border bg-admin-surface shadow-sm">'
$formNew = '<form wire:submit="save" class="relative z-60 overflow-hidden rounded-3xl border border-admin-border bg-admin-surface shadow-sm">'

if ($content.Contains($formOld)) {
    $content = $content.Replace($formOld, $formNew)
}

$openDiv = '<div class="space-y-6">'
$arrowStyle = @'
<div class="space-y-6">
    @if ($showForm)
        {{-- Back-to-top global diturunkan layer-nya supaya tidak menimpa form Loker. --}}
        <style>
            #back-to-top-btn { z-index: 20 !important; }
        </style>
    @endif
'@

if ($content.Contains($openDiv) -and -not $content.Contains('#back-to-top-btn { z-index: 20 !important; }')) {
    $content = $content.Replace($openDiv, $arrowStyle)
    Write-Host "  - Tombol arrow tidak lagi menimpa form Lowongan." -ForegroundColor Green
}

[System.IO.File]::WriteAllText((Resolve-Path $admin), $content, $utf8NoBom)

Step "[3/5] Memformat gaji Rupiah di halaman Careers ..."

$frontContent = [System.IO.File]::ReadAllText((Resolve-Path $front))

$oldFrontSalary = '<p class="mt-1 text-sm font-semibold text-[#493224]">{{ $job->salary_label }}</p>'
$newFrontSalary = '<p class="mt-1 text-sm font-semibold text-[#493224]">Rp {{ number_format((int) $job->salary_label, 0, '','', ''.'') }}</p>'

if ($frontContent.Contains($oldFrontSalary)) {
    $frontContent = $frontContent.Replace($oldFrontSalary, $newFrontSalary)
    Write-Host "  - Gaji frontend diformat menjadi Rupiah." -ForegroundColor Green
} elseif (-not $frontContent.Contains('number_format((int) $job->salary_label')) {
    throw "Tampilan salary_label di karier.blade.php tidak ditemukan."
}

[System.IO.File]::WriteAllText((Resolve-Path $front), $frontContent, $utf8NoBom)

Step "[4/5] Validasi syntax ..."
php -l $admin | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax admin Loker bermasalah. Restore dari: $backupDir" }

php -l $front | Out-Host
if ($LASTEXITCODE -ne 0) { throw "Syntax halaman Careers bermasalah. Restore dari: $backupDir" }

Step "[5/5] Bersihkan cache ..."
php artisan view:clear | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Perubahan:" -ForegroundColor Yellow
Write-Host "  - Arrow/back-to-top tidak lagi menimpa form Lowongan." -ForegroundColor White
Write-Host "  - Gaji hanya menerima digit 0-9." -ForegroundColor White
Write-Host "  - Prefix Rp tampil permanen di depan input." -ForegroundColor White
Write-Host "  - Frontend menampilkan format contoh: Rp 3.500.000." -ForegroundColor White
Write-Host "  - Tidak ada migration baru." -ForegroundColor DarkGray
