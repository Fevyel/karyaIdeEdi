<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Panel "Reset" di topbar Admin Panel (sebelah dropdown akun).
 * Dibuka dengan HOVER di desktop (mouseenter) ATAU TAP/klik di mobile
 * -- keduanya jalan berbarengan lewat Alpine (lihat blok <div> di bawah).
 *
 * Isinya 2 aksi yang SAMA-SAMA MERUSAK & TIDAK BISA DIBATALKAN:
 *
 *   - Reset Pabrik: mengosongkan SEMUA data toko (produk, kategori,
 *     pesanan/transaksi, testimoni) + file foto terkait di storage,
 *     KECUALI pengaturan website (tabel settings & home_sections) dan
 *     akun admin (tabel users) -- persis kayak toko baru pertama kali
 *     dipasang, belum ada interaksi customer maupun admin sama sekali.
 *
 *   - Reset Data: cuma mengosongkan data transaksi/pesanan & testimoni
 *     (+ foto testimoni). Produk dan kategori yang sudah diinput admin
 *     TETAP aman, tidak ikut terhapus.
 *
 * Karena efeknya permanen, tombol eksekusi baru aktif kalau admin
 * mengetik ULANG persis frasa konfirmasi ("RESET PABRIK" / "RESET DATA")
 * -- bukan modal "Yakin?" biasa yang gampang ke-klik tanpa sengaja.
 *
 * Asumsi folder storage (mengikuti pola upload yang SUDAH ada di project
 * ini, lihat produk-form.blade.php & testimoni-form.blade.php):
 *   - storage/app/public/produk    -> foto produk (thumbnail & galeri)
 *   - storage/app/public/testimoni -> foto testimoni (single & multi)
 * Folder storage/app/public/profil (foto profil admin) SENGAJA tidak
 * disentuh sama sekali oleh kedua mode reset ini.
 */
new class extends Component
{
    /** Mode confirm yang lagi aktif: null | 'pabrik' | 'data'. */
    public ?string $mode = null;

    public string $confirmText = '';

    public ?string $resultMessage = null;

    public function openConfirm(string $mode): void
    {
        $this->mode = $mode;
        $this->confirmText = '';
        $this->resultMessage = null;
        $this->resetErrorBag();
    }

    public function closeConfirm(): void
    {
        $this->mode = null;
        $this->confirmText = '';
        $this->resetErrorBag();
    }

    public function getRequiredPhraseProperty(): string
    {
        return $this->mode === 'pabrik' ? 'RESET PABRIK' : 'RESET DATA';
    }

    public function execute(): void
    {
        if (! in_array($this->mode, ['pabrik', 'data'], true)) {
            return;
        }

        if (strtoupper(trim($this->confirmText)) !== $this->requiredPhrase) {
            $this->addError('confirmText', 'Ketik persis "'.$this->requiredPhrase.'" untuk konfirmasi.');

            return;
        }

        if ($this->mode === 'pabrik') {
            $this->runResetPabrik();
        } else {
            $this->runResetData();
        }

        $this->mode = null;
        $this->confirmText = '';

        // Badge notifikasi sidebar (Pesanan/Interaksi) langsung ikut ter-update.
        $this->dispatch('admin-notifications-updated');

        // Redirect ke Dashboard supaya halaman manapun yang lagi dibuka admin
        // langsung menampilkan kondisi data yang sudah bersih, bukan data lama
        // yang kebetulan masih nyangkut di state Livewire halaman sebelumnya.
        session()->flash('reset-panel-message', $this->resultMessage);
        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    private function runResetPabrik(): void
    {
        // TRUNCATE di MySQL otomatis implicit-commit, jadi TIDAK BOLEH
        // dibungkus DB::transaction() -- kalau dibungkus, Laravel akan
        // gagal COMMIT di akhir karena transaksinya sudah "hilang"
        // duluan begitu TRUNCATE pertama jalan (PDOException "There is
        // no active transaction"). Urutan perintah di bawah ini PERSIS
        // sama seperti sebelumnya, cuma tanpa wrapper transaction.
        Schema::disableForeignKeyConstraints();
        DB::table('testimonials')->truncate();
        DB::table('transactions')->truncate();
        DB::table('product_images')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        Schema::enableForeignKeyConstraints();

        Storage::disk('public')->deleteDirectory('produk');
        Storage::disk('public')->deleteDirectory('testimoni');

        $this->resultMessage = 'Reset pabrik selesai -- produk, kategori, pesanan, dan testimoni sudah dikosongkan. Pengaturan website tidak ikut berubah.';
    }

    private function runResetData(): void
    {
        // Sama seperti runResetPabrik() -- TRUNCATE tidak boleh dibungkus
        // DB::transaction() karena implicit-commit di MySQL. Urutan
        // perintah tetap sama, cuma tanpa wrapper transaction.
        Schema::disableForeignKeyConstraints();
        DB::table('testimonials')->truncate();
        DB::table('transactions')->truncate();
        Schema::enableForeignKeyConstraints();

        Storage::disk('public')->deleteDirectory('testimoni');

        $this->resultMessage = 'Reset data selesai -- pesanan dan testimoni sudah dikosongkan. Produk & kategori tetap aman.';
    }
}; ?>

<div
    x-data="{ hoverOpen: false, tapOpen: false }"
    @click.outside="tapOpen = false"
    class="relative"
>
    <button
        type="button"
        @mouseenter="hoverOpen = true"
        @mouseleave="hoverOpen = false"
        @click="tapOpen = !tapOpen"
        class="flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition-colors duration-200 hover:bg-red-100"
    >
        <i class="fa-solid fa-arrow-rotate-left text-[11px]"></i>
        <span class="hidden sm:inline">Reset</span>
    </button>

    {{-- Tab melayang: muncul kalau di-hover (desktop) ATAU di-tap (mobile/klik).
         SENGAJA tanpa margin-top (nempel langsung ke tombol) -- kalau ada
         celah/gap di sini, kursor bisa "keluar" area hover pas jalan dari
         tombol ke panel, bikin panel keburu nutup sebelum sempat pencet
         pilihan yang lebih bawah (Reset Data). --}}
    <div
        x-show="hoverOpen || tapOpen"
        x-cloak
        x-transition.origin.top.right
        @mouseenter="hoverOpen = true"
        @mouseleave="hoverOpen = false"
        class="absolute right-0 top-full z-30 w-72 overflow-hidden rounded-2xl border border-admin-border bg-admin-surface p-2 pt-3 shadow-xl shadow-black/10"
    >
        <button
            type="button"
            wire:click="openConfirm('pabrik')"
            @click="tapOpen = false"
            class="flex w-full flex-col items-start gap-0.5 rounded-xl px-3 py-2.5 text-left transition-colors duration-150 hover:bg-red-500/10"
        >
            <span class="text-sm font-semibold text-red-600">Reset Pabrik</span>
            <span class="text-[11px] leading-snug text-admin-ink-soft">
                Kosongkan SEMUA data (produk, kategori, pesanan, testimoni). Website tidak ikut dihapus.
            </span>
        </button>

        <div class="my-1 border-t border-admin-border"></div>

        <button
            type="button"
            wire:click="openConfirm('data')"
            @click="tapOpen = false"
            class="flex w-full flex-col items-start gap-0.5 rounded-xl px-3 py-2.5 text-left transition-colors duration-150 hover:bg-amber-500/10"
        >
            <span class="text-sm font-semibold text-amber-600">Reset Data</span>
            <span class="text-[11px] leading-snug text-admin-ink-soft">
                Kosongkan data pesanan &amp; testimoni saja. Produk dan kategori tetap.
            </span>
        </button>
    </div>

    {{-- Modal konfirmasi ketik-ulang -- WAJIB sebelum aksi benar-benar jalan.

         PENTING: di-"teleport" ke <body> pakai Alpine (x-teleport), BUKAN
         dirender di tempat aslinya (dalam <header>). Sebabnya: <header>
         topbar admin pakai `backdrop-blur-xl`, dan `backdrop-filter`/`filter`
         di CSS itu bikin browser menganggap elemen itu jadi "containing
         block" baru untuk semua descendant `position: fixed` di dalamnya.
         Akibatnya overlay gelap & modal ini kejebak cuma sebatas tinggi
         header (bukan menutupi seluruh layar), DAN posisi klik tombolnya
         ikut meleset dari yang keliatan di layar. Teleport ke <body>
         membuang masalah itu -- modal jadi benar-benar relatif ke seluruh
         viewport seperti seharusnya. --}}
    @if ($mode)
        <template x-teleport="body">
        <div class="fixed inset-0 z-100 flex items-center justify-center bg-black/50 p-4" wire:key="reset-confirm-modal">
            <div class="w-full max-w-sm rounded-2xl bg-admin-surface p-6 shadow-2xl">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $mode === 'pabrik' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    </span>
                    <h3 class="text-base font-bold text-admin-ink">
                        {{ $mode === 'pabrik' ? 'Reset Pabrik' : 'Reset Data' }}
                    </h3>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-admin-ink-soft">
                    @if ($mode === 'pabrik')
                        Semua produk, kategori, pesanan, dan testimoni akan <span class="font-semibold text-admin-ink">dihapus permanen</span>. Pengaturan website tidak ikut terhapus.
                    @else
                        Semua pesanan dan testimoni akan <span class="font-semibold text-admin-ink">dihapus permanen</span>. Produk dan kategori tidak ikut terhapus.
                    @endif
                    Aksi ini tidak bisa dibatalkan.
                </p>

                <label class="mt-4 block text-[11px] font-semibold text-admin-ink">
                    Ketik <span class="font-mono text-red-600">{{ $this->requiredPhrase }}</span> untuk konfirmasi
                </label>
                <input
                    type="text"
                    wire:model="confirmText"
                    wire:keydown.enter="execute"
                    autocomplete="off"
                    placeholder="{{ $this->requiredPhrase }}"
                    class="mt-1.5 w-full rounded-lg border border-admin-border px-3 py-2 text-sm uppercase tracking-wide text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/20"
                >
                @error('confirmText')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="closeConfirm"
                        wire:loading.attr="disabled"
                        wire:target="execute"
                        class="rounded-lg px-3.5 py-2 text-xs font-semibold text-admin-ink-soft transition-colors hover:bg-admin-canvas disabled:opacity-50"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="execute"
                        wire:loading.attr="disabled"
                        wire:target="execute"
                        class="rounded-lg px-4 py-2 text-xs font-semibold text-white transition-colors disabled:opacity-60 {{ $mode === 'pabrik' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700' }}"
                    >
                        <span wire:loading.remove wire:target="execute">Ya, Hapus Permanen</span>
                        <span wire:loading wire:target="execute">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
        </template>
    @endif
</div>
