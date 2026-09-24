<?php

use App\Models\HomeSection;

$sections = HomeSection::whereIn('section_key', ['header', 'sejak-berdiri', 'produk-unggulan', 'kategori', 'testimoni', 'lokasi'])->get(['section_key', 'data']);

foreach ($sections as $s) {
    $d = $s->data;
    $bg = $d['bg_color'] ?? 'NULL (pakai warna bawaan section)';
    echo $s->section_key.' => bg_color: '.$bg.PHP_EOL;

    if ($s->section_key === 'testimoni') {
        echo '   card_colors: '.json_encode($d['card_colors'] ?? null).PHP_EOL;
    }
}
