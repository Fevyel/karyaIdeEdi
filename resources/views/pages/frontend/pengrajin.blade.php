<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Our Craftsmen | {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-[#F8F5F0] font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $setting = \App\Models\Setting::current();
        $craftsmenWaNumber = $setting->whatsappDigits();

        $hero = \App\Models\HomeSection::dataFor('our-craftsmen-hero', [
            'bg_color' => null,
            'eyebrow' => 'Our Craftsmen',
            'heading' => 'Tangan-Tangan di Balik Setiap Produk',
            'description' => 'Di balik setiap furnitur ada proses, ketelitian, dan keputusan yang dibuat dengan tangan. Karya Ide Edi tumbuh dari keyakinan bahwa kualitas terbaik lahir dari perhatian pada detail.',
        ]);

        $heroFrame = \App\Support\FrameBackground::resolve($hero['bg_color'] ?? null, $hero['bg_gradient'] ?? null, '#3A2418');
        $heroBase = $heroFrame['base'];

        $heroContrast = function (string $hex): array {
            $hex = ltrim($hex, '#');
            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;
            $lin = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            $lum = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
            return $lum > 0.5
                ? ['title' => '#2D1C13', 'body' => 'rgba(45,28,19,.72)', 'accent' => '#9B6E3E']
                : ['title' => '#FFFDF9', 'body' => 'rgba(255,253,249,.74)', 'accent' => '#D5A66D'];
        };
        $heroColors = $heroContrast($heroBase);

        $owner = \App\Models\HomeSection::dataFor('our-craftsmen-daftar', [
            'bg_color' => null,
            'eyebrow' => 'Pemilik & Founder',
            'name' => 'Pemilik Karya Ide Edi',
            'role' => 'Founder & Creative Director',
            'heading' => 'Berawal dari ketelitian, tumbuh menjadi karya yang dipercaya.',
            'description' => 'Karya Ide Edi dibangun dengan perhatian pada detail, fungsi, dan karakter setiap ruang. Setiap pesanan dikerjakan dengan pendekatan personal agar furnitur tidak hanya mengisi ruang, tetapi benar-benar menjadi bagian dari cerita pemiliknya.',
            'quote' => 'Bagi kami, furnitur yang baik bukan sekadar terlihat indah. Ia harus terasa tepat untuk orang dan ruang yang menggunakannya.',
            'photo_path' => null,
            'stats' => [
                ['value' => 'Custom', 'label' => 'Dibuat sesuai kebutuhan'],
                ['value' => 'Detail', 'label' => 'Fokus pada kerapian'],
                ['value' => 'Jepara', 'label' => 'Berbasis karya lokal'],
            ],
        ]);
        $ownerPhoto = ! empty($owner['photo_path'])
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($owner['photo_path'])
            : null;
        $ownerStats = is_array($owner['stats'] ?? null) ? array_slice(array_values($owner['stats']), 0, 3) : [];
        $ownerFrame = \App\Support\FrameBackground::resolve($owner['bg_color'] ?? null, null, '#F8F5F0');
        $ownerColors = $heroContrast($ownerFrame['base']);

        $cta = \App\Models\HomeSection::dataFor('our-craftsmen-cta', [
            'bg_color' => null,
            'eyebrow' => 'Mulai dari Sebuah Ide',
            'heading' => 'Punya ide furnitur custom?',
            'description' => 'Ceritakan ukuran, fungsi, gaya, atau referensi yang Anda inginkan. Kami siap membantu menerjemahkannya menjadi furnitur yang sesuai dengan ruang Anda.',
            'button_text' => 'Konsultasi via WhatsApp',
        ]);
        $ctaFrame = \App\Support\FrameBackground::resolve($cta['bg_color'] ?? null, null, '#21140D');
        $ctaColors = $heroContrast($ctaFrame['base']);    @endphp

    {{-- HERO --}}
    <section class="relative isolate overflow-hidden" style="background: {{ $heroFrame['css'] }};">
        <div class="pointer-events-none absolute inset-0 opacity-60" aria-hidden="true">
            <div class="absolute -right-20 -top-24 h-96 w-96 rounded-full border border-white/8"></div>
            <div class="absolute -right-8 -top-10 h-64 w-64 rounded-full border border-white/8"></div>
            
        </div>

        <div class="relative mx-auto grid min-h-[34rem] max-w-7xl items-center px-6 py-20 sm:px-8  lg:px-10 lg:py-24">
            <div class="max-w-4xl">
                <div class="flex items-center gap-4 text-xs font-semibold uppercase tracking-[0.34em]" style="color: {{ $heroColors['accent'] }};">
                    <span class="h-px w-10" style="background-color: {{ $heroColors['accent'] }};"></span>
                    {{ $hero['eyebrow'] }}
                </div>

                <h1 class="mt-6 font-display text-5xl font-semibold leading-[1.02] sm:text-6xl lg:text-7xl" style="color: {{ $heroColors['title'] }};">
                    {{ $hero['heading'] }}
                </h1>

                <p class="mt-7 max-w-3xl text-base leading-8 sm:text-lg sm:leading-9" style="color: {{ $heroColors['body'] }};">
                    {{ $hero['description'] }}
                </p>
            </div>
</div>
    </section>

    @include('partials.frontend.frame-seam', ['from' => $heroBase, 'to' => $ownerFrame['base']])

    {{-- PROFIL PEMILIK --}}
    <section class="relative overflow-hidden py-14 sm:py-16 lg:py-20" style="background: {{ $ownerFrame['css'] }};">
        <div class="pointer-events-none absolute -left-24 top-20 h-80 w-80 rounded-full bg-[#EADAC6]/55 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
            <div class="grid items-center gap-12 lg:grid-cols-[0.78fr_1.22fr] lg:gap-14">
                <div class="relative mx-auto w-full max-w-md lg:mx-0">
                    <div class="absolute -left-5 -top-5 h-24 w-24 rounded-4xl border border-[#C99B67]/35"></div>
                    <div class="relative aspect-4/5 overflow-hidden rounded-4xl bg-linear-to-br from-[#E7D6C1] to-[#CDB091] shadow-[0_35px_90px_-45px_rgba(58,36,24,.55)]">
                        @if ($ownerPhoto)
                            <img src="{{ $ownerPhoto }}" alt="{{ $owner['name'] }}" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <div class="flex h-full w-full flex-col items-center justify-center px-10 text-center text-[#684A34]">
                                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-white/55 text-3xl shadow-sm"><i class="fa-solid fa-user-tie"></i></span>
                                <p class="mt-5 font-display text-2xl font-semibold">Foto Pemilik</p>
                                <p class="mt-2 max-w-xs text-sm leading-6 text-[#765D4B]">Unggah foto asli melalui Edit Web &gt; Our Craftsmen &gt; Pemilik &amp; Founder.</p>
                            </div>
                        @endif
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-36 bg-linear-to-t from-[#21140D]/70 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 text-white sm:p-8">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#E8C28F]">{{ $owner['role'] }}</p>
                            <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">{{ $owner['name'] }}</h2>
                        </div>
                    </div>
                    <div class="absolute -bottom-5 -right-5 rounded-3xl bg-[#3A2418] px-5 py-4 text-white shadow-xl">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-[#DDB47E]">Karya Ide Edi</p>
                        <p class="mt-1 text-sm font-semibold">Made with intention</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.32em]" style="color: {{ $ownerColors['accent'] }};">
                        <span class="h-px w-10" style="background-color: {{ $ownerColors['accent'] }};"></span>
                        {{ $owner['eyebrow'] }}
                    </div>

                    <h2 class="mt-5 max-w-3xl font-display text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl" style="color: {{ $ownerColors['title'] }};">
                        {{ $owner['heading'] }}
                    </h2>

                    <p class="mt-6 max-w-3xl text-base leading-8 sm:text-[1.05rem] sm:leading-8" style="color: {{ $ownerColors['body'] }};">
                        {{ $owner['description'] }}
                    </p>

                    @if (! empty($owner['quote']))
                        <blockquote class="mt-7 border-l-2 pl-5 font-display text-lg italic leading-8 sm:text-xl" style="border-color: {{ $ownerColors['accent'] }}; color: {{ $ownerColors['title'] }};">
                            &ldquo;{{ $owner['quote'] }}&rdquo;
                        </blockquote>
                    @endif

                    @if (count($ownerStats) > 0)
                        <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @foreach ($ownerStats as $stat)
                                <div class="rounded-3xl border border-white/20 bg-white/65 p-4 shadow-[0_18px_45px_-38px_rgba(58,36,24,.45)] backdrop-blur">
                                    <p class="font-display text-xl font-semibold text-[#4B2F1F]">{{ $stat['value'] ?? '' }}</p>
                                    <p class="mt-1 text-xs leading-5 text-[#817267]">{{ $stat['label'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="relative overflow-hidden" style="background: {{ $ctaFrame['css'] }};">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-20 -top-28 h-96 w-96 rounded-full bg-[#9F6635]/18 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-[#D3A66E]/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 py-16 sm:px-8 lg:grid-cols-[1fr_auto] lg:px-10 lg:py-20">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.34em]" style="color: {{ $ctaColors['accent'] }};">{{ $cta['eyebrow'] }}</p>
                <h2 class="mt-4 font-display text-3xl font-semibold leading-tight sm:text-4xl" style="color: {{ $ctaColors['title'] }};">{{ $cta['heading'] }}</h2>
                <p class="mt-5 max-w-2xl text-base leading-8" style="color: {{ $ctaColors['body'] }};">{{ $cta['description'] }}</p>
            </div>

            <a
                href="{{ $craftsmenWaNumber ? 'https://wa.me/'.$craftsmenWaNumber : route('booking.index') }}"
                @if ($craftsmenWaNumber) target="_blank" rel="noopener" @endif
                class="group inline-flex w-fit items-center gap-3 rounded-full bg-white px-7 py-4 text-sm font-semibold text-[#2F1D13] shadow-xl transition duration-300 hover:-translate-y-0.5 hover:bg-[#F4E8D8]"
            >
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#2F1D13] text-white"><i class="fa-brands fa-whatsapp"></i></span>
                {{ $cta['button_text'] }}
                <i class="fa-solid fa-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
            </a>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
