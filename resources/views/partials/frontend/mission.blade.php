{{--
    ==========================================================
    TENTANG KAMI — Homepage Karya Ide Edi
    ==========================================================
    Komponen UI murni (belum ada logika bisnis apapun), dirombak
    sesuai referensi Figma terbaru: kolase 2 foto + kartu statistik
    di kiri, judul + paragraf + 3 poin unggulan + link profil di kanan.

    - Judul, paragraf, 3 poin unggulan, dan angka "15 thn" MASIH
      statis (hardcode) — belum ada kolom/tabel yang sesuai di
      database untuk konten ini.
    - Link "Lihat Profil Kami" mengarah ke '#' karena route Profil
      belum dibuat (pola sama seperti $navMenu di navbar).
    - Background section (#FEEDD8) & warna kartu statistik memakai
      nilai hex yang disamakan presisi dari hasil sampling piksel
      referensi Figma, senada dengan token --color-admin-gold yang
      sudah ada di project.
    - 2 foto SEMENTARA pakai stock photo Pexels (free-to-use, boleh
      dipakai komersial): "Carpenter Working in a Busy Workshop" oleh
      Alax Matias, dan "Wood Grain" oleh tbee. Ganti dengan foto asli
      begitu sudah ada — taruh di public/images/tentang-kami-1.jpg
      dan public/images/tentang-kami-2.jpg, lalu ganti src masing-masing
      jadi {{ asset('images/tentang-kami-1.jpg') }} dan
      {{ asset('images/tentang-kami-2.jpg') }}.

    Pemakaian:
        @include('partials.frontend.mission')
    ==========================================================
--}}

@php
    $missionSetting = \App\Models\Setting::current();
@endphp

<section class="bg-[#FEEDD8]">
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-14 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-20">

        {{-- ============ KIRI: Kolase foto + kartu statistik ============ --}}
        <div class="grid grid-cols-3 grid-rows-2 gap-4">
            <img
                src="https://images.pexels.com/photos/28513061/pexels-photo-28513061.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1200"
                alt="Proses pembuatan furnitur {{ $missionSetting->site_name }}"
                class="col-span-2 row-span-2 aspect-3/4 w-full rounded-2xl object-cover shadow-lg"
            >
            <img
                src="https://images.pexels.com/photos/82256/pexels-photo-82256.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=800"
                alt="Detail material furnitur {{ $missionSetting->site_name }}"
                class="col-span-1 row-span-1 h-full w-full rounded-2xl object-cover shadow-lg"
            >
            <div class="col-span-1 row-span-1 flex h-full flex-col justify-center rounded-2xl bg-admin-gold p-4 text-white shadow-lg">
                <p class="font-display text-2xl leading-none sm:text-3xl">15 thn</p>
                <p class="mt-2 text-[10px] font-semibold uppercase leading-snug tracking-wide text-white/80 sm:text-[11px]">
                    Penghargaan Karya Terbaik
                </p>
            </div>
        </div>

        {{-- ============ KANAN: Teks profil ============ --}}
        <div>
            <h2 class="font-display text-2xl leading-tight text-[#4B3A26] sm:text-3xl lg:text-4xl">
                Setiap Furnitur Memiliki Cerita di Baliknya
            </h2>
            <p class="mt-5 max-w-lg text-sm leading-relaxed text-admin-ink-soft">
                Berawal dari tangan seorang pengrajin yang mencintai kayu, Karya Ide Edi lahir
                untuk menghadirkan furnitur berkualitas yang dibuat dengan teliti, tanpa
                mengorbankan kualitas maupun kelestarian bahan. Kini, misi kami adalah membantu
                Anda menciptakan ruang yang mencerminkan kepribadian Anda, dengan kenyamanan
                yang bertahan bertahun-tahun.
            </p>

            {{-- 3 poin unggulan --}}
            <ul class="mt-7 space-y-5">
                @foreach ([
                    ['icon' => 'fa-leaf', 'title' => 'Bahan Baku Berkelanjutan', 'desc' => 'Setiap kayu bersertifikat dari hutan yang dikelola secara bertanggung jawab.'],
                    ['icon' => 'fa-hammer', 'title' => 'Pengrajin Ahli', 'desc' => 'Detail akhir dikerjakan tangan oleh pengrajin berpengalaman puluhan tahun.'],
                    ['icon' => 'fa-rotate-left', 'title' => 'Garansi Retur 30 Hari', 'desc' => 'Tidak sesuai ekspektasi? Kami jemput dan proses pengembalian tanpa ribet.'],
                ] as $point)
                    <li class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#CDA97E] text-white">
                            <i class="fa-solid {{ $point['icon'] }} text-sm"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-[#4B3A26]">{{ $point['title'] }}</p>
                            <p class="mt-0.5 text-sm text-admin-ink-soft">{{ $point['desc'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8 flex justify-end">
                <a
                    href="#"
                    class="group inline-flex items-center gap-2 border-b border-admin-ink/30 pb-0.5 text-sm font-medium text-[#4B3A26] transition-colors duration-300 hover:border-admin-accent hover:text-admin-accent"
                >
                    Lihat Profil Kami
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
            </div>
        </div>
    </div>
</section>
