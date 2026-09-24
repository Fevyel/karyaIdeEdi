<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-white font-sans text-[#2A211B] antialiased">
    @include('partials.frontend.navbar')

    @php
        $legalSetting = \App\Models\Setting::current();
        $legalContactEmail = $legalSetting->email ?: 'info@karyaideedi.com';
        $legalGmailLink = $legalSetting->gmailComposeUrl();
        $legalContactAddress = $legalSetting->alamat ?: 'Alamat toko akan diperbarui melalui halaman Pengaturan Admin.';
        $legalWaLink = $legalSetting->whatsappDigits() ? 'https://wa.me/'.$legalSetting->whatsappDigits() : null;

        $privacyHero = \App\Models\HomeSection::dataFor('privacy-hero', [
            'eyebrow' => 'Privacy Policy',
            'heading' => 'Kebijakan Privasi',
            'description' => 'Privasi Anda penting bagi kami. Halaman ini menjelaskan secara transparan informasi apa yang kami kumpulkan, mengapa kami membutuhkannya, dan bagaimana kami menjaganya.',
            'updated_date' => '2026-08-01',
        ]);

        try {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse($privacyHero['updated_date'] ?? '2026-08-01')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse('2026-08-01')->translatedFormat('d F Y');
        }

        $privacyDefaults = [
            'information' => [
                'id' => 'informasi-dikumpulkan',
                'icon' => 'fa-database',
                'title' => '1. Informasi yang Kami Kumpulkan',
                'intro' => 'Kami hanya mengumpulkan informasi yang benar-benar Anda berikan sendiri saat berinteraksi dengan kami, antara lain:',
                'bullets' => [
                    'Nama, nomor telepon/WhatsApp, dan alamat pengiriman - saat Anda mengisi formulir booking custom furniture.',
                    'Pesan atau lampiran gambar referensi - saat Anda menghubungi kami melalui WhatsApp.',
                    'Riwayat produk yang dilihat - digunakan hanya untuk menampilkan rekomendasi produk serupa, tersimpan sementara di server.',
                    'Data favorit & keranjang belanja - tersimpan lokal di penyimpanan browser perangkat Anda sendiri, bukan di server kami, dan tidak memerlukan akun/login.',
                ],
                'outro' => '',
            ],
            'usage' => [
                'id' => 'penggunaan-informasi',
                'icon' => 'fa-gears',
                'title' => '2. Bagaimana Kami Menggunakan Informasi Anda',
                'intro' => 'Informasi yang Anda berikan digunakan semata-mata untuk:',
                'bullets' => [
                    'Memproses dan mengonfirmasi pesanan custom furniture Anda.',
                    'Menghubungi Anda kembali terkait status pesanan melalui WhatsApp.',
                    'Menampilkan status pesanan pada halaman Lacak Pesanan menggunakan tautan unik milik Anda.',
                    'Meningkatkan kualitas katalog produk berdasarkan produk yang paling sering dilihat pembeli (secara agregat, tanpa mengidentifikasi individu).',
                ],
                'outro' => 'Kami tidak pernah menjual, menyewakan, atau memperdagangkan data pribadi Anda kepada pihak ketiga mana pun untuk kepentingan pemasaran.',
            ],
            'security' => [
                'id' => 'penyimpanan-keamanan',
                'icon' => 'fa-shield-halved',
                'title' => '3. Penyimpanan & Keamanan Data',
                'intro' => 'Data pesanan disimpan pada sistem basis data yang hanya dapat diakses oleh admin toko melalui panel administrasi yang dilindungi kata sandi. Kami menerapkan langkah keamanan yang wajar untuk mencegah akses, perubahan, atau pengungkapan data tanpa izin.',
                'bullets' => [],
                'outro' => 'Meski begitu, tidak ada metode transmisi data melalui internet yang 100% aman sepenuhnya. Kami senantiasa berupaya menjaga data Anda dengan sebaik mungkin.',
            ],
            'sharing' => [
                'id' => 'berbagi-data',
                'icon' => 'fa-people-arrows',
                'title' => '4. Berbagi Informasi dengan Pihak Ketiga',
                'intro' => 'Kami hanya membagikan informasi yang diperlukan kepada:',
                'bullets' => [
                    'Layanan WhatsApp - sebagai kanal komunikasi utama yang Anda pilih sendiri untuk bernegosiasi dan konfirmasi pesanan.',
                    'Mitra pengiriman/ekspedisi - sebatas nama & alamat, semata-mata untuk keperluan pengantaran barang pesanan Anda.',
                ],
                'outro' => 'Kami tidak membagikan data Anda kepada pengiklan atau pihak ketiga lain di luar kebutuhan operasional di atas.',
            ],
            'rights' => [
                'id' => 'hak-anda',
                'icon' => 'fa-user-shield',
                'title' => '5. Hak Anda atas Data Pribadi',
                'intro' => 'Anda berhak untuk:',
                'bullets' => [
                    'Meminta salinan data pribadi yang kami simpan terkait pesanan Anda.',
                    'Meminta koreksi apabila terdapat data yang keliru.',
                    'Meminta penghapusan data setelah pesanan selesai diproses, selama tidak melanggar kewajiban hukum/pembukuan yang berlaku.',
                ],
                'outro' => 'Untuk menggunakan hak-hak tersebut, silakan hubungi kami melalui kontak pada bagian akhir halaman ini.',
            ],
            'changes' => [
                'id' => 'perubahan-kebijakan',
                'icon' => 'fa-rotate',
                'title' => '6. Perubahan Kebijakan Ini',
                'intro' => 'Kebijakan Privasi ini dapat kami perbarui sewaktu-waktu mengikuti perkembangan layanan. Tanggal pembaruan terakhir selalu tercantum di bagian atas halaman ini. Kami menganjurkan Anda meninjau halaman ini secara berkala.',
                'bullets' => [],
                'outro' => '',
            ],
        ];

        $privacySections = [];
        foreach ($privacyDefaults as $key => $default) {
            $saved = \App\Models\HomeSection::dataFor('privacy-'.$key, [
                'title' => $default['title'],
                'intro' => $default['intro'],
                'bullets' => $default['bullets'],
                'outro' => $default['outro'],
            ]);

            $privacySections[] = [
                'id' => $default['id'],
                'icon' => $default['icon'],
                'title' => (string) ($saved['title'] ?? $default['title']),
                'intro' => (string) ($saved['intro'] ?? $default['intro']),
                'bullets' => array_values(is_array($saved['bullets'] ?? null) ? $saved['bullets'] : $default['bullets']),
                'outro' => (string) ($saved['outro'] ?? $default['outro']),
            ];
        }

        $privacyContact = \App\Models\HomeSection::dataFor('privacy-contact', [
            'heading' => 'Ada Pertanyaan Soal Privasi Anda?',
            'description' => 'Hubungi kami kapan saja - kami siap menjelaskan bagaimana data Anda dikelola.',
            'button_label' => 'Hubungi via WhatsApp',
        ]);
    @endphp

    <section class="relative overflow-hidden bg-[#1A1A1A]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-admin-accent/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 bottom-0 h-56 w-56 rounded-full bg-admin-accent/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="mx-auto flex w-fit items-center gap-3 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent-strong">
                <i class="fa-solid fa-lock text-[10px]"></i>
                {{ $privacyHero['eyebrow'] }}
            </div>
            <h1 class="mt-5 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                {{ $privacyHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/60 sm:text-base">
                {{ $privacyHero['description'] }}
            </p>
            <p class="mt-6 text-xs uppercase tracking-[0.15em] text-white/40">
                Terakhir diperbarui: {{ $legalUpdatedAt }}
            </p>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-6 py-14 sm:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-[260px_1fr]">
                <aside class="hidden lg:block">
                    <div class="sticky top-28 rounded-2xl border border-[#1A1A1A]/10 bg-admin-cream/40 p-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#6B6E76]">
                            Daftar Isi
                        </p>
                        <ul class="mt-4 space-y-3">
                            @foreach ($privacySections as $section)
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

                <div class="space-y-12">
                    @foreach ($privacySections as $section)
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
                                @if ($section['intro'] !== '')
                                    <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        {{ $section['intro'] }}
                                    </p>
                                @endif

                                @if (! empty($section['bullets']))
                                    <ul class="ml-1 list-disc space-y-2 pl-4 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        @foreach ($section['bullets'] as $point)
                                            <li>{{ $point }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($section['outro'] !== '')
                                    <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                                        {{ $section['outro'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="scroll-mt-28 rounded-2xl bg-[#1A1A1A] p-8 text-center sm:p-10">
                        <i class="fa-solid fa-envelope-open-text text-2xl text-admin-accent-strong"></i>
                        <h3 class="mt-3 font-display text-xl font-semibold text-white">
                            {{ $privacyContact['heading'] }}
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-white/60">
                            {{ $privacyContact['description'] }}
                        </p>

                        <div class="mt-5 flex flex-col items-center justify-center gap-3 text-sm text-white/70 sm:flex-row sm:gap-6">
                            @if ($legalGmailLink)
                                <a href="{{ $legalGmailLink }}" target="_blank" rel="noopener" class="flex items-center gap-2 transition-colors duration-300 hover:text-white">
                                    <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                                    {{ $legalContactEmail }}
                                </a>
                            @else
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                                    {{ $legalContactEmail }}
                                </span>
                            @endif

                            <span class="hidden h-4 w-px bg-white/15 sm:inline-block"></span>
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-admin-accent-strong"></i>
                                {{ $legalContactAddress }}
                            </span>
                        </div>

                        @if ($legalWaLink)
                            <a href="{{ $legalWaLink }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-admin-accent px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-admin-accent-strong">
                                <i class="fa-brands fa-whatsapp"></i>
                                {{ $privacyContact['button_label'] }}
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