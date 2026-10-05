@php
        // =========================================================
        // GALERI VIDEO PREMIUM â€” isi utama halaman Dokumentasi.
        // HERO di atas SENGAJA tidak disentuh.
        // Sumber data utama: section_key 'dokumentasi-3' dari Edit Web.
        // Kalau belum ada item di sana, fallback ke galeri lama (khusus
        // item bertipe video). Kalau masih kosong juga, section video tidak ditampilkan.
        // =========================================================
        $dokVideoSection = \App\Models\HomeSection::dataFor('dokumentasi-3', [
            'judul' => 'Galeri Video',
            'subjudul' => 'Dokumentasi Proses & Hasil',
            'deskripsi' => 'Lihat lebih dekat proses pengerjaan, detail finishing, hingga hasil akhir furnitur yang kami kerjakan.',
            'items' => [],
        ]);


        $dokVideoBentukItem = function (array $item) {
            if (($item['tipe'] ?? null) !== 'video') {
                return null;
            }

            $keterangan = trim((string) ($item['keterangan'] ?? ''));

            // Video hasil upload ke website.
            if (! empty($item['path'])) {
                return [
                    'src' => \Illuminate\Support\Facades\Storage::disk('public')->url($item['path']),
                    'provider' => 'direct',
                    'keterangan' => $keterangan,
                ];
            }

            // Video dari URL.
            $url = trim((string) ($item['url'] ?? ''));

            if ($url === '') {
                return null;
            }

            $info = \App\Models\HomeSection::classifyVideoUrl($url);

            $provider = $info['provider'] ?? null;
            $src = $info['embed_url'] ?? null;

            if (! $provider || ! $src) {
                return null;
            }

            // classifyVideoUrl() memakai "direct" sebagai fallback.
            // Jangan memaksa URL medsos/platform yang tidak dikenali
            // masuk ke tag <video>. URL tersebut tetap disimpan dan
            // dibuka ke platform asal.
            if (
                $provider === 'direct'
                && ! preg_match('/\.(mp4|webm|ogg|mov|m4v)(?:[?#].*)?$/i', $url)
            ) {
                $provider = 'external';
                $src = $url;
            }

            return [
                'src' => $src,
                'provider' => $provider,
                'thumbnail' => $info['thumbnail_url'] ?? null,
                'keterangan' => $keterangan,
            ];
        };

        $dokVideoFromDok3 = array_values(array_filter(array_map(
            $dokVideoBentukItem,
            is_array($dokVideoSection['items'] ?? null) ? $dokVideoSection['items'] : []
        )));

        $dokVideoFromLegacyGallery = array_values(array_filter(array_map(
            $dokVideoBentukItem,
            is_array($dokumentasiHero['galeri'] ?? null) ? $dokumentasiHero['galeri'] : []
        )));

        // KIE_REAL_DOK_VIDEO_FALLBACK:
        // Setelah dummy sample dibersihkan, Galeri Video bisa kosong walaupun
        // Dokumentasi punya video upload ASLI pada hero. Gunakan video asli
        // tersebut sebagai fallback terakhir; tidak ada dummy yang dikembalikan.
        $dokVideoFromHero = [];
        if (($dokumentasiVideo['provider'] ?? null) === 'direct' && ! empty($dokumentasiVideo['embed_url'])) {
            $dokVideoFromHero[] = [
                'src' => $dokumentasiVideo['embed_url'],
                'provider' => 'direct',
                'keterangan' => trim((string) ($dokumentasiHero['subjudul'] ?? '')) ?: 'Video Dokumentasi',
            ];
        }

        $dokVideoItems = count($dokVideoFromDok3) > 0
            ? $dokVideoFromDok3
            : (count($dokVideoFromLegacyGallery) > 0 ? $dokVideoFromLegacyGallery : $dokVideoFromHero);

        $dokVideoCount = count($dokVideoItems);

        $showAllVideos = (bool) ($showAllVideos ?? false);
        $dokVideoDisplayItems = $showAllVideos
            ? $dokVideoItems
            : array_slice($dokVideoItems, 0, 4);
        $dokVideoHasMore = ! $showAllVideos && $dokVideoCount > 4;
    @endphp

    @if ($dokVideoCount > 0)
        <section data-kie-dok-video-section class="relative overflow-hidden bg-[#F7F4EF] py-18 sm:py-20 lg:py-24">
            <div class="pointer-events-none absolute -left-32 top-16 h-48 w-48 rounded-full bg-[#E7D7C2]/45 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 bottom-8 h-44 w-44 rounded-full bg-[#D7C2A8]/30 blur-3xl"></div>

            <div
                x-data="{
                    open: false,
                    activeSrc: '',
                    activeTitle: '',
                    activeNumber: '',
                    activeProvider: '',
                    show(src, title, number, provider) {
                        this.activeSrc = src;
                        this.activeTitle = title;
                        this.activeNumber = number;
                        this.activeProvider = provider;
                        this.open = true;
                        document.body.classList.add('overflow-hidden');
                    },
                    close() {
                        this.open = false;
                        this.activeSrc = '';
                        this.activeTitle = '';
                        this.activeNumber = '';
                        this.activeProvider = '';
                        document.body.classList.remove('overflow-hidden');
                    }
                }"
                x-on:keydown.escape.window="close()"
                class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-10"
            >
                <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="h-px w-10 bg-[#B68A5B]"></span>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.42em] text-[#B07A43] sm:text-xs">
                                {{ $dokVideoSection['subjudul'] }}
                            </p>
                        </div>

                        <h2 class="font-display text-4xl font-semibold leading-none text-[#3D2B1F] sm:text-5xl lg:text-[4rem]">
                            {{ $dokVideoSection['judul'] }}
                        </h2>

                        <p class="mt-6 max-w-2xl text-base leading-9 text-[#6E6357] sm:text-lg">
                            {{ $dokVideoSection['deskripsi'] }}
                        </p>
                    </div>

                    <style>.kie-video-more-btn{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.25rem;border:1px solid #B98A52!important;border-radius:9999px;background:#6E4825!important;color:#fff!important;font-size:.875rem;font-weight:600;box-shadow:0 14px 38px -24px rgba(61,43,31,.68);transition:transform .25s ease,background .25s ease,box-shadow .25s ease}.kie-video-more-btn:before{content:"";position:absolute;top:-50%;left:-35%;width:22%;height:200%;background:linear-gradient(90deg,transparent,rgba(255,220,145,.95),transparent);transform:skewX(-22deg);opacity:0;pointer-events:none}.kie-video-more-btn:hover{background:#5B3A1E!important;transform:translateY(-2px);box-shadow:0 18px 44px -20px rgba(185,138,82,.8)}.kie-video-more-btn:hover:before{animation:kieGoldShine .72s ease-out}@keyframes kieGoldShine{0%{left:-35%;opacity:0}15%{opacity:1}100%{left:125%;opacity:0}}</style>

                    <div class="flex w-full flex-wrap items-center justify-end gap-3" style="justify-content:flex-end;">
                        @if ($dokVideoHasMore)
                            <a
                                href="{{ route('dokumentasi.video') }}"
                                class="kie-video-more-btn" style="order:2;"
                            >
                                Lihat Selengkapnya
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        @endif
                        <div class="inline-flex items-center gap-3 rounded-full border border-[#D7C4AD] bg-white/85 px-5 py-3 shadow-[0_14px_40px_-32px_rgba(61,43,31,0.45)] backdrop-blur" style="order:1;">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#4B2F1F] text-white">
                                <i class="fa-solid fa-film text-sm"></i>
                            </span>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#9F8B74]">Koleksi</p>
                                <p class="text-lg font-semibold text-[#3D2B1F]">{{ $dokVideoCount }} Video</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 h-px w-full bg-linear-to-r from-[#DCCEBB] via-[#CDB89D] to-transparent"></div>

                <div data-kie-video-grid class="mt-9 grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($dokVideoDisplayItems as $i => $video)
                        @php
                            $nomorVideo = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                            $judulVideo = $video['keterangan'] ?: 'Video Dokumentasi '.$nomorVideo;
                        @endphp

                        <button
                            type="button"
                            x-on:click="show(@js($video['src']), @js($judulVideo), @js($nomorVideo), @js($video['provider'] ?? 'direct'))"
                            data-kie-video-card
                            class="group relative overflow-hidden rounded-4xl border border-[#4E3324]/18 bg-[#EADCCB] text-left shadow-[0_26px_70px_-48px_rgba(61,43,31,0.62)] transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_30px_80px_-42px_rgba(61,43,31,0.72)] focus:outline-none focus:ring-2 focus:ring-[#9B6E3E]/30"
                        >
                            <div class="relative aspect-video overflow-hidden bg-[#D8C0A6]" style="aspect-ratio: 3 / 4;">
                                @if (($video['provider'] ?? 'direct') === 'direct')
                                <video
                                    src="{{ $video['src'] }}"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                    preload="metadata"
                                    x-data
                                    x-init="
const video = $el;
video.muted = true;
video.volume = 0;

const playVideo = () => video.play().catch(() => {});

playVideo();
video.addEventListener('loadeddata', playVideo);
video.addEventListener('canplay', playVideo);

if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                playVideo();
            } else {
                video.pause();
            }
        });
    }, { threshold: 0.15 });

    observer.observe(video);
}
"
                                ></video>

                                @elseif (($video['provider'] ?? null) === 'external')
                                <div class="absolute inset-0 flex items-center justify-center bg-[#2F2118] text-white">
                                    <div class="px-5 text-center">
                                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-white/20 bg-white/10">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-lg"></i>
                                        </span>

                                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em]">
                                            Buka Video
                                        </p>
                                    </div>
                                </div>

                                @elseif (($video['provider'] ?? null) === 'tiktok')
                                <iframe
                                    src="{{ $video['src'] }}?autoplay=1&loop=1&muted=1&controls=0&progress_bar=0&play_button=0&volume_control=0&fullscreen_button=0&timestamp=0&music_info=0&description=0&rel=0&native_context_menu=0&closed_caption=0"
                                    class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 border-0 bg-black" style="width:238%;height:100%;max-width:none;"
                                    allow="autoplay; encrypted-media; picture-in-picture"
                                    allowfullscreen
                                    scrolling="no"
                                    loading="eager"
                                    tabindex="-1"
                                    x-data
                                    x-init="
                                        const frame = $el;
                                        const startTikTok = () => {
                                            try {
                                                frame.contentWindow.postMessage({ type: 'mute', value: null, 'x-tiktok-player': true }, '*');
                                                frame.contentWindow.postMessage({ type: 'play', value: null, 'x-tiktok-player': true }, '*');
                                            } catch (e) {}
                                        };
                                        frame.addEventListener('load', () => setTimeout(startTikTok, 700));
                                        window.addEventListener('message', (event) => {
                                            if (event.source !== frame.contentWindow) return;
                                            if (event.data && event.data['x-tiktok-player'] && event.data.type === 'onPlayerReady') {
                                                startTikTok();
                                            }
                                        });
                                    "
                                ></iframe>

                                @else
                                <iframe
                                    src="{{ $video['src'] }}"
                                    class="pointer-events-none absolute inset-0 h-full w-full border-0 bg-black"
                                    allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                    allowfullscreen
                                    loading="eager"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    tabindex="-1"
                                ></iframe>
                                @endif

                                <div class="absolute inset-0 bg-linear-to-t from-[#160E09]/90 via-[#160E09]/25 to-[#160E09]/10"></div>
                                <div class="absolute inset-0 bg-linear-to-br from-[#6D4426]/24 via-transparent to-[#0F0906]/16"></div>

                                <div class="absolute left-4 right-4 top-4 flex items-start justify-between gap-3">
                                    <span class="inline-flex min-w-12 items-center justify-center rounded-full border border-white/20 bg-black/28 px-3 py-2 text-xs font-semibold tracking-[0.16em] text-white backdrop-blur">
                                        {{ $nomorVideo }}
                                    </span>

                                    <span class="inline-flex items-center gap-2 rounded-full border border-white/16 bg-black/28 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-white backdrop-blur">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#E8BE84]"></span>
                                        Video
                                    </span>
                                </div>

                                <div data-kie-video-caption class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-[#E7C89A]">
                                        Dokumentasi Karya
                                    </p>
                                    <h3 class="mt-2 line-clamp-2 text-xl font-semibold leading-snug text-white">
                                        {{ $judulVideo }}
                                    </h3>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 flex items-center justify-center gap-4 text-center text-sm text-[#9A7E61]">
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                    <p>Klik video untuk melihat dalam ukuran lebih besar</p>
                    <span class="hidden h-px w-14 bg-[#D9C9B4] sm:block"></span>
                </div>

                <div
                    x-show="open"
                    x-transition.opacity
                    x-cloak
                    class="fixed inset-0 z-100 flex items-center justify-center bg-[#120C08]/78 px-4 py-6 backdrop-blur-sm"
                >
                    <div class="absolute inset-0" x-on:click="close()"></div>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-6 scale-[0.98]"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 scale-[0.98]"
                        class="relative z-10 w-full overflow-hidden rounded-4xl border border-white/12 bg-[#17100D] shadow-[0_30px_100px_-36px_rgba(0,0,0,0.7)]"
                        x-bind:class="activeProvider === 'tiktok' ? 'max-w-md' : 'max-w-5xl'"
                    >
                        <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-[#D4B083]" x-text="'Video ' + activeNumber"></p>
                                <h3 class="mt-1 truncate text-lg font-semibold text-white sm:text-xl" x-text="activeTitle"></h3>
                            </div>

                            <button
                                type="button"
                                x-on:click="close()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/12 bg-white/6 text-white transition hover:bg-white/12"
                            >
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <div class="bg-black p-3 sm:p-4">
                            <div class="overflow-hidden rounded-3xl bg-black">
                                <template x-if="activeProvider === 'direct'">
                                    <video
                                        x-bind:src="activeSrc"
                                        class="mx-auto block max-h-[78vh] max-w-full bg-black object-contain"
                                        controls
                                        autoplay
                                        playsinline
                                    ></video>
                                </template>

                                <template x-if="activeProvider !== 'direct' && activeProvider !== 'external'">
                                    <iframe
                                        x-bind:src="activeSrc"
                                        x-bind:class="activeProvider === 'tiktok'
                                        ? 'mx-auto block h-[78vh] aspect-[9/16] max-w-full border-0 bg-black'
                                        : 'h-[72vh] w-full border-0 bg-black'"
                                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                        allowfullscreen
                                        referrerpolicy="strict-origin-when-cross-origin"
                                    ></iframe>
                                </template>

                                <template x-if="activeProvider === 'external'">
                                    <div class="flex min-h-72 items-center justify-center px-6 py-12 text-center">
                                        <div>
                                            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-xl"></i>
                                            </span>

                                            <p class="mt-5 text-sm leading-7 text-white/70">
                                                Tautan ini tetap dapat digunakan, tetapi platform asal tidak menyediakan pemutar yang bisa ditampilkan langsung di website.
                                            </p>

                                            <a
                                                x-bind:href="activeSrc"
                                                target="_blank"
                                                rel="noopener"
                                                class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-[#3D2B1F]"
                                            >
                                                Buka video di platform asal
                                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
