<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Reveal-on-scroll khusus halaman ini — tidak menyentuh app.css global
           supaya tidak berdampak ke halaman lain. Animasi ringan (fade + slide
           kecil), bukan bounce/blink/parallax. */
        [data-reveal] {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1);
        }
        [data-reveal].is-visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $profileSetting = \App\Models\Setting::current();
        $profileWaNumber = $profileSetting->whatsapp ? preg_replace('/\D/', '', $profileSetting->whatsapp) : null;
    @endphp

    {{-- =====================================================
         A. HERO PROFILE
    ====================================================== --}}
    <section class="relative overflow-hidden bg-[#F9F7F2]">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-14 sm:px-8 lg:grid-cols-2 lg:gap-10 lg:px-10 lg:py-20">
            <div data-reveal>
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                    <span class="h-px w-8 bg-admin-accent"></span>
                    Tentang Kami
                </div>

                <h1 class="mt-5 font-display text-4xl leading-[1.12] text-[#3D2B1F] sm:text-5xl lg:text-[3.25rem]">
                    Mewujudkan Ruang
                    <br class="hidden sm:block">
                    yang Punya Cerita.
                </h1>

                <p class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76] sm:text-base">
                    {{ $profileSetting->site_name }} menghadirkan furnitur yang dibuat dengan
                    teliti untuk melengkapi ruang Anda — bukan sekadar mengisinya. Setiap
                    karya dirancang untuk nyaman digunakan sekaligus enak dipandang, untuk
                    rumah maupun ruang kerja.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a
                        href="{{ route('products.index') }}"
                        class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-6 py-3 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md"
                    >
                        Lihat Produk
                        <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                    </a>
                    <a
                        href="{{ $profileWaNumber ? 'https://wa.me/'.$profileWaNumber : '#' }}"
                        @if ($profileWaNumber) target="_blank" rel="noopener" @endif
                        class="inline-flex items-center gap-2 rounded-lg border border-[#DCDDD7] px-6 py-3 text-sm font-medium text-[#3D2B1F] transition-all duration-300 hover:border-admin-accent hover:text-admin-accent"
                    >
                        <i class="fa-brands fa-whatsapp"></i>
                        Hubungi Kami
                    </a>
                </div>
            </div>

            <div class="relative flex items-center justify-center" data-reveal style="transition-delay:.1s">
                <span class="relative flex aspect-10/9 w-full items-center justify-center overflow-hidden rounded-[28px] bg-[#D7A26E] shadow-xl shadow-black/10">
                    <img
                        src="{{ asset('images/admin-login/hero.png') }}"
                        alt="Furniture {{ $profileSetting->site_name }}"
                        class="h-[88%] w-auto object-contain drop-shadow-2xl"
                    >
                </span>
            </div>
        </div>
    </section>

    {{-- =====================================================
         B. TENTANG KARYA IDE EDI
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
            <div class="order-2 lg:order-1" data-reveal>
                <span class="relative flex aspect-4/5 w-full items-center justify-center overflow-hidden rounded-[24px] bg-admin-cream shadow-lg">
                    <img
                        src="{{ asset('images/admin-login/kursi.png') }}"
                        alt="Furniture {{ $profileSetting->site_name }}"
                        class="h-[82%] w-auto object-contain"
                    >
                </span>
            </div>

            <div class="order-1 lg:order-2" data-reveal style="transition-delay:.1s">
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                    <span class="h-px w-8 bg-admin-accent"></span>
                    Tentang Kami
                </div>

                <h2 class="mt-5 font-display text-3xl leading-tight text-[#4B3A26] sm:text-4xl">
                    Lebih dari sekadar furniture.
                </h2>

                <p class="mt-6 max-w-lg text-sm leading-relaxed text-admin-ink-soft sm:text-base">
                    {{ $profileSetting->site_name }} adalah toko furniture yang menghadirkan
                    produk untuk membantu Anda menciptakan ruang yang nyaman, fungsional, dan
                    punya karakter. Kami percaya furnitur yang baik bukan cuma soal bentuk —
                    tapi juga soal bagaimana ia membuat ruang terasa lebih hidup untuk dipakai
                    sehari-hari.
                </p>

                <p class="mt-4 max-w-lg text-sm leading-relaxed text-admin-ink-soft sm:text-base">
                    Dari kebutuhan rumah tangga sampai ruang kerja, setiap produk kami pilih
                    dan siapkan dengan memperhatikan kualitas bahan, kenyamanan pemakaian, dan
                    kejelasan informasi — supaya Anda bisa memutuskan dengan tenang.
                </p>
            </div>
        </div>
    </section>

    {{-- =====================================================
         C. NILAI / KEUNGGULAN
    ====================================================== --}}
    <section class="bg-admin-cream">
        <div class="mx-auto max-w-7xl px-6 py-16 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-xl text-center" data-reveal>
                <div class="mx-auto flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                    <span class="h-px w-8 bg-admin-accent"></span>
                    Nilai Kami
                    <span class="h-px w-8 bg-admin-accent"></span>
                </div>
                <h2 class="mt-5 font-display text-3xl leading-tight text-[#4B3A26] sm:text-4xl">
                    Yang kami utamakan di setiap karya.
                </h2>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['no' => '01', 'icon' => 'fa-gem', 'title' => 'Kualitas Terpilih', 'desc' => 'Produk dipilih dengan mempertimbangkan kualitas dan fungsi.'],
                    ['no' => '02', 'icon' => 'fa-drafting-compass', 'title' => 'Desain Berkarakter', 'desc' => 'Furniture yang dirancang untuk melengkapi berbagai gaya ruang.'],
                    ['no' => '03', 'icon' => 'fa-shield-halved', 'title' => 'Pelayanan Terpercaya', 'desc' => 'Memberikan pengalaman belanja yang nyaman dan jelas.'],
                    ['no' => '04', 'icon' => 'fa-house', 'title' => 'Untuk Setiap Ruang', 'desc' => 'Pilihan furniture untuk kebutuhan rumah maupun ruang kerja.'],
                ] as $i => $value)
                    <div
                        data-reveal
                        style="transition-delay:{{ $i * .08 }}s"
                        class="group rounded-2xl border border-admin-border bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5"
                    >
                        <p class="font-display text-2xl text-admin-gold">{{ $value['no'] }}</p>
                        <span class="mt-4 flex h-12 w-12 items-center justify-center rounded-xl bg-admin-cream text-admin-accent transition-colors duration-300 group-hover:bg-admin-accent group-hover:text-white">
                            <i class="fa-solid {{ $value['icon'] }}"></i>
                        </span>
                        <p class="mt-4 text-base font-semibold text-[#3D2B1F]">{{ $value['title'] }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-admin-ink-soft">{{ $value['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =====================================================
         D. WHY CHOOSE US
    ====================================================== --}}
    <section class="bg-[#221B14]">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-24">
            <div data-reveal>
                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-gold">
                    <span class="h-px w-8 bg-admin-gold"></span>
                    Why Choose Us
                </div>
                <h2 class="mt-5 font-display text-3xl leading-tight text-white sm:text-4xl">
                    Kenapa memilih {{ $profileSetting->site_name }}?
                </h2>
                <p class="mt-5 max-w-md text-sm leading-relaxed text-white/60 sm:text-base">
                    Kami ingin proses memilih furniture terasa mudah dan tenang — dari
                    melihat produk sampai memutuskan yang paling cocok untuk ruang Anda.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-reveal style="transition-delay:.1s">
                @foreach ([
                    ['icon' => 'fa-layer-group', 'text' => 'Produk pilihan'],
                    ['icon' => 'fa-circle-info', 'text' => 'Informasi produk yang jelas'],
                    ['icon' => 'fa-cart-shopping', 'text' => 'Proses pemesanan mudah'],
                    ['icon' => 'fa-headset', 'text' => 'Dukungan pelanggan'],
                    ['icon' => 'fa-couch', 'text' => 'Pengalaman belanja yang nyaman'],
                ] as $point)
                    <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-admin-gold/15 text-admin-gold">
                            <i class="fa-solid {{ $point['icon'] }} text-sm"></i>
                        </span>
                        <p class="text-sm font-medium text-white">{{ $point['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =====================================================
         E. VALUE PROPOSITION
         (Bukan section statistik angka — belum ada data
         jumlah pelanggan/produk/tahun berdiri di database,
         jadi memakai pernyataan nilai tanpa angka karangan.)
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20" data-reveal>
            <i class="fa-solid fa-quote-left text-2xl text-admin-gold"></i>
            <p class="mt-6 font-display text-2xl leading-snug text-[#3D2B1F] sm:text-3xl">
                Pilihan furniture untuk ruang yang lebih nyaman —
                dipilih dengan teliti, disampaikan dengan jelas.
            </p>
        </div>
    </section>

    {{-- =====================================================
         F. CARA BERBELANJA
    ====================================================== --}}
    <section class="bg-admin-cream">
        <div class="mx-auto max-w-7xl px-6 py-16 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-xl text-center" data-reveal>
                <div class="mx-auto flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                    <span class="h-px w-8 bg-admin-accent"></span>
                    Cara Berbelanja
                    <span class="h-px w-8 bg-admin-accent"></span>
                </div>
                <h2 class="mt-5 font-display text-3xl leading-tight text-[#4B3A26] sm:text-4xl">
                    Empat langkah mudah.
                </h2>
            </div>

            <div class="relative mt-14 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Garis penghubung — desktop saja --}}
                <div class="pointer-events-none absolute left-0 right-0 top-6 hidden h-px bg-admin-border lg:block"></div>

                @foreach ([
                    ['no' => '01', 'icon' => 'fa-layer-group', 'title' => 'Pilih Produk', 'desc' => 'Jelajahi katalog dan temukan furniture yang sesuai kebutuhan.'],
                    ['no' => '02', 'icon' => 'fa-magnifying-glass', 'title' => 'Lihat Detail', 'desc' => 'Cek foto, spesifikasi, dan harga di halaman detail produk.'],
                    ['no' => '03', 'icon' => 'fa-comment-dots', 'title' => 'Pesan Produk', 'desc' => 'Hubungi kami lewat WhatsApp untuk melanjutkan pemesanan.'],
                    ['no' => '04', 'icon' => 'fa-box-open', 'title' => 'Produk Siap Diproses', 'desc' => 'Pesanan dikonfirmasi dan mulai diproses untuk Anda.'],
                ] as $i => $step)
                    <div class="relative flex flex-col items-center text-center" data-reveal style="transition-delay:{{ $i * .08 }}s">
                        <span class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 border-admin-accent bg-white text-admin-accent">
                            <i class="fa-solid {{ $step['icon'] }} text-sm"></i>
                        </span>
                        <p class="mt-4 font-display text-lg text-admin-gold">{{ $step['no'] }}</p>
                        <p class="mt-1 text-base font-semibold text-[#3D2B1F]">{{ $step['title'] }}</p>
                        <p class="mt-2 max-w-50 text-sm leading-relaxed text-admin-ink-soft">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =====================================================
         G. CTA
    ====================================================== --}}
    <section class="bg-[#2A1B12]">
        <div class="mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20" data-reveal>
            <h2 class="font-display text-3xl leading-tight text-white sm:text-4xl">
                Temukan furniture yang tepat untuk ruangmu.
            </h2>
            <p class="mt-4 text-sm leading-relaxed text-white/60 sm:text-base">
                Jelajahi katalog produk kami dan mulai lengkapi ruang Anda hari ini.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a
                    href="{{ route('products.index') }}"
                    class="group inline-flex items-center gap-2 rounded-lg bg-admin-accent px-6 py-3 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-admin-accent-strong hover:shadow-md"
                >
                    Lihat Produk
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
                <a
                    href="{{ $profileWaNumber ? 'https://wa.me/'.$profileWaNumber : '#' }}"
                    @if ($profileWaNumber) target="_blank" rel="noopener" @endif
                    class="inline-flex items-center gap-2 rounded-lg border border-white/20 px-6 py-3 text-sm font-medium text-white transition-all duration-300 hover:border-white/40 hover:bg-white/5"
                >
                    <i class="fa-brands fa-whatsapp"></i>
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>

    @include('partials.frontend.footer')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const revealTargets = document.querySelectorAll('[data-reveal]');

            if (!('IntersectionObserver' in window) || revealTargets.length === 0) {
                revealTargets.forEach((el) => el.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

            revealTargets.forEach((el) => observer.observe(el));
        });
    </script>
</body>
</html>
