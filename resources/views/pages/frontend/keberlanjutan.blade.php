<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sustainability — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    {{--
        Konten halaman ini SENGAJA generik (kualitas bahan & prinsip
        custom-made), BUKAN klaim keberlanjutan spesifik (mis. "kayu
        bersertifikat", "karbon netral") karena toko belum punya data
        atau bukti untuk klaim seperti itu. Lihat jawaban: "Belum ada
        yang spesifik, buat halaman generik soal kualitas/custom
        furniture". Kalau nanti ada praktik nyata yang mau ditonjolkan,
        halaman ini yang perlu diperbarui duluan.
    --}}
    @php
        $sustainabilityPoints = [
            [
                'icon' => 'fa-tree',
                'title' => 'Bahan Baku Pilihan',
                'text' => 'Setiap kayu diseleksi manual sebelum masuk proses produksi, supaya hasil akhirnya kuat dan awet dipakai bertahun-tahun.',
            ],
            [
                'icon' => 'fa-ruler-combined',
                'title' => 'Dibuat Sesuai Pesanan',
                'text' => 'Produk custom dikerjakan sesuai ukuran & kebutuhan pemesan — mengurangi kelebihan stok dan sisa bahan yang terbuang percuma.',
            ],
            [
                'icon' => 'fa-hammer',
                'title' => 'Dikerjakan Tangan, Bukan Massal',
                'text' => 'Diproses langsung oleh tukang kayu berpengalaman, bukan produksi pabrik — sehingga tiap detail bisa diperiksa satu per satu.',
            ],
            [
                'icon' => 'fa-couch',
                'title' => 'Furnitur untuk Jangka Panjang',
                'text' => 'Kami merancang furnitur yang tahan lama secara struktur, bukan sekadar tampilan — supaya lebih jarang perlu diganti.',
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
                Sustainability
                <span class="h-px w-8 bg-admin-accent"></span>
            </div>
            <h1 class="mt-4 font-display text-4xl font-semibold leading-tight text-[#1A1A1A] sm:text-5xl">
                Kualitas yang Dibuat untuk Bertahan
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-[#6B6E76] sm:text-base">
                Bagi kami, cara paling nyata untuk mengurangi limbah adalah membuat furnitur
                yang benar-benar awet dan tidak perlu cepat diganti — dikerjakan sesuai
                pesanan, dari bahan yang dipilih dengan hati-hati.
            </p>
        </div>
    </section>

    {{-- =====================================================
         POIN-POIN
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto max-w-5xl px-6 py-14 sm:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                @foreach ($sustainabilityPoints as $point)
                    <div class="flex items-start gap-4 rounded-2xl border border-[#1A1A1A]/10 p-6">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                            <i class="fa-solid {{ $point['icon'] }}"></i>
                        </span>
                        <div>
                            <h3 class="font-display text-lg font-semibold text-[#1A1A1A]">
                                {{ $point['title'] }}
                            </h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-[#6B6E76]">
                                {{ $point['text'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mx-auto mt-10 max-w-2xl text-center text-xs leading-relaxed text-[#6B6E76]">
                Catatan: halaman ini menjelaskan prinsip kerja kami secara umum. Kami akan
                memperbarui halaman ini kalau ke depannya ada praktik atau sertifikasi
                keberlanjutan yang lebih spesifik untuk ditampilkan.
            </p>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
