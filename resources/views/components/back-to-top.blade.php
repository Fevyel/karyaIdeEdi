{{--
    ==========================================================
    <x-back-to-top /> — Tombol "kembali ke atas"
    ==========================================================
    Muncul mengambang di kanan-bawah HANYA setelah user scroll
    ke bawah (disembunyikan lewat opacity/translate saat masih
    di atas halaman), lalu klik untuk smooth-scroll ke atas.

    Dipakai global lewat 2 titik include supaya otomatis tampil
    di SEMUA halaman tanpa perlu ditambahkan satu-satu:
        - partials/frontend/footer.blade.php  -> semua halaman frontend
        - layouts/admin-panel.blade.php       -> semua halaman admin
          (kecuali layouts/admin.blade.php yang dipakai khusus
          halaman login, karena kontennya pendek & tidak discroll)

    Di frontend, tombol ini "berbagi tempat" (kanan-bawah) dengan
    <x-whatsapp-float />: begitu tombol ini muncul, WhatsApp float
    otomatis digeser naik supaya keduanya tetap kelihatan, lalu
    turun lagi begitu tombol ini disembunyikan (discroll ke atas
    ATAU diklik). Di admin, #whatsapp-float-btn memang tidak ada,
    jadi bagian itu otomatis dilewati (aman, tidak error).

    Script di bawah sengaja query ulang elemennya sendiri (bukan
    menyimpan reference di variabel luar) supaya tetap aman kalau
    dieksekusi ulang setiap kali admin pindah halaman lewat
    wire:navigate (lihat catatan di layouts/admin-panel.blade.php).
    ==========================================================
--}}
<button
    type="button"
    id="back-to-top-btn"
    aria-label="Kembali ke atas"
    title="Kembali ke atas"
    class="fixed bottom-6 right-6 z-50 flex h-11 w-11 translate-y-3 items-center justify-center rounded-full bg-[#2A211B] text-white opacity-0 shadow-lg pointer-events-none transition-all duration-300 ease-out hover:bg-admin-accent"
>
    <x-icon-arrow direction="up" size="text-sm" />
</button>

<script>
    (function () {
        var btn = document.getElementById('back-to-top-btn');
        if (!btn) {
            return;
        }

        var SHOW_AFTER_PX = 300;

        function toggleVisibility() {
            var scrolled = window.scrollY || document.documentElement.scrollTop;
            var isVisible = scrolled > SHOW_AFTER_PX;

            if (isVisible) {
                btn.classList.remove('opacity-0', 'translate-y-3', 'pointer-events-none');
            } else {
                btn.classList.add('opacity-0', 'translate-y-3', 'pointer-events-none');
            }

            // Kasih tempat ke tombol back-to-top ini: geser WhatsApp float
            // naik saat tombol ini tampil, turun lagi saat tombol ini sembunyi.
            // Query ulang tiap kali (bukan di-cache) -- aman walau elemennya
            // belum ada sama sekali (mis. di admin) atau baru muncul belakangan.
            var waBtn = document.getElementById('whatsapp-float-btn');
            if (waBtn) {
                if (isVisible) {
                    waBtn.classList.remove('bottom-6');
                    waBtn.classList.add('bottom-20');
                } else {
                    waBtn.classList.remove('bottom-20');
                    waBtn.classList.add('bottom-6');
                }
            }
        }

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', toggleVisibility, { passive: true });

        // Cek posisi scroll saat ini juga -- misal halaman dimuat
        // lewat wire:navigate dalam keadaan sudah tidak di paling atas.
        toggleVisibility();
    })();
</script>