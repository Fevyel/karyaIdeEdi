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
            'is_featured_home' => 'boolean',
            'is_read_admin' => 'boolean',
            'rating' => 'integer',
            'urutan' => 'integer',
            'photos' => 'array',
        ];
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
