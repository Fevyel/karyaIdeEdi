<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TrackingController
{
    private const COOKIE_NAME = 'kie_tracking_tokens';

    private const DEVICE_COOKIE_NAME = 'kie_tracking_device';

    private const COOKIE_MINUTES = 60 * 24 * 60; // 60 hari

    private const MAX_TOKENS = 30;

    /** Batas waktu boleh mengirim Komentar Update, dihitung sejak komentar pertama dibuat. */
    private const UPDATE_WINDOW_DAYS = 30;

    /** Maksimal foto yang boleh dilampirkan per komentar (baik komentar pertama maupun update). */
    private const MAX_PHOTOS = 5;

    public function index(Request $request)
    {
        $tokens = $this->readTokens($request);

        $transactions = $tokens === []
            ? collect()
            : Transaction::query()
                ->with('product:id,nama,slug,thumbnail')
                ->whereIn('tracking_token', $tokens)
                ->latest('created_at')
                ->get()
                ->sortBy(function (Transaction $transaction) use ($tokens) {
                    return array_search($transaction->tracking_token, $tokens, true);
                })
                ->values();

        return view('pages.frontend.lacak-index', compact('transactions'));
    }

    public function show(Request $request, string $trackingToken)
    {
        $transaction = Transaction::query()
            ->with('product:id,nama,slug,thumbnail')
            ->where('tracking_token', $trackingToken)
            ->firstOrFail();

        $deviceId = $this->readOrCreateDeviceId($request);
        $deviceHash = hash('sha256', $deviceId);
        $isTrustedDevice = false;

        if ($transaction->tracking_device_hash === null) {
            $transaction->forceFill(['tracking_device_hash' => $deviceHash])->save();
            $isTrustedDevice = true;
        } else {
            $isTrustedDevice = hash_equals($transaction->tracking_device_hash, $deviceHash);
        }

        // Hanya perangkat pertama yang membuka link yang boleh menyimpan token
        // ke daftar pesanan perangkatnya. Perangkat lain tetap boleh membuka link,
        // tetapi hanya melihat informasi minimum.
        $tokens = $this->readTokens($request);
        if ($isTrustedDevice) {
            $tokens = array_values(array_unique(array_merge([$trackingToken], $tokens)));
            $tokens = array_slice($tokens, 0, self::MAX_TOKENS);
        }

        // Komentar pertama (kalau ada) untuk pesanan ini, beserta Update-nya
        // (kalau sudah pernah dikirim). Dicari lewat transaction_id, bukan
        // nama+produk, supaya tidak salah tangkap komentar pesanan lain.
        $originalComment = Testimonial::query()
            ->where('transaction_id', $transaction->id)
            ->topLevel()
            ->with('updateComment')
            ->first();

        $canSubmitUpdate = $originalComment !== null
            && $originalComment->updateComment === null
            && now()->lt($originalComment->created_at->copy()->addDays(self::UPDATE_WINDOW_DAYS));

        $response = response()->view('pages.frontend.tracking', [
            'transaction' => $transaction,
            'isTrustedDevice' => $isTrustedDevice,
            'originalComment' => $originalComment,
            'updateComment' => $originalComment?->updateComment,
            'canSubmitUpdate' => $canSubmitUpdate,
            'updateWindowDays' => self::UPDATE_WINDOW_DAYS,
            'maxPhotos' => self::MAX_PHOTOS,
        ]);

        $response->cookie(
            self::DEVICE_COOKIE_NAME,
            $deviceId,
            self::COOKIE_MINUTES,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );

        if ($isTrustedDevice) {
            $response->cookie(
                self::COOKIE_NAME,
                json_encode($tokens, JSON_THROW_ON_ERROR),
                self::COOKIE_MINUTES,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            );
        }

        return $response;
    }

    /**
     * Komentar opsional dari pembeli pada halaman tracking pesanan.
     * Kalau diisi, masuk ke tabel yang sama dengan menu "Interaksi" admin
     * (approval_status = pending, sesuai default kolom) untuk difilter admin.
     * Kalau dikosongkan, tidak terjadi apa-apa — memang opsional.
     *
     * `rating` (1-5, opsional) & `is_name_masked` (opsional, hanya berlaku
     * pada komentar pertama — lihat catatan di form) ikut disimpan di sini.
     *
     * Hanya perangkat tepercaya (pemilik link asli) yang boleh mengirim,
     * konsisten dengan proteksi privasi yang sudah ada di method show().
     *
     * Sekali transaksi ini SUDAH punya komentar pertama, submit berikutnya
     * lewat form yang sama otomatis diperlakukan sebagai "Komentar Update"
     * (parent_id menunjuk ke komentar pertama) — hanya boleh SEKALI, dan
     * hanya dalam UPDATE_WINDOW_DAYS hari sejak komentar pertama dibuat.
     */
    public function storeComment(Request $request, string $trackingToken)
    {
        $transaction = Transaction::query()
            ->where('tracking_token', $trackingToken)
            ->firstOrFail();

        $deviceId = $request->cookie(self::DEVICE_COOKIE_NAME);
        $isTrustedDevice = is_string($deviceId)
            && $transaction->tracking_device_hash !== null
            && hash_equals($transaction->tracking_device_hash, hash('sha256', $deviceId));

        abort_unless($isTrustedDevice, 403);

        // Komentar (termasuk Update) hanya boleh dikirim setelah pesanan
        // berstatus Selesai. Kalau masih pending/diproses/dibatalkan, endpoint
        // ini ditutup — konsisten dengan form yang juga disembunyikan di
        // tracking.blade.php untuk status selain 'completed'.
        abort_unless($transaction->status === 'completed', 403);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_name_masked' => ['nullable', 'boolean'],
            'provinsi' => ['nullable', 'string', 'max:255'],
            'kabupaten' => ['nullable', 'string', 'max:255'],
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2048 KB = 2 MB
        ]);

        $comment = trim((string) ($validated['comment'] ?? ''));
        $rating = $validated['rating'] ?? null;
        $isNameMasked = $request->boolean('is_name_masked');
        // Alamat (provinsi/kabupaten) — sama seperti sensor nama, opsional dan
        // hanya berlaku untuk komentar PERTAMA (lihat blok "if ($originalComment
        // === null)" di bawah), sesuai form yang juga menyembunyikannya untuk
        // Komentar Update di tracking.blade.php.
        $provinsi = trim((string) ($validated['provinsi'] ?? ''));
        $kabupaten = trim((string) ($validated['kabupaten'] ?? ''));
        $photos = $this->storePhotos($request->file('photos') ?? []);

        if ($comment === '' && $rating === null && $photos === []) {
            return redirect()->route('tracking.show', $trackingToken);
        }

        $originalComment = Testimonial::query()
            ->where('transaction_id', $transaction->id)
            ->topLevel()
            ->with('updateComment')
            ->first();

        // Belum pernah komentar sama sekali -> ini komentar pertama.
        if ($originalComment === null) {
            Testimonial::query()->create([
                'product_id' => $transaction->product_id,
                'transaction_id' => $transaction->id,
                'customer_name' => $transaction->customer_name,
                'is_name_masked' => $isNameMasked,
                'provinsi' => $provinsi !== '' ? $provinsi : null,
                'kabupaten' => $kabupaten !== '' ? $kabupaten : null,
                'rating' => $rating,
                'comment' => $comment,
                'photos' => $photos === [] ? null : $photos,
            ]);

            return redirect()
                ->route('tracking.show', $trackingToken)
                ->with('comment_status', 'sent');
        }

        // Sudah pernah komentar -> submit ini dianggap Komentar Update.
        $canSubmitUpdate = $originalComment->updateComment === null
            && now()->lt($originalComment->created_at->copy()->addDays(self::UPDATE_WINDOW_DAYS));

        if (! $canSubmitUpdate) {
            return redirect()
                ->route('tracking.show', $trackingToken)
                ->with('comment_status', 'update_blocked');
        }

        Testimonial::query()->create([
            'product_id' => $transaction->product_id,
            'transaction_id' => $transaction->id,
            'parent_id' => $originalComment->id,
            'customer_name' => $transaction->customer_name,
            'rating' => $rating,
            'comment' => $comment,
            'photos' => $photos === [] ? null : $photos,
        ]);

        return redirect()
            ->route('tracking.show', $trackingToken)
            ->with('comment_status', 'update_sent');
    }

    /**
     * Simpan foto yang diupload ke disk public, kembalikan daftar path-nya.
     * Foto yang gagal/invalid sudah disaring lewat validasi sebelum method
     * ini dipanggil, jadi di sini murni penyimpanan saja.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    private function storePhotos(array $files): array
    {
        $paths = [];

        foreach (array_slice($files, 0, self::MAX_PHOTOS) as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $paths[] = $file->store('testimoni', 'public');
        }

        return $paths;
    }

    private function readOrCreateDeviceId(Request $request): string
    {
        $existing = $request->cookie(self::DEVICE_COOKIE_NAME);

        if (is_string($existing) && preg_match('/^[A-Za-z0-9]{64}$/', $existing)) {
            return $existing;
        }

        return Str::random(64);
    }

    private function readTokens(Request $request): array
    {
        $raw = $request->cookie(self::COOKIE_NAME);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        try {
            $tokens = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($tokens)) {
            return [];
        }

        return array_values(array_filter(
            $tokens,
            static fn ($token) => is_string($token) && strlen($token) >= 24 && strlen($token) <= 64
        ));
    }
}
