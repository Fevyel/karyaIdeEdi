<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Testimoni — {{ \App\Models\Setting::current()->site_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-white font-sans antialiased text-[#2A211B]">
    @include('partials.frontend.navbar')

    {{-- =====================================================
         HERO / BREADCRUMB — pola sama persis dengan hero Shop
         (produk-index.blade.php), cuma judul & breadcrumb-nya
         diganti "Testimoni" supaya konsisten satu situs.
    ====================================================== --}}
    <section class="bg-[#F6F9F6]">
        <div class="mx-auto flex min-h-43.75 max-w-295 items-center justify-center px-5 py-12 sm:px-7 lg:px-8">
            <div class="flex flex-col items-center text-center">
                <h1 class="font-display text-4xl font-semibold tracking-tight text-[#171717] sm:text-5xl">Testimoni</h1>
                <div class="mt-3 flex items-center gap-2 text-[11px] text-[#A29587]">
                    <a href="{{ route('home') }}" class="transition-colors hover:text-[#2A211B]">Home</a>
                    <span>/</span>
                    <span class="font-medium text-[#F28A22]">Testimoni</span>
                </div>
            </div>
        </div>
    </section>

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
                    <select class="bg-transparent pr-1 font-medium text-[#2A211B] focus:outline-none">
                        <option>Popularity</option>
                        <option>Terbaru</option>
                        <option>Rating Tertinggi</option>
                    </select>
                </label>
            </div>

            @if ($testimonials->isEmpty())
                <div class="mt-8 flex flex-col items-center justify-center rounded-3xl border border-dashed border-[#E7D9C8] bg-[#FBF7F1] px-6 py-20 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white">
                        <i class="fa-solid fa-quote-left text-xl text-[#F28A22]"></i>
                    </span>
                    <p class="mt-5 text-sm font-semibold text-[#2A211B]">Belum ada testimoni</p>
                    <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-[#75685B]">
                        Testimoni dari pelanggan akan tampil di sini begitu tersedia.
                    </p>
                </div>
            @else
                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <div class="flex flex-col rounded-2xl border border-[#F0ECE7] bg-white p-6 shadow-sm">
                            <div class="flex items-center gap-0.5 text-[#F0A321]">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-solid fa-star text-[11px] {{ $i > ($testimonial->rating ?? 0) ? 'text-[#E3DED7]' : '' }}"></i>
                                @endfor
                            </div>

                            <p class="mt-4 flex-1 text-[11px] leading-relaxed text-[#5C5147] sm:text-xs">
                                {{ $testimonial->comment }}
                            </p>

                            <div class="mt-5 flex items-center gap-3 border-t border-[#F0ECE7] pt-4">
                                @if ($testimonial->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($testimonial->foto))
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($testimonial->foto) }}"
                                        alt="{{ $testimonial->customer_name }}"
                                        class="h-10 w-10 shrink-0 rounded-full object-cover"
                                    >
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F7F1E8] text-xs font-semibold text-[#F28A22]">
                                        {{ strtoupper(substr($testimonial->customer_name, 0, 1)) }}
                                    </span>
                                @endif

                                <div class="min-w-0">
                                    <p class="truncate text-[11px] font-semibold text-[#2A211B] sm:text-xs">
                                        {{ $testimonial->customer_name }}
                                    </p>
                                    @if ($testimonial->jabatan)
                                        <p class="truncate text-[10px] text-[#A1988E]">
                                            {{ $testimonial->jabatan }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
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

    @include('partials.frontend.footer')
</body>
</html>