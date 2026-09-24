# apply-susunan-mobile-tentang-kami.ps1
# Jalankan dari root project (folder yang ada file "artisan"-nya):
#   powershell -ExecutionPolicy Bypass -File .\apply-susunan-mobile-tentang-kami.ps1
#
# Tujuan: susunan ulang halaman "Tentang Kami" (/profil) KHUSUS tampilan HP
# (layar < 640px). Tablet dan desktop TIDAK berubah sama sekali.
#
# Cara kerjanya (supaya desktop aman):
#   - 4 section lama (Hero, Lebih dari sekadar furniture, Nilai Kami, Kenapa
#     memilih) cuma ditambah class "max-sm:hidden" -> hilang di HP saja.
#   - Satu blok baru "sm:hidden" (hilang di tablet/desktop) ditaruh sebelum
#     footer, berisi susunan HP yang baru.
#   Teks, warna, font, dan foto memakai sumber yang sama dengan section lama.
#
# Susunan HP yang baru:
#   1) Hero: judul -> mosaic 3 foto produk asli (bisa diklik ke detail produk)
#      -> dua tombol sejajar. Kalau produk berfoto kurang dari 3, otomatis
#      kembali ke kartu kursi seperti sebelumnya.
#   2) Lebih dari sekadar furniture: paragraf pembuka, lalu paragraf kedua
#      bersanding dengan foto lemari dalam satu panel.
#   3) Yang kami utamakan: 4 kartu geser ke samping (pola sama dengan Ulasan
#      Pelanggan di Beranda), tanpa penomoran.
#   4) Kenapa memilih: satu daftar bersekat + ajakan "Pesan furnitur custom"
#      yang menuju halaman Booking.
#   Label kecil berhuruf kapital ("TENTANG KAMI", "NILAI KAMI", dst) tidak
#   dipakai di HP karena sudah ada di menu tab atas dan judul tiap bagian.
#
# File yang diubah HANYA:  resources/views/pages/frontend/profil.blade.php
# Tidak ada perubahan logic/data/database. Aman dijalankan di atas patch
# "apply-fix-mobile-tentang-kami.ps1" (sudah/belum dipasang, dua-duanya jalan).
#
# Pengaman:
#   - Semua pola dicek dulu. Kalau ada yang tidak ketemu persis / ketemu lebih
#     dari 1 kali, script berhenti dan TIDAK menulis apa pun.
#   - Backup file asli dibuat di folder .backup-susunan-mobile-tentang-kami-<waktu>/
#     (sudah di-.gitignore).
#   - Encoding (ada/tidak ada BOM) dan jenis baris baru (LF/CRLF) dipertahankan.
#   - Aman dijalankan berulang: kalau sudah terpasang, tidak ada yang dilakukan.
#
# Setelah script selesai, jalankan:  npm run build

$ErrorActionPreference = "Stop"
$stamp   = Get-Date -Format "yyyyMMdd-HHmmss"
$relPath = "resources/views/pages/frontend/profil.blade.php"

if (-not (Test-Path "artisan")) {
    throw "File 'artisan' tidak ketemu. Jalankan script ini dari root project (C:\xampp\htdocs\karyaIdeEdi)."
}
if (-not (Test-Path $relPath)) {
    throw "Tidak ketemu: $relPath"
}

$full   = (Resolve-Path $relPath).Path
$bytes  = [System.IO.File]::ReadAllBytes($full)
$hasBom = ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)
$text   = [System.IO.File]::ReadAllText($full, (New-Object System.Text.UTF8Encoding($false)))
$nl     = "`n"
if ($text.Contains("`r`n")) { $nl = "`r`n" }

# Tanda kutip-penanda untuk karakter em dash (U+2014), diganti saat jalan supaya
# isi script ini murni ASCII dan aman dibaca Windows PowerShell versi lama.
$mdash = [string][char]0x2014

function Convert-Text($s) {
    $s = $s.Replace("@@MDASH@@", $mdash)
    return (($s -replace "`r`n", "`n") -replace "`n", $nl)
}

function Count-Match($haystack, $needle) {
    return ([regex]::Matches($haystack, [regex]::Escape($needle))).Count
}

$edits = New-Object System.Collections.ArrayList
function Add-Edit($label, $old, $new) {
    [void]$edits.Add(@{ Label = $label; Old = (Convert-Text $old); New = (Convert-Text $new) })
}

# ---- 1) Sembunyikan Hero desktop di HP
$old = @'
<section class="relative overflow-hidden bg-white">
'@
$new = @'
<section class="relative overflow-hidden bg-white max-sm:hidden">
'@
Add-Edit "Sembunyikan Hero desktop di HP" $old $new

# ---- 2) Sembunyikan 'Lebih dari sekadar furniture' desktop di HP
$old = @'
<section class="bg-admin-cream/40">
'@
$new = @'
<section class="bg-admin-cream/40 max-sm:hidden">
'@
Add-Edit "Sembunyikan 'Lebih dari sekadar furniture' desktop di HP" $old $new

# ---- 3) Sembunyikan 'Nilai Kami' desktop di HP
$old = @'
<section class="bg-admin-cream">
'@
$new = @'
<section class="bg-admin-cream max-sm:hidden">
'@
Add-Edit "Sembunyikan 'Nilai Kami' desktop di HP" $old $new

# ---- 4) Sembunyikan 'Kenapa memilih' desktop di HP
$old = @'
<section class="bg-[#221B14]">
'@
$new = @'
<section class="bg-[#221B14] max-sm:hidden">
'@
Add-Edit "Sembunyikan 'Kenapa memilih' desktop di HP" $old $new

# ---- 5) Tambah susunan baru khusus HP
$old = @'
    @include('partials.frontend.footer')
'@
$new = @'
    {{-- =====================================================
         MOBILE-TENTANG-KAMI -- susunan KHUSUS HP (< 640px)
         Section A-D di atas disembunyikan di HP (max-sm:hidden) dan
         digantikan blok ini. Di layar >= 640px blok ini hilang (sm:hidden)
         dan tampilan tablet/desktop TIDAK berubah sama sekali.
         Teks, warna, font, dan foto memakai sumber yang sama dengan
         section di atas ($profileSetting, $valueDefs, token admin-*).
    ====================================================== --}}
    @php
        // Foto untuk mosaic hero di HP: 3 produk berfoto. Kalau ada >= 7
        // produk berfoto, pakai 3 yang BEDA dari 4 foto di "Yang kami
        // utamakan" supaya tidak kembar. Kalau kurang dari 3, mosaic
        // diganti kartu kursi seperti tampilan lama.
        $mobileShowcase = \App\Models\Product::query()
            ->where('status', 'aktif')
            ->whereNotNull('thumbnail')
            ->orderByDesc('featured')
            ->orderByDesc('created_at')
            ->take(7)
            ->get()
            ->filter(fn ($mobileProduct) => \Illuminate\Support\Facades\Storage::disk('public')->exists($mobileProduct->thumbnail))
            ->values();

        $mobileHeroProducts = ($mobileShowcase->count() >= 7 ? $mobileShowcase->slice(4, 3) : $mobileShowcase->take(3))->values();
        $mobileValueDefs = $valueDefs ?? [];
    @endphp

    <div class="sm:hidden">
        {{-- M1. Hero: judul -> foto karya (mosaic) -> tombol --}}
        <section class="bg-white">
            <div class="px-6 pb-10 pt-8">
                <div data-reveal>
                    <h1 class="font-display text-4xl leading-[1.1] text-[#3D2B1F]">
                        Mewujudkan Ruang yang Punya Cerita.
                    </h1>
                    <p class="mt-4 text-[0.95rem] leading-relaxed text-[#6B6E76]">
                        {{ $profileSetting->site_name }} menghadirkan furnitur yang dibuat dengan
                        teliti untuk melengkapi ruang Anda @@MDASH@@ bukan sekadar mengisinya. Setiap
                        karya dirancang untuk nyaman digunakan sekaligus enak dipandang, untuk
                        rumah maupun ruang kerja.
                    </p>
                </div>

                @if ($mobileHeroProducts->count() === 3)
                    <div class="mt-7 grid aspect-5/4 grid-cols-5 grid-rows-2 gap-2" data-reveal>
                        @foreach ($mobileHeroProducts as $mobileIndex => $mobileProduct)
                            <a
                                href="{{ route('products.show', $mobileProduct) }}"
                                class="relative overflow-hidden rounded-2xl bg-admin-cream {{ $mobileIndex === 0 ? 'col-span-3 row-span-2' : 'col-span-2' }}"
                            >
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mobileProduct->thumbnail) }}"
                                    alt="{{ $mobileProduct->nama }}"
                                    decoding="async"
                                    class="absolute inset-0 h-full w-full object-cover"
                                >
                                @if ($mobileIndex === 0)
                                    <span class="absolute inset-x-0 bottom-0 truncate bg-linear-to-t from-black/60 to-transparent px-3 pb-2.5 pt-10 text-xs font-medium text-white">
                                        {{ $mobileProduct->nama }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-7" data-reveal>
                        <span class="relative flex aspect-4/3 w-full items-center justify-center overflow-hidden rounded-3xl bg-admin-cream">
                            <img
                                src="{{ asset('images/admin-login/kursi.png') }}"
                                alt="Furniture {{ $profileSetting->site_name }}"
                                class="h-[82%] w-auto object-contain drop-shadow-xl"
                            >
                        </span>
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-3">
                    <a
                        href="{{ route('products.index') }}"
                        class="group inline-flex items-center justify-center gap-2 rounded-xl bg-[#1A1A1A] px-4 py-3.5 text-sm font-medium text-white shadow-sm"
                    >
                        Lihat Produk
                        <x-icon-arrow direction="right" class="transition-transform duration-300 group-active:translate-x-1" />
                    </a>
                    <a
                        href="{{ $profileWaNumber ? 'https://wa.me/'.$profileWaNumber : '#' }}"
                        @if ($profileWaNumber) target="_blank" rel="noopener" @endif
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#DCDDD7] px-4 py-3.5 text-sm font-medium text-[#3D2B1F]"
                    >
                        <i class="fa-brands fa-whatsapp"></i>
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </section>

        {{-- M2. Cerita toko: paragraf pembuka, lalu paragraf kedua bersanding dengan foto lemari --}}
        <section class="bg-admin-cream/40">
            <div class="px-6 py-12">
                <div data-reveal>
                    <h2 class="font-display text-[1.75rem] leading-tight text-[#4B3A26]">
                        Lebih dari sekadar furniture.
                    </h2>
                    <p class="mt-4 text-[0.95rem] leading-relaxed text-admin-ink-soft">
                        {{ $profileSetting->site_name }} adalah toko furniture yang menghadirkan
                        produk untuk membantu Anda menciptakan ruang yang nyaman, fungsional, dan
                        punya karakter. Kami percaya furnitur yang baik bukan cuma soal bentuk @@MDASH@@
                        tapi juga soal bagaimana ia membuat ruang terasa lebih hidup untuk dipakai
                        sehari-hari.
                    </p>
                </div>

                <div class="mt-6 grid grid-cols-5 items-center gap-4 overflow-hidden rounded-3xl bg-white p-5 shadow-sm" data-reveal>
                    <p class="col-span-3 text-sm leading-relaxed text-admin-ink-soft">
                        Dari kebutuhan rumah tangga sampai ruang kerja, setiap produk kami pilih
                        dan siapkan dengan memperhatikan kualitas bahan, kenyamanan pemakaian, dan
                        kejelasan informasi @@MDASH@@ supaya Anda bisa memutuskan dengan tenang.
                    </p>
                    <img
                        src="{{ asset('images/admin-login/hero.png') }}"
                        alt="Furniture {{ $profileSetting->site_name }}"
                        loading="lazy"
                        class="col-span-2 h-auto w-full object-contain"
                    >
                </div>
            </div>
        </section>

        {{-- M3. Nilai kami: geser ke samping (pola sama dengan Ulasan Pelanggan di Beranda) --}}
        <section class="bg-white">
            <div class="py-12">
                <div class="px-6" data-reveal>
                    <h2 class="font-display text-[1.75rem] leading-tight text-[#4B3A26]">
                        Yang kami utamakan di setiap karya.
                    </h2>
                </div>

                <div class="mt-6 flex snap-x snap-mandatory scroll-pl-6 gap-4 overflow-x-auto px-6 pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($mobileValueDefs as $mobileValue)
                        <article class="w-[78%] shrink-0 snap-start overflow-hidden rounded-2xl border border-admin-border bg-white">
                            <div class="relative aspect-4/3 w-full overflow-hidden bg-admin-cream">
                                @if ($mobileValue['image'])
                                    <img
                                        src="{{ $mobileValue['image'] }}"
                                        alt="{{ $mobileValue['title'] }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-admin-ink-soft/30">
                                        <i class="fa-solid {{ $mobileValue['icon'] }} text-3xl"></i>
                                    </div>
                                @endif
                                <span class="absolute left-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-admin-accent shadow-sm">
                                    <i class="fa-solid {{ $mobileValue['icon'] }} text-sm"></i>
                                </span>
                            </div>
                            <div class="p-4">
                                <h3 class="text-base font-semibold text-[#3D2B1F]">{{ $mobileValue['title'] }}</h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-admin-ink-soft">{{ $mobileValue['desc'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- M4. Kenapa memilih + ajakan pesan custom --}}
        <section class="bg-[#221B14]">
            <div class="px-6 py-12">
                <div data-reveal>
                    <h2 class="font-display text-[1.75rem] leading-tight text-white">
                        Kenapa memilih {{ $profileSetting->site_name }}?
                    </h2>
                    <p class="mt-4 text-[0.95rem] leading-relaxed text-white/60">
                        Kami ingin proses memilih furniture terasa mudah dan tenang @@MDASH@@ dari
                        melihat produk sampai memutuskan yang paling cocok untuk ruang Anda.
                    </p>
                </div>

                <ul class="mt-7 divide-y divide-white/10 overflow-hidden rounded-2xl border border-white/10 bg-white/4" data-reveal>
                    @foreach ([
                        ['icon' => 'fa-layer-group', 'text' => 'Produk pilihan'],
                        ['icon' => 'fa-circle-info', 'text' => 'Informasi produk yang jelas'],
                        ['icon' => 'fa-cart-shopping', 'text' => 'Proses pemesanan mudah'],
                        ['icon' => 'fa-headset', 'text' => 'Dukungan pelanggan'],
                        ['icon' => 'fa-couch', 'text' => 'Pengalaman belanja yang nyaman'],
                    ] as $mobilePoint)
                        <li class="flex items-center gap-3 px-4 py-3.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-admin-gold/15 text-admin-gold">
                                <i class="fa-solid {{ $mobilePoint['icon'] }} text-sm"></i>
                            </span>
                            <span class="text-sm font-medium text-white">{{ $mobilePoint['text'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-9" data-reveal>
                    <p class="font-display text-xl leading-snug text-white">
                        Punya ukuran atau desain sendiri?
                    </p>
                    <a
                        href="{{ route('booking.index') }}"
                        class="mt-4 flex w-full items-center justify-center rounded-xl bg-admin-gold px-6 py-3.5 text-sm font-semibold text-[#221B14]"
                    >
                        Pesan furnitur custom
                    </a>
                </div>
            </div>
        </section>
    </div>

    @include('partials.frontend.footer')
'@
Add-Edit "Tambah susunan baru khusus HP" $old $new

# ============================================================
# TAHAP 0: sudah pernah dipasang? (penanda ada di dalam blok baru)
# Semua perubahan ditulis sekaligus, jadi kalau penanda ini ada berarti
# seluruh susunan baru sudah terpasang -> tidak ada yang dilakukan.
# ============================================================
if ($text.Contains("MOBILE-TENTANG-KAMI")) {
    Write-Host "Susunan mobile Tentang Kami sudah terpasang (penanda MOBILE-TENTANG-KAMI ada di file). Tidak ada yang dilakukan." -ForegroundColor Yellow
    return
}

# ============================================================
# TAHAP 1: cek semua pola dulu (belum menulis apa pun)
# ============================================================
$pending = New-Object System.Collections.ArrayList
$skipped = 0
$failed  = New-Object System.Collections.ArrayList

foreach ($e in $edits) {
    $n = Count-Match $text $e.Old
    if ($n -eq 1) {
        [void]$pending.Add($e)
    }
    elseif ($n -eq 0 -and (Count-Match $text $e.New) -ge 1) {
        Write-Host "   Sudah diterapkan, dilewati: $($e.Label)" -ForegroundColor Yellow
        $skipped++
    }
    elseif ($n -eq 0) {
        [void]$failed.Add("TIDAK ketemu persis : $($e.Label)")
    }
    else {
        [void]$failed.Add("Ketemu $n kali (harus 1): $($e.Label)")
    }
}

if ($failed.Count -gt 0) {
    Write-Host ""
    foreach ($f in $failed) { Write-Host "   $f" -ForegroundColor Red }
    throw "Ada pola yang tidak cocok di $relPath (kemungkinan file sudah diubah manual). TIDAK ada yang ditulis/ditimpa."
}

if ($pending.Count -eq 0) {
    Write-Host ""
    Write-Host "Semua perubahan sudah pernah diterapkan. Tidak ada yang dilakukan." -ForegroundColor Yellow
    return
}

# ============================================================
# TAHAP 2: backup lalu tulis
# ============================================================
$backupDir  = ".backup-susunan-mobile-tentang-kami-$stamp"
$backupFile = Join-Path $backupDir $relPath
New-Item -ItemType Directory -Force -Path (Split-Path $backupFile -Parent) | Out-Null
Copy-Item $relPath $backupFile

$newText = $text
foreach ($e in $pending) {
    Write-Host "-> $($e.Label)" -ForegroundColor Cyan
    $newText = $newText.Replace($e.Old, $e.New)
}

[System.IO.File]::WriteAllText($full, $newText, (New-Object System.Text.UTF8Encoding($hasBom)))

Write-Host ""
Write-Host "Selesai. $($pending.Count) perubahan diterapkan ($skipped dilewati)." -ForegroundColor Green
Write-Host "Backup : $backupFile" -ForegroundColor Green
Write-Host "Lanjut : npm run build   (lalu refresh HP/browser)" -ForegroundColor Green
