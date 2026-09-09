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
