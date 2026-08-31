<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $legalSetting = \App\Models\Setting::current();
        $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse('2026-08-01')->translatedFormat('d F Y');
        $legalContactEmail = $legalSetting->email ?: 'info@karyaideedi.com';
        $legalWaLink = $legalSetting->whatsappDigits() ? 'https://wa.me/'.$legalSetting->whatsappDigits() : null;

        $termsSections = [
            [
                'id' => 'penerimaan-ketentuan',
                'title' => '1. Penerimaan Ketentuan',
                'icon' => 'fa-file-signature',
                'body' => [
                    'Dengan mengakses dan menggunakan situs '.$legalSetting->site_name.', melihat katalog produk, atau mengirimkan formulir booking custom furniture, Anda dianggap telah membaca, memahami, dan menyetujui seluruh ketentuan yang tercantum dalam halaman ini.',
                ],
            ],
            [
                'id' => 'pesanan-custom',
                'title' => '2. Proses Pemesanan Custom',
                'icon' => 'fa-ruler-combined',
                'body' => [
                    'Setiap produk pada dasarnya bersifat custom-made (dibuat sesuai pesanan), sehingga:',
                    [
                        'Pengiriman formulir booking bukan konfirmasi final — pesanan baru dianggap sah setelah dikonfirmasi kedua belah pihak melalui WhatsApp.',
                        'Spesifikasi (ukuran, bahan, warna, deskripsi custom) yang Anda kirimkan menjadi acuan utama proses produksi.',
                        'Perubahan spesifikasi setelah produksi dimulai dapat memengaruhi waktu pengerjaan dan biaya tambahan, dan akan didiskusikan terlebih dahulu.',
                    ],
                ],
            ],
            [
                'id' => 'harga-pembayaran',
                'title' => '3. Harga &amp; Pembayaran',
                'icon' => 'fa-tags',
                'body' => [
                    'Harga yang tercantum di katalog merupakan estimasi awal dan dapat berubah menyesuaikan kompleksitas custom, bahan, dan ukuran akhir yang disepakati saat negosiasi via WhatsApp. Skema pembayaran (termasuk uang muka/DP bila berlaku) akan diinformasikan dan disepakati bersama sebelum produksi dimulai — kami tidak memproses pembayaran otomatis melalui situs ini.',
                ],
            ],
            [
                'id' => 'pengiriman-waktu',
                'title' => '4. Waktu Pengerjaan &amp; Pengiriman',
                'icon' => 'fa-truck-fast',
                'body' => [
                    'Estimasi waktu pengerjaan disampaikan saat konfirmasi pesanan dan dapat bervariasi tergantung tingkat kesulitan desain serta antrian produksi yang sedang berjalan. Status terkini dapat dipantau kapan saja melalui halaman Lacak Pesanan menggunakan tautan unik yang diberikan untuk setiap pesanan.',
                ],
            ],
            [
                'id' => 'pembatalan-perubahan',
                'title' => '5. Pembatalan &amp; Perubahan Pesanan',
                'icon' => 'fa-ban',
                'body' => [
                    'Pembatalan sebelum proses produksi dimulai dapat diajukan melalui WhatsApp dan akan diproses sesuai kesepakatan terkait pengembalian uang muka (bila ada). Setelah produksi berjalan, pembatalan menjadi lebih terbatas mengingat bahan dan waktu kerja yang sudah dialokasikan khusus untuk pesanan Anda — kondisi ini akan dijelaskan secara terbuka saat negosiasi.',
                ],
            ],
            [
                'id' => 'garansi-retur',
                'title' => '6. Garansi &amp; Pengembalian',
                'icon' => 'fa-shield-heart',
                'body' => [
                    'Ketentuan lengkap mengenai garansi produk dan kebijakan retur diatur secara khusus pada halaman Profil, bagian Garansi &amp; Pengiriman, agar informasinya selalu konsisten dan mudah ditemukan di satu tempat.',
                    [
                        route('profile.index').'#garansi|Lihat ketentuan Garansi',
                        route('profile.index').'#pengiriman|Lihat ketentuan Pengiriman & Retur',
                    ],
                ],
            ],
            [
                'id' => 'hak-kekayaan-intelektual',
                'title' => '7. Hak Cipta &amp; Konten Situs',
                'icon' => 'fa-copyright',
                'body' => [
                    'Seluruh nama merek, logo, foto produk, dan konten pada situs ini adalah milik '.$legalSetting->site_name.' dan dilindungi hak cipta. Penggunaan, penyalinan, atau reproduksi konten tanpa izin tertulis tidak diperkenankan.',
                ],
            ],
            [
                'id' => 'batasan-tanggung-jawab',
                'title' => '8. Batasan Tanggung Jawab',
                'icon' => 'fa-scale-balanced',
                'body' => [
                    'Kami berupaya menampilkan informasi produk dan harga seakurat mungkin, namun variasi kecil pada warna atau tekstur material alami (kayu) adalah hal wajar dan bukan merupakan cacat produk. Kami tidak bertanggung jawab atas keterlambatan yang disebabkan oleh faktor di luar kendali kami, seperti kendala pihak ekspedisi.',
                ],
            ],
            [
                'id' => 'perubahan-ketentuan',
                'title' => '9. Perubahan Ketentuan',
                'icon' => 'fa-rotate',
                'body' => [
                    'Ketentuan Layanan ini dapat diperbarui sewaktu-waktu untuk menyesuaikan perkembangan layanan kami. Tanggal pembaruan terakhir selalu tercantum pada bagian atas halaman ini.',
                ],
            ],
        ];
    @endphp

    {{-- =====================================================
         HERO
    ====================================================== --}}
    <section class="relative overflow-hidden bg-[#1A1A1A]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-admin-accent/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 bottom-0 h-56 w-56 rounded-full bg-admin-accent/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="mx-auto flex w-fit items-center gap-3 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent-strong">
                <i class="fa-solid fa-file-contract text-[10px]"></i>
                Terms of Service
            </div>
            <h1 class="mt-5 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                Ketentuan Layanan
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/60 sm:text-base">
                Ketentuan berikut mengatur hubungan Anda dengan {{ $legalSetting->site_name }} —
                mulai dari proses pemesanan custom furniture hingga pengiriman ke tangan Anda.
            </p>
            <p class="mt-6 text-xs uppercase tracking-[0.15em] text-white/40">
                Terakhir diperbarui: {{ $legalUpdatedAt }}
            </p>
        </div>
    </section>

    {{-- =====================================================
         KONTEN + TOC
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-6 py-14 sm:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-[260px_1fr]">

                {{-- TOC --}}
                <aside class="hidden lg:block">
                    <div class="sticky top-28 rounded-2xl border border-[#1A1A1A]/10 bg-admin-cream/40 p-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#6B6E76]">
                            Daftar Isi
                        </p>
                        <ul class="mt-4 space-y-3">
                            @foreach ($termsSections as $section)
                                <li>
                                    <a href="#{{ $section['id'] }}" class="group flex items-start gap-2.5 text-sm text-[#6B6E76] transition-colors duration-300 hover:text-admin-accent">
                                        <i class="fa-solid {{ $section['icon'] }} mt-0.5 w-4 text-admin-accent/70 group-hover:text-admin-accent"></i>
                                        <span>{{ $section['title'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>

                {{-- SECTIONS --}}
                <div class="space-y-12">
                    @foreach ($termsSections as $section)
                        <div id="{{ $section['id'] }}" class="scroll-mt-28">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                                    <i class="fa-solid {{ $section['icon'] }}"></i>
                                </span>
                                <h2 class="font-display text-xl font-semibold text-[#1A1A1A] sm:text-2xl">
                                    {!! $section['title'] !!}
                                </h2>
                            </div>

                            <div class="mt-4 space-y-3 border-l-2 border-admin-accent/20 pl-[3.25rem] sm:pl-[3.25rem]">
                                @foreach ($section['body'] as $paragraph)
                                    @if (is_array($paragraph) && str_contains($paragraph[0] ?? '', '|'))
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            @foreach ($paragraph as $link)
                                                @php [$linkHref, $linkLabel] = explode('|', $link); @endphp
                                                <a href="{{ $linkHref }}" class="inline-flex items-center gap-2 rounded-full border border-admin-accent/30 px-4 py-2 text-xs font-semibold text-admin-accent transition-colors duration-300 hover:bg-admin-cream">
                                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                    {{ $linkLabel }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @elseif (is_array($paragraph))
                                        <ul class="ml-1 list-disc space-y-2 pl-4 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                            @foreach ($paragraph as $point)
                                                <li>{{ $point }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                            {!! $paragraph !!}
                                        </p>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- KONTAK --}}
                    <div class="scroll-mt-28 rounded-2xl bg-[#1A1A1A] p-8 text-center sm:p-10">
                        <i class="fa-solid fa-handshake text-2xl text-admin-accent-strong"></i>
                        <h3 class="mt-3 font-display text-xl font-semibold text-white">
                            Butuh Penjelasan Lebih Lanjut?
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-white/60">
                            Tim kami siap membantu menjelaskan ketentuan pemesanan sebelum Anda
                            melanjutkan booking.
                        </p>
                        <div class="mt-5 flex items-center justify-center gap-2 text-sm text-white/70">
                            <i class="fa-solid fa-envelope text-admin-accent-strong"></i> {{ $legalContactEmail }}
                        </div>
                        @if ($legalWaLink)
                            <a href="{{ $legalWaLink }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-admin-accent px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-admin-accent-strong">
                                <i class="fa-brands fa-whatsapp"></i>
                                Hubungi via WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>
