/**
 * ==========================================================
 * Reveal galeri foto/video halaman Dokumentasi (/dokumentasi)
 * ==========================================================
 * Dipakai di resources/views/pages/frontend/booking.blade.php,
 * bagian "GALERI DOKUMENTASI" (di bawah hero). Pola IntersectionObserver-nya
 * MENIRU dokumentasi-video.js, tapi lebih sederhana: begitu satu kartu
 * galeri masuk ke layar (± 25% terlihat), kartu itu "masuk" (geser +
 * memudar) satu kali saja dan tidak pernah balik lagi -- beda dengan
 * video hero yang play/pause berulang, di sini efeknya sekali jalan
 * seperti membuka halaman katalog di galeri fisik.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('dokumentasiGaleriItem', () => ({
        masuk: false,

        init() {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this.masuk = true;
                    observer.unobserve(this.$el);
                }
            }, { threshold: 0.25 });

            observer.observe(this.$el);
        },
    }));
});
