<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * SCAFFOLD -- baru kerangka halaman & menu sidebar-nya dulu.
 * Belum ada field/fitur edit apa pun di sini; ini cuma tempat
 * kosong (empty state) yang nanti diisi bertahap per bagian
 * (misalnya: konten Beranda, konten Profil, dll).
 */
new #[Layout('layouts::admin-panel')] #[Title('Edit Web')] class extends Component
{
    //
};
?>

<div class="mx-auto max-w-6xl space-y-6">

    <div>
        <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
            Edit Web
        </h2>
        <p class="mt-1 text-sm text-admin-ink-soft">
            Kelola konten yang tampil di halaman-halaman website (Beranda, Profil, dll).
        </p>
    </div>

    <div class="rounded-2xl border border-dashed border-admin-border bg-admin-surface px-5 py-16 text-center">
        <i class="fa-solid fa-pen-to-square mb-3 text-2xl text-admin-ink-soft"></i>
        <p class="text-sm font-medium text-admin-ink">
            Halaman ini masih dalam pengembangan.
        </p>
        <p class="mt-1 text-xs text-admin-ink-soft">
            Bagian untuk mengedit konten website akan ditambahkan di sini.
        </p>
    </div>

</div>
