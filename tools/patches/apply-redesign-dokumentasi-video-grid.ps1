$ErrorActionPreference = 'Stop'

function Write-Step($message) {
    Write-Host "`n$message" -ForegroundColor Cyan
}

function Assert-ProjectRoot {
    if (-not (Test-Path '.\artisan')) {
        throw "File 'artisan' tidak ditemukan. Jalankan script ini dari root project Laravel (folder karyaIdeEdi)."
    }
}

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Redesign Halaman Dokumentasi -> Grid Video 4 Kolom' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

Assert-ProjectRoot

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupDir = Join-Path (Get-Location) ".backup-redesign-dokumentasi-video-grid-$timestamp"
$editWebPath = '.\resources\views\pages\admin\edit-web.blade.php'
$bookingPath = '.\resources\views\pages\frontend\booking.blade.php'

Write-Step '[1/5] Membuat folder backup ...'
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $backupDir 'resources\views\pages\admin') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $backupDir 'resources\views\pages\frontend') -Force | Out-Null
Copy-Item $editWebPath (Join-Path $backupDir 'resources\views\pages\admin\edit-web.blade.php') -Force
Copy-Item $bookingPath (Join-Path $backupDir 'resources\views\pages\frontend\booking.blade.php') -Force
Write-Host "  Backup dibuat di: $backupDir" -ForegroundColor DarkGray

Write-Step '[2/5] Menerapkan patch source ...'
$tmpPhp = Join-Path $env:TEMP "patch-redesign-dokumentasi-video-grid-$timestamp.php"

@'
<?php
$root = getcwd();
$edit = $root . DIRECTORY_SEPARATOR . 'resources/views/pages/admin/edit-web.blade.php';
$booking = $root . DIRECTORY_SEPARATOR . 'resources/views/pages/frontend/booking.blade.php';

function replace_or_skip(string $content, string $search, string $replace, string $label): string
{
    if (strpos($content, $replace) !== false && strpos($content, $search) === false) {
        echo "  - {$label}: sudah sesuai\n";
        return $content;
    }

    if (strpos($content, $search) === false) {
        throw new RuntimeException("Pattern tidak ditemukan: {$label}");
    }

    echo "  - {$label}: dipatch\n";
    return str_replace($search, $replace, $content);
}

$editContent = file_get_contents($edit);
$bookingContent = file_get_contents($booking);

$search = <<<'TXT'
        ['key' => 'dokumentasi', 'label' => 'Hero & Galeri', 'icon' => 'fa-images', 'ready' => true],
        ['key' => 'dokumentasi-3', 'label' => 'Dokumentasi 3', 'icon' => 'fa-layer-group', 'ready' => true],
TXT;
$replace = <<<'TXT'
        ['key' => 'dokumentasi', 'label' => 'Hero', 'icon' => 'fa-images', 'ready' => true],
        ['key' => 'dokumentasi-3', 'label' => 'Galeri Video', 'icon' => 'fa-layer-group', 'ready' => true],
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Label sidebar Dokumentasi');

$search = <<<'TXT'
            'description' => 'Hero & Galeri, Dokumentasi 3 (kartu 3D).',
TXT;
$replace = <<<'TXT'
            'description' => 'Hero dan galeri video dokumentasi.',
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Deskripsi kartu grup Dokumentasi');

$search = <<<'TXT'
            'judul' => 'Arsip Pilihan',
            'subjudul' => 'Kartu Karya',
            'deskripsi' => 'Beberapa karya yang paling sering ditanyakan pelanggan. Arahkan kursor ke salah satu kartu untuk melihatnya lebih dekat.',
TXT;
$replace = <<<'TXT'
            'judul' => 'Galeri Video',
            'subjudul' => 'Dokumentasi Proses & Hasil',
            'deskripsi' => 'Kumpulan video proses kerja dan hasil akhir furnitur Karya Ide Edi. Tampil rapi seperti katalog, maksimal 4 video per baris lalu lanjut otomatis ke bawah.',
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Default teks Galeri Video');

$search = <<<'TXT'
        $this->dok3Tipe[$key] = 'foto';
TXT;
$replace = <<<'TXT'
        $this->dok3Tipe[$key] = 'video';
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Default item baru = video');

$search = <<<'TXT'
                            Dokumentasi 3 &mdash; Kartu 3D Bertumpuk
TXT;
$replace = <<<'TXT'
                            Galeri Video &mdash; Grid Modern
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Judul panel Galeri Video');

$search = <<<'TXT'
                            Kartu foto/video bertumpuk yang "mengipas" miring 3D begitu masuk layar, lalu menegak &amp; terangkat saat disentuh kursor. Section ini TAMBAHAN di bawah hero &amp; galeri
                            halaman Dokumentasi &mdash; hero dan galeri di atasnya tidak
                            ikut berubah. Video di halaman Dokumentasi langsung
                            <strong>autoplay &amp; mute permanen</strong> (tidak perlu diklik
                            untuk main, dan tidak ada tombol suara) karena ini galeri, bukan
                            pemutar video.
TXT;
$replace = <<<'TXT'
                            Section ini dipakai untuk mengisi isi utama halaman Dokumentasi dalam bentuk <strong>grid video modern</strong> seperti katalog produk.
                            Maksimal <strong>4 video per baris</strong> di layar besar, lalu otomatis lanjut ke bawah kalau videonya lebih banyak.
                            Video di halaman Dokumentasi langsung <strong>autoplay &amp; mute permanen</strong> agar tampil elegan dan ringan dilihat.
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Deskripsi panel Galeri Video');

$search = <<<'TXT'
                                <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Isi Kartu</p>
TXT;
$replace = <<<'TXT'
                                <p class="text-xs font-semibold uppercase tracking-wide text-admin-ink-soft">Daftar Video</p>
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Label blok daftar video');

$search = <<<'TXT'
                                    Urutan kartu = urutan tumpukannya dari kiri ke kanan. Jumlahnya BEBAS, tidak ada batas maksimal --
                                    klik "Tambah" untuk menambah sebanyak yang dibutuhkan, atau
                                    hapus yang tidak dipakai. Kalau belum ada satu pun yang diisi,
                                    halaman Dokumentasi memakai isi dummy sementara dari internet.
TXT;
$replace = <<<'TXT'
                                    Urutan video mengikuti urutan tampil di halaman dari kiri ke kanan, lalu lanjut ke baris berikutnya. Jumlahnya BEBAS, tidak ada batas maksimal.
                                    Gunakan format ini khusus untuk <strong>video</strong>. Klik "Tambah Video" untuk menambah item baru, lalu hapus yang tidak dipakai.
                                    Kalau belum ada satu pun yang diisi, halaman Dokumentasi memakai video dummy sementara dari internet.
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Penjelasan daftar video');

$search = <<<'TXT'
                                <i class="fa-solid fa-plus text-[10px]"></i> Tambah Kartu
TXT;
$replace = <<<'TXT'
                                <i class="fa-solid fa-plus text-[10px]"></i> Tambah Video
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Tombol tambah video');

$search = <<<'TXT'
                                Belum ada kartu yang ditambahkan. Klik "Tambah Kartu" di atas untuk mulai mengisi.
TXT;
$replace = <<<'TXT'
                                Belum ada video yang ditambahkan. Klik "Tambah Video" di atas untuk mulai mengisi.
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Empty state galeri video');

$search = <<<'TXT'
                                            Kartu {{ $urutan + 1 }}
TXT;
$replace = <<<'TXT'
                                            Video {{ $urutan + 1 }}
TXT;
$editContent = replace_or_skip($editContent, $search, $replace, 'Label per item video');

$bookingBlockStart = strpos($bookingContent, <<<'TXT'
    @php
        // Galeri "Dokumentasi"
TXT);
$bookingBlockEnd = strpos($bookingContent, <<<'TXT'
    @include('partials.frontend.footer')
TXT);

if ($bookingBlockEnd === false) {
    throw new RuntimeException("Marker footer booking.blade.php tidak ditemukan.");
}

$newBookingBlock = <<<'BLADE'
    @php
        // Isi utama halaman Dokumentasi sekarang memakai section_key
        // 'dokumentasi-3' sebagai galeri video bergaya katalog. Hero di
        // atas TIDAK diubah. Kalau admin belum mengisi section ini,
        // halaman otomatis mencoba mengambil video dari galeri lama;
        // kalau tetap kosong, baru pakai dummy sementara.
        $dokVideoSection = \App\Models\HomeSection::dataFor('dokumentasi-3', [
            'judul' => 'Galeri Video',
            'subjudul' => 'Dokumentasi Proses & Hasil',
            'deskripsi' => 'Kumpulan video proses kerja dan hasil akhir furnitur Karya Ide Edi. Tampil rapi seperti katalog, maksimal 4 video per baris lalu lanjut otomatis ke bawah.',
            'items' => [],
        ]);

        $dokFallbackVideos = [
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4', 'keterangan' => 'Proses produksi furnitur custom'],
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 'keterangan' => 'Detail finishing dan perakitan'],
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 'keterangan' => 'Hasil akhir siap dikirim ke pelanggan'],
            ['src' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4', 'keterangan' => 'Cuplikan pengerjaan dari dekat'],
        ];

        $dokVideoBentukItem = function (array $item) {
            $src = isset($item['path']) && $item['path']
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path'])
                : ($item['url'] ?? null);

            if (! $src) {
                return null;
            }

            if (($item['tipe'] ?? null) !== 'video') {
                return null;
            }

            return [
                'src' => $src,
                'keterangan' => trim((string) ($item['keterangan'] ?? '')),
            ];
        };

        $dokVideoFromDok3 = array_values(array_filter(array_map($dokVideoBentukItem, is_array($dokVideoSection['items'] ?? null) ? $dokVideoSection['items'] : [])));
        $dokVideoFromLegacyGallery = array_values(array_filter(array_map($dokVideoBentukItem, is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : [])));

        $dokVideoItems = count($dokVideoFromDok3) > 0
            ? $dokVideoFromDok3
            : (count($dokVideoFromLegacyGallery) > 0 ? $dokVideoFromLegacyGallery : $dokFallbackVideos);
    @endphp

    @if (count($dokVideoItems) > 0)
        <section class="relative overflow-hidden bg-[#F7F4EF] py-16 sm:py-20 lg:py-24">
            <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[#F0E7DA]/80 to-transparent"></div>

            <div class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-[#9B6E3E]">
                        {{ $dokVideoSection['subjudul'] }}
                    </p>
                    <h2 class="mt-4 font-display text-3xl font-semibold text-[#3D2B1F] sm:text-4xl">
                        {{ $dokVideoSection['judul'] }}
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-[#6B6E76] sm:text-[15px]">
                        {{ $dokVideoSection['deskripsi'] }}
                    </p>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($dokVideoItems as $i => $video)
                        <article class="group overflow-hidden rounded-[1.75rem] border border-[#E6DACC] bg-white shadow-[0_22px_60px_-38px_rgba(61,43,31,0.4)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_70px_-34px_rgba(61,43,31,0.52)]">
                            <div class="relative aspect-[4/5] overflow-hidden bg-[#E7D8C4]">
                                <span class="absolute left-4 top-4 z-10 inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold tracking-[0.18em] text-[#6F5132] shadow-sm backdrop-blur uppercase">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="absolute right-4 top-4 z-10 inline-flex items-center gap-1 rounded-full bg-[#1F1813]/65 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur">
                                    <i class="fa-solid fa-play text-[9px]"></i> Video
                                </span>

                                <video
                                    src="{{ $video['src'] }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                    autoplay muted loop playsinline preload="metadata"
                                    x-data x-init="$el.muted = true; $el.volume = 0; $el.addEventListener('volumechange', () => { $el.muted = true; $el.volume = 0; })"
                                ></video>

                                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-[#1E1712]/85 via-[#1E1712]/28 to-transparent"></div>
                            </div>

                            <div class="space-y-3 px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold leading-relaxed text-[#3D2B1F]">
                                        {{ $video['keterangan'] ?: 'Video Dokumentasi '.str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                    </h3>
                                    <span class="shrink-0 rounded-full bg-[#F3E9DC] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#9B6E3E]">
                                        Auto
                                    </span>
                                </div>

                                <p class="text-xs leading-relaxed text-[#7A6B5D]">
                                    Cuplikan dokumentasi proses dan hasil pengerjaan furnitur Karya Ide Edi.
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p class="mt-6 text-center text-xs leading-relaxed text-[#8A7B6A]">
                    Semua video diputar otomatis tanpa suara agar tampilan tetap rapi, ringan, dan nyaman dilihat seperti katalog modern.
                </p>
            </div>
        </section>
    @endif

BLADE;

if (strpos($bookingContent, 'Isi utama halaman Dokumentasi sekarang memakai section_key') !== false) {
    echo "  - Konten frontend Dokumentasi: sudah sesuai\n";
} else {
    if ($bookingBlockStart === false || $bookingBlockEnd <= $bookingBlockStart) {
        throw new RuntimeException("Blok konten Dokumentasi di booking.blade.php tidak ditemukan.");
    }

    echo "  - Konten frontend Dokumentasi: dipatch\n";
    $bookingContent = substr($bookingContent, 0, $bookingBlockStart) . $newBookingBlock . substr($bookingContent, $bookingBlockEnd);
}

file_put_contents($edit, $editContent);
file_put_contents($booking, $bookingContent);

echo "PATCH_OK\n";
'@ | Set-Content -Path $tmpPhp -Encoding UTF8

php $tmpPhp
Remove-Item $tmpPhp -Force

Write-Step '[3/5] Validasi syntax PHP / Blade ...'
php -l $editWebPath | Out-Host
php -l $bookingPath | Out-Host

Write-Step '[4/5] Ringkasan perubahan ...'
Write-Host '  - resources/views/pages/frontend/booking.blade.php' -ForegroundColor DarkGray
Write-Host '    -> konten halaman Dokumentasi setelah hero diubah menjadi grid video modern (maksimal 4 kolom per baris).' -ForegroundColor DarkGray
Write-Host '  - resources/views/pages/admin/edit-web.blade.php' -ForegroundColor DarkGray
Write-Host '    -> label section admin diperbarui menjadi Hero + Galeri Video, default item baru menjadi video.' -ForegroundColor DarkGray

Write-Step '[5/5] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host ''
Write-Host 'Lanjutkan dengan:' -ForegroundColor Yellow
Write-Host '  php artisan view:clear' -ForegroundColor White
Write-Host '  php artisan serve' -ForegroundColor White
