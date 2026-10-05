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

    // KIE_SHOPEE_CART_V2
    const addCart = (el) => {
        const product = productFrom(el);

        if (!product.id || product.stock < 1) {
            notify('Produk sedang tidak tersedia.');
            return;
        }

        const cart = read(CART_KEY);
        const existing = cart.find(
            (item) => Number(item.id) === product.id
        );

        const quantitySource = el.dataset.cartQuantitySource
            ? document.querySelector(
                `[${el.dataset.cartQuantitySource}] [data-quantity-display]`
            )
            : null;

        const requested = Math.max(
            1,
            Number(quantitySource?.textContent || 1)
        );

        const maxQty = Math.max(
            1,
            Math.min(99, Number(product.stock || 1))
        );

        if (existing) {
            existing.quantity = Math.min(
                maxQty,
                Number(existing.quantity || 1) + requested
            );

            existing.selected = true;

            Object.assign(existing, product);

            write(CART_KEY, cart.slice(0, 50));
            syncBadges();

            notify('Jumlah produk di keranjang diperbarui.');

            if (document.querySelector('[data-store-page="cart"]')) {
                renderCart();
            }

            return;
        }

        cart.unshift({
            ...product,
            quantity: Math.min(maxQty, requested),
            selected: true,
        });

        write(CART_KEY, cart.slice(0, 50));

        syncBadges();
        notify('Produk masuk ke keranjang.');

        if (document.querySelector('[data-store-page="cart"]')) {
            renderCart();
        }
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
        list.innerHTML = favorites.length ? `<div data-kie-favorites-grid class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">${favorites.map((item) => `
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
            if (existing) {
                existing.quantity = Math.min(
                    99,
                    Number(item.stock || 99),
                    Number(existing.quantity || 0) + 1
                );

                existing.selected = true;
            } else {
                cart.unshift({
                    ...item,
                    quantity: 1,
                    selected: true
                });
            }
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

        let cart = read(CART_KEY)
            .map((item) => ({
                ...item,
                quantity: Math.max(
                    1,
                    Math.min(
                        99,
                        Number(item.stock || 99),
                        Number(item.quantity || 1)
                    )
                ),
                selected: item.selected !== false,
            }))
            .filter((item) => item.stock > 0);

        write(CART_KEY, cart);

        empty.classList.toggle('hidden', cart.length > 0);

        if (!cart.length) {
            list.innerHTML = '';
            syncBadges();
            return;
        }

        const selectedItems = cart.filter(
            (item) => item.selected !== false
        );

        const selectedQuantity = selectedItems.reduce(
            (sum, item) => sum + Number(item.quantity || 1),
            0
        );

        const selectedTotal = selectedItems.reduce(
            (sum, item) =>
                sum
                + Number(item.price || 0)
                * Number(item.quantity || 1),
            0
        );

        const allSelected =
            selectedItems.length === cart.length;

        list.innerHTML = `
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

                <section class="min-w-0">

                    <div class="mb-3 flex items-center justify-between gap-3 rounded-2xl border border-[#E7DED2] bg-white px-4 py-3 shadow-sm">
                        <label class="flex cursor-pointer items-center gap-3 text-xs font-semibold text-[#2A211B]">
                            <input
                                type="checkbox"
                                data-cart-select-all
                                ${allSelected ? 'checked' : ''}
                                class="h-4 w-4 rounded border-[#D8CBBB] accent-[#F28A22]"
                            >
                            <span>
                                Pilih Semua
                                <span class="font-normal text-[#9A8E82]">
                                    (${cart.length} produk)
                                </span>
                            </span>
                        </label>

                        <button
                            type="button"
                            data-cart-remove-selected
                            ${selectedItems.length ? '' : 'disabled'}
                            class="text-[10px] font-semibold text-[#B65C4A] transition hover:text-[#8F4234] disabled:cursor-not-allowed disabled:opacity-35"
                        >
                            Hapus yang dipilih
                        </button>
                    </div>

                    <div class="space-y-3">

                        ${cart.map((item) => `
                            <article class="grid grid-cols-[22px_92px_minmax(0,1fr)] gap-3 rounded-[20px] border border-[#E7DED2] bg-white p-3 shadow-sm sm:grid-cols-[24px_128px_minmax(0,1fr)] sm:gap-4 sm:p-4">

                                <label class="flex cursor-pointer items-start justify-center pt-2">
                                    <input
                                        type="checkbox"
                                        data-cart-select="${Number(item.id)}"
                                        ${item.selected !== false ? 'checked' : ''}
                                        class="h-4 w-4 rounded border-[#D8CBBB] accent-[#F28A22]"
                                    >
                                </label>

                                <a
                                    href="/produk/${encodeURIComponent(item.slug)}"
                                    class="aspect-square w-full overflow-hidden rounded-xl bg-[#F5F5F1]"
                                >
                                    ${imageMarkup(item)}
                                </a>

                                <div class="min-w-0 py-0.5">

                                    <div class="flex items-start justify-between gap-2">

                                        <div class="min-w-0">
                                            <p class="text-[8px] font-semibold uppercase tracking-[.16em] text-admin-accent sm:text-[9px]">
                                                ${escapeHtml(item.category)}
                                            </p>

                                            <a
                                                href="/produk/${encodeURIComponent(item.slug)}"
                                                class="mt-1 block line-clamp-2 text-xs font-semibold leading-5 text-[#2A211B] hover:text-admin-accent sm:text-sm"
                                            >
                                                ${escapeHtml(item.name)}
                                            </a>
                                        </div>

                                        <button
                                            type="button"
                                            data-cart-remove="${Number(item.id)}"
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[#9A8E82] transition hover:bg-[#F8EEE5] hover:text-[#B65C4A]"
                                            aria-label="Hapus dari keranjang"
                                        >
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>

                                    </div>

                                    <p class="mt-2 text-sm font-semibold text-[#F28A22]">
                                        ${money(item.price)}
                                    </p>

                                    <div class="mt-4 flex flex-wrap items-end justify-between gap-3">

                                        <div>
                                            <p class="mb-1.5 text-[9px] text-[#9A8E82]">
                                                Jumlah
                                            </p>

                                            <div class="inline-flex h-9 items-center overflow-hidden rounded-lg border border-[#E3DED7] bg-white">
                                                <button
                                                    type="button"
                                                    data-cart-minus="${Number(item.id)}"
                                                    class="flex h-full w-9 items-center justify-center text-[#7A6E63] transition hover:bg-[#F8F4EF]"
                                                    aria-label="Kurangi jumlah"
                                                >
                                                    <i class="fa-solid fa-minus text-[9px]"></i>
                                                </button>

                                                <span class="flex w-9 justify-center text-xs font-semibold">
                                                    ${Number(item.quantity)}
                                                </span>

                                                <button
                                                    type="button"
                                                    data-cart-plus="${Number(item.id)}"
                                                    class="flex h-full w-9 items-center justify-center text-[#7A6E63] transition hover:bg-[#F8F4EF]"
                                                    aria-label="Tambah jumlah"
                                                >
                                                    <i class="fa-solid fa-plus text-[9px]"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-[9px] text-[#9A8E82]">
                                                Subtotal
                                            </p>

                                            <p class="mt-1 text-sm font-semibold text-[#2A211B]">
                                                ${money(
                                                    Number(item.price)
                                                    * Number(item.quantity)
                                                )}
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </article>
                        `).join('')}

                    </div>
                </section>

                <aside class="hidden h-fit rounded-[22px] border border-[#E7DED2] bg-[#2A211B] p-6 text-white shadow-[0_24px_70px_-35px_rgba(42,33,27,.65)] lg:sticky lg:top-28 lg:block">

                    <p class="text-[9px] font-semibold uppercase tracking-[.2em] text-[#D7B77D]">
                        Ringkasan Belanja
                    </p>

                    <div class="mt-5 flex items-center justify-between border-b border-white/10 pb-4 text-xs">
                        <span class="text-white/55">
                            Produk dipilih
                        </span>
                        <span>${selectedItems.length}</span>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-b border-white/10 pb-4 text-xs">
                        <span class="text-white/55">
                            Jumlah barang
                        </span>
                        <span>${selectedQuantity}</span>
                    </div>

                    <div class="mt-5 flex items-end justify-between gap-3">
                        <span class="text-xs text-white/55">
                            Total
                        </span>

                        <span class="text-xl font-semibold">
                            ${money(selectedTotal)}
                        </span>
                    </div>

                    <p class="mt-4 text-[10px] leading-5 text-white/45">
                        Harga akhir dan ketersediaan akan dikonfirmasi admin melalui WhatsApp.
                    </p>

                    <button
                        type="button"
                        data-cart-whatsapp
                        ${selectedItems.length ? '' : 'disabled'}
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-[#58B13F] px-4 py-3 text-[11px] font-semibold text-white transition hover:bg-[#489C32] disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <i class="fa-brands fa-whatsapp"></i>
                        Pesan Produk Terpilih
                    </button>

                    <button
                        type="button"
                        data-cart-clear
                        class="mt-2 w-full py-2 text-[10px] font-medium text-white/45 hover:text-white"
                    >
                        Kosongkan keranjang
                    </button>

                </aside>
            </div>

            <div class="fixed bottom-3 left-3 right-3 z-40 flex items-center gap-3 rounded-2xl border border-[#E7DED2] bg-white/95 px-4 py-3 shadow-2xl backdrop-blur lg:hidden">

                <label class="flex shrink-0 cursor-pointer items-center gap-2 text-[10px] font-medium text-[#6F6358]">
                    <input
                        type="checkbox"
                        data-cart-select-all
                        ${allSelected ? 'checked' : ''}
                        class="h-4 w-4 rounded border-[#D8CBBB] accent-[#F28A22]"
                    >
                    Semua
                </label>

                <div class="ml-auto min-w-0 text-right">
                    <p class="text-[8px] uppercase tracking-wide text-[#9A8E82]">
                        Total
                    </p>

                    <p class="truncate text-sm font-bold text-[#F28A22]">
                        ${money(selectedTotal)}
                    </p>
                </div>

                <button
                    type="button"
                    data-cart-whatsapp
                    ${selectedItems.length ? '' : 'disabled'}
                    class="shrink-0 rounded-xl bg-[#F28A22] px-4 py-3 text-[10px] font-bold text-white transition hover:bg-[#DD7614] disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Pesan (${selectedItems.length})
                </button>

            </div>
        `;

        list.querySelectorAll('[data-cart-remove]').forEach(
            (button) => button.addEventListener('click', () => {
                write(
                    CART_KEY,
                    read(CART_KEY).filter(
                        (item) =>
                            Number(item.id)
                            !== Number(button.dataset.cartRemove)
                    )
                );

                syncBadges();
                renderCart();
            })
        );

        list.querySelectorAll('[data-cart-minus]').forEach(
            (button) => button.addEventListener(
                'click',
                () => updateCartQty(
                    Number(button.dataset.cartMinus),
                    -1
                )
            )
        );

        list.querySelectorAll('[data-cart-plus]').forEach(
            (button) => button.addEventListener(
                'click',
                () => updateCartQty(
                    Number(button.dataset.cartPlus),
                    1
                )
            )
        );

        list.querySelectorAll('[data-cart-select]').forEach(
            (checkbox) => checkbox.addEventListener(
                'change',
                () => {
                    const id = Number(
                        checkbox.dataset.cartSelect
                    );

                    const next = read(CART_KEY);

                    const item = next.find(
                        (entry) =>
                            Number(entry.id) === id
                    );

                    if (item) {
                        item.selected = checkbox.checked;
                    }

                    write(CART_KEY, next);
                    renderCart();
                }
            )
        );

        list.querySelectorAll('[data-cart-select-all]').forEach(
            (checkbox) => checkbox.addEventListener(
                'change',
                () => {
                    const checked = checkbox.checked;

                    write(
                        CART_KEY,
                        read(CART_KEY).map(
                            (item) => ({
                                ...item,
                                selected: checked
                            })
                        )
                    );

                    renderCart();
                }
            )
        );

        list.querySelector('[data-cart-remove-selected]')
            ?.addEventListener('click', () => {
                write(
                    CART_KEY,
                    read(CART_KEY).filter(
                        (item) =>
                            item.selected === false
                    )
                );

                syncBadges();
                renderCart();
            });

        list.querySelector('[data-cart-clear]')
            ?.addEventListener('click', () => {
                write(CART_KEY, []);
                syncBadges();
                renderCart();
            });

        list.querySelectorAll('[data-cart-whatsapp]').forEach(
            (button) => button.addEventListener(
                'click',
                () => {
                    const currentCart = read(CART_KEY);

                    const chosen = currentCart.filter(
                        (item) =>
                            item.selected !== false
                    );

                    const number = document.querySelector(
                        '[data-store-page="cart"]'
                    )?.dataset.waNumber || '';

                    if (!chosen.length) {
                        return notify(
                            'Pilih minimal satu produk terlebih dahulu.'
                        );
                    }

                    if (!number) {
                        return notify(
                            'Nomor WhatsApp admin belum diatur.'
                        );
                    }

                    const chosenTotal = chosen.reduce(
                        (sum, item) =>
                            sum
                            + Number(item.price || 0)
                            * Number(item.quantity || 1),
                        0
                    );

                    const lines = chosen.map(
                        (item, index) =>
                            `${index + 1}. ${item.name}\n`
                            + `   ${item.quantity} x ${money(item.price)}`
                            + ` = ${money(
                                Number(item.price)
                                * Number(item.quantity)
                            )}`
                    );

                    const message =
                        `Halo Admin Karya Ide Edi, saya ingin memesan produk berikut:\n\n`
                        + `${lines.join('\n\n')}\n\n`
                        + `Total perkiraan: ${money(chosenTotal)}\n\n`
                        + `Mohon dibantu cek ketersediaan dan proses selanjutnya.`;

                    window.open(
                        `https://wa.me/${number}?text=${encodeURIComponent(message)}`,
                        '_blank',
                        'noopener'
                    );

                    // Hanya item yang dicentang yang keluar dari keranjang.
                    write(
                        CART_KEY,
                        currentCart.filter(
                            (item) =>
                                item.selected === false
                        )
                    );

                    syncBadges();
                    renderCart();

                    notify(
                        'Produk terpilih diteruskan ke WhatsApp.'
                    );
                }
            )
        );
    }
    function updateCartQty(id, delta) {
        const cart = read(CART_KEY);

        const item = cart.find(
            (entry) => Number(entry.id) === id
        );

        if (!item) return;

        const maxQty = Math.max(
            1,
            Math.min(
                99,
                Number(item.stock || 99)
            )
        );

        item.quantity = Math.max(
            1,
            Math.min(
                maxQty,
                Number(item.quantity || 1) + delta
            )
        );

        write(CART_KEY, cart);

        syncBadges();
        renderCart();
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

/**
 * ==========================================================
 * VIDEO "KENAPA PILIH KAMI" (Beranda) -- autoplay saat di-scroll
 * ==========================================================
 * Dipakai oleh partials/frontend/expertise.blade.php. Didaftarkan
 * lewat 'alpine:init' seperti numberStepper di atas (bukan tag
 * <script> biasa) supaya tidak kena race condition Alpine.start()
 * yang sama.
 *
 * Perilaku yang diminta:
 * - Video otomatis play + bersuara begitu section ini masuk layar
 *   (>=50% terlihat), tanpa tombol play.
 * - Saat pengunjung scroll MELEWATI section (keluar layar), suara
 *   meredup pelan (fade volume 1 -> 0, ~800ms) baru videonya pause
 *   -- bukan berhenti mendadak.
 * - Tombol speaker manual di kiri-bawah video tetap tersedia dan
 *   dihormati (kalau pengunjung sudah mematikan suara sendiri,
 *   video tidak akan otomatis dibunyikan lagi saat scroll balik).
 *
 * CATATAN PENTING (keterbatasan browser, bukan bug):
 * Chrome/Safari/Firefox memblokir autoplay video BERSUARA kalau
 * pengunjung belum pernah berinteraksi apapun dengan halaman (klik,
 * scroll dianggap bukan interaksi yang cukup di sebagian browser).
 * Ini kebijakan keamanan browser, tidak bisa dilewati dari kode
 * manapun. Kalau itu terjadi, kode di bawah otomatis fallback ke
 * video jalan dalam kondisi mute (bukan error/patah) -- pengunjung
 * tinggal klik tombol speaker sekali untuk membunyikannya, dan
 * setelah interaksi pertama itu section berikutnya akan otomatis
 * bersuara.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('keahlianVideoPlayer', () => ({
        muted: true,
        manuallyMuted: false,
        fadeTimer: null,

        init() {
            const video = this.$refs.video;
            if (!video) return;

            new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this.enter(video);
                } else {
                    this.exit(video);
                }
            }, { threshold: 0.5 }).observe(this.$el);
        },

        enter(video) {
            clearInterval(this.fadeTimer);
            video.volume = 1;
            video.muted = true;
            this.muted = true;
            video.play().catch(() => {});
        },

        exit(video) {
            clearInterval(this.fadeTimer);

            if (video.muted || video.volume === 0) {
                video.pause();
                return;
            }

            const steps = 20;
            let step = 0;
            this.fadeTimer = setInterval(() => {
                step += 1;
                video.volume = Math.max(0, 1 - step / steps);
                if (step >= steps) {
                    clearInterval(this.fadeTimer);
                    video.pause();
                }
            }, 40); // 20 x 40ms = ~800ms
        },

        toggleMute() {
            const video = this.$refs.video;
            if (!video) return;

            this.manuallyMuted = !this.muted;
            this.muted = this.manuallyMuted;
            video.muted = this.muted;

            if (!this.muted) {
                video.volume = 1;
                video.play().catch(() => {});
            }
        },
    }));

    /**
     * Versi YouTube dari player di atas -- pakai YouTube IFrame
     * postMessage API (butuh enablejsapi=1 di embed_url, sudah
     * diset dari App\Models\HomeSection::classifyVideoUrl()).
     * Iframe baru diisi src-nya saat section masuk layar (supaya
     * video tidak ikut ter-load kalau pengunjung tidak sampai
     * scroll ke sana).
     */
    Alpine.data('keahlianYoutubePlayer', (embedUrl) => ({
        muted: true,
        manuallyMuted: false,
        loaded: false,
        fadeTimer: null,

        init() {
            new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this.enter();
                } else {
                    this.exit();
                }
            }, { threshold: 0.5 }).observe(this.$el);
        },

        command(func, args = []) {
            const iframe = this.$refs.iframe;
            if (!iframe || !iframe.contentWindow) return;
            iframe.contentWindow.postMessage(JSON.stringify({ event: 'command', func, args }), '*');
        },

        enter() {
            clearInterval(this.fadeTimer);

            if (!this.loaded) {
                this.loaded = true;

                // Selalu mulai SENYAP -- kebijakan autoplay browser cuma
                // mengizinkan video autoplay kalau videonya mute. Kalau
                // langsung minta mute=0 di sini, browser diam-diam MENOLAK
                // autoplay-nya sama sekali (video berhenti di posisi
                // "cued"/layar hitam, tidak ada fallback otomatis seperti
                // tag <video> biasa).
                this.$refs.iframe.src = embedUrl + '&autoplay=1&mute=1';

                if (!this.manuallyMuted) {
                    // Kita tidak memuat skrip resmi iframe_api Google (biar
                    // ringan), jadi tidak ada event "player sudah siap" yang
                    // bisa didengar -- pakai jeda singkat sebagai perkiraan
                    // aman sebelum mencoba membunyikan otomatis.
                    setTimeout(() => {
                        if (!this.manuallyMuted && this.loaded) {
                            this.command('unMute');
                            this.command('setVolume', [100]);
                            this.muted = false;
                        }
                    }, 600);
                }

                return;
            }

            this.command('playVideo');
            if (!this.manuallyMuted) {
                this.command('unMute');
                this.command('setVolume', [100]);
                this.muted = false;
            }
        },

        exit() {
            clearInterval(this.fadeTimer);
            if (!this.loaded || this.muted) {
                this.command('pauseVideo');
                return;
            }

            const steps = 20;
            let step = 0;
            this.fadeTimer = setInterval(() => {
                step += 1;
                this.command('setVolume', [Math.max(0, 100 - Math.round((100 * step) / steps))]);
                if (step >= steps) {
                    clearInterval(this.fadeTimer);
                    this.command('pauseVideo');
                }
            }, 40);
        },

        toggleMute() {
            this.manuallyMuted = !this.muted;
            this.muted = this.manuallyMuted;
            this.command(this.muted ? 'mute' : 'unMute');
            if (!this.muted) {
                this.command('setVolume', [100]);
                this.command('playVideo');
            }
        },
    }));
});

import './dokumentasi-video.js';
import './dokumentasi-galeri.js';
