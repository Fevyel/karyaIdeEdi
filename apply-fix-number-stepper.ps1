# ============================================================
# FIX: Tombol naik/turun di input angka (Harga, Stok, Berat, dst)
# tidak merespons klik.
#
# Penyebab: Alpine.data('numberStepper', ...) didaftarkan lewat tag
# <script> biasa di admin-panel.blade.php, di bagian BAWAH <body>.
# Alpine sendiri dibundel di dalam livewire.js dan bisa langsung
# Alpine.start() sebelum tag <script> itu sempat jalan -- begitu itu
# terjadi, x-data="numberStepper()" gagal diam-diam dan tombol jadi
# tidak merespons klik sama sekali (tanpa error yang kelihatan di UI).
#
# Fix: definisi numberStepper dipindah ke resources/js/app.js (di-
# bundle Vite, dimuat di <head>, urutannya terjamin), didaftarkan
# lewat event 'alpine:init' -- pola resmi yang direkomendasikan
# Livewire untuk komponen Alpine kustom.
#
# File ditulis pakai [System.IO.File]::WriteAllText(..., $false) --
# TIDAK memakai Set-Content -Encoding UTF8, supaya tidak kepasang BOM
# (itu penyebab error "namespace declaration" kemarin).
#
# Cara pakai: jalankan dari folder project, lalu build ulang asset JS.
#   .\apply-fix-number-stepper.ps1
#   npm run build        (atau restart 'npm run dev' kalau lagi jalan)
# ============================================================

$ErrorActionPreference = "Stop"

function Write-Utf8NoBom {
    param([string]$Path, [string]$Content)
    $fullPath = Join-Path (Get-Location) $Path
    [System.IO.File]::WriteAllText($fullPath, $Content, (New-Object System.Text.UTF8Encoding($false)))
}

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Fix: Tombol naik/turun input angka" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] Jalankan script ini dari folder project (yang ada file 'artisan')." -ForegroundColor Red
    exit 1
}

$appJs = @'
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
 * ADMIN NUMBER STEPPER (tombol naik/turun kustom)
 * ==========================================================
 * Dipakai bareng <x-icon-arrow> untuk tombol naik/turun di setiap
 * <input type="number"> (gantinya spinner bawaan browser yang
 * disembunyikan lewat CSS di app.css). Lihat pemakaiannya di
 * produk-form.blade.php & kategori-form.blade.php.
 *
 * SENGAJA didaftarkan di sini (dibundel Vite, dimuat di <head>),
 * BUKAN lewat tag <script> biasa di admin-panel.blade.php. Alpine
 * ikut dibundel di dalam livewire.js dan Alpine.start() bisa saja
 * sudah kepanggil duluan sebelum tag <script> di body sempat
 * mendaftarkan Alpine.data() — begitu itu terjadi, x-data="numberStepper()"
 * gagal diam-diam (Alpine tidak menemukan komponennya) dan tombol
 * naik/turun jadi tidak merespons klik sama sekali. Mendaftarkan
 * lewat event 'alpine:init' di file yang dimuat awal seperti ini
 * adalah pola resmi yang direkomendasikan Livewire, aman dari race
 * condition tersebut.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('numberStepper', () => ({
        step(delta) {
            const el = this.$refs.numInput;
            const stepAttr = parseFloat(el.step) || 1;
            const min = el.min !== '' ? parseFloat(el.min) : -Infinity;
            const max = el.max !== '' ? parseFloat(el.max) : Infinity;
            let current = parseFloat(el.value);
            if (isNaN(current)) current = 0;

            const next = Math.min(max, Math.max(min, current + delta * stepAttr));
            const decimals = (stepAttr.toString().split('.')[1] || '').length;
            el.value = decimals ? next.toFixed(decimals) : String(next);

            el.dispatchEvent(new Event('input', { bubbles: true }));
        },
    }));
});

/**
 * ==========================================================
 * ADMIN NUMBER INPUT UX
 * ==========================================================
 *
 * Goal:
 * - A create-form number field whose initial value is the default
 *   "0" must let the first typed digit replace that 0.
 * - Existing database values must remain editable normally.
 * - The behavior must survive Livewire re-renders/navigation.
 *
 * Why focus/select alone is NOT enough:
 * Livewire can re-render/restore an input between focus and the
 * browser's next input event. That can make the selection disappear,
 * producing "09764" even though input.select() was called.
 *
 * Therefore the actual replacement is enforced during beforeinput/keydown
 * capture, immediately before the browser inserts the user's first digit.
 * The native input event then fires with the clean value, so Livewire
 * receives "9764", not "09764".
 *
 * data-zero-replace="true" is intentionally explicit. This lets the
 * server distinguish a create-form default 0 from a real database value
 * of 0 on an edit form. Do NOT infer this from value === "0" alone.
 * ==========================================================
 */
const adminNumberSelector = 'input[type="number"][data-zero-replace="true"]';

function isDefaultZeroNumberInput(input) {
    return input instanceof HTMLInputElement
        && input.matches(adminNumberSelector)
        && input.value === '0';
}

// Visual/UX behavior: select the default zero when the field is focused.
document.addEventListener('focusin', (event) => {
    if (isDefaultZeroNumberInput(event.target)) {
        event.target.select();
    }
});

// Reliability behavior: handle the user's actual text insertion BEFORE
// the browser applies it. This is more reliable than focus/select alone
// because Livewire can re-render an input between focus and key input.
document.addEventListener('beforeinput', (event) => {
    const input = event.target;

    if (!isDefaultZeroNumberInput(input)) {
        return;
    }

    // A normal typed digit (1-9) replaces the default zero.
    if (event.inputType === 'insertText' && /^[1-9]$/.test(event.data ?? '')) {
        input.value = '';
        return;
    }

    // Pasting or other text insertion replaces the default zero as a whole.
    if (
        (event.inputType === 'insertFromPaste' || event.inputType === 'insertReplacementText')
        && event.data
    ) {
        input.value = '';
    }
}, true);

// Keyboard fallback for browsers that do not expose useful beforeinput
// data for <input type="number">. It only handles 1-9, so decimal entry
// such as 0.5 remains possible and is not accidentally converted to 5.
document.addEventListener('keydown', (event) => {
    const input = event.target;

    if (!isDefaultZeroNumberInput(input) || event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    if (/^[1-9]$/.test(event.key)) {
        input.value = '';
    }
}, true);

// Paste safety: select the zero immediately before paste so native paste
// replaces it. This also covers browsers whose paste does not provide
// beforeinput.data for number inputs.
document.addEventListener('paste', (event) => {
    if (isDefaultZeroNumberInput(event.target)) {
        event.target.select();
    }
}, true);


/**
 * ==========================================================
 * FRONTEND PRODUCT COLLECTION — FAVORIT & KERANJANG
 * ==========================================================
 * Data disimpan di localStorage agar pengunjung tidak perlu login.
 * Tidak mengubah database, Transaction, antrean, atau sistem booking.
 */
(() => {
    const FAVORITES_KEY = 'kie_favorites_v1';
    const CART_KEY = 'kie_cart_v1';

    const read = (key) => {
        try {
            const value = JSON.parse(localStorage.getItem(key) || '[]');
            return Array.isArray(value) ? value : [];
        } catch (_) {
            return [];
        }
    };

    const write = (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (_) {
            // Storage bisa dibatasi browser; UI tetap berjalan tanpa persistence.
        }
    };

    const readFavorites = () => read(FAVORITES_KEY);

    const productFrom = (el) => ({
        id: Number(el.dataset.productId),
        slug: el.dataset.productSlug || '',
        name: el.dataset.productName || 'Produk',
        price: Number(el.dataset.productPrice || 0),
        image: el.dataset.productImage || '',
        category: el.dataset.productCategory || 'Furniture',
        stock: Math.max(0, Number(el.dataset.productStock || 0)),
    });

    const favoriteIds = () => new Set(read(FAVORITES_KEY).map((item) => Number(item.id)));

    // Badge Favorit & Keranjang DISAMAKAN: murni JUMLAH PRODUK yang sedang
    // ada di masing-masing daftar. TIDAK ada konsep "sudah dilihat/belum" —
    // tanda ini tetap tampil walaupun halaman Favorit/Keranjang sudah
    // dibuka, dan hanya berkurang kalau produknya benar-benar hilang dari
    // daftar (Favorit: aksi manual. Keranjang: menunggu konfirmasi admin —
    // lihat catatan di data-cart-whatsapp).
    const syncBadges = () => {
        const favoriteCount = readFavorites().length;

        const cart = read(CART_KEY);
        const cartCount = cart.reduce(
            (sum, item) => sum + Math.max(0, Number(item.quantity || 0)),
            0
        );

        document.querySelectorAll('[data-favorite-count]').forEach((el) => {
            el.textContent = String(favoriteCount);

            el.classList.toggle('hidden', favoriteCount === 0);
            el.classList.toggle('flex', favoriteCount > 0);
        });

        document.querySelectorAll('[data-cart-count]').forEach((el) => {
            el.textContent = String(cartCount);

            el.classList.toggle('hidden', cartCount === 0);
            el.classList.toggle('flex', cartCount > 0);
        });
    };

    const syncFavoriteButtons = () => {
        const ids = favoriteIds();
        document.querySelectorAll('[data-favorite-product]').forEach((button) => {
            const active = ids.has(Number(button.dataset.productId));
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.classList.toggle('text-[#B65C4A]', active);
            button.classList.toggle('text-[#6E6257]', !active);
            const icon = button.querySelector('[data-favorite-icon]');
            if (icon) {
                icon.classList.toggle('fa-solid', active);
                icon.classList.toggle('fa-regular', !active);
            }
            const label = button.querySelector('[data-favorite-label]');
            if (label) label.textContent = active ? 'Tersimpan di Favorit' : 'Simpan ke Favorit';
        });
    };

    const notify = (message) => {
        const existing = document.querySelector('[data-store-toast]');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.dataset.storeToast = 'true';
        toast.className = 'fixed bottom-5 right-5 z-[9999] max-w-[320px] rounded-xl bg-[#2A211B] px-4 py-3 text-xs font-medium text-white shadow-2xl';
        toast.textContent = message;
        document.body.appendChild(toast);
        window.setTimeout(() => toast.remove(), 2200);
    };

    const toggleFavorite = (el) => {
        const product = productFrom(el);
        let favorites = read(FAVORITES_KEY);
        const exists = favorites.some((item) => Number(item.id) === product.id);
        if (exists) {
            favorites = favorites.filter((item) => Number(item.id) !== product.id);
            notify('Produk dihapus dari Favorit.');
        } else {
            favorites.unshift({
                ...product,
                _addedAt: Date.now()
            });

            notify('Produk disimpan ke Favorit.');
        }
        write(FAVORITES_KEY, favorites.slice(0, 100));
        syncBadges();
        syncFavoriteButtons();
        if (document.querySelector('[data-store-page="favorites"]')) renderFavorites();
    };

    const addCart = (el) => {
        const product = productFrom(el);
        if (!product.id || product.stock < 1) {
            notify('Produk sedang tidak tersedia.');
            return;
        }
        const quantitySource = el.dataset.cartQuantitySource
            ? document.querySelector(`[${el.dataset.cartQuantitySource}] [data-quantity-display]`)
            : null;
        const requested = Math.max(1, Number(quantitySource?.textContent || 1));
        const cart = read(CART_KEY);
        const existing = cart.find((item) => Number(item.id) === product.id);
        if (existing) {
            existing.quantity = Math.min(product.stock, Number(existing.quantity || 0) + requested);
            Object.assign(existing, product);
        } else {
            cart.unshift({ ...product, quantity: Math.min(product.stock, requested) });
        }
        write(CART_KEY, cart.slice(0, 50));

        syncBadges();

        notify(
            existing
                ? 'Jumlah produk di keranjang diperbarui.'
                : 'Produk masuk ke keranjang.'
        );
        if (document.querySelector('[data-store-page="cart"]')) renderCart();
    };

    const money = (value) => `Rp${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
    const imageMarkup = (item) => item.image
        ? `<img src="${item.image}" alt="${escapeHtml(item.name)}" class="h-full w-full object-contain p-5">`
        : '<div class="flex h-full w-full items-center justify-center text-[#C8BAA9]"><i class="fa-solid fa-couch text-3xl"></i></div>';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));

    function renderFavorites() {
        const list = document.querySelector('[data-favorites-list]');
        const empty = document.querySelector('[data-favorites-empty]');
        if (!list || !empty) return;
        const favorites = read(FAVORITES_KEY);
        empty.classList.toggle('hidden', favorites.length > 0);
        list.innerHTML = favorites.length ? `<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">${favorites.map((item) => `
            <article class="group overflow-hidden rounded-[22px] border border-[#E7DED2] bg-white shadow-[0_18px_60px_-35px_rgba(42,33,27,.25)]">
                <div class="relative aspect-square bg-[#F5F5F1]">${imageMarkup(item)}
                    <button type="button" data-remove-favorite="${Number(item.id)}" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-[#B65C4A] shadow-sm hover:bg-[#B65C4A] hover:text-white"><i class="fa-solid fa-heart text-xs"></i></button>
                </div>
                <div class="p-4">
                    <p class="text-[9px] font-semibold uppercase tracking-[.16em] text-admin-accent">${escapeHtml(item.category)}</p>
                    <a href="/produk/${encodeURIComponent(item.slug)}" class="mt-1 block text-sm font-semibold text-[#2A211B] hover:text-admin-accent">${escapeHtml(item.name)}</a>
                    <p class="mt-2 text-sm font-semibold text-[#2A211B]">${money(item.price)}</p>
                    <div class="mt-4 flex gap-2">
                        <button type="button" data-readd-cart-id="${Number(item.id)}" class="flex-1 rounded-md bg-[#2A211B] px-3 py-2.5 text-[10px] font-semibold text-white hover:bg-[#403129]">Ke Keranjang</button>
                        <a href="/produk/${encodeURIComponent(item.slug)}" class="flex h-9 w-10 items-center justify-center rounded-md border border-[#E3DED7] text-[#5C5147] hover:border-[#C7A16D] hover:text-admin-accent" aria-label="Lihat produk"><i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a>
                    </div>
                </div>
            </article>`).join('')}</div>` : '';
        list.querySelectorAll('[data-remove-favorite]').forEach((button) => button.addEventListener('click', () => {
            const id = Number(button.dataset.removeFavorite);
            write(FAVORITES_KEY, read(FAVORITES_KEY).filter((item) => Number(item.id) !== id));
            syncBadges(); renderFavorites();
        }));
        list.querySelectorAll('[data-readd-cart-id]').forEach((button) => button.addEventListener('click', () => {
            const id = Number(button.dataset.readdCartId);
            const item = read(FAVORITES_KEY).find((entry) => Number(entry.id) === id);
            if (!item || item.stock < 1) return notify('Produk sedang tidak tersedia.');

            const cart = read(CART_KEY);
            const existing = cart.find((entry) => Number(entry.id) === id);
            if (existing) existing.quantity = Math.min(item.stock, Number(existing.quantity || 0) + 1);
            else cart.unshift({ ...item, quantity: 1 });
            write(CART_KEY, cart.slice(0, 50));

            // Produk berpindah ke Keranjang = hilang dari Favorit (bukan disalin).
            write(FAVORITES_KEY, read(FAVORITES_KEY).filter((entry) => Number(entry.id) !== id));

            syncBadges();
            syncFavoriteButtons();
            renderFavorites();

            notify('Produk dipindahkan ke keranjang.');
        }));
    }

    function renderCart() {
        const list = document.querySelector('[data-cart-list]');
        const empty = document.querySelector('[data-cart-empty]');
        if (!list || !empty) return;
        let cart = read(CART_KEY).map((item) => ({ ...item, quantity: Math.max(1, Math.min(Number(item.stock || 0), Number(item.quantity || 1))) })).filter((item) => item.stock > 0);
        write(CART_KEY, cart);
        empty.classList.toggle('hidden', cart.length > 0);
        if (!cart.length) { list.innerHTML = ''; syncBadges(); return; }
        const total = cart.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.quantity || 1), 0);
        list.innerHTML = `<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-3">${cart.map((item) => `
                <article class="flex gap-4 rounded-[20px] border border-[#E7DED2] bg-white p-3 sm:p-4">
                    <a href="/produk/${encodeURIComponent(item.slug)}" class="h-28 w-28 shrink-0 overflow-hidden rounded-xl bg-[#F5F5F1] sm:h-32 sm:w-32">${imageMarkup(item)}</a>
                    <div class="min-w-0 flex-1 py-1">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-[9px] font-semibold uppercase tracking-[.16em] text-admin-accent">${escapeHtml(item.category)}</p><a href="/produk/${encodeURIComponent(item.slug)}" class="mt-1 block truncate text-sm font-semibold text-[#2A211B] hover:text-admin-accent">${escapeHtml(item.name)}</a></div>
                            <button type="button" data-cart-remove="${Number(item.id)}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[#9A8E82] hover:bg-[#F8EEE5] hover:text-[#B65C4A]" aria-label="Hapus dari keranjang"><i class="fa-solid fa-trash-can text-[10px]"></i></button>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="inline-flex h-9 items-center overflow-hidden rounded-md border border-[#E3DED7]"><button type="button" data-cart-minus="${Number(item.id)}" class="flex h-full w-8 items-center justify-center text-[#7A6E63] hover:bg-[#F8F4EF]">−</button><span class="flex w-8 justify-center text-xs font-semibold">${Number(item.quantity)}</span><button type="button" data-cart-plus="${Number(item.id)}" class="flex h-full w-8 items-center justify-center text-[#7A6E63] hover:bg-[#F8F4EF]">+</button></div>
                            <p class="text-sm font-semibold text-[#2A211B]">${money(Number(item.price) * Number(item.quantity))}</p>
                        </div>
                    </div>
                </article>`).join('')}</div>
            <aside class="h-fit rounded-[22px] border border-[#E7DED2] bg-[#2A211B] p-6 text-white shadow-[0_24px_70px_-35px_rgba(42,33,27,.65)]">
                <p class="text-[9px] font-semibold uppercase tracking-[.2em] text-[#D7B77D]">Ringkasan Pilihan</p>
                <div class="mt-5 flex items-center justify-between border-b border-white/10 pb-4 text-xs"><span class="text-white/55">Jumlah item</span><span>${cart.reduce((sum, item) => sum + Number(item.quantity), 0)}</span></div>
                <div class="mt-4 flex items-end justify-between"><span class="text-xs text-white/55">Perkiraan nilai</span><span class="text-lg font-semibold">${money(total)}</span></div>
                <p class="mt-4 text-[10px] leading-5 text-white/45">Harga dan ketersediaan tetap dikonfirmasi oleh admin saat konsultasi.</p>
                <button type="button" data-cart-whatsapp class="mt-6 flex w-full items-center justify-center gap-2 rounded-md bg-[#58B13F] px-4 py-3 text-[11px] font-semibold text-white hover:bg-[#489C32]"><i class="fa-brands fa-whatsapp"></i> Tanya Admin via WhatsApp</button>
                <button type="button" data-cart-clear class="mt-2 w-full py-2 text-[10px] font-medium text-white/45 hover:text-white">Kosongkan keranjang</button>
            </aside>
        </div>`;
        list.querySelectorAll('[data-cart-remove]').forEach((b) => b.addEventListener('click', () => { write(CART_KEY, read(CART_KEY).filter((i) => Number(i.id) !== Number(b.dataset.cartRemove))); syncBadges(); renderCart(); }));
        list.querySelectorAll('[data-cart-minus]').forEach((b) => b.addEventListener('click', () => updateCartQty(Number(b.dataset.cartMinus), -1)));
        list.querySelectorAll('[data-cart-plus]').forEach((b) => b.addEventListener('click', () => updateCartQty(Number(b.dataset.cartPlus), 1)));
        list.querySelector('[data-cart-clear]')?.addEventListener('click', () => { write(CART_KEY, []); syncBadges(); renderCart(); });
        list.querySelector('[data-cart-whatsapp]')?.addEventListener('click', () => {
            const number = document.querySelector('[data-store-page="cart"]')?.dataset.waNumber || '';

            if (!number) {
                return notify('Nomor WhatsApp admin belum diatur.');
            }

            const lines = cart.map(
                (item, index) => `${index + 1}. ${item.name} x${item.quantity}`
            );

            const text = `Halo Admin Karya Ide Edi, saya ingin berkonsultasi mengenai produk berikut:\n\n${lines.join('\n')}\n\nPerkiraan nilai: ${money(total)}\n\nMohon dibantu cek ketersediaan dan proses selanjutnya.`;

            window.open(
                `https://wa.me/${number}?text=${encodeURIComponent(text)}`,
                '_blank',
                'noopener'
            );

            // Tidak ada sistem akun, jadi tidak ada cara pasti mengetahui
            // "pesanan ini milik siapa" dari sisi web setelah pembeli pindah
            // ke aplikasi WhatsApp. Maka klik tombol ini SENDIRI dianggap
            // sebagai tanda "sudah dipesan" → Keranjang langsung dikosongkan.
            write(CART_KEY, []);
            syncBadges();
            renderCart();

            notify('Pesanan terkirim ke Admin. Keranjang dikosongkan.');
        });
    }

    function updateCartQty(id, delta) {
        const cart = read(CART_KEY);
        const item = cart.find((entry) => Number(entry.id) === id);
        if (!item) return;
        item.quantity = Math.max(1, Math.min(Number(item.stock || 1), Number(item.quantity || 1) + delta));
        write(CART_KEY, cart); syncBadges(); renderCart();
    }

    document.addEventListener('click', (event) => {
        const favorite = event.target.closest('[data-favorite-product]');
        if (favorite) { event.preventDefault(); event.stopPropagation(); toggleFavorite(favorite); return; }
        const cart = event.target.closest('[data-cart-product]');
        if (cart) { event.preventDefault(); event.stopPropagation(); addCart(cart); }
    });

    window.addEventListener('storage', () => { syncBadges(); syncFavoriteButtons(); renderFavorites(); renderCart(); });
    document.addEventListener('DOMContentLoaded', () => {
        syncBadges();
        syncFavoriteButtons();
        renderFavorites();
        renderCart();
    });
})();

'@

Write-Host "[1/2] resources/js/app.js (tambah registrasi numberStepper)..." -ForegroundColor Yellow
Write-Utf8NoBom -Path "resources\js\app.js" -Content $appJs
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

$layout = @'
<!DOCTYPE html>
<html lang="id" data-theme="{{ auth()->user()->theme ?? 'glow' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Admin Panel' }} - Karya Ide Edi</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,600i,700|instrument-sans:400,500,600,700" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        @livewireStyles
    </head>
    <body
        x-data="{ sidebarOpen: false }"
        class="bg-admin-canvas font-sans text-admin-ink antialiased"
    >
        {{--
            Garis aksen emas: SENGAJA ditaruh di sini, sebagai anak langsung
            <body>, BUKAN di dalam <header>. <header> memakai backdrop-blur-xl,
            dan backdrop-filter membuat browser menganggap header sebagai
            containing block baru — akibatnya `fixed inset-x-0` jadi relatif
            terhadap header (tidak full-width, terpotong di sisi sidebar),
            bukan relatif terhadap viewport. Ditaruh di luar semua ancestor
            ber-filter/transform supaya `fixed` benar-benar relatif ke
            viewport dan membentang penuh dari ujung kiri ke ujung kanan
            browser, termasuk melewati area sidebar.
        --}}
        <div class="fixed inset-x-0 top-0 z-50 h-0.75 bg-linear-to-r from-admin-gold via-admin-accent to-admin-gold"></div>

        <div class="flex min-h-screen">

            {{-- ================= SIDEBAR ================= --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                class="fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 flex-col bg-admin-sidebar text-admin-sidebar-ink shadow-2xl shadow-black/30 transition-transform duration-300 lg:translate-x-0"
            >
                {{-- garis aksen emas tipis di tepi kanan sidebar --}}
                <div class="pointer-events-none absolute inset-y-0 right-0 w-px bg-linear-to-b from-transparent via-admin-gold/30 to-transparent"></div>

                {{-- logo lockup — sumber tunggal: partials.logo (Pengaturan > Identitas Website) --}}
                @php $siteSetting = \App\Models\Setting::current(); @endphp
                <div class="relative flex h-18 shrink-0 items-center gap-3 border-b border-admin-sidebar-border bg-admin-sidebar-ink/3 px-6">
                    @include('partials.logo', [
                        'boxSize' => 'h-11 w-11',
                        'rounded' => 'rounded-2xl',
                        'boxClass' => 'bg-linear-to-br from-admin-gold to-admin-accent shadow-lg shadow-black/30 ring-1 ring-admin-sidebar-ink/10',
                        'imgClass' => 'shadow-lg shadow-black/30 ring-1 ring-admin-sidebar-ink/10',
                        'iconClass' => 'text-lg text-admin-sidebar-ink',
                    ])
                    <div class="min-w-0">
                        <span class="block truncate font-display text-base font-semibold leading-tight text-admin-sidebar-ink">
                            {{ $siteSetting->site_name }}
                        </span>
                        <span class="block text-[10.5px] font-semibold uppercase tracking-[0.18em] text-admin-sidebar-ink/40">
                            Admin Panel
                        </span>
                    </div>
                </div>

                <div class="px-6 pt-5">
                    <span class="inline-flex items-center gap-2 rounded-full border border-admin-sidebar-border bg-admin-sidebar-ink/6 px-3 py-1.5 text-[10.5px] font-medium text-admin-sidebar-ink/50 shadow-inner shadow-black/20">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        </span>
                        Toko Aktif
                    </span>
                </div>

                <nav data-sidebar-scroll class="admin-scroll flex-1 overflow-y-auto px-5 py-6">
                    <p class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-admin-sidebar-ink/35">
                        Menu
                    </p>
                    <ul class="space-y-1.5">
                        @php
                            $navItem = fn (string $route, string $icon, string $label) => [
                                'route' => $route,
                                'icon' => $icon,
                                'label' => $label,
                                'active' => request()->routeIs($route),
                            ];
                            $mainNav = [
                                $navItem('admin.dashboard', 'fa-gauge', 'Dashboard'),
                                $navItem('admin.products', 'fa-couch', 'Produk'),
                                $navItem('admin.categories', 'fa-tags', 'Kategori'),
                                $navItem('admin.transactions', 'fa-receipt', 'Pesanan'),
                                $navItem('admin.transactions.history', 'fa-clock-rotate-left', 'History Pesanan'),
                                $navItem('admin.customers', 'fa-users', 'Pelanggan'),
                                $navItem('admin.reports', 'fa-chart-column', 'Laporan'),
                            ];
                        @endphp

                        <li>
                            <a
                                href="{{ route('home') }}"
                                class="group relative flex items-center gap-3 overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium text-admin-sidebar-ink/55 transition-all duration-200 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink"
                            >
                                <span class="absolute inset-y-0 left-0 w-0.75 scale-y-0 rounded-r-full bg-admin-gold transition-transform duration-200 group-hover:scale-y-100"></span>
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-admin-sidebar-ink/0 transition-colors duration-200 group-hover:bg-admin-sidebar-ink/8">
                                    <i class="fa-solid fa-house text-center text-[13px] text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80"></i>
                                </span>
                                Home
                            </a>
                        </li>

                        @foreach ($mainNav as $item)
                            <li>
                                <a
                                    href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                    wire:navigate
                                    class="group relative flex items-center justify-between overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium transition-all duration-200
                                        {{ $item['active']
                                            ? 'bg-linear-to-r from-admin-accent to-admin-accent-strong text-white shadow-md shadow-black/25'
                                            : 'text-admin-sidebar-ink/55 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink' }}"
                                >
                                    <span class="absolute inset-y-0 left-0 w-0.75 rounded-r-full bg-admin-gold transition-transform duration-200
                                        {{ $item['active'] ? 'scale-y-100' : 'scale-y-0 group-hover:scale-y-100' }}"></span>
                                    <span class="flex items-center gap-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors duration-200
                                            {{ $item['active'] ? 'bg-white/15' : 'bg-admin-sidebar-ink/0 group-hover:bg-admin-sidebar-ink/8' }}">
                                            <i class="fa-solid {{ $item['icon'] }} text-center text-[13px] {{ $item['active'] ? 'text-white' : 'text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80' }}"></i>
                                        </span>
                                        {{ $item['label'] }}
                                    </span>
                                    @if ($item['route'] === 'admin.dashboard')
                                        <livewire:admin.nav-badge type="dashboard" :active="$item['active']" :key="'nav-badge-dashboard'" />
                                    @elseif ($item['route'] === 'admin.transactions')
                                        <livewire:admin.nav-badge type="pesanan" :active="$item['active']" :key="'nav-badge-pesanan'" />
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 border-t border-admin-sidebar-border pt-6">
                    <p class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-admin-sidebar-ink/35">
                        Lainnya
                    </p>
                    <ul class="space-y-1.5">
                        @php
                            $otherNav = [
                                $navItem('admin.testimonials', 'fa-star', 'Testimoni'),
                                $navItem('admin.website-editor', 'fa-pen-to-square', 'Edit Web'),
                                $navItem('admin.settings', 'fa-gear', 'Pengaturan'),
                            ];
                            $isInteraksiActive = request()->routeIs('admin.interaksi');
                        @endphp

                        @foreach ($otherNav as $item)
                            <li>
                                <a
                                    href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                    wire:navigate
                                    class="group relative flex items-center gap-3 overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium transition-all duration-200
                                        {{ $item['active']
                                            ? 'bg-linear-to-r from-admin-accent to-admin-accent-strong text-white shadow-md shadow-black/25'
                                            : 'text-admin-sidebar-ink/55 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink' }}"
                                >
                                    <span class="absolute inset-y-0 left-0 w-0.75 rounded-r-full bg-admin-gold transition-transform duration-200
                                        {{ $item['active'] ? 'scale-y-100' : 'scale-y-0 group-hover:scale-y-100' }}"></span>
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors duration-200
                                        {{ $item['active'] ? 'bg-white/15' : 'bg-admin-sidebar-ink/0 group-hover:bg-admin-sidebar-ink/8' }}">
                                        <i class="fa-solid {{ $item['icon'] }} text-center text-[13px] {{ $item['active'] ? 'text-white' : 'text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80' }}"></i>
                                    </span>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach

                        {{-- Interaksi — moderasi komentar/testimoni pembeli --}}
                        <li>
                            <a
                                href="{{ route('admin.interaksi') }}"
                                wire:navigate
                                class="group relative flex items-center justify-between overflow-hidden rounded-xl px-3 py-2.75 text-sm font-medium transition-all duration-200
                                    {{ $isInteraksiActive
                                        ? 'bg-linear-to-r from-admin-accent to-admin-accent-strong text-white shadow-md shadow-black/25'
                                        : 'text-admin-sidebar-ink/55 hover:translate-x-0.5 hover:bg-admin-sidebar-ink/6 hover:text-admin-sidebar-ink' }}"
                            >
                                <span class="absolute inset-y-0 left-0 w-0.75 rounded-r-full bg-admin-gold transition-transform duration-200
                                    {{ $isInteraksiActive ? 'scale-y-100' : 'scale-y-0 group-hover:scale-y-100' }}"></span>
                                <span class="flex items-center gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors duration-200
                                        {{ $isInteraksiActive ? 'bg-white/15' : 'bg-admin-sidebar-ink/0 group-hover:bg-admin-sidebar-ink/8' }}">
                                        <i class="fa-solid fa-comments text-center text-[13px] {{ $isInteraksiActive ? 'text-white' : 'text-admin-sidebar-ink/40 group-hover:text-admin-sidebar-ink/80' }}"></i>
                                    </span>
                                    Interaksi
                                </span>
                                <livewire:admin.nav-badge type="interaksi" :active="$isInteraksiActive" :key="'nav-badge-interaksi'" />
                            </a>
                        </li>
                    </ul>
                    </div>
                </nav>

                <div class="border-t border-admin-sidebar-border bg-admin-sidebar-ink/3 p-5">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.75 text-sm font-medium text-admin-sidebar-ink/55 transition-all duration-200 hover:bg-red-500/10 hover:text-red-300"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-admin-sidebar-ink/0 transition-colors duration-200 group-hover:bg-red-500/15">
                                <x-icon-arrow direction="logout" class="text-center" />
                            </span>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            {{-- overlay mobile --}}
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm lg:hidden"
            ></div>

            {{-- ================= MAIN CONTENT ================= --}}
            <div class="flex min-h-screen flex-1 flex-col lg:ml-72">

                {{-- topbar --}}
                <header class="sticky top-0 z-20 border-b border-admin-border bg-admin-surface/90 shadow-sm shadow-black/3 backdrop-blur-xl">
                    <div class="flex h-18 shrink-0 items-center justify-between px-5 sm:px-8">
                        <div class="flex items-center gap-3.5">
                            <button
                                @click="sidebarOpen = true"
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-admin-ink-soft transition-colors duration-200 hover:bg-admin-cream hover:text-admin-ink lg:hidden"
                            >
                                <i class="fa-solid fa-bars"></i>
                            </button>
                            <div>
                                <h1 class="font-display text-base font-semibold leading-tight tracking-tight text-admin-ink sm:text-lg">
                                    {{ $title ?? 'Dashboard' }}
                                </h1>
                                <p class="hidden text-[11px] font-medium uppercase tracking-[0.08em] text-admin-ink-soft/80 sm:block">
                                    Karya Ide Edi &middot; Panel Admin
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3.5 sm:gap-5">
                            {{-- toggle tema Glow / Dark --}}
                            <button
                                type="button"
                                x-data="{ dark: {{ (auth()->user()->theme ?? 'glow') === 'dark' ? 'true' : 'false' }} }"
                                @click="
                                    dark = !dark;
                                    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'glow');
                                    fetch('{{ route('admin.theme.update') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json',
                                        },
                                        body: JSON.stringify({ theme: dark ? 'dark' : 'glow' }),
                                    });
                                "
                                class="relative flex h-8 w-15 shrink-0 items-center rounded-full border border-admin-border bg-admin-cream px-1 transition-all duration-300 ease-out hover:border-admin-accent/40"
                                title="Ganti tema Glow / Dark"
                            >
                                <i class="fa-solid fa-sun absolute left-1.5 text-[11px] text-admin-gold"></i>
                                <i class="fa-solid fa-moon absolute right-1.5 text-[11px] text-admin-ink-soft"></i>
                                <span
                                    class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full bg-admin-accent text-white shadow-md shadow-black/20 transition-transform duration-300"
                                    :class="dark ? 'translate-x-[1.85rem]' : 'translate-x-0'"
                                >
                                    <i class="fa-solid text-[10px]" :class="dark ? 'fa-moon' : 'fa-sun'"></i>
                                </span>
                            </button>

                            {{-- jam & tanggal --}}
                            <div
                                x-data="{ time: '' }"
                                x-init="
                                    const tick = () => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                                    tick();
                                    setInterval(tick, 1000);
                                "
                                class="hidden items-center gap-2.5 rounded-full border border-admin-border bg-admin-cream/60 px-3.5 py-2 text-xs font-medium text-admin-ink-soft md:flex"
                            >
                                <i class="fa-regular fa-clock text-admin-accent"></i>
                                <span x-text="time" class="font-medium tabular-nums text-admin-ink"></span>
                                <span class="text-admin-border">|</span>
                                <span>{{ now()->translatedFormat('l, d M Y') }}</span>
                            </div>

                            {{-- dropdown profil --}}
                            <div x-data="{ profileOpen: false }" class="relative">
                                <button
                                    @click="profileOpen = !profileOpen"
                                    @click.outside="profileOpen = false"
                                    class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-2 transition-colors duration-200 hover:bg-admin-cream"
                                >
                                    @if (auth()->user()?->fotoProfilUrl())
                                        <img
                                            src="{{ auth()->user()->fotoProfilUrl() }}"
                                            alt="Foto profil"
                                            class="h-9 w-9 rounded-full object-cover ring-2 ring-admin-cream"
                                        >
                                    @else
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-admin-panel text-sm font-semibold text-white ring-2 ring-admin-cream">
                                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                                        </span>
                                    @endif
                                    <span class="hidden text-left sm:block">
                                        <span class="block text-sm font-semibold leading-tight text-admin-ink">
                                            {{ auth()->user()->name ?? 'Admin' }}
                                        </span>
                                        <span class="block text-xs leading-tight text-admin-ink-soft">
                                            Pemilik Toko
                                        </span>
                                    </span>
                                    <x-icon-arrow direction="chevron-down" size="text-[10px]" class="hidden text-admin-ink-soft sm:block" />
                                </button>

                                <div
                                    x-show="profileOpen"
                                    x-cloak
                                    x-transition.origin.top.right
                                    class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-2xl border border-admin-border bg-admin-surface py-2 shadow-xl shadow-black/10"
                                >
                                    <div class="border-b border-admin-border bg-admin-cream/50 px-4 py-3">
                                        <p class="truncate text-sm font-semibold text-admin-ink">
                                            {{ auth()->user()->name ?? 'Admin' }}
                                        </p>
                                        <p class="truncate text-xs text-admin-ink-soft">
                                            {{ auth()->user()->email ?? '' }}
                                        </p>
                                    </div>

                                    <div class="my-1 border-t border-admin-border"></div>

                                    <form method="POST" action="{{ route('admin.logout') }}" class="p-1.5 pt-0">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-500 transition-all duration-150 hover:translate-x-0.5 hover:bg-red-500/10"
                                        >
                                            <x-icon-arrow direction="logout" class="w-4 text-center" />
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main wire:key="main-{{ request()->path() }}" class="flex-1 animate-fade-in-up px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts

        {{--
            Numeric-input UX is implemented globally in resources/js/app.js.
            Number fields that are allowed to replace a create-form default
            zero are explicitly marked with data-zero-replace="true".
            Existing database values on edit forms are not marked, so they
            keep normal editing behavior.
        --}}
        {{--
            BUG FIX: sidebar admin (menu Dashboard/Produk/Kategori/.../Interaksi)
            punya scroll sendiri (overflow-y-auto). Setiap kali pindah halaman
            lewat wire:navigate, seluruh <body> dimuat ulang dari server supaya
            badge notifikasi selalu segar — efek sampingnya, tanpa kode ini,
            posisi scroll sidebar ikut ke-reset ke paling atas setiap navigasi.

            Simpan posisi scroll sidebar sesaat SEBELUM navigasi dimulai
            (livewire:navigate), lalu kembalikan lagi begitu halaman baru
            selesai dimuat (livewire:navigated) — jadi sidebar terasa "diam"
            seperti dashboard modern (TikTok/Facebook/Discord), walau
            sebenarnya di-render ulang dari server tiap pindah menu.
        --}}
        <script>
            (function () {
                var STORAGE_KEY = 'adminSidebarScrollTop';

                document.addEventListener('livewire:navigate', function () {
                    var sidebar = document.querySelector('[data-sidebar-scroll]');
                    if (sidebar) {
                        sessionStorage.setItem(STORAGE_KEY, String(sidebar.scrollTop));
                    }
                });

                document.addEventListener('livewire:navigated', function () {
                    var sidebar = document.querySelector('[data-sidebar-scroll]');
                    var saved = sessionStorage.getItem(STORAGE_KEY);
                    if (sidebar && saved !== null) {
                        sidebar.scrollTop = parseInt(saved, 10);
                    }
                });
            })();
        </script>
    </body>
</html>
'@

Write-Host "[2/2] resources/views/layouts/admin-panel.blade.php (hapus script lama yang salah tempat)..." -ForegroundColor Yellow
Write-Utf8NoBom -Path "resources\views\layouts\admin-panel.blade.php" -Content $layout
Write-Host "      OK" -ForegroundColor Green
Write-Host ""

Write-Host "==============================================" -ForegroundColor Cyan
Write-Host " Selesai." -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host "Langkah selanjutnya (WAJIB, ini perubahan JS, bukan cuma PHP/Blade):" -ForegroundColor White
Write-Host "  - Kalau 'npm run dev' sedang jalan: cukup save, Vite auto-reload." -ForegroundColor White
Write-Host "  - Kalau pakai build production: jalankan 'npm run build'." -ForegroundColor White
Write-Host "Lalu refresh halaman Tambah/Edit Produk dan coba tombol naik/turunnya." -ForegroundColor White
