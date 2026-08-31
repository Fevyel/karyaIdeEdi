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
