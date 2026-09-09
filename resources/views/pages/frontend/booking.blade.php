<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

@php
    $siteSetting = \App\Models\Setting::current();
    $waDigits = $siteSetting->whatsapp ? preg_replace('/\D+/', '', (string) $siteSetting->whatsapp) : '';
    if ($waDigits !== '' && str_starts_with($waDigits, '0')) {
        $waDigits = '62'.substr($waDigits, 1);
    } elseif ($waDigits !== '' && str_starts_with($waDigits, '8')) {
        $waDigits = '62'.$waDigits;
    }

    // Rekomendasi produk terlaris — dihitung dari jumlah Transaction per
    // produk (status selain 'cancelled' dianggap pesanan sah), bukan
    // sekadar produk terbaru. Produk yang belum pernah dipesan tetap ikut
    // tampil di urutan bawah (sold_count 0) supaya katalog baru tidak kosong.
    $bookingProducts = \App\Models\Product::query()
        ->where('status', 'aktif')
        ->with('category:id,name,slug')
        ->withCount(['transactions as sold_count' => function ($transactionQuery) {
            $transactionQuery->where('status', '!=', 'cancelled');
        }])
        ->orderByDesc('sold_count')
        ->latest()
        ->take(8)
        ->get();

    // BUG FIX: closure ini dipanggil di kartu produk bawah ($waForProduct($product))
    // tapi hilang tanpa sengaja saat blok @php ditulis ulang pada edit
    // "booking-category-fix" sebelumnya -> ErrorException "Undefined variable
    // $waForProduct" saat halaman /booking dibuka. Dikembalikan persis seperti
    // sebelum regresi (lihat booking.blade.php.bak-before-booking-category-fix).
    $waForProduct = function ($product) use ($waDigits) {
        if ($waDigits === '') {
            return null;
        }

        $message = "Halo Karya Ide Edi, saya tertarik dengan produk: {$product->nama}. Saya ingin bertanya mengenai produk ini dan cara pemesanannya.";

        return 'https://wa.me/'.$waDigits.'?text='.urlencode($message);
    };

@endphp

<body class="min-h-screen bg-[#F7F4EF] font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    {{-- =========================================================
         HERO — bahasa desain (latar terang, dua kolom, tipografi) mengikuti
         hero beranda/profil, TAPI kontennya sengaja dibedakan: kanan pakai
         kartu alur booking (bukan foto produk yang sama seperti di
         Beranda & Profil), kiri pakai trust badges (bukan statistik toko).
         Tujuannya supaya hero Booking tidak kembar dengan 2 halaman lain.
         ========================================================= --}}
    <section class="relative overflow-hidden bg-[#F9F7F2]">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-12 sm:px-8 lg:grid-cols-2 lg:gap-8 lg:px-10 lg:py-16">

            {{-- ============ KIRI: Teks, langkah singkat, CTA ============ --}}
            <div class="animate-fade-in-up">
                <div class="inline-flex items-center gap-2 rounded-full border border-[#3D2B1F]/10 bg-white px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#9B6E3E]">
                    <i class="fa-solid fa-calendar-check"></i>
                    Booking Karya Ide Edi
                </div>

                <h1 class="mt-6 font-display text-3xl leading-[1.15] text-[#3D2B1F] sm:text-4xl lg:text-[2.5rem] xl:text-[2.75rem]">
                    <span class="font-semibold">Pilih produk,</span><br>
                    <span class="font-semibold">konsultasikan dengan admin,</span><br>
                    <span class="font-normal">kami siapkan prosesnya.</span>
                </h1>

                <p class="mt-6 max-w-md text-sm leading-relaxed text-[#6B6E76]">
                    Booking di Karya Ide Edi dilakukan langsung bersama admin melalui WhatsApp.
                    Data pemesan, alamat pengantaran, dan proses pesanan dicatat oleh tim kami,
                    lalu kamu dapat link tracking pribadi untuk memantau statusnya.
                </p>

                {{-- Trust badges — bukan angka statistik seperti hero beranda,
                     dan tidak mengulang 3 langkah yang sudah ditampilkan
                     detail di kartu sebelah kanan. --}}
                <div class="mt-5 flex flex-wrap gap-x-7 gap-y-3 border-t border-[#3D2B1F]/10 pt-4 text-[11px] text-[#8A7C6E]">
                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-[#9B6E3E]"></i> Tanpa checkout publik</span>
                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-shield-halved text-[#9B6E3E]"></i> Detail pesanan privat</span>
                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-link text-[#9B6E3E]"></i> Tracking pribadi</span>
                </div>

                {{-- CTA --}}
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="#pilih-produk" class="group inline-flex items-center gap-2 rounded-lg bg-[#1A1A1A] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-300 hover:bg-black hover:shadow-md">
                        Pilih Produk
                        <i class="fa-solid fa-arrow-down text-xs transition-transform duration-300 group-hover:translate-y-0.5"></i>
                    </a>
                    @if ($waDigits)
                        <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-[#DCDDD7] px-5 py-2.5 text-sm font-medium text-[#3D2B1F] transition-all duration-300 hover:border-admin-accent hover:text-admin-accent">
                            <i class="fa-brands fa-whatsapp"></i>
                            Hubungi Admin
                        </a>
                    @endif
                </div>
            </div>

            {{-- ============ KANAN: Kartu alur booking =============
                 Sengaja BUKAN foto produk (foto hero.png sudah dipakai di
                 hero Beranda & Profil) -- supaya hero halaman ini tidak
                 kembar dengan keduanya. Tetap satu bahasa desain (latar
                 terang, rounded besar, tipografi sama), tapi kontennya
                 tentang proses booking, bukan showcase produk.
            ============================================================ --}}
            <div class="relative flex items-center justify-center">
                <div class="relative w-full max-w-md overflow-hidden rounded-[28px] border border-[#E7DED2] bg-white p-7 shadow-xl shadow-black/5 sm:p-9">
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#F3E6D5]/70 blur-2xl"></div>

                    <div class="relative flex items-center justify-between border-b border-[#EFE7DA] pb-6">
                        <div>
                            <p class="text-[9px] font-semibold uppercase tracking-[0.24em] text-[#A27A4E]">Pusat pemesanan</p>
                            <p class="mt-2 font-display text-2xl font-semibold text-[#2A211B]">Alur booking.</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3E6D5] text-[#9B6E3E]">
                            <i class="fa-brands fa-whatsapp text-xl"></i>
                        </div>
                    </div>

                    <div class="relative mt-7 space-y-5">
                        <div class="flex gap-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2A211B] text-[10px] font-bold text-white">01</span>
                            <div>
                                <p class="text-sm font-semibold text-[#2A211B]">Pilih produk yang sesuai</p>
                                <p class="mt-1 text-[11px] leading-5 text-[#8A7C6E]">Lihat katalog dan tentukan produk yang ingin kamu konsultasikan.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#E7DED2] text-[10px] font-bold text-[#9B6E3E]">02</span>
                            <div>
                                <p class="text-sm font-semibold text-[#2A211B]">Bahas kebutuhan dengan admin</p>
                                <p class="mt-1 text-[11px] leading-5 text-[#8A7C6E]">Ukuran, bahan, jumlah, harga, dan kebutuhan khusus dibicarakan langsung.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#E7DED2] text-[10px] font-bold text-[#9B6E3E]">03</span>
                            <div>
                                <p class="text-sm font-semibold text-[#2A211B]">Pesanan dicatat, kirim tracking</p>
                                <p class="mt-1 text-[11px] leading-5 text-[#8A7C6E]">Admin mencatat pesanan lalu mengirim link tracking pribadi untukmu.</p>
                            </div>
                        </div>
                    </div>

                    @if ($waDigits)
                        <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" class="relative mt-7 flex w-full items-center justify-center gap-2 rounded-xl bg-[#2A211B] px-5 py-3.5 text-xs font-bold text-white transition hover:bg-[#4A392D]">
                            <i class="fa-brands fa-whatsapp"></i>
                            Hubungi Admin Karya Ide Edi
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================
         ALUR BOOKING — tidak lagi memakai panel kosong/slider
         ========================================================= --}}
    <section class="border-b border-[#E7DED2] bg-[#F7F4EF]">
        <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:px-10 lg:py-20">
            <div class="max-w-2xl">
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#A27A4E]">Cara kerja</p>
                <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight text-[#2A211B] sm:text-4xl">Empat langkah, tanpa ribet.</h2>
                <p class="mt-3 max-w-xl text-sm leading-6 text-[#8A7C6E]">Tidak ada checkout publik dan tidak ada data pembeli yang dipajang. Pengunjung hanya memilih produk dan menghubungi admin.</p>
            </div>

            <div class="mt-10 grid overflow-hidden rounded-4xl border border-[#DED2C4] bg-white shadow-[0_20px_60px_-40px_rgba(42,33,27,0.45)] sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $steps = [
                        ['01', 'Pilih produk', 'Temukan mebel yang kamu suka dari katalog.', 'fa-couch'],
                        ['02', 'Tanya admin', 'Klik WhatsApp untuk menanyakan ukuran, bahan, stok, harga, atau kebutuhan khusus.', 'fa-comments'],
                        ['03', 'Admin buat pesanan', 'Admin mencatat data pemesan, jumlah, dan alamat pengantaran.', 'fa-clipboard-list'],
                        ['04', 'Terima tracking', 'Admin mengirim link pribadi untuk melihat status pesanan milikmu.', 'fa-link'],
                    ];
                @endphp

                @foreach ($steps as $index => $step)
                    <div class="relative p-7 sm:p-8 {{ $index < 3 ? 'lg:border-r lg:border-[#E9E0D5]' : '' }} {{ $index < 2 ? 'border-b sm:border-b lg:border-b-0 border-[#E9E0D5]' : '' }}">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#2A211B] text-[10px] font-bold text-white">{{ $step[0] }}</span>
                            <i class="fa-solid {{ $step[3] }} text-[#B68B5B]"></i>
                        </div>
                        <h3 class="mt-7 font-display text-xl font-semibold text-[#2A211B]">{{ $step[1] }}</h3>
                        <p class="mt-2 text-xs leading-6 text-[#8A7C6E]">{{ $step[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================================================
         TRUST / PRIVACY
         ========================================================= --}}
    <section class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-4 px-5 py-12 sm:px-8 md:grid-cols-3 lg:px-10">
            <div class="rounded-3xl border border-[#E7DED2] bg-[#FCFAF7] p-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F3E6D5] text-[#9B6E3E]"><i class="fa-solid fa-comments"></i></div>
                <h3 class="mt-5 font-display text-lg font-semibold">Konsultasi langsung</h3>
                <p class="mt-2 text-xs leading-6 text-[#8A7C6E]">Tanyakan ukuran, bahan, stok, harga, atau kebutuhan khusus kepada admin melalui WhatsApp.</p>
            </div>
            <div class="rounded-3xl border border-[#E7DED2] bg-[#FCFAF7] p-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F3E6D5] text-[#9B6E3E]"><i class="fa-solid fa-truck"></i></div>
                <h3 class="mt-5 font-display text-lg font-semibold">Diantar tim kami</h3>
                <p class="mt-2 text-xs leading-6 text-[#8A7C6E]">Alamat penerima dicatat oleh admin untuk kebutuhan pengantaran oleh tim Karya Ide Edi.</p>
            </div>
            <div class="rounded-3xl border border-[#E7DED2] bg-[#FCFAF7] p-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F3E6D5] text-[#9B6E3E]"><i class="fa-solid fa-shield-halved"></i></div>
                <h3 class="mt-5 font-display text-lg font-semibold">Tracking pribadi</h3>
                <p class="mt-2 text-xs leading-6 text-[#8A7C6E]">Detail pesanan hanya dibuka melalui link tracking pribadi yang dikirim admin kepada pemesan.</p>
            </div>
        </div>
    </section>

    {{-- =========================================================
         PRODUK TERLARIS — direkomendasikan berdasarkan jumlah pesanan
         (Transaction) per produk, bukan sekadar produk terbaru.
         ========================================================= --}}
    <section id="pilih-produk" class="scroll-mt-24 bg-white">
        <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:px-10 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#A27A4E]">Paling banyak dipesan</p>
                    <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight text-[#2A211B] sm:text-4xl">Produk terlaris.</h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-[#8A7C6E]">Klik <strong class="font-semibold text-[#5C4B3D]">Tanya Admin</strong> pada produk yang kamu inginkan. Tidak perlu mengisi data pesanan di website.</p>
                </div>
                <a href="{{ route('products.index') }}" class="group inline-flex items-center gap-2 border-b border-[#B7A28D] pb-1 text-xs font-semibold text-[#4D4035] transition hover:border-[#9B6E3E] hover:text-[#9B6E3E]">
                    Lihat katalog lengkap
                    <i class="fa-solid fa-arrow-right text-[10px] transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>

            @if ($bookingProducts->isEmpty())
                <div class="mt-10 rounded-3xl border border-dashed border-[#DCCFC0] bg-[#FCFAF7] p-10 text-center">
                    <i class="fa-solid fa-couch text-3xl text-[#C7B6A3]"></i>
                    <p class="mt-4 text-sm font-semibold">Belum ada produk aktif.</p>
                    <p class="mt-1 text-xs text-[#8A7C6E]">Silakan kembali lagi setelah katalog diperbarui.</p>
                </div>
            @else
                <div class="mt-10 grid grid-cols-1 gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($bookingProducts as $product)
                        @php
                            $hasDiscount = $product->harga_diskon && (float) $product->harga_diskon > 0 && (float) $product->harga_diskon < (float) $product->harga;
                            $displayPrice = $hasDiscount ? (float) $product->harga_diskon : (float) $product->harga;
                            $thumbnailUrl = ($product->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->thumbnail))
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->thumbnail)
                                : null;
                            $productWa = $waForProduct($product);
                        @endphp

                        <article class="group min-w-0">
                            <div class="relative overflow-hidden rounded-3xl bg-[#F4EFE8]">
                                @if ($thumbnailUrl)
                                    <img src="{{ $thumbnailUrl }}" alt="{{ $product->nama }}" loading="lazy" class="aspect-[0.95/1] h-full w-full object-contain p-5 transition duration-500 group-hover:scale-[1.035]">
                                @else
                                    <div class="flex aspect-[0.95/1] items-center justify-center text-[#C7B6A3]"><i class="fa-solid fa-couch text-5xl"></i></div>
                                @endif

                                <div class="absolute left-3 top-3 flex items-center gap-2">
                                    @if ($hasDiscount)
                                        <span class="rounded-full bg-[#2A211B] px-2.5 py-1 text-[9px] font-semibold text-white">Diskon</span>
                                    @endif
                                    @if ((int) $product->stok > 0)
                                        <span class="rounded-full bg-white/90 px-2.5 py-1 text-[9px] font-semibold text-[#4E6A53] shadow-sm backdrop-blur">Tersedia</span>
                                    @else
                                        <span class="rounded-full bg-white/90 px-2.5 py-1 text-[9px] font-semibold text-[#9A5A52] shadow-sm backdrop-blur">Habis</span>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-4">
                                <p class="text-[9px] uppercase tracking-[0.16em] text-[#A09384]">{{ $product->category?->name ?? 'Furniture' }}</p>
                                <h3 class="mt-1.5 font-display text-lg font-semibold leading-tight text-[#2A211B]">{{ $product->nama }}</h3>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-[#2A211B]">Rp{{ number_format($displayPrice, 0, ',', '.') }}</span>
                                    @if ($hasDiscount)
                                        <span class="text-[10px] text-[#A09384] line-through">Rp{{ number_format((float) $product->harga, 0, ',', '.') }}</span>
                                    @endif
                                </div>

                                @if ($productWa && (int) $product->stok > 0)
                                    <a href="{{ $productWa }}" target="_blank" rel="noopener" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#2A211B] px-4 py-3 text-xs font-semibold text-white transition hover:bg-[#4A392D]">
                                        <i class="fa-brands fa-whatsapp text-sm text-[#8ED081]"></i>
                                        Tanya Admin
                                    </a>
                                @elseif ((int) $product->stok <= 0)
                                    <span class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#E8E0D6] px-4 py-3 text-xs font-semibold text-[#8A7C6E]">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        Stok Habis
                                    </span>
                                @else
                                    <a href="{{ route('products.show', $product) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full border border-[#DCCFC0] px-4 py-3 text-xs font-semibold text-[#4D4035] transition hover:bg-[#F7F2EC]">
                                        Lihat Detail
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- =========================================================
         FINAL CTA
         ========================================================= --}}
    <section class="bg-[#211A15] text-white">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-7 px-5 py-14 sm:px-8 md:flex-row md:items-center lg:px-10 lg:py-16">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#D7A867]">Siap mulai?</p>
                <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Pilih mebelnya. Biar admin yang bantu.</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/55">Setelah pesanan dibuat oleh admin, link tracking pribadi akan dikirimkan kepada pembeli.</p>
            </div>
            <a href="#pilih-produk" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#D7A867] px-6 py-3.5 text-sm font-semibold text-[#241A12] transition hover:bg-[#E4BD87]">
                <i class="fa-solid fa-couch"></i>
                Pilih Produk
            </a>
        </div>
    </section>

    @include('partials.frontend.footer')
</body>
</html>