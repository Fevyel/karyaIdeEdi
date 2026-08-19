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
