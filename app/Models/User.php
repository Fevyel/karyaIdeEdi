<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $foto_profil
 * @property string|null $nama_toko
 * @property string $email
 * @property string|null $whatsapp
 * @property string|null $alamat
 * @property string|null $bio
 * @property string $theme
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'foto_profil', 'nama_toko', 'email', 'whatsapp', 'alamat', 'bio', 'theme', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dashboard_read_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * URL publik foto profil, atau null kalau belum ada foto.
     */
    public function fotoProfilUrl(): ?string
    {
        return $this->foto_profil
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->foto_profil)
            : null;
    }

    // ==================================================================
    // NOTIFIKASI ADMIN (badge sidebar & ikon admin di frontend)
    // ==================================================================
    // Tidak ada tabel notifikasi terpisah.
    //
    // Interaksi & Pesanan: dihitung dari flag `is_read_admin` LANGSUNG
    // di baris Testimonial/Transaction masing-masing (persis seperti
    // status baca di inbox TikTok) — BUKAN dari perbandingan tanggal.
    // Baris baru selalu is_read_admin = false; membuka halaman terkait
    // menandai semua baris false saat itu menjadi true. Kalau ada baris
    // baru masuk SETELAH itu (termasuk selagi admin masih di halaman
    // yang sama), baris itu tetap is_read_admin = false sampai halaman
    // dibuka ulang — sehingga badge bisa naik lagi tanpa admin pindah
    // halaman, sesuai perilaku inbox yang diinginkan.
    //
    // Dashboard: TETAP pakai perbandingan timestamp (dashboard_read_at)
    // karena "peringatan stok menipis" adalah kondisi/state produk,
    // bukan baris baru yang dibuat — jadi tidak punya flag is_read
    // sendiri untuk ditandai.

    private const LOW_STOCK_THRESHOLD = 5;

    /** Komentar/interaksi yang belum dibuka admin. */
    public function unreadInteraksiCount(): int
    {
        return \App\Models\Testimonial::query()->unreadAdmin()->count();
    }

    /** Pesanan yang belum dibuka admin. */
    public function unreadPesananCount(): int
    {
        return \App\Models\Transaction::query()->unreadAdmin()->count();
    }

    /** Produk yang baru menipis stoknya sejak terakhir dibuka Dashboard. */
    public function unreadDashboardCount(): int
    {
        return \App\Models\Product::query()
            ->where('stok', '<=', self::LOW_STOCK_THRESHOLD)
            ->where('updated_at', '>', $this->dashboard_read_at ?? '1970-01-01')
            ->count();
    }

    /** Total gabungan — dipakai badge ikon admin di navbar frontend (selalu angka asli, tidak pernah 99+). */
    public function unreadNotificationsCount(): int
    {
        return $this->unreadInteraksiCount() + $this->unreadPesananCount() + $this->unreadDashboardCount();
    }

    /** Tandai semua komentar yang belum dibaca sebagai sudah dibaca (dipanggil saat membuka menu Interaksi). */
    public function markInteraksiRead(): void
    {
        \App\Models\Testimonial::query()->unreadAdmin()->update(['is_read_admin' => true]);
    }

    /** Tandai semua pesanan yang belum dibaca sebagai sudah dibaca (dipanggil saat membuka menu Pesanan). */
    public function markPesananRead(): void
    {
        \App\Models\Transaction::query()->unreadAdmin()->update(['is_read_admin' => true]);
    }

    /** Tandai kategori "dashboard" (peringatan stok) sebagai sudah dibaca (dipanggil saat membuka Dashboard). */
    public function markDashboardRead(): void
    {
        $this->forceFill(['dashboard_read_at' => now()])->save();
    }

    /** Tandai SEMUA kategori sebagai sudah dibaca sekaligus (dipakai saat panel notifikasi ikon admin dibuka). */
    public function markAllNotificationsRead(): void
    {
        $this->markInteraksiRead();
        $this->markPesananRead();
        $this->markDashboardRead();
    }

    /**
     * Format badge SIDEBAR admin: 0 -> null (disembunyikan), 1-99 -> angka
     * asli, 100+ -> "99+". HANYA sidebar yang memakai pembulatan ini.
     */
    public static function formatSidebarBadge(int $count): ?string
    {
        return match (true) {
            $count <= 0 => null,
            $count > 99 => '99+',
            default => (string) $count,
        };
    }
}