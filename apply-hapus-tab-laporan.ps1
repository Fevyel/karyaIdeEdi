# ============================================================
# FIX: Hapus tab "Laporan" dari sidebar admin (dianggap 11/12
#      sama dengan Dashboard). Sebelum dihapus, 2 bagian yang
#      PENTING dan TIDAK ADA di Dashboard dipindahkan dulu:
#
# 1) "Status Pesanan" (persentase per status: pending, diproses,
#    dst) -- menggantikan kartu "Statistik Booking" di Dashboard
#    yang selama ini cuma DATA DUMMY/contoh, belum terhubung ke
#    fitur apapun.
# 2) "Produk Terlaris" versi DATA ASLI -- menggantikan kartu
#    "Produk Terlaris" di Dashboard yang selama ini JUGA DATA
#    DUMMY (nama produk hardcode: Sofa Minimalis Oslo dkk, bukan
#    dari database).
#
# Keduanya di Dashboard di-scope "bulan ini", konsisten dengan
# kartu-kartu statistik Dashboard lain (Pendapatan Bulan Ini,
# Produk Terjual, dll).
#
# "Rata-rata Nilai Pesanan" dan tombol "Ekspor Laporan (CSV)"
# TIDAK dipindahkan (dianggap bukan yang paling krusial) --
# kalau ternyata dua itu juga dibutuhkan, tinggal bilang saja.
#
# File yang diubah:
# - resources/views/layouts/admin-panel.blade.php  (hapus item nav Laporan)
# - routes/web.php                                  (hapus route 'reports')
# - resources/views/pages/admin/dashboard.blade.php (tambah Status Pesanan
#   & Produk Terlaris data asli, gantikan yang dummy)
# - resources/views/pages/admin/laporan.blade.php   (DIHAPUS, dengan backup)
#
# Cara pakai (dari VS Code integrated terminal, di root project):
#   .\apply-hapus-tab-laporan.ps1
#
# Setelah itu jalankan: php artisan view:clear
# (murni logic PHP/Blade + routing, tidak ada perubahan Tailwind/JS,
# jadi TIDAK perlu npm run build)
# ============================================================

$ErrorActionPreference = "Stop"

function Replace-ExactlyOnce {
    param(
        [string]$Content,
        [string]$Old,
        [string]$New,
        [string]$Label
    )

    $occurrences = ([regex]::Matches($Content, [regex]::Escape($Old))).Count
    if ($occurrences -eq 0) {
        Write-Host "[ERROR] Bagian '$Label' tidak ditemukan persis di file." -ForegroundColor Red
        Write-Host "        Kemungkinan file ini sudah beda dari yang saya kira -- SAYA BERHENTI, tidak ada yang diubah." -ForegroundColor Red
        exit 1
    }
    if ($occurrences -gt 1) {
        Write-Host "[ERROR] Bagian '$Label' muncul $occurrences kali (harusnya cuma 1)." -ForegroundColor Red
        Write-Host "        SAYA BERHENTI supaya tidak salah ganti bagian yang lain." -ForegroundColor Red
        exit 1
    }

    return $Content.Replace($Old, $New)
}

function Backup-File {
    param([string]$Path, [string]$Suffix)
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupPath = "$Path.bak-$Suffix-$stamp"
    Copy-Item -Path $Path -Destination $backupPath -Force
    Write-Host "  Backup dibuat: $backupPath" -ForegroundColor DarkGray
}

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Hapus Tab Laporan + Pindahkan ke Dashboard" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# 1) Sidebar -- hapus item nav "Laporan"
# ------------------------------------------------------------
Write-Host "[1/4] Menghapus item 'Laporan' dari sidebar ..." -ForegroundColor Yellow

$navPath = "resources\views\layouts\admin-panel.blade.php"
if (-not (Test-Path $navPath)) { throw "Tidak ketemu: $navPath" }
$navContent = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $navPath))

$oldNav = @'
                                $navItem('admin.customers', 'fa-users', 'Pelanggan'),
                                $navItem('admin.reports', 'fa-chart-column', 'Laporan'),
'@
$newNav = @'
                                $navItem('admin.customers', 'fa-users', 'Pelanggan'),
'@

$navContent = Replace-ExactlyOnce -Content $navContent -Old $oldNav -New $newNav -Label "Nav item Laporan"
Backup-File -Path $navPath -Suffix "hapus-tab-laporan"
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $navPath), $navContent, (New-Object System.Text.UTF8Encoding($false)))

# ------------------------------------------------------------
# 2) routes/web.php -- hapus route 'reports'
# ------------------------------------------------------------
Write-Host "[2/4] Menghapus route 'admin.reports' ..." -ForegroundColor Yellow

$routesPath = "routes\web.php"
if (-not (Test-Path $routesPath)) { throw "Tidak ketemu: $routesPath" }
$routesContent = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $routesPath))

$oldRoute = @'
    Route::livewire('/pelanggan', 'pages::admin.pelanggan')->name('customers');
    Route::livewire('/laporan', 'pages::admin.laporan')->name('reports');
'@
$newRoute = @'
    Route::livewire('/pelanggan', 'pages::admin.pelanggan')->name('customers');
'@

$routesContent = Replace-ExactlyOnce -Content $routesContent -Old $oldRoute -New $newRoute -Label "Route admin.reports"
Backup-File -Path $routesPath -Suffix "hapus-tab-laporan"
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $routesPath), $routesContent, (New-Object System.Text.UTF8Encoding($false)))

# ------------------------------------------------------------
# 3) Dashboard -- tambah Status Pesanan & Produk Terlaris (data asli)
# ------------------------------------------------------------
Write-Host "[3/4] Memindahkan Status Pesanan + Produk Terlaris (data asli) ke Dashboard ..." -ForegroundColor Yellow

$dashPath = "resources\views\pages\admin\dashboard.blade.php"
if (-not (Test-Path $dashPath)) { throw "Tidak ketemu: $dashPath" }
$dash = [System.IO.File]::ReadAllText((Join-Path (Get-Location) $dashPath))

# 3a. Tambah helper statusStyle() setelah formatRupiah()
$oldA = @'
    private function formatRupiah(float $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }

    public function with(): array
'@
$newA = @'
    private function formatRupiah(float $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }

    /** Warna & label badge status -- sama seperti halaman Pesanan/History Pesanan. */
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

    public function with(): array
'@
$dash = Replace-ExactlyOnce -Content $dash -Old $oldA -New $newA -Label "Dashboard: helper statusStyle()"

# 3b. Hitung statusBreakdown & topProducts (bulan ini) sebelum return [...]
$oldB = @'
        $donutGradient = $gradientParts->implode(', ');

        return [
'@
$newB = @'
        $donutGradient = $gradientParts->implode(', ');

        // ---- Status Pesanan & Produk Terlaris bulan ini (data asli) -- dipindahkan dari halaman Laporan (dihapus) ----
        $monthlyOrdersQuery = fn () => Transaction::query()->where('created_at', '>=', $startOfMonth);

        $totalOrdersThisMonth = $monthlyOrdersQuery()->count();

        $statusBreakdown = collect(Transaction::STATUSES)
            ->map(fn (string $status) => [
                'status' => $status,
                'label' => ucfirst($status),
                'count' => (clone $monthlyOrdersQuery())->where('status', $status)->count(),
                'style' => $this->statusStyle($status),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values();

        $topProducts = Transaction::query()
            ->where('transactions.created_at', '>=', $startOfMonth)
            ->where('transactions.status', 'completed')
            ->join('products', 'products.id', '=', 'transactions.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('products.id as product_id, products.nama as product_nama, categories.name as category_name, SUM(transactions.quantity) as qty, SUM(transactions.total) as total')
            ->groupBy('products.id', 'products.nama', 'categories.name')
            ->orderByDesc('qty')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        return [
'@
$dash = Replace-ExactlyOnce -Content $dash -Old $oldB -New $newB -Label "Dashboard: query statusBreakdown/topProducts"

# 3c. Tambahkan ke array yang dikembalikan with()
$oldC = @'
            'latestTransactions' => Transaction::query()->with('product')->latest()->take(5)->get(),
            'recentInteraksi' => Testimonial::query()->latest()->take(5)->get(),
        ];
    }
'@
$newC = @'
            'latestTransactions' => Transaction::query()->with('product')->latest()->take(5)->get(),
            'recentInteraksi' => Testimonial::query()->latest()->take(5)->get(),
            'totalOrdersThisMonth' => $totalOrdersThisMonth,
            'statusBreakdown' => $statusBreakdown,
            'topProducts' => $topProducts,
        ];
    }
'@
$dash = Replace-ExactlyOnce -Content $dash -Old $oldC -New $newC -Label "Dashboard: return array with()"

# 3d. Ganti markup "Statistik Booking" + "Produk Terlaris" dummy -> data asli
$oldD = @'
    {{-- ================= STATISTIK BOOKING & PRODUK TERLARIS (dummy) ================= --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- statistik booking — dummy, belum terhubung ke fitur booking --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md">
            <p class="text-sm font-semibold text-admin-ink">Statistik Booking</p>
            <p class="text-xs text-admin-ink-soft">Contoh tampilan, menunggu fitur booking dibangun</p>

            <div class="mt-5 space-y-4">
                @foreach ([
                    ['label' => 'Booking Baru', 'value' => 6, 'total' => 12, 'color' => 'var(--color-admin-accent)'],
                    ['label' => 'Diproses', 'value' => 4, 'total' => 12, 'color' => 'var(--color-admin-gold)'],
                    ['label' => 'Selesai', 'value' => 2, 'total' => 12, 'color' => 'var(--color-admin-panel)'],
                ] as $row)
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-admin-ink">{{ $row['label'] }}</span>
                            <span class="text-admin-ink-soft">{{ $row['value'] }}</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-admin-cream">
                            <div
                                class="h-full rounded-full"
                                style="width: {{ round(($row['value'] / $row['total']) * 100) }}%; background: {{ $row['color'] }}"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- produk terlaris — dummy, belum terhubung ke penjualan riil --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md xl:col-span-2">
            <p class="text-sm font-semibold text-admin-ink">Produk Terlaris</p>
            <p class="text-xs text-admin-ink-soft">Contoh tampilan, menunggu data penjualan riil</p>

            <ul class="mt-5 divide-y divide-admin-border">
                @foreach ([
                    ['name' => 'Sofa Minimalis Oslo', 'category' => 'Ruang Tamu', 'terjual' => 24],
                    ['name' => 'Meja Makan Jati Klasik', 'category' => 'Ruang Makan', 'terjual' => 18],
                    ['name' => 'Lemari Pakaian 3 Pintu', 'category' => 'Kamar Tidur', 'terjual' => 15],
                    ['name' => 'Kursi Kerja Ergonomis', 'category' => 'Kantor', 'terjual' => 11],
                ] as $index => $product)
                    <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-admin-cream text-xs font-semibold text-admin-accent">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-admin-ink">{{ $product['name'] }}</p>
                            <p class="text-xs text-admin-ink-soft">{{ $product['category'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-admin-cream px-2.5 py-1 text-[11px] font-semibold text-admin-accent">
                            {{ $product['terjual'] }} terjual
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
'@
$newD = @'
    {{-- ================= STATUS PESANAN & PRODUK TERLARIS ================= --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- status pesanan bulan ini (data asli) -- dipindahkan dari halaman Laporan (dihapus) --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md">
            <p class="text-sm font-semibold text-admin-ink">Status Pesanan</p>
            <p class="text-xs text-admin-ink-soft">Dari {{ number_format($totalOrdersThisMonth, 0, ',', '.') }} pesanan bulan ini</p>

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
                                style="width: {{ $totalOrdersThisMonth > 0 ? round(($row['count'] / $totalOrdersThisMonth) * 100) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-admin-ink-soft">Belum ada pesanan bulan ini.</p>
                @endforelse
            </div>
        </div>

        {{-- produk terlaris bulan ini (data asli) -- dipindahkan dari halaman Laporan (dihapus) --}}
        <div class="rounded-2xl border border-admin-border bg-admin-surface p-5 shadow-sm transition-shadow duration-300 hover:shadow-md xl:col-span-2">
            <p class="text-sm font-semibold text-admin-ink">Produk Terlaris</p>
            <p class="text-xs text-admin-ink-soft">Berdasarkan pesanan selesai bulan ini</p>

            <ul class="mt-5 divide-y divide-admin-border">
                @forelse ($topProducts as $index => $product)
                    <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-admin-cream text-xs font-semibold text-admin-accent">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-admin-ink">{{ $product->product_nama }}</p>
                            <p class="text-xs text-admin-ink-soft">{{ $product->category_name ?? 'Tanpa Kategori' }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-admin-cream px-2.5 py-1 text-[11px] font-semibold text-admin-accent">
                            {{ (int) $product->qty }} terjual
                        </span>
                    </li>
                @empty
                    <li class="py-6 text-center text-xs text-admin-ink-soft">Belum ada produk terjual bulan ini.</li>
                @endforelse
            </ul>
        </div>
    </div>
'@
$dash = Replace-ExactlyOnce -Content $dash -Old $oldD -New $newD -Label "Dashboard: markup Statistik Booking/Produk Terlaris"

Backup-File -Path $dashPath -Suffix "hapus-tab-laporan"
[System.IO.File]::WriteAllText((Join-Path (Get-Location) $dashPath), $dash, (New-Object System.Text.UTF8Encoding($false)))

# ------------------------------------------------------------
# 4) Hapus file laporan.blade.php (dengan backup)
# ------------------------------------------------------------
Write-Host "[4/4] Menghapus resources/views/pages/admin/laporan.blade.php ..." -ForegroundColor Yellow

$laporanPath = "resources\views\pages\admin\laporan.blade.php"
if (Test-Path $laporanPath) {
    Backup-File -Path $laporanPath -Suffix "sebelum-dihapus"
    Remove-Item -Path $laporanPath -Force
    Write-Host "  Dihapus: $laporanPath" -ForegroundColor DarkGray
} else {
    Write-Host "  (sudah tidak ada, dilewati)" -ForegroundColor DarkGray
}

Write-Host ""
Write-Host "Selesai." -ForegroundColor Green
Write-Host "Diubah: $navPath, $routesPath, $dashPath"
Write-Host "Dihapus: $laporanPath"
Write-Host ""
Write-Host "Jalankan: php artisan view:clear" -ForegroundColor Cyan
