$ErrorActionPreference = 'Stop'

function Write-Step($message) {
    Write-Host "`n$message" -ForegroundColor Cyan
}

if (-not (Test-Path '.\artisan')) {
    throw "File artisan tidak ditemukan. Jalankan script ini dari C:\xampp\htdocs\karyaIdeEdi"
}

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Dokumentasi - Redesign Grid Video Premium' -ForegroundColor Yellow
Write-Host ' HERO TIDAK DISENTUH' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$target = '.\resources\views\pages\frontend\booking.blade.php'
$backupDir = ".\.backup-dokumentasi-video-premium-$timestamp"
$backupFile = Join-Path $backupDir 'resources\views\pages\frontend\booking.blade.php'

Write-Step '[1/4] Backup booking.blade.php ...'
New-Item -ItemType Directory -Path (Split-Path $backupFile) -Force | Out-Null
Copy-Item $target $backupFile -Force
Write-Host "  Backup: $backupFile" -ForegroundColor DarkGray

Write-Step '[2/4] Mengganti HANYA section grid video setelah hero ...'
$tmpPhp = Join-Path $env:TEMP "patch-dokumentasi-video-premium-$timestamp.php"

@'
<?php
$path = getcwd() . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/booking.blade.php';
$content = file_get_contents($path);

$startMarker = <<<'TXT'
    @php
        // Isi utama halaman Dokumentasi sekarang memakai section_key
TXT;
$endMarker = <<<'TXT'
    @include('partials.frontend.footer')
TXT;

$start = strpos($content, $startMarker);
$end = strpos($content, $endMarker);

if ($start === false || $end === false || $end <= $start) {
    throw new RuntimeException('Section grid video yang akan diperbaiki tidak ditemukan. HERO tidak diubah dan patch dihentikan.');
}

$newBlock = <<<'BLADE'
    @php
        // Isi utama halaman Dokumentasi memakai section_key 'dokumentasi-3'.
        // HERO DI ATAS TIDAK DISENTUH. Data tetap berasal dari Edit Web >
        // Dokumentasi > Galeri Video. Jika belum ada video tersimpan, fallback
        // sementara tetap dipakai supaya layout bisa langsung dilihat.
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
                : ($item['url'] ?? $item['src'] ?? null);

            if (! $src || (($item['tipe'] ?? 'video') !== 'video')) {
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
    @endphp

    @if (count($dokVideoItems) > 0)
        <section
            class="relative overflow-hidden border-t border-[#E8DED1] bg-[#F7F4EF] py-16 sm:py-20 lg:py-24"
            x-data="{
                modalOpen: false,
                modalSrc: '',
                modalTitle: '',
                openVideo(el) {
                    this.modalSrc = el.dataset.src;
                    this.modalTitle = el.dataset.title;
                    this.modalOpen = true;
                    document.body.style.overflow = 'hidden';
                },
                closeVideo() {
                    this.modalOpen = false;
                    this.modalSrc = '';
                    this.modalTitle = '';
                    document.body.style.overflow = '';
                }
            }"
            x-on:keydown.escape.window="closeVideo()"
        >
            {{-- dekorasi halus, tidak mengganggu isi --}}
            <div class="pointer-events-none absolute -left-28 top-16 h-72 w-72 rounded-full bg-[#D6B98B]/12 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 bottom-0 h-80 w-80 rounded-full bg-[#8D6A4A]/10 blur-3xl"></div>

            <div class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                {{-- Header editorial: lebih rapat dan tidak terlalu kosong --}}
                <div class="flex flex-col gap-6 border-b border-[#DED3C5] pb-8 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <div class="flex items-center gap-3">
                            <span class="h-px w-10 bg-[#A56F3B]"></span>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#9B6E3E]">
                                {{ $dokVideoSection['subjudul'] }}
                            </p>
                        </div>

                        <h2 class="mt-4 font-display text-3xl font-semibold leading-tight text-[#3D2B1F] sm:text-4xl lg:text-[2.7rem]">
                            {{ $dokVideoSection['judul'] }}
                        </h2>

                        <p class="mt-3 max-w-xl text-sm leading-7 text-[#6F665D] sm:text-[15px]">
                            {{ $dokVideoSection['deskripsi'] }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-3 rounded-full border border-[#DCCFBE] bg-white/70 px-4 py-2 shadow-sm backdrop-blur">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#3D2B1F] text-xs text-white">
                            <i class="fa-solid fa-film"></i>
                        </span>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#9A8C7D]">Koleksi</p>
                            <p class="text-sm font-semibold text-[#3D2B1F]">{{ count($dokVideoItems) }} Video</p>
                        </div>
                    </div>
                </div>

                {{-- Maksimal 4 video per baris. Lebih dari 4 otomatis turun. --}}
                <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($dokVideoItems as $i => $video)
                        @php
                            $videoTitle = $video['keterangan'] ?: 'Dokumentasi '.str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                        @endphp

                        <button
                            type="button"
                            class="group relative overflow-hidden rounded-3xl bg-[#241B15] text-left shadow-[0_18px_50px_-30px_rgba(42,33,27,0.6)] ring-1 ring-[#CDBDA9]/55 transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_28px_65px_-28px_rgba(42,33,27,0.7)] focus:outline-none focus:ring-2 focus:ring-[#A87948]"
                            data-src="{{ $video['src'] }}"
                            data-title="{{ $videoTitle }}"
                            x-on:click="openVideo($el)"
                        >
                            <div class="relative aspect-video overflow-hidden bg-[#2D211A]">
                                <video
                                    src="{{ $video['src'] }}"
                                    class="h-full w-full object-cover opacity-90 transition duration-500 group-hover:scale-105 group-hover:opacity-100"
                                    autoplay muted loop playsinline preload="metadata"
                                    x-data
                                    x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                                ></video>

                                {{-- lapisan visual supaya card tetap bagus saat frame video sedang kosong/loading --}}
                                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_70%_20%,rgba(213,177,126,0.16),transparent_38%)]"></div>
                                <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-[#17110D]/95 via-[#17110D]/20 to-transparent"></div>

                                <span class="absolute left-4 top-4 inline-flex h-8 min-w-8 items-center justify-center rounded-full border border-white/20 bg-black/35 px-2.5 text-[10px] font-semibold tracking-[0.18em] text-white backdrop-blur-md">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-black/30 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/90 backdrop-blur-md">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#D6B077]"></span>
                                    Video
                                </span>

                                {{-- play button modern --}}
                                <span class="absolute left-1/2 top-1/2 flex h-12 w-12 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-white/35 bg-white/15 text-white shadow-lg backdrop-blur-md transition duration-300 group-hover:scale-110 group-hover:bg-white group-hover:text-[#3D2B1F]">
                                    <i class="fa-solid fa-play translate-x-px text-sm"></i>
                                </span>

                                {{-- caption menyatu dengan video, bukan kotak putih panjang --}}
                                <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                    <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-[#DFC49B]">
                                        Dokumentasi Karya
                                    </p>
                                    <h3 class="mt-1.5 line-clamp-2 text-sm font-semibold leading-snug text-white sm:text-[15px]">
                                        {{ $videoTitle }}
                                    </h3>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 flex items-center justify-center gap-3 text-center text-xs text-[#8A7B6A]">
                    <span class="h-px w-8 bg-[#D5C6B3]"></span>
                    <span>Klik video untuk melihat dalam ukuran lebih besar</span>
                    <span class="h-px w-8 bg-[#D5C6B3]"></span>
                </div>
            </div>

            {{-- Modal video: klik kartu -> video besar dengan controls --}}
            <div
                x-cloak
                x-show="modalOpen"
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-[100] flex items-center justify-center bg-[#120D09]/80 p-4 backdrop-blur-md sm:p-8"
                x-on:click.self="closeVideo()"
            >
                <div
                    x-show="modalOpen"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="w-full max-w-5xl overflow-hidden rounded-3xl border border-white/10 bg-[#1C1510] shadow-2xl"
                >
                    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#C8A675]">Dokumentasi</p>
                            <h3 class="mt-1 truncate text-sm font-semibold text-white sm:text-base" x-text="modalTitle"></h3>
                        </div>
                        <button
                            type="button"
                            x-on:click="closeVideo()"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/5 text-white transition hover:bg-white hover:text-[#2A211B]"
                            aria-label="Tutup video"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="bg-black">
                        <template x-if="modalOpen && modalSrc">
                            <video
                                :src="modalSrc"
                                class="max-h-[75vh] w-full bg-black object-contain"
                                autoplay controls playsinline
                            ></video>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    @endif

BLADE;

$newContent = substr($content, 0, $start) . $newBlock . substr($content, $end);
file_put_contents($path, $newContent);

echo "PATCH_OK\n";
'@ | Set-Content -Path $tmpPhp -Encoding UTF8

php $tmpPhp
Remove-Item $tmpPhp -Force

Write-Step '[3/4] Validasi syntax ...'
php -l $target | Out-Host

Write-Step '[4/4] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host "Hero Dokumentasi tidak disentuh." -ForegroundColor Green
Write-Host "Yang diubah hanya tampilan grid video setelah hero." -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Lanjutkan:' -ForegroundColor Yellow
Write-Host '  php artisan view:clear' -ForegroundColor White
Write-Host '  php artisan serve' -ForegroundColor White
