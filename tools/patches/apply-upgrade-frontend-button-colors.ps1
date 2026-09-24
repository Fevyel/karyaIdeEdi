$ErrorActionPreference = 'Stop'

function Step($text) { Write-Host "`n$text" -ForegroundColor Cyan }

if (-not (Test-Path '.\artisan')) {
    throw "File artisan tidak ditemukan. Jalankan script dari root project C:\xampp\htdocs\karyaIdeEdi"
}

Write-Host '=============================================================' -ForegroundColor Yellow
Write-Host ' Upgrade Warna Tombol Frontend - Premium Furniture UI' -ForegroundColor Yellow
Write-Host '=============================================================' -ForegroundColor Yellow

$cssPath = '.\resources\css\app.css'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "$cssPath.bak-button-colors-$stamp"

Step '[1/4] Backup app.css ...'
Copy-Item $cssPath $backup -Force
Write-Host "  Backup: $backup" -ForegroundColor DarkGray

Step '[2/4] Terapkan sistem warna tombol frontend ...'
$content = Get-Content $cssPath -Raw
$startMarker = '/* KIE-FRONTEND-BUTTON-SYSTEM:START */'
$endMarker   = '/* KIE-FRONTEND-BUTTON-SYSTEM:END */'

$block = @'
/* KIE-FRONTEND-BUTTON-SYSTEM:START */
/*
   Sistem warna tombol frontend Karya Ide Edi.
   Scope HANYA html[data-site='frontend'] agar Admin Panel tidak berubah.
   Tujuan: palet furniture premium yang konsisten tanpa mengubah ukuran,
   radius, layout, atau fungsi tombol yang sudah fix.
*/
html[data-site='frontend'] {
    --kie-btn-espresso-1: #5B3925;
    --kie-btn-espresso-2: #2B1A12;
    --kie-btn-espresso-hover-1: #6A4630;
    --kie-btn-espresso-hover-2: #352017;

    --kie-btn-copper-1: #C58B4B;
    --kie-btn-copper-2: #99602F;
    --kie-btn-copper-hover-1: #D09A5B;
    --kie-btn-copper-hover-2: #A96C38;

    --kie-btn-cream-1: #FFFDF9;
    --kie-btn-cream-2: #F4E9DA;
    --kie-btn-cream-border: #DFCEBA;

    --kie-btn-green-1: #319962;
    --kie-btn-green-2: #1F7449;
    --kie-btn-green-hover-1: #39A96D;
    --kie-btn-green-hover-2: #245F43;

    --kie-btn-focus: rgba(197, 139, 75, .38);
    --kie-btn-shadow: 0 12px 28px -18px rgba(53, 32, 23, .72);
    --kie-btn-shadow-hover: 0 18px 36px -18px rgba(53, 32, 23, .82);
}

/* ----------------------------------------------------------
   PRIMARY / ESPRESSO
   Tombol utama gelap: search, cart, filter, empty-state CTA,
   back-to-top, dan tombol gelap lain yang sudah ada.
   ---------------------------------------------------------- */
html[data-site='frontend'] :is(a, button)[class~='bg-admin-panel'],
html[data-site='frontend'] :is(a, button)[class~='bg-[#2A211B]'],
html[data-site='frontend'] :is(a, button)[class~='bg-[#2A211B]/95'],
html[data-site='frontend'] :is(a, button)[class~='bg-[#211B17]'],
html[data-site='frontend'] :is(a, button)[class~='bg-[#2A1B12]'] {
    background-image: linear-gradient(135deg, var(--kie-btn-espresso-1), var(--kie-btn-espresso-2)) !important;
    background-color: var(--kie-btn-espresso-2) !important;
    color: #FFF9F2 !important;
    border-color: rgba(221, 190, 151, .18) !important;
    box-shadow: var(--kie-btn-shadow);
}

html[data-site='frontend'] :is(a, button)[class~='bg-admin-panel']:hover,
html[data-site='frontend'] :is(a, button)[class~='bg-[#2A211B]']:hover,
html[data-site='frontend'] :is(a, button)[class~='bg-[#2A211B]/95']:hover,
html[data-site='frontend'] :is(a, button)[class~='bg-[#211B17]']:hover,
html[data-site='frontend'] :is(a, button)[class~='bg-[#2A1B12]']:hover {
    background-image: linear-gradient(135deg, var(--kie-btn-espresso-hover-1), var(--kie-btn-espresso-hover-2)) !important;
    color: #FFFFFF !important;
    box-shadow: var(--kie-btn-shadow-hover);
}

/* ----------------------------------------------------------
   ACCENT / COPPER
   Menggantikan aksen oranye generik dengan warna copper-bronze
   yang lebih sesuai furnitur premium.
   ---------------------------------------------------------- */
html[data-site='frontend'] :is(a, button)[class~='bg-admin-accent'],
html[data-site='frontend'] :is(a, button)[class~='bg-[#FF6600]'] {
    background-image: linear-gradient(135deg, var(--kie-btn-copper-1), var(--kie-btn-copper-2)) !important;
    background-color: var(--kie-btn-copper-2) !important;
    color: #FFFFFF !important;
    border-color: rgba(111, 70, 39, .18) !important;
    box-shadow: 0 12px 28px -18px rgba(135, 79, 37, .72);
}

html[data-site='frontend'] :is(a, button)[class~='bg-admin-accent']:hover,
html[data-site='frontend'] :is(a, button)[class~='bg-[#FF6600]']:hover {
    background-image: linear-gradient(135deg, var(--kie-btn-copper-hover-1), var(--kie-btn-copper-hover-2)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 18px 36px -18px rgba(135, 79, 37, .82);
}

/* ----------------------------------------------------------
   WHATSAPP / SUCCESS
   Tetap hijau agar identitas WhatsApp terbaca, tapi dibuat lebih
   dalam dan tidak neon.
   ---------------------------------------------------------- */
html[data-site='frontend'] :is(a, button)[class~='bg-[#52B747]'] {
    background-image: linear-gradient(135deg, var(--kie-btn-green-1), var(--kie-btn-green-2)) !important;
    background-color: var(--kie-btn-green-2) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 28px -18px rgba(24, 100, 62, .62);
}

html[data-site='frontend'] :is(a, button)[class~='bg-[#52B747]']:hover {
    background-image: linear-gradient(135deg, var(--kie-btn-green-hover-1), var(--kie-btn-green-hover-2)) !important;
    box-shadow: 0 18px 36px -18px rgba(24, 100, 62, .74);
}

/* ----------------------------------------------------------
   OUTLINE / SECONDARY
   Outline aksen dibuat warm brass, hover menjadi cream lembut.
   ---------------------------------------------------------- */
html[data-site='frontend'] :is(a, button)[class~='border-admin-accent/30'],
html[data-site='frontend'] :is(a, button)[class~='border-admin-accent'] {
    border-color: #C69A64 !important;
    color: #8A552C !important;
    background-color: rgba(255, 253, 249, .78);
}

html[data-site='frontend'] :is(a, button)[class~='border-admin-accent/30']:hover,
html[data-site='frontend'] :is(a, button)[class~='border-admin-accent']:hover {
    border-color: #B57A40 !important;
    background-image: linear-gradient(135deg, #FFFDF9, #F3E5D4) !important;
    color: #654021 !important;
}

/* Filter/tracking secondary button yang memakai border krem. */
html[data-site='frontend'] :is(a, button)[class~='border-[#E8DED1]'] {
    border-color: var(--kie-btn-cream-border) !important;
    background-image: linear-gradient(135deg, var(--kie-btn-cream-1), var(--kie-btn-cream-2));
    color: #5B4637 !important;
}
html[data-site='frontend'] :is(a, button)[class~='border-[#E8DED1]']:hover {
    border-color: #C6A77F !important;
    background-image: linear-gradient(135deg, #FFFDF9, #EEDCC5) !important;
    color: #3A281D !important;
}

/* ----------------------------------------------------------
   ICON ACTIONS
   Favorite tetap terang; cart tetap primary. Tidak mengubah ukuran.
   ---------------------------------------------------------- */
html[data-site='frontend'] button[data-favorite-product] {
    background-image: linear-gradient(145deg, #FFFFFF, #F7EEE3) !important;
    border-color: #E2D1BD !important;
    color: #6A5140 !important;
    box-shadow: 0 8px 20px -16px rgba(50, 31, 21, .7);
}
html[data-site='frontend'] button[data-favorite-product]:hover {
    background-image: linear-gradient(145deg, #FFFDF8, #ECD8BF) !important;
    border-color: #C79255 !important;
    color: #9A612F !important;
}

/* Tombol transparan di lightbox/video dibuat warm glass, bukan abu generik. */
html[data-site='frontend'] button[class~='bg-white/10'],
html[data-site='frontend'] button[class~='bg-black/50'] {
    background-color: rgba(54, 35, 24, .56) !important;
    border-color: rgba(240, 216, 184, .2) !important;
    color: #FFF9F2 !important;
    backdrop-filter: blur(10px);
}
html[data-site='frontend'] button[class~='bg-white/10']:hover,
html[data-site='frontend'] button[class~='bg-black/50']:hover {
    background-color: rgba(104, 68, 44, .72) !important;
}

/* Quantity +/-: hanya warna hover, struktur tetap sama. */
html[data-site='frontend'] button[data-quantity-minus],
html[data-site='frontend'] button[data-quantity-plus] {
    color: #715540 !important;
}
html[data-site='frontend'] button[data-quantity-minus]:hover,
html[data-site='frontend'] button[data-quantity-plus]:hover {
    background-color: #F4E8D9 !important;
    color: #3D291D !important;
}

/* Aksen tab lama yang masih memakai orange #F28A22. */
html[data-site='frontend'] :is(button, a)[class~='text-[#F28A22]'] {
    color: #B8793E !important;
}
html[data-site='frontend'] :is(button, a)[class~='border-[#F28A22]'] {
    border-color: #B8793E !important;
}

/* Focus keyboard yang konsisten dan terlihat profesional. */
html[data-site='frontend'] :is(a, button):focus-visible {
    outline: 2px solid transparent;
    box-shadow: 0 0 0 3px var(--kie-btn-focus), var(--kie-btn-shadow);
}

/* Disabled tetap terbaca, tanpa mengubah fungsi existing. */
html[data-site='frontend'] button:disabled,
html[data-site='frontend'] button[disabled] {
    filter: saturate(.65);
}
/* KIE-FRONTEND-BUTTON-SYSTEM:END */
'@

if ($content.Contains($startMarker) -and $content.Contains($endMarker)) {
    $pattern = '(?s)/\* KIE-FRONTEND-BUTTON-SYSTEM:START \*/.*?/\* KIE-FRONTEND-BUTTON-SYSTEM:END \*/'
    $content = [regex]::Replace($content, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $block }, 1)
    Write-Host '  Sistem tombol lama ditemukan -> diperbarui.' -ForegroundColor DarkGray
} else {
    $content = $content.TrimEnd() + "`r`n`r`n" + $block + "`r`n"
    Write-Host '  Sistem warna tombol frontend ditambahkan.' -ForegroundColor DarkGray
}

Set-Content -Path $cssPath -Value $content -Encoding UTF8

Step '[3/4] Bersihkan cache view ...'
php artisan view:clear | Out-Host

Step '[4/4] Selesai ...'
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host 'Yang diubah hanya resources\css\app.css dan hanya scope FRONTEND.' -ForegroundColor Green
Write-Host 'Admin Panel tidak disentuh.' -ForegroundColor Green
Write-Host ''
Write-Host 'Jalankan setelah ini:' -ForegroundColor Yellow
Write-Host '  npm run build' -ForegroundColor White
