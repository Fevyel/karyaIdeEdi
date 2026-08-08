<?php

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * DUA FUNGSI TERPISAH dalam satu komponen (dibungkus 1 <div> root supaya
 * tidak memicu MultipleRootElementsDetectedException), TAPI TIDAK SALING
 * MENGGANTIKAN:
 *
 * A. Admin Access — ikon profil (fa-user-shield) di navbar frontend.
 *    Klik -> langsung buka Dashboard Admin (atau halaman Login kalau
 *    somehow belum ter-auth). Ini FUNGSI UTAMA ikon tersebut, sudah ada
 *    sejak awal, TIDAK BOLEH diganti jadi ikon lonceng atau kehilangan
 *    href-nya.
 *
 * B. Notification Toast — floating kecil ala TikTok, elemen TERPISAH
 *    yang muncul otomatis di dekat ikon admin saat ada notifikasi baru,
 *    ~10 detik lalu slide-down + fade-out sendiri. TIDAK ada Notification
 *    Panel/dropdown. Toast tidak membuka Dashboard maupun apa pun -- ia
 *    cuma tampil lalu hilang.
 *
 * Badge merah kecil di pojok ikon admin = indikator jumlah unread (opsional,
 * murni visual), TIDAK mengubah href/fungsi klik ikon.
 *
 * Tidak ada tabel notifikasi terpisah -- badge & toast dihitung langsung
 * dari data asli (lihat App\Models\User::unread*Count()).
 */
new class extends Component
{
    #[On('admin-notifications-updated')]
    public function refresh(): void
    {
        // Kosong secara sengaja -- kehadiran listener ini memaksa Livewire
        // me-render ulang komponen (dan getUnreadCountProperty() di bawah
        // selalu dihitung ulang, tidak di-cache), jadi badge langsung dapat
        // angka terbaru begitu event ini diterima dari halaman admin lain.
    }

    public function getUnreadCountProperty(): int
    {
        $user = auth()->user();

        return $user ? $user->unreadNotificationsCount() : 0;
    }

    /** Ringkasan per kategori untuk toast -- hanya kategori dengan count > 0. */
    public function getUnreadBreakdownProperty(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $groups = [
            ['icon' => 'fa-comment-dots', 'label' => 'Interaksi Baru', 'count' => $user->unreadInteraksiCount()],
            ['icon' => 'fa-cart-shopping', 'label' => 'Pesanan Baru', 'count' => $user->unreadPesananCount()],
            ['icon' => 'fa-triangle-exclamation', 'label' => 'Stok Menipis', 'count' => $user->unreadDashboardCount()],
        ];

        return array_values(array_filter($groups, fn (array $g) => $g['count'] > 0));
    }
};
?>

<div
    class="relative shrink-0"
    x-data="{
        toastVisible: false,
        toastGroups: @js($this->unreadBreakdown),
        toastTotal: 0,
        initToast() {
            this.toastTotal = this.toastGroups.reduce((sum, g) => sum + g.count, 0);

            // Bandingkan dengan total terakhir kali toast ditampilkan (per tab
            // browser). Toast HANYA muncul kalau totalnya berubah/bertambah
            // dibanding sebelumnya -- supaya tidak muncul ulang terus-menerus
            // hanya karena reload/F5 pada notifikasi yang sama.
            const lastSeenTotal = parseInt(sessionStorage.getItem('notif-toast-last-total') || '0', 10);

            if (this.toastTotal > 0 && this.toastTotal !== lastSeenTotal) {
                this.toastVisible = true;
                sessionStorage.setItem('notif-toast-last-total', String(this.toastTotal));
                setTimeout(() => { this.toastVisible = false }, 10000);
            }
        },
    }"
    x-init="initToast()"
>
    @if (auth()->check())
        {{-- ================= A. ADMIN ACCESS (fungsi utama -- klik = buka Dashboard) ================= --}}
        <a
            href="{{ auth()->check() ? route('admin.dashboard') : route('admin.login') }}"
            wire:navigate
            wire:poll.20s
            aria-label="Admin Access — buka Dashboard"
            class="relative flex h-9 w-9 items-center justify-center rounded-full border border-admin-border text-admin-ink-soft transition-all duration-300 hover:border-admin-accent hover:text-admin-accent hover:shadow-md"
        >
            <i class="fa-solid fa-user-shield text-sm"></i>

            @if ($this->unreadCount > 0)
                <span class="pointer-events-none absolute -right-1 -top-1 flex h-4.5 min-w-4.5 items-center justify-center rounded-full border-2 border-admin-surface bg-admin-danger px-1 text-[9px] font-bold leading-none text-white">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </a>

        {{-- ================= B. FLOATING TOAST (ala TikTok) -- elemen TERPISAH, tidak menggantikan ikon di atas ================= --}}
        <div
            x-show="toastVisible"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            style="display: none;"
            @click="toastVisible = false"
            class="absolute right-0 top-full z-50 mt-2.5 flex w-max max-w-64 cursor-pointer items-center gap-2 rounded-full px-3.5 py-2 shadow-lg shadow-black/25"
            :style="{ backgroundColor: '#FE2C55' }"
        >
            <i class="fa-solid fa-bell shrink-0 text-xs text-white"></i>
            <span class="whitespace-nowrap text-[12px] font-semibold text-white" x-text="toastTotal + ' notifikasi baru'"></span>
        </div>
    @endif
</div>