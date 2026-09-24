<!DOCTYPE html>
<html lang="id" data-site="frontend">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Toko Mebel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    </head>
    <body class="min-h-screen bg-admin-canvas font-sans antialiased">

        {{-- ================= NAVBAR ================= --}}
        @php $siteSetting = \App\Models\Setting::current(); @endphp
        @include('partials.frontend.navbar')

        {{--
            ================= WARNA UNTUK FRAME SEAM =================
            Dihitung di sini (bukan mengubah partial section manapun)
            supaya semua batas frame di Beranda (kecuali navbar &
            footer) bisa dikasih transisi halus lewat
            partials.frontend.frame-seam -- lihat komentar lengkap di
            file itu. Section, section_key, dan warna fallback di
            bawah SAMA PERSIS dengan yang dipakai section terkait
            sendiri (hero.blade.php, mission.blade.php, dst) --
            hanya diulang di sini buat ambil warna dasarnya saja,
            TIDAK menyentuh/menduplikasi logika tampilnya.
        --}}
        @php
            $seamHeaderSection = \App\Models\HomeSection::dataFor('header', ['bg_color' => null]);
            $seamHero = $seamHeaderSection['bg_color'] ?: '#F9F7F2';

            $seamFeatures = '#FFFFFF'; // features.blade.php: bg-admin-surface (frontend selalu tema terang)

            $seamMissionSection = \App\Models\HomeSection::dataFor('sejak-berdiri', ['bg_color' => null, 'bg_gradient' => null]);
            $seamMission = \App\Support\FrameBackground::resolve($seamMissionSection['bg_color'] ?? null, $seamMissionSection['bg_gradient'] ?? null, '#FEEDD8')['base'];

            $seamProdukSection = \App\Models\HomeSection::dataFor('produk-unggulan', ['bg_color' => null, 'bg_gradient' => null]);
            $seamProduk = \App\Support\FrameBackground::resolve($seamProdukSection['bg_color'] ?? null, $seamProdukSection['bg_gradient'] ?? null, '#FFFFFF')['base'];

            $seamKategoriSection = \App\Models\HomeSection::dataFor('kategori', ['bg_color' => null, 'bg_gradient' => null]);
            $seamKategori = \App\Support\FrameBackground::resolve($seamKategoriSection['bg_color'] ?? null, $seamKategoriSection['bg_gradient'] ?? null, '#FEEDD8')['base'];

            $seamTestimoniSection = \App\Models\HomeSection::dataFor('testimoni', ['bg_color' => null, 'bg_gradient' => null]);
            $seamTestimoni = \App\Support\FrameBackground::resolve($seamTestimoniSection['bg_color'] ?? null, $seamTestimoniSection['bg_gradient'] ?? null, '#FAF8F4')['base'];

            $seamExpertise = '#FFFFFF'; // expertise.blade.php: bg-white
            $seamAlurBooking = '#F7F4EF'; // alur-booking.blade.php: bg-[#F7F4EF]
            $seamFaq = '#FFFFFF'; // faq.blade.php: bg-white

            $seamLokasiSection = \App\Models\HomeSection::dataFor('lokasi', ['bg_color' => null, 'bg_gradient' => null]);
            $seamLokasi = \App\Support\FrameBackground::resolve($seamLokasiSection['bg_color'] ?? null, $seamLokasiSection['bg_gradient'] ?? null, '#FFFFFF')['base'];
        @endphp

        {{-- ================= HERO ================= --}}
        @include('partials.frontend.hero')

        {{-- ================= KEUNGGULAN ================= --}}
        {{-- Sengaja TANPA frame-seam sebelum/sesudahnya: section ini terlalu
             tipis (~100px), sementara reach frame-seam (sampai 140px total
             di layar besar) sampai menutup TOTAL isinya (ikon & teks
             ketutup lapisan seam yang z-index-nya lebih tinggi). Transisi
             halusnya sudah ditangani gradasi background section ini sendiri
             lewat bgFrom/bgTo di bawah. --}}
        @include('partials.frontend.features', ['bgFrom' => $seamHero, 'bgTo' => $seamMission])

        {{-- ================= SEJAK BERDIRI ================= --}}
        @include('partials.frontend.mission')

        @include('partials.frontend.frame-seam', ['from' => $seamMission, 'to' => $seamProduk])

        {{-- ================= SEMUA PRODUK ================= --}}
        @include('partials.frontend.products')

        @include('partials.frontend.frame-seam', ['from' => $seamProduk, 'to' => $seamKategori])

        {{-- ================= PRODUK BERDASARKAN KATEGORI ================= --}}
        @include('partials.frontend.categories')

        @include('partials.frontend.frame-seam', ['from' => $seamKategori, 'to' => $seamTestimoni])

        {{-- ================= ULASAN PELANGGAN KAMI ================= --}}
        @include('partials.frontend.testimonials')

        @include('partials.frontend.frame-seam', ['from' => $seamTestimoni, 'to' => $seamExpertise])

        {{-- ================= KEUNGGULAN KAMI ================= --}}
        @include('partials.frontend.expertise')

        @include('partials.frontend.frame-seam', ['from' => $seamExpertise, 'to' => $seamAlurBooking])

        {{-- ================= ALUR BOOKING ================= --}}
        @include('partials.frontend.alur-booking')

        @include('partials.frontend.frame-seam', ['from' => $seamAlurBooking, 'to' => $seamFaq])

        {{-- ================= FAQ ================= --}}
        @include('partials.frontend.faq')

        @include('partials.frontend.frame-seam', ['from' => $seamFaq, 'to' => $seamLokasi])

        {{-- ================= LOKASI / ALAMAT ================= --}}
        @include('partials.frontend.lokasi')

        {{-- ================= FOOTER ================= --}}
        @include('partials.frontend.footer')
    </body>
</html>