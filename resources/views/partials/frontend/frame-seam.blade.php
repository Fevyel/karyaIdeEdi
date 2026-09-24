{{--
    ==========================================================
    FRAME SEAM — transisi halus warna antar frame Admin > Edit Web
    ==========================================================
    Tiap frame yang bisa diedit di Edit Web (Header, Sejak Berdiri,
    Produk Unggulan, Kategori, Testimoni, Lokasi, Tentang Kami,
    Sejarah, Nilai Kami, Kenapa Pilih Kami, Our Craftsmen Hero, dst)
    adalah <section> sendiri dengan background-nya sendiri (warna
    polos ATAU gradasi -- lihat App\Support\FrameBackground). Kalau
    dua frame bertetangga warnanya beda, sambungannya jadi garis
    tegas/patah.

    Partial ini ditaruh PERSIS DI ANTARA dua @include section (bukan
    DI DALAM salah satunya) dan me-render satu strip tipis berisi
    gradasi (warna section ATAS -> warna section BAWAH). Warna &
    gradasi section itu sendiri SAMA SEKALI TIDAK diubah -- ini murni
    lapisan penghalus di sambungannya saja.

    CATATAN FIX #3 (versi non-overlap -- GANTI TOTAL cara kerja lama):
    Versi SEBELUMNYA strip ini sengaja ditumpuk DI ATAS (position:
    relative + z-index:1) dan menjorok masuk ke section atas/bawah
    lewat margin negatif, supaya tetap tampil biarpun overflow-hidden
    section-nya aktif (lihat riwayat commit/CATATAN FIX #1 & #2 versi
    lama). Masalahnya: z-index:1 bikin strip ini SELALU tampil DI ATAS
    konten section tetangga yang tidak positioned (kartu, shadow,
    teks) -- kalau section-nya TIPIS (mis. "Keunggulan" ±100px,
    sedangkan jorokan strip bisa sampai 140px di layar besar) atau ada
    elemen dekat tepi (shadow kartu di "Alur Booking", dll), strip ini
    literally MENUTUPI konten itu, bukan cuma menghaluskan warnanya.
    Ini kejadian di scope GLOBAL karena partial ini dipakai di 16+
    tempat berbeda dengan section yang tinggi & isinya macam-macam.

    Perbaikannya: strip ini SEKARANG elemen block BIASA (bukan lagi
    positioned, tidak ada z-index, tidak ada margin negatif) yang
    murni MENYISIP sebagai ruang sendiri di antara dua section --
    TIDAK PERNAH numpuk/menjorok ke section manapun, jadi TIDAK
    MUNGKIN lagi menutupi apa pun, setinggi/setipis apapun section
    tetangganya. Konsekuensinya: total tinggi halaman nambah sedikit
    (setinggi strip ini, bukan lagi 0 seperti versi overlap) -- ini
    trade-off yang sengaja diambil demi strip ini tidak pernah lagi
    menutupi konten section manapun. Karena bukan lagi anak dari
    section manapun (tetap sibling, ditaruh di antara dua @include),
    overflow-hidden section manapun tetap tidak memotongnya.

    Kenapa color-mix(in oklch, ...) per titik stop (bukan cuma
    linear-gradient RGB biasa): dua warna yang jauh beda -- misal
    coklat tua ke putih -- kalau dicampur linear di ruang warna RGB,
    titik tengahnya jatuh ke abu-abu/coklat pudar yang "kotor" dan
    keliatan kayak sambungan dipaksa/patah, bukan gradasi mulus.
    Mixing di ruang oklch mengikuti persepsi mata (lightness bergerak
    rata dari ujung ke ujung), jadi warnanya sendiri sudah benar.

    Kenapa easing SMOOTHSTEP juga dipakai (bukan cuma warnanya di-mix
    oklch lalu ditaruh di gradient linear polos): linear-gradient
    biasa punya KECEPATAN perubahan warna yang konstan dari ujung ke
    ujung strip. Section flat di atas & bawah strip ini kecepatan
    perubahannya 0 (warnanya diam). Jadi persis di kedua ujung strip
    ada PATAHAN KECEPATAN -- dari diam, tiba-tiba berubah dengan
    kecepatan konstan, lalu berhenti mendadak lagi. Mata manusia
    sensitif ke patahan kecepatan ini (bukan cuma ke warnanya sendiri
    yang sudah benar), makanya transisinya kerasa "maksa"/dipotong
    walau sudah pakai oklch. Kurva smoothstep (t' = t^2*(3-2t)) bikin
    kecepatan perubahannya 0 juga persis di kedua ujung strip, jadi
    transisinya "berangkat" dan "berhenti" landai, nyambung mulus ke
    section flat di atas & bawahnya -- baru di tengah strip warnanya
    berubah lebih cepat. Caranya: strip dipecah jadi beberapa titik
    stop (bukan cuma 2), tiap titik warnanya di-mix oklch dengan
    proporsi yang sudah lewat kurva smoothstep, bukan proporsi linear.

    Props:
      $from   -- hex warna dasar section DI ATAS seam ini (pakai
                 ['base'] dari App\Support\FrameBackground::resolve(),
                 supaya benar baik section itu lagi pakai gradasi
                 maupun warna polos/bawaan).
      $to     -- hex warna dasar section DI BAWAH seam ini.
      ($height -- SUDAH TIDAK DIPAKAI. Tinggi strip diatur lewat CSS
                 class .frame-seam-strip di bawah, responsif per
                 breakpoint.)

    Pemakaian -- taruh PERSIS di antara dua section, TIDAK di dalam
    keduanya (lihat home-placeholder.blade.php / profil.blade.php /
    pengrajin.blade.php untuk contoh lengkap):

        @include('partials.frontend.mission')
        @include('partials.frontend.frame-seam', ['from' => $seamMission, 'to' => $seamProduk])
        @include('partials.frontend.products')
    ==========================================================
--}}
@once
    <style>
        .frame-seam-strip {
            height: 20px;
        }
        @media (min-width: 640px) {
            .frame-seam-strip {
                height: 32px;
            }
        }
        @media (min-width: 1024px) {
            .frame-seam-strip {
                height: 48px;
            }
        }
    </style>
@endonce
@php
    // 10 segmen = 11 titik stop. Cukup rapat supaya pendekatan smoothstep-nya
    // halus, tanpa bikin panjang string CSS-nya berlebihan.
    $segments = 10;
    $stops = [];

    for ($i = 0; $i <= $segments; $i++) {
        $p = $i / $segments;
        $t = $p * $p * (3 - 2 * $p); // smoothstep(p): slope 0 di p=0 dan p=1
        $toPercent = round($t * 100, 2);
        $fromPercent = round(100 - $toPercent, 2);
        $position = round($p * 100, 2);

        $stops[] = "color-mix(in oklch, {$from} {$fromPercent}%, {$to} {$toPercent}%) {$position}%";
    }

    $gradient = 'linear-gradient(to bottom in oklch, '.implode(', ', $stops).')';
@endphp
<div
    class="frame-seam-strip pointer-events-none"
    style="background: {{ $gradient }};"
    aria-hidden="true"
></div>
