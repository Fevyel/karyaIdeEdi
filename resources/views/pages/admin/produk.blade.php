<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin-panel')] #[Title('Produk')] class extends Component
{
    use WithPagination;

    /**
     * Halaman daftar produk (index). Create/Edit ada di halaman
     * terpisah (produk-form), Delete dieksekusi langsung di sini.
     * Search & filter kategori di atas tabel masih UI saja, belum
     * terhubung ke query (menunggu tahap berikutnya).
     */
    public function with(): array
    {
        return [
            'products' => Product::query()->with('category')->latest()->paginate(10),
            'totalProduk' => Product::query()->count(),
            'kategoriList' => Category::query()->ordered()->get(),
        ];
    }

    public function delete(int $productId): void
    {
        $product = Product::findOrFail($productId);

        if ($product->thumbnail) {
            Storage::disk('public')->delete($product->thumbnail);
        }

        $product->delete();

        session()->flash('status', 'Produk "'.$product->nama.'" berhasil dihapus.');
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

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
                Manajemen Produk
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                {{ $totalProduk }} produk furniture terdaftar di katalog.
            </p>
        </div>
        <a
            href="{{ route('admin.products.create') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-admin-accent-strong"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            Tambah Produk
        </a>
    </div>

    {{-- ================= SEARCH & FILTER (UI saja, belum berfungsi) ================= --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-admin-border bg-admin-surface p-4 shadow-sm lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-admin-ink-soft"></i>
            <input
                type="text"
                title="Pencarian belum berfungsi"
                placeholder="Cari nama produk..."
                class="w-full rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
            >
        </div>

        <div class="relative lg:w-56 lg:shrink-0">
            <i class="fa-solid fa-tags pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-admin-accent"></i>
            <select
                title="Filter belum berfungsi"
                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-8 pr-8 text-xs font-medium text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 sm:text-sm"
            >
                <option>Semua Kategori</option>
                @foreach ($kategoriList as $kategori)
                    <option>{{ $kategori->name }}</option>
                @endforeach
            </select>
            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
        </div>
    </div>

    {{-- ================= TABEL PRODUK ================= --}}
    @if ($products->isEmpty())
        <div class="relative overflow-hidden rounded-2xl border border-admin-border bg-admin-surface px-6 py-20 text-center shadow-sm">
            <div class="pointer-events-none absolute inset-0 opacity-[0.4]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-border) 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-(--color-admin-accent)/20">
                    <i class="fa-solid fa-couch text-xl text-white"></i>
                </span>
                <p class="mt-5 font-display text-base font-semibold text-admin-ink">
                    Belum ada produk
                </p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    Katalog produk furniture masih kosong. Klik "Tambah Produk" untuk mulai menambahkan produk pertama Anda.
                </p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-admin-border bg-admin-surface shadow-sm">
            <div class="admin-scroll overflow-x-auto">
                <table class="w-full min-w-230 text-left text-sm">
                    <thead>
                        <tr class="border-b border-admin-border bg-admin-canvas text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            <th class="px-5 py-3 font-semibold">Thumbnail</th>
                            <th class="px-3 py-3 font-semibold">Nama Produk</th>
                            <th class="px-3 py-3 font-semibold">Kategori</th>
                            <th class="px-3 py-3 font-semibold">Harga</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 font-semibold">Featured</th>
                            <th class="px-3 py-3 font-semibold">Stok</th>
                            <th class="px-3 py-3 font-semibold">Dibuat</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        @foreach ($products as $product)
                            <tr class="transition-colors duration-200 hover:bg-admin-canvas">
                                <td class="px-5 py-3">
                                    @if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail))
                                        <img
                                            src="{{ Storage::disk('public')->url($product->thumbnail) }}"
                                            alt="{{ $product->nama }}"
                                            class="h-11 w-11 rounded-xl object-cover ring-1 ring-admin-border"
                                        >
                                    @else
                                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-admin-cream">
                                            <i class="fa-solid fa-couch text-sm text-admin-accent"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 font-medium text-admin-ink">
                                    {{ $product->nama }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    {{ $product->category?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 font-semibold text-admin-ink">
                                    Rp{{ number_format((float) $product->harga, 0, ',', '.') }}
                                    @if ($product->harga_diskon)
                                        <span class="mt-0.5 block text-xs font-normal text-admin-ink-soft line-through">
                                            Rp{{ number_format((float) $product->harga_diskon, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold
                                        {{ $product->status === 'aktif'
                                            ? 'bg-admin-success/10 text-admin-success'
                                            : 'bg-admin-danger/10 text-admin-danger' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $product->status === 'aktif' ? 'bg-admin-success' : 'bg-admin-danger' }}"></span>
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($product->featured)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-admin-gold/15 px-2.5 py-1 text-[11px] font-semibold text-admin-accent-strong">
                                            <i class="fa-solid fa-star text-[10px]"></i>
                                            Featured
                                        </span>
                                    @else
                                        <span class="text-xs text-admin-ink-soft">&mdash;</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 {{ $product->stok === 0 ? 'font-semibold text-admin-danger' : 'text-admin-ink' }}">
                                    {{ $product->stok }}
                                </td>
                                <td class="px-3 py-3 text-xs text-admin-ink-soft">
                                    {{ $product->created_at?->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5" wire:key="aksi-{{ $product->id }}">
                                        <a
                                            href="{{ route('admin.products.edit', $product) }}"
                                            wire:navigate
                                            title="Edit produk"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-accent"
                                        >
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                        <button
                                            type="button"
                                            title="Hapus produk"
                                            wire:click="delete({{ $product->id }})"
                                            wire:confirm="Yakin ingin menghapus produk &quot;{{ $product->nama }}&quot;? Tindakan ini tidak bisa dibatalkan."
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
                {{ $products->onEachSide(1)->links() }}
            </div>
        </div>
    @endif
</div>