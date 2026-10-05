@php
        // GALERI FOTO PREMIUM â€” data baru section_key 'dokumentasi-foto'.
        // Fallback ke galeri lama hanya untuk menjaga foto existing sebelum
        // admin pertama kali menekan Simpan di tab Galeri Foto yang baru.
        $dokFotoSection = \App\Models\HomeSection::dataFor('dokumentasi-foto', [
            'judul' => 'Momen Karya dalam Bingkai',
            'subjudul' => 'Galeri Foto',
            'deskripsi' => 'Dokumentasi visual yang menampilkan proses, detail pengerjaan, hingga hasil akhir furnitur secara lebih dekat, bersih, dan profesional.',
            'items' => [],
        ]);
        $dokPhotoBentukItem = function (array $item) {
            $src = isset($item['path']) && $item['path'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['path']) : ($item['url'] ?? null);
            if (! $src || ($item['tipe'] ?? null) !== 'foto') return null;
            return ['src' => $src, 'keterangan' => trim((string) ($item['keterangan'] ?? ''))];
        };
        $dokPhotoItems = array_values(array_filter(array_map($dokPhotoBentukItem, is_array($dokFotoSection['items'] ?? null) ? $dokFotoSection['items'] : [])));
        if ($dokPhotoItems === []) {
            $dokPhotoItems = array_values(array_filter(array_map($dokPhotoBentukItem, is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : [])));
        }
        $dokPhotoCount = count($dokPhotoItems);
    @endphp

    @if ($dokPhotoCount > 0)
        <section class="relative overflow-hidden border-t border-[#E7DCCF] bg-white py-18 sm:py-20 lg:py-24">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-linear-to-b from-[#F6EFE6]/85 to-transparent"></div>
            <div x-data="{open:false,activeSrc:'',activeTitle:'',activeNumber:'',show(src,title,n){this.activeSrc=src;this.activeTitle=title;this.activeNumber=n;this.open=true;document.body.classList.add('overflow-hidden')},close(){this.open=false;document.body.classList.remove('overflow-hidden')}}" x-on:keydown.escape.window="close()" class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_26rem] lg:items-end">
                    <div><div class="mb-4 flex items-center gap-3"><span class="h-px w-10 bg-[#C39058]"></span><p class="text-[11px] font-semibold uppercase tracking-[0.42em] text-[#BC8651] sm:text-xs">{{ $dokFotoSection['subjudul'] }}</p></div><h2 class="font-display text-4xl font-semibold leading-none text-[#3D2B1F] sm:text-5xl lg:text-[3.8rem]">{{ $dokFotoSection['judul'] }}</h2><p class="mt-6 max-w-2xl text-base leading-9 text-[#6E6357] sm:text-lg">{{ $dokFotoSection['deskripsi'] }}</p></div>
                    <style>
                        .kie-photo-actions{
                            display:flex;
                            width:100%;
                            flex-wrap:wrap;
                            align-items:center;
                            justify-content:flex-end;
                            gap:.75rem;
                        }
                        .kie-photo-more-btn{
                            position:relative;
                            overflow:hidden;
                            display:inline-flex !important;
                            align-items:center;
                            justify-content:center;
                            gap:.5rem;
                            padding:.75rem 1.25rem;
                            border:1px solid #B98A52;
                            border-radius:9999px;
                            background:#6E4825;
                            color:#fff !important;
                            text-decoration:none !important;
                            font-size:.875rem;
                            font-weight:600;
                            box-shadow:0 14px 38px -24px rgba(61,43,31,.68);
                            transition:transform .25s ease,background .25s ease,box-shadow .25s ease;
                        }
                        .kie-photo-more-btn::before{
                            content:"";
                            position:absolute;
                            top:-55%;
                            left:-35%;
                            width:24%;
                            height:210%;
                            background:linear-gradient(90deg,transparent,rgba(255,224,151,.98),transparent);
                            transform:skewX(-22deg);
                            opacity:0;
                            pointer-events:none;
                        }
                        .kie-photo-more-btn:hover{
                            background:#5B3A1E;
                            transform:translateY(-2px);
                            box-shadow:0 18px 44px -20px rgba(185,138,82,.82);
                        }
                        .kie-photo-more-btn:hover::before{
                            animation:kiePhotoGoldShine .72s ease-out;
                        }
                        @keyframes kiePhotoGoldShine{
                            0%{left:-35%;opacity:0}
                            15%{opacity:1}
                            100%{left:125%;opacity:0}
                        }
                    </style>

                    <div class="kie-photo-actions">
                        <div
                            data-kie-photo-count-pill
                            class="inline-flex w-fit items-center gap-3 rounded-full border border-[#D7C4AD] bg-white/85 px-5 py-3 shadow-[0_14px_40px_-32px_rgba(61,43,31,0.45)] backdrop-blur"
                        >
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#4B2F1F] text-white">
                                <i class="fa-solid fa-camera-retro text-sm"></i>
                            </span>

                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#9F8B74]">
                                    Portofolio
                                </p>
                                <p class="text-lg font-semibold text-[#3D2B1F]">
                                    {{ $dokPhotoCount }} Foto Pilihan
                                </p>
                            </div>
                        </div>

                        @if (($showPhotoMoreButton ?? true) && $dokPhotoCount > 4)
                            <a
                                href="{{ \Illuminate\Support\Facades\Route::has('dokumentasi.foto') ? route('dokumentasi.foto') : url('/dokumentasi/foto') }}"
                                class="kie-photo-more-btn"
                            >
                                <span style="position:relative;z-index:1;">Lihat Selengkapnya</span>
                                <i class="fa-solid fa-arrow-right text-xs" style="position:relative;z-index:1;"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div data-kie-photo-grid class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4" style="grid-auto-rows:220px;">
                    @foreach ($dokPhotoItems as $i => $photo)
                        @php
                            $n = str_pad($i + 1, 2, '0', STR_PAD_LEFT); $title = $photo['keterangan'] ?: 'Foto Dokumentasi '.$n; $sisa = $dokPhotoCount % 4;
                            $isLast = $i === $dokPhotoCount - 1;
                            $layout = match ($i % 6) {0 => 'md:col-span-2 md:row-span-2',1 => 'xl:row-span-2',2 => '',3 => '',4 => 'md:col-span-2',default => ''};
                            if ($isLast && $sisa === 1) $layout = 'md:col-span-2 xl:col-span-4 md:row-span-2';
                        @endphp
                        <button type="button" x-on:click="show(@js($photo['src']),@js($title),@js($n))" data-kie-photo-card class="group relative {{ $layout }} overflow-hidden rounded-4xl border border-[#E7DACB] bg-[#F3E6D5] text-left shadow-[0_22px_60px_-40px_rgba(61,43,31,0.4)] transition duration-300 hover:-translate-y-1">
                            <img src="{{ $photo['src'] }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.05]" loading="lazy"><div class="absolute inset-0 bg-linear-to-t from-[#140D08]/88 via-[#140D08]/22 to-transparent"></div>
                            <div class="absolute left-4 right-4 top-4 flex justify-between"><span class="rounded-full border border-white/18 bg-black/24 px-3 py-2 text-xs font-semibold text-white backdrop-blur">{{ $n }}</span><span class="rounded-full border border-white/15 bg-white/10 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white backdrop-blur"><i class="fa-solid fa-image mr-1"></i> Foto</span></div>
                            <div data-kie-video-caption class="absolute inset-x-0 bottom-0 p-5 sm:p-6"><p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-[#E8C692]">Dokumentasi Visual</p><h3 class="mt-2 line-clamp-2 text-xl font-semibold text-white">{{ $title }}</h3></div>
                        </button>
                    @endforeach
                </div>
                <div class="mt-8 flex items-center justify-center gap-4 text-sm text-[#9A7E61]"><span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span><p>Klik foto untuk melihat dalam ukuran lebih besar</p><span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span></div>
                <div x-show="open" x-transition.opacity x-cloak class="fixed inset-0 z-100 flex items-center justify-center bg-[#120C08]/80 px-4 py-6 backdrop-blur-sm"><div class="absolute inset-0" x-on:click="close()"></div><div class="relative z-10 w-full max-w-6xl overflow-hidden rounded-4xl bg-[#17110E]"><div class="flex items-center justify-between border-b border-white/10 px-6 py-4"><div><p class="text-[11px] uppercase tracking-[0.3em] text-[#D4B083]" x-text="'Foto '+activeNumber"></p><h3 class="mt-1 text-xl font-semibold text-white" x-text="activeTitle"></h3></div><button type="button" x-on:click="close()" class="h-11 w-11 rounded-full border border-white/12 text-white"><i class="fa-solid fa-xmark"></i></button></div><div class="bg-[#120C08] p-4"><img x-bind:src="activeSrc" x-bind:alt="activeTitle" class="max-h-[78vh] w-full rounded-3xl object-contain"></div></div></div>
            </div>
        </section>
    @endif
