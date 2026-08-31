<?php

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin-panel')] #[Title('Pelanggan')] class extends Component
{
    use WithPagination;

    public string $search = '';

    /** 'recent' (pesanan terakhir), 'spend' (belanja terbesar), 'orders' (pesanan terbanyak). */
    public string $sortBy = 'recent';

    public bool $showDetail = false;

    /** Key pelanggan (nomor WA ternormalisasi, atau "tanpa-wa:<nama>") yang sedang dibuka detailnya. */
    public ?string $detailKey = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function openDetail(string $key): void
    {
        $this->detailKey = $key;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailKey = null;
    }

    /**
     * Samakan format nomor WhatsApp jadi digit internasional (62xxxxxxxxxx).
     * Ini memastikan "081234567890", "+62 812-3456-7890", dan "62812..."
     * milik orang yang sama dikenali sebagai SATU pelanggan yang sama —
     * bukan tiga baris terpisah hanya karena beda cara ketik nomor.
     */
    private static function normalizeWhatsapp(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Toko ini TIDAK punya akun/login untuk pembeli (tidak ada tabel
     * customers) — jejak identitas pelanggan hanya berupa customer_name +
     * whatsapp yang diisi ulang di setiap baris transactions (booking atau
     * pesanan admin). Jadi "Pelanggan" di halaman ini dibentuk dengan
     * MENGELOMPOKKAN seluruh transaksi berdasarkan nomor WhatsApp
     * ternormalisasi (fallback ke nama kalau WhatsApp kosong), bukan query
     * ke tabel tersendiri.
     *
     * Pengelompokan dilakukan di level Collection (bukan GROUP BY SQL)
     * supaya normalisasi nomor WhatsApp di atas bisa dipakai sebagai kunci
     * tanpa bergantung pada ekspresi SQL yang berisiko beda hasil antar
     * driver database.
     */
    private function buildCustomers()
    {
        return Transaction::query()
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(function (Transaction $transaction) {
                return self::normalizeWhatsapp($transaction->whatsapp)
                    ?? 'tanpa-wa:'.Str::lower(trim($transaction->customer_name));
            })
            ->map(function ($transactions, string $key) {
                // Sudah terurut created_at DESC dari query awal.
                $latest = $transactions->first();
                $oldest = $transactions->last();
                $completed = $transactions->where('status', 'completed');
                $ordersCount = $transactions->count();

                $waDigits = self::normalizeWhatsapp($latest->whatsapp);
                $waLink = $waDigits
                    ? 'https://wa.me/'.$waDigits.'?text='.urlencode(
                        "Halo {$latest->customer_name}, terima kasih sudah mempercayai Karya Ide Edi. Ada yang bisa kami bantu seputar pesanan Anda?"
                    )
                    : null;

                $tier = match (true) {
                    $ordersCount >= 3 => ['label' => 'Pelanggan Setia', 'icon' => 'fa-crown', 'class' => 'bg-linear-to-r from-admin-gold to-admin-accent-strong text-white'],
                    $ordersCount === 2 => ['label' => 'Berulang', 'icon' => 'fa-arrow-rotate-right', 'class' => 'bg-blue-50 text-blue-600'],
                    default => ['label' => 'Baru', 'icon' => 'fa-seedling', 'class' => 'bg-admin-cream text-admin-ink-soft'],
                };

                $initial = mb_strtoupper(mb_substr(trim($latest->customer_name), 0, 1)) ?: '?';

                return (object) [
                    'key' => $key,
                    'name' => $latest->customer_name,
                    'initial' => $initial,
                    'whatsapp' => $latest->whatsapp,
                    'wa_link' => $waLink,
                    'orders_count' => $ordersCount,
                    'completed_count' => $completed->count(),
                    'total_spend' => (float) $completed->sum('total'),
                    'last_order_at' => $latest->created_at,
                    'first_order_at' => $oldest->created_at,
                    'tier' => $tier,
                    'transactions' => $transactions->values(),
                ];
            })
            ->values();
    }

    public function with(): array
    {
        $allCustomers = $this->buildCustomers();

        $startOfMonth = Carbon::now()->startOfMonth();

        $totalPelanggan = $allCustomers->count();
        $pelangganBerulang = $allCustomers->filter(fn ($c) => $c->orders_count > 1)->count();
        $pelangganBaruBulanIni = $allCustomers->filter(
            fn ($c) => $c->first_order_at?->greaterThanOrEqualTo($startOfMonth)
        )->count();
        $totalBelanjaTervalidasi = $allCustomers->sum('total_spend');

        $customers = $allCustomers;

        if ($this->search !== '') {
            $keyword = Str::lower(trim($this->search));
            $customers = $customers->filter(function ($c) use ($keyword) {
                $waDigitsOnly = $c->whatsapp ? preg_replace('/\D/', '', $c->whatsapp) : '';

                return str_contains(Str::lower($c->name), $keyword)
                    || str_contains($waDigitsOnly, preg_replace('/\D/', '', $keyword));
            })->values();
        }

        $customers = (match ($this->sortBy) {
            'spend' => $customers->sortByDesc('total_spend'),
            'orders' => $customers->sortByDesc('orders_count'),
            default => $customers->sortByDesc('last_order_at'),
        })->values();

        $perPage = 10;
        $page = $this->getPage();

        $paginatedCustomers = new LengthAwarePaginator(
            $customers->forPage($page, $perPage)->values(),
            $customers->count(),
            $perPage,
            $page,
            ['pageName' => 'page'],
        );

        return [
            'customers' => $paginatedCustomers,
            'totalPelanggan' => $totalPelanggan,
            'pelangganBerulang' => $pelangganBerulang,
            'pelangganBaruBulanIni' => $pelangganBaruBulanIni,
            'totalBelanjaTervalidasi' => $totalBelanjaTervalidasi,
            'detailCustomer' => $this->showDetail && $this->detailKey
                ? $allCustomers->firstWhere('key', $this->detailKey)
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
                Manajemen Pelanggan
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                {{ $totalPelanggan }} pelanggan terekam dari riwayat pesanan &amp; booking.
            </p>
        </div>
        <a
            href="{{ route('admin.transactions.history') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-full border border-admin-border px-5 py-2.5 text-sm font-semibold text-admin-ink-soft transition-all duration-300 hover:-translate-y-0.5 hover:border-admin-accent hover:text-admin-accent"
        >
            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
            Lihat History Pesanan
        </a>
    </div>

    {{-- ================= STAT CARDS ================= --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $statCards = [
                [
                    'label' => 'Total Belanja Tervalidasi',
                    'value' => 'Rp'.number_format($totalBelanjaTervalidasi, 0, ',', '.'),
                    'icon' => 'fa-sack-dollar',
                    'highlight' => true,
                ],
                [
                    'label' => 'Total Pelanggan',
                    'value' => number_format($totalPelanggan, 0, ',', '.').' orang',
                    'icon' => 'fa-users',
                    'highlight' => false,
                ],
                [
                    'label' => 'Pelanggan Berulang',
                    'value' => number_format($pelangganBerulang, 0, ',', '.').' orang',
                    'icon' => 'fa-arrow-rotate-right',
                    'highlight' => false,
                ],
                [
                    'label' => 'Pelanggan Baru Bulan Ini',
                    'value' => number_format($pelangganBaruBulanIni, 0, ',', '.').' orang',
                    'icon' => 'fa-seedling',
                    'highlight' => false,
                ],
            ];
        @endphp

        @foreach ($statCards as $card)
            <div class="group relative overflow-hidden rounded-[1.25rem] border p-5 transition-all duration-300 ease-out hover:-translate-y-1
                {{ $card['highlight']
                    ? 'border-admin-accent-strong/40 bg-linear-to-br from-admin-accent via-admin-accent to-admin-accent-strong text-white shadow-lg shadow-(--color-admin-accent)/25 hover:shadow-xl hover:shadow-(--color-admin-accent)/35'
                    : 'border-admin-border bg-admin-surface text-admin-ink shadow-sm hover:border-admin-accent/40 hover:shadow-lg hover:shadow-(--color-admin-accent)/10' }}">

                <div class="absolute inset-x-0 top-0 h-0.75 bg-linear-to-r from-admin-gold via-admin-accent-strong to-admin-gold
                    {{ $card['highlight'] ? 'opacity-90' : 'opacity-0 transition-opacity duration-300 group-hover:opacity-100' }}"></div>

                <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full opacity-0 blur-2xl transition-opacity duration-500 group-hover:opacity-100
                    {{ $card['highlight'] ? 'bg-white/20' : 'bg-admin-gold/20' }}"></div>

                <div class="relative flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wide {{ $card['highlight'] ? 'text-white/75' : 'text-admin-ink-soft' }}">
                        {{ $card['label'] }}
                    </span>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-105 group-hover:-rotate-3
                        {{ $card['highlight'] ? 'bg-white/15 ring-1 ring-white/25' : 'bg-admin-cream ring-1 ring-admin-border' }}">
                        <i class="fa-solid {{ $card['icon'] }} text-sm {{ $card['highlight'] ? 'text-white' : 'text-admin-accent' }}"></i>
                    </span>
                </div>

                <p class="relative mt-5 font-display text-2xl font-bold tracking-tight sm:text-[1.7rem] {{ $card['highlight'] ? 'text-white' : 'text-admin-ink' }}">
                    {{ $card['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- ================= SEARCH & SORT ================= --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-admin-border bg-admin-surface p-4 shadow-sm lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-admin-ink-soft"></i>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari nama atau nomor WhatsApp pelanggan..."
                class="w-full rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-10 pr-4 text-sm text-admin-ink placeholder:text-admin-ink-soft focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15"
            >
        </div>

        <div class="relative lg:w-64 lg:shrink-0">
            <i class="fa-solid fa-arrow-down-wide-short pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-admin-accent"></i>
            <select
                wire:model.live="sortBy"
                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-8 pr-8 text-xs font-medium text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 sm:text-sm"
            >
                <option value="recent">Urutkan: Pesanan Terakhir</option>
                <option value="spend">Urutkan: Belanja Terbesar</option>
                <option value="orders">Urutkan: Pesanan Terbanyak</option>
            </select>
            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
        </div>
    </div>

    {{-- ================= TABEL PELANGGAN ================= --}}
    @if ($customers->isEmpty())
        <div class="relative overflow-hidden rounded-2xl border border-admin-border bg-admin-surface px-6 py-20 text-center shadow-sm">
            <div class="pointer-events-none absolute inset-0 opacity-[0.4]" style="background-image: radial-gradient(circle at 1px 1px, var(--color-admin-border) 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-(--color-admin-accent)/20">
                    <i class="fa-solid fa-users text-xl text-white"></i>
                </span>
                <p class="mt-5 font-display text-base font-semibold text-admin-ink">
                    {{ $search !== '' ? 'Pelanggan tidak ditemukan' : 'Belum ada pelanggan' }}
                </p>
                <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-admin-ink-soft">
                    {{ $search !== ''
                        ? 'Coba kata kunci pencarian lain.'
                        : 'Pelanggan akan otomatis muncul di sini begitu ada pesanan atau booking yang masuk.' }}
                </p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-admin-border bg-admin-surface shadow-sm">
            <div class="admin-scroll overflow-x-auto">
                <table class="w-full min-w-220 text-left text-sm">
                    <thead>
                        <tr class="border-b border-admin-border bg-admin-canvas text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            <th class="px-5 py-3 font-semibold">Pelanggan</th>
                            <th class="px-3 py-3 font-semibold">WhatsApp</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 font-semibold">Jumlah Pesanan</th>
                            <th class="px-3 py-3 font-semibold">Total Belanja</th>
                            <th class="px-3 py-3 font-semibold">Pesanan Terakhir</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        @foreach ($customers as $customer)
                            <tr wire:key="pelanggan-row-{{ $customer->key }}" class="transition-colors duration-200 hover:bg-admin-canvas">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-admin-gold to-admin-accent text-xs font-bold text-white shadow-sm">
                                            {{ $customer->initial }}
                                        </span>
                                        <span class="font-medium text-admin-ink">{{ $customer->name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-admin-ink-soft">
                                    {{ $customer->whatsapp ?: '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $customer->tier['class'] }}">
                                        <i class="fa-solid {{ $customer->tier['icon'] }} text-[10px]"></i>
                                        {{ $customer->tier['label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-admin-ink">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-admin-cream px-2.5 py-1 text-[11px] font-semibold text-admin-ink">
                                        {{ $customer->orders_count }} pesanan
                                    </span>
                                </td>
                                <td class="px-3 py-3 font-semibold text-admin-ink">
                                    Rp{{ number_format($customer->total_spend, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-xs text-admin-ink-soft">
                                    {{ $customer->last_order_at?->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5" wire:key="aksi-pelanggan-{{ $customer->key }}">
                                        @if ($customer->wa_link)
                                            <a
                                                href="{{ $customer->wa_link }}"
                                                target="_blank"
                                                rel="noopener"
                                                title="Hubungi via WhatsApp"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-emerald-50 hover:text-emerald-600"
                                            >
                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                            </a>
                                        @endif
                                        <button
                                            type="button"
                                            title="Lihat detail pelanggan"
                                            wire:click="openDetail('{{ $customer->key }}')"
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
                {{ $customers->onEachSide(1)->links() }}
            </div>
        </div>
    @endif

    {{-- ================= MODAL: DETAIL PELANGGAN ================= --}}
    @if ($showDetail && $detailCustomer)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div wire:click="closeDetail" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div class="admin-scroll relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-admin-surface shadow-2xl">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-admin-border bg-admin-surface px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-admin-gold to-admin-accent text-sm font-bold text-white shadow-sm">
                            {{ $detailCustomer->initial }}
                        </span>
                        <div>
                            <h3 class="font-display text-lg font-semibold text-admin-ink">{{ $detailCustomer->name }}</h3>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $detailCustomer->tier['class'] }}">
                                <i class="fa-solid {{ $detailCustomer->tier['icon'] }} text-[9px]"></i>
                                {{ $detailCustomer->tier['label'] }}
                            </span>
                        </div>
                    </div>
                    <button type="button" wire:click="closeDetail" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    {{-- Ringkasan --}}
                    <div class="grid grid-cols-2 gap-4 rounded-xl border border-admin-border bg-admin-canvas p-4 sm:grid-cols-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Total Pesanan</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailCustomer->orders_count }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Selesai</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailCustomer->completed_count }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Total Belanja</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">Rp{{ number_format($detailCustomer->total_spend, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Pelanggan Sejak</p>
                            <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailCustomer->first_order_at?->translatedFormat('d M Y') }}</p>
                        </div>
                    </div>

                    @if ($detailCustomer->whatsapp)
                        <div class="flex items-center justify-between rounded-xl border border-admin-border bg-admin-canvas p-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-admin-ink-soft">Nomor WhatsApp</p>
                                <p class="mt-1 text-sm font-semibold text-admin-ink">{{ $detailCustomer->whatsapp }}</p>
                            </div>
                            @if ($detailCustomer->wa_link)
                                <a
                                    href="{{ $detailCustomer->wa_link }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-emerald-600"
                                >
                                    <i class="fa-brands fa-whatsapp"></i> Hubungi
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Riwayat transaksi --}}
                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-admin-ink-soft">
                            Riwayat Transaksi ({{ $detailCustomer->transactions->count() }})
                        </p>
                        <div class="admin-scroll max-h-72 space-y-2 overflow-y-auto pr-1">
                            @foreach ($detailCustomer->transactions as $transaction)
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
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-admin-border bg-admin-canvas px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-admin-ink">{{ $transaction->order_code }}</p>
                                        <p class="truncate text-xs text-admin-ink-soft">
                                            {{ $transaction->product?->nama ?? '&mdash;' }} &times; {{ $transaction->quantity }}
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusStyle['pill'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                        <p class="text-xs font-semibold text-admin-ink">Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
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
</div>