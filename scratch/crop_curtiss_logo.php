<?php
$file = 'C:/Users/Susara Senaratne/.gemini/antigravity-ide/brain/d0340d8e-f620-44f2-8f22-9c759c898754/.user_uploaded/media_1792565568214.png';
$im = imagecreatefrompng($file);
$w = imagesx($im);
$h = imagesy($im);

// Let's find columns with non-transparent pixels
$cols = [];
for ($x = 0; $x < $w; $x++) {
    $hasColor = false;
    for ($y = 0; $y < $h; $y++) {
        $rgba = imagecolorat($im, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;
        if ($alpha < 120) {
            $hasColor = true;
            break;
        }
    }
    $cols[$x] = $hasColor ? 1 : 0;
}

// Find segments of colored pixels
$segments = [];
$inSeg = false;
$start = 0;
for ($x = 0; $x < $w; $x++) {
    if ($cols[$x] && !$inSeg) {
        $inSeg = true;
        $start = $x;
    } elseif (!$cols[$x] && $inSeg) {
        $inSeg = false;
        $segments[] = [$start, $x - 1];
    }
}
if ($inSeg) {
    $segments[] = [$start, $w - 1];
}

print_r($segments);
