<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Nilai valid kolom `status` (lihat migration
     * 2026_08_18_090000_add_queue_and_tracking_fields_to_transactions_table).
     */
    public const STATUSES = ['pending', 'confirmed', 'processing', 'preparing', 'completed', 'cancelled'];

    protected $fillable = [
        'product_id',
        'order_code',
        'customer_name',
        'quantity',
        'total',
        'status',
        'is_read_admin',
        'queue_number',
        'queue_date',
        'tracking_token',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'quantity' => 'integer',
            'is_read_admin' => 'boolean',
            'queue_number' => 'integer',
            'queue_date' => 'date',
        ];
    }

    /**
     * Isi otomatis queue_date, queue_number (reset per hari), dan
     * tracking_token (acak aman) begitu pesanan dibuat — kalau
     * belum diisi manual. Tidak ada UI di tahap ini; ini murni
     * supaya field baru "bisa digunakan" begitu form admin dibuat nanti.
     */
    protected static function booted(): void
    {
        static::creating(function (self $transaction) {
            $transaction->queue_date ??= now()->toDateString();

            if (! $transaction->queue_number) {
                $lastNumber = static::query()
                    ->where('queue_date', $transaction->queue_date)
                    ->max('queue_number');

                $transaction->queue_number = ($lastNumber ?? 0) + 1;
            }

            if (! $transaction->tracking_token) {
                do {
                    $token = Str::random(48);
                } while (static::query()->where('tracking_token', $token)->exists());

                $transaction->tracking_token = $token;
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Pesanan yang belum dibuka/dibaca admin — dasar hitungan badge notifikasi. */
    public function scopeUnreadAdmin(Builder $query): Builder
    {
        return $query->where('is_read_admin', false);
    }
}