# ============================================================
# LANJUTAN AMAN: Hapus Tab Laporan + pindahkan bagian penting
# ke Dashboard.
#
# Dibuat untuk kondisi ketika script v1 sempat berhasil pada
# langkah [1/4] dan [2/4], lalu gagal pada langkah [3/4].
#
# Prinsip script v2:
# - Tidak menyentuh file di luar 4 file target.
# - Semua validasi dilakukan SEBELUM source file ditulis.
# - Kalau sidebar/route Laporan sudah hilang, dilewati (aman rerun).
# - Dashboard dipatch memakai marker section, bukan mencocokkan
#   seluruh blok dummy secara persis seperti script v1.
# - Backup rapi dalam SATU folder .backup-hapus-tab-laporan-v2-<timestamp>.
#
# Target:
# 1. resources/views/layouts/admin-panel.blade.php
# 2. routes/web.php
# 3. resources/views/pages/admin/dashboard.blade.php
# 4. resources/views/pages/admin/laporan.blade.php (dihapus setelah backup)
# ============================================================

$ErrorActionPreference = 'Stop'

function Read-Utf8File {
    param([Parameter(Mandatory = $true)][string]$Path)
    return [System.IO.File]::ReadAllText((Join-Path (Get-Location) $Path))
}

function Write-Utf8NoBom {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Content
    )

    [System.IO.File]::WriteAllText(
        (Join-Path (Get-Location) $Path),
        $Content,
        (New-Object System.Text.UTF8Encoding($false))
    )
}

function Get-Eol {
    param([Parameter(Mandatory = $true)][string]$Content)
    if ($Content.Contains("`r`n")) { return "`r`n" }
    return "`n"
}

function Convert-ToEol {
    param(
        [Parameter(Mandatory = $true)][string]$Text,
        [Parameter(Mandatory = $true)][string]$Eol
    )

    $normalized = $Text.Replace("`r`n", "`n").Replace("`r", "`n")
    return $normalized.Replace("`n", $Eol)
}

function Count-Literal {
    param(
        [Parameter(Mandatory = $true)][string]$Content,
        [Parameter(Mandatory = $true)][string]$Needle
    )

    return ([regex]::Matches($Content, [regex]::Escape($Needle))).Count
}

function Backup-ToFolder {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$BackupRoot
    )

    if (-not (Test-Path $Path)) { return }

    $destination = Join-Path $BackupRoot $Path
    $destinationDir = Split-Path -Parent $destination
    New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    Copy-Item -Path $Path -Destination $destination -Force
    Write-Host "  Backup: $destination" -ForegroundColor DarkGray
}

Write-Host '====================================================' -ForegroundColor Cyan
Write-Host ' Hapus Tab Laporan + Pindahkan ke Dashboard (v2)' -ForegroundColor Cyan
Write-Host '====================================================' -ForegroundColor Cyan
Write-Host ''

if (-not (Test-Path '.\artisan')) {
    Write-Host "[ERROR] Jalankan script dari root project yang berisi file 'artisan'." -ForegroundColor Red
    exit 1
}

$navPath = 'resources\views\layouts\admin-panel.blade.php'
$routesPath = 'routes\web.php'
$dashPath = 'resources\views\pages\admin\dashboard.blade.php'
$laporanPath = 'resources\views\pages\admin\laporan.blade.php'

foreach ($required in @($navPath, $routesPath, $dashPath)) {
    if (-not (Test-Path $required)) {
        Write-Host "[ERROR] File wajib tidak ditemukan: $required" -ForegroundColor Red
        exit 1
    }
}

# ============================================================
# FASE A - BACA + VALIDASI + SIAPKAN SEMUA PERUBAHAN DI MEMORY
# Belum ada source file yang ditulis pada fase ini.
# ============================================================

$navOriginal = Read-Utf8File $navPath
$routesOriginal = Read-Utf8File $routesPath
$dashOriginal = Read-Utf8File $dashPath

$navNew = $navOriginal
$routesNew = $routesOriginal
$dashNew = $dashOriginal

$navChanged = $false
$routesChanged = $false
$dashChanged = $false
$laporanExists = Test-Path $laporanPath

# ------------------------------------------------------------
# A1) Sidebar - hapus HANYA baris nav admin.reports jika masih ada.
# ------------------------------------------------------------
$navPattern = '(?m)^[ \t]*\$navItem\(''admin\.reports''[^\r\n]*\),[ \t]*(?:\r?\n)?'
$navMatches = [regex]::Matches($navNew, $navPattern)

if ($navMatches.Count -gt 1) {
    Write-Host "[ERROR] Ditemukan lebih dari satu nav item admin.reports. Dibatalkan agar tidak salah edit." -ForegroundColor Red
    exit 1
}

if ($navMatches.Count -eq 1) {
    $navNew = [regex]::Replace($navNew, $navPattern, '', 1)
    $navChanged = $true
} elseif ($navNew.Contains('admin.reports')) {
    Write-Host "[ERROR] Masih ada referensi admin.reports di sidebar, tetapi formatnya tidak dikenali v2." -ForegroundColor Red
    Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# A2) routes/web.php - hapus HANYA route halaman laporan jika masih ada.
# ------------------------------------------------------------
$routePattern = '(?m)^[ \t]*Route::livewire\(''/laporan'',\s*''pages::admin\.laporan''\)->name\(''reports''\);[ \t]*(?:\r?\n)?'
$routeMatches = [regex]::Matches($routesNew, $routePattern)

if ($routeMatches.Count -gt 1) {
    Write-Host "[ERROR] Ditemukan lebih dari satu route admin.reports. Dibatalkan agar tidak salah edit." -ForegroundColor Red
    exit 1
}

if ($routeMatches.Count -eq 1) {
    $routesNew = [regex]::Replace($routesNew, $routePattern, '', 1)
    $routesChanged = $true
} elseif ($routesNew.Contains("name('reports')") -or $routesNew.Contains("'/laporan'")) {
    Write-Host "[ERROR] Masih ada route Laporan, tetapi formatnya tidak dikenali v2." -ForegroundColor Red
    Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# A3) Dashboard - helper statusStyle().
# ------------------------------------------------------------
$dashEol = Get-Eol $dashNew
$helperSignature = '    private function statusStyle(string $status): array'

if (-not $dashNew.Contains($helperSignature)) {
    $withAnchor = '    public function with(): array'
    $withCount = Count-Literal -Content $dashNew -Needle $withAnchor

    if ($withCount -ne 1) {
        Write-Host "[ERROR] Anchor Dashboard 'public function with(): array' harus muncul tepat 1 kali, ditemukan $withCount." -ForegroundColor Red
        Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
        exit 1
    }

    $helperBlock = @'
    /** Warna badge status -- sama dengan halaman Pesanan/History Pesanan. */
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

'@
    $helperBlock = Convert-ToEol -Text $helperBlock -Eol $dashEol
    $dashNew = $dashNew.Replace($withAnchor, $helperBlock + $withAnchor)
    $dashChanged = $true
}

# ------------------------------------------------------------
# A4) Dashboard - query Status Pesanan + Produk Terlaris bulan ini.
# ------------------------------------------------------------
$hasStatusQuery = $dashNew.Contains('$statusBreakdown = collect(Transaction::STATUSES)')
$hasTopProductsQuery = $dashNew.Contains('$topProducts = Transaction::query()')

if ($hasStatusQuery -xor $hasTopProductsQuery) {
    Write-Host '[ERROR] Dashboard terlihat sudah terpatch sebagian pada bagian query.' -ForegroundColor Red
    Write-Host '        Saya berhenti supaya tidak menduplikasi logic yang sudah ada.' -ForegroundColor Red
    Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
    exit 1
}

if (-not $hasStatusQuery -and -not $hasTopProductsQuery) {
    $queryAnchor = "        `$donutGradient = `$gradientParts->implode(', ');"
    $queryAnchorCount = Count-Literal -Content $dashNew -Needle $queryAnchor

    if ($queryAnchorCount -ne 1) {
        Write-Host "[ERROR] Anchor Dashboard donutGradient harus muncul tepat 1 kali, ditemukan $queryAnchorCount." -ForegroundColor Red
        Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
        exit 1
    }

    $queryBlock = @'

        // ---- Status Pesanan & Produk Terlaris bulan ini (data asli dari database) ----
        $statusCounts = Transaction::query()
            ->where('created_at', '>=', $startOfMonth)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalOrdersThisMonth = (int) $statusCounts->sum();

        $statusBreakdown = collect(Transaction::STATUSES)
            ->map(fn (string $status) => [
                'status' => $status,
                'label' => ucfirst($status),
                'count' => (int) ($statusCounts[$status] ?? 0),
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
'@
    $queryBlock = Convert-ToEol -Text $queryBlock -Eol $dashEol
    $dashNew = $dashNew.Replace($queryAnchor, $queryAnchor + $queryBlock)
    $dashChanged = $true
}

# ------------------------------------------------------------
# A5) Dashboard - expose 3 data baru dari with().
# ------------------------------------------------------------
$returnKey1 = "            'totalOrdersThisMonth' => `$totalOrdersThisMonth,"
$returnKey2 = "            'statusBreakdown' => `$statusBreakdown,"
$returnKey3 = "            'topProducts' => `$topProducts,"

$hasReturn1 = $dashNew.Contains($returnKey1)
$hasReturn2 = $dashNew.Contains($returnKey2)
$hasReturn3 = $dashNew.Contains($returnKey3)
# Hitung eksplisit agar state parsial mudah dideteksi.
$returnCount = 0
if ($hasReturn1) { $returnCount++ }
if ($hasReturn2) { $returnCount++ }
if ($hasReturn3) { $returnCount++ }

if ($returnCount -gt 0 -and $returnCount -lt 3) {
    Write-Host '[ERROR] Dashboard terlihat sudah terpatch sebagian pada return array with().' -ForegroundColor Red
    Write-Host '        Saya berhenti supaya tidak membuat state campuran.' -ForegroundColor Red
    Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
    exit 1
}

if ($returnCount -eq 0) {
    $returnAnchor = "            'recentInteraksi' => Testimonial::query()->latest()->take(5)->get(),"
    $returnAnchorCount = Count-Literal -Content $dashNew -Needle $returnAnchor

    if ($returnAnchorCount -ne 1) {
        Write-Host "[ERROR] Anchor Dashboard recentInteraksi harus muncul tepat 1 kali, ditemukan $returnAnchorCount." -ForegroundColor Red
        Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
        exit 1
    }

    $returnBlock = @'
            'totalOrdersThisMonth' => $totalOrdersThisMonth,
            'statusBreakdown' => $statusBreakdown,
            'topProducts' => $topProducts,
'@
    $returnBlock = Convert-ToEol -Text $returnBlock -Eol $dashEol
    $dashNew = $dashNew.Replace($returnAnchor, $returnAnchor + $dashEol + $returnBlock)
    $dashChanged = $true
}

# ------------------------------------------------------------
# A6) Dashboard - ganti HANYA satu section dummy memakai marker.
# Inilah bagian yang gagal di script v1 karena v1 meminta seluruh blok
# harus sama persis karakter demi karakter.
# ------------------------------------------------------------
$newSectionMarker = '    {{-- ================= STATUS PESANAN & PRODUK TERLARIS ================= --}}'
$oldSectionMarker = '    {{-- ================= STATISTIK BOOKING & PRODUK TERLARIS (dummy) ================= --}}'
$nextSectionMarker = '    {{-- ================= TABEL & AKTIVITAS ================= --}}'

$hasNewSection = $dashNew.Contains($newSectionMarker)
$hasOldSection = $dashNew.Contains($oldSectionMarker)

if ($hasNewSection -and $hasOldSection) {
    Write-Host '[ERROR] Dashboard mengandung section lama DAN baru sekaligus. Dibatalkan agar tidak merusak layout.' -ForegroundColor Red
    Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
    exit 1
}

if (-not $hasNewSection) {
    if (-not $hasOldSection) {
        Write-Host '[ERROR] Marker section Statistik Booking dummy tidak ditemukan dan section baru juga belum ada.' -ForegroundColor Red
        Write-Host '        Dashboard lokal kemungkinan berbeda dari ZIP acuan; saya tidak akan menebak bagian yang harus diganti.' -ForegroundColor Red
        Write-Host '        Tidak ada source file yang ditulis.' -ForegroundColor Red
        exit 1
    }

    $oldStart = $dashNew.IndexOf($oldSectionMarker)
    $nextStart = $dashNew.IndexOf($nextSectionMarker, $oldStart)

    if ($oldStart -lt 0 -or $nextStart -lt 0 -or $nextStart -le $oldStart) {
        Write-Host '[ERROR] Batas section Dashboard tidak valid. Tidak ada source file yang ditulis.' -ForegroundColor Red
        exit 1
    }

    $newSection = @'
    {{-- ================= STATUS PESANAN & PRODUK TERLARIS ================= --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- status pesanan bulan ini -- data asli dari database --}}
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

        {{-- produk terlaris bulan ini -- data asli dari pesanan completed --}}
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
    $newSection = Convert-ToEol -Text $newSection -Eol $dashEol

    $dashNew = $dashNew.Substring(0, $oldStart) + $newSection + $dashNew.Substring($nextStart)
    $dashChanged = $true
}

# ------------------------------------------------------------
# A7) Validasi final DI MEMORY sebelum satu pun file ditulis.
# ------------------------------------------------------------
$validationErrors = New-Object System.Collections.Generic.List[string]

if ($navNew.Contains('admin.reports')) {
    $validationErrors.Add('Sidebar masih mengandung admin.reports.')
}
if ($routesNew.Contains("name('reports')") -or $routesNew.Contains("'/laporan'")) {
    $validationErrors.Add('routes/web.php masih mengandung route Laporan.')
}
if (-not $dashNew.Contains($helperSignature)) {
    $validationErrors.Add('Dashboard belum memiliki helper statusStyle().')
}
if (-not $dashNew.Contains('$statusBreakdown = collect(Transaction::STATUSES)')) {
    $validationErrors.Add('Dashboard belum memiliki query statusBreakdown.')
}
if (-not $dashNew.Contains('$topProducts = Transaction::query()')) {
    $validationErrors.Add('Dashboard belum memiliki query topProducts.')
}
if (-not $dashNew.Contains($returnKey1) -or -not $dashNew.Contains($returnKey2) -or -not $dashNew.Contains($returnKey3)) {
    $validationErrors.Add('Dashboard belum mengembalikan semua data Status Pesanan / Produk Terlaris.')
}
if (-not $dashNew.Contains($newSectionMarker)) {
    $validationErrors.Add('Section baru Status Pesanan & Produk Terlaris belum ditemukan.')
}
if ($dashNew.Contains($oldSectionMarker)) {
    $validationErrors.Add('Section dummy Statistik Booking masih tersisa.')
}
if ($dashNew.Contains("['name' => 'Sofa Minimalis Oslo'")) {
    $validationErrors.Add('Data dummy Produk Terlaris masih tersisa.')
}

if ($validationErrors.Count -gt 0) {
    Write-Host '[ERROR] Validasi final gagal. Tidak ada source file yang ditulis:' -ForegroundColor Red
    foreach ($item in $validationErrors) {
        Write-Host "  - $item" -ForegroundColor Red
    }
    exit 1
}

# ============================================================
# FASE B - BACKUP + WRITE
# Semua validasi sudah lolos.
# ============================================================

$anythingToChange = $navChanged -or $routesChanged -or $dashChanged -or $laporanExists

if (-not $anythingToChange) {
    Write-Host '[OK] Semua perubahan task ini ternyata sudah terpasang. Tidak ada file yang diubah.' -ForegroundColor Green
    exit 0
}

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupRoot = ".backup-hapus-tab-laporan-v2-$stamp"
New-Item -ItemType Directory -Path $backupRoot -Force | Out-Null
Write-Host "Backup disimpan rapi di: $backupRoot" -ForegroundColor DarkGray
Write-Host ''

if ($navChanged) {
    Write-Host '[1/4] Sidebar: menghapus tab Laporan ...' -ForegroundColor Yellow
    Backup-ToFolder -Path $navPath -BackupRoot $backupRoot
    Write-Utf8NoBom -Path $navPath -Content $navNew
} else {
    Write-Host '[1/4] Sidebar: tab Laporan sudah tidak ada, dilewati.' -ForegroundColor DarkGray
}

if ($routesChanged) {
    Write-Host '[2/4] Route: menghapus admin.reports ...' -ForegroundColor Yellow
    Backup-ToFolder -Path $routesPath -BackupRoot $backupRoot
    Write-Utf8NoBom -Path $routesPath -Content $routesNew
} else {
    Write-Host '[2/4] Route: admin.reports sudah tidak ada, dilewati.' -ForegroundColor DarkGray
}

if ($dashChanged) {
    Write-Host '[3/4] Dashboard: memasang Status Pesanan + Produk Terlaris data asli ...' -ForegroundColor Yellow
    Backup-ToFolder -Path $dashPath -BackupRoot $backupRoot
    Write-Utf8NoBom -Path $dashPath -Content $dashNew
} else {
    Write-Host '[3/4] Dashboard: section baru sudah terpasang, dilewati.' -ForegroundColor DarkGray
}

if ($laporanExists) {
    Write-Host '[4/4] Menghapus halaman Laporan setelah dibackup ...' -ForegroundColor Yellow
    Backup-ToFolder -Path $laporanPath -BackupRoot $backupRoot
    Remove-Item -Path $laporanPath -Force
    Write-Host "  Dihapus: $laporanPath" -ForegroundColor DarkGray
} else {
    Write-Host '[4/4] Halaman Laporan sudah tidak ada, dilewati.' -ForegroundColor DarkGray
}

Write-Host ''
Write-Host 'SELESAI. Hanya scope task Laporan/Dashboard yang disentuh.' -ForegroundColor Green
Write-Host "Backup: $backupRoot" -ForegroundColor Green
Write-Host ''
Write-Host 'Berikutnya jalankan:' -ForegroundColor Cyan
Write-Host '  php artisan view:clear'
Write-Host '  php artisan route:clear'
Write-Host '  php artisan route:list --name=admin.reports'
Write-Host '  php artisan route:list --name=admin.dashboard'
