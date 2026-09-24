<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cookies &mdash; {{ \App\Models\Setting::current()->site_name }}</title>
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

        $cookiesHero = \App\Models\HomeSection::dataFor('cookies-hero', [
            'eyebrow' => 'Cookies',
            'heading' => 'Kebijakan Cookie',
            'description' => 'Kami menjaga situs ini seringan dan sesederhana mungkin. Berikut rincian lengkap dan jujur soal data apa saja yang tersimpan di perangkat Anda saat berkunjung ke '.$legalSetting->site_name.'.',
            'updated_date' => '2026-08-01',
        ]);

        try {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse($cookiesHero['updated_date'] ?? '2026-08-01')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            $legalUpdatedAt = \Illuminate\Support\Facades\Date::parse('2026-08-01')->translatedFormat('d F Y');
        }

        $cookiesSummary = \App\Models\HomeSection::dataFor('cookies-summary', [
            'label' => 'Singkatnya:',
            'text' => 'kami tidak memasang cookie pelacak iklan atau analitik pihak ketiga. Yang kami simpan di perangkat Anda hanya hal-hal yang membuat pengalaman belanja lebih nyaman - seperti daftar favorit dan keranjang Anda sendiri.',
        ]);

        $cookieCategoryDefaults = [
            ['icon' => 'fa-gear', 'status' => 'always', 'name' => 'Esensial', 'desc' => 'Diperlukan agar situs berfungsi dengan baik - misalnya menjaga sesi login admin tetap aktif dan keamanan formulir. Tidak dapat dinonaktifkan karena situs tidak akan berjalan normal tanpanya.'],
            ['icon' => 'fa-heart', 'status' => 'local', 'name' => 'Preferensi Lokal', 'desc' => 'Favorit dan Keranjang belanja Anda disimpan di localStorage browser perangkat ini (bukan cookie, dan bukan di server kami) agar tetap tersimpan saat Anda kembali berkunjung - tanpa perlu membuat akun.'],
            ['icon' => 'fa-link', 'status' => 'local', 'name' => 'Tautan Lacak Pesanan', 'desc' => 'Setelah pesanan dibuat, token pelacakan pesanan Anda didaftarkan pada perangkat ini supaya halaman Lacak Pesanan bisa langsung menampilkan riwayat pesanan Anda tanpa harus login.'],
            ['icon' => 'fa-chart-line', 'status' => 'none', 'name' => 'Analitik Pihak Ketiga', 'desc' => 'Kami tidak memasang Google Analytics, Meta Pixel, atau alat pelacak iklan pihak ketiga mana pun di situs ini.'],
            ['icon' => 'fa-bullhorn', 'status' => 'none', 'name' => 'Iklan & Retargeting', 'desc' => 'Kami tidak membagikan data kunjungan Anda kepada jaringan iklan, dan tidak menampilkan iklan pihak ketiga di situs ini.'],
        ];

        $cookiesCategories = \App\Models\HomeSection::dataFor('cookies-categories', [
            'heading' => 'Rincian per Kategori',
            'description' => 'Kategori esensial & preferensi lokal tidak dapat kami hindari karena menjadi dasar fungsi belanja di situs ini. Sisanya, secara sadar tidak kami gunakan.',
            'items' => $cookieCategoryDefaults,
            'status_labels' => [
                'always' => 'Selalu Aktif',
                'local' => 'Tersimpan di Perangkat Anda',
                'none' => 'Tidak Digunakan',
            ],
        ]);

        $cookieTypes = array_values(is_array($cookiesCategories['items'] ?? null) ? $cookiesCategories['items'] : $cookieCategoryDefaults);
        foreach ($cookieTypes as $index => $item) {
            $cookieTypes[$index]['icon'] = $cookieCategoryDefaults[$index]['icon'] ?? 'fa-cookie-bite';
            $cookieTypes[$index]['status'] = $cookieCategoryDefaults[$index]['status'] ?? 'none';
        }

        $statusLabels = is_array($cookiesCategories['status_labels'] ?? null)
            ? $cookiesCategories['status_labels']
            : ['always' => 'Selalu Aktif', 'local' => 'Tersimpan di Perangkat Anda', 'none' => 'Tidak Digunakan'];

        $cookieStatusMap = [
            'always' => ['label' => $statusLabels['always'] ?? 'Selalu Aktif', 'color' => 'bg-admin-accent text-white'],
            'local' => ['label' => $statusLabels['local'] ?? 'Tersimpan di Perangkat Anda', 'color' => 'bg-admin-cream text-admin-accent'],
            'none' => ['label' => $statusLabels['none'] ?? 'Tidak Digunakan', 'color' => 'bg-[#F2F2F2] text-[#8A8A8A]'],
        ];

        $cookiesBrowser = \App\Models\HomeSection::dataFor('cookies-browser', [
            'heading' => 'Mengatur Penyimpanan di Browser Anda',
            'text' => 'Karena favorit dan keranjang tersimpan secara lokal di browser Anda, Anda bisa menghapusnya kapan saja melalui pengaturan "Clear browsing data" / "Hapus data situs" pada browser yang Anda gunakan. Menghapus data ini hanya akan mengosongkan favorit & keranjang di perangkat tersebut - tidak memengaruhi pesanan yang sudah dikonfirmasi.',
        ]);

        $cookiesContact = \App\Models\HomeSection::dataFor('cookies-contact', [
            'heading' => 'Masih Ada yang Ingin Ditanyakan?',
            'description' => 'Kami senang menjelaskan lebih lanjut soal bagaimana situs ini bekerja.',
            'button_label' => 'Hubungi via WhatsApp',
        ]);
    @endphp

    <section class="relative overflow-hidden bg-[#1A1A1A]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-admin-accent/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 bottom-0 h-56 w-56 rounded-full bg-admin-accent/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl px-6 py-16 text-center sm:px-8 lg:py-20">
            <div class="mx-auto flex w-fit items-center gap-3 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent-strong">
                <i class="fa-solid fa-cookie-bite text-[10px]"></i>
                {{ $cookiesHero['eyebrow'] }}
            </div>
            <h1 class="mt-5 font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                {{ $cookiesHero['heading'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/60 sm:text-base">
                {{ $cookiesHero['description'] }}
            </p>
            <p class="mt-6 text-xs uppercase tracking-[0.15em] text-white/40">
                Terakhir diperbarui: {{ $legalUpdatedAt }}
            </p>
        </div>
    </section>

    <section class="bg-admin-cream/40">
        <div class="mx-auto max-w-5xl px-6 py-10 sm:px-8">
            <div class="flex flex-col items-start gap-4 rounded-2xl border border-admin-accent/20 bg-white p-6 sm:flex-row sm:items-center sm:p-7">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                </span>
                <p class="text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                    <span class="font-semibold text-[#1A1A1A]">{{ $cookiesSummary['label'] }}</span>
                    {{ $cookiesSummary['text'] }}
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-5xl px-6 py-14 sm:px-8 lg:py-20">
            <h2 class="font-display text-2xl font-semibold text-[#1A1A1A] sm:text-3xl">
                {{ $cookiesCategories['heading'] }}
            </h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[#6B6E76]">
                {{ $cookiesCategories['description'] }}
            </p>

            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
                @foreach ($cookieTypes as $cookie)
                    @php $statusInfo = $cookieStatusMap[$cookie['status']] ?? $cookieStatusMap['none']; @endphp
                    <div class="flex flex-col rounded-2xl border border-[#1A1A1A]/10 p-6 transition-shadow duration-300 hover:shadow-[0_8px_24px_-8px_rgba(34,26,20,0.15)]">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent">
                                <i class="fa-solid {{ $cookie['icon'] }}"></i>
                            </span>
                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $statusInfo['color'] }}">
                                {{ $statusInfo['label'] }}
                            </span>
                        </div>
                        <h3 class="mt-4 font-display text-lg font-semibold text-[#1A1A1A]">
                            {{ $cookie['name'] }}
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#6B6E76]">
                            {{ $cookie['desc'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 rounded-2xl border border-[#1A1A1A]/10 bg-admin-cream/30 p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-admin-accent">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <h3 class="font-display text-lg font-semibold text-[#1A1A1A]">
                        {{ $cookiesBrowser['heading'] }}
                    </h3>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-[#4A423B] sm:text-[15px]">
                    {{ $cookiesBrowser['text'] }}
                </p>
            </div>

            <div class="mt-8 rounded-2xl bg-[#1A1A1A] p-8 text-center sm:p-10">
                <i class="fa-solid fa-comments text-2xl text-admin-accent-strong"></i>
                <h3 class="mt-3 font-display text-xl font-semibold text-white">
                    {{ $cookiesContact['heading'] }}
                </h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-white/60">
                    {{ $cookiesContact['description'] }}
                </p>

                <div class="mt-5 flex items-center justify-center gap-2 text-sm text-white/70">
                    @if ($legalGmailLink)
                        <a href="{{ $legalGmailLink }}" target="_blank" rel="noopener" class="flex items-center gap-2 transition-colors duration-300 hover:text-white">
                            <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                            {{ $legalContactEmail }}
                        </a>
                    @else
                        <i class="fa-solid fa-envelope text-admin-accent-strong"></i>
                        {{ $legalContactEmail }}
                    @endif
                </div>

                @if ($legalWaLink)
                    <a href="{{ $legalWaLink }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full bg-admin-accent px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-admin-accent-strong">
                        <i class="fa-brands fa-whatsapp"></i>
                        {{ $cookiesContact['button_label'] }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>