<?php

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin-panel')] #[Title('Laporan')] class extends Component
{
    /** Opsi periode yang valid untuk filter laporan. */
    private const PERIODS = ['7', '30', '90', 'this_month', 'last_month', 'this_year', 'all', 'custom'];

    /** Periode aktif â€” default 30 hari terakhir. */
    public string $period = '30';

    /** Tanggal mulai & selesai untuk periode 'custom' (dari <input type="date">, format Y-m-d). */
    public ?string $customStart = null;

    public ?string $customEnd = null;

    /** Isi default rentang custom (30 hari terakhir) supaya input tidak pernah kosong saat pertama dipilih. */
    public function mount(): void
    {
        $this->customEnd = Carbon::now()->toDateString();
        $this->customStart = Carbon::now()->subDays(29)->toDateString();
    }

    public function updatedPeriod(string $value): void
    {
        if (! in_array($value, self::PERIODS, true)) {
            $this->period = '30';
        }
    }

    /** Format angka rupiah ringkas ala dashboard (1.250.000 -> "Rp1,25jt"). */
    private function formatCompact(float $value): string
    {
        if ($value >= 1_000_000) {
            return 'Rp'.number_format($value / 1_000_000, 2, ',', '.').'jt';
        }

        if ($value >= 1_000) {
            return 'Rp'.number_format($value / 1_000, 0, ',', '.').'rb';
        }

        return 'Rp'.number_format($value, 0, ',', '.');
    }

    private function formatRupiah(float $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }

    /** Warna & label badge status â€” konsisten dengan halaman Pesanan/History Pesanan. */
    private function statusStyle(string $status): array
    {
        return match ($status) {
            'pending' => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
            'confirmed' => ['dot' => 'bg-blue-500', 'pill' => 'bg-blue-50 text-blue-600'],
            'processing' => ['dot' => 'bg-violet-500', 'pill' => 'bg-violet-50 text-violet-600'],
            'preparing' => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-600'],
            'completed' => ['dot' => 'bg-admin-success', 'pill' => 'bg-admin-success/10 text-admin-success'],
            'cancelled' => ['dot' => 'bg-admin-danger', 'pill' => 'bg-admin-danger/10 text-admin-danger'],
            default => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-600'],
        };
    }

    /**
     * Tentukan rentang tanggal [start, end, label] sesuai periode yang aktif.
     * start/end selalu mencakup 1 hari penuh (00:00:00 - 23:59:59) supaya
     * transaksi di batas hari tidak pernah miss dari perhitungan.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveDateRange(): array
    {
        $now = Carbon::now();

        return match ($this->period) {
            '7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '7 Hari Terakhir'],
            '90' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay(), '90 Hari Terakhir'],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'Bulan Ini'],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'Bulan Lalu',
            ],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay(), 'Tahun Ini'],
            'all' => [$this->earliestTransactionDate(), $now->copy()->endOfDay(), 'Sepanjang Waktu'],
            'custom' => $this->resolveCustomRange($now),
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), '30 Hari Terakhir'],
        };
    }

    /** Tanggal transaksi paling lama â€” dipakai sebagai titik awal periode "Sepanjang Waktu". */
    private function earliestTransactionDate(): Carbon
    {
        $earliest = Transaction::query()->oldest('created_at')->value('created_at');

        return $earliest ? Carbon::parse($earliest)->startOfDay() : Carbon::now()->startOfDay();
    }

    /**
     * Validasi & normalisasi input tanggal periode 'custom': tukar posisi
     * kalau start > end, dan jangan pernah biarkan end melewati hari ini.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveCustomRange(Carbon $now): array
    {
        try {
            $start = $this->customStart ? Carbon::parse($this->customStart)->startOfDay() : $now->copy()->subDays(29)->startOfDay();
        } catch (\Throwable) {
            $start = $now->copy()->subDays(29)->startOfDay();
        }

        try {
            $end = $this->customEnd ? Carbon::parse($this->customEnd)->endOfDay() : $now->copy()->endOfDay();
        } catch (\Throwable) {
            $end = $now->copy()->endOfDay();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        if ($end->greaterThan($now)) {
            $end = $now->copy()->endOfDay();
        }

        return [$start, $end, 'Kustom: '.$start->translatedFormat('d M Y').' â€“ '.$end->translatedFormat('d M Y')];
    }

    /**
     * Bangun titik-titik grafik penjualan (pendapatan transaksi completed)
     * sepanjang rentang tanggal â€” otomatis dikelompokkan per hari (<=31 hari),
     * per bulan (<=730 hari), atau per tahun (di atas itu), supaya grafik
     * tetap terbaca baik untuk periode pendek maupun "Sepanjang Waktu".
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function buildSalesSeries(Carbon $start, Carbon $end): array
    {
        $totalDays = $start->diffInDays($end) + 1;
        $points = collect();

        if ($totalDays <= 31) {
            $cursor = $start->copy()->startOfDay();

            while ($cursor->lessThanOrEqualTo($end)) {
                $total = Transaction::query()
                    ->where('status', 'completed')
                    ->whereDate('created_at', $cursor->toDateString())
                    ->sum('total');

                $points->push(['label' => $cursor->translatedFormat('d M'), 'total' => (float) $total]);
                $cursor->addDay();
            }
        } elseif ($totalDays <= 730) {
            $cursor = $start->copy()->startOfMonth();
            $lastMonth = $end->copy()->startOfMonth();

            while ($cursor->lessThanOrEqualTo($lastMonth)) {
                $rangeStart = $cursor->copy()->startOfMonth()->max($start);
                $rangeEnd = $cursor->copy()->endOfMonth()->min($end);

                $total = Transaction::query()
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                    ->sum('total');

                $points->push(['label' => $cursor->translatedFormat('M Y'), 'total' => (float) $total]);
                $cursor->addMonthNoOverflow();
            }
        } else {
            $cursor = $start->copy()->startOfYear();
            $lastYear = $end->copy()->startOfYear();

            while ($cursor->lessThanOrEqualTo($lastYear)) {
                $rangeStart = $cursor->copy()->startOfYear()->max($start);
                $rangeEnd = $cursor->copy()->endOfYear()->min($end);

                $total = Transaction::query()
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                    ->sum('total');

                $points->push(['label' => $cursor->format('Y'), 'total' => (float) $total]);
                $cursor->addYear();
            }
        }

        $maxTotal = max(1, (float) $points->max('total'));
        $axisMax = max(100_000, ceil(($maxTotal * 1.2) / 50_000) * 50_000);

        $points = $points->map(function (array $point) use ($axisMax) {
            $point['heightPercent'] = round(($point['total'] / $axisMax) * 100, 1);

            return $point;
        });

        $axisSteps = collect([1, 0.75, 0.5, 0.25, 0])
            ->map(fn ($fraction) => $this->formatCompact($axisMax * $fraction));

        return [$points, $axisSteps];
    }

    /**
     * Kumpulkan seluruh data laporan untuk satu rentang tanggal. Dipakai
     * bersama oleh with() (tampilan layar) dan export() (unduhan CSV) â€”
     * supaya keduanya SELALU menghitung dari logika yang persis sama dan
     * angka yang tampil di layar tidak pernah berbeda dengan file yang
     * diunduh.
     */
    private function buildReportData(Carbon $start, Carbon $end): array
    {
        $periodQuery = fn () => Transaction::query()->whereBetween('created_at', [$start, $end]);
        $completedQuery = fn () => (clone $periodQuery())->where('status', 'completed');

        $revenue = (float) $completedQuery()->sum('total');
        $completedCount = $completedQuery()->count();
        $unitsSold = (int) $completedQuery()->sum('quantity');
        $averageOrderValue = $completedCount > 0 ? $revenue / $completedCount : 0.0;

        $totalOrders = $periodQuery()->count();
        $cancelledCount = (clone $periodQuery())->where('status', 'cancelled')->count();

        $statusBreakdown = collect(Transaction::STATUSES)
            ->map(fn (string $status) => [
                'status' => $status,
                'label' => ucfirst($status),
                'count' => (clone $periodQuery())->where('status', $status)->count(),
                'style' => $this->statusStyle($status),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values();

        $topProducts = Transaction::query()
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where('transactions.status', 'completed')
            ->join('products', 'products.id', '=', 'transactions.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('products.id as product_id, products.nama as product_nama, categories.name as category_name, SUM(transactions.quantity) as qty, SUM(transactions.total) as total')
            ->groupBy('products.id', 'products.nama', 'categories.name')
            ->orderByDesc('qty')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $categoryPerformance = Transaction::query()
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where('transactions.status', 'completed')
            ->join('products', 'products.id', '=', 'transactions.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw("COALESCE(categories.name, 'Tanpa Kategori') as category_name, SUM(transactions.total) as total, SUM(transactions.quantity) as qty")
            ->groupBy('category_name')
            ->orderByDesc('total')
            ->get();

        $categoryPalette = ['var(--color-admin-accent)', 'var(--color-admin-gold)', 'var(--color-admin-panel)', '#B7AFA3'];
        $categorySum = max(1, (float) $categoryPerformance->sum('total'));

        $donutSegments = $categoryPerformance->take(4)->values()->map(function ($row, int $index) use ($categorySum, $categoryPalette) {
            return [
                'label' => $row->category_name,
                'percent' => round(((float) $row->total / $categorySum) * 100),
                'color' => $categoryPalette[$index] ?? '#B7AFA3',
            ];
        });

        $cursor = 0;
        $donutGradient = $donutSegments->map(function (array $segment) use (&$cursor) {
            $segStart = $cursor;
            $cursor += $segment['percent'];

            return "{$segment['color']} {$segStart}% {$cursor}%";
        })->implode(', ');

        [$chartPoints, $chartAxisSteps] = $this->buildSalesSeries($start, $end);

        return [
            'revenue' => $revenue,
            'completedCount' => $completedCount,
            'unitsSold' => $unitsSold,
            'averageOrderValue' => $averageOrderValue,
            'totalOrders' => $totalOrders,
            'cancelledCount' => $cancelledCount,
            'statusBreakdown' => $statusBreakdown,
            'topProducts' => $topProducts,
            'categoryPerformance' => $categoryPerformance,
            'donutSegments' => $donutSegments,
            'donutGradient' => $donutGradient !== '' ? $donutGradient : 'var(--color-admin-border) 0% 100%',
            'chartPoints' => $chartPoints,
            'chartAxisSteps' => $chartAxisSteps,
        ];
    }

    public function with(): array
    {
        [$start, $end, $periodLabel] = $this->resolveDateRange();
        $data = $this->buildReportData($start, $end);

        $cards = [
            [
                'label' => 'Pendapatan',
                'value' => $this->formatRupiah($data['revenue']),
                'icon' => 'fa-sack-dollar',
                'highlight' => true,
            ],
            [
                'label' => 'Pesanan Selesai',
                'value' => number_format($data['completedCount'], 0, ',', '.').' pesanan',
                'icon' => 'fa-receipt',
                'highlight' => false,
            ],
            [
                'label' => 'Produk Terjual',
                'value' => number_format($data['unitsSold'], 0, ',', '.').' unit',
                'icon' => 'fa-box-open',
                'highlight' => false,
            ],
            [
                'label' => 'Rata-rata Nilai Pesanan',
                'value' => $this->formatRupiah($data['averageOrderValue']),
                'icon' => 'fa-chart-line',
                'highlight' => false,
            ],
        ];

        return array_merge($data, [
            'periodLabel' => $periodLabel,
            'rangeStart' => $start,
            'rangeEnd' => $end,
            'cards' => $cards,
        ]);
    }

    /**
     * Ekspor laporan periode aktif sebagai file CSV (ringkasan + daftar
     * lengkap Produk Terlaris) â€” dihitung dari method yang SAMA dengan
     * yang menampilkan angka di layar (buildReportData()), supaya file
     * yang diunduh tidak pernah berbeda dengan yang terlihat admin.
     */
    public function export()
    {
        [$start, $end, $periodLabel] = $this->resolveDateRange();
        $data = $this->buildReportData($start, $end);

        $filename = 'laporan-penjualan-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($data, $periodLabel, $start, $end) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 supaya "Rp" dan nama produk tetap tampil benar saat file dibuka di Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Laporan Penjualan - Karya Ide Edi']);
            fputcsv($handle, ['Periode', $periodLabel]);
            fputcsv($handle, ['Rentang Tanggal', $start->translatedFormat('d M Y').' - '.$end->translatedFormat('d M Y')]);
            fputcsv($handle, []);

            fputcsv($handle, ['Ringkasan Penjualan']);
            fputcsv($handle, ['Metrik', 'Nilai']);
            fputcsv($handle, ['Pendapatan (Rp)', number_format($data['revenue'], 0, '', '')]);
            fputcsv($handle, ['Pesanan Selesai', $data['completedCount']]);
            fputcsv($handle, ['Produk Terjual (unit)', $data['unitsSold']]);
            fputcsv($handle, ['Rata-rata Nilai Pesanan (Rp)', number_format($data['averageOrderValue'], 0, '', '')]);
            fputcsv($handle, ['Total Pesanan Masuk', $data['totalOrders']]);
            fputcsv($handle, ['Pesanan Dibatalkan', $data['cancelledCount']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Produk Terlaris']);
            fputcsv($handle, ['Peringkat', 'Produk', 'Kategori', 'Qty Terjual', 'Total Pendapatan (Rp)']);

            foreach ($data['topProducts'] as $index => $product) {
                fputcsv($handle, [
                    $index + 1,
                    $product->product_nama,
                    $product->category_name ?? 'Tanpa Kategori',
                    (int) $product->qty,
                    number_format((float) $product->total, 0, '', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
};
?>

<div class="space-y-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-xl font-semibold text-admin-ink sm:text-2xl">
                Laporan
            </h2>
            <p class="mt-1 text-sm text-admin-ink-soft">
                Ringkasan penjualan, produk terlaris, dan performa toko dari waktu ke waktu.
            </p>
        </div>
        <button
            type="button"
            wire:click="export"
            wire:loading.attr="disabled"
            wire:target="export"
            class="inline-flex items-center justify-center gap-2 rounded-full bg-admin-panel px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-(--color-admin-panel)/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-admin-accent-strong disabled:opacity-60"
        >
            <i class="fa-solid fa-file-arrow-down text-xs" wire:loading.remove wire:target="export"></i>
            <i class="fa-solid fa-spinner fa-spin text-xs" wire:loading wire:target="export"></i>
            Ekspor Laporan
        </button>
    </div>

    {{-- ================= FILTER PERIODE ================= --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-admin-border bg-admin-surface p-4 shadow-sm lg:flex-row lg:flex-wrap lg:items-center">
        <div class="relative lg:w-60 lg:shrink-0">
            <i class="fa-solid fa-calendar-days pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-admin-accent"></i>
            <select
                wire:model.live="period"
                class="w-full cursor-pointer appearance-none rounded-xl border border-admin-border bg-admin-canvas py-2.5 pl-8 pr-8 text-xs font-medium text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 sm:text-sm"
            >
                <option value="7">7 Hari Terakhir</option>
                <option value="30">30 Hari Terakhir</option>
                <option value="90">90 Hari Terakhir</option>
                <option value="this_month">Bulan Ini</option>
                <option value="last_month">Bulan Lalu</option>
                <option value="this_year">Tahun Ini</option>
                <option value="all">Sepanjang Waktu</option>
                <option value="custom">Kustom</option>
            </select>
            <x-icon-arrow direction="chevron-down" size="text-[10px]" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-admin-ink-soft" />
        </div>

        @if ($period === 'custom')
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input
                    type="date"
                    wire:model.live="customStart"
                    class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3 py-2.5 text-xs font-medium text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 sm:text-sm"
                >
                <span class="hidden text-xs text-admin-ink-soft sm:inline">s/d</span>
                <input
                    type="date"
                    wire:model.live="customEnd"
                    class="w-full rounded-xl border border-admin-border bg-admin-canvas px-3 py-2.5 text-xs font-medium text-admin-ink focus:border-admin-accent focus:outline-none focus:ring-2 focus:ring-admin-accent/15 sm:text-sm"
                >
            </div>
        @endif

        <p class="text-xs text-admin-ink-soft lg:ml-auto">
            Menampilkan data: <span class="font-semibold text-admin-ink">{{ $periodLabel }}</span>
        </p>
    </div>

    {{-- ================= RINGKASAN PENJUALAN ================= --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
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

    {{-- ================= GRAFIK PENJUALAN & KOMPOSISI KATEGORI ================= --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- grafik penjualan periode --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-admin-ink">Grafik Penjualan</p>
                    <p class="mt-1 text-xs text-admin-ink-soft">{{ $periodLabel }}</p>
                </div>
                <span class="text-xl font-bold text-admin-ink">{{ $this->formatRupiah($revenue) }}</span>
            </div>

            @if ($chartPoints->sum('total') <= 0)
                <div class="mt-8 flex h-40 items-center justify-center text-center text-xs text-admin-ink-soft">
                    Belum ada transaksi selesai pada periode ini.
                </div>
            @else
                <div class="mt-6 flex items-end gap-3 sm:gap-4">
                    <div class="flex h-40 flex-col justify-between pb-6 text-right text-[10px] text-admin-ink-soft">
                        @foreach ($chartAxisSteps as $step)
                            <span>{{ $step }}</span>
                        @endforeach
                    </div>
                    <div class="admin-scroll flex-1 overflow-x-auto">
                        <div class="flex h-40 items-end gap-2" style="min-width: {{ max(100, $chartPoints->count() * 32) }}px">
                            @foreach ($chartPoints as $point)
                                <div class="group relative flex h-40 w-7 shrink-0 flex-col items-center justify-end gap-2 sm:w-8">
                                    <div class="relative flex h-full w-full items-end">
                                        <div
                                            style="height: {{ max(3, $point['heightPercent']) }}%"
                                            class="w-full rounded-t-lg bg-admin-border transition-all duration-500 group-hover:bg-admin-accent"
                                        ></div>
                                        <div class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-admin-panel px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                            {{ $this->formatCompact($point['total']) }}
                                        </div>
                                    </div>
                                    <span class="w-full truncate text-center text-[9px] text-admin-ink-soft">{{ $point['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- komposisi kategori --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md">
            <p class="text-sm font-semibold text-admin-ink">Komposisi Kategori</p>
            <p class="text-xs text-admin-ink-soft">Pendapatan pada periode ini</p>

            <div class="mt-6 flex items-center justify-center">
                <div
                    class="relative flex h-40 w-40 items-center justify-center rounded-full"
                    style="background: conic-gradient({{ $donutGradient }})"
                >
                    <div class="flex h-28 w-28 flex-col items-center justify-center rounded-full bg-admin-surface text-center">
                        <span class="text-sm font-bold text-admin-ink">{{ $this->formatCompact($revenue) }}</span>
                        <span class="text-[10px] text-admin-ink-soft">total pendapatan</span>
                    </div>
                </div>
            </div>

            <ul class="mt-6 space-y-2.5">
                @forelse ($donutSegments as $segment)
                    <li class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-2 text-admin-ink-soft">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $segment['color'] }}"></span>
                            {{ $segment['label'] }}
                        </span>
                        <span class="font-semibold text-admin-ink">{{ $segment['percent'] }}%</span>
                    </li>
                @empty
                    <li class="text-center text-xs text-admin-ink-soft">Belum ada transaksi selesai pada periode ini.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ================= STATUS PESANAN & PRODUK TERLARIS ================= --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- status pesanan --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md">
            <p class="text-sm font-semibold text-admin-ink">Status Pesanan</p>
            <p class="text-xs text-admin-ink-soft">Dari {{ number_format($totalOrders, 0, ',', '.') }} pesanan pada periode ini</p>

            <div class="mt-5 space-y-4">
                @forelse ($statusBreakdown as $row)
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 font-medium text-admin-ink">
                                <span class="h-1.5 w-1.5 rounded-full {{ $row['style']['dot'] }}"></span>
                                {{ $row['label'] }}
                            </span>
                            <span class="text-admin-ink-soft">{{ $row['count'] }}</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-admin-cream">
                            <div
                                class="h-full rounded-full {{ $row['style']['dot'] }}"
                                style="width: {{ $totalOrders > 0 ? round(($row['count'] / $totalOrders) * 100) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-admin-ink-soft">Belum ada pesanan pada periode ini.</p>
                @endforelse
            </div>
        </div>

        {{-- produk terlaris --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md xl:col-span-2">
            <p class="text-sm font-semibold text-admin-ink">Produk Terlaris</p>
            <p class="text-xs text-admin-ink-soft">Berdasarkan pesanan selesai pada periode ini</p>

            <ul class="mt-5 divide-y divide-admin-border">
                @forelse ($topProducts as $index => $product)
                    <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-admin-cream text-xs font-semibold text-admin-accent">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-admin-ink">{{ $product->product_nama }}</p>
                            <p class="text-xs text-admin-ink-soft">{{ $product->category_name ?? 'Tanpa Kategori' }} &middot; {{ $this->formatRupiah((float) $product->total) }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-admin-cream px-2.5 py-1 text-[11px] font-semibold text-admin-accent">
                            {{ (int) $product->qty }} terjual
                        </span>
                    </li>
                @empty
                    <li class="py-6 text-center text-xs text-admin-ink-soft">Belum ada produk terjual pada periode ini.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
