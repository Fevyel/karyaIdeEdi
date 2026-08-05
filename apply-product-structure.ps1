<#
    apply-product-structure.ps1

    Jalankan dari DALAM folder project (folder yang berisi file "artisan").
    Contoh:
        cd C:\xampp\htdocs\karyaIdeEdi
        powershell -ExecutionPolicy Bypass -File .\apply-product-structure.ps1

    Script ini HANYA mengubah:
      1. database/migrations/2026_07_27_070001_create_products_table.php
         (struktur tabel products diganti sesuai field baru yang diminta)
      2. app/Models/Product.php
         (fillable, casts, relasi transactions/testimonials, plus accessor
          "name" -> alias baca-saja ke kolom "nama" supaya kode dashboard/
          interaksi yang sudah ada tetap jalan tanpa diubah)

    Tidak menyentuh file lain sama sekali (login, dashboard, profil,
    pengaturan, navbar, hero, dll semuanya dibiarkan apa adanya).

    Aman dijalankan berkali-kali (idempotent).
#>

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".\artisan")) {
    Write-Host "ERROR: File 'artisan' tidak ditemukan di folder ini." -ForegroundColor Red
    Write-Host "Pastikan kamu menjalankan script ini dari dalam folder root project Laravel." -ForegroundColor Red
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
    $normalized = $Content -replace "`r`n", "`n"
    $normalized = $normalized -replace "`n", "`r`n"
    [System.IO.File]::WriteAllText((Join-Path (Get-Location) $Path), $normalized, $encoding)
    Write-Host "  [OK] $Path" -ForegroundColor Green
}

Write-Host ""
Write-Host "== 1. Menulis migration products (struktur baru) ==" -ForegroundColor Cyan
Write-Utf8NoBom -Path "database\migrations\2026_07_27_070001_create_products_table.php" -Content @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel produk furniture - dipakai untuk katalog, statistik dashboard,
     * dan direferensikan oleh testimoni (interaksi pembeli).
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->string('kategori');
            $table->decimal('harga', 12, 2)->default(0);
            $table->decimal('harga_diskon', 12, 2)->nullable();
            $table->string('deskripsi_pendek', 500);
            $table->text('deskripsi_lengkap');
            $table->string('thumbnail');
            $table->string('status')->default('aktif');
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('stok')->default(0);
            $table->decimal('berat', 8, 2)->default(0);
            $table->decimal('panjang', 8, 2)->default(0);
            $table->decimal('lebar', 8, 2)->default(0);
            $table->decimal('tinggi', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
'@

Write-Host ""
Write-Host "== 2. Menulis app/Models/Product.php ==" -ForegroundColor Cyan
Write-Utf8NoBom -Path "app\Models\Product.php" -Content @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'slug',
        'kategori',
        'harga',
        'harga_diskon',
        'deskripsi_pendek',
        'deskripsi_lengkap',
        'thumbnail',
        'status',
        'featured',
        'stok',
        'berat',
        'panjang',
        'lebar',
        'tinggi',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'harga_diskon' => 'decimal:2',
            'featured' => 'boolean',
            'stok' => 'integer',
            'berat' => 'decimal:2',
            'panjang' => 'decimal:2',
            'lebar' => 'decimal:2',
            'tinggi' => 'decimal:2',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    /**
     * Alias baca-saja ke kolom `nama`, supaya kode yang sudah ada
     * (mis. resources/views/pages/admin/dashboard.blade.php dan
     * interaksi.blade.php yang memanggil `$product->name`) tetap
     * berfungsi tanpa perlu diubah, sesuai instruksi "jangan mengubah
     * apa pun yang sudah selesai".
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nama,
        );
    }
}
'@

Write-Host ""
Write-Host "== SELESAI ==" -ForegroundColor Cyan
Write-Host "Struktur tabel products berubah, jadi migration lama perlu di-refresh." -ForegroundColor White
Write-Host "Karena belum ada CRUD/seeder produk (tidak ada data penting untuk" -ForegroundColor White
Write-Host "tabel products), jalankan salah satu dari ini:" -ForegroundColor White
Write-Host ""
Write-Host "  php artisan migrate:fresh --seed" -ForegroundColor Gray
Write-Host "  (mengulang SEMUA tabel dari awal, lalu isi ulang akun admin)" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  ATAU, jika hanya mau mengulang tabel products saja:" -ForegroundColor White
Write-Host "  php artisan migrate:rollback --step=1" -ForegroundColor Gray
Write-Host "  php artisan migrate" -ForegroundColor Gray
Write-Host ""
