<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Our Craftsmen — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $craftsmenWaNumber = \App\Models\Setting::current()->whatsappDigits();

        /*
         * DUMMY — belum ada data & foto tukang/pengrajin asli dari pemilik
         * toko (lihat jawaban: "ambil (dummy) dari internet terlebih
         * dahulu"). Foto pakai placeholder generik dari Picsum Photos
         * (bukan foto pengrajin sungguhan), nama & pengalaman juga contoh.
         * GANTI seluruh isi array ini begitu ada data & foto asli.
         */
        $craftsmen = [
            [
                'name' => 'Pak Wawan Setiawan',
                'role' => 'Kepala Tukang Kayu',
                'experience' => '18 tahun pengalaman',
                'bio' => 'Memimpin proses konstruksi utama, dari pemilihan kayu hingga perakitan akhir setiap produk custom.',
                'photo' => 'https://picsum.photos/seed/karyaideedi-craftsman-1/480/480',
            ],
            [
                'name' => 'Pak Dedi Kurniawan',
                'role' => 'Spesialis Finishing & Cat',
                'experience' => '12 tahun pengalaman',
                'bio' => 'Bertanggung jawab pada tahap akhir: pengamplasan, pewarnaan, dan lapisan pelindung agar hasil rapi dan tahan lama.',
                'photo' => 'https://picsum.photos/seed/karyaideedi-craftsman-2/480/480',
            ],
            [
                'name' => 'Pak Agus Prabowo',
                'role' => 'Tukang Ukir',
                'experience' => '15 tahun pengalaman',
                'bio' => 'Mengerjakan detail ukiran dan sentuhan dekoratif pada produk-produk custom yang membutuhkan motif khusus.',
                'photo' => 'https://picsum.photos/seed/karyaideedi-craftsman-3/480/480',
            ],
            [
                'name' => 'Bu Sri Handayani',
                'role' => 'Quality Control',
                'experience' => '9 tahun pengalaman',
                'bio' => 'Memeriksa setiap produk sebelum dikirim, memastikan ukuran, kekuatan sambungan, dan finishing sesuai standar toko.',
                'photo' => 'https://picsum.photos/seed/karyaideedi-craftsman-4/480/480',
            ],
        ];
    @endphp

    {{-- =====================================================
         HERO
    ====================================================== --}}
    <section class="relative overflow-hidden bg-[#F9F7F2]">
        <div class="mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                <span class="h-px w-8 bg-admin-accent"></span>
                Our Craftsmen
                <span class="h-px w-8 bg-admin-accent"></span>
            </div>
            <h1 class="mt-4 font-display text-4xl font-semibold leading-tight text-[#1A1A1A] sm:text-5xl">
                Tangan-Tangan di Balik Setiap Produk
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-[#6B6E76] sm:text-base">
                Setiap produk Karya Ide Edi dibuat langsung oleh tukang kayu berpengalaman —
                bukan produksi massal. Berikut sebagian dari tim yang mengerjakan pesanan Anda
                dari awal sampai jadi.
            </p>
        </div>
    </section>

    {{-- =====================================================
         GRID PENGRAJIN
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-6 py-14 sm:px-8 lg:py-20">
            <p class="mx-auto mb-10 max-w-2xl text-center text-xs text-[#6B6E76]">
                * Foto & profil di bawah ini masih data contoh (dummy) sambil menunggu foto
                dan data asli dari pemilik toko.
            </p>

            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($craftsmen as $person)
                    <div class="group text-center">
                        <div class="mx-auto h-36 w-36 overflow-hidden rounded-full border border-[#1A1A1A]/10 bg-[#F1EEE7] sm:h-40 sm:w-40">
                            <img
                                src="{{ $person['photo'] }}"
                                alt="{{ $person['name'] }}"
                                loading="lazy"
                                class="h-full w-full object-cover grayscale transition duration-500 group-hover:grayscale-0"
                            >
                        </div>
                        <h3 class="mt-4 font-display text-lg font-semibold text-[#1A1A1A]">
                            {{ $person['name'] }}
                        </h3>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-admin-accent">
                            {{ $person['role'] }}
                        </p>
                        <p class="mt-1 text-xs text-[#6B6E76]">{{ $person['experience'] }}</p>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-relaxed text-[#6B6E76]">
                            {{ $person['bio'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =====================================================
         CTA
    ====================================================== --}}
    <section class="bg-[#1A1A1A]">
        <div class="mx-auto flex max-w-4xl flex-col items-center gap-4 px-6 py-14 text-center sm:px-8">
            <h2 class="font-display text-2xl font-semibold text-white sm:text-3xl">
                Punya ide furnitur custom?
            </h2>
            <p class="max-w-xl text-sm leading-relaxed text-white/60">
                Ceritakan kebutuhan Anda, tim kami siap membantu mewujudkannya.
            </p>
            <a
                href="{{ $craftsmenWaNumber ? 'https://wa.me/'.$craftsmenWaNumber : route('booking.index') }}"
                @if ($craftsmenWaNumber) target="_blank" rel="noopener" @endif
                class="mt-2 inline-flex items-center gap-2 rounded-lg bg-admin-accent px-6 py-3 text-sm font-medium text-white transition-colors duration-300 hover:bg-admin-accent-strong"
            >
                <i class="fa-brands fa-whatsapp"></i>
                Hubungi Kami
            </a>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
