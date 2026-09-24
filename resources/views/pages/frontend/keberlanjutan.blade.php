<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sustainability | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $sustainHero = \App\Models\HomeSection::dataFor('sustainability-hero', [
            'bg_color' => null,
            'eyebrow' => 'Sustainability',
            'heading' => 'Kualitas yang Dibuat untuk Bertahan',
            'description' => 'Bagi kami, cara paling nyata untuk mengurangi limbah adalah membuat furnitur yang benar-benar awet dan tidak perlu cepat diganti â€” dikerjakan sesuai pesanan, dari bahan yang dipilih dengan hati-hati.',
        ]);
        $sustainPoints = \App\Models\HomeSection::dataFor('sustainability-points', [
            'bg_color' => null,
            'items' => [
                ['icon' => 'fa-tree', 'title' => 'Bahan Baku Pilihan', 'text' => 'Setiap kayu diseleksi manual sebelum masuk proses produksi, supaya hasil akhirnya kuat dan awet dipakai bertahun-tahun.'],
                ['icon' => 'fa-ruler-combined', 'title' => 'Dibuat Sesuai Pesanan', 'text' => 'Produk custom dikerjakan sesuai ukuran & kebutuhan pemesan â€” mengurangi kelebihan stok dan sisa bahan yang terbuang percuma.'],
                ['icon' => 'fa-hammer', 'title' => 'Dikerjakan Tangan, Bukan Massal', 'text' => 'Diproses langsung oleh tukang kayu berpengalaman, bukan produksi pabrik â€” sehingga tiap detail bisa diperiksa satu per satu.'],
                ['icon' => 'fa-couch', 'title' => 'Furnitur untuk Jangka Panjang', 'text' => 'Kami merancang furnitur yang tahan lama secara struktur, bukan sekadar tampilan â€” supaya lebih jarang perlu diganti.'],
            ],
            'note' => 'Catatan: halaman ini menjelaskan prinsip kerja kami secara umum. Kami akan memperbarui halaman ini kalau ke depannya ada praktik atau sertifikasi keberlanjutan yang lebih spesifik untuk ditampilkan.',
        ]);

        $contrast = function (?string $hex, bool $defaultLight = true): array {
            $hex = $hex ?: ($defaultLight ? '#F9F7F2' : '#FFFFFF');
            $h = ltrim($hex, '#');
            if (! preg_match('/^[0-9A-Fa-f]{6}$/', $h)) $h = 'F9F7F2';
            $r = hexdec(substr($h,0,2)); $g = hexdec(substr($h,2,2)); $b = hexdec(substr($h,4,2));
            $lum = (0.2126*$r + 0.7152*$g + 0.0722*$b) / 255;
            $dark = $lum < .48;
            return $dark
                ? ['title'=>'#FFFFFF','body'=>'rgba(255,255,255,.76)','accent'=>'#E5B878','card'=>'rgba(255,255,255,.07)','border'=>'rgba(255,255,255,.14)','iconbg'=>'rgba(255,255,255,.12)']
                : ['title'=>'#1A1A1A','body'=>'#6B6E76','accent'=>'#A66D32','card'=>'#FFFFFF','border'=>'rgba(26,26,26,.10)','iconbg'=>'#F6ECDB'];
        };

        $heroBg = $sustainHero['bg_color'] ?: '#F9F7F2';
        $pointsBg = $sustainPoints['bg_color'] ?: '#FFFFFF';
        $heroColors = $contrast($heroBg);
        $pointsColors = $contrast($pointsBg, false);
    @endphp

    <section class="relative overflow-hidden" style="background: {{ $heroBg }};">
        <div class="mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em]" style="color: {{ $heroColors['accent'] }};">
                <span class="h-px w-8" style="background: {{ $heroColors['accent'] }};"></span>
                {{ $sustainHero['eyebrow'] }}
                <span class="h-px w-8" style="background: {{ $heroColors['accent'] }};"></span>
            </div>
            <h1 class="mt-4 font-display text-4xl font-semibold leading-tight sm:text-5xl" style="color: {{ $heroColors['title'] }};">
                {{ $sustainHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed sm:text-base" style="color: {{ $heroColors['body'] }};">
                {{ $sustainHero['description'] }}
            </p>
        </div>
    </section>

    <section style="background: {{ $pointsBg }};">
        <div class="mx-auto max-w-5xl px-6 py-14 sm:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                @foreach ($sustainPoints['items'] as $point)
                    <div class="flex items-start gap-4 rounded-2xl p-6" style="background: {{ $pointsColors['card'] }}; border: 1px solid {{ $pointsColors['border'] }};">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" style="background: {{ $pointsColors['iconbg'] }}; color: {{ $pointsColors['accent'] }};">
                            <i class="fa-solid {{ $point['icon'] }}"></i>
                        </span>
                        <div>
                            <h3 class="font-display text-lg font-semibold" style="color: {{ $pointsColors['title'] }};">{{ $point['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed" style="color: {{ $pointsColors['body'] }};">{{ $point['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mx-auto mt-10 max-w-2xl text-center text-xs leading-relaxed" style="color: {{ $pointsColors['body'] }};">
                {{ $sustainPoints['note'] }}
            </p>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>