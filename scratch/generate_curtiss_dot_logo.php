<?php
$file = 'C:/Users/Susara Senaratne/.gemini/antigravity-ide/brain/d0340d8e-f620-44f2-8f22-9c759c898754/.user_uploaded/media_1792565568214.png';
$im = imagecreatefrompng($file);

// Find Y bounds for curtiss (x: 81 to 336)
$minY = 9999;
$maxY = 0;
for ($x = 81; $x <= 336; $x++) {
    for ($y = 0; $y < imagesy($im); $y++) {
        $rgba = imagecolorat($im, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;
        if ($alpha < 120) {
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}
$curtissHeight = $maxY - $minY + 1;
$curtissWidth = 336 - 81 + 1;

// Find bounds for the red dot (x: 517 to 525)
$dotMinY = 9999;
$dotMaxY = 0;
for ($x = 517; $x <= 525; $x++) {
    for ($y = 0; $y < imagesy($im); $y++) {
        $rgba = imagecolorat($im, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;
        if ($alpha < 120) {
            if ($y < $dotMinY) $dotMinY = $y;
            if ($y > $dotMaxY) $dotMaxY = $y;
        }
    }
}
$dotHeight = $dotMaxY - $dotMinY + 1;
$dotWidth = 525 - 517 + 1;

// Spacing between 's' and dot
$gap = 10;
$totalW = $curtissWidth + $gap + $dotWidth + 4;
$totalH = $curtissHeight + 4;

// 1. Transparent PNG
$outPng = imagecreatetruecolor($totalW, $totalH);
imagealphablending($outPng, false);
imagesavealpha($outPng, true);
$transparent = imagecolorallocatealpha($outPng, 255, 255, 255, 127);
imagefilledrectangle($outPng, 0, 0, $totalW, $totalH, $transparent);
imagealphablending($outPng, true);

// Copy "curtiss"
imagecopy($outPng, $im, 2, 2, 81, $minY, $curtissWidth, $curtissHeight);

// Copy dot (vertically aligned with where it was relative to baseline)
$dotOffsetY = 2 + ($dotMinY - $minY);
imagecopy($outPng, $im, 2 + $curtissWidth + $gap, $dotOffsetY, 517, $dotMinY, $dotWidth, $dotHeight);

imagealphablending($outPng, false);
imagesavealpha($outPng, true);
imagepng($outPng, __DIR__ . '/../public/assets/images/logo.png');

// 2. High-quality white-canvas JPEG (for zero-GD Dompdf)
$outJpg = imagecreatetruecolor($totalW, $totalH);
$white = imagecolorallocate($outJpg, 255, 255, 255);
imagefilledrectangle($outJpg, 0, 0, $totalW, $totalH, $white);
imagecopy($outJpg, $outPng, 0, 0, 0, 0, $totalW, $totalH);
imagejpeg($outJpg, __DIR__ . '/../public/assets/images/logo.jpg', 100);

imagedestroy($im);
imagedestroy($outPng);
imagedestroy($outJpg);

echo "Curtiss. logo created: {$totalW}x{$totalH}\n";
