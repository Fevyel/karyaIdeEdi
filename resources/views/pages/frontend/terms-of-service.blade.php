<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $legalSetting = \App\Models\Setting::current();
        $legalContactEmail = $legalSetting->email ?: 'info@karyaideedi.com';
        $legalGmailLink = $legalSetting->gmailComposeUrl();
        $legalWaLink = $legalSetting->whatsappDigits() ? 'https://wa.me/'.$legalSetting->whatsappDigits() : null;

        $termsHero = \App\Models\HomeSection::dataFor('terms-hero', [
            'eyebrow' => 'Terms of Service',
            'heading' => 'Ketentuan Layanan',
            'description' => 'Ketentuan berikut mengatur hubungan Anda dengan '.$legalSetting->site_name.' - mulai dari proses pemesanan custom furniture hingga pengiriman ke tangan Anda.',
            'updated_date' => '2026-08-01',
        ]);

        try {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse($termsHero['updated_date'] ?? '2026-08-01')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse('2026-08-01')->translatedFormat('d F Y');
        }

        $termsDefaults = [
            'acceptance' => [
                'id' => 'penerimaan-ketentuan',
                'icon' => 'fa-file-signature',
                'title' => '1. Penerimaan Ketentuan',
                'text' => 'Dengan mengakses dan menggunakan situs '.$legalSetting->site_name.', melihat katalog produk, atau mengirimkan formulir booking custom furniture, Anda dianggap telah membaca, memahami, dan menyetujui seluruh ketentuan yang tercantum dalam halaman ini.',
            ],
            'custom-order' => [
                'id' => 'pesanan-custom',
                'icon' => 'fa-ruler-combined',
                'title' => '2. Proses Pemesanan Custom',
                'text' => 'Setiap produk pada dasarnya bersifat custom-made (dibuat sesuai pesanan), sehingga:',
                'bullets' => [
                    'Pengiriman formulir booking bukan konfirmasi final - pesanan baru dianggap sah setelah dikonfirmasi kedua belah pihak melalui WhatsApp.',
                    'Spesifikasi (ukuran, bahan, warna, deskripsi custom) yang Anda kirimkan menjadi acuan utama proses produksi.',
                    'Perubahan spesifikasi setelah produksi dimulai dapat memengaruhi waktu pengerjaan dan biaya tambahan, dan akan didiskusikan terlebih dahulu.',
                ],
            ],
            'payment' => [
                'id' => 'harga-pembayaran',
                'icon' => 'fa-tags',
                'title' => '3. Harga & Pembayaran',
                'text' => 'Harga yang tercantum di katalog merupakan estimasi awal dan dapat berubah menyesuaikan kompleksitas custom, bahan, dan ukuran akhir yang disepakati saat negosiasi via WhatsApp. Skema pembayaran (termasuk uang muka/DP bila berlaku) akan diinformasikan dan disepakati bersama sebelum produksi dimulai - kami tidak memproses pembayaran otomatis melalui situs ini.',
            ],
            'delivery' => [
                'id' => 'pengiriman-waktu',
                'icon' => 'fa-truck-fast',
                'title' => '4. Waktu Pengerjaan & Pengiriman',
                'text' => 'Estimasi waktu pengerjaan disampaikan saat konfirmasi pesanan dan dapat bervariasi tergantung tingkat kesulitan desain serta antrian produksi yang sedang berjalan. Status terkini dapat dipantau kapan saja melalui halaman Lacak Pesanan menggunakan tautan unik yang diberikan untuk setiap pesanan.',
            ],
            'cancellation' => [
                'id' => 'pembatalan-perubahan',
                'icon' => 'fa-ban',
                'title' => '5. Pembatalan & Perubahan Pesanan',
                'text' => 'Pembatalan sebelum proses produksi dimulai dapat diajukan melalui WhatsApp dan akan diproses sesuai kesepakatan terkait pengembalian uang muka (bila ada). Setelah produksi berjalan, pembatalan menjadi lebih terbatas mengingat bahan dan waktu kerja yang sudah dialokasikan khusus untuk pesanan Anda - kondisi ini akan dijelaskan secara terbuka saat negosiasi.',
            ],
            'warranty' => [
                'id' => 'garansi-retur',
                'icon' => 'fa-shield-heart',
                'title' => '6. Garansi & Pengembalian',
                'text' => 'Ketentuan lengkap mengenai garansi produk dan kebijakan retur diatur secara khusus pada halaman Profil, bagian Garansi & Pengiriman, agar informasinya selalu konsisten dan mudah ditemukan di satu tempat.',
                'link_labels' => [
                    'Lihat ketentuan Garansi',
                    'Lihat ketentuan Pengiriman & Retur',
                ],
            ],
            'copyright' => [
                'id' => 'hak-kekayaan-intelektual',
                'icon' => 'fa-copyright',
                'title' => '7. Hak Cipta & Konten Situs',
                'text' => 'Seluruh nama merek, logo, foto produk, dan konten pada situs ini adalah milik '.$legalSetting->site_name.' dan dilindungi hak cipta. Penggunaan, penyalinan, atau reproduksi konten tanpa izin tertulis tidak diperkenankan.',
            ],
            'liability' => [
                'id' => 'batasan-tanggung-jawab',
                'icon' => 'fa-scale-balanced',
                'title' => '8. Batasan Tanggung Jawab',
                'text' => 'Kami berupaya menampilkan informasi produk dan harga seakurat mungkin, namun variasi kecil pada warna atau tekstur material alami (kayu) adalah hal wajar dan bukan merupakan cacat produk. Kami tidak bertanggung jawab atas keterlambatan yang disebabkan oleh faktor di luar kendali kami, seperti kendala pihak ekspedisi.',
            ],
            'changes' => [
                'id' => 'perubahan-ketentuan',
                'icon' => 'fa-rotate',
                'title' => '9. Perubahan Ketentuan',
                'text' => 'Ketentuan Layanan ini dapat diperbarui sewaktu-waktu untuk menyesuaikan perkembangan layanan kami. Tanggal pembaruan terakhir selalu tercantum pada bagian atas halaman ini.',
            ],
        ];

        $termsSections = [];
        foreach ($termsDefaults as $key => $default) {
            $dataDefaults = [
                'title' => $default['title'],
                'text' => $default['text'],
            ];

            if (isset($default['bullets'])) {
                $dataDefaults['bullets'] = $default['bullets'];
            }

            if (isset($default['link_labels'])) {
                $dataDefaults['link_labels'] = $default['link_labels'];
            }

            $saved = \App\Models\HomeSection::dataFor('terms-'.$key, $dataDefaults);

            $section = [
                'id' => $default['id'],
                'icon' => $default['icon'],
                'title' => (string) ($saved['title'] ?? $default['title']),
                'text' => (string) ($saved['text'] ?? $default['text']),
            ];

            if (isset($default['bullets'])) {
                $section['bullets'] = array_values(is_array($saved['bullets'] ?? null) ? $saved['bullets'] : $default['bullets']);
            }

            if (isset($default['link_labels'])) {
                $labels = array_values(is_array($saved['link_labels'] ?? null) ? $saved['link_labels'] : $default['link_labels']);
                $section['links'] = [
                    ['href' => route('profile.index').'#garansi', 'label' => $labels[0] ?? $default['link_labels'][0]],
                    ['href' => route('profile.index').'#pengiriman', 'label' => $labels[1] ?? $default['link_labels'][1]],
                ];
            }

            $termsSections[] = $section;
        }

        $termsContact = \App\Models\HomeSection::dataFor('terms-contact', [
            'heading' => 'Butuh Penjelasan Lebih Lanjut?',
            'description' => 'Tim kami siap membantu menjelaskan ketentuan pemesanan sebelum Anda melanjutkan booking.',
            'button_label' => 'Hubungi via WhatsApp',
        ]);
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
                {{ $termsHero['eyebrow'] }}
            </div>
            <h1 class="mt-5 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                {{ $termsHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/60 sm:text-base">
                {{ $termsHero['description'] }}
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
                                    {{ $section['title'] }}
                                </h2>
                            </div>

                            <div class="mt-4 space-y-3 border-l-2 border-admin-accent/20 pl-[3.25rem] sm:pl-[3.25rem]">
                                <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                    {{ $section['text'] }}
                                </p>

                                @if (! empty($section['bullets']))
                                    <ul class="ml-1 list-disc space-y-2 pl-4 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        @foreach ($section['bullets'] as $point)
                                            <li>{{ $point }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (! empty($section['links']))
                                    <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                        @foreach ($section['links'] as $link)
                                            <a href="{{ $link['href'] }}" class="inline-flex items-center gap-2 rounded-full border border-admin-accent/30 px-4 py-2 text-xs font-semibold text-admin-accent transition-colors duration-300 hover:bg-admin-cream">
                                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                {{ $link['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- KONTAK --}}
                    <div class="scroll-mt-28 rounded-2xl bg-[#1A1A1A] p-8 text-center sm:p-10">
                        <i class="fa-solid fa-handshake text-2xl text-admin-accent-strong"></i>
                        <h3 class="mt-3 font-display text-xl font-semibold text-white">
                            {{ $termsContact['heading'] }}
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-white/60">
                            {{ $termsContact['description'] }}
                        </p>
                        <div class="mt-5 flex items-center justify-center gap-2 text-sm text-white/70">
                            @if ($legalGmailLink)
                                <a href="{{ $legalGmailLink }}" target="_blank" rel="noopener" class="flex items-center gap-2 transition-colors duration-300 hover:text-white"><i class="fa-solid fa-envelope text-admin-accent-strong"></i> {{ $legalContactEmail }}</a>
                            @else
                                <i class="fa-solid fa-envelope text-admin-accent-strong"></i> {{ $legalContactEmail }}
                            @endif
                        </div>
                        @if ($legalWaLink)
                            <a href="{{ $legalWaLink }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-admin-accent px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-admin-accent-strong">
                                <i class="fa-brands fa-whatsapp"></i>
                                {{ $termsContact['button_label'] }}
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
