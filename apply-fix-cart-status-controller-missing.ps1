# ============================================================
# FIX: "Class App\Http\Controllers\CartStatusController does not
#      exist" -- muncul waktu jalanin php artisan route:list
#      (dan bakal 500 kalau ada yang buka /keranjang/status).
#
# ROOT CAUSE:
# routes\web.php sudah lama manggil CartStatusController (dipakai
# fitur cek status pesanan di halaman Keranjang), tapi file
# app\Http\Controllers\CartStatusController.php sendiri kelewat
# ke-save/ketinggalan -- gak pernah ada di project.
#
# APA YANG DIBUAT CONTROLLER INI (method check()):
# Dipanggil dari Keranjang untuk mengecek produk mana di keranjang
# yang SUDAH punya pesanan yang diinput admin DAN terbukti dari
# perangkat yang sama -- pakai device cookie yang SAMA dengan fitur
# Lacak Pesanan (kie_tracking_device, lihat TrackingController).
# Kalau perangkat ini belum pernah buka link Lacak Pesanan, cookie-nya
# belum ada, jadi otomatis dianggap "belum ada pesanan" (aman, tidak error).
#
# Script ini HANYA membuat 1 file baru. Tidak ada file lain yang disentuh.
#
# Cara pakai:
# 1. Jalankan dari folder project (C:\xampp\htdocs\karyaIdeEdi)
# 2. .\apply-fix-cart-status-controller-missing.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$targetFile = "app\Http\Controllers\CartStatusController.php"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: CartStatusController.php hilang" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    Write-Host "Contoh: C:\xampp\htdocs\karyaIdeEdi" -ForegroundColor Yellow
    exit 1
}

if (Test-Path $targetFile) {
    Write-Host "[ERROR] File sudah ada: $targetFile" -ForegroundColor Red
    Write-Host "Kemungkinan script ini sudah pernah dijalankan, atau file sudah dibuat manual." -ForegroundColor Yellow
    Write-Host "Cek isinya dulu ya, ge, sebelum lanjut." -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/3] Membuat file: $targetFile" -ForegroundColor Green

$content = @'
<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dipanggil dari Keranjang (resources/js/app.js) untuk mengecek produk mana
 * saja (dari daftar product_id di keranjang browser) yang SUDAH punya
 * pesanan tersimpan oleh admin DAN pesanan itu terbukti milik perangkat
 * yang sama.
 *
 * Keranjang tidak pakai akun/login (disimpan di browser saja), jadi
 * satu-satunya cara menghubungkan "keranjang di browser ini" dengan
 * "pesanan yang sudah diinput admin" adalah lewat device cookie yang
 * SAMA dipakai fitur Lacak Pesanan (lihat TrackingController) — cookie
 * ini hanya terisi kalau perangkat ini pernah membuka link Lacak Pesanan
 * (/lacak/{trackingToken}) sebagai pemilik link aslinya.
 */
class CartStatusController extends Controller
{
    private const DEVICE_COOKIE_NAME = 'kie_tracking_device';

    public function check(Request $request): JsonResponse
    {
        $productIds = collect((array) $request->query('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return response()->json(['orders' => []]);
        }

        $deviceId = $request->cookie(self::DEVICE_COOKIE_NAME);

        if (! is_string($deviceId) || $deviceId === '') {
            return response()->json(['orders' => []]);
        }

        $deviceHash = hash('sha256', $deviceId);

        $orders = Transaction::query()
            ->whereIn('product_id', $productIds)
            ->where('tracking_device_hash', $deviceHash)
            ->latest('created_at')
            ->get(['id', 'product_id', 'status', 'order_code', 'tracking_token'])
            ->unique('product_id')
            ->mapWithKeys(fn (Transaction $transaction) => [
                $transaction->product_id => [
                    'status' => $transaction->status,
                    'order_code' => $transaction->order_code,
                    'tracking_url' => route('tracking.show', $transaction->tracking_token),
                ],
            ]);

        return response()->json(['orders' => $orders]);
    }
}

'@

$utf8Bom = New-Object System.Text.UTF8Encoding $true
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $targetFile), $content, $utf8Bom)

Write-Host "[2/3] Menjalankan 'composer dump-autoload' (memastikan class baru langsung terdeteksi)..." -ForegroundColor Green
composer dump-autoload

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "[ERROR] composer dump-autoload gagal (exit code $LASTEXITCODE)." -ForegroundColor Red
    Write-Host "File sudah dibuat, tapi coba jalankan manual: composer dump-autoload" -ForegroundColor Yellow
    exit 1
}

Write-Host "[3/3] Selesai." -ForegroundColor Green
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " FIX DITERAPKAN!" -ForegroundColor Cyan
Write-Host " Coba lagi: php artisan route:list --name=testimonials" -ForegroundColor Cyan
Write-Host " atau: php artisan route:list --name=cart" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
