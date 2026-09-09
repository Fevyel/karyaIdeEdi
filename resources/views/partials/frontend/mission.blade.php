{{--
    ==========================================================
    SEJAK BERDIRI — Homepage Karya Ide Edi
    ==========================================================
    Ini section "Tentang Kami" yang bisa diedit admin lewat
    Admin > Edit Web > Sejak Berdiri (lihat pages/admin/edit-web.blade.php).
    Data tersimpan di tabel home_sections dengan section_key =
    'sejak-berdiri' (App\Models\HomeSection). Yang BISA diedit admin:
    warna latar (frame), judul, paragraf, 3 poin unggulan (ikon + judul +
    deskripsi), angka & label pada kartu statistik, dan 2 foto kolase
    (foto besar rasio 3:4, foto kecil rasio 1:1 -- diatur lewat cropper
    drag+zoom, sama seperti Header/Produk/Kategori/Pengaturan). Warna
    judul/paragraf/ikon/kartu angka BUKAN field terpisah -- otomatis
    dihitung dari kontras warna latar (lihat $contrastMissionColors di
    bawah), pola sama dengan Header di partials/frontend/hero.blade.php.

    Yang TIDAK bisa diedit dari admin (sengaja hardcode):
    - Label & link "Lihat Profil Kami" (selalu menuju route profile.index).

    Sebelum admin pernah mengganti foto, dipakai 2 foto stok Pexels
    (free-to-use, boleh dipakai komersial) sebagai fallback: "Carpenter
    Working in a Busy Workshop" oleh Alax Matias, dan "Wood Grain" oleh
    tbee -- persis seperti sebelum section ini bisa diedit.

    CATATAN FIX (crop foto kecil beda dengan Edit Web):
    Kolom kanan kolase (foto kecil + kartu angka) sekarang dibungkus
    flex-col sendiri. Foto kecil TIDAK boleh dikasih h-full lagi --
    sebelumnya h-full+w-full menimpa aspect-square sehingga box-nya
    ikut bentuk sel grid (bukan kotak 1:1), jadi crop yang tampil di
    Beranda beda dari crop kotak yang diatur admin. Sekarang foto kecil
    murni kotak dari lebarnya sendiri, dan kartu angka (flex-1) yang
    mengisi sisa tinggi kolom -- jadi tidak ada celah kosong.

    Pemakaian:
        @include('partials.frontend.mission')
    ==========================================================
--}}

@php
    $missionSetting = \App\Models\Setting::current();

    $missionSection = \App\Models\HomeSection::dataFor('sejak-berdiri', [
        'bg_color' => null,
        'title' => 'Setiap Furnitur Memiliki Cerita di Baliknya',
        'description' => 'Berawal dari tangan seorang pengrajin yang mencintai kayu, Karya Ide Edi lahir untuk menghadirkan furnitur berkualitas yang dibuat dengan teliti, tanpa mengorbankan kualitas maupun kelestarian bahan. Kini, misi kami adalah membantu Anda menciptakan ruang yang mencerminkan kepribadian Anda, dengan kenyamanan yang bertahan bertahun-tahun.',
        'points' => [
            ['icon' => 'fa-leaf', 'title' => 'Bahan Baku Berkelanjutan', 'desc' => 'Setiap kayu bersertifikat dari hutan yang dikelola secara bertanggung jawab.'],
            ['icon' => 'fa-hammer', 'title' => 'Pengrajin Ahli', 'desc' => 'Detail akhir dikerjakan tangan oleh pengrajin berpengalaman puluhan tahun.'],
            ['icon' => 'fa-rotate-left', 'title' => 'Garansi Retur 30 Hari', 'desc' => 'Tidak sesuai ekspektasi? Kami jemput dan proses pengembalian tanpa ribet.'],
        ],
        'stat_value' => '15 thn',
        'stat_label' => 'Penghargaan Karya Terbaik',
        'image_path_besar' => null,
        'image_path_kecil' => null,
        'stat_bg_image' => null,
    ]);

    $missionBgColor = $missionSection['bg_color'] ?: '#FEEDD8';

    /**
     * Warna judul/paragraf/ikon/kartu angka TIDAK diatur manual -- dihitung
     * otomatis dari kontras warna latar ($missionBgColor), pola sama dengan
     * $contrastTextColors di partials/frontend/hero.blade.php. Warna latar
     * bawaan (belum diganti admin, #FEEDD8) selalu menghasilkan palet
     * default toko persis seperti sebelum section ini bisa diedit.
     */
    $contrastMissionColors = function (string $hex): array {
        $hex = ltrim($hex, '#');

        if (strtoupper($hex) === 'FEEDD8') {
            return [
                'heading' => '#4B3A26',
                'body' => '#756A5D',
                'accentBg' => '#CDA97E',
                'accentText' => '#FFFFFF',
                'statBg' => '#C9A566',
                'statText' => '#FFFFFF',
            ];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $linearize($r) + 0.7152 * $linearize($g) + 0.0722 * $linearize($b);

        $textColors = $luminance > 0.5
            ? ['heading' => '#1A1208', 'body' => 'rgba(26, 18, 8, 0.72)']
            : ['heading' => '#FFFFFF', 'body' => 'rgba(255, 255, 255, 0.78)'];

        // Ikon & kartu angka selalu memakai warna latar yang digelapkan --
        // supaya kontras dengan teks putih tetap aman untuk latar apa pun.
        return array_merge($textColors, [
            'accentBg' => "color-mix(in oklab, #{$hex} 55%, black 30%)",
            'accentText' => '#FFFFFF',
            'statBg' => "color-mix(in oklab, #{$hex} 45%, black 40%)",
            'statText' => '#FFFFFF',
        ]);
    };

    $missionColors = $contrastMissionColors($missionBgColor);

    $missionImageBesarUrl = $missionSection['image_path_besar']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($missionSection['image_path_besar'])
        : 'https://images.pexels.com/photos/28513061/pexels-photo-28513061.jpeg?auto=compress&cs=tinysrgb&w=1200';

    $missionImageKecilUrl = $missionSection['image_path_kecil']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($missionSection['image_path_kecil'])
        : 'https://images.pexels.com/photos/82256/pexels-photo-82256.jpeg?auto=compress&cs=tinysrgb&w=800';

    // Foto latar kartu angka penghargaan -- OPSIONAL. Kalau admin belum pernah
    // upload, kartu tetap pakai warna solid ($missionColors['statBg']) persis
    // seperti sebelum fitur ini ada (lihat null-check di kelas section KIRI).
    $missionStatBgUrl = $missionSection['stat_bg_image']
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($missionSection['stat_bg_image'])
        : null;
@endphp

<section
    class="relative overflow-hidden"
    style="background: linear-gradient(135deg, color-mix(in oklab, {{ $missionBgColor }} 100%, white 10%) 0%, {{ $missionBgColor }} 55%, color-mix(in oklab, {{ $missionBgColor }} 100%, black 14%) 100%);"
>
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-14 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-20">

        {{-- ============ KIRI: Kolase foto + kartu statistik ============ --}}
        <div class="grid grid-cols-3 gap-4">
            <img
                src="{{ $missionImageBesarUrl }}"
                alt="Proses pembuatan furnitur {{ $missionSetting->site_name }}"
                class="col-span-2 aspect-3/4 w-full rounded-2xl object-cover shadow-lg"
            >
            <div class="col-span-1 flex flex-col gap-4">
                <img
                    src="{{ $missionImageKecilUrl }}"
                    alt="Detail material furnitur {{ $missionSetting->site_name }}"
                    class="aspect-square w-full rounded-2xl object-cover shadow-lg"
                >
                <div
                    class="flex flex-1 flex-col justify-center rounded-2xl p-4 shadow-lg"
                    style="color: {{ $missionColors['statText'] }}; {{ $missionStatBgUrl ? 'background: linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url(\''.$missionStatBgUrl.'\') center/cover no-repeat;' : 'background: '.$missionColors['statBg'].';' }}"
                >
                    <p class="font-display text-2xl leading-none sm:text-3xl">{{ $missionSection['stat_value'] }}</p>
                    <p class="mt-2 text-[10px] font-semibold uppercase leading-snug tracking-wide opacity-80 sm:text-[11px]">
                        {{ $missionSection['stat_label'] }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ============ KANAN: Teks profil ============ --}}
        <div>
            <h2 class="font-display text-2xl leading-tight sm:text-3xl lg:text-4xl" style="color: {{ $missionColors['heading'] }};">
                {{ $missionSection['title'] }}
            </h2>
            <p class="mt-5 max-w-lg text-sm leading-relaxed" style="color: {{ $missionColors['body'] }};">
                {{ $missionSection['description'] }}
            </p>

            {{-- 3 poin unggulan --}}
            <ul class="mt-7 space-y-5">
                @foreach ($missionSection['points'] as $point)
                    <li class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" style="background: {{ $missionColors['accentBg'] }}; color: {{ $missionColors['accentText'] }};">
                            <i class="fa-solid {{ $point['icon'] }} text-sm"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold" style="color: {{ $missionColors['heading'] }};">{{ $point['title'] }}</p>
                            <p class="mt-0.5 text-sm" style="color: {{ $missionColors['body'] }};">{{ $point['desc'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8 flex justify-end">
                <a
                    href="{{ route('profile.index') }}"
                    class="group inline-flex items-center gap-2 border-b pb-0.5 text-sm font-medium transition-colors duration-300 hover:border-admin-accent hover:text-admin-accent"
                    style="color: {{ $missionColors['heading'] }}; border-color: color-mix(in oklab, {{ $missionColors['heading'] }} 30%, transparent);"
                >
                    Lihat Tentang Kami
                    <x-icon-arrow direction="right" class="transition-transform duration-300 group-hover:translate-x-1" />
                </a>
            </div>
        </div>
    </div>
</section>