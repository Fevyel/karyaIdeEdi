<#
    apply-login-admin.ps1

    Jalankan script ini dari DALAM folder project (folder yang berisi file "artisan").
    Contoh:
        cd D:\path\ke\karyaIdeEdi
        powershell -ExecutionPolicy Bypass -File .\apply-login-admin.ps1

    Script ini akan:
      1. Menghapus file lama yang tidak dipakai (Google OAuth + login lama yang rusak).
      2. Menulis ulang routes/web.php, layout admin, login page, dan seeder admin
         dengan encoding UTF-8 TANPA BOM (supaya tidak ada karakter aneh / file corrupt).
      3. Membersihkan .env dan .env.example dari konfigurasi Google OAuth (jika ada).

    Aman dijalankan berkali-kali (idempotent).
#>

$ErrorActionPreference = "Stop"

# --- Pastikan dijalankan dari root project Laravel ---
if (-not (Test-Path ".\artisan")) {
    Write-Host "ERROR: File 'artisan' tidak ditemukan di folder ini." -ForegroundColor Red
    Write-Host "Pastikan kamu menjalankan script ini dari dalam folder karyaIdeEdi (root project Laravel)." -ForegroundColor Red
    exit 1
}

function Write-Utf8NoBom {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Content
    )
    $dir = Split-Path -Parent $Path
    if ($dir -and -not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    $encoding = New-Object System.Text.UTF8Encoding($false)
    # Normalisasi ke CRLF supaya konsisten dengan file Laravel lain di Windows
    $normalized = $Content -replace "`r`n", "`n"
    $normalized = $normalized -replace "`n", "`r`n"
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $normalized, $encoding)
    Write-Host "  [OK] $Path" -ForegroundColor Green
}

Write-Host ""
Write-Host "== 1. Menghapus file lama (Google OAuth + login rusak) ==" -ForegroundColor Cyan

$filesToRemove = @(
    "app\Http\Controllers\Admin\GoogleRedirectController.php",
    "app\Http\Controllers\Admin\GoogleCallbackController.php",
    "config\admin.php"
)
foreach ($f in $filesToRemove) {
    if (Test-Path $f) {
        Remove-Item $f -Force
        Write-Host "  [DELETED] $f" -ForegroundColor Yellow
    }
}

# Hapus SEMUA file lama di resources/views/pages/admin (termasuk yang nama filenya
# rusak karena karakter unicode saat unzip sebelumnya), lalu kita tulis ulang bersih.
$pagesAdminDir = "resources\views\pages\admin"
if (Test-Path $pagesAdminDir) {
    Get-ChildItem -Path $pagesAdminDir -Force | Remove-Item -Force
    Write-Host "  [CLEANED] $pagesAdminDir\*" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "== 2. Menulis routes/web.php ==" -ForegroundColor Cyan
Write-Utf8NoBom -Path "routes\web.php" -Content @'
<?php

use App\Http\Controllers\Admin\LogoutController;
use Illuminate\Support\Facades\Route;

// ==========================================================
// Sementara: arahkan root langsung ke halaman login admin
// (belum ada frontend customer)
// ==========================================================
Route::redirect('/', '/adminmode/login')->name('home');

// ==========================================================
// Admin Panel - hanya bisa diakses lewat /adminmode
// (URL ini sengaja tidak ditautkan di menu customer)
// ==========================================================
Route::prefix('adminmode')->name('admin.')->group(function () {
    // Halaman login admin (Livewire full-page component)
    Route::livewire('/login', 'pages::admin.login')
        ->middleware('guest')
        ->name('login');

    // Placeholder setelah login. Ini BUKAN dashboard final,
    // hanya untuk membuktikan alur redirect + middleware auth berjalan.
    // Akan diganti pada tahap pembuatan Dashboard.
    Route::view('/', 'admin.dashboard-placeholder')
        ->middleware('auth')
        ->name('dashboard');

    Route::post('/logout', LogoutController::class)
        ->middleware('auth')
        ->name('logout');
});
'@

Write-Host ""
Write-Host "== 3. Menulis resources/views/layouts/admin.blade.php ==" -ForegroundColor Cyan
Write-Utf8NoBom -Path "resources\views\layouts\admin.blade.php" -Content @'
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Admin Panel' }} - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        @livewireStyles
    </head>
    <body class="bg-[var(--color-admin-canvas)] font-sans text-[var(--color-admin-ink)] antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
'@

Write-Host ""
Write-Host "== 4. Menulis resources/views/pages/admin/login.blade.php ==" -ForegroundColor Cyan

# Emoji "bowing" dibuat dari code point (bukan ditulis langsung di source script)
# supaya tidak rusak walaupun script ini dibaca dengan encoding lama di Windows PowerShell.
$bowingEmoji = [System.Char]::ConvertFromUtf32(0x1F647)

$loginBladeContent = @'
<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Login Admin')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Proses login admin: validasi input, cek rate limit,
     * autentikasi via Laravel Auth bawaan, regenerasi session, lalu redirect.
     */
    public function login(): mixed
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, decaySeconds: 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-white px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <h1 class="font-display text-2xl font-bold text-[var(--color-admin-ink)]">
                <i class="fa-solid fa-hands-praying mr-2 text-[var(--color-admin-accent)]"></i>
                Selamat Datang Kembali Boss ????
            </h1>
            <p class="mt-3 text-sm text-[var(--color-admin-ink-soft)]">
                Silakan masukkan email dan password untuk memverifikasi identitas Anda
                sebelum mengakses Dashboard Admin.
            </p>
        </div>

        <div class="rounded-2xl border border-[var(--color-admin-border)] bg-white p-6 shadow-xl shadow-black/5 sm:p-8">
            <form wire:submit="login" class="space-y-5">
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        autocomplete="username"
                        autofocus
                        placeholder="admin@example.com"
                        class="w-full rounded-md border border-[var(--color-admin-border)] px-3 py-2 text-sm text-[var(--color-admin-ink)] focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-1 focus:ring-[var(--color-admin-accent)]"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-[var(--color-admin-ink)]">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="current-password"
                        placeholder="********"
                        class="w-full rounded-md border border-[var(--color-admin-border)] px-3 py-2 text-sm text-[var(--color-admin-ink)] focus:border-[var(--color-admin-accent)] focus:outline-none focus:ring-1 focus:ring-[var(--color-admin-accent)]"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input
                        id="remember"
                        type="checkbox"
                        wire:model="remember"
                        class="h-4 w-4 rounded border-[var(--color-admin-border)] text-[var(--color-admin-accent)] focus:ring-[var(--color-admin-accent)]"
                    >
                    <label for="remember" class="ml-2 text-sm text-[var(--color-admin-ink-soft)]">
                        Ingat Saya
                    </label>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="login"
                    class="w-full rounded-md bg-[var(--color-admin-panel)] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[var(--color-admin-accent-strong)] disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="login">Masuk ke Dashboard</span>
                    <span wire:loading wire:target="login">Memproses...</span>
                </button>
            </form>
        </div>
    </div>
</div>
'@

$loginBladeContent = $loginBladeContent.Replace("????", $bowingEmoji)
Write-Utf8NoBom -Path "resources\views\pages\admin\login.blade.php" -Content $loginBladeContent

Write-Host ""
Write-Host "== 5. Menulis database/seeders/AdminUserSeeder.php ==" -ForegroundColor Cyan
Write-Utf8NoBom -Path "database\seeders\AdminUserSeeder.php" -Content @'
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Buat/pastikan satu-satunya akun admin Toko Mebel.
     * Password disimpan ter-hash menggunakan Hash::make (bcrypt).
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'karyaku@gmail.com'],
            [
                'name' => 'Admin Toko Mebel',
                'password' => Hash::make('pemilikasliideedi123'),
                'email_verified_at' => now(),
            ],
        );
    }
}
'@

Write-Host ""
Write-Host "== 6. Membersihkan .env / .env.example dari config Google OAuth (jika ada) ==" -ForegroundColor Cyan

function Clean-EnvFile {
    param([string]$Path)
    if (-not (Test-Path $Path)) { return }
    $raw = Get-Content -LiteralPath $Path -Raw
    $pattern = "(?ms)^#\s*=+\s*\r?\n#\s*Google OAuth \(Login Admin\)\s*\r?\n#\s*=+\s*\r?\n(GOOGLE_CLIENT_ID=.*\r?\n)(GOOGLE_CLIENT_SECRET=.*\r?\n)(GOOGLE_REDIRECT_URI=.*\r?\n)\r?\n#.*\r?\nADMIN_EMAIL=.*\r?\n?"
    $cleaned = [System.Text.RegularExpressions.Regex]::Replace($raw, $pattern, "")
    if ($cleaned -ne $raw) {
        $encoding = New-Object System.Text.UTF8Encoding($false)
        [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $cleaned, $encoding)
        Write-Host "  [CLEANED] $Path" -ForegroundColor Green
    } else {
        Write-Host "  [SKIP] $Path (tidak ada config Google / sudah bersih)" -ForegroundColor DarkGray
    }
}

Clean-EnvFile ".env"
Clean-EnvFile ".env.example"

Write-Host ""
Write-Host "== SELESAI ==" -ForegroundColor Cyan
Write-Host "Langkah selanjutnya, jalankan manual:" -ForegroundColor White
Write-Host "  composer install" -ForegroundColor Gray
Write-Host "  npm install" -ForegroundColor Gray
Write-Host "  npm run build     (atau npm run dev saat development)" -ForegroundColor Gray
Write-Host "  php artisan migrate --seed" -ForegroundColor Gray
Write-Host "  php artisan serve" -ForegroundColor Gray
Write-Host ""
Write-Host "Lalu buka: http://127.0.0.1:8000/adminmode/login" -ForegroundColor White
Write-Host "Login dengan: karyaku@gmail.com / pemilikasliideedi123" -ForegroundColor White
