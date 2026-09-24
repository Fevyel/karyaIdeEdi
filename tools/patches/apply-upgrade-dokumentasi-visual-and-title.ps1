$ErrorActionPreference = 'Stop'

function Write-Step($text) {
    Write-Host "`n$text" -ForegroundColor Cyan
}

function Assert-ProjectRoot {
    if (-not (Test-Path '.\artisan')) {
        throw "File 'artisan' tidak ditemukan. Jalankan script ini dari root project Laravel (folder karyaIdeEdi)."
    }
}

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Upgrade Tampilan Dokumentasi + Perbaiki Judul Halaman' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

Assert-ProjectRoot

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupDir = Join-Path (Get-Location) ".backup-upgrade-dokumentasi-visual-$timestamp"
$bookingPath = '.\resources\views\pages\frontend\booking.blade.php'

Write-Step '[1/5] Membuat backup ...'
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $backupDir 'resources\views\pages\frontend') -Force | Out-Null
Copy-Item $bookingPath (Join-Path $backupDir 'resources\views\pages\frontend\booking.blade.php') -Force
Write-Host "  Backup dibuat di: $backupDir" -ForegroundColor DarkGray

Write-Step '[2/5] Menerapkan patch booking.blade.php ...'
$tmpPhp = Join-Path $env:TEMP "patch-upgrade-dokumentasi-visual-$timestamp.php"

@'
<?php
$path = getcwd() . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/booking.blade.php';
$content = file_get_contents($path);
if ($content === false) {
    throw new RuntimeException('Gagal membaca booking.blade.php');
}

// ------------------------------------------------------------
// [A] Perbaiki judul tab/browser supaya tidak mojibake lagi.
//     Pakai ASCII aman: "Dokumentasi | Nama Situs".
// ------------------------------------------------------------
$titleReplacement = '<title>Dokumentasi | {{ \\App\\Models\\Setting::current()->site_name }}</title>';
if (preg_match('~<title>.*?</title>~s', $content)) {
    $content = preg_replace('~<title>.*?</title>~s', $titleReplacement, $content, 1);
    echo "  - Judul halaman/tab: diperbarui\n";
} else {
    throw new RuntimeException('Tag <title> tidak ditemukan.');
}

// ------------------------------------------------------------
// [B] Ganti isi setelah HERO menjadi galeri video premium.
//     HERO sengaja TIDAK disentuh.
// ------------------------------------------------------------
$footerNeedle = "    @include('partials.frontend.footer')";
$footerPos = strpos($content, $footerNeedle);
if ($footerPos === false) {
    throw new RuntimeException("Marker footer tidak ditemukan.");
}

$sectionNeedlePos = strpos($content, "'dokumentasi-3'");
if ($sectionNeedlePos === false) {
    throw new RuntimeException("Section key 'dokumentasi-3' tidak ditemukan di booking.blade.php.");
}

$beforeSection = substr($content, 0, $sectionNeedlePos);
$startPos = strrpos($beforeSection, "    @php");
if ($startPos === false || $startPos >= $footerPos) {
    throw new RuntimeException("Awal blok galeri video tidak ditemukan dengan aman.");
}

$newBlock = <<<'BLADE'
    @php
        // =========================================================
        // GALERI VIDEO PREMIUM — isi utama halaman Dokumentasi.
        // HERO di atas SENGAJA tidak disentuh.
        // Sumber data utama: section_key 'dokumentasi-3' dari Edit Web.
        // Kalau belum ada item di sana, fallback ke galeri lama (khusus
        // item bertipe video). Kalau masih kosong juga, pakai dummy.
        // =========================================================
        $dokVideoSection = \App\Models\HomeSection::dataFor('dokumentasi-3', [
            'judul' => 'Galeri Video',
            'subjudul' => 'Dokumentasi Proses & Hasil',
            'deskripsi' => 'Lihat lebih dekat proses pengerjaan, detail finishing, hingga hasil akhir furnitur yang kami kerjakan.',
            'items' => [],
        ]);

        $dokFallbackVideos = [
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4', 'keterangan' => 'Proses Produksi Furnitur Custom'],
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 'keterangan' => 'Detail Finishing dan Perakitan'],
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 'keterangan' => 'Hasil Akhir Siap Dikirim'],
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4', 'keterangan' => 'Cuplikan Pengerjaan dari Dekat'],
        ];

        $dokVideoBentukItem = function (array $item) {
            $src = isset($item['path']) && $item['path']
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path'])
                : ($item['url'] ?? null);

            if (! $src || ($item['tipe'] ?? null) !== 'video') {
                return null;
            }

            return [
                'src' => $src,
                'keterangan' => trim((string) ($item['keterangan'] ?? '')),
            ];
        };

        $dokVideoFromDok3 = array_values(array_filter(array_map(
            $dokVideoBentukItem,
            is_array($dokVideoSection['items'] ?? null) ? $dokVideoSection['items'] : []
        )));

        $dokVideoFromLegacyGallery = array_values(array_filter(array_map(
            $dokVideoBentukItem,
            is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : []
        )));

        $dokVideoItems = count($dokVideoFromDok3) > 0
            ? $dokVideoFromDok3
            : (count($dokVideoFromLegacyGallery) > 0 ? $dokVideoFromLegacyGallery : $dokFallbackVideos);

        $dokVideoCount = count($dokVideoItems);
    @endphp

    @if ($dokVideoCount > 0)
        <section class="relative overflow-hidden bg-[#F7F4EF] py-18 sm:py-20 lg:py-24">
            <div class="pointer-events-none absolute left-[-8rem] top-16 h-48 w-48 rounded-full bg-[#E7D7C2]/45 blur-3xl"></div>
            <div class="pointer-events-none absolute right-[-6rem] bottom-8 h-44 w-44 rounded-full bg-[#D7C2A8]/30 blur-3xl"></div>

            <div
                x-data="{
                    open: false,
                    activeSrc: '',
                    activeTitle: '',
                    activeNumber: '',
                    show(src, title, number) {
                        this.activeSrc = src;
                        this.activeTitle = title;
                        this.activeNumber = number;
                        this.open = true;
                        document.body.classList.add('overflow-hidden');
                    },
                    close() {
                        this.open = false;
                        this.activeSrc = '';
                        this.activeTitle = '';
                        this.activeNumber = '';
                        document.body.classList.remove('overflow-hidden');
                    }
                }"
                x-on:keydown.escape.window="close()"
                class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10"
            >
                <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="h-px w-10 bg-[#B68A5B]"></span>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.42em] text-[#B07A43] sm:text-xs">
                                {{ $dokVideoSection['subjudul'] }}
                            </p>
                        </div>

                        <h2 class="font-display text-4xl font-semibold leading-none text-[#3D2B1F] sm:text-5xl lg:text-[4rem]">
                            {{ $dokVideoSection['judul'] }}
                        </h2>

                        <p class="mt-6 max-w-2xl text-base leading-9 text-[#6E6357] sm:text-lg">
                            {{ $dokVideoSection['deskripsi'] }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 lg:justify-end">
                        <div class="inline-flex items-center gap-3 rounded-full border border-[#D7C4AD] bg-white/85 px-5 py-3 shadow-[0_14px_40px_-32px_rgba(61,43,31,0.45)] backdrop-blur">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#4B2F1F] text-white">
                                <i class="fa-solid fa-film text-sm"></i>
                            </span>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#9F8B74]">Koleksi</p>
                                <p class="text-lg font-semibold text-[#3D2B1F]">{{ $dokVideoCount }} Video</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 h-px w-full bg-linear-to-r from-[#DCCEBB] via-[#CDB89D] to-transparent"></div>

                <div class="mt-9 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($dokVideoItems as $i => $video)
                        @php
                            $nomorVideo = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                            $judulVideo = $video['keterangan'] ?: 'Video Dokumentasi '.$nomorVideo;
                        @endphp

                        <button
                            type="button"
                            x-on:click="show(@js($video['src']), @js($judulVideo), @js($nomorVideo))"
                            class="group relative overflow-hidden rounded-[2rem] border border-[#4E3324]/18 bg-[#EADCCB] text-left shadow-[0_26px_70px_-48px_rgba(61,43,31,0.62)] transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_30px_80px_-42px_rgba(61,43,31,0.72)] focus:outline-none focus:ring-2 focus:ring-[#9B6E3E]/30"
                        >
                            <div class="relative aspect-video overflow-hidden bg-[#D8C0A6]">
                                <video
                                    src="{{ $video['src'] }}"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                    autoplay muted loop playsinline preload="metadata"
                                    x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                                ></video>

                                <div class="absolute inset-0 bg-linear-to-t from-[#160E09]/90 via-[#160E09]/25 to-[#160E09]/10"></div>
                                <div class="absolute inset-0 bg-linear-to-br from-[#6D4426]/24 via-transparent to-[#0F0906]/16"></div>

                                <div class="absolute left-4 right-4 top-4 flex items-start justify-between gap-3">
                                    <span class="inline-flex min-w-12 items-center justify-center rounded-full border border-white/20 bg-black/28 px-3 py-2 text-xs font-semibold tracking-[0.16em] text-white backdrop-blur">
                                        {{ $nomorVideo }}
                                    </span>

                                    <span class="inline-flex items-center gap-2 rounded-full border border-white/16 bg-black/28 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-white backdrop-blur">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#E8BE84]"></span>
                                        Video
                                    </span>
                                </div>

                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-white/28 bg-white/12 text-white shadow-lg backdrop-blur-md transition duration-300 group-hover:scale-110 group-hover:bg-white/20">
                                        <i class="fa-solid fa-play text-sm"></i>
                                    </span>
                                </div>

                                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-[#E7C89A]">
                                        Dokumentasi Karya
                                    </p>
                                    <h3 class="mt-2 line-clamp-2 text-xl font-semibold leading-snug text-white">
                                        {{ $judulVideo }}
                                    </h3>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 flex items-center justify-center gap-4 text-center text-sm text-[#9A7E61]">
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                    <p>Klik video untuk melihat dalam ukuran lebih besar</p>
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                </div>

                <div
                    x-show="open"
                    x-transition.opacity
                    x-cloak
                    class="fixed inset-0 z-100 flex items-center justify-center bg-[#120C08]/78 px-4 py-6 backdrop-blur-sm"
                >
                    <div class="absolute inset-0" x-on:click="close()"></div>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-6 scale-[0.98]"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 scale-[0.98]"
                        class="relative z-10 w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/12 bg-[#17100D] shadow-[0_30px_100px_-36px_rgba(0,0,0,0.7)]"
                    >
                        <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-[#D4B083]" x-text="'Video ' + activeNumber"></p>
                                <h3 class="mt-1 truncate text-lg font-semibold text-white sm:text-xl" x-text="activeTitle"></h3>
                            </div>

                            <button
                                type="button"
                                x-on:click="close()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/12 bg-white/6 text-white transition hover:bg-white/12"
                            >
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <div class="bg-black p-3 sm:p-4">
                            <div class="overflow-hidden rounded-[1.5rem] bg-black">
                                <video
                                    x-bind:src="activeSrc"
                                    class="aspect-video w-full bg-black"
                                    controls autoplay playsinline
                                ></video>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

BLADE;

$content = substr($content, 0, $startPos) . $newBlock . substr($content, $footerPos);
file_put_contents($path, $content);

echo "  - Galeri video premium: diperbarui\n";
echo "PATCH_OK\n";
'@ | Set-Content -Path $tmpPhp -Encoding UTF8

php $tmpPhp
Remove-Item $tmpPhp -Force

Write-Step '[3/5] Validasi syntax ...'
php -l $bookingPath | Out-Host

Write-Step '[4/5] Ringkasan perubahan ...'
Write-Host '  - Hero halaman Dokumentasi tetap utuh, tidak disentuh.' -ForegroundColor DarkGray
Write-Host '  - Judul tab/browser diperbaiki menjadi: Dokumentasi | Nama Situs' -ForegroundColor DarkGray
Write-Host '  - Section galeri video dibuat lebih premium dan modern.' -ForegroundColor DarkGray
Write-Host '  - Maksimal 4 video per baris di desktop, responsif di tablet/mobile.' -ForegroundColor DarkGray
Write-Host '  - Klik kartu video untuk membuka tampilan video yang lebih besar (modal).' -ForegroundColor DarkGray

Write-Step '[5/5] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Hero Dokumentasi tidak disentuh.' -ForegroundColor Green
Write-Host 'Yang diubah hanya judul halaman/tab dan galeri video setelah hero.' -ForegroundColor Green
