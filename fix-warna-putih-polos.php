<?php

use App\Models\HomeSection;

$targets = ['header', 'sejak-berdiri', 'testimoni'];

foreach ($targets as $key) {
    $section = HomeSection::where('section_key', $key)->first();

    if (! $section) {
        echo $key.' => TIDAK DITEMUKAN, dilewati'.PHP_EOL;

        continue;
    }

    $data = $section->data;
    $before = $data['bg_color'] ?? null;
    $data['bg_color'] = '#FFFFFF';
    $section->data = $data;
    $section->save();

    echo $key.' => diubah dari '.($before ?? 'NULL').' ke #FFFFFF'.PHP_EOL;
}
