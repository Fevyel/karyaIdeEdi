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

    /**
     * Status yang masih dihitung sebagai bagian dari ANTREAN AKTIF.
     * 'processing' khusus dipakai utk pesanan yang sedang di posisi
     * antrean #1 (lihat promoteQueueFront()) — status lain di sini
     * ('pending', 'confirmed', 'preparing') berarti masih menunggu giliran.
     */
    public const ACTIVE_STATUSES = ['pending', 'confirmed', 'processing', 'preparing'];

    /** Status akhir — pesanan sudah keluar dari antrean aktif secara permanen. */
    public const FINAL_STATUSES = ['completed', 'cancelled'];

    /**
     * Tipe pesanan: 'tetap' pakai harga produk dari database seperti biasa,
     * 'custom' butuh ukuran & harga hasil negosiasi manual dengan customer
     * (lihat migration 2026_08_26_150000_add_order_type_and_custom_fields_to_transactions).
     */
    public const ORDER_TYPES = ['tetap', 'custom'];

    protected $fillable = [
        'product_id',
        'order_type',
        'custom_tinggi',
        'custom_lebar',
        'custom_panjang',
        'custom_harga_satuan',
        'order_code',
        'customer_name',
        'whatsapp',
        'nama_penerima',
        'alamat_lengkap',
        'kecamatan',
        'kota',
        'provinsi',
        'kode_pos',
        'quantity',
        'catatan',
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
            'custom_tinggi' => 'decimal:2',
            'custom_lebar' => 'decimal:2',
            'custom_panjang' => 'decimal:2',
            'custom_harga_satuan' => 'decimal:2',
            'is_read_admin' => 'boolean',
            'queue_number' => 'integer',
            'queue_date' => 'date',
        ];
    }

    /**
     * Isi otomatis order_code, queue_date, queue_number, dan tracking_token
     * (acak aman) begitu pesanan dibuat — kalau belum diisi manual.
     *
     * Format order_code: KIE-{YYYYMMDD}-{NNNN}, mis. "KIE-20260824-0007".
     * YYYYMMDD di sini HANYA label tanggal dibuatnya pesanan (informasi),
     * BUKAN penentu nilai NNNN. NNNN adalah COUNTER GLOBAL (lihat
     * nextOrderCode()) — lanjut terus lintas hari, TIDAK reset ke 0001
     * setiap ganti tanggal. order_code SELALU permanen: tidak pernah
     * diubah lagi setelah dibuat, apa pun yang terjadi pada queue_number
     * (lihat compactQueue()/promoteQueueFront() — keduanya tidak pernah
     * menyentuh order_code).
     */
    protected static function booted(): void
    {
        static::creating(function (self $transaction) {
            $queueDate = self::normalizeDateInput($transaction->queue_date) ?? now()->toDateString();
            $transaction->queue_date = $queueDate;

            if (! $transaction->queue_number) {
                // PERBAIKAN NOMOR ANTREAN: queue_number SEKARANG GLOBAL —
                // posisi di antrean aktif lintas semua tanggal, BUKAN reset
                // per queue_date lagi. Sebelumnya discope ->whereDate('queue_date', ...),
                // sehingga pesanan aktif dari tanggal berbeda bisa sama-sama
                // dapat #1 (dua "antrean depan" berjalan bersamaan). queue_date
                // tetap disimpan & tetap dipakai untuk order_code (lihat
                // nextOrderCode() di bawah) — hanya PENOMORAN ANTREAN yang
                // sekarang lepas dari tanggal.
                //
                // lockForUpdate() di sini efektif karena create() pesanan selalu
                // dipanggil dari dalam DB::transaction() (lihat pesanan.blade.php
                // save()) — mengunci baris-baris status aktif supaya dua admin
                // tidak bisa mendapat nomor antrean yang sama sekaligus.
                $lastNumber = static::query()
                    ->whereIn('status', self::ACTIVE_STATUSES)
                    ->lockForUpdate()
                    ->max('queue_number');

                $transaction->queue_number = ($lastNumber ?? 0) + 1;
            }

            if (! $transaction->order_code) {
                $transaction->order_code = static::nextOrderCode($queueDate);
            }

            if (! $transaction->tracking_token) {
                do {
                    $token = Str::random(48);
                } while (static::query()->where('tracking_token', $token)->exists());

                $transaction->tracking_token = $token;
            }
        });
    }

    /**
     * Generator NNNN pada order_code — COUNTER GLOBAL (bukan per-tanggal,
     * bukan count()).
     *
     * PERBAIKAN KODE PESANAN (root cause lama): sebelumnya sequence dihitung
     * dari `MAX(order_code)` yang di-filter LIKE 'KIE-{tanggal ini}-%' — jadi
     * begitu tanggal berganti, prefix LIKE-nya ikut berubah, tidak ada baris
     * lama yang cocok, MAX() jadi null, dan NNNN diam-diam balik ke 0001
     * setiap hari (persis gejala di laporan: "0001" muncul lagi tiap tanggal
     * baru). Sekarang sequence-nya TIDAK bergantung pada tanggal sama sekali.
     *
     * Caranya: ambil order_code milik baris TERAKHIR DIBUAT (ORDER BY id
     * DESC, bukan MAX(order_code) — MAX angka tidak valid lagi begitu ada
     * wrap-around, karena "0002" > "9999" secara numerik itu salah arah),
     * ambil 4 digit terakhirnya sebagai sequence terakhir, lalu +1. Kalau
     * sequence terakhir sudah 9999, lanjut ke 1 (wrap-around) — BUKAN ke
     * 10000, supaya format tetap 4 digit selamanya.
     *
     * lockForUpdate() pada SELECT baris terakhir (ORDER BY id DESC LIMIT 1)
     * efektif untuk mengunci pembuatan pesanan concurrent: InnoDB menahan
     * gap/next-key lock di ujung index sampai transaction ini commit/
     * rollback, jadi admin kedua yang mencoba insert pesanan baru hampir
     * bersamaan otomatis menunggu (sama seperti mekanisme lockForUpdate()
     * pada queue_number di booted() — sudah dipakai & diandalkan di tempat
     * lain pada model ini).
     *
     * Guard while-loop di bawah adalah jaring pengaman WAJIB (bukan
     * opsional) untuk kasus wrap-around: order_code lama TIDAK PERNAH
     * dihapus/di-null-kan (permanen — lihat compactQueue()), jadi begitu
     * sequence balik ke 1, kita harus pastikan dulu "KIE-{tanggal ini}-0001"
     * belum pernah dipakai sebelum benar-benar memakainya; kalau sudah
     * pernah (kebetulan tanggal yang sama dipakai lagi setelah wrap penuh),
     * naik terus ke sequence berikutnya sampai benar-benar unik.
     */
    private static function nextOrderCode(string $queueDate): string
    {
        $lastOrderCode = static::query()
            ->latest('id')
            ->lockForUpdate()
            ->value('order_code');

        $lastSequence = $lastOrderCode ? static::parseOrderCodeSequence($lastOrderCode) : 0;

        $sequence = $lastSequence >= 9999 ? 1 : $lastSequence + 1;
        $orderCode = static::generateOrderCode($queueDate, $sequence);

        while (static::query()->where('order_code', $orderCode)->exists()) {
            $sequence = $sequence >= 9999 ? 1 : $sequence + 1;
            $orderCode = static::generateOrderCode($queueDate, $sequence);
        }

        return $orderCode;
    }

    /** Ambil 4 digit NNNN terakhir dari sebuah order_code berformat KIE-YYYYMMDD-NNNN. */
    private static function parseOrderCodeSequence(string $orderCode): int
    {
        return (int) substr($orderCode, -4);
    }

    /**
     * Terima queue_date dalam bentuk apa pun (string 'Y-m-d', DateTimeInterface,
     * atau null) dan selalu kembalikan string tanggal polos 'Y-m-d' — supaya
     * tidak ada satu pun tempat lain yang tanpa sengaja ikut membaca ulang
     * attribute ber-cast dan kena bug format datetime yang sama.
     */
    public static function normalizeDateInput(mixed $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : (string) $date;
    }

    /**
     * Formatter murni: gabungkan tanggal + sequence jadi string order_code.
     * Format: KIE-YYYYMMDD-0001..9999. TIDAK melakukan query apa pun di
     * sini — logic penentuan sequence (global, wrap-around, cek bentrok)
     * sepenuhnya ada di nextOrderCode().
     *
     * WRAP-AROUND 9999 -> 0001: karena sequence sekarang GLOBAL (bukan
     * reset per tanggal), angka 4 digit ini bisa habis kalau total pesanan
     * sepanjang sejarah toko sudah melewati 9999. Begitu itu terjadi,
     * nextOrderCode() sengaja memutar sequence balik ke 1 — dan SELALU
     * memverifikasi dulu order_code hasilnya belum pernah dipakai sebelum
     * dipakai ulang (order_code lama tidak pernah dihapus, jadi tabrakan
     * betulan mungkin terjadi setelah wrap penuh). Kalau volume pesanan
     * suatu saat memang melewati 9999 dalam siklus wrap yang sama, solusi
     * amannya adalah menambah lebar digit (mis. jadi 5 digit) lewat
     * migration baru — BUKAN menghapus unique constraint order_code,
     * supaya kode lama & histori tetap terjamin tidak pernah tertukar.
     */
    public static function generateOrderCode(string $queueDate, int $queueNumber): string
    {
        return sprintf('KIE-%s-%04d', str_replace('-', '', $queueDate), $queueNumber);
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

    /** Pesanan yang masih berada di antrean aktif (belum completed/cancelled). */
    public function scopeActiveQueue(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Rapatkan queue_number SEMUA pesanan AKTIF (GLOBAL, lintas queue_date),
     * supaya tidak ada celah nomor setelah satu pesanan selesai/dibatalkan
     * (mis. #1,#3,#4 -> #1,#2,#3). Urutan relatif antar pesanan
     * dipertahankan (diurutkan dari queue_number lama, dengan created_at
     * & id sebagai tie-breaker stabil), yang berubah HANYA nilai
     * queue_number-nya — order_code, queue_date, & tracking_token setiap
     * pesanan tidak pernah disentuh di sini.
     *
     * PERBAIKAN NOMOR ANTREAN (root cause lama): sebelumnya method ini
     * hanya merapatkan antrean PER queue_date (parameter $queueDate),
     * padahal queue_number sekarang memang harus jadi SATU antrean global.
     * Kalau ada pesanan aktif yang menumpuk lebih dari satu hari (mis. sisa
     * kemarin belum selesai + pesanan baru hari ini), versi lama membuat
     * dua "antrean depan" berjalan sendiri-sendiri per tanggal (bisa
     * dua-duanya #1 sekaligus). Sekarang tidak ada lagi parameter tanggal —
     * SEMUA pesanan berstatus final di tanggal manapun yang masih memegang
     * queue_number dilepas, dan SEMUA pesanan aktif di tanggal manapun
     * dirapatkan jadi satu urutan #1..#N.
     *
     * Dikerjakan 2 FASE (BUG FIX lama, tetap dipertahankan): kolom
     * queue_number ada dalam unique constraint gabungan (queue_date,
     * queue_number) yang dicek MySQL di SETIAP statement UPDATE (bukan
     * ditunda sampai commit). Kalau renumber dilakukan langsung ke angka
     * final satu-per-satu, ada risiko nyata dua baris sempat "berebut"
     * angka yang sama di tengah proses -> Integrity constraint violation
     * (1062 Duplicate entry). Makanya: fase 1 geser SEMUA baris aktif ke
     * rentang sementara yang mustahil bentrok (+100000), fase 2 baru set
     * ke angka final 1..N — di titik manapun proses berhenti, tidak akan
     * pernah ada dua baris memegang angka yang sama.
     */
    public static function compactQueue(): void
    {
        // Pesanan yang sudah final (completed/cancelled) tapi masih
        // memegang queue_number (baik baru saja diselesaikan/batal, maupun
        // sisa data lama) akan SELAMANYA mengunci slot nomor itu kalau
        // tidak dilepas — dicek di SEMUA tanggal (GLOBAL), bukan cuma satu
        // queue_date lagi. Lepas dulu (set NULL) semua queue_number milik
        // pesanan final; MySQL tidak menganggap banyak NULL sebagai
        // duplikat, jadi ini aman & membersihkan sisa data lama secara
        // otomatis tanpa perlu migration/edit manual.
        static::query()
            ->whereIn('status', self::FINAL_STATUSES)
            ->whereNotNull('queue_number')
            ->update(['queue_number' => null]);

        $activeTransactions = static::query()
            ->activeQueue()
            ->orderBy('queue_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        // Fase 1: rentang sementara aman dari tabrakan unique constraint.
        foreach ($activeTransactions as $index => $transaction) {
            $transaction->forceFill(['queue_number' => 100000 + $index])->save();
        }

        // Fase 2: angka final sesuai urutan antrean (global, #1..#N).
        foreach ($activeTransactions as $index => $transaction) {
            $transaction->forceFill(['queue_number' => $index + 1])->save();
        }
    }

    /**
     * Pastikan pesanan di posisi terdepan antrean GLOBAL (queue_number
     * terkecil, di antara SEMUA yang masih aktif, lintas queue_date)
     * berstatus 'processing'. Dipanggil setiap kali antrean berubah:
     * pesanan baru dibuat, pesanan selesai, atau pesanan dibatalkan.
     *
     * PERBAIKAN NOMOR ANTREAN: sebelumnya di-scope per queue_date
     * (parameter $queueDate) — root cause dua pesanan dari tanggal
     * berbeda bisa sama-sama jadi 'processing' (#1) di waktu yang sama.
     * Sekarang cuma ada SATU pesanan terdepan yang boleh 'processing'
     * dalam seluruh antrean aktif, tidak peduli tanggalnya.
     */
    public static function promoteQueueFront(): void
    {
        $front = static::query()
            ->activeQueue()
            ->orderBy('queue_number')
            ->lockForUpdate()
            ->first();

        if ($front && $front->status !== 'processing') {
            $front->forceFill(['status' => 'processing'])->save();
        }
    }
}