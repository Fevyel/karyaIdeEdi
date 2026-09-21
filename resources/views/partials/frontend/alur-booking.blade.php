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
                <div class="relative grid grid-cols-[auto_1fr] items-start gap-x-4 border-[#E9E0D5] p-5 sm:block sm:p-8 {{ $index < 3 ? 'lg:border-r' : '' }} {{ $index < 2 ? 'border-b sm:border-b lg:border-b-0' : '' }} {{ $index === 2 ? 'border-b sm:border-b-0' : '' }}">
                    <div class="contents sm:flex sm:items-start sm:justify-between sm:gap-4">
                        <span class="row-span-2 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#2A211B] text-[10px] font-bold text-white sm:row-span-1">{{ $step[0] }}</span>
                        <i class="fa-solid {{ $step[3] }} absolute right-5 top-5 text-[#B68B5B] sm:static"></i>
                    </div>
                    <h3 class="pr-8 font-display text-xl font-semibold text-[#2A211B] sm:mt-7 sm:pr-0">{{ $step[1] }}</h3>
                    <p class="mt-1 text-sm leading-5 text-[#6F6255] sm:mt-2 sm:text-xs sm:leading-6 sm:text-[#8A7C6E]">{{ $step[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
