<?php

use App\Models\Product;
use App\Models\Transaction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin-panel')] #[Title('Pesanan')] class extends Component
{
    use WithPagination;

    public string $search = '';

    /** 'all' atau salah satu dari Transaction::STATUSES. */
    public string $statusFilter = 'all';

    /**
     * Modal "+ Tambah Pesanan" — TAHAP INI baru kerangka modal/form
     * kosongnya saja, belum ada logic submit (sesuai instruksi).
     */
    public bool $showAddModal = false;

    public bool $showDetail = false;

    public ?int $detailId = null;

    /** Begitu Pesanan dibuka, semua pesanan yang belum dibaca langsung ditandai sudah dibaca. */
    public function mount(): void
    {
        auth()->user()?->markPesananRead();
        $this->dispatch('admin-notifications-updated');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $status): void
    {
        $valid = array_merge(['all'], Transaction::STATUSES);
        $this->statusFilter = in_array($status, $valid, true) ? $status : 'all';
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
    }

    public function openDetail(int $transactionId): void
    {
        $this->detailId = $transactionId;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailId = null;
    }

    public function with(): array
    {
        $query = Transaction::query()
            ->with('product:id,nama')
            ->when($this->search !== '', function ($q) {
                $keyword = $this->search;
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('order_code', 'like', "%{$keyword}%")
                        ->orWhere('customer_name', 'like', "%{$keyword}%")
                        ->orWhereHas('product', fn ($p) => $p->where('nama', 'like', "%{$keyword}%"));
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest();

        return [
            'transactions' => $query->paginate(10),
            'totalPesanan' => Transaction::query()->count(),
            'produkList' => Product::query()->where('status', 'aktif')->orderBy('nama')->get(['id', 'nama']),
            'detailItem' => $this->showDetail && $this->detailId
                ? Transaction::query()->with('product:id,nama')->find($this->detailId)
                : null,
        ];
    }
};
?>

<div class="space-y-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
                Pesanan
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                {{ $totalPesanan }} pesanan tercatat. Pantau, cari, dan filter pesanan pelanggan dari sini.
            </p>
        </div>
        <button
            type="button"
            wire:click="openAddModal"
            class="inline-flex items-center justify-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-admin-accent-strong"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            Tambah Pesanan
        </button>
    </div>

    {{-- ================= SEARCH & FILTER STATUS (fungsional) ================= --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-admin-border bg-admin-surface p-4 shadow-sm lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-admin-ink-soft"></i>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari kode pesanan, nama customer, atau nama produk..."
                class="w-full rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
            >
        </div>

        <div class="relative lg:w-56 lg:shrink-0">
            <i class="fa-solid fa-filter pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-admin-accent"></i>
            <select
                wire:model.live="statusFilter"
                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-8 pr-8 text-xs font-medium text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 sm:text-sm"
            >
                <option value="all">Semua Status</option>
                @foreach (\App\Models\Transaction::STATUSES as $statusOption)
                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                @endforeach
            </select>
            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
        </div>
    </div>

    {{-- ================= TABEL PESANAN ================= --}}
    @if ($transactions->isEmpty())
        <div class="relative overflow-hidden rounded-2xl border border-admin-border bg-admin-surface px-6 py-20 text-center shadow-sm">
            <div class="pointer-events-none absolute inset-0 opacity-[0.4]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-border) 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-(--color-admin-accent)/20">
                    <i class="fa-solid fa-receipt text-xl text-white"></i>
                </span>
                <p class="mt-5 font-display text-base font-semibold text-admin-ink">
                    {{ $search !== '' || $statusFilter !== 'all' ? 'Tidak ada pesanan yang cocok' : 'Belum ada pesanan' }}
                </p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    @if ($search !== '' || $statusFilter !== 'all')
                        Coba ubah kata kunci pencarian atau filter status di atas.
                    @else
                        Pesanan pelanggan yang masuk akan muncul di sini. Klik "Tambah Pesanan" untuk mencatat pesanan pertama secara manual.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-admin-border bg-admin-surface shadow-sm">
            <div class="admin-scroll overflow-x-auto">
                <table class="w-full min-w-260 text-left text-sm">
                    <thead>
                        <tr class="border-b border-admin-border bg-admin-canvas text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            <th class="px-5 py-3 font-semibold">Kode Pesanan</th>
                            <th class="px-3 py-3 font-semibold">Customer</th>
                            <th class="px-3 py-3 font-semibold">Produk</th>
                            <th class="px-3 py-3 font-semibold">Jumlah</th>
                            <th class="px-3 py-3 font-semibold">Total</th>
                            <th class="px-3 py-3 font-semibold">No. Antrean</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 font-semibold">Tanggal</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        @foreach ($transactions as $transaction)
                            @php
                                $statusStyle = match ($transaction->status) {
                                    'pending' => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
                                    'confirmed' => ['dot' => 'bg-blue-500', 'pill' => 'bg-blue-50 text-blue-600'],
                                    'processing' => ['dot' => 'bg-violet-500', 'pill' => 'bg-violet-50 text-violet-600'],
                                    'preparing' => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-600'],
                                    'completed' => ['dot' => 'bg-admin-success', 'pill' => 'bg-admin-success/10 text-admin-success'],
                                    'cancelled' => ['dot' => 'bg-admin-danger', 'pill' => 'bg-admin-danger/10 text-admin-danger'],
                                    default => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
                                };
                            @endphp
                            <tr class="transition-colors duration-200 hover:bg-admin-canvas">
                                <td class="px-5 py-3 font-medium text-admin-ink">
                                    {{ $transaction->order_code }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink">
                                    {{ $transaction->customer_name }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    {{ $transaction->product?->nama ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink">
                                    {{ $transaction->quantity }}
                                </td>
                                <td class="px-3 py-3 font-semibold text-admin-ink">
                                    Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    @if ($transaction->queue_number)
                                        <span class="font-medium text-admin-ink">#{{ $transaction->queue_number }}</span>
                                        <span class="block text-[11px]">{{ $transaction->queue_date?->translatedFormat('d M Y') }}</span>
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusStyle['pill'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-xs text-admin-ink-soft">
                                    {{ $transaction->created_at?->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5" wire:key="aksi-{{ $transaction->id }}">
                                        <button
                                            type="button"
                                            title="Lihat detail pesanan"
                                            wire:click="openDetail({{ $transaction->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-accent"
                                        >
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-admin-border px-5 py-3.5">
                {{ $transactions->onEachSide(1)->links() }}
            </div>
        </div>
    @endif

    {{-- ================= MODAL: DETAIL PESANAN (read-only) ================= --}}
    @if ($showDetail && $detailItem)
        @php
            $detailStatusStyle = match ($detailItem->status) {
                'pending' => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
                'confirmed' => ['dot' => 'bg-blue-500', 'pill' => 'bg-blue-50 text-blue-600'],
                'processing' => ['dot' => 'bg-violet-500', 'pill' => 'bg-violet-50 text-violet-600'],
                'preparing' => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-600'],
                'completed' => ['dot' => 'bg-admin-success', 'pill' => 'bg-admin-success/10 text-admin-success'],
                'cancelled' => ['dot' => 'bg-admin-danger', 'pill' => 'bg-admin-danger/10 text-admin-danger'],
                default => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
            };
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div wire:click="closeDetail" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div class="admin-scroll relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-admin-surface shadow-2xl">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-admin-border bg-admin-surface px-6 py-4">
                    <h3 class="font-display text-lg font-semibold text-admin-ink">Detail Pesanan</h3>
                    <button type="button" wire:click="closeDetail" class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Kode Pesanan</p>
                            <p class="mt-0.5 text-sm font-semibold text-admin-ink">{{ $detailItem->order_code }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $detailStatusStyle['pill'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $detailStatusStyle['dot'] }}"></span>
                            {{ ucfirst($detailItem->status) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 rounded-xl border border-admin-border bg-admin-canvas p-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Customer</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Produk</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->product?->nama ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Jumlah</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->quantity }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Total</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">Rp{{ number_format((float) $detailItem->total, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Nomor Antrean</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">
                                {{ $detailItem->queue_number ? '#'.$detailItem->queue_number : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Tanggal Pesanan</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailItem->created_at?->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">Tracking Token</p>
                        <p class="mt-1 break-all rounded-lg bg-admin-canvas px-3 py-2 font-mono text-xs text-admin-ink-soft">
                            {{ $detailItem->tracking_token ?? '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-admin-border px-6 py-4">
                    <button type="button" wire:click="closeDetail" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL: TAMBAH PESANAN =================
         Kerangka form saja untuk tahap ini — belum ada wire:model /
         wire:submit, sesuai instruksi (logic submit menyusul tahap
         berikutnya).
    ====================================================== --}}
    @if ($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div wire:click="closeAddModal" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div class="admin-scroll relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-admin-surface shadow-2xl">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-admin-border bg-admin-surface px-6 py-4">
                    <h3 class="font-display text-lg font-semibold text-admin-ink">Tambah Pesanan</h3>
                    <button type="button" wire:click="closeAddModal" class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <p class="rounded-xl border border-dashed border-admin-border bg-admin-canvas px-4 py-3 text-xs leading-relaxed text-admin-ink-soft">
                        <i class="fa-solid fa-circle-info mr-1.5 text-admin-accent"></i>
                        Form ini belum tersambung ke logic simpan — kerangka tampilan saja untuk tahap berikutnya.
                    </p>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Nama Customer</label>
                        <input
                            type="text"
                            title="Belum berfungsi"
                            placeholder="Nama pelanggan..."
                            class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Produk</label>
                        <div class="relative">
                            <select
                                title="Belum berfungsi"
                                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 pr-8 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                            >
                                <option>Pilih produk...</option>
                                @foreach ($produkList as $produkItem)
                                    <option>{{ $produkItem->nama }}</option>
                                @endforeach
                            </select>
                            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Jumlah</label>
                            <input
                                type="number"
                                title="Belum berfungsi"
                                min="1"
                                placeholder="1"
                                class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                            >
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Total (Rp)</label>
                            <input
                                type="number"
                                title="Belum berfungsi"
                                placeholder="0"
                                class="w-full rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-admin-ink">Status</label>
                        <div class="relative">
                            <select
                                title="Belum berfungsi"
                                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas px-4 py-2.5 pr-8 text-sm text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
                            >
                                @foreach (\App\Models\Transaction::STATUSES as $statusOption)
                                    <option {{ $statusOption === 'pending' ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-admin-border px-6 py-4">
                    <button type="button" wire:click="closeAddModal" class="rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        Batal
                    </button>
                    <button
                        type="button"
                        disabled
                        title="Logic simpan belum tersedia di tahap ini"
                        class="cursor-not-allowed rounded-full bg-admin-panel/40 px-5 py-2.5 text-sm font-semibold text-white/70"
                    >
                        Simpan Pesanan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>