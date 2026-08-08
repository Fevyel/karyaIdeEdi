/**
 * ==========================================================
 * SortableJS (drag & drop kategori — Prioritas 2)
 * ==========================================================
 * Sebelumnya di-load lewat tag <script src="...cdnjs..."> yang
 * ditaruh langsung di kategori.blade.php. Tag <script> di dalam
 * root komponen Livewire memicu bug root-element-detection
 * (server 500 / MultipleRootElementsDetectedException), jadi
 * tag itu sudah dihapus (lihat komentar "PRIORITAS 1 FIX" di
 * kategori.blade.php).
 *
 * Ini "Prioritas 2": Sortable di-bundle lewat Vite (npm import)
 * di sini, bukan tag <script> runtime — jadi tidak menyentuh DOM
 * root Livewire sama sekali dan tidak memicu bug yang sama.
 *
 * Diekspos sebagai `window.Sortable` supaya kode Alpine di dalam
 * blok @script pada kategori.blade.php (yang memanggil
 * `new Sortable(el, {...})` sebagai variabel global, bukan lewat
 * import) tetap bisa memakainya tanpa perlu diubah strukturnya.
 */
import Sortable from 'sortablejs';

window.Sortable = Sortable;

/**
 * ==========================================================
 * UX FIX: input angka dengan nilai default "0" di panel admin
 * ==========================================================
 * Masalah: setiap field angka (harga, stok, berat, urutan
 * kategori, dll) defaultnya "0", jadi admin harus hapus "0"
 * itu dulu sebelum bisa mengetik angka yang sebenarnya —
 * kalau tidak, hasilnya jadi "05" alih-alih "5".
 *
 * Solusi: begitu field number difokus DAN nilainya masih
 * persis "0", teks di dalamnya otomatis diseleksi. Karena
 * teks terseleksi, ketikan pertama otomatis MENGGANTIKAN "0"
 * (perilaku standar browser untuk selection + keystroke),
 * bukan disisipkan di depannya.
 *
 * Field yang sudah berisi data asli dari database (misalnya
 * "1250000" atau "24") TIDAK ikut diseleksi — kondisinya cuma
 * berlaku kalau value === "0" persis — jadi proses edit data
 * yang sudah ada tetap seperti biasa (kursor normal, tidak ada
 * teks yang otomatis terhapus/terganti).
 *
 * Dipasang SEKALI lewat event delegation di document (bukan
 * per-elemen), jadi otomatis berlaku untuk SEMUA
 * <input type="number"> di seluruh panel admin — termasuk
 * input yang baru muncul lewat re-render Livewire, dan input
 * di halaman admin yang dibuat setelah file ini ditulis.
 * Tidak menyentuh value, tidak memicu event input/change,
 * jadi tidak memengaruhi validasi Laravel, wire:model, atau
 * cara datanya tersimpan ke database.
 * ==========================================================
 */
document.addEventListener('focusin', (event) => {
    const input = event.target;

    if (
        input.tagName === 'INPUT'
        && input.type === 'number'
        && input.value === '0'
    ) {
        input.select();
    }
});