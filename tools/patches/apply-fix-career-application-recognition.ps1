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
$backupDir = ".backup-career-recognition-$stamp"

$files = @(
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\routes\web.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\cek-lamaran.blade.php"
)

Write-Host "==============================================================" -ForegroundColor Yellow
Write-Host " Career - Fix Deteksi Lamaran Lama + Lihat Hasil Otomatis" -ForegroundColor Yellow
Write-Host "==============================================================" -ForegroundColor Yellow

Step "[1/5] Membuat backup ..."
foreach ($item in $files) {
    if (Test-Path $item) {
        $relative = $item.TrimStart('.', '\')
        $dest = Join-Path $backupDir $relative
        $destDir = Split-Path $dest -Parent
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        Copy-Item $item $dest -Force
    }
}
Write-Host "  Backup: $backupDir" -ForegroundColor DarkGray

Step "[2/5] Membuat halaman recovery lamaran ..."

$lookupView = @'
<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cek Lamaran | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F7F4EF] font-sans text-[#2A211B] antialiased">
@include('partials.frontend.navbar')

<main class="py-14 sm:py-20">
    <div class="mx-auto max-w-3xl px-6 sm:px-8">
        <div class="overflow-hidden rounded-4xl border border-[#E2D6C8] bg-white shadow-[0_24px_70px_-45px_rgba(61,43,31,.45)]">
            <div class="bg-[#2F1D14] px-6 py-7 text-white sm:px-8">
                <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#D8AC72]">Cek Lamaran</p>
                <h1 class="mt-3 font-display text-3xl font-semibold sm:text-4xl">{{ $job->title }}</h1>
                <p class="mt-3 max-w-xl text-sm leading-7 text-white/65">
                    Gunakan email dan nomor WhatsApp yang sama saat mendaftar. Setelah berhasil diverifikasi,
                    browser ini akan mengingat lamaran Anda dan tombol di halaman Career berubah menjadi Lihat Hasil.
                </p>
            </div>

            <form method="POST" action="{{ route('careers.application.claim', $job) }}" class="space-y-5 p-6 sm:p-8">
                @csrf

                @if ($errors->has('claim'))
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $errors->first('claim') }}
                    </div>
                @endif

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#493224]">Email *</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"
                        placeholder="nama@email.com"
                    >
                    @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#493224]">WhatsApp *</label>
                    <input
                        type="text"
                        name="whatsapp"
                        value="{{ old('whatsapp') }}"
                        required
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="15"
                        autocomplete="tel"
                        oninput="this.value=this.value.replace(/\D/g,'')"
                        class="w-full rounded-2xl border border-[#DED1C1] bg-[#FCFAF7] px-4 py-3 text-sm outline-none focus:border-[#A96D37] focus:ring-2 focus:ring-[#A96D37]/15"
                        placeholder="081234567890"
                    >
                    @error('whatsapp')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-[#E8DED2] pt-5 sm:flex-row sm:justify-between">
                    <a href="{{ route('careers.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-[#D8C7B5] px-5 py-3 text-sm font-semibold text-[#5B402D]">
                        <i class="fa-solid fa-arrow-left text-xs"></i>Kembali
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#3B2518] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>Cek Lamaran Saya
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

@include('partials.frontend.footer')
</body>
</html>
'@

Write-NoBom ".\resources\views\pages\frontend\cek-lamaran.blade.php" $lookupView
Write-Host "  - Halaman Cek Lamaran dibuat." -ForegroundColor Green

Step "[3/5] Menghubungkan tracking session/cookie + route + tombol ..."

$patchPhp = @'
<?php

$root = $argv[1] ?? getcwd();

function fp(string $root, string $rel): string
{
    return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
}

function readf(string $root, string $rel): string
{
    $path = fp($root, $rel);
    if (!is_file($path)) {
        throw new RuntimeException("File tidak ditemukan: {$rel}");
    }

    $text = file_get_contents($path);
    if ($text === false) {
        throw new RuntimeException("Gagal membaca: {$rel}");
    }

    return $text;
}

function savef(string $root, string $rel, string $text): void
{
    if (file_put_contents(fp($root, $rel), $text) === false) {
        throw new RuntimeException("Gagal menulis: {$rel}");
    }
}

// ------------------------------------------------------------
// Controller: session tracking + secure claim flow.
// ------------------------------------------------------------
$rel = 'app/Http/Controllers/JobApplicationController.php';
$text = readf($root, $rel);

// result() juga mengingat aplikasi.
if (!str_contains($text, '$this->rememberApplication($application);'."\n\n        return view('pages.frontend.hasil-lamaran'")) {
    $old = <<<'PHP'
    public function result(JobApplication $application, string $token): View
    {
        $this->guardPublicToken($application, $token);

        return view('pages.frontend.hasil-lamaran', [
PHP;

    $new = <<<'PHP'
    public function result(JobApplication $application, string $token): View
    {
        $this->guardPublicToken($application, $token);
        $this->rememberApplication($application);

        return view('pages.frontend.hasil-lamaran', [
PHP;

    if (!str_contains($text, $old)) {
        throw new RuntimeException('Method result() tidak ditemukan dalam bentuk yang diharapkan.');
    }

    $text = str_replace($old, $new, $text);
}

// Tambah halaman claim untuk lamaran lama / browser baru.
if (!str_contains($text, 'public function lookupForm(JobVacancy $job)')) {
    $anchor = "    public function result(JobApplication \$application, string \$token): View";

    if (!str_contains($text, $anchor)) {
        throw new RuntimeException('Anchor method result() tidak ditemukan.');
    }

    $methods = <<<'PHP'
    public function lookupForm(JobVacancy $job): View
    {
        return view('pages.frontend.cek-lamaran', compact('job'));
    }

    public function claim(Request $request, JobVacancy $job): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'whatsapp' => ['required', 'regex:/^\d{8,15}$/'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum valid.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp harus berisi 8-15 digit angka.',
        ]);

        $application = JobApplication::query()
            ->where('job_vacancy_id', $job->id)
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($validated['email']))])
            ->where('whatsapp', preg_replace('/\D+/', '', $validated['whatsapp']))
            ->latest('id')
            ->first();

        if (! $application) {
            return back()
                ->withInput()
                ->withErrors([
                    'claim' => 'Data lamaran tidak ditemukan. Pastikan email dan WhatsApp sama dengan yang digunakan saat mendaftar.',
                ]);
        }

        if (! $application->public_token) {
            $application->forceFill([
                'public_token' => Str::random(64),
            ])->save();
        }

        $this->rememberApplication($application);

        return redirect()
            ->route('careers.application.result', [$application, $application->public_token])
            ->with('career-info', 'Lamaran berhasil ditemukan. Browser ini sekarang akan mengingat status lamaran Anda.');
    }

PHP;

    $text = str_replace($anchor, $methods.$anchor, $text);
}

// Session tracking: lebih andal daripada hanya queued cookie.
$oldRemember = <<<'PHP'
    private function rememberApplication(JobApplication $application): void
    {
        Cookie::queue(cookie(
PHP;

if (str_contains($text, $oldRemember) && !str_contains($text, "session()->put('career_applications.")) {
    $newRemember = <<<'PHP'
    private function rememberApplication(JobApplication $application): void
    {
        session()->put(
            'career_applications.'.$application->job_vacancy_id,
            $application->public_token
        );

        Cookie::queue(cookie(
PHP;

    $text = str_replace($oldRemember, $newRemember, $text);
}

savef($root, $rel, $text);

// ------------------------------------------------------------
// Routes claim.
// ------------------------------------------------------------
$rel = 'routes/web.php';
$text = readf($root, $rel);

if (!str_contains($text, "name('careers.application.lookup')")) {
    $anchor = "Route::get('/karier/hasil/{application}/{token}', [\\App\\Http\\Controllers\\JobApplicationController::class, 'result'])->name('careers.application.result');";

    if (!str_contains($text, $anchor)) {
        throw new RuntimeException('Route hasil lamaran tidak ditemukan.');
    }

    $addition = $anchor."\n"
        ."Route::get('/karier/cek/{job}', [\\App\\Http\\Controllers\\JobApplicationController::class, 'lookupForm'])->name('careers.application.lookup');\n"
        ."Route::post('/karier/cek/{job}', [\\App\\Http\\Controllers\\JobApplicationController::class, 'claim'])->middleware('throttle:10,1')->name('careers.application.claim');";

    $text = str_replace($anchor, $addition, $text);
}

savef($root, $rel, $text);

// ------------------------------------------------------------
// Career view: cookies + session + recovery link + explicit selection state.
// ------------------------------------------------------------
$rel = 'resources/views/pages/frontend/karier.blade.php';
$text = readf($root, $rel);

$oldTokens = <<<'BLADE'
    $applicationTokens = collect(request()->cookies->all())
        ->filter(fn ($value, $key) => str_starts_with($key, 'career_application_'))
        ->values()
        ->filter();

    $myApplications = $applicationTokens->isEmpty()
BLADE;

$newTokens = <<<'BLADE'
    $cookieApplicationTokens = collect(request()->cookies->all())
        ->filter(fn ($value, $key) => str_starts_with($key, 'career_application_'))
        ->values()
        ->filter();

    $sessionApplicationTokens = collect(session('career_applications', []))
        ->values()
        ->filter();

    $applicationTokens = $cookieApplicationTokens
        ->merge($sessionApplicationTokens)
        ->filter()
        ->unique()
        ->values();

    $myApplications = $applicationTokens->isEmpty()
BLADE;

if (str_contains($text, $oldTokens)) {
    $text = str_replace($oldTokens, $newTokens, $text);
} elseif (!str_contains($text, '$sessionApplicationTokens')) {
    throw new RuntimeException('Blok tracking applicationTokens tidak ditemukan.');
}

// Explicit selection info if legacy vacancy hasn't been updated yet.
$oldSelection = <<<'BLADE'
                                        @if ($job->selectionStartDate() && $job->selection_end)
                                            <div>
                                                <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Masa Seleksi</p>
                                                <p class="mt-1 text-sm font-semibold leading-6 text-[#493224]">{{ $job->selectionStartDate()->translatedFormat('d M') }} – {{ $job->selection_end->translatedFormat('d M Y') }}</p>
                                            </div>
                                        @endif
BLADE;

$newSelection = <<<'BLADE'
                                        @if ($job->selectionStartDate() && $job->selection_end)
                                            <div>
                                                <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-[#A08A72]">Masa Seleksi</p>
                                                <p class="mt-1 text-sm font-semibold leading-6 text-[#493224]">{{ $job->selectionStartDate()->translatedFormat('d M') }} – {{ $job->selection_end->translatedFormat('d M Y') }}</p>
                                            </div>
                                        @else
                                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3">
                                                <p class="text-[9px] font-semibold uppercase tracking-[0.18em] text-amber-700">Masa Seleksi</p>
                                                <p class="mt-1 text-xs font-semibold leading-5 text-amber-800">Jadwal seleksi belum diatur oleh admin.</p>
                                            </div>
                                        @endif
BLADE;

if (str_contains($text, $oldSelection)) {
    $text = str_replace($oldSelection, $newSelection, $text);
}

// Add legacy claim link beneath Daftar Sekarang.
$oldButton = <<<'BLADE'
                                    @else
                                        <a href="{{ route('careers.apply', $job) }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-[#3B2518] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15 transition hover:-translate-y-0.5 hover:bg-[#6F4527]">
                                            <i class="fa-solid fa-paper-plane text-xs"></i>Daftar Sekarang
                                        </a>
                                    @endif
BLADE;

$newButton = <<<'BLADE'
                                    @else
                                        <div class="mt-6 space-y-2.5">
                                            <a href="{{ route('careers.apply', $job) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#3B2518] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#3B2518]/15 transition hover:-translate-y-0.5 hover:bg-[#6F4527]">
                                                <i class="fa-solid fa-paper-plane text-xs"></i>Daftar Sekarang
                                            </a>
                                            <a href="{{ route('careers.application.lookup', $job) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-[#D8C7B5] bg-white px-5 py-2.5 text-xs font-semibold text-[#6F5132] transition hover:border-[#B98A5A]">
                                                <i class="fa-solid fa-magnifying-glass text-[10px]"></i>Sudah melamar? Cek hasil
                                            </a>
                                        </div>
                                    @endif
BLADE;

if (str_contains($text, $oldButton)) {
    $text = str_replace($oldButton, $newButton, $text);
} elseif (!str_contains($text, "careers.application.lookup")) {
    throw new RuntimeException('Blok tombol Daftar Sekarang tidak ditemukan.');
}

savef($root, $rel, $text);

echo "PATCH_OK\n";
'@

$tmp = Join-Path $env:TEMP "patch-career-recognition-$stamp.php"
[System.IO.File]::WriteAllText($tmp, $patchPhp, $utf8NoBom)

php $tmp (Get-Location).Path
$exitCode = $LASTEXITCODE
Remove-Item $tmp -Force -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Patch gagal. Backup tersedia di: $backupDir"
}

Step "[4/5] Validasi syntax ..."
$check = @(
    ".\app\Http\Controllers\JobApplicationController.php",
    ".\routes\web.php",
    ".\resources\views\pages\frontend\karier.blade.php",
    ".\resources\views\pages\frontend\cek-lamaran.blade.php"
)

foreach ($file in $check) {
    php -l $file | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Syntax error pada $file. Backup: $backupDir"
    }
}

Step "[5/5] Clear cache + cek route ..."
php artisan optimize:clear | Out-Host
php artisan route:list --name=careers.application | Out-Host

Write-Host ""
Write-Host "SELESAI" -ForegroundColor Green
Write-Host ""
Write-Host "Perubahan:" -ForegroundColor Yellow
Write-Host "  - Lamaran baru dikenali lewat SESSION + cookie." -ForegroundColor White
Write-Host "  - Setelah submit, tombol posisi otomatis menjadi Lihat Hasil." -ForegroundColor White
Write-Host "  - Lamaran lama bisa diklaim aman memakai Email + WhatsApp." -ForegroundColor White
Write-Host "  - Setelah klaim 1x, browser ini mengingat lamaran tersebut." -ForegroundColor White
Write-Host "  - Lowongan lama tanpa selection_end sekarang menampilkan peringatan Jadwal seleksi belum diatur." -ForegroundColor White
Write-Host "  - Tidak ada migration baru." -ForegroundColor DarkGray
Write-Host ""
Write-Host "Backup: $backupDir" -ForegroundColor DarkGray
