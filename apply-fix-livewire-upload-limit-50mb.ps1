<#
  Fix: upload video di "Kenapa Pilih Kami" > Video (Upload Perangkat) selalu
  gagal ("Pilih file video dari perangkat terlebih dahulu") walau file sudah
  dipilih, kalau ukurannya di atas 12MB.

  Sebabnya: Livewire punya batas upload BAWAAN 12MB (terpisah dari validasi
  50MB yang sudah kamu atur di app\Livewire komponen Edit Web). Batas 12MB
  ini aktif duluan, di level upload sementara Livewire sendiri, SEBELUM
  validasi 50MB kamu sempat jalan -- jadi file 12MB-50MB gagal diam-diam.

  Fix-nya: publish config/livewire.php (file ini BELUM ADA di project kamu
  sebelumnya -- ini menambah file baru, TIDAK mengubah file lain yang sudah
  ada) dan naikkan limitnya jadi 50MB, disamakan dengan yang sudah kamu
  janjikan di form.

  CATATAN: PHP/XAMPP sendiri juga punya batas upload_max_filesize &
  post_max_size di php.ini. Kalau setelah ini masih gagal untuk file besar,
  itu batas PHP-nya yang perlu dinaikkan juga (lihat pesan di akhir script).

  Cara pakai (dari terminal VS Code, di root project C:\xampp\htdocs\karyaIdeEdi):
    .\apply-fix-livewire-upload-limit-50mb.ps1
#>

$ErrorActionPreference = 'Stop'

$relativePath = 'config\livewire.php'
$path = Join-Path $PSScriptRoot $relativePath

if (Test-Path $path) {
    Write-Host "File sudah ada: $relativePath -- tidak ditimpa, cek manual dulu isinya." -ForegroundColor Yellow
    exit 1
}

$content = @'
<?php

return [

    'component_locations' => [
        resource_path('views/components'),
        resource_path('views/livewire'),
    ],

    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
        'pages' => resource_path('views/pages'),
    ],

    'component_layout' => 'layouts::app',

    'component_placeholder' => null,

    'make_command' => [
        'type' => 'sfc',
        'emoji' => true,
        'with' => [
            'js' => false,
            'css' => false,
            'test' => false,
        ],
    ],

    'class_namespace' => 'App\\Livewire',

    'class_path' => app_path('Livewire'),

    'view_path' => resource_path('views/livewire'),

    /*
    |---------------------------------------------------------------------------
    | Temporary File Uploads
    |---------------------------------------------------------------------------
    |
    | Dinaikkan dari default Livewire (12MB) jadi 50MB (51200 KB), disamakan
    | dengan validasi 'max:51200' di saveKeahlian() / edit-web.blade.php,
    | supaya upload video "Kenapa Pilih Kami" tidak gagal diam-diam untuk
    | file 12MB-50MB.
    |
    */

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', 'max:51200'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

    'render_on_redirect' => false,

    'legacy_model_binding' => false,

    'inject_assets' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    'inject_morph_markers' => true,

    'smart_wire_keys' => true,

    'pagination_theme' => 'tailwind',

    'release_token' => 'a',

    'csp_safe' => false,

    'payload' => [
        'max_size' => 1024 * 1024,
        'max_nesting_depth' => 10,
        'max_calls' => 50,
        'max_components' => 200,
    ],
];
'@

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $content, $utf8NoBom)

Write-Host "OK   : dibuat $relativePath (limit upload Livewire 12MB -> 50MB)" -ForegroundColor Green
Write-Host ""
Write-Host "Langkah selanjutnya:" -ForegroundColor Cyan
Write-Host "  1. php artisan config:clear"
Write-Host "  2. Buka C:\xampp\php\php.ini, cari 'upload_max_filesize' dan 'post_max_size'."
Write-Host "     Kalau nilainya di bawah 50M, naikkan berdua jadi 60M (kasih buffer)."
Write-Host "     Contoh: upload_max_filesize = 60M , post_max_size = 60M"
Write-Host "  3. Restart Apache dari XAMPP Control Panel (Stop lalu Start lagi) -- WAJIB setelah edit php.ini."
Write-Host "  4. Hard refresh browser, coba upload video yang tadi gagal lagi."
