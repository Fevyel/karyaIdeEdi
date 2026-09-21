{{--
    ==========================================================
    FAQ (PERTANYAAN YANG SERING DITANYAKAN) — Homepage
    ==========================================================
    Sengaja pakai <details>/<summary> bawaan HTML (bukan Alpine
    x-data) supaya accordion ini PASTI jalan buat semua pengunjung
    — Alpine di project ini cuma ke-load kalau ada komponen
    Livewire yang benar-benar dirender di request itu (mis. cuma
    saat admin login), jadi kalau dipaksa pakai x-data di halaman
    publik untuk guest, klik-nya bisa diam saja tanpa toggle.

    Isi pertanyaan disesuaikan sama alur toko ini sendiri (booking
    by WhatsApp, garansi retur 30 hari, tracking pesanan) — bukan
    FAQ generik e-commerce yang gak nyambung sama cara toko ini
    beroperasi.

    Pemakaian:
        @include('partials.frontend.faq')
    ==========================================================
--}}

@php
    $faqSetting = $siteSetting ?? \App\Models\Setting::current();
    $faqWaNumber = $faqSetting->whatsappDigits();

    // Konten diedit admin lewat Edit Web > FAQ (lihat App\Models\HomeSection
    // & saveFaq() di pages/admin/edit-web.blade.php). Default di bawah ini
    // dipakai HANYA kalau admin belum pernah menyimpan section ini sama
    // sekali -- isinya sengaja sama persis dengan konten lama supaya
    // tampilan tidak berubah sebelum admin mengubahnya sendiri.
    $faqItems = \App\Models\HomeSection::dataFor('faq', [
        'items' => [
            [
                'q' => 'Bagaimana cara memesan produk di '.$faqSetting->site_name.'?',
                'a' => 'Pilih produk yang kamu suka di halaman Produk, lalu hubungi admin lewat tombol WhatsApp untuk konsultasi ukuran, bahan, dan harga. Pesanan baru tercatat resmi setelah kesepakatan dikonfirmasi lewat WhatsApp — lihat halaman Booking untuk alur lengkapnya.',
            ],
            [
                'q' => 'Apakah bisa pesan furniture custom sesuai ukuran ruangan saya?',
                'a' => 'Bisa. Sampaikan ukuran, bahan, dan kebutuhan khususmu ke admin lewat WhatsApp saat proses booking — kami sesuaikan sebelum pesanan diproses.',
            ],
            [
                'q' => 'Berapa lama proses pengerjaan dan pengirimannya?',
                'a' => 'Estimasi waktu pengerjaan dan biaya ongkir menyesuaikan lokasi serta ukuran pesanan, dan akan diinfokan admin sebelum pesanan difinalkan lewat WhatsApp.',
            ],
            [
                'q' => 'Bagaimana kalau produk yang sampai cacat atau tidak sesuai?',
                'a' => 'Kami kasih garansi retur 30 hari sejak barang diterima. Kerusakan akibat cacat produksi kami tanggung — cukup kirim foto/video kondisi barang lewat WhatsApp untuk klaim.',
            ],
            [
                'q' => 'Bagaimana cara memantau status pesanan saya?',
                'a' => 'Setelah pesanan dikonfirmasi admin, kamu akan mendapat link tracking pribadi yang bisa dibuka kapan saja di halaman Lacak Pesanan.',
            ],
            [
                'q' => 'Apakah saya bisa lihat detail dan foto produk sebelum booking?',
                'a' => 'Tentu — buka halaman Produk untuk melihat foto, kategori, dan harga tiap produk sebelum menghubungi admin untuk melanjutkan pemesanan.',
            ],
        ],
    ])['items'];
@endphp

<section class="bg-white">
    <div class="mx-auto max-w-3xl px-6 py-16 sm:px-8 lg:py-24">
        <div class="mx-auto max-w-xl text-center">
            <div class="mx-auto flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-admin-accent">
                <span class="h-px w-8 bg-admin-accent"></span>
                FAQ
                <span class="h-px w-8 bg-admin-accent"></span>
            </div>
            <h2 class="mt-5 font-display text-3xl leading-tight text-[#4B3A26] sm:text-4xl">
                Pertanyaan yang sering ditanyakan.
            </h2>
        </div>

        <div class="mt-10 space-y-3">
            @foreach ($faqItems as $faqItem)
                <details class="group rounded-xl border border-admin-border bg-white open:bg-admin-cream/30">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-semibold text-[#3D2B1F] sm:text-base">
                        {{ $faqItem['q'] }}
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-admin-cream text-admin-accent transition-transform duration-300 group-open:rotate-45">
                            <i class="fa-solid fa-plus text-xs"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-4 text-sm leading-relaxed text-admin-ink-soft sm:text-base">
                        {{ $faqItem['a'] }}
                    </div>
                </details>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-admin-ink-soft">
            Masih ada pertanyaan lain?
            <a
                href="{{ $faqWaNumber ? 'https://wa.me/'.$faqWaNumber : route('booking.index') }}"
                @if ($faqWaNumber) target="_blank" rel="noopener" @endif
                class="font-medium text-admin-accent underline underline-offset-2"
            >
                Hubungi admin lewat WhatsApp
            </a>
        </p>
    </div>
</section>