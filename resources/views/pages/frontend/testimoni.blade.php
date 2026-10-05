<!DOCTYPE html>
<html lang="id" data-site="frontend">
<head>
    @include('partials.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Testimoni — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

{{-- KIE-TESTIMONIAL-PAGE-MOBILE-COMPACT:START --}}
<style>
    @media (max-width: 639.98px) {
        html[data-site='frontend']
        body[data-kie-testimonial-page]
        select[data-kie-testimonial-sort] {
            width: 8.4rem !important;
            min-width: 8.4rem !important;
            max-width: 8.4rem !important;

            height: 1.8rem !important;
            min-height: 1.8rem !important;

            padding: 0 1.45rem 0 .5rem !important;
            border-radius: .45rem !important;

            font-size: 10.5px !important;
            line-height: 1 !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        select[data-kie-testimonial-sort] option {
            font-size: 10.5px !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        :has(> select[data-kie-testimonial-sort]) {
            gap: .35rem !important;
            font-size: 9.5px !important;
            line-height: 1 !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] {
            min-height: 0 !important;
            height: auto !important;

            margin-top: .75rem !important;
            padding: .9rem .85rem !important;

            border-radius: .9rem !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] .mt-8 {
            margin-top: .6rem !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] .mt-6 {
            margin-top: .5rem !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] .mt-4 {
            margin-top: .4rem !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty]
        :is(.h-14, .h-16, .w-14, .w-16) {
            width: 2rem !important;
            height: 2rem !important;
            min-width: 2rem !important;
            min-height: 2rem !important;
            border-radius: .65rem !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] .fa-quote-left,
        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] .fa-quote-right {
            font-size: .72rem !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] h2,
        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] h3 {
            font-size: .92rem !important;
            line-height: 1.2 !important;
        }

        html[data-site='frontend']
        body[data-kie-testimonial-page]
        [data-kie-testimonial-empty] p {
            font-size: .68rem !important;
            line-height: 1.45 !important;
        }
    }
</style>
{{-- KIE-TESTIMONIAL-PAGE-MOBILE-COMPACT:END --}}
</head>

<body data-kie-testimonial-page class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    @php
        $testimoniPageWarna = \App\Models\HomeSection::dataFor('testimoni-page', [
            'frame_2_color' => null,
        ]);

        /*
         * KIE_TESTIMONI_HERO_SYNC_PRODUK_V1
         * Hero Testimoni selalu mengikuti warna hero halaman Produk
         * agar header halaman frontend konsisten.
         */
        $produkWarnaData = \App\Models\HomeSection::dataFor('produk', [
            'bg_color_hero' => null,
        ]);

        $testimoniPageFrame1Color =
            $produkWarnaData['bg_color_hero'] ?? '#F6F9F6';

        $testimoniPageFrame2Color =
            $testimoniPageWarna['frame_2_color'] ?: '#FFFFFF';

        $testimoniHeroKontras = function (string $hex): array {
            $hex = ltrim($hex, '#');

            if (strtoupper($hex) === 'F6F9F6') {
                return [
                    'heading' => '#171717',
                    'soft' => '#A29587',
                    'strong' => '#2A211B',
                ];
            }

            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;

            $linearize = fn (float $c): float =>
                $c <= 0.03928
                    ? $c / 12.92
                    : (($c + 0.055) / 1.055) ** 2.4;

            $luminance =
                0.2126 * $linearize($r)
                + 0.7152 * $linearize($g)
                + 0.0722 * $linearize($b);

            return $luminance > 0.5
                ? [
                    'heading' => '#1A1208',
                    'soft' => 'rgba(26,18,8,.60)',
                    'strong' => '#1A1208',
                ]
                : [
                    'heading' => '#FFFFFF',
                    'soft' => 'rgba(255,255,255,.68)',
                    'strong' => '#FFFFFF',
                ];
        };

        $testimoniHeroWarna =
            $testimoniHeroKontras($testimoniPageFrame1Color);
    @endphp
    {{-- =====================================================
         HERO / BREADCRUMB — pola sama persis dengan hero Shop
         (produk-index.blade.php), cuma judul & breadcrumb-nya
         diganti "Testimoni" supaya konsisten satu situs.
    ====================================================== --}}
    <section style="background-color: {{ $testimoniPageFrame1Color }};">
        <div class="mx-auto flex min-h-43.75 max-w-295 items-center justify-center px-5 py-12 sm:px-7 lg:px-8">
            <div class="flex flex-col items-center text-center">
                <h1 class="font-display text-4xl font-semibold tracking-tight sm:text-5xl" style="color: {{ $testimoniHeroWarna['heading'] }};">Testimoni</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px]" style="color: {{ $testimoniHeroWarna['soft'] }};">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium" style="color: {{ $testimoniHeroWarna['strong'] }};">Testimoni</span>
                </div>
            </div>
        </div>
    </section>

    <div class="testimoni-page-frame2" style="background-color: {{ $testimoniPageFrame2Color }};">
        <style>
            .testimoni-page-frame2 > section { background: transparent !important; }
        </style>

    {{-- =====================================================
         TOP 3 TESTIMONI UNGGULAN — section yang SAMA PERSIS
         dengan yang ada di Beranda (partial yang sama, bukan
         duplikat logic): 3 testimoni approved+active yang
         DIPILIH ADMIN lewat is_featured_home. Kalau admin ganti
         pilihannya di menu Testimoni, otomatis ikut berubah di
         sini juga.
    ====================================================== --}}
    @include('partials.frontend.testimonials')

    {{-- =====================================================
         LIST TESTIMONI — hanya yang approval_status = approved
         DAN is_active = true. Ditampilkan apa adanya (nama,
         rating, komentar) tanpa keterangan tambahan bahwa ini
         komentar "pilihan admin" — murni daftar komentar.
    ====================================================== --}}
    <section class="bg-white">
        <div class="mx-auto max-w-295 px-5 py-10 sm:px-7 sm:py-12 lg:px-8 lg:py-14">

            <div class="flex flex-wrap items-center justify-between gap-3">
                @if ($testimonials->total() > 0)
                    <p class="text-[11px] text-[#75685B]">
                        Menampilkan {{ $testimonials->firstItem() }}–{{ $testimonials->lastItem() }} dari {{ $testimonials->total() }} Testimoni
                    </p>
                @else
                    <span></span>
                @endif

                {{-- Tampilan saja, belum difungsikan — mengikuti konvensi yang sudah
                     dipakai di navbar (search bar, dropdown kategori, dll juga
                     sengaja belum difungsikan). --}}
                <label class="flex items-center gap-2 rounded-md border border-[#E7D9C8] px-3 py-1.5 text-[11px] text-[#5C5147]">
                    Sort by:
                    <select data-kie-testimonial-sort class="bg-transparent pr-1 font-medium text-[#2A211B] focus:outline-none">
                        <option>Popularity</option>
                        <option>Terbaru</option>
                        <option>Rating Tertinggi</option>
                    </select>
                </label>
            </div>

            @if ($testimonials->isEmpty())
                <div class="mt-8 flex flex-col items-center justify-center rounded-3xl border border-dashed border-[#E7D9C8] bg-[#FBF7F1] px-6 py-20 text-center" data-kie-testimonial-empty>
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white">
                        <i class="fa-solid fa-quote-left text-xl text-[#F28A22]"></i>
                    </span>
                    <p class="mt-5 text-sm font-semibold text-[#2A211B]">Belum ada testimoni</p>
                    <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-[#75685B]">
                        Testimoni dari pelanggan akan tampil di sini begitu tersedia.
                    </p>
                </div>
            @else
                <style>
    .kie-all-review-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:22px;
        margin-top:24px;
    }
    .kie-all-review-card{
        overflow:hidden;
        border:1px solid #EEE4D8;
        border-radius:18px;
        background:#fff;
        box-shadow:0 5px 16px rgba(42,33,27,.06);
    }
    .kie-all-review-layout{
        display:grid;
        grid-template-columns:minmax(0,1fr) 125px;
        min-height:210px;
    }
    .kie-all-review-main{
        min-width:0;
        padding:22px;
        display:flex;
        flex-direction:column;
    }
    .kie-all-review-profile{
        display:flex;
        align-items:center;
        gap:11px;
    }
    .kie-all-review-avatar{
        width:42px;
        height:42px;
        flex:0 0 42px;
        border-radius:999px;
        object-fit:cover;
    }
    .kie-all-review-avatar-fallback{
        width:42px;
        height:42px;
        flex:0 0 42px;
        border-radius:999px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:#F7F1E8;
        color:#F28A22;
        font-size:12px;
        font-weight:700;
    }
    .kie-all-review-name{
        min-width:0;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
        font-size:13px;
        font-weight:700;
        color:#2A211B;
    }
    .kie-all-review-stars{
        margin-top:13px;
        display:flex;
        gap:3px;
        color:#F0A321;
        font-size:12px;
    }
    .kie-all-review-comment{
        margin-top:14px;
        flex:1;
        color:#5C5147;
    }
    .kie-all-review-comment i{
        color:#B5A596;
        font-size:12px;
    }
    .kie-all-review-comment p{
        margin-top:5px;
        font-size:12px;
        line-height:1.6;
        overflow-wrap:anywhere;
    }
    .kie-all-review-photos{
        display:flex;
        gap:8px;
        margin-top:15px;
        overflow-x:auto;
    }
    .kie-all-review-photo{
        width:68px;
        height:54px;
        flex:0 0 68px;
        overflow:hidden;
        border-radius:10px;
        border:1px solid #E8DED1;
    }
    .kie-all-review-photo img{
        width:100%;
        height:100%;
        object-fit:cover;
        display:block;
    }
    .kie-all-review-address{
        padding:22px 18px;
        border-left:1px solid #E8DED1;
        background:#FBF8F3;
        display:flex;
        flex-direction:column;
        justify-content:center;
    }
    .kie-all-review-address-title{
        font-size:9px;
        font-weight:800;
        letter-spacing:.18em;
        text-transform:uppercase;
        color:#8A7C6E;
    }
    .kie-all-review-address-list{
        display:grid;
        gap:16px;
        margin-top:16px;
    }
    .kie-all-review-address-label{
        font-size:8px;
        letter-spacing:.11em;
        text-transform:uppercase;
        color:#A29587;
    }
    .kie-all-review-address-value{
        margin-top:4px;
        font-size:11px;
        line-height:1.4;
        font-weight:650;
        color:#2A211B;
        overflow-wrap:anywhere;
    }
    .kie-all-review-address-value.is-empty{
        color:#A29587;
        font-weight:500;
        font-style:italic;
    }

    @media (min-width:640px) and (max-width:1023.98px){
        .kie-all-review-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
        .kie-all-review-layout{
            grid-template-columns:minmax(0,1fr) 125px;
        }
    }

    @media (max-width:639.98px){
        .kie-all-review-grid{
            grid-template-columns:1fr;
            gap:14px;
        }
        .kie-all-review-layout{
            grid-template-columns:1fr;
            min-height:0;
        }
        .kie-all-review-main{
            padding:16px;
        }
        .kie-all-review-address{
            padding:14px 16px 16px;
            border-left:0;
            border-top:1px solid #E8DED1;
        }
        .kie-all-review-address-list{
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:12px;
            margin-top:10px;
        }
    }
</style>

<div class="kie-all-review-grid">
    @foreach ($testimonials as $testimonial)
        @php
            $reviewPhotos = collect($testimonial->photos ?? [])
                ->filter()
                ->take(5)
                ->values();
        @endphp

        <article data-kie-all-testimonial-card class="kie-all-review-card">
            <div class="kie-all-review-layout">

                <div class="kie-all-review-main">
                    <div class="kie-all-review-profile">
                        @if ($testimonial->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($testimonial->foto))
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($testimonial->foto) }}"
                                alt="{{ $testimonial->displayName() }}"
                                class="kie-all-review-avatar"
                            >
                        @else
                            <span class="kie-all-review-avatar-fallback">
                                {{ strtoupper(mb_substr($testimonial->displayName(), 0, 1)) }}
                            </span>
                        @endif

                        <p class="kie-all-review-name">
                            {{ $testimonial->displayName() }}
                        </p>
                    </div>

                    <div class="kie-all-review-stars">
                        @for ($i = 1; $i <= 5; $i++)
                            <i
                                class="fa-solid fa-star"
                                style="opacity: {{ $i <= ($testimonial->rating ?? 0) ? '1' : '.25' }};"
                            ></i>
                        @endfor
                    </div>

                    <div class="kie-all-review-comment">
                        <i class="fa-solid fa-quote-left"></i>
                        <p>"{{ $testimonial->comment }}"</p>
                    </div>

                    @if ($reviewPhotos->isNotEmpty())
                        <div class="kie-all-review-photos">
                            @foreach ($reviewPhotos as $photo)
                                <a
                                    href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="kie-all-review-photo"
                                >
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}"
                                        alt="Foto dari {{ $testimonial->displayName() }}"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside class="kie-all-review-address">
                    <p class="kie-all-review-address-title">Alamat</p>

                    <div class="kie-all-review-address-list">
                        <div>
                            <p class="kie-all-review-address-label">Provinsi</p>
                            <p class="kie-all-review-address-value {{ filled($testimonial->provinsi) ? '' : 'is-empty' }}">
                                {{ $testimonial->provinsi ?: 'Belum tersedia' }}
                            </p>
                        </div>

                        <div>
                            <p class="kie-all-review-address-label">Kabupaten / Kota</p>
                            <p class="kie-all-review-address-value {{ filled($testimonial->kabupaten) ? '' : 'is-empty' }}">
                                {{ $testimonial->kabupaten ?: 'Belum tersedia' }}
                            </p>
                        </div>
                    </div>
                </aside>

            </div>
        </article>
    @endforeach
</div>

@if ($testimonials->hasPages())
                    <div class="mt-12 border-t border-[#E7D9C8] pt-6">
                        {{ $testimonials->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>

    </div>

    @include('partials.frontend.footer')
</body>
</html><style>
/* KIE-MOBILE-TESTIMONIAL-COMPACT-V2 */
@media (max-width:639.98px){

    .kie-all-review-grid{
        grid-template-columns:1fr !important;
        gap:12px !important;
        margin-top:16px !important;
    }

    .kie-all-review-card{
        border-radius:15px !important;
    }

    .kie-all-review-layout{
        display:block !important;
        min-height:0 !important;
    }

    .kie-all-review-main{
        display:block !important;
        min-height:0 !important;
        padding:15px !important;
    }

    .kie-all-review-profile{
        gap:9px !important;
    }

    .kie-all-review-avatar,
    .kie-all-review-avatar-fallback{
        width:36px !important;
        height:36px !important;
        flex-basis:36px !important;
    }

    .kie-all-review-name{
        font-size:12px !important;
    }

    .kie-all-review-stars{
        margin-top:9px !important;
        font-size:11px !important;
    }

    .kie-all-review-comment{
        flex:none !important;
        margin-top:10px !important;
    }

    .kie-all-review-comment p{
        margin-top:4px !important;
        font-size:11.5px !important;
        line-height:1.5 !important;
    }

    .kie-all-review-photos{
        margin-top:10px !important;
        gap:7px !important;
    }

    .kie-all-review-photo{
        width:62px !important;
        height:49px !important;
        flex-basis:62px !important;
        border-radius:8px !important;
    }

    .kie-all-review-address{
        padding:11px 15px 13px !important;
        border-left:0 !important;
        border-top:1px solid #E8DED1 !important;
    }

    .kie-all-review-address-title{
        font-size:8px !important;
    }

    .kie-all-review-address-list{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        gap:12px !important;
        margin-top:8px !important;
    }

    .kie-all-review-address-label{
        font-size:7px !important;
    }

    .kie-all-review-address-value{
        margin-top:3px !important;
        font-size:10.5px !important;
    }
}
</style>
<style>
/* KIE-MOBILE-TESTIMONIAL-ADDRESS-RIGHT */
@media (max-width:639.98px){

    .kie-all-review-layout{
        display:grid !important;
        grid-template-columns:minmax(0,1fr) 108px !important;
        min-height:0 !important;
        align-items:stretch !important;
    }

    .kie-all-review-main{
        display:block !important;
        min-width:0 !important;
        min-height:0 !important;
        padding:14px 12px 14px 14px !important;
    }

    .kie-all-review-address{
        min-width:0 !important;
        padding:14px 10px !important;

        border-top:0 !important;
        border-left:1px solid #E8DED1 !important;

        display:flex !important;
        flex-direction:column !important;
        justify-content:center !important;
    }

    .kie-all-review-address-list{
        display:grid !important;
        grid-template-columns:1fr !important;
        gap:12px !important;
        margin-top:10px !important;
    }

    .kie-all-review-address-title{
        font-size:7.5px !important;
        letter-spacing:.13em !important;
    }

    .kie-all-review-address-label{
        font-size:6.5px !important;
        line-height:1.3 !important;
    }

    .kie-all-review-address-value{
        margin-top:3px !important;
        font-size:9.5px !important;
        line-height:1.35 !important;
        overflow-wrap:anywhere !important;
    }

    .kie-all-review-avatar,
    .kie-all-review-avatar-fallback{
        width:34px !important;
        height:34px !important;
        flex-basis:34px !important;
    }

    .kie-all-review-stars{
        margin-top:8px !important;
    }

    .kie-all-review-comment{
        flex:none !important;
        margin-top:9px !important;
    }

    .kie-all-review-photos{
        margin-top:9px !important;
    }

    .kie-all-review-photo{
        width:56px !important;
        height:45px !important;
        flex-basis:56px !important;
    }
}
</style>
