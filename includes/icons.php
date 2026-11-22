<?php
/** Ikon garis (UI) dan ilustrasi dekoratif bertema ibu hamil dan bayi. */

function icon(string $nama, int $ukuran = 22, string $kelas = ''): string
{
    static $ikon = [
        'home'      => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h4"/>',
        'heart'     => '<path d="M12 20s-7-4.4-7-10a4 4 0 017-2.6A4 4 0 0119 10c0 5.6-7 10-7 10z"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14.5c3 0 5 1.6 5 4.5"/>',
        'bell'      => '<path d="M6 16V11a6 6 0 0112 0v5l2 2H4z"/><path d="M10 21h4"/>',
        'chev-r'    => '<path d="M9 6l6 6-6 6"/>',
        'chev-l'    => '<path d="M15 6l-6 6 6 6"/>',
        'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'alert'     => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18v.5"/>',
        'pin'       => '<path d="M12 21s7-6 7-11a7 7 0 10-14 0c0 5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone'     => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',
        'lock'      => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
        'logout'    => '<path d="M10 4H5v16h5"/><path d="M15 8l4 4-4 4M19 12H9"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'stetho'    => '<path d="M6 3v6a4 4 0 008 0V3"/><path d="M10 13v2a5 5 0 0010 0v-2"/><circle cx="20" cy="11" r="2"/>',
        'download'  => '<path d="M12 4v11M7 11l5 5 5-5M5 20h14"/>',
        'print'     => '<path d="M7 9V3h10v6"/><rect x="4" y="9" width="16" height="9" rx="2"/><path d="M7 14h10v7H7z"/>',
        'share'     => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.2 10.8l7.6-3.6M8.2 13.2l7.6 3.6"/>',
        'baby'      => '<circle cx="12" cy="11" r="7"/><path d="M9.5 11.5v.01M14.5 11.5v.01M10 14.5c1.2 1 2.8 1 4 0"/><path d="M12 4c1.5 0 2.5 1 2.5 2"/>',
    ];
    $isi = $ikon[$nama] ?? '';
    return '<svg class="ic ' . e($kelas) . '" width="' . $ukuran . '" height="' . $ukuran
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $isi . '</svg>';
}

/** Ilustrasi dekoratif. Warna mengikuti `color` CSS (currentColor). */
function deco(string $nama, int $ukuran = 64, string $kelas = ''): string
{
    $buka  = '<svg class="deco ' . e($kelas) . '" width="' . $ukuran . '" height="' . $ukuran . '" viewBox="0 0 64 64" aria-hidden="true">';
    $tutup = '</svg>';
    switch ($nama) {
        case 'kaki':
            $kaki = '<ellipse cx="0" cy="7" rx="8" ry="13"/><circle cx="-8" cy="-9" r="3.2"/><circle cx="-3" cy="-13" r="3"/><circle cx="2.5" cy="-14" r="2.8"/><circle cx="7.5" cy="-11.5" r="2.5"/><circle cx="10.5" cy="-6.5" r="2.1"/>';
            return $buka . '<g fill="currentColor"><g transform="translate(21 38) rotate(-12)">' . $kaki . '</g><g transform="translate(44 30) rotate(12)">' . $kaki . '</g></g>' . $tutup;
        case 'botol':
            return $buka . '<g fill="currentColor"><path d="M28 6c0-3 8-3 8 0v6h-8z"/><rect x="23" y="12" width="18" height="7" rx="2.5"/><rect x="21" y="20" width="22" height="38" rx="7"/></g>'
                . '<g stroke="#fff" stroke-width="2.4" stroke-linecap="round"><path d="M27 31h10M27 39h6M27 47h10"/></g>' . $tutup;
        case 'dot':
            return $buka . '<circle cx="32" cy="13" r="8" fill="currentColor"/><ellipse cx="32" cy="31" rx="23" ry="11" fill="currentColor"/>'
                . '<circle cx="32" cy="31" r="4" fill="#fff"/><circle cx="32" cy="51" r="7" fill="none" stroke="currentColor" stroke-width="4"/>' . $tutup;
        case 'kereta':
            return $buka . '<g fill="currentColor"><path d="M8 30h38c0 12-9 19-21 19S8 42 8 30z"/><path d="M8 30C8 17 20 11 32 13v17z"/><circle cx="19" cy="55" r="5"/><circle cx="40" cy="55" r="5"/></g>'
                . '<path d="M46 30l8-15h5" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>' . $tutup;
        case 'bintang':
            return $buka . '<path fill="currentColor" d="M32 6l7.4 15.6 17 2.2-12.4 11.9 3.1 16.9L32 44.4 16.9 52.6 20 35.7 7.6 23.8l17-2.2z"/>' . $tutup;
        case 'hati':
            return $buka . '<path fill="currentColor" d="M32 56S6 40 6 22a13 13 0 0126-4 13 13 0 0126 4c0 18-26 34-26 34z"/>' . $tutup;
        case 'bulan':
            return $buka . '<path fill="currentColor" d="M42 8a24 24 0 1014 36A20 20 0 0142 8z"/>' . $tutup;
    }
    return '';
}

/** Ilustrasi ibu hamil (tampak depan) memeluk perutnya. */
function ilustrasi_ibu(string $kelas = ''): string
{
    return '<svg class="ilustrasi ' . e($kelas) . '" viewBox="0 0 240 290" role="img" aria-label="Ilustrasi ibu hamil memeluk perutnya">'
        . '<path d="M82 74C78 30 162 30 158 74c2 26-8 42-8 42H90s-10-16-8-42z" fill="#4A1D3F"/>'
        . '<rect x="108" y="94" width="24" height="24" rx="8" fill="#F2B79A"/>'
        . '<path d="M80 122c20-12 60-12 80 0 12 32 18 98 26 156H54c8-58 14-124 26-156z" fill="#FF4DA6"/>'
        . '<ellipse cx="120" cy="196" rx="47" ry="50" fill="#FF84C0"/>'
        . '<path d="M120 216c-22-14-18-33-7-31 4 1 7 4 7 7 0-3 3-6 7-7 11-2 15 17-7 31z" fill="#fff"/>'
        . '<path d="M86 128c-27 36-14 88 24 100" fill="none" stroke="#F6C9AE" stroke-width="16" stroke-linecap="round"/>'
        . '<path d="M154 128c27 36 14 88-24 100" fill="none" stroke="#F6C9AE" stroke-width="16" stroke-linecap="round"/>'
        . '<circle cx="120" cy="72" r="31" fill="#F6C9AE"/>'
        . '<path d="M88 68c0-30 64-30 64 0-10-15-54-15-64 0z" fill="#4A1D3F"/>'
        . '<circle cx="120" cy="36" r="13" fill="#4A1D3F"/>'
        . '<path d="M104 74q5 5 10 0M126 74q5 5 10 0" fill="none" stroke="#4A1D3F" stroke-width="3" stroke-linecap="round"/>'
        . '<path d="M112 86q8 8 16 0" fill="none" stroke="#C2185B" stroke-width="3" stroke-linecap="round"/>'
        . '<circle cx="101" cy="84" r="5" fill="#FF9FCB" opacity=".7"/><circle cx="139" cy="84" r="5" fill="#FF9FCB" opacity=".7"/>'
        . '<path fill="#FFD3EA" d="M30 70l3 7 7 3-7 3-3 7-3-7-7-3 7-3zM205 112l2.5 6 6 2.5-6 2.5-2.5 6-2.5-6-6-2.5 6-2.5z"/>'
        . '</svg>';
}
