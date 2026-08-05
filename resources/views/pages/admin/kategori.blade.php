<?php

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin-panel')] #[Title('Kategori')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Aktifkan / nonaktifkan kategori langsung dari tabel.
     * Kategori nonaktif disembunyikan dari frontend, produk di
     * dalamnya tetap aman (tidak terhapus, tidak kehilangan relasi).
     */
    public function toggleActive(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    /**
     * Simpan urutan baru hasil drag & drop (SortableJS di sisi klien
     * sudah menampilkan urutan yang benar secara instan; method ini
     * hanya menuliskan urutan itu ke kolom `sort_order`).
     *
     * skipRender() dipakai supaya Livewire tidak menimpa ulang DOM
     * tabel yang sudah lebih dulu diatur SortableJS (mencegah "kedip").
     *
     * @param  array<int, int>  $orderedIds  ID kategori, urutan pertama = tampil paling atas / featured pertama.
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $categoryId) {
            Category::query()->whereKey($categoryId)->update(['sort_order' => $position]);
        }

        $this->skipRender();
    }

    /**
     * Kategori TIDAK BOLEH dihapus kalau masih dipakai produk —
     * supaya tidak ada produk yang kehilangan relasi kategorinya.
     * Admin harus memindahkan produknya ke kategori lain dulu.
     */
    public function delete(int $categoryId): void
    {
        $category = Category::query()->withCount('products')->findOrFail($categoryId);

        if ($category->products_count > 0) {
            session()->flash('error', "Kategori \"{$category->name}\" masih dipakai oleh {$category->products_count} produk. Pindahkan produk-produk itu ke kategori lain dulu sebelum menghapus.");

            return;
        }

        if ($category->cover) {
            Storage::disk('public')->delete($category->cover);
        }

        $name = $category->name;
        $category->delete();

        session()->flash('status', "Kategori \"{$name}\" berhasil dihapus.");
    }

    /**
     * Drag & drop cuma masuk akal kalau SEMUA kategori kelihatan dalam satu
     * layar (posisi 1-4 nentuin Featured Category, jadi tidak boleh terpisah
     * halaman). Karena itu saat tidak sedang mencari, daftar ditampilkan utuh
     * tanpa pagination. Saat mencari, balik ke mode paginated biasa dan drag
     * dinonaktifkan (mengurutkan hasil filter parsial tidak ada gunanya).
     */
    public function with(): array
    {
        $sortable = $this->search === '';

        $query = Category::query()->withCount('products');

        if ($sortable) {
            return [
                'categories' => $query->ordered()->get(),
                'totalKategori' => Category::query()->count(),
                'sortable' => true,
            ];
        }

        return [
            'categories' => $query->where('name', 'like', '%'.$this->search.'%')->ordered()->paginate(10),
            'totalKategori' => Category::query()->count(),
            'sortable' => false,
        ];
    }
};
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>

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
                Manajemen Kategori
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                {{ $totalKategori }} kategori produk terdaftar. Produk memilih kategori dari daftar ini lewat dropdown.
            </p>
        </div>
        <a
            href="{{ route('admin.categories.create') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-admin-accent-strong"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            Tambah Kategori
        </a>
    </div>

    {{-- ================= SEARCH ================= --}}
    <div class="rounded-2xl border border-admin-border bg-admin-surface p-4 shadow-sm">
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-admin-ink-soft"></i>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari nama kategori..."
                class="w-full rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
            >
        </div>
    </div>

    {{-- ================= TABEL KATEGORI ================= --}}
    @if ($categories->isEmpty())
        <div class="relative overflow-hidden rounded-2xl border border-admin-border bg-admin-surface px-6 py-20 text-center shadow-sm">
            <div class="pointer-events-none absolute inset-0 opacity-[0.4]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-border) 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-(--color-admin-accent)/20">
                    <i class="fa-solid fa-tags text-xl text-white"></i>
                </span>
                <p class="mt-5 font-display text-base font-semibold text-admin-ink">
                    {{ $search !== '' ? 'Kategori tidak ditemukan' : 'Belum ada kategori' }}
                </p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    {{ $search !== ''
                        ? 'Coba kata kunci pencarian lain.'
                        : 'Buat kategori terlebih dahulu sebelum menambahkan produk. Klik "Tambah Kategori" untuk mulai.' }}
                </p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-admin-border bg-admin-surface shadow-sm">
            @if ($sortable)
                <p class="flex items-center gap-2 border-b border-admin-border px-5 py-3 text-xs text-admin-ink-soft">
                    <i class="fa-solid fa-arrows-up-down text-admin-accent"></i>
                    Tahan ikon <i class="fa-solid fa-grip-vertical mx-0.5"></i> lalu geser untuk mengubah urutan. Tersimpan otomatis — 4 kategori teratas jadi <span class="font-medium text-admin-ink">Featured Category</span> di frontend.
                </p>
            @else
                <p class="flex items-center gap-2 border-b border-admin-border px-5 py-3 text-xs text-admin-ink-soft">
                    <i class="fa-solid fa-circle-info text-admin-accent"></i>
                    Urutan tidak bisa diubah saat sedang mencari. Kosongkan pencarian untuk mengatur urutan drag &amp; drop.
                </p>
            @endif
            <div class="admin-scroll overflow-x-auto">
                <table class="w-full min-w-180 text-left text-sm">
                    <thead>
                        <tr class="border-b border-admin-border bg-admin-canvas text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            <th class="w-10 px-5 py-3"></th>
                            <th class="px-3 py-3 font-semibold">Cover</th>
                            <th class="px-3 py-3 font-semibold">Nama Kategori</th>
                            <th class="px-3 py-3 font-semibold">Slug</th>
                            <th class="px-3 py-3 font-semibold">Jumlah Produk</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody
                        x-data="categorySortable(@js($sortable))"
                        x-init="init($el)"
                        class="divide-y divide-admin-border"
                    >
                        @foreach ($categories as $category)
                            <tr wire:key="kategori-row-{{ $category->id }}" data-id="{{ $category->id }}" class="transition-colors duration-200 hover:bg-admin-canvas">
                                <td class="px-5 py-3 text-center">
                                    @if ($sortable)
                                        <i class="fa-solid fa-grip-vertical drag-handle cursor-grab text-sm text-admin-ink-soft/50 transition hover:text-admin-accent active:cursor-grabbing"></i>
                                    @else
                                        <i class="fa-solid fa-grip-vertical text-sm text-admin-ink-soft/20"></i>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($category->coverUrl())
                                        <img
                                            src="{{ $category->coverUrl() }}"
                                            alt="{{ $category->name }}"
                                            class="h-11 w-11 rounded-xl object-cover ring-1 ring-admin-border"
                                        >
                                    @else
                                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-admin-cream">
                                            <i class="fa-solid fa-tags text-sm text-admin-accent"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 font-medium text-admin-ink">{{ $category->name }}</td>
                                <td class="px-3 py-3 text-admin-ink-soft">{{ $category->slug }}</td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-admin-cream px-2.5 py-1 text-[11px] font-semibold text-admin-ink">
                                        <i class="fa-solid fa-couch text-[10px] text-admin-accent"></i>
                                        {{ $category->products_count }} produk
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $category->id }})"
                                        wire:confirm="{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }} kategori &quot;{{ $category->name }}&quot;?"
                                        @class([
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold transition-colors duration-200',
                                            'bg-admin-success/10 text-admin-success hover:bg-admin-success/20' => $category->is_active,
                                            'bg-admin-danger/10 text-admin-danger hover:bg-admin-danger/20' => ! $category->is_active,
                                        ])
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full {{ $category->is_active ? 'bg-admin-success' : 'bg-admin-danger' }}"></span>
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5" wire:key="aksi-kategori-{{ $category->id }}">
                                        <a
                                            href="{{ route('admin.categories.edit', $category) }}"
                                            wire:navigate
                                            title="Edit kategori"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-accent"
                                        >
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                        <button
                                            type="button"
                                            title="Hapus kategori"
                                            wire:click="delete({{ $category->id }})"
                                            wire:confirm="Yakin ingin menghapus kategori &quot;{{ $category->name }}&quot;?"
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

            @unless ($sortable)
                <div class="border-t border-admin-border px-5 py-3.5">
                    {{ $categories->onEachSide(1)->links() }}
                </div>
            @endunless
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('categorySortable', (sortable) => ({
        sortableInstance: null,

        init(el) {
            if (! sortable) return;

            this.sortableInstance = new Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'bg-admin-cream',
                onEnd: () => {
                    const orderedIds = Array.from(el.querySelectorAll('tr[data-id]'))
                        .map((tr) => parseInt(tr.dataset.id, 10));

                    this.$wire.reorder(orderedIds);
                },
            });
        },
    }));
</script>
@endscript
