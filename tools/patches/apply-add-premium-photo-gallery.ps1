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
Write-Host ' Tambah Galeri Foto Premium di Halaman Dokumentasi' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

Assert-ProjectRoot

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupDir = Join-Path (Get-Location) ".backup-add-premium-photo-gallery-$timestamp"
$bookingPath = '.\resources\views\pages\frontend\booking.blade.php'

Write-Step '[1/5] Membuat backup ...'
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $backupDir 'resources\views\pages\frontend') -Force | Out-Null
Copy-Item $bookingPath (Join-Path $backupDir 'resources\views\pages\frontend\booking.blade.php') -Force
Write-Host "  Backup dibuat di: $backupDir" -ForegroundColor DarkGray

Write-Step '[2/5] Menambahkan section Galeri Foto premium ...'
$tmpPhp = Join-Path $env:TEMP "patch-add-premium-photo-gallery-$timestamp.php"

@'
<?php
$path = getcwd() . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/booking.blade.php';
$content = file_get_contents($path);
if ($content === false) {
    throw new RuntimeException('Gagal membaca booking.blade.php');
}

if (strpos($content, 'GALERI FOTO PREMIUM') !== false) {
    echo "  - Section Galeri Foto premium: sudah ada, dilewati\n";
    echo "PATCH_OK\n";
    exit(0);
}

$insertNeedle = "    @include('partials.frontend.footer')";
$insertPos = strpos($content, $insertNeedle);
if ($insertPos === false) {
    throw new RuntimeException("Marker footer tidak ditemukan.");
}

$newBlock = <<<'BLADE'

    @php
        // =========================================================
        // GALERI FOTO PREMIUM — memakai galeri lama di section_key
        // 'dokumentasi' / data hero yang sama, tetapi DIAMBIL KHUSUS
        // item bertipe foto saja. Jadi admin cukup isi foto di Edit Web
        // > Dokumentasi > Hero (galeri) dan halaman ini otomatis sinkron.
        // =========================================================
        $dokFallbackPhotos = [
            ['src' => 'https://picsum.photos/seed/kie-photo-a/1200/900', 'keterangan' => 'Detail pengerjaan furnitur custom'],
            ['src' => 'https://picsum.photos/seed/kie-photo-b/1200/900', 'keterangan' => 'Area workshop dan proses produksi'],
            ['src' => 'https://picsum.photos/seed/kie-photo-c/1200/900', 'keterangan' => 'Finishing dan quality control'],
            ['src' => 'https://picsum.photos/seed/kie-photo-d/1200/900', 'keterangan' => 'Hasil furnitur siap kirim'],
            ['src' => 'https://picsum.photos/seed/kie-photo-e/1200/900', 'keterangan' => 'Pemasangan dan penataan akhir'],
            ['src' => 'https://picsum.photos/seed/kie-photo-f/1200/900', 'keterangan' => 'Sudut dokumentasi pilihan'],
        ];

        $dokPhotoBentukItem = function (array $item) {
            $src = isset($item['path']) && $item['path']
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path'])
                : ($item['url'] ?? null);

            if (! $src || ($item['tipe'] ?? null) !== 'foto') {
                return null;
            }

            return [
                'src' => $src,
                'keterangan' => trim((string) ($item['keterangan'] ?? '')),
            ];
        };

        $dokPhotoFromHeroGallery = array_values(array_filter(array_map(
            $dokPhotoBentukItem,
            is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : []
        )));

        $dokPhotoItems = count($dokPhotoFromHeroGallery) > 0
            ? $dokPhotoFromHeroGallery
            : $dokFallbackPhotos;

        $dokPhotoCount = count($dokPhotoItems);
    @endphp

    @if ($dokPhotoCount > 0)
        <section class="relative overflow-hidden border-t border-[#E7DCCF] bg-white py-18 sm:py-20 lg:py-24">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-linear-to-b from-[#F6EFE6]/85 to-transparent"></div>
            <div class="pointer-events-none absolute left-10 top-24 h-40 w-40 rounded-full bg-[#F0E0CC]/55 blur-3xl"></div>
            <div class="pointer-events-none absolute right-0 bottom-0 h-56 w-56 rounded-full bg-[#E8D3B8]/35 blur-3xl"></div>

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
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_21rem] lg:items-end">
                    <div>
                        <div class="mb-4 flex items-center gap-3">
                            <span class="h-px w-10 bg-[#C39058]"></span>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.42em] text-[#BC8651] sm:text-xs">
                                Galeri Foto
                            </p>
                        </div>

                        <h2 class="font-display text-4xl font-semibold leading-none text-[#3D2B1F] sm:text-5xl lg:text-[3.8rem]">
                            Momen Karya dalam Bingkai
                        </h2>

                        <p class="mt-6 max-w-2xl text-base leading-9 text-[#6E6357] sm:text-lg">
                            Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.
                        </p>
                    </div>

                    <div class="rounded-4xl border border-[#E2D2BF] bg-[#FCFAF7] p-5 shadow-[0_18px_50px_-38px_rgba(61,43,31,0.35)]">
                        <div class="flex items-center gap-4">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#4B2F1F] text-white shadow-lg shadow-[#4B2F1F]/25">
                                <i class="fa-solid fa-camera-retro text-base"></i>
                            </span>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#AA8E71]">Portofolio</p>
                                <p class="text-lg font-semibold text-[#3D2B1F]">{{ $dokPhotoCount }} Foto Pilihan</p>
                            </div>
                        </div>

                        <div class="mt-4 h-px bg-linear-to-r from-[#E1D2BF] to-transparent"></div>
                        <p class="mt-4 text-sm leading-7 text-[#7A6B5E]">
                            Disusun seperti editorial gallery agar tampilan dokumentasi terasa lebih estetik, mewah, dan meyakinkan bagi calon pelanggan.
                        </p>
                    </div>
                </div>

                <div class="mt-10 grid auto-rows-[220px] grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($dokPhotoItems as $i => $photo)
                        @php
                            $nomorFoto = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                            $judulFoto = $photo['keterangan'] ?: 'Foto Dokumentasi '.$nomorFoto;
                            $layoutClass = match ($i % 6) {
                                0 => 'md:col-span-2 md:row-span-2',
                                1 => 'xl:row-span-2',
                                2 => 'md:col-span-1',
                                3 => 'md:col-span-1',
                                4 => 'md:col-span-2',
                                default => 'md:col-span-1',
                            };
                        @endphp

                        <button
                            type="button"
                            x-on:click="show(@js($photo['src']), @js($judulFoto), @js($nomorFoto))"
                            class="group relative {{ $layoutClass }} overflow-hidden rounded-4xl border border-[#E7DACB] bg-[#F3E6D5] text-left shadow-[0_22px_60px_-40px_rgba(61,43,31,0.4)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_26px_70px_-34px_rgba(61,43,31,0.5)] focus:outline-none focus:ring-2 focus:ring-[#9B6E3E]/25"
                        >
                            <img
                                src="{{ $photo['src'] }}"
                                alt="{{ $judulFoto }}"
                                class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.05]"
                                loading="lazy"
                            >

                            <div class="absolute inset-0 bg-linear-to-t from-[#140D08]/88 via-[#140D08]/22 to-transparent"></div>
                            <div class="absolute inset-0 bg-linear-to-br from-[#8D5E36]/15 via-transparent to-transparent opacity-80"></div>

                            <div class="absolute left-4 right-4 top-4 flex items-start justify-between gap-3">
                                <span class="inline-flex min-w-12 items-center justify-center rounded-full border border-white/18 bg-black/24 px-3 py-2 text-xs font-semibold tracking-[0.16em] text-white backdrop-blur">
                                    {{ $nomorFoto }}
                                </span>

                                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-md">
                                    <i class="fa-solid fa-image text-[10px]"></i>
                                    Foto
                                </span>
                            </div>

                            <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-[#E8C692]">
                                    Dokumentasi Visual
                                </p>
                                <h3 class="mt-2 line-clamp-2 text-xl font-semibold leading-snug text-white sm:text-[1.35rem]">
                                    {{ $judulFoto }}
                                </h3>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 flex items-center justify-center gap-4 text-center text-sm text-[#9A7E61]">
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                    <p>Klik foto untuk melihat dalam ukuran lebih besar</p>
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                </div>

                <div
                    x-show="open"
                    x-transition.opacity
                    x-cloak
                    class="fixed inset-0 z-100 flex items-center justify-center bg-[#120C08]/80 px-4 py-6 backdrop-blur-sm"
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
                        class="relative z-10 w-full max-w-6xl overflow-hidden rounded-4xl border border-white/12 bg-[#17110E] shadow-[0_30px_100px_-36px_rgba(0,0,0,0.72)]"
                    >
                        <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-[#D4B083]" x-text="'Foto ' + activeNumber"></p>
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

                        <div class="bg-[#120C08] p-3 sm:p-4">
                            <div class="overflow-hidden rounded-3xl bg-[#120C08]">
                                <img
                                    x-bind:src="activeSrc"
                                    x-bind:alt="activeTitle"
                                    class="max-h-[78vh] w-full object-contain"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
BLADE;

$content = substr($content, 0, $insertPos) . $newBlock . substr($content, $insertPos);
file_put_contents($path, $content);

echo "  - Section Galeri Foto premium: ditambahkan\n";
echo "PATCH_OK\n";
'@ | Set-Content -Path $tmpPhp -Encoding UTF8

php $tmpPhp
Remove-Item $tmpPhp -Force

Write-Step '[3/5] Validasi syntax ...'
php -l $bookingPath | Out-Host

Write-Step '[4/5] Ringkasan perubahan ...'
Write-Host '  - Menambahkan section Galeri Foto premium di bawah galeri video.' -ForegroundColor DarkGray
Write-Host '  - Sumber foto diambil dari Edit Web > Dokumentasi > Hero (galeri), khusus item bertipe foto.' -ForegroundColor DarkGray
Write-Host '  - Tampilannya bento/editorial, modern, estetik, dan profesional.' -ForegroundColor DarkGray
Write-Host '  - Klik foto akan membuka lightbox/modal besar.' -ForegroundColor DarkGray
Write-Host '  - Hero dan galeri video tidak disentuh.' -ForegroundColor DarkGray

Write-Step '[5/5] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Hero dan galeri video tidak disentuh.' -ForegroundColor Green
Write-Host 'Yang ditambahkan hanya Galeri Foto premium.' -ForegroundColor Green
