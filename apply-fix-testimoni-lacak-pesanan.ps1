# ============================================================
# FIX/ADD: Rating bintang + sensor nama di Lacak Pesanan,
# dan urutan kartu Testimoni (Nama -> Produk -> Foto Pembeli -> Komentar)
#
# 1. Form "Tambah Komentar" di halaman Lacak Pesanan sekarang punya:
#    - Rating bintang (opsional, klik untuk pilih 1-5).
#    - Checkbox "Samarkan sebagian nama saya" (hanya di komentar PERTAMA,
#      karena cuma itu yang jadi kartu testimoni publik).
# 2. Kartu testimoni publik (Beranda & /testimoni) urutannya sekarang:
#    Nama pembeli (+ foto profil & rating) -> Produk yang dibeli ->
#    Foto yang dikirim pembeli (kalau ada, kalau tidak langsung ke
#    komentar) -> Komentar.
#
# TIDAK menyentuh file lain di luar yang disebut di atas.
#
# Cara pakai: jalankan dari folder project, lalu migrate.
#   .\apply-fix-testimoni-lacak-pesanan.ps1
#   php artisan migrate
# ============================================================

$ErrorActionPreference = "Stop"

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Rating + Sensor Nama (Lacak Pesanan) & Urutan Kartu Testimoni" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$migration = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saklar per-komentar: pembeli boleh minta namanya disamarkan saat
     * tampil sebagai testimoni publik (dicentang di form "Tambah Komentar"
     * pada halaman Lacak Pesanan, hanya untuk komentar pertama — lihat
     * TrackingController::storeComment & Testimonial::displayName()).
     * Nama ASLI di kolom `customer_name` tidak pernah diubah/ditimpa,
     * ini murni flag tampilan.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'is_name_masked')) {
                $table->boolean('is_name_masked')->default(false)->after('customer_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (Schema::hasColumn('testimonials', 'is_name_masked')) {
                $table->dropColumn('is_name_masked');
            }
        });
    }
};

'@

Write-Host "[1/6] Migration is_name_masked..." -ForegroundColor Yellow
$migrationPath = "database\migrations\2026_09_01_090000_add_name_masking_to_testimonials_table.php"
if (-not (Test-Path $migrationPath)) {
    Set-Content -Path $migrationPath -Value $migration -Encoding UTF8 -NoNewline
    Write-Host "      OK - migration dibuat." -ForegroundColor Green
} else {
    Write-Host "      Sudah ada, dilewati." -ForegroundColor Gray
}
Write-Host ""

$model = @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Testimoni pelanggan.
 *
 * Dua jalur mengisi tabel yang sama (TIDAK ada tabel testimoni terpisah):
 * - "Interaksi": komentar (untuk sekarang: dummy, nanti dari pembeli asli)
 *   masuk dengan approval_status = pending -> admin menyetujui (approved)
 *   atau menolak (rejected) lewat menu Interaksi.
 * - Menu "Testimoni": admin membuat testimoni langsung (nama, foto,
 *   jabatan, rating, urutan tampil), otomatis approval_status = approved.
 *
 * approval_status = keputusan moderasi (pending/approved/rejected).
 * is_active       = saklar tampil/sembunyikan terpisah, dipakai bersama
 *                    oleh kedua jalur di atas.
 * Frontend hanya menampilkan approval_status = approved DAN is_active = true.
 */
class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'transaction_id',
        'parent_id',
        'customer_name',
        'is_name_masked',
        'foto',
        'photos',
        'jabatan',
        'rating',
        'comment',
        'approval_status',
        'is_active',
        'is_featured_home',
        'is_read_admin',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_name_masked' => 'boolean',
            'is_featured_home' => 'boolean',
            'is_read_admin' => 'boolean',
            'rating' => 'integer',
            'urutan' => 'integer',
            'photos' => 'array',
        ];
    }

    /**
     * Nama yang ditampilkan di halaman publik. Kalau pembeli mencentang
     * "sembunyikan sebagian nama" waktu kirim komentar pertama (di halaman
     * Lacak Pesanan), tiap kata pada nama disamarkan jadi huruf pertama +
     * bintang, mis. "Ahmad Fauzi" -> "A**** F****". Admin (menu Interaksi/
     * Testimoni) tetap melihat nama asli lewat kolom `customer_name`
     * langsung — method ini hanya dipakai di view publik.
     */
    public function displayName(): string
    {
        if (! $this->is_name_masked) {
            return $this->customer_name;
        }

        $words = preg_split('/\s+/', trim($this->customer_name)) ?: [];

        return collect($words)
            ->filter(fn ($word) => $word !== '')
            ->map(fn ($word) => mb_substr($word, 0, 1).str_repeat('*', max(mb_strlen($word) - 1, 1)))
            ->implode(' ');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** Komentar aslinya, kalau baris ini adalah komentar Update. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Testimonial::class, 'parent_id');
    }

    /** Komentar Update dari komentar ini (kalau ada). Maksimal 1, dijaga di controller. */
    public function updateComment(): HasOne
    {
        return $this->hasOne(Testimonial::class, 'parent_id');
    }

    /** Komentar Update yang sudah disetujui & aktif, siap tampil publik di bawah komentar aslinya. */
    public function approvedUpdateComment(): HasOne
    {
        return $this->hasOne(Testimonial::class, 'parent_id')
            ->where('approval_status', 'approved')
            ->where('is_active', true);
    }

    /** Komentar yang masih menunggu keputusan admin. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', 'pending');
    }

    /** Komentar/testimoni yang sudah disetujui admin. */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    /** Komentar yang ditolak admin (tetap tersimpan, tidak tampil publik). */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('approval_status', 'rejected');
    }

    /** Saklar tampil/sembunyikan — terpisah dari keputusan moderasi. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Dipilih admin secara manual untuk tampil di section beranda (maks. 3). */
    public function scopeFeaturedHome(Builder $query): Builder
    {
        return $query->where('is_featured_home', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('urutan')->latest();
    }

    /** Komentar yang belum dibuka/dibaca admin — dasar hitungan badge notifikasi. */
    public function scopeUnreadAdmin(Builder $query): Builder
    {
        return $query->where('is_read_admin', false);
    }

    /** Komentar utama saja (BUKAN komentar Update susulan). Dipakai untuk list utama publik/admin. */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Khusus komentar Update susulan. */
    public function scopeUpdates(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function isUpdate(): bool
    {
        return $this->parent_id !== null;
    }
}

'@

Write-Host "[2/6] app/Models/Testimonial.php (tambah is_name_masked + displayName())..." -ForegroundColor Yellow
Set-Content -Path "app\Models\Testimonial.php" -Value $model -Encoding UTF8 -NoNewline
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

$controller = @'
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
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2048 KB = 2 MB
        ]);

        $comment = trim((string) ($validated['comment'] ?? ''));
        $rating = $validated['rating'] ?? null;
        $isNameMasked = $request->boolean('is_name_masked');
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

'@

Write-Host "[3/6] app/Http/Controllers/TrackingController.php (validasi rating & is_name_masked)..." -ForegroundColor Yellow
Set-Content -Path "app\Http\Controllers\TrackingController.php" -Value $controller -Encoding UTF8 -NoNewline
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

$trackingView = @'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lacak Pesanan {{ $transaction->order_code }} — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    @php
        $statusStyle = match ($transaction->status) {
            'pending', 'confirmed', 'preparing' => ['label' => 'Menunggu Antrean', 'pill' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
            'processing' => ['label' => 'Sedang Diproses', 'pill' => 'bg-violet-50 text-violet-600', 'dot' => 'bg-violet-500'],
            'completed' => ['label' => 'Selesai', 'pill' => 'bg-emerald-50 text-emerald-600', 'dot' => 'bg-emerald-500'],
            'cancelled' => ['label' => 'Dibatalkan', 'pill' => 'bg-red-50 text-red-600', 'dot' => 'bg-red-500'],
            default => ['label' => ucfirst($transaction->status), 'pill' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
        };

        $isActiveQueue = in_array($transaction->status, \App\Models\Transaction::ACTIVE_STATUSES, true);
        $addressParts = array_filter([
            $transaction->alamat_lengkap,
            $transaction->kecamatan ? 'Kec. '.$transaction->kecamatan : null,
            $transaction->kota,
            $transaction->provinsi,
            $transaction->kode_pos ? 'Kode Pos '.$transaction->kode_pos : null,
        ]);
    @endphp

    <section class="bg-[#F6F9F6]">
        <div class="mx-auto flex min-h-43.75 max-w-295 items-center justify-center px-5 py-12 sm:px-7 lg:px-8">
            <div class="flex flex-col items-center text-center">
                <h1 class="font-display text-3xl font-semibold tracking-tight text-[#171717] sm:text-4xl">Lacak Pesanan</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px] text-[#A29587]">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium text-[#F28A22]">Lacak Pesanan</span>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full px-5 py-12 sm:px-8 lg:px-12 xl:px-16">
        <div class="rounded-2xl border border-[#EFE7DC] bg-white p-6 shadow-sm sm:p-8">
            @unless ($isTrustedDevice)
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">
                    <div class="flex items-start gap-2.5">
                        <i class="fa-solid fa-shield-halved mt-0.5"></i>
                        <div>
                            <p class="font-semibold">Akses terbatas pada perangkat ini</p>
                            <p class="mt-1">Link pesanan ini terdaftar pada perangkat pemiliknya. Demi privasi, detail pribadi, alamat, WhatsApp, jumlah, total, dan catatan pesanan tidak ditampilkan di perangkat ini.</p>
                        </div>
                    </div>
                </div>
            @endunless

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Kode Pesanan</p>
                    <p class="mt-1 font-display text-xl font-semibold text-[#2A211B]">{{ $transaction->order_code }}</p>
                    <p class="mt-1 text-xs text-[#8A7C6E]">{{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusStyle['pill'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                    {{ $statusStyle['label'] }}
                </span>
            </div>

            @if ($isTrustedDevice && $isActiveQueue)
                <div class="mt-6 rounded-xl bg-[#F7F8F6] p-5 text-center">
                    <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Posisi Antrean Anda</p>
                    <p class="mt-1 font-display text-4xl font-semibold text-[#F28A22]">#{{ $transaction->queue_number }}</p>
                    <p class="mt-2 text-xs leading-relaxed text-[#8A7C6E]">
                        @if ($transaction->status === 'processing')
                            Pesanan Anda sedang dikerjakan sekarang.
                        @else
                            Mohon ditunggu, pesanan Anda akan diproses sesuai urutan antrean.
                        @endif
                    </p>
                </div>
            @endif

            <div class="mt-6 border-t border-[#EFE7DC] pt-6">
                <h2 class="font-display text-base font-semibold text-[#2A211B]">Detail Pesanan</h2>
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @if ($isTrustedDevice)
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Nama Pemesan</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">WhatsApp</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->whatsapp ?: '—' }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Produk</p>
                        <p class="mt-1 text-sm font-semibold">{{ $transaction->product?->nama ?? '—' }}</p>
                    </div>
                    @if ($isTrustedDevice)
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Jumlah</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->quantity }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Total</p>
                            <p class="mt-1 text-sm font-semibold">Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}</p>
                        </div>
                    @endif
                    @if ($isTrustedDevice && $transaction->catatan)
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-[#A29587]">Catatan Pesanan</p>
                            <p class="mt-1 text-sm font-semibold">{{ $transaction->catatan }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if ($isTrustedDevice)
            <div class="mt-6 border-t border-[#EFE7DC] pt-6">
                <h2 class="font-display text-base font-semibold text-[#2A211B]">Alamat Pengiriman</h2>
                <div class="mt-4 rounded-xl bg-[#F7F8F6] p-4 text-sm leading-relaxed text-[#5F554B]">
                    @if ($addressParts)
                        <p class="font-semibold text-[#2A211B]">{{ $transaction->nama_penerima ?: $transaction->customer_name }}</p>
                        <p class="mt-1">{{ implode(', ', $addressParts) }}</p>
                    @else
                        <p class="text-[#8A7C6E]">Alamat pengiriman belum tersedia pada pesanan ini.</p>
                    @endif
                </div>
            </div>
            @endif

            <div class="mt-6 flex flex-col gap-3 border-t border-[#EFE7DC] pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('tracking.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-[#E8DED1] px-5 py-2.5 text-sm font-semibold text-[#6E6257] transition-colors hover:bg-[#F7F8F6]">
                    <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                    Pesanan Saya di Perangkat Ini
                </a>
                <p class="text-xs leading-relaxed text-[#A29587] sm:max-w-sm sm:text-right">
                    Link ini terikat pada perangkat pertama yang membukanya. Gunakan perangkat yang sama untuk melihat detail lengkap pesanan.
                </p>
            </div>
        </div>

        @if ($isTrustedDevice && $transaction->status === 'completed')
            <div class="mt-6 rounded-2xl border border-[#EFE7DC] bg-white p-6 shadow-sm sm:p-8">
                <h2 class="font-display text-base font-semibold text-[#2A211B]">Komentar</h2>
                <p class="mt-1 text-xs leading-relaxed text-[#8A7C6E]">
                    Opsional — boleh diisi, boleh juga dilewati. Komentar akan ditinjau admin dulu sebelum tampil sebagai testimoni publik.
                </p>

                @if (session('comment_status') === 'sent')
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        <i class="fa-solid fa-circle-check mr-1.5"></i>
                        Terima kasih! Komentar Anda sudah terkirim dan sedang ditinjau admin.
                    </div>
                @elseif (session('comment_status') === 'update_sent')
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        <i class="fa-solid fa-circle-check mr-1.5"></i>
                        Terima kasih! Update komentar Anda sudah terkirim dan sedang ditinjau admin.
                    </div>
                @elseif (session('comment_status') === 'update_blocked')
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                        Update komentar untuk pesanan ini sudah tidak bisa dikirim lagi (sudah pernah update sebelumnya, atau sudah melewati {{ $updateWindowDays }} hari).
                    </div>
                @endif

                {{-- Komentar yang sudah pernah dikirim (kalau ada), ditampilkan sebagai histori. --}}
                @if ($originalComment)
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl border border-[#E8DED1] bg-[#F7F8F6] p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-[#A29587]">Komentar Anda</p>

                            @if ($originalComment->rating)
                                <div class="mt-1.5 flex items-center gap-0.5 text-[#F0A321]">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star text-xs {{ $i > $originalComment->rating ? 'text-[#E3DED7]' : '' }}"></i>
                                    @endfor
                                </div>
                            @endif

                            <p class="mt-1.5 text-sm leading-relaxed text-[#2A211B]">{{ $originalComment->comment }}</p>

                            @if (!empty($originalComment->photos))
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($originalComment->photos as $photo)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                            alt="Foto komentar"
                                            class="h-16 w-16 rounded-lg object-cover"
                                        >
                                    @endforeach
                                </div>
                            @endif

                            <p class="mt-2 text-[11px] text-[#A29587]">{{ $originalComment->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>

                        @if ($updateComment)
                            <div class="rounded-xl border border-[#F28A22]/30 bg-[#FFF7ED] p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-[#C46A1A]">
                                    <i class="fa-solid fa-arrow-turn-up mr-1"></i>Update Komentar
                                </p>

                                @if ($updateComment->rating)
                                    <div class="mt-1.5 flex items-center gap-0.5 text-[#F0A321]">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa-solid fa-star text-xs {{ $i > $updateComment->rating ? 'text-[#E3DED7]' : '' }}"></i>
                                        @endfor
                                    </div>
                                @endif

                                <p class="mt-1.5 text-sm leading-relaxed text-[#2A211B]">{{ $updateComment->comment }}</p>

                                @if (!empty($updateComment->photos))
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($updateComment->photos as $photo)
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                                alt="Foto update komentar"
                                                class="h-16 w-16 rounded-lg object-cover"
                                            >
                                        @endforeach
                                    </div>
                                @endif

                                <p class="mt-2 text-[11px] text-[#A29587]">{{ $updateComment->created_at->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Form: kalau belum pernah komentar sama sekali, ATAU sudah komentar
                     tapi masih boleh kirim Update (belum pernah update & masih dalam
                     jendela waktu). Endpoint sama; TrackingController yang menentukan
                     ini dianggap komentar pertama atau komentar Update. --}}
                @if (! $originalComment || $canSubmitUpdate)
                    <form
                        method="POST"
                        action="{{ route('tracking.comment', $transaction->tracking_token) }}"
                        enctype="multipart/form-data"
                        class="mt-4"
                    >
                        @csrf

                        @if ($originalComment)
                            <p class="mb-3 text-xs font-semibold text-[#8A7C6E]">
                                Produk berubah setelah beberapa hari? Kirim Update Komentar (hanya bisa sekali, dalam {{ $updateWindowDays }} hari sejak komentar pertama).
                            </p>
                        @endif

                        {{-- Rating bintang — opsional, dipilih pembeli dengan klik. Nilainya
                             dikirim lewat hidden input "rating" (1-5) dan dipakai sebagai
                             bintang testimoni publik (lihat Testimonial::rating). --}}
                        <div x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }" class="mb-3">
                            <label class="text-xs font-semibold text-[#8A7C6E]">
                                Beri Rating <span class="font-normal text-[#A29587]">(opsional)</span>
                            </label>
                            <div class="mt-1.5 flex items-center gap-1">
                                <input type="hidden" name="rating" :value="rating || ''">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button
                                        type="button"
                                        x-on:click="rating = (rating === {{ $i }} ? 0 : {{ $i }})"
                                        x-on:mouseenter="hover = {{ $i }}"
                                        x-on:mouseleave="hover = 0"
                                        class="p-0.5 text-xl leading-none transition-colors"
                                        :class="(hover || rating) >= {{ $i }} ? 'text-[#F0A321]' : 'text-[#E3DED7]'"
                                        aria-label="Beri {{ $i }} bintang"
                                    >
                                        <i class="fa-solid fa-star"></i>
                                    </button>
                                @endfor
                            </div>
                            @error('rating')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <textarea
                            name="comment"
                            rows="4"
                            maxlength="1000"
                            placeholder="Tulis komentar Anda tentang pesanan ini (opsional)..."
                            class="w-full rounded-xl border border-[#E8DED1] bg-[#F7F8F6] p-4 text-sm text-[#2A211B] placeholder:text-[#A29587] focus:border-[#F28A22] focus:outline-none focus:ring-2 focus:ring-[#F28A22]/20"
                        >{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        {{-- Sensor nama — hanya tampil di komentar PERTAMA, karena hanya
                             komentar pertama (topLevel) yang dijadikan kartu testimoni
                             publik. Lihat Testimonial::displayName(). --}}
                        @unless ($originalComment)
                            <label class="mt-3 flex items-start gap-2.5 text-xs text-[#5C5147]">
                                <input
                                    type="checkbox"
                                    name="is_name_masked"
                                    value="1"
                                    {{ old('is_name_masked') ? 'checked' : '' }}
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#E8DED1] text-[#F28A22] focus:ring-[#F28A22]/30"
                                >
                                <span>
                                    Samarkan sebagian nama saya saat tampil sebagai testimoni publik
                                    <span class="text-[#A29587]">(mis. "{{ \Illuminate\Support\Str::of($transaction->customer_name)->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1).str_repeat('*', max(\Illuminate\Support\Str::length($w) - 1, 1)))->implode(' ') }}")</span>
                                </span>
                            </label>
                        @endunless

                        <div class="mt-3">
                            <label class="text-xs font-semibold text-[#8A7C6E]">
                                Lampirkan Foto <span class="font-normal text-[#A29587]">(opsional, maks. {{ $maxPhotos }} foto, masing-masing maks. 2 MB)</span>
                            </label>
                            <input
                                type="file"
                                name="photos[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                class="mt-1.5 block w-full text-xs text-[#8A7C6E] file:mr-3 file:rounded-full file:border-0 file:bg-[#2A211B] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-[#403129]"
                            >
                            @error('photos')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="mt-4 inline-flex items-center justify-center gap-2 rounded-full bg-[#2A211B] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#403129]"
                        >
                            <i class="fa-regular fa-paper-plane text-xs"></i>
                            {{ $originalComment ? 'Kirim Update Komentar' : 'Kirim Komentar' }}
                        </button>
                    </form>
                @endif
            </div>
        @elseif ($isTrustedDevice)
            <div class="mt-6 rounded-2xl border border-dashed border-[#E8DED1] bg-[#FBF7F1] p-6 text-center sm:p-8">
                <i class="fa-regular fa-comment-dots mb-2 text-lg text-[#A29587]"></i>
                <p class="text-sm font-semibold text-[#2A211B]">Komentar belum bisa dikirim</p>
                <p class="mt-1 text-xs leading-relaxed text-[#8A7C6E]">
                    Kolom komentar baru terbuka setelah pesanan ini berstatus <span class="font-semibold">Selesai</span>.
                </p>
            </div>
        @endif
    </section>

    @include('partials.frontend.footer')
</body>
</html>

'@

Write-Host "[4/6] resources/views/pages/frontend/tracking.blade.php (star picker + checkbox sensor nama)..." -ForegroundColor Yellow
Set-Content -Path "resources\views\pages\frontend\tracking.blade.php" -Value $trackingView -Encoding UTF8 -NoNewline
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

$testimoniView = @'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Testimoni — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    {{-- =====================================================
         HERO / BREADCRUMB — pola sama persis dengan hero Shop
         (produk-index.blade.php), cuma judul & breadcrumb-nya
         diganti "Testimoni" supaya konsisten satu situs.
    ====================================================== --}}
    <section class="bg-[#F6F9F6]">
        <div class="mx-auto flex min-h-43.75 max-w-295 items-center justify-center px-5 py-12 sm:px-7 lg:px-8">
            <div class="flex flex-col items-center text-center">
                <h1 class="font-display text-4xl font-semibold tracking-tight text-[#171717] sm:text-5xl">Testimoni</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px] text-[#A29587]">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium text-[#F28A22]">Testimoni</span>
                </div>
            </div>
        </div>
    </section>

    {{-- =====================================================
         TOP 3 TESTIMONI UNGGULAN — section yang SAMA PERSIS
         dengan yang ada di Beranda (partial yang sama, bukan
         duplikat logic): 3 testimoni approved+active yang
         DIPILIH ADMIN lewat is_featured_home. Kalau admin ganti
         pilihannya di menu Testimoni, otomatis ikut berubah di
         sini juga.
    ====================================================== --}}
    @include('partials.frontend.testimonials')

    {{-- =====================================================
         LIST TESTIMONI — hanya yang approval_status = approved
         DAN is_active = true. Ditampilkan apa adanya (nama,
         rating, komentar) tanpa keterangan tambahan bahwa ini
         komentar "pilihan admin" — murni daftar komentar.
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto max-w-295 px-5 py-10 sm:px-7 sm:py-12 lg:px-8 lg:py-14">

            <div class="flex flex-wrap items-center justify-between gap-3">
                @if ($testimonials->total() > 0)
                    <p class="text-[11px] text-[#75685B]">
                        Menampilkan {{ $testimonials->firstItem() }}–{{ $testimonials->lastItem() }} dari {{ $testimonials->total() }} Testimoni
                    </p>
                @else
                    <span></span>
                @endif

                {{-- Tampilan saja, belum difungsikan — mengikuti konvensi yang sudah
                     dipakai di navbar (search bar, dropdown kategori, dll juga
                     sengaja belum difungsikan). --}}
                <label class="flex items-center gap-2 rounded-md border border-[#E7D9C8] px-3 py-1.5 text-[11px] text-[#5C5147]">
                    Sort by:
                    <select class="bg-transparent pr-1 font-medium text-[#2A211B] focus:outline-none">
                        <option>Popularity</option>
                        <option>Terbaru</option>
                        <option>Rating Tertinggi</option>
                    </select>
                </label>
            </div>

            @if ($testimonials->isEmpty())
                <div class="mt-8 flex flex-col items-center justify-center rounded-3xl border border-dashed border-[#E7D9C8] bg-[#FBF7F1] px-6 py-20 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white">
                        <i class="fa-solid fa-quote-left text-xl text-[#F28A22]"></i>
                    </span>
                    <p class="mt-5 text-sm font-semibold text-[#2A211B]">Belum ada testimoni</p>
                    <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-[#75685B]">
                        Testimoni dari pelanggan akan tampil di sini begitu tersedia.
                    </p>
                </div>
            @else
                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <div class="flex flex-col rounded-2xl border border-[#F0ECE7] bg-white p-6 shadow-sm">

                            {{-- 1. Nama pembeli (foto profil admin/rating menyertai baris yang sama) --}}
                            <div class="flex items-center gap-3">
                                @if ($testimonial->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($testimonial->foto))
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($testimonial->foto) }}"
                                        alt="{{ $testimonial->displayName() }}"
                                        class="h-10 w-10 shrink-0 rounded-full object-cover"
                                    >
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F7F1E8] text-xs font-semibold text-[#F28A22]">
                                        {{ strtoupper(substr($testimonial->displayName(), 0, 1)) }}
                                    </span>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-semibold text-[#2A211B] sm:text-xs">
                                        {{ $testimonial->displayName() }}
                                    </p>
                                    @if ($testimonial->jabatan)
                                        <p class="truncate text-[10px] text-[#A1988E]">
                                            {{ $testimonial->jabatan }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center gap-0.5 text-[#F0A321]">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star text-[11px] {{ $i > ($testimonial->rating ?? 0) ? 'text-[#E3DED7]' : '' }}"></i>
                                    @endfor
                                </div>
                            </div>

                            {{-- 2. Produk yang dibeli --}}
                            @if ($testimonial->product)
                                <div class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-[#F7F1E8] px-3 py-1 text-[10px] font-medium text-[#8A6D3B]">
                                    <i class="fa-solid fa-box text-[9px]"></i>
                                    {{ $testimonial->product->nama }}
                                </div>
                            @endif

                            {{-- 3. Foto yang dikirim pembeli (kalau ada) — kalau tidak ada, langsung ke komentar --}}
                            @if (! empty($testimonial->photos))
                                <div class="mt-3 flex gap-2 overflow-x-auto">
                                    @foreach ($testimonial->photos as $photo)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                            alt="Foto dari {{ $testimonial->displayName() }}"
                                            class="h-16 w-16 shrink-0 rounded-lg object-cover"
                                        >
                                    @endforeach
                                </div>
                            @endif

                            {{-- 4. Komentar --}}
                            <p class="mt-3 flex-1 text-[11px] leading-relaxed text-[#5C5147] sm:text-xs">
                                {{ $testimonial->comment }}
                            </p>
                        </div>
                    @endforeach
                </div>

                @if ($testimonials->hasPages())
                    <div class="mt-12 border-t border-[#E7D9C8] pt-6">
                        {{ $testimonials->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
'@

Write-Host "[5/6] resources/views/pages/frontend/testimoni.blade.php (urutan kartu baru)..." -ForegroundColor Yellow
Set-Content -Path "resources\views\pages\frontend\testimoni.blade.php" -Value $testimoniView -Encoding UTF8 -NoNewline
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

$testimonialsPartial = @'
{{--
    ==========================================================
    ULASAN PELANGGAN KAMI — Homepage Karya Ide Edi
    ==========================================================
    Menampilkan MAKSIMAL 3 komentar yang DIPILIH SENDIRI oleh admin
    (is_featured_home = true) lewat menu "Testimoni" — bukan otomatis
    yang terbaru. Tetap disaring approval_status = 'approved' DAN
    is_active = true (jaga-jaga kalau ada testimoni yang ditarik/
    dinonaktifkan setelah dipilih tampil di beranda).

    Tabel yang sama diisi dari 2 jalur: komentar (untuk sekarang:
    dummy — lihat TestimonialSeeder) yang disetujui admin lewat
    menu "Interaksi", ATAU testimoni yang dibuat langsung admin
    lewat menu "Testimoni" (approval_status otomatis 'approved').
    Section ini tidak peduli asalnya, cukup baca yang dipilih admin
    untuk tampil. Rating bintang dibaca dari kolom `rating` — tidak
    ada yang di-hardcode 5 bintang.

    Semua warna pakai token admin-* (ikut tema Glow/Dark yang
    sudah ada), TIDAK ada warna hardcode.

    Pemakaian:
        @include('partials.frontend.testimonials')
    ==========================================================
--}}

@php
    $testimonialList = \App\Models\Testimonial::query()
        ->approved()
        ->active()
        ->featuredHome()
        ->with('product:id,nama')
        ->orderBy('urutan')
        ->latest()
        ->take(3)
        ->get();
@endphp

<section class="bg-admin-canvas">
    <div class="mx-auto max-w-7xl px-6 py-14 sm:px-8 lg:px-10 lg:py-20">

        <h2 class="font-display text-2xl text-admin-ink sm:text-3xl">Ulasan Pelanggan Kami</h2>

        @if ($testimonialList->isEmpty())
            {{-- Empty state — tetap rapi selagi belum ada testimoni --}}
            <div class="mt-10 flex flex-col items-center justify-center rounded-3xl border border-dashed border-admin-border bg-admin-surface px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-admin-cream">
                    <i class="fa-solid fa-quote-left text-xl text-admin-accent"></i>
                </span>
                <p class="mt-5 text-sm font-semibold text-admin-ink">Belum ada ulasan</p>
                <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    Testimoni dari pelanggan akan tampil di sini begitu tersedia.
                </p>
            </div>
        @else
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($testimonialList as $index => $testimonial)
                    @php $isDark = $index % 3 !== 1; @endphp
                    <div
                        x-data="{ shown: false }"
                        x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { setTimeout(() => shown = true, {{ ($index % 3) * 100 }}); } }, { threshold: 0.15 }).observe($el)"
                        :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="flex flex-col rounded-3xl p-6 shadow-sm transition-all duration-500 ease-out hover:-translate-y-1.5 hover:shadow-xl
                            {{ $isDark ? 'bg-admin-panel text-white' : 'bg-admin-cream text-admin-ink' }}"
                    >
                        {{-- 1. Nama pembeli (+ foto profil & rating di baris yang sama) --}}
                        <div class="flex items-center gap-3">
                            @if ($testimonial->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($testimonial->foto))
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($testimonial->foto) }}"
                                    alt="{{ $testimonial->displayName() }}"
                                    class="h-11 w-11 shrink-0 rounded-full object-cover"
                                >
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                                    {{ $isDark ? 'bg-white/10 text-white' : 'bg-white text-admin-accent' }}">
                                    {{ strtoupper(substr($testimonial->displayName(), 0, 1)) }}
                                </span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold {{ $isDark ? 'text-white' : 'text-admin-ink' }}">
                                    {{ $testimonial->displayName() }}
                                </p>
                                @if ($testimonial->jabatan)
                                    <p class="truncate text-[11px] uppercase tracking-wide {{ $isDark ? 'text-white/50' : 'text-admin-ink-soft' }}">
                                        {{ $testimonial->jabatan }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-0.5 text-admin-gold">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-solid fa-star text-[11px] {{ $i > ($testimonial->rating ?? 0) ? ($isDark ? 'text-white/20' : 'text-admin-border') : '' }}"></i>
                                @endfor
                            </div>
                        </div>

                        {{-- 2. Produk yang dibeli --}}
                        @if ($testimonial->product)
                            <div class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-medium
                                {{ $isDark ? 'bg-white/10 text-white/70' : 'bg-white text-admin-ink-soft' }}">
                                <i class="fa-solid fa-box text-[9px]"></i>
                                {{ $testimonial->product->nama }}
                            </div>
                        @endif

                        {{-- 3. Foto yang dikirim pembeli (kalau ada) — kalau tidak ada, langsung ke komentar --}}
                        @if (! empty($testimonial->photos))
                            <div class="mt-3 flex gap-2 overflow-x-auto">
                                @foreach ($testimonial->photos as $photo)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                        alt="Foto dari {{ $testimonial->displayName() }}"
                                        class="h-14 w-14 shrink-0 rounded-lg object-cover"
                                    >
                                @endforeach
                            </div>
                        @endif

                        {{-- 4. Komentar --}}
                        <div class="mt-4 flex-1">
                            <i class="fa-solid fa-quote-left text-lg {{ $isDark ? 'text-white/40' : 'text-admin-accent/50' }}"></i>
                            <p class="mt-2 text-sm leading-relaxed {{ $isDark ? 'text-white/85' : 'text-admin-ink-soft' }}">
                                "{{ $testimonial->comment }}"
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

'@

Write-Host "[6/6] resources/views/partials/frontend/testimonials.blade.php (urutan kartu baru, Beranda)..." -ForegroundColor Yellow
Set-Content -Path "resources\views\partials\frontend\testimonials.blade.php" -Value $testimonialsPartial -Encoding UTF8 -NoNewline
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

Write-Host "[+] routes/web.php: eager-load relasi produk di query testimoni publik..." -ForegroundColor Yellow
$routesPath = "routes\web.php"
$routesContent = Get-Content -Path $routesPath -Raw
$search = "->with('approvedUpdateComment')"
$replace = "->with(['approvedUpdateComment', 'product:id,nama'])"
if ($routesContent -like "*$search*") {
    $routesContent = $routesContent.Replace($search, $replace)
    Set-Content -Path $routesPath -Value $routesContent -Encoding UTF8 -NoNewline
    Write-Host "      OK - baris eager-load diganti." -ForegroundColor Green
} elseif ($routesContent -like "*product:id,nama*") {
    Write-Host "      Sudah ter-update sebelumnya, dilewati." -ForegroundColor Gray
} else {
    Write-Host "      [WARNING] Baris yang dicari tidak ketemu di routes/web.php -- cek manual query testimoni publik (route testimonials.index), tambahkan ->with('product:id,nama') secara manual." -ForegroundColor Yellow
}
Write-Host ""

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host "Langkah selanjutnya:" -ForegroundColor White
Write-Host "  1. php artisan migrate" -ForegroundColor White
Write-Host "  2. Cek halaman Lacak Pesanan (pesanan berstatus Selesai) -> coba isi rating & centang sensor nama." -ForegroundColor White
Write-Host "  3. Cek Beranda & /testimoni -> urutan kartu: Nama -> Produk -> Foto Pembeli (kalau ada) -> Komentar." -ForegroundColor White
