{{--
    ==========================================================
    <x-whatsapp-float :href="$waLink" /> — Tombol WhatsApp mengambang
    ==========================================================
    Khusus FRONTEND (jangan dipasang di layout admin) -- tampil
    di kanan-bawah SEJAK halaman pertama kali dibuka (beda dengan
    <x-back-to-top /> yang baru muncul setelah scroll).

    Supaya tidak tumpang tindih dengan tombol back-to-top yang
    muncul belakangan di titik yang sama (kanan-bawah), tombol ini
    otomatis "naik" memberi tempat begitu back-to-top muncul, lalu
    turun lagi ke posisi semula begitu back-to-top disembunyikan
    (baik karena discroll balik ke atas maupun diklik). Koordinasi
    posisi itu diatur dari script di back-to-top.blade.php (dia
    yang query #whatsapp-float-btn dan toggle class bottom-6/bottom-20
    di sini) -- jadi component ini WAJIB dirender SEBELUM
    <x-back-to-top /> di halaman supaya elemen ini sudah ada di DOM
    saat scriptnya jalan.

    Kalau nomor WhatsApp belum diisi admin di menu Pengaturan,
    tombol ini tidak dirender sama sekali (bukan tombol mati/disabled).

    Props:
        href (string|null, wajib) — link wa.me yang sudah jadi,
        biasanya dikirim dari variabel yang sama dipakai footer
        (mis. $footerWaLink).
    ==========================================================
--}}
@if ($href)
    <a
        href="{{ $href }}"
        target="_blank"
        rel="noopener"
        id="whatsapp-float-btn"
        aria-label="Konsultasi via WhatsApp"
        title="Konsultasi via WhatsApp"
        class="fixed bottom-6 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-[#58B13F] text-white shadow-lg transition-all duration-300 ease-out hover:bg-[#489C32]"
    >
        <i class="fa-brands fa-whatsapp text-lg"></i>
    </a>
@endif