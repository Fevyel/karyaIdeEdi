<?php

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Badge notifikasi kecil untuk item sidebar admin (Dashboard, Pesanan,
 * Interaksi). Poll ringan setiap 20 detik supaya "realtime" tanpa
 * perlu WebSocket — begitu ada data baru, badge muncul/berubah sendiri
 * tanpa admin perlu navigasi atau refresh.
 *
 * Selain polling, komponen ini juga dengar event 'admin-notifications-updated'
 * (dikirim oleh halaman Dashboard/Pesanan/Interaksi begitu masing-masing
 * ditandai sudah dibaca) supaya badge langsung hilang SAAT ITU JUGA,
 * tanpa menunggu siklus poll berikutnya atau refresh manual kedua.
 *
 * Aturan tampilan (HANYA berlaku di sidebar):
 *   0      -> badge disembunyikan
 *   1-99   -> angka asli
 *   100+   -> "99+"
 */
new class extends Component
{
    public string $type = '';

    public bool $active = false;

    public function mount(string $type, bool $active = false): void
    {
        $this->type = $type;
        $this->active = $active;
    }

    #[On('admin-notifications-updated')]
    public function refresh(): void
    {
        // Method ini sengaja kosong — kehadiran listener saja sudah
        // memaksa Livewire me-render ulang komponen ini, dan getCountProperty()
        // di bawah selalu dihitung ulang (tidak di-cache), jadi otomatis dapat
        // angka terbaru begitu event ini diterima.
    }

    public function getCountProperty(): int
    {
        $user = auth()->user();

        if (! $user) {
            return 0;
        }

        return match ($this->type) {
            'interaksi' => $user->unreadInteraksiCount(),
            'pesanan' => $user->unreadPesananCount(),
            'dashboard' => $user->unreadDashboardCount(),
            default => 0,
        };
    }

    public function getLabelProperty(): ?string
    {
        return \App\Models\User::formatSidebarBadge($this->count);
    }
};
?>

<span
    wire:poll.20s
    class="{{ $this->label
        ? 'flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold '.($active ? 'bg-white/25 text-white' : 'bg-admin-gold text-admin-panel')
        : 'hidden' }}"
>{{ $this->label }}</span>