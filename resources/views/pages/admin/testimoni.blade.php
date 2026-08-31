<?php

use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin-panel')] #[Title('Testimoni')] class extends Component
{
    use WithPagination;

    /** Maksimal komentar yang boleh tampil di section beranda sekaligus. */
    private const MAX_FEATURED_HOME = 3;

    #[Url(as: 'cari', history: true)]
    public string $search = '';

    /** ID testimoni yang mau ditampilkan admin, tapi 3 slot beranda sudah penuh. */
    public ?int $swapCandidateId = null;

    public bool $showSwapModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'testimonials' => Testimonial::query()
                ->when($this->search, fn ($query) => $query
                    ->where('customer_name', 'like', '%'.$this->search.'%')
                    ->orWhere('comment', 'like', '%'.$this->search.'%'))
                ->ordered()
                ->paginate(10),
            'totalTestimoni' => Testimonial::query()->count(),
            'featuredCount' => Testimonial::query()->featuredHome()->count(),
            'swapCandidate' => $this->swapCandidateId ? Testimonial::query()->find($this->swapCandidateId) : null,
            'currentlyFeatured' => $this->showSwapModal
                ? Testimonial::query()->featuredHome()->orderBy('urutan')->get()
                : collect(),
        ];
    }

    public function toggleAktif(int $testimonialId): void
    {
        $testimonial = Testimonial::findOrFail($testimonialId);
        $testimonial->is_active = ! $testimonial->is_active;

        // Nonaktif -> otomatis lepas dari slot beranda juga, supaya slotnya
        // bisa dipakai testimoni lain (testimoni nonaktif tidak mungkin
        // tampil di frontend meski masih ditandai "featured").
        if (! $testimonial->is_active && $testimonial->is_featured_home) {
            $testimonial->is_featured_home = false;
        }

        $testimonial->save();
    }

    /**
     * Toggle "tampil di beranda". Kalau 3 slot sudah penuh dan admin
     * mencoba menambah yang ke-4, buka modal supaya admin memilih
     * sendiri komentar mana yang mau digantikan.
     */
    public function toggleFeaturedHome(int $testimonialId): void
    {
        $testimonial = Testimonial::query()->findOrFail($testimonialId);

        if ($testimonial->is_featured_home) {
            $testimonial->update(['is_featured_home' => false]);
            session()->flash('status', 'Testimoni "'.$testimonial->customer_name.'" ditarik dari beranda.');

            return;
        }

        if ($testimonial->approval_status !== 'approved' || ! $testimonial->is_active) {
            session()->flash('error', 'Hanya testimoni yang berstatus disetujui & aktif yang bisa ditampilkan di beranda.');

            return;
        }

        if (Testimonial::query()->featuredHome()->count() >= self::MAX_FEATURED_HOME) {
            $this->swapCandidateId = $testimonialId;
            $this->showSwapModal = true;

            return;
        }

        $testimonial->update(['is_featured_home' => true]);
        session()->flash('status', 'Testimoni "'.$testimonial->customer_name.'" ditampilkan di beranda.');
    }

    /** Gantikan salah satu dari 3 slot beranda dengan calon baru. */
    public function swapFeaturedHome(int $replaceId): void
    {
        if (! $this->swapCandidateId) {
            return;
        }

        $old = Testimonial::query()->find($replaceId);
        $new = Testimonial::query()->find($this->swapCandidateId);

        if ($old) {
            $old->update(['is_featured_home' => false]);
        }

        if ($new) {
            $new->update(['is_featured_home' => true]);
        }

        session()->flash('status', $old && $new
            ? 'Slot beranda "'.$old->customer_name.'" diganti dengan "'.$new->customer_name.'".'
            : 'Slot beranda berhasil diperbarui.');

        $this->cancelSwap();
    }

    public function cancelSwap(): void
    {
        $this->swapCandidateId = null;
        $this->showSwapModal = false;
    }

    public function delete(int $testimonialId): void
    {
        $testimonial = Testimonial::findOrFail($testimonialId);

        if ($testimonial->foto) {
            Storage::disk('public')->delete($testimonial->foto);
        }

        $testimonial->delete();

        session()->flash('status', 'Testimoni "'.$testimonial->customer_name.'" berhasil dihapus.');
    }
};
?>

<div class="space-y-6">

    @if (session('status'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
                Manajemen Testimoni
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                {{ $totalTestimoni }} testimoni terdaftar. Untuk komentar dari pembeli, lihat menu
                <a href="{{ route('admin.interaksi') }}" wire:navigate class="font-medium text-admin-accent hover:underline">Interaksi</a>.
            </p>
        </div>
        <a
            href="{{ route('admin.testimonials.create') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-admin-accent-strong"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            Tambah Testimoni
        </a>
    </div>

    {{-- ================= SEARCH ================= --}}
    <div class="rounded-2xl border border-admin-border bg-admin-surface p-4 shadow-sm">
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-admin-ink-soft"></i>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari nama pelanggan atau isi testimoni..."
                class="w-full rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
            >
        </div>
    </div>

    {{-- ================= TABEL TESTIMONI ================= --}}
    @if ($testimonials->isEmpty())
        <div class="relative overflow-hidden rounded-2xl border border-admin-border bg-admin-surface px-6 py-20 text-center shadow-sm">
            <div class="pointer-events-none absolute inset-0 opacity-[0.4]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-border) 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-(--color-admin-accent)/20">
                    <i class="fa-solid fa-quote-left text-xl text-white"></i>
                </span>
                <p class="mt-5 font-display text-base font-semibold text-admin-ink">
                    {{ $search ? 'Testimoni tidak ditemukan' : 'Belum ada testimoni' }}
                </p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    @if ($search)
                        Tidak ada testimoni yang cocok dengan pencarian "{{ $search }}".
                    @else
                        Klik "Tambah Testimoni" untuk menambahkan testimoni pertama.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-admin-border bg-admin-surface shadow-sm">
            <div class="admin-scroll overflow-x-auto">
                <table class="w-full min-w-230 text-left text-sm">
                    <thead>
                        <tr class="border-b border-admin-border bg-admin-canvas text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            <th class="px-5 py-3 font-semibold">Foto</th>
                            <th class="px-3 py-3 font-semibold">Nama</th>
                            <th class="px-3 py-3 font-semibold">Jabatan</th>
                            <th class="px-3 py-3 font-semibold">Isi Testimoni</th>
                            <th class="px-3 py-3 font-semibold">Rating</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 font-semibold">Urutan</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        @foreach ($testimonials as $testimonial)
                            <tr class="transition-colors duration-200 hover:bg-admin-canvas">
                                <td class="px-5 py-3">
                                    @if ($testimonial->foto && Storage::disk('public')->exists($testimonial->foto))
                                        <img
                                            src="{{ Storage::disk('public')->url($testimonial->foto) }}"
                                            alt="{{ $testimonial->customer_name }}"
                                            class="h-11 w-11 rounded-full object-cover ring-1 ring-admin-border"
                                        >
                                    @else
                                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-admin-cream text-sm font-semibold text-admin-accent">
                                            {{ strtoupper(substr($testimonial->customer_name, 0, 1)) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 font-medium text-admin-ink">
                                    {{ $testimonial->customer_name }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    {{ $testimonial->jabatan ?: '—' }}
                                </td>
                                <td class="px-3 py-3 max-w-70 truncate text-admin-ink-soft">
                                    {{ $testimonial->comment }}
                                </td>
                                <td class="px-3 py-3">
                                    <span class="flex items-center gap-0.5 text-admin-gold">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa-solid fa-star text-[11px] {{ $i > ($testimonial->rating ?? 0) ? 'text-admin-border' : '' }}"></i>
                                        @endfor
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <button
                                        type="button"
                                        wire:click="toggleAktif({{ $testimonial->id }})"
                                        title="Klik untuk ubah status"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold transition-opacity hover:opacity-80
                                            {{ $testimonial->is_active
                                                ? 'bg-admin-success/10 text-admin-success'
                                                : 'bg-admin-danger/10 text-admin-danger' }}"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full {{ $testimonial->is_active ? 'bg-admin-success' : 'bg-admin-danger' }}"></span>
                                        {{ $testimonial->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="px-3 py-3 text-admin-ink">
                                    {{ $testimonial->urutan }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5" wire:key="aksi-{{ $testimonial->id }}">
                                        <button
                                            type="button"
                                            wire:click="toggleFeaturedHome({{ $testimonial->id }})"
                                            title="{{ $testimonial->is_featured_home ? 'Tarik dari beranda' : 'Tampilkan di beranda' }}"
                                            @class([
                                                'flex h-8 w-8 items-center justify-center rounded-lg transition-colors duration-200',
                                                'text-admin-gold hover:bg-admin-gold/15' => $testimonial->is_featured_home,
                                                'text-admin-ink-soft hover:bg-admin-cream hover:text-admin-ink' => ! $testimonial->is_featured_home,
                                            ])
                                        >
                                            <i class="fa-{{ $testimonial->is_featured_home ? 'solid' : 'regular' }} fa-star text-xs"></i>
                                        </button>
                                        <a
                                            href="{{ route('admin.testimonials.edit', $testimonial) }}"
                                            wire:navigate
                                            title="Edit testimoni"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-accent"
                                        >
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                        <button
                                            type="button"
                                            title="Hapus testimoni"
                                            wire:click="delete({{ $testimonial->id }})"
                                            wire:confirm="Yakin ingin menghapus testimoni dari &quot;{{ $testimonial->customer_name }}&quot;? Tindakan ini tidak bisa dibatalkan."
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-red-50 hover:text-red-500"
                                        >
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-admin-border px-5 py-3.5">
                {{ $testimonials->onEachSide(1)->links() }}
            </div>
        </div>
    @endif

    {{-- ================= MODAL: GANTI SLOT BERANDA (3 slot penuh) ================= --}}
    @if ($showSwapModal && $swapCandidate)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div wire:click="cancelSwap" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-admin-surface shadow-2xl">
                <div class="border-b border-admin-border px-6 py-4">
                    <h3 class="font-display text-lg font-semibold text-admin-ink">Slot Beranda Sudah Penuh</h3>
                    <p class="mt-1 text-sm text-admin-ink-soft">
                        Maksimal 3 testimoni tampil di beranda. Pilih salah satu di bawah ini untuk digantikan dengan
                        <span class="font-medium text-admin-ink">"{{ $swapCandidate->customer_name }}"</span>.
                    </p>
                </div>

                <div class="space-y-2 px-6 py-5">
                    @foreach ($currentlyFeatured as $featured)
                        <div wire:key="swap-{{ $featured->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-admin-border bg-admin-canvas px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-admin-ink">{{ $featured->customer_name }}</p>
                                <p class="truncate text-xs text-admin-ink-soft">{{ $featured->comment }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="swapFeaturedHome({{ $featured->id }})"
                                class="shrink-0 rounded-full bg-admin-accent px-3.5 py-1.5 text-xs font-semibold text-white transition-colors duration-200 hover:bg-admin-accent-strong"
                            >
                                Ganti Ini
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-admin-border px-6 py-4">
                    <button type="button" wire:click="cancelSwap" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
