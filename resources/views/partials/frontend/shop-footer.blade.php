@php
    $shopFooterSetting = \App\Models\Setting::current();
    $shopFooterCategories = \App\Models\Category::active()->ordered()->take(5)->get(['slug', 'name']);

    $shopFooterWa = $shopFooterSetting->whatsapp
        ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $shopFooterSetting->whatsapp)
        : '#';
@endphp

{{-- Feature strip --}}
<section class="bg-[#FEEDD8]">
    <div class="mx-auto grid max-w-[1180px] grid-cols-1 gap-8 px-5 py-16 sm:grid-cols-3 sm:gap-10 sm:px-7 lg:px-8 lg:py-20">
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center bg-[#FFF3E4] text-[#8C6A45]">
                <i class="fa-solid fa-truck-fast text-base"></i>
            </span>
            <div>
                <p class="text-sm font-semibold text-[#2A211B]">Free Shipping</p>
                <p class="mt-1 text-xs text-[#9A8B7B]">Gratis ongkir untuk pesanan tertentu</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center bg-[#FFF3E4] text-[#8C6A45]">
                <i class="fa-regular fa-credit-card text-base"></i>
            </span>
            <div>
                <p class="text-sm font-semibold text-[#2A211B]">Flexible Payment</p>
                <p class="mt-1 text-xs text-[#9A8B7B]">Pilihan pembayaran yang aman</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center bg-[#FFF3E4] text-[#8C6A45]">
                <i class="fa-solid fa-headset text-base"></i>
            </span>
            <div>
                <p class="text-sm font-semibold text-[#2A211B]">24/7 Support</p>
                <p class="mt-1 text-xs text-[#9A8B7B]">Kami siap membantu kebutuhan Anda</p>
            </div>
        </div>
    </div>
</section>

<footer class="bg-[#1A1A1A] text-white">
    <div class="mx-auto max-w-[1180px] px-5 py-14 sm:px-7 lg:px-8 lg:py-16">
        <div class="grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-16">

            <div>
                <a href="{{ route('home') }}" class="font-display text-2xl font-semibold tracking-tight text-white">
                    {{ $shopFooterSetting->site_name }}
                </a>
                <p class="mt-5 max-w-xs text-xs leading-6 text-white/45">
                    Furniture yang dibuat dengan perhatian pada detail, material, dan kenyamanan untuk ruang yang terasa benar-benar milik Anda.
                </p>
                <div class="mt-6 flex items-center gap-3">
                    <a href="#" aria-label="Instagram" class="text-white/50 transition hover:text-white"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook" class="text-white/50 transition hover:text-white"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="{{ $shopFooterWa }}" target="_blank" rel="noopener" aria-label="WhatsApp" class="text-white/50 transition hover:text-white"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white">Information</p>
                <ul class="mt-5 space-y-3 text-xs text-white/45">
                    <li><a href="#" class="transition hover:text-white">About Us</a></li>
                    <li><a href="#" class="transition hover:text-white">Privacy Policy</a></li>
                    <li><a href="#" class="transition hover:text-white">Terms of Service</a></li>
                    @foreach ($shopFooterCategories->take(2) as $category)
                        <li><a href="{{ route('products.index', ['category' => $category->slug]) }}" class="transition hover:text-white">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white">Customer Service</p>
                <ul class="mt-5 space-y-3 text-xs text-white/45">
                    <li><a href="#" class="transition hover:text-white">Contact Us</a></li>
                    <li><a href="#" class="transition hover:text-white">Returns</a></li>
                    <li><a href="#" class="transition hover:text-white">Order Status</a></li>
                    <li><a href="{{ $shopFooterWa }}" target="_blank" rel="noopener" class="transition hover:text-white">WhatsApp</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white">Newsletter</p>
                <p class="mt-5 text-xs leading-5 text-white/45">Dapatkan kabar terbaru tentang produk dan koleksi kami.</p>
                <form class="mt-5 flex overflow-hidden border border-white/10 bg-white/[0.06]" onsubmit="return false;">
                    <input type="email" placeholder="Enter email" class="min-w-0 flex-1 bg-transparent px-3 py-3 text-xs text-white placeholder:text-white/30 outline-none">
                    <button type="submit" aria-label="Subscribe" class="w-11 shrink-0 bg-white/10 text-white/70 transition hover:bg-white/20 hover:text-white">
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-white/10 pt-6 text-[10px] uppercase tracking-[0.08em] text-white/35 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $shopFooterSetting->site_name }}. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <span>Visa</span>
                <span>Mastercard</span>
                <span>PayPal</span>
            </div>
        </div>
    </div>
</footer>
