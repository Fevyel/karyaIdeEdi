<?php

use App\Models\Product;
use App\Models\Testimonial;
use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Pusat notifikasi admin (ikon di navbar frontend, mirip inbox TikTok).
 *
 * Tidak ada tabel notifikasi terpisah — daftar & badge dihitung langsung
 * dari data asli (Transaction, Testimonial, Product) dibanding dengan
 * kapan admin terakhir membaca tiap kategori (lihat App\Models\User).
 *
 * Beda dari badge sidebar: badge ikon ini SELALU angka asli, TIDAK
 * pernah dibulatkan jadi "99+".
 */
new class extends Component
{
    public bool $open = false;

    public function mount(): void
    {
        // DIAGNOSTIK SEMENTARA — lihat storage/logs/laravel.log setelah reload
        // halaman, lalu hapus baris ini setelah root cause ketemu.
        logger()->info('NotificationBell mount', [
            'open' => $this->open,
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ]);
    }

    #[On('admin-notifications-updated')]
    public function refresh(): void
    {
        // Kosong secara sengaja — lihat catatan yang sama di admin/⚡nav-badge.blade.php.
    }

    public function getUnreadCountProperty(): int
    {
        $user = auth()->user();

        return $user ? $user->unreadNotificationsCount() : 0;
    }

    /** Daftar notifikasi 30 hari terakhir, dikelompokkan per hari (Hari ini/Kemarin/Minggu ini/Lebih lama). */
    public function getGroupedItemsProperty()
    {
        $since = now()->subDays(30);

        $pesanan = Transaction::query()
            ->where('created_at', '>=', $since)
            ->latest()
            ->take(15)
            ->get()
            ->map(fn (Transaction $t) => [
                'icon' => 'fa-cart-shopping',
                'iconClass' => 'bg-emerald-100 text-emerald-600',
                'title' => 'Pesanan Baru',
                'description' => 'Pesanan #'.$t->order_code.' masuk',
                'time' => $t->created_at,
            ]);

        $interaksi = Testimonial::query()
            ->where('created_at', '>=', $since)
            ->latest()
            ->take(15)
            ->get()
            ->map(fn (Testimonial $t) => [
                'icon' => 'fa-comment-dots',
                'iconClass' => 'bg-admin-cream text-admin-accent',
                'title' => 'Interaksi Baru',
                'description' => $t->customer_name.' mengirim komentar',
                'time' => $t->created_at,
            ]);

        $dashboard = Product::query()
            ->where('stok', '<=', 5)
            ->where('updated_at', '>=', $since)
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(fn (Product $p) => [
                'icon' => 'fa-triangle-exclamation',
                'iconClass' => 'bg-red-100 text-red-600',
                'title' => 'Stok Menipis',
                'description' => 'Produk "'.$p->nama.'" tersisa '.$p->stok,
                'time' => $p->updated_at,
            ]);

        return $pesanan->concat($interaksi)->concat($dashboard)
            ->sortByDesc('time')
            ->values()
            ->take(30)
            ->groupBy(function (array $item) {
                $time = $item['time'];

                return match (true) {
                    $time->isToday() => 'Hari ini',
                    $time->isYesterday() => 'Kemarin',
                    $time->greaterThanOrEqualTo(now()->startOfWeek()) => 'Minggu ini',
                    default => 'Lebih lama',
                };
            });
    }

    public function togglePanel(): void
    {
        $this->open = ! $this->open;

        if ($this->open) {
            auth()->user()?->markAllNotificationsRead();
        }
    }

    public function closePanel(): void
    {
        $this->open = false;
    }
};
?>

<div class="group relative shrink-0" x-data @click.outside="$wire.closePanel()">
    @if (auth()->check())
        <button
            type="button"
            wire:click="togglePanel"
            wire:poll.20s
            aria-label="Notifikasi Admin"
            class="relative flex h-9 w-9 items-center justify-center rounded-full border border-admin-border text-admin-ink-soft transition-all duration-300 hover:border-admin-accent hover:text-admin-accent hover:shadow-md"
        >
            <i class="fa-solid fa-user-shield text-sm"></i>

            @if ($this->unreadCount > 0)
                <span class="absolute -right-1 -top-1 flex h-4.5 min-w-4.5 items-center justify-center rounded-full border-2 border-admin-surface bg-admin-danger px-1 text-[9px] font-bold leading-none text-white">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </button>

        @if (! $open)
            <span class="pointer-events-none absolute right-0 top-full mt-2 whitespace-nowrap rounded-md bg-admin-panel px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-lg transition-opacity duration-200 group-hover:opacity-100">
                Notifikasi Admin
            </span>
        @endif

        {{-- ================= PANEL NOTIFIKASI (mirip inbox TikTok) ================= --}}
        @if ($open)
            <div
                x-transition.origin.top.right
                class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-admin-border bg-admin-surface shadow-2xl sm:w-96"
            >
                <div class="flex items-center justify-between border-b border-admin-border px-4 py-3">
                    <p class="text-sm font-semibold text-admin-ink">Notifikasi</p>
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-xs font-medium text-admin-accent hover:underline">
                        Buka Dashboard
                    </a>
                </div>

                <div class="admin-scroll max-h-96 overflow-y-auto">
                    @forelse ($this->groupedItems as $groupLabel => $groupItems)
                        <div>
                            <p class="sticky top-0 bg-admin-cream/70 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft backdrop-blur-sm">
                                {{ $groupLabel }}
                            </p>
                            @foreach ($groupItems as $notif)
                                <div class="flex items-start gap-3 border-b border-admin-border/60 px-4 py-3 last:border-b-0">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $notif['iconClass'] }}">
                                        <i class="fa-solid {{ $notif['icon'] }} text-xs"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-admin-ink">{{ $notif['title'] }}</p>
                                        <p class="truncate text-xs text-admin-ink-soft">{{ $notif['description'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-admin-ink-soft/70">{{ $notif['time']->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <i class="fa-regular fa-bell-slash mb-2 text-xl text-admin-ink-soft"></i>
                            <p class="text-xs text-admin-ink-soft">Belum ada notifikasi.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    @endif
</div>