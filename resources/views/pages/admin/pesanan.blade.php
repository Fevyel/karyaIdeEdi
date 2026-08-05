<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin-panel')] #[Title('Pesanan')] class extends Component
{
    /** Begitu Pesanan dibuka, semua pesanan yang belum dibaca langsung ditandai sudah dibaca. */
    public function mount(): void
    {
        auth()->user()?->markPesananRead();
        $this->dispatch('admin-notifications-updated');
    }
};
?>

@include('partials.admin.placeholder', [
    'icon' => 'fa-receipt',
    'heading' => 'Manajemen Pesanan',
    'description' => 'Pantau dan kelola pesanan/booking yang masuk dari pelanggan.',
    'features' => [
        'Daftar pesanan real-time',
        'Ubah status pesanan',
        'Detail booking custom',
        'Riwayat per pelanggan',
    ],
])
